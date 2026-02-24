<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/CollectionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CollectionService;

class marketplaceSellerCollectionsAction extends AhgController
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

        $collectionService = new CollectionService();
        $this->collections = $collectionService->getSellerCollections($this->seller->id);

        // Handle POST: delete collection
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');

            if ($formAction === 'delete') {
                $collectionId = (int) $request->getParameter('collection_id');
                if ($collectionId) {
                    $result = $collectionService->deleteCollection($collectionId);

                    if ($result['success']) {
                        $this->getUser()->setFlash('notice', 'Collection deleted.');
                    } else {
                        $this->getUser()->setFlash('error', $result['error']);
                    }

                    // Reload collections
                    $this->collections = $collectionService->getSellerCollections($this->seller->id);
                }
            }
        }
    }
}
