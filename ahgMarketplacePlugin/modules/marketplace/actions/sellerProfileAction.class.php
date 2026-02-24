<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/CurrencyService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CurrencyService;

class marketplaceSellerProfileAction extends AhgController
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

        $currencyService = new CurrencyService();
        $this->currencies = $currencyService->getCurrencies();

        // Handle POST: update profile
        if ($request->isMethod('post')) {
            $data = [
                'display_name' => trim($request->getParameter('display_name', '')),
                'seller_type' => $request->getParameter('seller_type', $this->seller->seller_type),
                'bio' => trim($request->getParameter('bio', '')),
                'country' => trim($request->getParameter('country', '')),
                'city' => trim($request->getParameter('city', '')),
                'website' => trim($request->getParameter('website', '')),
                'instagram' => trim($request->getParameter('instagram', '')),
                'email' => trim($request->getParameter('email', '')),
                'phone' => trim($request->getParameter('phone', '')),
                'payout_method' => $request->getParameter('payout_method', 'bank_transfer'),
                'payout_currency' => $request->getParameter('payout_currency', 'ZAR'),
            ];

            // Sectors multiselect
            $selectedSectors = $request->getParameter('sectors');
            if (is_array($selectedSectors)) {
                $data['sectors'] = $selectedSectors;
            }

            // Handle avatar upload
            $avatarFile = $request->getFiles('avatar');
            if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/marketplace/avatars';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $ext = pathinfo($avatarFile['name'], PATHINFO_EXTENSION);
                $filename = 'avatar_' . $this->seller->id . '_' . time() . '.' . $ext;
                $destination = $uploadDir . '/' . $filename;

                if (move_uploaded_file($avatarFile['tmp_name'], $destination)) {
                    $data['avatar_path'] = '/uploads/marketplace/avatars/' . $filename;
                }
            }

            // Handle banner upload
            $bannerFile = $request->getFiles('banner');
            if ($bannerFile && $bannerFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = sfConfig::get('sf_root_dir') . '/uploads/marketplace/banners';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }

                $ext = pathinfo($bannerFile['name'], PATHINFO_EXTENSION);
                $filename = 'banner_' . $this->seller->id . '_' . time() . '.' . $ext;
                $destination = $uploadDir . '/' . $filename;

                if (move_uploaded_file($bannerFile['tmp_name'], $destination)) {
                    $data['banner_path'] = '/uploads/marketplace/banners/' . $filename;
                }
            }

            $result = $sellerService->updateProfile($this->seller->id, $data);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Profile updated successfully.');
                // Reload seller data after update
                $this->seller = $sellerService->getSellerByUserId($userId);
            } else {
                $this->getUser()->setFlash('error', $result['error']);
            }
        }
    }
}
