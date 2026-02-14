<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Portable Export actions — Admin UI for generating self-contained
 * catalogue viewers for CD/USB/ZIP distribution.
 */
class portableExportActions extends sfActions
{
    protected function loadServices(): void
    {
        static $loaded = false;
        if (!$loaded) {
            $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
            require_once $ahgDir . '/lib/Services/ExportPipelineService.php';
            require_once $ahgDir . '/lib/Services/CatalogueExtractor.php';
            require_once $ahgDir . '/lib/Services/AssetCollector.php';
            require_once $ahgDir . '/lib/Services/SearchIndexBuilder.php';
            require_once $ahgDir . '/lib/Services/ViewerPackager.php';
            $loaded = true;
        }
    }

    protected function requireAdmin(): void
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }
    }

    protected function jsonResponse(array $data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    // ─── Export Form UI ─────────────────────────────────────────────

    public function executeIndex(sfWebRequest $request)
    {
        $this->requireAdmin();

        // Get repositories for the scope selector
        $this->repositories = DB::table('repository')
            ->join('actor_i18n as ai', 'repository.id', '=', 'ai.id')
            ->where('ai.culture', '=', 'en')
            ->orderBy('ai.authorized_form_of_name')
            ->select('repository.id', 'ai.authorized_form_of_name as name')
            ->get()
            ->toArray();

        // Get past exports
        $this->exports = DB::table('portable_export')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    // ─── API: Start Export ──────────────────────────────────────────

    public function executeApiStartExport(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $title = $request->getParameter('title', 'Portable Export');
        $scopeType = $request->getParameter('scope_type', 'all');
        $scopeSlug = $request->getParameter('scope_slug');
        $repositoryId = $request->getParameter('repository_id');
        $mode = $request->getParameter('mode', 'read_only');
        $culture = $request->getParameter('culture', 'en');
        $includeObjects = $request->getParameter('include_objects', '1');
        $includeThumbnails = $request->getParameter('include_thumbnails', '1');
        $includeReferences = $request->getParameter('include_references', '1');
        $includeMasters = $request->getParameter('include_masters', '0');

        // Branding
        $branding = [];
        if ($request->getParameter('branding_title')) {
            $branding['title'] = $request->getParameter('branding_title');
        }
        if ($request->getParameter('branding_subtitle')) {
            $branding['subtitle'] = $request->getParameter('branding_subtitle');
        }
        if ($request->getParameter('branding_footer')) {
            $branding['footer'] = $request->getParameter('branding_footer');
        }

        // Create export record
        $exportId = DB::table('portable_export')->insertGetId([
            'user_id' => (int) $this->getUser()->getAttribute('user_id'),
            'title' => $title,
            'scope_type' => $scopeType,
            'scope_slug' => $scopeSlug ?: null,
            'scope_repository_id' => $repositoryId ? (int) $repositoryId : null,
            'mode' => $mode,
            'include_objects' => $includeObjects ? 1 : 0,
            'include_thumbnails' => $includeThumbnails ? 1 : 0,
            'include_references' => $includeReferences ? 1 : 0,
            'include_masters' => $includeMasters ? 1 : 0,
            'branding' => !empty($branding) ? json_encode($branding) : null,
            'culture' => $culture,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Launch background process
        $atomRoot = sfConfig::get('sf_root_dir');
        $cmd = sprintf(
            'nohup php %s/symfony portable:export --export-id=%d > %s/downloads/portable-exports/export-%d.log 2>&1 &',
            escapeshellarg($atomRoot),
            $exportId,
            $atomRoot,
            $exportId
        );

        // Ensure downloads dir exists
        @mkdir($atomRoot . '/downloads/portable-exports', 0755, true);
        exec($cmd);

        return $this->jsonResponse([
            'success' => true,
            'export_id' => $exportId,
            'message' => 'Export started. Polling for progress...',
        ]);
    }

    // ─── API: Progress Polling ──────────────────────────────────────

    public function executeApiProgress(sfWebRequest $request)
    {
        $this->requireAdmin();

        $exportId = (int) $request->getParameter('id');
        if (!$exportId) {
            return $this->jsonResponse(['error' => 'Missing id parameter'], 400);
        }

        $export = DB::table('portable_export')->where('id', $exportId)->first();
        if (!$export) {
            return $this->jsonResponse(['error' => 'Export not found'], 404);
        }

        $data = [
            'id' => (int) $export->id,
            'status' => $export->status,
            'progress' => (int) $export->progress,
            'total_descriptions' => (int) $export->total_descriptions,
            'total_objects' => (int) $export->total_objects,
            'output_size' => (int) $export->output_size,
            'error_message' => $export->error_message,
            'started_at' => $export->started_at,
            'completed_at' => $export->completed_at,
        ];

        return $this->jsonResponse($data);
    }

    // ─── API: List Past Exports ─────────────────────────────────────

    public function executeApiList(sfWebRequest $request)
    {
        $this->requireAdmin();

        $exports = DB::table('portable_export')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->toArray();

        $result = array_map(function ($e) {
            return [
                'id' => (int) $e->id,
                'title' => $e->title,
                'scope_type' => $e->scope_type,
                'mode' => $e->mode,
                'status' => $e->status,
                'progress' => (int) $e->progress,
                'total_descriptions' => (int) $e->total_descriptions,
                'total_objects' => (int) $e->total_objects,
                'output_size' => (int) $e->output_size,
                'created_at' => $e->created_at,
                'completed_at' => $e->completed_at,
            ];
        }, $exports);

        return $this->jsonResponse(['exports' => $result]);
    }

    // ─── Download Completed Export ──────────────────────────────────

    public function executeDownload(sfWebRequest $request)
    {
        // Check for token-based access first
        $token = $request->getParameter('token');
        if ($token) {
            return $this->downloadByToken($token);
        }

        // Admin access
        $this->requireAdmin();

        $exportId = (int) $request->getParameter('id');
        $export = DB::table('portable_export')->where('id', $exportId)->first();

        if (!$export || $export->status !== 'completed' || !$export->output_path) {
            $this->forward404('Export not found or not ready');
        }

        if (!file_exists($export->output_path)) {
            $this->forward404('Export file not found on disk');
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $export->title) . '.zip';
        $this->serveFile($export->output_path, $filename);
    }

    /**
     * Download by secure token (no admin required).
     */
    protected function downloadByToken(string $token): void
    {
        $tokenRow = DB::table('portable_export_token')
            ->where('token', $token)
            ->first();

        if (!$tokenRow) {
            $this->forward404('Invalid download token');
        }

        // Check expiry
        if ($tokenRow->expires_at && strtotime($tokenRow->expires_at) < time()) {
            $this->forward404('Download token has expired');
        }

        // Check max downloads
        if ($tokenRow->max_downloads && $tokenRow->download_count >= $tokenRow->max_downloads) {
            $this->forward404('Download limit reached');
        }

        $export = DB::table('portable_export')->where('id', $tokenRow->export_id)->first();
        if (!$export || $export->status !== 'completed' || !file_exists($export->output_path)) {
            $this->forward404('Export not available');
        }

        // Increment download count
        DB::table('portable_export_token')
            ->where('id', $tokenRow->id)
            ->increment('download_count');

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $export->title) . '.zip';
        $this->serveFile($export->output_path, $filename);
    }

    /**
     * Serve a file for download.
     */
    protected function serveFile(string $path, string $filename): void
    {
        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType('application/zip');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHttpHeader('Content-Length', (string) filesize($path));
        $response->setHttpHeader('Cache-Control', 'no-cache, must-revalidate');
        $response->sendHttpHeaders();

        readfile($path);

        throw new sfStopException();
    }

    // ─── API: Delete Export ─────────────────────────────────────────

    public function executeApiDelete(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $exportId = (int) $request->getParameter('id');
        if (!$exportId) {
            return $this->jsonResponse(['error' => 'Missing id parameter'], 400);
        }

        $pipeline = new \AhgPortableExportPlugin\Services\ExportPipelineService();
        $pipeline->deleteExport($exportId);

        return $this->jsonResponse(['success' => true]);
    }

    // ─── API: Generate Download Token ───────────────────────────────

    public function executeApiToken(sfWebRequest $request)
    {
        $this->requireAdmin();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $exportId = (int) $request->getParameter('id');
        if (!$exportId) {
            return $this->jsonResponse(['error' => 'Missing id parameter'], 400);
        }

        $export = DB::table('portable_export')->where('id', $exportId)->first();
        if (!$export || $export->status !== 'completed') {
            return $this->jsonResponse(['error' => 'Export not completed'], 400);
        }

        $maxDownloads = $request->getParameter('max_downloads');
        $expiresHours = $request->getParameter('expires_hours', 168); // Default 7 days

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ((int) $expiresHours * 3600));

        DB::table('portable_export_token')->insert([
            'export_id' => $exportId,
            'token' => $token,
            'max_downloads' => $maxDownloads ? (int) $maxDownloads : null,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $baseUrl = sfConfig::get('app_siteBaseUrl', $request->getUriPrefix());
        $downloadUrl = rtrim($baseUrl, '/') . '/portable-export/download?token=' . $token;

        return $this->jsonResponse([
            'success' => true,
            'token' => $token,
            'download_url' => $downloadUrl,
            'expires_at' => $expiresAt,
        ]);
    }
}
