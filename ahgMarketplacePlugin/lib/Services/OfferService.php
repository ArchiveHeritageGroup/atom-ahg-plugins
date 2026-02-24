<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/OfferRepository.php';
require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\OfferRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class OfferService
{
    private OfferRepository $offerRepo;
    private ListingRepository $listingRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->offerRepo = new OfferRepository();
        $this->listingRepo = new ListingRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    public function createOffer(int $listingId, int $buyerId, float $amount, ?string $message = null): array
    {
        $listing = $this->listingRepo->getById($listingId);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found'];
        }

        if ($listing->status !== 'active') {
            return ['success' => false, 'error' => 'Listing is not available'];
        }

        if ($listing->listing_type === 'auction') {
            return ['success' => false, 'error' => 'Cannot make offer on auction listing'];
        }

        // Check minimum offer
        if ($listing->minimum_offer && $amount < $listing->minimum_offer) {
            return ['success' => false, 'error' => 'Offer must be at least ' . number_format($listing->minimum_offer, 2)];
        }

        // Check for existing pending offer
        if ($this->offerRepo->hasPendingOffer($listingId, $buyerId)) {
            return ['success' => false, 'error' => 'You already have a pending offer on this listing'];
        }

        $expiryDays = (int) $this->settingsRepo->get('offer_expiry_days', 7);

        $id = $this->offerRepo->create([
            'listing_id' => $listingId,
            'buyer_id' => $buyerId,
            'status' => 'pending',
            'offer_amount' => $amount,
            'currency' => $listing->currency,
            'message' => $message,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryDays} days")),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Increment enquiry count
        $this->listingRepo->update($listingId, [
            'enquiry_count' => ($listing->enquiry_count ?? 0) + 1,
        ]);

        return ['success' => true, 'id' => $id];
    }

    public function acceptOffer(int $offerId): array
    {
        $offer = $this->offerRepo->getById($offerId);
        if (!$offer || !in_array($offer->status, ['pending', 'countered'])) {
            return ['success' => false, 'error' => 'Offer cannot be accepted'];
        }

        $this->offerRepo->update($offerId, [
            'status' => 'accepted',
            'responded_at' => date('Y-m-d H:i:s'),
        ]);

        // Reserve the listing
        $this->listingRepo->update($offer->listing_id, ['status' => 'reserved']);

        return ['success' => true, 'offer' => $offer];
    }

    public function rejectOffer(int $offerId, ?string $response = null): array
    {
        $offer = $this->offerRepo->getById($offerId);
        if (!$offer || !in_array($offer->status, ['pending', 'countered'])) {
            return ['success' => false, 'error' => 'Offer cannot be rejected'];
        }

        $this->offerRepo->update($offerId, [
            'status' => 'rejected',
            'seller_response' => $response,
            'responded_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    public function counterOffer(int $offerId, float $counterAmount, ?string $response = null): array
    {
        $offer = $this->offerRepo->getById($offerId);
        if (!$offer || $offer->status !== 'pending') {
            return ['success' => false, 'error' => 'Offer cannot be countered'];
        }

        $expiryDays = (int) $this->settingsRepo->get('offer_expiry_days', 7);

        $this->offerRepo->update($offerId, [
            'status' => 'countered',
            'counter_amount' => $counterAmount,
            'seller_response' => $response,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryDays} days")),
            'responded_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    public function withdrawOffer(int $offerId, int $buyerId): array
    {
        $offer = $this->offerRepo->getById($offerId);
        if (!$offer || $offer->buyer_id != $buyerId) {
            return ['success' => false, 'error' => 'Offer not found'];
        }

        if (!in_array($offer->status, ['pending', 'countered'])) {
            return ['success' => false, 'error' => 'Offer cannot be withdrawn'];
        }

        $this->offerRepo->update($offerId, [
            'status' => 'withdrawn',
            'responded_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }

    public function acceptCounter(int $offerId, int $buyerId): array
    {
        $offer = $this->offerRepo->getById($offerId);
        if (!$offer || $offer->buyer_id != $buyerId || $offer->status !== 'countered') {
            return ['success' => false, 'error' => 'Counter-offer cannot be accepted'];
        }

        $this->offerRepo->update($offerId, [
            'status' => 'accepted',
            'offer_amount' => $offer->counter_amount,
            'responded_at' => date('Y-m-d H:i:s'),
        ]);

        // Reserve the listing
        $this->listingRepo->update($offer->listing_id, ['status' => 'reserved']);

        return ['success' => true, 'price' => $offer->counter_amount];
    }

    public function getBuyerOffers(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->offerRepo->getBuyerOffers($userId, $limit, $offset);
    }

    public function getSellerOffers(int $sellerId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        return $this->offerRepo->getSellerOffers($sellerId, $status, $limit, $offset);
    }

    public function getOfferWithDetails(int $id): ?object
    {
        return $this->offerRepo->getOfferWithDetails($id);
    }

    public function processExpiredOffers(): int
    {
        $expired = $this->offerRepo->getExpiredOffers();
        $count = 0;

        foreach ($expired as $offer) {
            $this->offerRepo->update($offer->id, ['status' => 'expired']);
            $count++;
        }

        return $count;
    }
}
