<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomExtensions\Services\AclService;

use Illuminate\Database\Capsule\Manager as DB;

class AhgSettingsErrorLogAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            AclService::forwardUnauthorized();
        }

        \AhgCore\Core\AhgDb::init();

        // Handle mark-all-read
        if ($request->isMethod('post') && $request->getParameter('mark_read') === 'all') {
            DB::table('ahg_error_log')
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            $this->getUser()->setFlash('success', 'All errors marked as read');
            $this->redirect(['module' => 'ahgSettings', 'action' => 'errorLog']);
        }

        // Handle clear old logs
        if ($request->isMethod('post') && $request->getParameter('clear_old')) {
            $days = (int) $request->getParameter('clear_old', 30);
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $deleted = DB::table('ahg_error_log')
                ->where('created_at', '<', $cutoff)
                ->delete();

            $this->getUser()->setFlash('success', sprintf('%d log entries older than %d days deleted', $deleted, $days));
            $this->redirect(['module' => 'ahgSettings', 'action' => 'errorLog']);
        }

        // Handle delete single
        if ($request->isMethod('post') && $request->getParameter('delete_id')) {
            DB::table('ahg_error_log')
                ->where('id', (int) $request->getParameter('delete_id'))
                ->delete();

            $this->getUser()->setFlash('success', 'Error log entry deleted');
            $this->redirect(['module' => 'ahgSettings', 'action' => 'errorLog']);
        }

        // Filters
        $level = $request->getParameter('level', '');
        $search = $request->getParameter('q', '');
        $page = max(1, (int) $request->getParameter('page', 1));
        $perPage = 25;

        $query = DB::table('ahg_error_log')->orderBy('created_at', 'desc');

        if ($level) {
            $query->where('level', $level);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%")
                  ->orWhere('exception_class', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $this->errors = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        $this->total = $total;
        $this->page = $page;
        $this->perPage = $perPage;
        $this->totalPages = max(1, (int) ceil($total / $perPage));
        $this->level = $level;
        $this->search = $search;

        // Stats
        $this->unreadCount = (int) DB::table('ahg_error_log')->where('is_read', 0)->count();
        $this->todayCount = (int) DB::table('ahg_error_log')
            ->where('created_at', '>=', date('Y-m-d 00:00:00'))
            ->count();
    }
}
