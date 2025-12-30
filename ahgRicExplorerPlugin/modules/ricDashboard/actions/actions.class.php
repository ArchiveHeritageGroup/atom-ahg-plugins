<?php

/**
 * RIC Dashboard Actions
 * 
 * Admin dashboard for monitoring and managing RIC synchronization.
 * AtoM 2.10 / Symfony 1.4 / Bootstrap 5 / PHP 8.3
 */

class ricDashboardActions extends sfActions
{
    protected $syncService;

    public function preExecute()
    {
        parent::preExecute();

        // Check authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        // Check admin access - use isAdministrator() instead of QubitAcl::check()
        if (!$this->context->user->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        // Initialize Laravel framework and service
        try {
            $bootstrapFile = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($bootstrapFile)) {
                require_once $bootstrapFile;
                $this->syncService = new \AtomFramework\Services\RicSyncService();
            }
        } catch (\Exception $e) {
            // Service not available - will show error in template
            $this->syncService = null;
        }
    }

    public function executeIndex(sfWebRequest $request)
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

        $this->syncSummary = $this->getSyncSummary();
        $this->queueStatus = $this->getQueueStatus();
        $this->recentOperations = $this->getRecentOperations(20);
        $this->orphanCount = $this->getOrphanCount();
        $this->fusekiStatus = $this->checkFusekiStatus();
        $this->configSettings = $this->getConfigSettings();
        $this->syncTrend = $this->getSyncTrend(7);
        $this->operationsByType = $this->getOperationsByType();
    }

    public function executeSyncStatus(sfWebRequest $request)
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

    public function executeOrphans(sfWebRequest $request)
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

    public function executeQueue(sfWebRequest $request)
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

    public function executeLogs(sfWebRequest $request)
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

    public function executeConfig(sfWebRequest $request)
    {
        $this->config = $this->getConfigSettings();

        if ($request->isMethod('post')) {
            foreach ($request->getParameter('config', []) as $key => $value) {
                \Illuminate\Database\Capsule\Manager::table('ric_sync_config')
                    ->where('config_key', $key)->update(['config_value' => $value]);
            }
            $this->getUser()->setFlash('notice', 'Configuration saved successfully');
            $this->redirect(['module' => 'ricDashboard', 'action' => 'config']);
        }
    }

    // AJAX Endpoints
    public function executeAjaxStats(sfWebRequest $request)
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

    public function executeAjaxIntegrityCheck(sfWebRequest $request)
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

    public function executeAjaxCleanupOrphans(sfWebRequest $request)
    {
        $dryRun = $request->getParameter('dry_run', false);
        try {
            $stats = $this->syncService->cleanupOrphanedTriples((bool) $dryRun);
            return $this->renderJson(['success' => true, 'stats' => $stats]);
        } catch (\Exception $e) {
            return $this->renderJson(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function executeAjaxResync(sfWebRequest $request)
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

    public function executeAjaxClearQueueItem(sfWebRequest $request)
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

    public function executeAjaxUpdateOrphan(sfWebRequest $request)
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
        $endpoint = $config['fuseki_endpoint'] ?? 'http://192.168.0.112:3030/ric';
        $username = $config['fuseki_username'] ?? 'admin';
        $password = $config['fuseki_password'] ?? 'admin123';

        try {
            $ch = curl_init($endpoint . '/query');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => 'SELECT (COUNT(*) AS ?count) WHERE { ?s ?p ?o }',
                CURLOPT_HTTPHEADER => ['Content-Type: application/sparql-query', 'Accept: application/json'],
                CURLOPT_USERPWD => "{$username}:{$password}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                $count = $data['results']['bindings'][0]['count']['value'] ?? 0;
                return ['online' => true, 'triple_count' => (int) $count];
            }
            return ['online' => false, 'error' => 'HTTP ' . $httpCode];
        } catch (\Exception $e) {
            return ['online' => false, 'error' => $e->getMessage()];
        }
    }

    protected function getConfigSettings(): array
    {
        try {
            return \Illuminate\Database\Capsule\Manager::table('ric_sync_config')
                ->pluck('config_value', 'config_key')
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

    protected function renderJson(array $data)
    {
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($data));
    }
}
