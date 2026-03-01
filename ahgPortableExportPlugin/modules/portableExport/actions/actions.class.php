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
            require_once $ahgDir . '/lib/Services/ArchiveExtractor.php';
            require_once $ahgDir . '/lib/Services/ManifestBuilder.php';
            require_once $ahgDir . '/lib/Services/ExportEstimator.php';
            require_once $ahgDir . '/lib/Services/ArchiveImporter.php';
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
            ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture())
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

        // Branding + archive entity types
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
        if ($mode === 'archive' && $request->getParameter('entity_types')) {
            $entityTypes = json_decode($request->getParameter('entity_types'), true);
            if (is_array($entityTypes)) {
                $branding['entity_types'] = $entityTypes;
            }
        }

        // Create export record with retention
        $expiresAt = $this->calculateExpiresAt();

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
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Launch background process
        $this->launchBackground($exportId);

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

    // ─── API: Quick Start Export (from description page) ──────────

    public function executeApiQuickStart(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $slug = $request->getParameter('slug');
        if (!$slug) {
            return $this->jsonResponse(['error' => 'Missing slug parameter'], 400);
        }

        // Resolve slug to get the title
        $io = DB::table('slug as s')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('s.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('s.slug', $slug)
            ->select('s.object_id', 'ioi.title')
            ->first();

        if (!$io) {
            return $this->jsonResponse(['error' => 'Description not found'], 404);
        }

        $title = $io->title ?: 'Portable Export - ' . $slug;
        $expiresAt = $this->calculateExpiresAt();

        // Load defaults from settings
        $defaults = $this->getSettingsDefaults();

        $exportId = DB::table('portable_export')->insertGetId([
            'user_id' => (int) $this->getUser()->getAttribute('user_id'),
            'title' => $title,
            'scope_type' => 'fonds',
            'scope_slug' => $slug,
            'mode' => $defaults['mode'],
            'include_objects' => $defaults['include_objects'],
            'include_thumbnails' => $defaults['include_thumbnails'],
            'include_references' => $defaults['include_references'],
            'include_masters' => $defaults['include_masters'],
            'culture' => $defaults['culture'],
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Launch background process
        $this->launchBackground($exportId);

        return $this->jsonResponse([
            'success' => true,
            'export_id' => $exportId,
            'message' => 'Export started for "' . $title . '"',
        ]);
    }

    // ─── API: Clipboard Export ────────────────────────────────────

    public function executeApiClipboardExport(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $slugsParam = $request->getParameter('slugs');
        if (!$slugsParam) {
            return $this->jsonResponse(['error' => 'No items provided'], 400);
        }

        $slugs = is_array($slugsParam) ? $slugsParam : explode(',', $slugsParam);
        $slugs = array_filter(array_map('trim', $slugs));

        if (empty($slugs)) {
            return $this->jsonResponse(['error' => 'No valid items provided'], 400);
        }

        // Resolve slugs to IDs
        $items = DB::table('slug')
            ->whereIn('slug', $slugs)
            ->pluck('object_id', 'slug')
            ->toArray();

        if (empty($items)) {
            return $this->jsonResponse(['error' => 'No matching descriptions found'], 404);
        }

        $title = $request->getParameter('title', 'Clipboard Export (' . count($items) . ' items)');
        $expiresAt = $this->calculateExpiresAt();
        $defaults = $this->getSettingsDefaults();

        $exportId = DB::table('portable_export')->insertGetId([
            'user_id' => (int) $this->getUser()->getAttribute('user_id'),
            'title' => $title,
            'scope_type' => 'custom',
            'scope_items' => json_encode(array_values($items)),
            'mode' => $defaults['mode'],
            'include_objects' => $defaults['include_objects'],
            'include_thumbnails' => $defaults['include_thumbnails'],
            'include_references' => $defaults['include_references'],
            'include_masters' => $defaults['include_masters'],
            'culture' => $defaults['culture'],
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Launch background process
        $this->launchBackground($exportId);

        return $this->jsonResponse([
            'success' => true,
            'export_id' => $exportId,
            'item_count' => count($items),
            'message' => 'Clipboard export started with ' . count($items) . ' items',
        ]);
    }

    // ─── API: Fonds Autocomplete ─────────────────────────────────

    public function executeApiFondsSearch(sfWebRequest $request)
    {
        $this->requireAdmin();

        $query = trim($request->getParameter('q', ''));
        if (strlen($query) < 2) {
            return $this->jsonResponse(['results' => []]);
        }

        // Search top-level descriptions (parent_id = 1 = root) by title or identifier
        $results = DB::table('information_object as io')
            ->join('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->join('slug as s', 'io.id', '=', 's.object_id')
            ->where('io.parent_id', 1)
            ->where(function ($q) use ($query) {
                $q->where('ioi.title', 'LIKE', '%' . $query . '%')
                  ->orWhere('io.identifier', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('ioi.title')
            ->limit(15)
            ->select('io.id', 'ioi.title', 'io.identifier', 's.slug')
            ->get()
            ->toArray();

        return $this->jsonResponse(['results' => $results]);
    }

    // ─── API: Dry-Run Estimate ────────────────────────────────────

    public function executeApiEstimate(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
        require_once $ahgDir . '/lib/Services/ExportEstimator.php';

        $scopeType = $request->getParameter('scope_type', 'all');
        $scopeSlug = $request->getParameter('scope_slug');
        $repositoryId = $request->getParameter('repository_id');
        $mode = $request->getParameter('mode', 'archive');
        $scopeItems = null;

        if ($request->getParameter('scope_items')) {
            $scopeItems = json_decode($request->getParameter('scope_items'), true);
        }

        $estimator = new \AhgPortableExportPlugin\Services\ExportEstimator();
        $estimate = $estimator->estimate(
            $scopeType,
            $scopeSlug ?: null,
            $repositoryId ? (int) $repositoryId : null,
            $scopeItems,
            $mode
        );

        return $this->jsonResponse($estimate);
    }

    // ─── Import UI ─────────────────────────────────────────────────

    public function executeImport(sfWebRequest $request)
    {
        $this->requireAdmin();

        // Get past imports
        $this->imports = DB::table('portable_import')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    // ─── API: Validate Archive ─────────────────────────────────────

    public function executeApiImportValidate(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $archiveDir = null;
        $tempDir = null;

        // Handle file upload or server path
        $serverPath = $request->getParameter('server_path');
        if ($serverPath) {
            if (pathinfo($serverPath, PATHINFO_EXTENSION) === 'zip') {
                // Extract ZIP to temp
                $tempDir = sys_get_temp_dir() . '/portable-validate-' . uniqid();
                @mkdir($tempDir, 0755, true);

                $zip = new \ZipArchive();
                if ($zip->open($serverPath) !== true) {
                    return $this->jsonResponse(['error' => 'Failed to open ZIP file'], 400);
                }
                $zip->extractTo($tempDir);
                $zip->close();

                $archiveDir = $tempDir;

                // Check for nested directory
                $items = @scandir($tempDir);
                $subdirs = array_filter($items ?: [], function ($item) use ($tempDir) {
                    return $item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item);
                });
                if (count($subdirs) === 1 && !file_exists($tempDir . '/manifest.json')) {
                    $archiveDir = $tempDir . '/' . reset($subdirs);
                }
            } elseif (is_dir($serverPath)) {
                $archiveDir = $serverPath;
            } else {
                return $this->jsonResponse(['error' => 'Invalid path — provide a ZIP file or directory'], 400);
            }
        } else {
            // Handle uploaded file
            $uploadDir = sfConfig::get('sf_root_dir') . '/downloads/portable-imports';
            @mkdir($uploadDir, 0755, true);

            if (isset($_FILES['archive_file']) && $_FILES['archive_file']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['archive_file']['tmp_name'];
                $originalName = $_FILES['archive_file']['name'];

                $destPath = $uploadDir . '/' . uniqid('import-') . '-' . basename($originalName);
                move_uploaded_file($uploadedFile, $destPath);

                $tempDir = sys_get_temp_dir() . '/portable-validate-' . uniqid();
                @mkdir($tempDir, 0755, true);

                $zip = new \ZipArchive();
                if ($zip->open($destPath) !== true) {
                    return $this->jsonResponse(['error' => 'Failed to open uploaded ZIP'], 400);
                }
                $zip->extractTo($tempDir);
                $zip->close();

                $archiveDir = $tempDir;

                // Check for nested directory
                $items = @scandir($tempDir);
                $subdirs = array_filter($items ?: [], function ($item) use ($tempDir) {
                    return $item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item);
                });
                if (count($subdirs) === 1 && !file_exists($tempDir . '/manifest.json')) {
                    $archiveDir = $tempDir . '/' . reset($subdirs);
                }

                // Store the uploaded ZIP path for later import
                $request->setAttribute('uploaded_zip', $destPath);
            } else {
                return $this->jsonResponse(['error' => 'No file uploaded or server path provided'], 400);
            }
        }

        // Load importer
        $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
        require_once $ahgDir . '/lib/Services/ArchiveImporter.php';

        $importer = new \AhgPortableExportPlugin\Services\ArchiveImporter();
        $validation = $importer->validate($archiveDir);

        $response = [
            'valid' => $validation['valid'],
            'errors' => $validation['errors'],
            'entity_counts' => $validation['entity_counts'],
            'archive_path' => $archiveDir,
        ];

        if ($validation['manifest']) {
            $response['source'] = $validation['manifest']['source'] ?? [];
            $response['culture'] = $validation['manifest']['culture'] ?? 'en';
            $response['scope'] = $validation['manifest']['scope'] ?? [];
            $response['version'] = $validation['manifest']['version'] ?? 'unknown';
            $response['created_at'] = $validation['manifest']['created_at'] ?? null;
            $response['total_files'] = count($validation['manifest']['files'] ?? []);
        }

        // Cleanup temp if just validating
        // Note: we keep archiveDir for the actual import step

        return $this->jsonResponse($response);
    }

    // ─── API: Start Import ─────────────────────────────────────────

    public function executeApiStartImport(sfWebRequest $request)
    {
        $this->requireAdmin();
        $this->loadServices();

        if (!$request->isMethod('post')) {
            return $this->jsonResponse(['error' => 'POST required'], 405);
        }

        $archivePath = $request->getParameter('archive_path');
        $mode = $request->getParameter('mode', 'merge');
        $entityTypesParam = $request->getParameter('entity_types');
        $title = $request->getParameter('title', 'Web Import');

        if (!$archivePath || !is_dir($archivePath)) {
            return $this->jsonResponse(['error' => 'Invalid archive path. Please validate first.'], 400);
        }

        // Parse entity types
        $entityTypes = null;
        if ($entityTypesParam) {
            $entityTypes = json_decode($entityTypesParam, true);
        }

        // Read manifest for source info
        $sourceUrl = null;
        $sourceVersion = null;
        $totalEntities = 0;
        $manifestPath = $archivePath . '/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $sourceUrl = $manifest['source']['url'] ?? null;
            $sourceVersion = $manifest['source']['framework'] ?? null;
            $totalEntities = array_sum($manifest['counts'] ?? []);
        }

        $importId = DB::table('portable_import')->insertGetId([
            'user_id' => (int) $this->getUser()->getAttribute('user_id'),
            'title' => $title,
            'source_url' => $sourceUrl,
            'source_version' => $sourceVersion,
            'archive_path' => $archivePath,
            'mode' => $mode,
            'entity_types' => $entityTypes ? json_encode($entityTypes) : null,
            'status' => 'importing',
            'total_entities' => $totalEntities,
            'started_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Launch background import
        $this->launchImportBackground($importId);

        return $this->jsonResponse([
            'success' => true,
            'import_id' => $importId,
            'message' => 'Import started. Polling for progress...',
        ]);
    }

    // ─── API: Import Progress ──────────────────────────────────────

    public function executeApiImportProgress(sfWebRequest $request)
    {
        $this->requireAdmin();

        $importId = (int) $request->getParameter('id');
        if (!$importId) {
            return $this->jsonResponse(['error' => 'Missing id parameter'], 400);
        }

        $import = DB::table('portable_import')->where('id', $importId)->first();
        if (!$import) {
            return $this->jsonResponse(['error' => 'Import not found'], 404);
        }

        return $this->jsonResponse([
            'id' => (int) $import->id,
            'status' => $import->status,
            'progress' => (int) $import->progress,
            'total_entities' => (int) $import->total_entities,
            'imported_entities' => (int) $import->imported_entities,
            'skipped_entities' => (int) $import->skipped_entities,
            'error_count' => (int) $import->error_count,
            'error_log' => $import->error_log,
            'started_at' => $import->started_at,
            'completed_at' => $import->completed_at,
        ]);
    }

    // ─── API: Import List ──────────────────────────────────────────

    public function executeApiImportList(sfWebRequest $request)
    {
        $this->requireAdmin();

        $imports = DB::table('portable_import')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->toArray();

        $result = array_map(function ($i) {
            return [
                'id' => (int) $i->id,
                'title' => $i->title,
                'source_url' => $i->source_url,
                'mode' => $i->mode,
                'status' => $i->status,
                'progress' => (int) $i->progress,
                'imported_entities' => (int) $i->imported_entities,
                'skipped_entities' => (int) $i->skipped_entities,
                'error_count' => (int) $i->error_count,
                'created_at' => $i->created_at,
                'completed_at' => $i->completed_at,
            ];
        }, $imports);

        return $this->jsonResponse(['imports' => $result]);
    }

    /**
     * Launch background import process.
     */
    protected function launchImportBackground(int $importId): void
    {
        // Try queue dispatch first
        try {
            if (class_exists('\AtomFramework\Services\QueueService')) {
                $queueService = new \AtomFramework\Services\QueueService();
                $userId = $this->userId();
                $queueService->dispatch(
                    'portable:import',
                    ['task' => 'portable:import', 'args' => '--import-id=' . $importId],
                    'import',
                    5,
                    0,
                    1,
                    $userId
                );

                return;
            }
        } catch (\Throwable $e) {
            // Queue unavailable, fall through to nohup
        }

        // Fallback: legacy nohup launch
        $atomRoot = sfConfig::get('sf_root_dir');
        $logDir = $atomRoot . '/downloads/portable-imports';
        @mkdir($logDir, 0755, true);

        $cmd = sprintf(
            'nohup php %s/symfony portable:import --import-id=%d > %s/import-%d.log 2>&1 &',
            escapeshellarg($atomRoot),
            $importId,
            $logDir,
            $importId
        );
        exec($cmd);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * Calculate expiry date from settings retention period.
     */
    protected function calculateExpiresAt(): ?string
    {
        $days = (int) (DB::table('ahg_settings')
            ->where('setting_key', 'portable_export_retention_days')
            ->value('setting_value') ?: 30);

        return date('Y-m-d H:i:s', strtotime("+{$days} days"));
    }

    /**
     * Get default export settings from ahg_settings.
     */
    protected function getSettingsDefaults(): array
    {
        $settings = DB::table('ahg_settings')
            ->where('setting_group', 'portable_export')
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        return [
            'mode' => $settings['portable_export_default_mode'] ?? 'read_only',
            'culture' => $settings['portable_export_default_culture'] ?? 'en',
            'include_objects' => ($settings['portable_export_include_objects'] ?? 'true') === 'true' ? 1 : 0,
            'include_thumbnails' => ($settings['portable_export_include_thumbnails'] ?? 'true') === 'true' ? 1 : 0,
            'include_references' => ($settings['portable_export_include_references'] ?? 'true') === 'true' ? 1 : 0,
            'include_masters' => ($settings['portable_export_include_masters'] ?? 'false') === 'true' ? 1 : 0,
        ];
    }

    /**
     * Launch background export process.
     *
     * Uses QueueService if available, falls back to nohup.
     */
    protected function launchBackground(int $exportId): void
    {
        // Try queue dispatch first
        try {
            if (class_exists('\AtomFramework\Services\QueueService')) {
                $queueService = new \AtomFramework\Services\QueueService();
                $userId = $this->userId();
                $queueService->dispatch(
                    'portable:export',
                    ['task' => 'portable:export', 'args' => '--export-id=' . $exportId],
                    'export',
                    5,
                    0,
                    1,
                    $userId
                );

                return;
            }
        } catch (\Throwable $e) {
            // Queue unavailable, fall through to nohup
        }

        // Fallback: legacy nohup launch
        $atomRoot = sfConfig::get('sf_root_dir');
        $cmd = sprintf(
            'nohup php %s/symfony portable:export --export-id=%d > %s/downloads/portable-exports/export-%d.log 2>&1 &',
            escapeshellarg($atomRoot),
            $exportId,
            $atomRoot,
            $exportId
        );

        @mkdir($atomRoot . '/downloads/portable-exports', 0755, true);
        exec($cmd);
    }
}
