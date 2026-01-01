<?php

class ahgAuditTrailActions extends sfActions
{
    protected $db = null;

    public function preExecute()
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }
        $this->initDatabase();
    }

    protected function initDatabase()
    {
        $frameworkPath = sfConfig::get('sf_root_dir') . '/atom-framework';
        require_once $frameworkPath . '/vendor/autoload.php';

        $configPath = sfConfig::get('sf_root_dir') . '/config/config.php';
        $config = require $configPath;

        $param = $config['all']['propel']['param'];
        $dsn = $param['dsn'] ?? '';
        $username = $param['username'] ?? 'root';
        $password = $param['password'] ?? '';

        $host = 'localhost';
        $database = 'archive';
        $port = 3306;

        if (preg_match('/host=([^;]+)/', $dsn, $matches)) {
            $host = $matches[1];
        }
        if (preg_match('/dbname=([^;]+)/', $dsn, $matches)) {
            $database = $matches[1];
        }
        if (preg_match('/port=([^;]+)/', $dsn, $matches)) {
            $port = (int)$matches[1];
        }

        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);

        $this->db = $capsule->getConnection();
    }

    public function executeIndex(sfWebRequest $request)
    {
        $page = max(1, (int)$request->getParameter('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'action' => $request->getParameter('action_filter'),
            'module' => $request->getParameter('module_filter'),
            'username' => $request->getParameter('username'),
            'date_from' => $request->getParameter('date_from'),
            'date_to' => $request->getParameter('date_to'),
        ];

        $query = $this->db->table('ahg_audit_log');

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (!empty($filters['username'])) {
            $query->where('username', 'like', '%' . $filters['username'] . '%');
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $this->total = $query->count();
        $this->totalPages = ceil($this->total / $limit);
        $this->page = $page;
        $this->filters = $filters;

        $this->logs = $query->orderByDesc('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $this->actions = $this->db->table('ahg_audit_log')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $this->modules = $this->db->table('ahg_audit_log')
            ->select('module')
            ->distinct()
            ->whereNotNull('module')
            ->orderBy('module')
            ->pluck('module');
    }

    public function executeDashboard(sfWebRequest $request)
    {
        $period = $request->getParameter('period', '7');
        $since = date('Y-m-d H:i:s', strtotime("-{$period} days"));

        $this->totalActions = $this->db->table('ahg_audit_log')
            ->where('created_at', '>=', $since)
            ->count();

        $this->actionsByType = $this->db->table('ahg_audit_log')
            ->select('action', $this->db->raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        $this->actionsByUser = $this->db->table('ahg_audit_log')
            ->select('username', $this->db->raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('username')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $this->recentActivity = $this->db->table('ahg_audit_log')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $this->period = $period;
    }

    public function executeSettings(sfWebRequest $request)
    {
        if ($request->isMethod('post')) {
            $settings = [
                'audit_enabled' => $request->getParameter('audit_enabled') ? '1' : '0',
                'audit_authentication' => $request->getParameter('audit_authentication') ? '1' : '0',
                'audit_failed_logins' => $request->getParameter('audit_failed_logins') ? '1' : '0',
                'audit_creates' => $request->getParameter('audit_creates') ? '1' : '0',
                'audit_updates' => $request->getParameter('audit_updates') ? '1' : '0',
                'audit_deletes' => $request->getParameter('audit_deletes') ? '1' : '0',
                'audit_views' => $request->getParameter('audit_views') ? '1' : '0',
                'audit_imports' => $request->getParameter('audit_imports') ? '1' : '0',
                'audit_exports' => $request->getParameter('audit_exports') ? '1' : '0',
                'audit_downloads' => $request->getParameter('audit_downloads') ? '1' : '0',
                'audit_sensitive_access' => $request->getParameter('audit_sensitive_access') ? '1' : '0',
                'audit_permission_changes' => $request->getParameter('audit_permission_changes') ? '1' : '0',
                'audit_api_requests' => $request->getParameter('audit_api_requests') ? '1' : '0',
                'audit_searches' => $request->getParameter('audit_searches') ? '1' : '0',
                'audit_ip_anonymize' => $request->getParameter('audit_ip_anonymize') ? '1' : '0',
            ];

            foreach ($settings as $key => $value) {
                $this->db->table('ahg_audit_settings')
                    ->updateOrInsert(
                        ['setting_key' => $key],
                        ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                    );
            }

            $this->getUser()->setFlash('notice', 'Audit settings updated successfully.');
            $this->redirect(['module' => 'ahgAuditTrail', 'action' => 'settings']);
        }

        $this->settings = $this->db->table('ahg_audit_settings')
            ->pluck('setting_value', 'setting_key')->toArray();
    }

    public function executeExport(sfWebRequest $request)
    {
        $logs = $this->db->table('ahg_audit_log')
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get();

        $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date/Time', 'User', 'Action', 'Module', 'Entity Type', 'Entity ID', 'IP Address', 'Status']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log->id,
                $log->created_at,
                $log->username,
                $log->action,
                $log->module,
                $log->entity_type,
                $log->entity_id,
                $log->ip_address,
                $log->status,
            ]);
        }

        fclose($output);
        exit;
    }
}
