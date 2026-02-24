<?php

use AtomFramework\Http\Controllers\AhgController;

require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgMarketplacePlugin/lib/Repositories/ListingRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use Illuminate\Database\Capsule\Manager as DB;

class marketplaceApiFavouriteAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Auth required
        if (!$this->context->user->isAuthenticated()) {
            $this->getResponse()->setStatusCode(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required']);

            return sfView::NONE;
        }

        // POST only
        if (!$request->isMethod('post')) {
            $this->getResponse()->setStatusCode(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);

            return sfView::NONE;
        }

        $userId = (int) $this->context->user->getAttribute('user_id');
        $listingId = (int) $request->getParameter('id');

        if ($listingId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid listing ID']);

            return sfView::NONE;
        }

        $listingRepo = new ListingRepository();

        // Verify listing exists
        $listing = $listingRepo->getById($listingId);
        if (!$listing) {
            echo json_encode(['success' => false, 'error' => 'Listing not found']);

            return sfView::NONE;
        }

        // Check if ahgFavoritesPlugin is available
        $favPluginEnabled = DB::table('atom_plugin')
            ->where('name', 'ahgFavoritesPlugin')
            ->where('is_enabled', 1)
            ->exists();

        $favourited = false;

        if ($favPluginEnabled) {
            // Use the favourites system via its table
            $existing = DB::table('favourite')
                ->where('user_id', $userId)
                ->where('object_id', $listingId)
                ->where('object_type', 'marketplace_listing')
                ->first();

            if ($existing) {
                // Remove favourite
                DB::table('favourite')
                    ->where('user_id', $userId)
                    ->where('object_id', $listingId)
                    ->where('object_type', 'marketplace_listing')
                    ->delete();
                $favourited = false;
            } else {
                // Add favourite
                DB::table('favourite')->insert([
                    'user_id' => $userId,
                    'object_id' => $listingId,
                    'object_type' => 'marketplace_listing',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $favourited = true;
            }
        } else {
            // Simple tracking via marketplace_favourite table
            $existing = DB::table('marketplace_favourite')
                ->where('user_id', $userId)
                ->where('listing_id', $listingId)
                ->first();

            if ($existing) {
                DB::table('marketplace_favourite')
                    ->where('user_id', $userId)
                    ->where('listing_id', $listingId)
                    ->delete();
                $favourited = false;
            } else {
                DB::table('marketplace_favourite')->insert([
                    'user_id' => $userId,
                    'listing_id' => $listingId,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $favourited = true;
            }
        }

        // Update listing favourite_count
        if ($favourited) {
            $listingRepo->incrementFavouriteCount($listingId);
        } else {
            $listingRepo->decrementFavouriteCount($listingId);
        }

        // Get updated count
        $updatedListing = $listingRepo->getById($listingId);
        $count = (int) ($updatedListing->favourite_count ?? 0);

        echo json_encode([
            'success' => true,
            'favourited' => $favourited,
            'count' => $count,
        ]);

        return sfView::NONE;
    }
}
