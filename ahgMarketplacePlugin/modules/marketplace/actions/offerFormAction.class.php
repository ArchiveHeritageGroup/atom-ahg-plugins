<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/OfferService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/CurrencyService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\OfferService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\CurrencyService;

class marketplaceOfferFormAction extends AhgController
{
    public function execute($request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->getUser()->setFlash('error', 'Please log in to continue.');
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $userId = (int) $this->context->user->getAttribute('user_id');
        $slug = $request->getParameter('slug');

        if (empty($slug)) {
            $this->redirect(['module' => 'marketplace', 'action' => 'browse']);
        }

        $marketplaceService = new MarketplaceService();
        $offerService = new OfferService();
        $currencyService = new CurrencyService();

        // Get listing without incrementing view count
        $listing = $marketplaceService->getListingById(
            \Illuminate\Database\Capsule\Manager::table('marketplace_listing')
                ->where('slug', $slug)->value('id') ?? 0
        );

        if (!$listing) {
            $this->forward404();
        }

        // Validate listing accepts offers
        if ($listing->status !== 'active') {
            $this->getUser()->setFlash('error', 'This listing is no longer available.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        if ($listing->listing_type === 'auction') {
            $this->getUser()->setFlash('error', 'Cannot make offers on auction listings. Please place a bid instead.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        // Get primary image
        $images = $marketplaceService->getListingImages($listing->id);
        $primaryImage = null;
        foreach ($images as $img) {
            if ($img->is_primary) {
                $primaryImage = $img;
                break;
            }
        }
        if (!$primaryImage && !empty($images)) {
            $primaryImage = $images[0];
        }

        // Handle POST: create offer
        if ($request->isMethod('post')) {
            $amount = (float) $request->getParameter('offer_amount');
            $message = trim($request->getParameter('message', ''));

            $result = $offerService->createOffer($listing->id, $userId, $amount, $message ?: null);

            if ($result['success']) {
                $this->getUser()->setFlash('notice', 'Your offer has been submitted successfully.');
                $this->redirect(['module' => 'marketplace', 'action' => 'myOffers']);
            } else {
                $this->getUser()->setFlash('error', $result['error']);
            }
        }

        $this->listing = $listing;
        $this->primaryImage = $primaryImage;
        $this->currencies = $currencyService->getCurrencies();
    }
}
