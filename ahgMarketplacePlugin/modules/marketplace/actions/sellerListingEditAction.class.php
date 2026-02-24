<?php

use AtomFramework\Http\Controllers\AhgController;

$pluginPath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin';
require_once $pluginPath . '/lib/Services/SellerService.php';
require_once $pluginPath . '/lib/Services/MarketplaceService.php';
require_once $pluginPath . '/lib/Services/CurrencyService.php';
require_once $pluginPath . '/lib/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\SellerService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CurrencyService;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class marketplaceSellerListingEditAction extends AhgController
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
        if ((int) $this->listing->seller_id !== (int) $this->seller->id) {
            $this->getUser()->setFlash('error', 'You do not have permission to edit this listing.');
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerListings']);
        }

        $settingsRepo = new SettingsRepository();
        $currencyService = new CurrencyService();

        $this->sectors = ['gallery', 'museum', 'archive', 'library', 'dam'];
        $this->categories = $settingsRepo->getCategories();
        $this->currencies = $currencyService->getCurrencies();

        // Handle POST: update listing
        if ($request->isMethod('post')) {
            $data = [
                'title' => trim($request->getParameter('title', '')),
                'sector' => $request->getParameter('sector', $this->listing->sector),
                'listing_type' => $request->getParameter('listing_type', $this->listing->listing_type),
                'category_id' => $request->getParameter('category_id') ?: null,
                'description' => trim($request->getParameter('description', '')),
                'price' => $request->getParameter('price') ? (float) $request->getParameter('price') : null,
                'currency' => $request->getParameter('currency', 'ZAR'),
                'minimum_offer' => $request->getParameter('minimum_offer') ? (float) $request->getParameter('minimum_offer') : null,
                'artist_name' => trim($request->getParameter('artist_name', '')),
                'medium' => trim($request->getParameter('medium', '')),
                'dimensions' => trim($request->getParameter('dimensions', '')),
                'year_created' => trim($request->getParameter('year_created', '')),
                'provenance' => trim($request->getParameter('provenance', '')),
                'condition_rating' => $request->getParameter('condition_rating') ?: null,
                'condition_notes' => trim($request->getParameter('condition_notes', '')),
                'is_digital' => $request->getParameter('is_digital') ? 1 : 0,
                'requires_shipping' => $request->getParameter('requires_shipping') ? 1 : 0,
                'shipping_from_country' => trim($request->getParameter('shipping_from_country', '')),
                'shipping_domestic_price' => $request->getParameter('shipping_domestic_price') ? (float) $request->getParameter('shipping_domestic_price') : null,
                'shipping_international_price' => $request->getParameter('shipping_international_price') ? (float) $request->getParameter('shipping_international_price') : null,
            ];

            // Validate required fields
            $errors = [];
            if (empty($data['title'])) {
                $errors[] = 'Title is required.';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));
            } else {
                $result = $marketplaceService->updateListing($listingId, $data);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Listing updated successfully.');
                    // Reload listing data
                    $this->listing = $marketplaceService->getListingById($listingId);
                } else {
                    $this->getUser()->setFlash('error', $result['error']);
                }
            }
        }
    }
}
