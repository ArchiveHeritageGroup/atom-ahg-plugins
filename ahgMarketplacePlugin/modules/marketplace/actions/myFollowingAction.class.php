<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceMyFollowingAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $settingsRepo = new SettingsRepository();
        $sellerRepo = new SellerRepository();

        $page = max(1, (int) $request->getParameter('page', 1));
        $limit = 24;
        $offset = ($page - 1) * $limit;

        $result = $settingsRepo->getFollowedSellers($userId, $limit, $offset);

        // Enrich sellers with listing counts
        foreach ($result['items'] as &$seller) {
            $seller->listing_count = DB::table('marketplace_listing')
                ->where('seller_id', $seller->id)
                ->where('status', 'active')
                ->count();
        }
        unset($seller);

        $this->sellers = $result['items'];
        $this->total = $result['total'];
        $this->page = $page;
        $this->limit = $limit;
    }
}
