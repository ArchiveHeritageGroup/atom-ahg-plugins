<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;

class marketplaceSellerRegisterAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');

        $sellerService = new SellerService();

        // If already registered, redirect to dashboard
        $existing = $sellerService->getSellerByUserId($userId);
        if ($existing) {
            $this->redirect(['module' => 'marketplace', 'action' => 'dashboard']);
        }

        // Sectors for multiselect
        $this->sectors = ['gallery', 'museum', 'archive', 'library', 'dam'];

        // Handle POST: register as seller
        if ($request->isMethod('post')) {
            $displayName = trim($request->getParameter('display_name', ''));
            $sellerType = $request->getParameter('seller_type', 'artist');
            $email = trim($request->getParameter('email', ''));
            $acceptTerms = $request->getParameter('accept_terms');

            // Validate
            $errors = [];
            if (empty($displayName)) {
                $errors[] = 'Display name is required.';
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email address is required.';
            }
            if (!$acceptTerms) {
                $errors[] = 'You must accept the terms and conditions.';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));
            } else {
                $data = [
                    'display_name' => $displayName,
                    'seller_type' => $sellerType,
                    'email' => $email,
                    'bio' => trim($request->getParameter('bio', '')),
                    'country' => trim($request->getParameter('country', '')),
                    'city' => trim($request->getParameter('city', '')),
                    'website' => trim($request->getParameter('website', '')),
                    'instagram' => trim($request->getParameter('instagram', '')),
                    'phone' => trim($request->getParameter('phone', '')),
                ];

                // Sectors multiselect
                $selectedSectors = $request->getParameter('sectors');
                if (is_array($selectedSectors) && !empty($selectedSectors)) {
                    $data['sectors'] = $selectedSectors;
                }

                $result = $sellerService->register($userId, $data);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Welcome! Your seller profile has been created.');
                    $this->redirect(['module' => 'marketplace', 'action' => 'dashboard']);
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            }
        }
    }
}
