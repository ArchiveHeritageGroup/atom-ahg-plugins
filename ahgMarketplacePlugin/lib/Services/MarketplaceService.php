<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class MarketplaceService
{
    private ListingRepository $listingRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->listingRepo = new ListingRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    // =========================================================================
    // Listing CRUD
    // =========================================================================

    public function createListing(int $sellerId, array $data): array
    {
        $data['seller_id'] = $sellerId;
        $data['listing_number'] = $this->listingRepo->generateListingNumber();
        $data['slug'] = $this->generateSlug($data['title']);
        $data['status'] = 'draft';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = $this->listingRepo->create($data);

        return ['success' => true, 'id' => $id, 'listing_number' => $data['listing_number']];
    }

    public function updateListing(int $id, array $data): array
    {
        $listing = $this->listingRepo->getById($id);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found'];
        }

        if (isset($data['title']) && $data['title'] !== $listing->title) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $this->listingRepo->update($id, $data);

        return ['success' => true];
    }

    public function publishListing(int $id): array
    {
        $listing = $this->listingRepo->getById($id);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found'];
        }

        if (!in_array($listing->status, ['draft', 'expired'])) {
            return ['success' => false, 'error' => 'Listing cannot be published from status: ' . $listing->status];
        }

        $moderationEnabled = $this->settingsRepo->get('listing_moderation_enabled', true);
        $durationDays = (int) $this->settingsRepo->get('listing_duration_days', 90);
        $now = date('Y-m-d H:i:s');

        $updateData = [
            'status' => $moderationEnabled ? 'pending_review' : 'active',
            'listed_at' => $moderationEnabled ? null : $now,
            'expires_at' => $moderationEnabled ? null : date('Y-m-d H:i:s', strtotime("+{$durationDays} days")),
        ];

        $this->listingRepo->update($id, $updateData);

        return ['success' => true, 'status' => $updateData['status']];
    }

    public function approveListing(int $id): array
    {
        $listing = $this->listingRepo->getById($id);
        if (!$listing || $listing->status !== 'pending_review') {
            return ['success' => false, 'error' => 'Listing is not pending review'];
        }

        $durationDays = (int) $this->settingsRepo->get('listing_duration_days', 90);
        $now = date('Y-m-d H:i:s');

        $this->listingRepo->update($id, [
            'status' => 'active',
            'listed_at' => $now,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$durationDays} days")),
        ]);

        return ['success' => true];
    }

    public function rejectListing(int $id): array
    {
        $listing = $this->listingRepo->getById($id);
        if (!$listing || $listing->status !== 'pending_review') {
            return ['success' => false, 'error' => 'Listing is not pending review'];
        }

        $this->listingRepo->update($id, ['status' => 'draft']);

        return ['success' => true];
    }

    public function withdrawListing(int $id): array
    {
        $listing = $this->listingRepo->getById($id);
        if (!$listing || !in_array($listing->status, ['active', 'pending_review'])) {
            return ['success' => false, 'error' => 'Listing cannot be withdrawn'];
        }

        $this->listingRepo->update($id, ['status' => 'withdrawn']);

        return ['success' => true];
    }

    public function markSold(int $id): void
    {
        $this->listingRepo->update($id, [
            'status' => 'sold',
            'sold_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // Browse & Search
    // =========================================================================

    public function browse(array $filters = [], int $limit = 24, int $offset = 0, string $sort = 'newest'): array
    {
        return $this->listingRepo->browse($filters, $limit, $offset, $sort);
    }

    public function getListing(string $slug): ?object
    {
        $listing = $this->listingRepo->getBySlug($slug);
        if ($listing) {
            $this->listingRepo->incrementViewCount($listing->id);
        }

        return $listing;
    }

    public function getListingById(int $id): ?object
    {
        return $this->listingRepo->getById($id);
    }

    public function getListingImages(int $listingId): array
    {
        return $this->listingRepo->getImages($listingId);
    }

    public function getFeaturedListings(int $limit = 12): array
    {
        return $this->listingRepo->getFeatured($limit);
    }

    public function getFacetCounts(array $filters = []): array
    {
        return $this->listingRepo->getFacetCounts($filters);
    }

    // =========================================================================
    // Images
    // =========================================================================

    public function addListingImage(int $listingId, array $data): int
    {
        $data['listing_id'] = $listingId;
        $data['created_at'] = date('Y-m-d H:i:s');

        $imageCount = $this->listingRepo->getImageCount($listingId);
        if ($imageCount === 0) {
            $data['is_primary'] = 1;
        }

        return $this->listingRepo->addImage($data);
    }

    public function setPrimaryImage(int $listingId, int $imageId): void
    {
        $this->listingRepo->setImagePrimary($listingId, $imageId);

        // Update featured_image_path on listing
        $image = \Illuminate\Database\Capsule\Manager::table('marketplace_listing_image')
            ->where('id', $imageId)->first();
        if ($image) {
            $this->listingRepo->update($listingId, ['featured_image_path' => $image->file_path]);
        }
    }

    public function deleteListingImage(int $imageId): bool
    {
        return $this->listingRepo->deleteImage($imageId);
    }

    // =========================================================================
    // Expiry
    // =========================================================================

    public function processExpiredListings(): int
    {
        $expired = $this->listingRepo->getExpiredListings();
        $count = 0;

        foreach ($expired as $listing) {
            $this->listingRepo->update($listing->id, ['status' => 'expired']);
            $count++;
        }

        return $count;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while ($this->listingRepo->getBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getSetting(string $key, $default = null)
    {
        return $this->settingsRepo->get($key, $default);
    }
}
