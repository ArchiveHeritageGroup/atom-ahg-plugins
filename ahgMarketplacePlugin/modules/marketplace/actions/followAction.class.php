<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SettingsRepository.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class marketplaceFollowAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            if ($request->isXmlHttpRequest()) {
                $this->response->setContentType('application/json');
                echo json_encode(['error' => 'Not authenticated']);
                throw new sfStopException();
            }
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        // POST only
        if (!$request->isMethod('post')) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');
        $sellerSlug = $request->getParameter('seller');

        $settingsRepo = new SettingsRepository();
        $sellerRepo = new SellerRepository();

        // Resolve seller by slug
        $seller = $sellerRepo->getBySlug($sellerSlug);
        if (!$seller) {
            if ($request->isXmlHttpRequest()) {
                $this->response->setContentType('application/json');
                echo json_encode(['error' => 'Seller not found']);
                throw new sfStopException();
            }
            $this->getUser()->setFlash('error', 'Seller not found.');
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $followed = $settingsRepo->toggleFollow($userId, $seller->id);

        // JSON response for AJAX
        if ($request->isXmlHttpRequest()) {
            $this->response->setContentType('application/json');
            echo json_encode([
                'success' => true,
                'followed' => $followed,
            ]);
            throw new sfStopException();
        }

        // Standard redirect for non-AJAX
        $this->getUser()->setFlash('notice', $followed ? 'You are now following this seller.' : 'You have unfollowed this seller.');
        $this->redirect(['module' => 'marketplace', 'action' => 'seller', 'slug' => $sellerSlug]);
    }
}
