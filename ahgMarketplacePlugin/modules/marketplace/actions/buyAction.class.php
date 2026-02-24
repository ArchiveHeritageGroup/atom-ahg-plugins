<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/TransactionService.php';
require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/MarketplaceService.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Services\TransactionService;
use AtomAhgPlugins\ahgMarketplacePlugin\Services\MarketplaceService;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceBuyAction extends AhgController
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

        // Get listing
        $listing = DB::table('marketplace_listing')->where('slug', $slug)->first();
        if (!$listing) {
            $this->forward404();
        }

        // Validate listing is active
        if ($listing->status !== 'active') {
            $this->getUser()->setFlash('error', 'This listing is no longer available.');
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        // Validate fixed price type (or auction buy-now)
        if ($listing->listing_type === 'offer_only') {
            $this->getUser()->setFlash('error', 'This listing only accepts offers.');
            $this->redirect(['module' => 'marketplace', 'action' => 'offerForm', 'slug' => $slug]);
        }

        $transactionService = new TransactionService();

        if ($listing->listing_type === 'auction') {
            // Handle auction buy-now
            $auction = DB::table('marketplace_auction')
                ->where('listing_id', $listing->id)
                ->first();

            if (!$auction || !$auction->buy_now_price) {
                $this->getUser()->setFlash('error', 'Buy Now is not available for this auction.');
                $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
            }

            require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Services/AuctionService.php';
            $auctionService = new \AtomAhgPlugins\ahgMarketplacePlugin\Services\AuctionService();
            $buyResult = $auctionService->buyNow($auction->id, $userId);

            if (!$buyResult['success']) {
                $this->getUser()->setFlash('error', $buyResult['error']);
                $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
            }

            // Create transaction from auction
            $result = $transactionService->createFromAuction($auction->id);
        } else {
            // Fixed price purchase
            $result = $transactionService->createFromFixedPrice($listing->id, $userId);
        }

        if (!$result['success']) {
            $this->getUser()->setFlash('error', $result['error']);
            $this->redirect(['module' => 'marketplace', 'action' => 'listing', 'slug' => $slug]);
        }

        // Try to integrate with ahgCartPlugin if available
        $cartPluginEnabled = DB::table('atom_plugin')
            ->where('name', 'ahgCartPlugin')
            ->where('is_enabled', 1)
            ->exists();

        if ($cartPluginEnabled) {
            // Add to cart and redirect there
            try {
                $cartItemData = [
                    'user_id' => $userId,
                    'item_type' => 'marketplace_listing',
                    'item_id' => $listing->id,
                    'title' => $listing->title,
                    'quantity' => 1,
                    'unit_price' => $result['transaction']->grand_total ?? $listing->price,
                    'currency' => $listing->currency,
                    'metadata' => json_encode([
                        'transaction_id' => $result['transaction_id'],
                        'listing_slug' => $listing->slug,
                        'featured_image_path' => $listing->featured_image_path,
                    ]),
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                if (DB::getSchemaBuilder()->hasTable('cart_item')) {
                    DB::table('cart_item')->insert($cartItemData);
                    $this->getUser()->setFlash('notice', 'Item added to your cart.');
                    $this->redirect(['module' => 'cart', 'action' => 'index']);
                }
            } catch (\Exception $e) {
                // Cart integration failed, fall through to default redirect
            }
        }

        // Default: redirect to purchases
        $this->getUser()->setFlash('notice', 'Purchase initiated. Transaction #' . ($result['transaction']->transaction_number ?? $result['transaction_id']) . ' created.');
        $this->redirect(['module' => 'marketplace', 'action' => 'myPurchases']);
    }
}
