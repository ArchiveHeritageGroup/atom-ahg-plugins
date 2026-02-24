<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/TransactionService.php';
require_once $pluginPath . '/lib/Services/OfferService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\OfferService;

class marketplaceDashboardAction extends AhgController
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

        // Auto-provision seller profile for admins
        if (!$this->seller && $this->context->user->isAdministrator()) {
            $user = \Illuminate\Database\Capsule\Manager::table('user')
                ->where('id', $userId)->first();
            $actor = $user ? \Illuminate\Database\Capsule\Manager::table('actor_i18n')
                ->where('id', $user->id)->where('culture', 'en')->first() : null;
            $displayName = ($actor->authorized_form_of_name ?? null) ?: 'Platform Owner';

            $result = $sellerService->register($userId, [
                'display_name' => $displayName,
                'seller_type' => 'institution',
                'email' => $user->email ?? null,
                'sectors' => ['gallery', 'museum', 'archive', 'library', 'dam'],
            ]);

            if ($result['success']) {
                // Auto-verify and set premium trust for admin
                $sellerService->verifySeller($result['id'], $userId, 'Auto-verified platform owner');
                \Illuminate\Database\Capsule\Manager::table('marketplace_seller')
                    ->where('id', $result['id'])
                    ->update(['trust_level' => 'premium']);
                $this->seller = $sellerService->getSellerByUserId($userId);
            }
        }

        if (!$this->seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        // Dashboard stats
        $this->stats = $sellerService->getDashboardStats($this->seller->id);

        // Recent transactions (limit 5)
        $transactionService = new TransactionService();
        $recentResult = $transactionService->getSellerTransactions($this->seller->id, 5, 0);
        $this->recentTransactions = $recentResult['items'];

        // Pending offers count
        $offerService = new OfferService();
        $pendingResult = $offerService->getSellerOffers($this->seller->id, 'pending', 1, 0);
        $this->pendingOfferCount = $pendingResult['total'];
    }
}
