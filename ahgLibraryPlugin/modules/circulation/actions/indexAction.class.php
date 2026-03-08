<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Circulation dashboard — checkout station.
 *
 * Displays barcode scanning inputs, current patron info,
 * and recent circulation transactions.
 */
class circulationIndexAction extends AhgController
{
    public function execute($request)
    {
        
        // Load framework
        require_once $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';

        // Flash messages from checkout/checkin/renew actions
        $this->notice = $this->getUser()->getFlash('notice');
        $this->error = $this->getUser()->getFlash('error');

        // Load recent transactions (last 50)
        try {
            $this->recentTransactions = DB::table('library_circulation as lc')
                ->leftJoin('library_item as li', 'lc.library_item_id', '=', 'li.id')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('li.information_object_id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('lc.patron_id', '=', 'ai.id')
                         ->where('ai.culture', '=', 'en');
                })
                ->select([
                    'lc.id',
                    'lc.action_type',
                    'lc.checkout_date',
                    'lc.due_date',
                    'lc.return_date',
                    'lc.renewals',
                    'li.barcode as item_barcode',
                    'li.call_number',
                    'ioi.title as item_title',
                    'ai.authorized_form_of_name as patron_name',
                ])
                ->orderBy('lc.created_at', 'desc')
                ->limit(50)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            $this->recentTransactions = [];
        }

        // Load patron info if patron barcode provided (for display area)
        $patronBarcode = $request->getParameter('patron_barcode');
        $this->patronBarcode = $patronBarcode;
        $this->patronInfo = null;

        if (!empty($patronBarcode)) {
            $this->loadPatronInfo($patronBarcode);
        }

        // Circulation stats
        try {
            $this->stats = [
                'checkedOut' => DB::table('library_circulation')
                    ->where('action_type', 'checkout')
                    ->whereNull('return_date')
                    ->count(),
                'overdueCount' => DB::table('library_circulation')
                    ->where('action_type', 'checkout')
                    ->whereNull('return_date')
                    ->where('due_date', '<', date('Y-m-d'))
                    ->count(),
                'todayTransactions' => DB::table('library_circulation')
                    ->whereDate('created_at', date('Y-m-d'))
                    ->count(),
            ];
        } catch (\Exception $e) {
            $this->stats = [
                'checkedOut' => 0,
                'overdueCount' => 0,
                'todayTransactions' => 0,
            ];
        }
    }

    /**
     * Load patron information by barcode.
     */
    protected function loadPatronInfo(string $barcode): void
    {
        try {
            // Look up patron by barcode in library_patron table
            $patron = DB::table('library_patron as lp')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('lp.actor_id', '=', 'ai.id')
                         ->where('ai.culture', '=', 'en');
                })
                ->where('lp.barcode', $barcode)
                ->select([
                    'lp.id',
                    'lp.actor_id',
                    'lp.barcode',
                    'lp.patron_type',
                    'lp.status as patron_status',
                    'ai.authorized_form_of_name as name',
                ])
                ->first();

            if ($patron) {
                // Count active checkouts
                $activeCheckouts = DB::table('library_circulation')
                    ->where('patron_id', $patron->actor_id)
                    ->where('action_type', 'checkout')
                    ->whereNull('return_date')
                    ->count();

                // Count overdue items
                $overdueItems = DB::table('library_circulation')
                    ->where('patron_id', $patron->actor_id)
                    ->where('action_type', 'checkout')
                    ->whereNull('return_date')
                    ->where('due_date', '<', date('Y-m-d'))
                    ->count();

                // Get outstanding fines
                $fines = DB::table('library_fine')
                    ->where('patron_id', $patron->actor_id)
                    ->where('status', 'outstanding')
                    ->sum('amount');

                $this->patronInfo = (object) [
                    'id' => $patron->id,
                    'actor_id' => $patron->actor_id,
                    'barcode' => $patron->barcode,
                    'name' => $patron->name ?? __('Unknown'),
                    'patron_type' => $patron->patron_type,
                    'patron_status' => $patron->patron_status,
                    'active_checkouts' => $activeCheckouts,
                    'overdue_items' => $overdueItems,
                    'outstanding_fines' => $fines ?: 0,
                ];
            }
        } catch (\Exception $e) {
            // Tables may not exist yet; patron info stays null
        }
    }
}
