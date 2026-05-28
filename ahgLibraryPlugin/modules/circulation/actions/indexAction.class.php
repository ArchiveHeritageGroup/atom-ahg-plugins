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
            $this->recentTransactions = DB::table('library_checkout as c')
                ->leftJoin('library_copy as cp', 'c.copy_id', '=', 'cp.id')
                ->leftJoin('library_item as li', 'cp.library_item_id', '=', 'li.id')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('li.information_object_id', '=', 'ioi.id')
                         ->where('ioi.culture', '=', 'en');
                })
                ->leftJoin('library_patron as lp', 'c.patron_id', '=', 'lp.id')
                ->select([
                    'c.id',
                    DB::raw("CASE WHEN c.return_date IS NULL THEN 'checkout' ELSE 'checkin' END as action_type"),
                    'c.checkout_date',
                    'c.due_date',
                    'c.return_date',
                    'c.renewed_count as renewals',
                    'cp.barcode as item_barcode',
                    'ioi.title as item_title',
                    DB::raw("TRIM(CONCAT(COALESCE(lp.first_name, ''), ' ', COALESCE(lp.last_name, ''))) as patron_name"),
                ])
                ->orderBy('c.created_at', 'desc')
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
                'checkedOut' => DB::table('library_checkout')
                    ->whereNull('return_date')
                    ->count(),
                'overdueCount' => DB::table('library_checkout')
                    ->whereNull('return_date')
                    ->where('due_date', '<', date('Y-m-d'))
                    ->count(),
                'todayTransactions' => DB::table('library_checkout')
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
                ->where('lp.card_number', $barcode)
                ->select([
                    'lp.id',
                    'lp.actor_id',
                    'lp.card_number as barcode',
                    'lp.patron_type',
                    'lp.borrowing_status as patron_status',
                    DB::raw("TRIM(CONCAT(COALESCE(lp.first_name, ''), ' ', COALESCE(lp.last_name, ''))) as name"),
                ])
                ->first();

            if ($patron) {
                // Count active checkouts
                $activeCheckouts = DB::table('library_checkout')
                    ->where('patron_id', $patron->id)
                    ->whereNull('return_date')
                    ->count();

                // Count overdue items
                $overdueItems = DB::table('library_checkout')
                    ->where('patron_id', $patron->id)
                    ->whereNull('return_date')
                    ->where('due_date', '<', date('Y-m-d'))
                    ->count();

                // Get outstanding fines
                $fines = DB::table('library_fine')
                    ->where('patron_id', $patron->id)
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
