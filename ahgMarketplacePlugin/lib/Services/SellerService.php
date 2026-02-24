<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/SellerRepository.php';
require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/TransactionRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class SellerService
{
    private SellerRepository $sellerRepo;
    private ListingRepository $listingRepo;
    private TransactionRepository $txnRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->sellerRepo = new SellerRepository();
        $this->listingRepo = new ListingRepository();
        $this->txnRepo = new TransactionRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    public function register(int $userId, array $data): array
    {
        $registrationOpen = $this->settingsRepo->get('seller_registration_open', true);
        if (!$registrationOpen) {
            return ['success' => false, 'error' => 'Seller registration is currently closed'];
        }

        // Check if user already has a seller profile
        $existing = $this->sellerRepo->getByUserId($userId);
        if ($existing) {
            return ['success' => false, 'error' => 'You already have a seller profile', 'seller' => $existing];
        }

        $slug = $this->generateSellerSlug($data['display_name']);
        $defaultCommission = (float) $this->settingsRepo->get('default_commission_rate', 10);

        $id = $this->sellerRepo->create([
            'seller_type' => $data['seller_type'] ?? 'artist',
            'actor_id' => $data['actor_id'] ?? null,
            'gallery_artist_id' => $data['gallery_artist_id'] ?? null,
            'repository_id' => $data['repository_id'] ?? null,
            'heritage_contributor_id' => $data['heritage_contributor_id'] ?? null,
            'display_name' => $data['display_name'],
            'slug' => $slug,
            'bio' => $data['bio'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
            'website' => $data['website'] ?? null,
            'instagram' => $data['instagram'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'commission_rate' => $defaultCommission,
            'payout_method' => $data['payout_method'] ?? 'bank_transfer',
            'payout_details' => isset($data['payout_details']) ? json_encode($data['payout_details']) : null,
            'payout_currency' => $data['payout_currency'] ?? 'ZAR',
            'sectors' => isset($data['sectors']) ? json_encode($data['sectors']) : null,
            'verification_status' => 'unverified',
            'trust_level' => 'new',
            'is_active' => 1,
            'terms_accepted_at' => date('Y-m-d H:i:s'),
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'id' => $id, 'slug' => $slug];
    }

    public function updateProfile(int $sellerId, array $data): array
    {
        $seller = $this->sellerRepo->getById($sellerId);
        if (!$seller) {
            return ['success' => false, 'error' => 'Seller not found'];
        }

        $updateData = [];
        $allowedFields = [
            'display_name', 'bio', 'country', 'city', 'website', 'instagram',
            'email', 'phone', 'payout_method', 'payout_currency', 'avatar_path',
            'banner_path', 'seller_type',
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['payout_details'])) {
            $updateData['payout_details'] = json_encode($data['payout_details']);
        }
        if (isset($data['sectors'])) {
            $updateData['sectors'] = json_encode($data['sectors']);
        }

        if (isset($data['display_name']) && $data['display_name'] !== $seller->display_name) {
            $updateData['slug'] = $this->generateSellerSlug($data['display_name']);
        }

        $this->sellerRepo->update($sellerId, $updateData);

        return ['success' => true];
    }

    public function verifySeller(int $sellerId): array
    {
        $this->sellerRepo->update($sellerId, [
            'verification_status' => 'verified',
            'trust_level' => 'active',
        ]);

        return ['success' => true];
    }

    public function suspendSeller(int $sellerId): array
    {
        $this->sellerRepo->update($sellerId, [
            'verification_status' => 'suspended',
            'is_active' => 0,
        ]);

        return ['success' => true];
    }

    public function getSellerBySlug(string $slug): ?object
    {
        return $this->sellerRepo->getBySlug($slug);
    }

    public function getSellerByUserId(int $userId): ?object
    {
        return $this->sellerRepo->getByUserId($userId);
    }

    public function getSellerById(int $id): ?object
    {
        return $this->sellerRepo->getById($id);
    }

    public function getDashboardStats(int $sellerId): array
    {
        $listings = $this->listingRepo->getSellerListings($sellerId);
        $revenue = $this->txnRepo->getRevenueStats($sellerId);
        $pendingPayout = $this->txnRepo->getSellerPendingPayoutAmount($sellerId);
        $followers = $this->sellerRepo->getFollowerCount($sellerId);

        $activeListings = 0;
        $draftListings = 0;
        foreach ($listings['items'] as $l) {
            if ($l->status === 'active') {
                $activeListings++;
            }
            if ($l->status === 'draft') {
                $draftListings++;
            }
        }

        return [
            'total_listings' => $listings['total'],
            'active_listings' => $activeListings,
            'draft_listings' => $draftListings,
            'total_sales' => $revenue['total_sales'],
            'total_revenue' => $revenue['total_seller_amount'],
            'pending_payout' => $pendingPayout,
            'followers' => $followers,
        ];
    }

    public function browseSellers(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        return $this->sellerRepo->browse($filters, $limit, $offset);
    }

    private function generateSellerSlug(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while ($this->sellerRepo->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
