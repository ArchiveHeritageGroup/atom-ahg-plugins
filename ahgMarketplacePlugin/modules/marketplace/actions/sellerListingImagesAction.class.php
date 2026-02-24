<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/MarketplaceService.php';
require_once $pluginPath . '/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceSellerListingImagesAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $sellerService = new SellerService();
        $seller = $sellerService->getSellerByUserId($userId);

        if (!$seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        $listingId = (int) $request->getParameter('id');
        if (!$listingId) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerListings']);
        }

        $marketplaceService = new MarketplaceService();
        $this->listing = $marketplaceService->getListingById($listingId);

        if (!$this->listing) {
            $this->forward404();
        }

        // Verify seller owns this listing
        if ((int) $this->listing->seller_id !== (int) $seller->id) {
            $this->getUser()->setFlash('error', 'You do not have permission to manage this listing.');
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerListings']);
        }

        $settingsRepo = new SettingsRepository();
        $this->maxImages = (int) $settingsRepo->get('max_images_per_listing', 10);

        // Handle POST actions
        if ($request->isMethod('post')) {
            $formAction = $request->getParameter('form_action', '');

            if ($formAction === 'upload') {
                $this->handleUpload($request, $marketplaceService, $listingId);
            } elseif ($formAction === 'set_primary') {
                $imageId = (int) $request->getParameter('image_id');
                if ($imageId) {
                    $marketplaceService->setPrimaryImage($listingId, $imageId);
                    $this->getUser()->setFlash('notice', 'Primary image updated.');
                }
            } elseif ($formAction === 'delete') {
                $imageId = (int) $request->getParameter('image_id');
                if ($imageId) {
                    $marketplaceService->deleteListingImage($imageId);
                    $this->getUser()->setFlash('notice', 'Image removed.');
                }
            }
        }

        // Reload images after any action
        $this->images = $marketplaceService->getListingImages($listingId);
    }

    private function handleUpload($request, MarketplaceService $service, int $listingId): void
    {
        $file = $request->getFiles('listing_image');
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->getUser()->setFlash('error', 'Please select an image to upload.');

            return;
        }

        // Validate current count against max
        $currentImages = $service->getListingImages($listingId);
        if (count($currentImages) >= $this->maxImages) {
            $this->getUser()->setFlash('error', 'Maximum image limit reached (' . $this->maxImages . ').');

            return;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $this->getUser()->setFlash('error', 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP.');

            return;
        }

        // Validate file size (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $this->getUser()->setFlash('error', 'File size exceeds the 10 MB limit.');

            return;
        }

        $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/marketplace/listings/' . $listingId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'img_' . $listingId . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $destination = $uploadDir . '/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $caption = trim($request->getParameter('image_caption', ''));

            $service->addListingImage($listingId, [
                'file_path' => '/uploads/marketplace/listings/' . $listingId . '/' . $filename,
                'original_filename' => $file['name'],
                'mime_type' => $file['type'],
                'file_size' => $file['size'],
                'caption' => $caption ?: null,
                'sort_order' => count($currentImages),
            ]);

            $this->getUser()->setFlash('notice', 'Image uploaded successfully.');
        } else {
            $this->getUser()->setFlash('error', 'Failed to upload image. Please try again.');
        }
    }
}
