<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/CollectionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\CollectionService;

class marketplaceCollectionAction extends AhgController
{
    public function execute($request)
    {
        $slug = $request->getParameter('slug');
        if (empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $collectionService = new CollectionService();

        // Get collection with its items
        $data = $collectionService->getCollection($slug);
        if (!$data) {
            $this->forward404();
        }

        // Only show public collections to visitors
        if (!$data['collection']->is_public) {
            // Allow if authenticated user owns this collection
            $allowed = false;
            if ($this->getUser()->isAuthenticated()) {
                $userId = (int) $this->getUser()->getAttribute('user_id');
                $createdBy = $data['collection']->created_by ?? null;
                if ($createdBy && $createdBy == $userId) {
                    $allowed = true;
                }
            }

            if (!$allowed) {
                $this->forward404();
            }
        }

        $this->collection = $data['collection'];
        $this->items = $data['items'];
    }
}
