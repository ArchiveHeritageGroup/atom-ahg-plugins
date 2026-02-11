<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * RIC Dashboard Actions
 * 
 * Admin dashboard for monitoring and managing RIC synchronization.
 * AtoM 2.10 / Symfony 1.4 / Bootstrap 5 / PHP 8.3
 */

class ricDashboardActions extends AhgController
{
    protected $syncService;

    public function boot(): void
    {
// Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin access - use isAdministrator() instead of QubitAcl::check()
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Initialize Laravel framework and service
        try {
            $bootstrapFile = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrapFile)) {
                require_once $bootstrapFile;
                $this->syncService = new \AtomFramework\Services\RicSyncService();
            }
        } catch (\Exception $e) {
            // Service not available - will show error in template
            $this->syncService = null;
        }
    }

    public function executeIndex($request)
    {
        // Check if service is available
        if ($this->syncService === null) {
            $this->serviceError = 'RIC Sync Service not available. Run install_ric_sync.sh first.';
            $this->syncSummary = [];
            $this->queueStatus = [];
            $this->recentOperations = [];
            $this->orphanCount = 0;
            $this->fusekiStatus = ['online' => false, 'error' => 'Service not initialized'];
            $this->configSettings = [];
            $this->syncTrend = [];
            $this->operationsByType = [];
            return;
        }

        // OPTIMIZATION: Only load minimal data for initial page render
        // Heavy data is loaded via AJAX after page load
        $this->fusekiStatus = $this->checkFusekiStatus();  // Fast ASK query
        $this->configSettings = $this->getConfigSettings(); // Fast DB query

        // These will be loaded via AJAX
        $this->syncSummary = [];
        $this->queueStatus = [];
        $this->recentOperations = [];
        $this->orphanCount = 0;
        $this->syncTrend = [];
        $this->operationsByType = [];
        $this->asyncLoad = true;  // Flag for template to load via AJAX
    }

    public function executeSyncStatus($request)
    {
        $entityType = $request->getParameter('entity_type');
        $status = $request->getParameter('status');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 50;

        $query = \Illuminate\Database\Capsule\Manager::table('ric_sync_status');
        if ($entityType) $query->where('entity_type', $entityType);
        if ($status) $query->where('sync_status', $status);

        $this->totalCount = $query->count();
        $this->records = $query->orderBy('updated_at', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();
        $this->currentPage = $page;
        $this->totalPages = ceil($this->totalCount / $limit);
        $this->entityType = $entityType;
        $this->status = $status;
        $this->entityTypes = \Illuminate\Database\Capsule\Manager::table('ric_sync_status')
            ->select('entity_type')->distinct()->pluck('entity_type')->toArray();
        $this->statuses = ['synced', 'pending', 'failed', 'deleted', 'orphaned'];
    }

    public function executeOrphans($request)
    {
        $status = $request->getParameter('status', 'detected');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 50;

        $query = \Illuminate\Database\Capsule\Manager::table('ric_orphan_tracking');
        if ($status && $status !== 'all') $query->where('status', $status);

        $this->totalCount = $query->count();
        $this->orphans = $query->orderBy('detected_at', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();
        $this->currentPage = $page;
        $this->totalPages = ceil($this->totalCount / $limit);
        $this->currentStatus = $status;
        $this->statusCounts = \Illuminate\Database\Capsule\Manager::table('ric_orphan_tracking')
            ->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();
    }

    public function executeQueue($request)
    {
        $status = $request->getParameter('status', 'queued');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 50;

        $query = \Illuminate\Database\Capsule\Manager::table('ric_sync_queue');
        if ($status && $status !== 'all') $query->where('status', $status);

        $this->totalCount = $query->count();
        $this->queueItems = $query->orderBy('priority')->orderBy('scheduled_at')->offset(($page - 1) * $limit)->limit($limit)->get();
        $this->currentPage = $page;
        $this->totalPages = ceil($this->totalCount / $limit);
        $this->currentStatus = $status;
        $this->statusCounts = \Illuminate\Database\Capsule\Manager::table('ric_sync_queue')
            ->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status')->toArray();
    }

    public function executeLogs($request)
    {
        $operation = $request->getParameter('operation');
        $status = $request->getParameter('status');
        $entityType = $request->getParameter('entity_type');
        $dateFrom = $request->getParameter('date_from');
        $dateTo = $request->getParameter('date_to');
        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 100;

        $query = \Illuminate\Database\Capsule\Manager::table('ric_sync_log');
        if ($operation) $query->where('operation', $operation);
        if ($status) $query->where('status', $status);
        if ($entityType) $query->where('entity_type', $entityType);
        if ($dateFrom) $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        if ($dateTo) $query->where('created_at', '<=', $dateTo . ' 23:59:59');

        $this->totalCount = $query->count();
        $this->logs = $query->orderBy('created_at', 'desc')->offset(($page - 1) * $limit)->limit($limit)->get();
        $this->currentPage = $page;
        $this->totalPages = ceil($this->totalCount / $limit);
        $this->filters = compact('operation', 'status', 'entityType', 'dateFrom', 'dateTo');
    }

    public function executeConfig($request)
    {
        // Redirect to AHG Settings - Fuseki section (centralized config)
        $this->redirect(['module' => 'ahgSettings', 'action' => 'section', 'section' => 'fuseki']);
    }

    // AJAX Endpoints
    public function executeAjaxStats($request)
    {
        return $this->renderJson([
            'sync_summary' => $this->getSyncSummary(),
            'queue_status' => $this->getQueueStatus(),
            'orphan_count' => $this->getOrphanCount(),
            'fuseki_status' => $this->checkFusekiStatus(),
            'recent_operations' => $this->getRecentOperations(10),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Full dashboard data endpoint - used for async loading
     * Includes caching to reduce database load
     */
    public function executeAjaxDashboard($request)
    {
        $cacheKey = 'ric_dashboard_stats';
        $cacheTtl = 60; // Cache for 60 seconds

        // Try cache first
        $cached = $this->getFromCache($cacheKey);
        if ($cached && !$request->getParameter('refresh')) {
            $cached['from_cache'] = true;
            return $this->renderJson($cached);
        }

        // Build fresh data
        $data = [
            'sync_summary' => $this->getSyncSummary(),
            'queue_status' => $this->getQueueStatus(),
            'orphan_count' => $this->getOrphanCount(),
            'recent_operations' => $this->getRecentOperations(10),
            'sync_trend' => $this->getSyncTrend(7),
            'operations_by_type' => $this->getOperationsByType(),
            'timestamp' => date('Y-m-d H:i:s'),
            'from_cache' => false,
        ];

        // Save to cache
        $this->saveToCache($cacheKey, $data, $cacheTtl);

        return $this->renderJson($data);
    }

    /**
     * Simple file-based cache for dashboard stats
     */
    protected function getFromCache(string $key): ?array
    {
        $cacheFile = $this->config('sf_cache_dir') . "/ric_{$key}.json";
        if (!file_exists($cacheFile)) {
            return null;
        }

        $data = json_decode(file_get_contents($cacheFile), true);
        if (!$data || !isset($data['_expires']) || $data['_expires'] < time()) {
            @unlink($cacheFile);
            return null;
        }

        unset($data['_expires']);
        return $data;
    }

    protected function saveToCache(string $key, array $data, int $ttl): void
    {
        $cacheFile = $this->config('sf_cache_dir') . "/ric_{$key}.json";
        $data['_expires'] = time() + $ttl;
        @file_put_contents($cacheFile, json_encode($data));
    }

    public function executeAjaxIntegrityCheck($request)
    {
        try {
            $results = $this->syncService->runIntegrityCheck();
            return $this->renderJson([
                'success' => true,
                'report' => [
                    'summary' => [
                        'orphaned_count' => count($results['orphaned_triples']),
                        'missing_count' => count($results['missing_records']),
                        'inconsistency_count' => count($results['inconsistencies']),
                    ],
                    'orphaned_triples' => array_slice($results['orphaned_triples'], 0, 20),
                    'missing_records' => array_slice($results['missing_records'], 0, 20),
                    'checked_at' => $results['checked_at'],
                ],
            ]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeAjaxCleanupOrphans($request)
    {
        $dryRun = $request->getParameter('dry_run', false);
        try {
            $stats = $this->syncService->cleanupOrphanedTriples((bool) $dryRun);
            return $this->renderJson(['success' => true, 'stats' => $stats]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeAjaxResync($request)
    {
        $entityType = $request->getParameter('entity_type');
        $entityId = (int) $request->getParameter('entity_id');
        try {
            $result = $this->syncService->syncRecord($entityType, $entityId, 'resync');
            return $this->renderJson(['success' => $result]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeAjaxClearQueueItem($request)
    {
        $id = (int) $request->getParameter('id');
        $action = $request->getParameter('queue_action');

        if ($action === 'retry') {
            \Illuminate\Database\Capsule\Manager::table('ric_sync_queue')
                ->where('id', $id)->update(['status' => 'queued', 'attempts' => 0]);
        } elseif ($action === 'cancel') {
            \Illuminate\Database\Capsule\Manager::table('ric_sync_queue')
                ->where('id', $id)->update(['status' => 'cancelled']);
        }

        return $this->renderJson(['success' => true]);
    }

    public function executeAjaxUpdateOrphan($request)
    {
        $id = (int) $request->getParameter('id');
        $status = $request->getParameter('orphan_status');
        $validStatuses = ['reviewed', 'retained', 'cleaned'];

        if (!in_array($status, $validStatuses)) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid status']);
        }

        \Illuminate\Database\Capsule\Manager::table('ric_orphan_tracking')
            ->where('id', $id)->update([
                'status' => $status,
                'resolved_at' => $status === 'cleaned' ? date('Y-m-d H:i:s') : null,
                'resolved_by' => $this->getUser()->getAttribute('user_id'),
            ]);

        return $this->renderJson(['success' => true]);
    }

    // Helper Methods
    protected function getSyncSummary(): array
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('ric_sync_status')
                ->selectRaw('entity_type, sync_status, COUNT(*) as count')
                ->groupBy('entity_type', 'sync_status')
                ->get()
                ->groupBy('entity_type')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getQueueStatus(): array
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('ric_sync_queue')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getRecentOperations(int $limit): array
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('ric_sync_log')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getOrphanCount(): int
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('ric_orphan_tracking')
                ->where('status', 'detected')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function checkFusekiStatus(): array
    {
        $config = $this->getConfigSettings();
        $endpoint = $config['fuseki_endpoint'] ?? $this->config('app_ric_fuseki_endpoint', 'http://localhost:3030/ric');
        $username = $config['fuseki_username'] ?? $this->config('app_ric_fuseki_username', 'admin');
        $password = $config['fuseki_password'] ?? $this->config('app_ric_fuseki_password', '');

        try {
            // Use ASK query for quick connectivity check (faster than COUNT on large datasets)
            $ch = curl_init($endpoint . '/query');
            $headers = ['Content-Type: application/sparql-query', 'Accept: application/json'];
            $curlOpts = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => 'ASK { ?s ?p ?o }',
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ];

            // Only add auth if password is set
            if (!empty($password)) {
                $curlOpts[CURLOPT_USERPWD] = "{$username}:{$password}";
            }

            curl_setopt_array($ch, $curlOpts);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $hasData = $data['boolean'] ?? false;
                return ['online' => true, 'has_data' => $hasData];
            }
            return ['online' => false, 'error' => 'HTTP ' . $httpCode];
        } catch (\Exception $e) {
            return ['online' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getConfigSettings(): array
    {
        try {
            // Read from ahg_settings table (AHG Settings UI) - fuseki section
            return \Illuminate\Database\Capsule\Manager::table('ahg_settings')
                ->where('setting_group', 'fuseki')
                ->pluck('setting_value', 'setting_key')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getSyncTrend(int $days): array
    {
        try {
            $data = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $counts = \Illuminate\Database\Capsule\Manager::table('ric_sync_log')
                    ->selectRaw('status, COUNT(*) as count')
                    ->whereRaw('DATE(created_at) = ?', [$date])
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray();
                $data[] = ['date' => $date, 'success' => $counts['success'] ?? 0, 'failure' => $counts['failure'] ?? 0];
            }
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getOperationsByType(): array
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('ric_sync_log')
                ->selectRaw('operation, COUNT(*) as count')
                ->where('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
                ->groupBy('operation')
                ->pluck('count', 'operation')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function renderJson(array $data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($data));
    }
}
