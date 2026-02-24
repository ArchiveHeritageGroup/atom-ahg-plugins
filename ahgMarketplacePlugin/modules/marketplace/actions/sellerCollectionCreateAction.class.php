<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/CollectionService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CollectionService;

class marketplaceSellerCollectionCreateAction extends AhgController
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

        // Handle POST: create collection
        if ($request->isMethod('post')) {
            $title = trim($request->getParameter('title', ''));
            $description = trim($request->getParameter('description', ''));
            $isPublic = $request->getParameter('is_public', 1) ? 1 : 0;

            // Validate
            $errors = [];
            if (empty($title)) {
                $errors[] = 'Collection title is required.';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));
            } else {
                $data = [
                    'title' => $title,
                    'description' => $description ?: null,
                    'is_public' => $isPublic,
                ];

                // Handle cover image upload
                $coverFile = $request->getFiles('cover_image');
                if ($coverFile && $coverFile['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/marketplace/collections';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    $ext = pathinfo($coverFile['name'], PATHINFO_EXTENSION);
                    $filename = 'collection_' . $this->seller->id . '_' . time() . '.' . $ext;
                    $destination = $uploadDir . '/' . $filename;

                    if (move_uploaded_file($coverFile['tmp_name'], $destination)) {
                        $data['cover_image_path'] = '/uploads/marketplace/collections/' . $filename;
                    }
                }

                $collectionService = new CollectionService();
                $result = $collectionService->createCollection($this->seller->id, $data);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Collection created successfully.');
                    $this->redirect(['module' => 'marketplace', 'action' => 'sellerCollections']);
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            }
        }
    }
}
