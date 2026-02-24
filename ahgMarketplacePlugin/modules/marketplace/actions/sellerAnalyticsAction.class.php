<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/TransactionService.php';
require_once $pluginPath . '/lib/Repositories/ListingRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceSellerAnalyticsAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $sellerService = new SellerService();
        $this->seller = $sellerService->getSellerByUserId($userId);

        if (!$this->seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        $transactionService = new TransactionService();

        // Monthly revenue chart data (last 12 months)
        $this->monthlyRevenue = $transactionService->getMonthlyRevenue($this->seller->id, 12);

        // Revenue stats
        $this->revenueStats = $transactionService->getRevenueStats($this->seller->id);

        // Top listings by views
        $this->topListings = DB::table('marketplace_listing')
            ->where('seller_id', $this->seller->id)
            ->whereIn('status', ['active', 'sold', 'reserved'])
            ->orderBy('view_count', 'DESC')
            ->limit(10)
            ->get()
            ->all();

        // Top listings by sales (most transactions)
        $this->topSelling = DB::table('marketplace_transaction as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->select('l.id', 'l.title', 'l.slug', 'l.featured_image_path', DB::raw('COUNT(t.id) as sales_count'), DB::raw('SUM(t.seller_amount) as total_earned'))
            ->where('t.seller_id', $this->seller->id)
            ->where('t.payment_status', 'paid')
            ->groupBy('l.id', 'l.title', 'l.slug', 'l.featured_image_path')
            ->orderBy('sales_count', 'DESC')
            ->limit(10)
            ->get()
            ->all();

        // Sales by sector breakdown
        $this->sectorBreakdown = DB::table('marketplace_transaction as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->select('l.sector', DB::raw('COUNT(t.id) as count'), DB::raw('SUM(t.seller_amount) as revenue'))
            ->where('t.seller_id', $this->seller->id)
            ->where('t.payment_status', 'paid')
            ->groupBy('l.sector')
            ->orderBy('revenue', 'DESC')
            ->get()
            ->all();
    }
}
