<?php

use Illuminate\Database\Capsule\Manager as DB;

class auditActions extends AhgActions
{
    public function preExecute()
    {
        if (!$this->getUser()->isAuthenticated() || !$this->getUser()->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Administrator access required');
            $this->redirect('@homepage');
        }
    }

    public function executeIndex(sfWebRequest $request)
    {
        $this->filters = [
            'table' => $request->getParameter('table'),
            'action' => $request->getParameter('action'),
            'user_id' => $request->getParameter('user_id'),
            'from_date' => $request->getParameter('from_date'),
            'to_date' => $request->getParameter('to_date'),
            'search' => $request->getParameter('q'),
        ];

        $query = DB::table('audit_log as a')
            ->leftJoin('user as u', 'a.user_id', '=', 'u.id')
            ->select('a.*', 'u.username as user_name');

        if ($this->filters['table']) {
            $query->where('a.table_name', $this->filters['table']);
        }
        if ($this->filters['action']) {
            $query->where('a.action', $this->filters['action']);
        }
        if ($this->filters['user_id']) {
            $query->where('a.user_id', $this->filters['user_id']);
        }
        if ($this->filters['from_date']) {
            $query->where('a.created_at', '>=', $this->filters['from_date'] . ' 00:00:00');
        }
        if ($this->filters['to_date']) {
            $query->where('a.created_at', '<=', $this->filters['to_date'] . ' 23:59:59');
        }
        if ($this->filters['search']) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('a.old_value', 'LIKE', '%' . $search . '%')
                  ->orWhere('a.new_value', 'LIKE', '%' . $search . '%')
                  ->orWhere('a.action_description', 'LIKE', '%' . $search . '%');
            });
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 50;
        $this->totalCount = $query->count();
        $this->totalPages = ceil($this->totalCount / $perPage);
        $this->currentPage = $page;

        $this->logs = $query
            ->orderBy('a.created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        $this->tables = DB::table('audit_log')
            ->select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name')
            ->toArray();

        $this->users = DB::table('audit_log as a')
            ->leftJoin('user as u', 'a.user_id', '=', 'u.id')
            ->whereNotNull('a.user_id')
            ->select('a.user_id', 'u.username')
            ->distinct()
            ->get()
            ->toArray();

        $this->stats = [
            'total' => DB::table('audit_log')->count(),
            'today' => DB::table('audit_log')->where('created_at', '>=', date('Y-m-d 00:00:00'))->count(),
            'creates' => DB::table('audit_log')->where('action', 'create')->count(),
            'updates' => DB::table('audit_log')->where('action', 'update')->count(),
            'deletes' => DB::table('audit_log')->where('action', 'delete')->count(),
        ];
    }

    public function executeView(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id');
        
        $this->entry = DB::table('audit_log as a')
            ->leftJoin('user as u', 'a.user_id', '=', 'u.id')
            ->where('a.id', $id)
            ->select('a.*', 'u.username as user_name')
            ->first();

        if (!$this->entry) {
            $this->forward404('Audit entry not found');
        }

        $this->oldRecord = $this->entry->old_record ? json_decode($this->entry->old_record, true) : null;
        $this->newRecord = $this->entry->new_record ? json_decode($this->entry->new_record, true) : null;
        $this->changes = [];
        if ($this->oldRecord && $this->newRecord) {
            $allKeys = array_unique(array_merge(array_keys($this->oldRecord), array_keys($this->newRecord)));
            foreach ($allKeys as $key) {
                $old = $this->oldRecord[$key] ?? null;
                $new = $this->newRecord[$key] ?? null;
                if ($old !== $new) {
                    $this->changes[$key] = ['old' => $old, 'new' => $new];
                }
            }
        }
    }

    public function executeRecord(sfWebRequest $request)
    {
        $this->tableName = $request->getParameter('table');
        $this->recordId = (int) $request->getParameter('record_id');

        $this->history = DB::table('audit_log as a')
            ->leftJoin('user as u', 'a.user_id', '=', 'u.id')
            ->where('a.table_name', $this->tableName)
            ->where('a.record_id', $this->recordId)
            ->select('a.*', 'u.username as user_name')
            ->orderBy('a.created_at', 'desc')
            ->get()
            ->toArray();

        $this->timeline = [];
        foreach ($this->history as $entry) {
            $date = date('Y-m-d', strtotime($entry->created_at));
            if (!isset($this->timeline[$date])) {
                $this->timeline[$date] = [];
            }
            $this->timeline[$date][] = $entry;
        }
    }

    public function executeUser(sfWebRequest $request)
    {
        $this->userId = (int) $request->getParameter('id');
        $this->user = DB::table('user')->where('id', $this->userId)->first();
        if (!$this->user) {
            $this->forward404('User not found');
        }

        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 50;
        $query = DB::table('audit_log')->where('user_id', $this->userId);

        $this->totalCount = $query->count();
        $this->totalPages = ceil($this->totalCount / $perPage);
        $this->currentPage = $page;

        $this->activity = $query
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->toArray();

        $this->tableStats = DB::table('audit_log')
            ->where('user_id', $this->userId)
            ->select('table_name', DB::raw('COUNT(*) as count'))
            ->groupBy('table_name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();

        $this->actionStats = DB::table('audit_log')
            ->where('user_id', $this->userId)
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->get()
            ->toArray();
    }

    public function executeExport(sfWebRequest $request)
    {
        $query = DB::table('audit_log as a')
            ->leftJoin('user as u', 'a.user_id', '=', 'u.id')
            ->select('a.*', 'u.username as user_name');

        if ($request->getParameter('table')) {
            $query->where('a.table_name', $request->getParameter('table'));
        }
        if ($request->getParameter('from_date')) {
            $query->where('a.created_at', '>=', $request->getParameter('from_date') . ' 00:00:00');
        }
        if ($request->getParameter('to_date')) {
            $query->where('a.created_at', '<=', $request->getParameter('to_date') . ' 23:59:59');
        }

        $logs = $query->orderBy('a.created_at', 'desc')->limit(10000)->get()->toArray();

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Date/Time', 'User', 'Action', 'Table', 'Record ID', 'Field', 'Old Value', 'New Value', 'Description', 'IP Address']);
        
        foreach ($logs as $log) {
            fputcsv($output, [
                $log->id, $log->created_at, $log->user_name ?? 'System', $log->action,
                $log->table_name, $log->record_id, $log->field_name,
                $log->old_value, $log->new_value, $log->action_description, $log->ip_address,
            ]);
        }
        fclose($output);
        exit;
    }
}
