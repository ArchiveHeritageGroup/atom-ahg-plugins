<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Overdue items list.
 *
 * Shows all items past their due date that have not been returned.
 */
class circulationOverdueAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Pagination
        $this->limit = 25;
        $this->page = max(1, (int) $request->getParameter('page', 1));
        $offset = ($this->page - 1) * $this->limit;

        $today = date('Y-m-d');

        try {
            // Total overdue count
            $this->total = DB::table('library_circulation')
                ->where('action_type', 'checkout')
                ->whereNull('return_date')
                ->where('due_date', '<', $today)
                ->count();

            // Overdue items with details
            $this->overdueItems = DB::table('library_circulation as lc')
                ->join('library_item as li', 'lc.library_item_id', '=', 'li.id')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('li.information_object_id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('lc.patron_id', '=', 'ai.id')
                         ->where('ai.culture', '=', 'en');
                })
                ->leftJoin('library_patron as lp', 'lc.patron_id', '=', 'lp.actor_id')
                ->where('lc.action_type', 'checkout')
                ->whereNull('lc.return_date')
                ->where('lc.due_date', '<', $today)
                ->select([
                    'lc.id',
                    'lc.checkout_date',
                    'lc.due_date',
                    'lc.renewals',
                    'li.barcode as item_barcode',
                    'li.call_number',
                    'ioi.title as item_title',
                    'ai.authorized_form_of_name as patron_name',
                    'lp.barcode as patron_barcode',
                    DB::raw("DATEDIFF('{$today}', lc.due_date) as days_overdue"),
                ])
                ->orderBy('lc.due_date', 'asc')
                ->offset($offset)
                ->limit($this->limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->total = 0;
            $this->overdueItems = [];
        }

        $this->totalPages = $this->total > 0 ? (int) ceil($this->total / $this->limit) : 1;
    }
}
