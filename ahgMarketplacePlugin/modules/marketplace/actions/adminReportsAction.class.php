<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/TransactionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceAdminReportsAction extends AhgController
{
    public function execute($request)
    {
        // Admin check
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }
        if (!$this->context->user->hasCredential('administrator')) {
            $this->getUser()->setFlash('error', 'Admin access required.');
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $txnService = new TransactionService();

        // Revenue stats (total sales, total revenue, commission, seller amount)
        $this->revenueStats = $txnService->getRevenueStats();

        // Monthly revenue (last 12 months)
        $this->monthlyRevenue = $txnService->getMonthlyRevenue(null, 12);

        // Top sellers by revenue
        $this->topSellers = DB::table('marketplace_transaction as t')
            ->join('marketplace_seller as s', 't.seller_id', '=', 's.id')
            ->where('t.payment_status', 'paid')
            ->select(
                's.id',
                's.display_name',
                's.slug',
                DB::raw('COUNT(t.id) as sales_count'),
                DB::raw('SUM(t.sale_price) as total_revenue'),
                DB::raw('SUM(t.platform_commission_amount) as total_commission')
            )
            ->groupBy('s.id', 's.display_name', 's.slug')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->all();

        // Top items by sales count
        $this->topItems = DB::table('marketplace_transaction as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->where('t.payment_status', 'paid')
            ->select(
                'l.id',
                'l.title',
                'l.slug',
                'l.sector',
                DB::raw('COUNT(t.id) as sales_count'),
                DB::raw('SUM(t.sale_price) as total_revenue')
            )
            ->groupBy('l.id', 'l.title', 'l.slug', 'l.sector')
            ->orderByDesc('sales_count')
            ->limit(10)
            ->get()
            ->all();
    }
}
