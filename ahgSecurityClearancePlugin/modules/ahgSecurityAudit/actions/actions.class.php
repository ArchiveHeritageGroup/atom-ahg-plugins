<?php

class arSecurityAuditActions extends sfActions
{
    public function preExecute()
    {
        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
    }

    public function executeDashboard(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }

        $period = $request->getParameter('period', '7 days');
        $since = date('Y-m-d H:i:s', strtotime("-{$period}"));

        $this->stats = $this->getStatistics($since);
        $this->period = $period;
    }

    public function executeIndex(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required.');
            $this->redirect('@homepage');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        // Get filter values - use log_action to avoid conflict with Symfony's action parameter
        $rawUser = $request->getParameter('user');
        $rawAction = $request->getParameter('log_action');  // Changed from 'action'
        $rawCategory = $request->getParameter('category');
        $rawDateFrom = $request->getParameter('date_from');
        $rawDateTo = $request->getParameter('date_to');

        $hasUserFilter = !empty($rawUser) && is_string($rawUser) && strlen(trim($rawUser)) > 0;
        $hasActionFilter = !empty($rawAction) && is_string($rawAction) && strlen(trim($rawAction)) > 0;
        $hasCategoryFilter = !empty($rawCategory) && is_string($rawCategory) && strlen(trim($rawCategory)) > 0;
        $hasDateFromFilter = !empty($rawDateFrom) && is_string($rawDateFrom) && strlen(trim($rawDateFrom)) > 0;
        $hasDateToFilter = !empty($rawDateTo) && is_string($rawDateTo) && strlen(trim($rawDateTo)) > 0;

        $filters = [
            'user_name' => $hasUserFilter ? trim($rawUser) : null,
            'log_action' => $hasActionFilter ? trim($rawAction) : null,
            'category' => $hasCategoryFilter ? trim($rawCategory) : null,
            'date_from' => $hasDateFromFilter ? trim($rawDateFrom) : null,
            'date_to' => $hasDateToFilter ? trim($rawDateTo) : null,
        ];

        $page = max(1, (int)$request->getParameter('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Base total
        $baseTotal = $db::table('spectrum_audit_log')->count();

        // Build count query
        $countQuery = $db::table('spectrum_audit_log as sal');
        
        if ($hasUserFilter) {
            $countQuery->where('sal.user_name', 'LIKE', '%' . trim($rawUser) . '%');
        }
        if ($hasActionFilter) {
            $countQuery->where('sal.action', '=', trim($rawAction));
        }
        if ($hasCategoryFilter) {
            $countQuery->where('sal.procedure_type', 'LIKE', '%' . trim($rawCategory) . '%');
        }
        if ($hasDateFromFilter) {
            $countQuery->where('sal.action_date', '>=', trim($rawDateFrom) . ' 00:00:00');
        }
        if ($hasDateToFilter) {
            $countQuery->where('sal.action_date', '<=', trim($rawDateTo) . ' 23:59:59');
        }
        
        $total = $countQuery->count();

        // Build logs query
        $logsQuery = $db::table('spectrum_audit_log as sal')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('sal.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'sal.object_id', '=', 'slug.object_id');

        if ($hasUserFilter) {
            $logsQuery->where('sal.user_name', 'LIKE', '%' . trim($rawUser) . '%');
        }
        if ($hasActionFilter) {
            $logsQuery->where('sal.action', '=', trim($rawAction));
        }
        if ($hasCategoryFilter) {
            $logsQuery->where('sal.procedure_type', 'LIKE', '%' . trim($rawCategory) . '%');
        }
        if ($hasDateFromFilter) {
            $logsQuery->where('sal.action_date', '>=', trim($rawDateFrom) . ' 00:00:00');
        }
        if ($hasDateToFilter) {
            $logsQuery->where('sal.action_date', '<=', trim($rawDateTo) . ' 23:59:59');
        }

        $logs = $logsQuery->select(
                'sal.id',
                'sal.object_id',
                $db::raw("'spectrum' as source"),
                'sal.user_id',
                'sal.user_name',
                'sal.action',
                'sal.procedure_type as action_category',
                'sal.ip_address',
                'sal.action_date as created_at',
                'ioi.title as object_title',
                'slug.slug as object_slug'
            )
            ->orderByDesc('sal.action_date')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->toArray();

        $this->logs = $logs;
        $this->total = $total;
        $this->baseTotal = $baseTotal;
        $this->page = $page;
        $this->totalPages = $total > 0 ? ceil($total / $limit) : 1;
        $this->filters = $filters;
        $this->actions = $db::table('spectrum_audit_log')->distinct()->pluck('action')->toArray();
        $this->categories = $db::table('spectrum_audit_log')->distinct()->pluck('procedure_type')->toArray();
    }

    public function executeExport(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;

        $logs = $db::table('spectrum_audit_log as sal')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('sal.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->select(
                'sal.action_date as created_at',
                'sal.user_name',
                'sal.action',
                'sal.procedure_type as action_category',
                'ioi.title as object_title',
                $db::raw("'spectrum' as source"),
                'sal.ip_address'
            )
            ->orderByDesc('sal.action_date')
            ->limit(10000)
            ->get();

        $filename = 'security_audit_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date/Time', 'User', 'Action', 'Category', 'Object', 'Source', 'IP Address']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log->created_at,
                $log->user_name,
                $log->action,
                $log->action_category,
                $log->object_title ?? 'N/A',
                $log->source,
                $log->ip_address ?? 'N/A',
            ]);
        }

        fclose($output);
        return sfView::NONE;
    }

    public function executeObjectAccess(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated() || !$this->context->user->hasCredential('administrator')) {
            $this->redirect('@homepage');
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $objectId = $request->getParameter('object_id');
        
        if (!$objectId) {
            $this->getUser()->setFlash('error', 'No object specified.');
            $this->redirect(['module' => 'arSecurityAudit', 'action' => 'dashboard']);
        }
        
        $period = $request->getParameter('period', '30 days');
        $since = date('Y-m-d H:i:s', strtotime("-{$period}"));

        $this->object = $db::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $objectId)
            ->select('io.id', 'io.identifier', 'ioi.title', 'slug.slug')
            ->first();

        if (!$this->object) {
            $this->getUser()->setFlash('error', 'Object not found (ID: ' . $objectId . ')');
            $this->redirect(['module' => 'arSecurityAudit', 'action' => 'dashboard']);
        }

        $this->accessLogs = $db::table('access_log')
            ->where('object_id', $objectId)
            ->where('access_date', '>=', $since)
            ->orderByDesc('access_date')
            ->limit(100)
            ->get()
            ->toArray();

        $this->securityLogs = $db::table('spectrum_audit_log')
            ->where('object_id', $objectId)
            ->where('action_date', '>=', $since)
            ->orderByDesc('action_date')
            ->limit(100)
            ->get()
            ->toArray();

        $this->dailyAccess = $db::table('access_log')
            ->where('object_id', $objectId)
            ->where('access_date', '>=', $since)
            ->select(
                $db::raw('DATE(access_date) as date'),
                $db::raw('COUNT(*) as count')
            )
            ->groupBy($db::raw('DATE(access_date)'))
            ->orderBy('date')
            ->get()
            ->toArray();

        $this->period = $period;
        $this->totalAccess = count($this->accessLogs);
    }

    protected function getStatistics($since)
    {
        $db = \Illuminate\Database\Capsule\Manager::class;

        $byCategory = $db::table('spectrum_audit_log')
            ->where('action_date', '>=', $since)
            ->select('procedure_type as category', $db::raw('COUNT(*) as count'))
            ->groupBy('procedure_type')
            ->pluck('count', 'category')
            ->toArray();

        $byUser = $db::table('spectrum_audit_log')
            ->where('action_date', '>=', $since)
            ->whereNotNull('user_name')
            ->select('user_name', $db::raw('COUNT(*) as count'))
            ->groupBy('user_name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        $byAction = $db::table('spectrum_audit_log')
            ->where('action_date', '>=', $since)
            ->select('action', $db::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        $byDay = $db::table('spectrum_audit_log')
            ->where('action_date', '>=', $since)
            ->select($db::raw('DATE(action_date) as date'), $db::raw('COUNT(*) as count'))
            ->groupBy($db::raw('DATE(action_date)'))
            ->orderBy('date')
            ->get()
            ->toArray();

        $topObjects = $db::table('access_log as al')
            ->leftJoin('information_object_i18n as ioi', function($join) {
                $join->on('al.object_id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'al.object_id', '=', 'slug.object_id')
            ->where('al.access_date', '>=', $since)
            ->select('al.object_id', 'ioi.title', 'slug.slug', $db::raw('COUNT(*) as count'))
            ->groupBy('al.object_id', 'ioi.title', 'slug.slug')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();

        $securityEvents = $db::table('spectrum_audit_log')
            ->where('action_date', '>=', $since)
            ->where('procedure_type', 'LIKE', '%clearance%')
            ->count();

        return [
            'since' => $since,
            'by_category' => $byCategory,
            'by_user' => $byUser,
            'by_action' => $byAction,
            'by_day' => $byDay,
            'top_objects' => $topObjects,
            'security_events' => $securityEvents,
            'total_events' => array_sum($byCategory),
        ];
    }
}
