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

class marketplaceSellerListingCreateAction extends AhgController
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

        // Auto-provision for admins — redirect to dashboard which handles it
        if (!$this->seller && $this->context->user->isAdministrator()) {
            $this->redirect(['module' => 'marketplace', 'action' => 'dashboard']);
        }

        if (!$this->seller) {
            $this->redirect(['module' => 'marketplace', 'action' => 'sellerRegister']);
        }

        $settingsRepo = new SettingsRepository();
        $currencyService = new CurrencyService();

        $this->sectors = ['gallery', 'museum', 'archive', 'library', 'dam'];
        $this->categories = $settingsRepo->getCategories();
        $this->currencies = $currencyService->getCurrencies();

        // Pre-fill from information object if ?io= parameter provided
        $this->prefill = null;
        $ioId = (int) $request->getParameter('io', 0);
        if ($ioId > 0) {
            $io = \Illuminate\Database\Capsule\Manager::table('information_object AS io')
                ->leftJoin('information_object_i18n AS i18n', function ($j) {
                    $j->on('i18n.id', '=', 'io.id')->where('i18n.culture', '=', 'en');
                })
                ->leftJoin('slug', function ($j) {
                    $j->on('slug.object_id', '=', 'io.id');
                })
                ->where('io.id', $ioId)
                ->select('io.id', 'i18n.title', 'i18n.scope_and_content', 'i18n.extent_and_medium',
                         'i18n.archival_history', 'slug.slug')
                ->first();
            if ($io) {
                // Detect sector from display_object_config
                $doc = \Illuminate\Database\Capsule\Manager::table('display_object_config')
                    ->where('information_object_id', $ioId)->first();
                $sector = $doc->glam_type ?? 'archive';

                $this->prefill = (object) [
                    'information_object_id' => $io->id,
                    'title' => $io->title ?? '',
                    'description' => $io->scope_and_content ?? '',
                    'medium' => $io->extent_and_medium ?? '',
                    'provenance' => $io->archival_history ?? '',
                    'sector' => $sector,
                    'slug' => $io->slug ?? '',
                ];
            }
        }

        // Handle POST: create listing
        if ($request->isMethod('post')) {
            $title = trim($request->getParameter('title', ''));
            $sector = $request->getParameter('sector', '');
            $listingType = $request->getParameter('listing_type', 'fixed_price');

            // Validate required fields
            $errors = [];
            if (empty($title)) {
                $errors[] = 'Title is required.';
            }
            if (empty($sector)) {
                $errors[] = 'Sector is required.';
            }
            if (!in_array($listingType, ['fixed_price', 'auction', 'offer_only'])) {
                $errors[] = 'Invalid listing type.';
            }

            if (!empty($errors)) {
                $this->getUser()->setFlash('error', implode(' ', $errors));
            } else {
                $data = [
                    'title' => $title,
                    'sector' => $sector,
                    'listing_type' => $listingType,
                    'information_object_id' => $request->getParameter('information_object_id') ?: null,
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

                $marketplaceService = new MarketplaceService();
                $result = $marketplaceService->createListing($this->seller->id, $data);

                if ($result['success']) {
                    $this->getUser()->setFlash('notice', 'Listing created. Add images to complete your listing.');
                    $this->redirect(['module' => 'marketplace', 'action' => 'sellerListingImages', 'id' => $result['id']]);
                } else {
                    $this->getUser()->setFlash('error', $result['error'] ?? 'Failed to create listing.');
                }
            }
        }
    }
}
