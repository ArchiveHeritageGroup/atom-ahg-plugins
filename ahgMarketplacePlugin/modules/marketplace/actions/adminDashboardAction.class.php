<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/TransactionService.php';
require_once $pluginPath . '/lib/Repositories/SellerRepository.php';
require_once $pluginPath . '/lib/Repositories/ListingRepository.php';
require_once $pluginPath . '/lib/Repositories/TransactionRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceAdminDashboardAction extends AhgController
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

        $sellerRepo = new SellerRepository();
        $listingRepo = new ListingRepository();
        $txnRepo = new TransactionRepository();
        $txnService = new TransactionService();

        // Totals
        $this->totalSellers = DB::table('marketplace_seller')->count();
        $this->totalListings = DB::table('marketplace_listing')->count();
        $this->totalTransactions = DB::table('marketplace_transaction')->count();
        $this->totalRevenue = DB::table('marketplace_transaction')
            ->where('payment_status', 'paid')
            ->sum('sale_price') ?? 0;

        // Pending counts
        $this->pendingListings = DB::table('marketplace_listing')
            ->where('status', 'pending_review')
            ->count();
        $this->unverifiedSellers = DB::table('marketplace_seller')
            ->where('verification_status', 'unverified')
            ->count();
        $this->pendingPayoutsCount = DB::table('marketplace_payout')
            ->where('status', 'pending')
            ->count();

        // Recent transactions (5)
        $recentResult = $txnRepo->getAllForAdmin([], 5, 0);
        $this->recentTransactions = $recentResult['items'];

        // Monthly revenue
        $this->monthlyRevenue = $txnService->getMonthlyRevenue(null, 12);
    }
}
