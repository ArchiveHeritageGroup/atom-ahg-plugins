<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/TransactionRepository.php';
require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/AuctionRepository.php';
require_once dirname(__DIR__) . '/Repositories/OfferRepository.php';
require_once dirname(__DIR__) . '/Repositories/SellerRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\TransactionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\AuctionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\OfferRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository;

class MarketplaceNotificationService
{
    private TransactionRepository $txnRepo;
    private ListingRepository $listingRepo;
    private AuctionRepository $auctionRepo;
    private OfferRepository $offerRepo;
    private SellerRepository $sellerRepo;

    public function __construct()
    {
        $this->txnRepo = new TransactionRepository();
        $this->listingRepo = new ListingRepository();
        $this->auctionRepo = new AuctionRepository();
        $this->offerRepo = new OfferRepository();
        $this->sellerRepo = new SellerRepository();
    }

    // =========================================================================
    // Auction Notifications
    // =========================================================================

    public function notifyBidPlaced(int $auctionId, float $bidAmount): void
    {
        $auction = $this->auctionRepo->getById($auctionId);
        if (!$auction) {
            return;
        }

        $listing = $this->listingRepo->getById($auction->listing_id);
        $title = $listing ? $listing->title : 'Unknown listing';

        $this->log('bid_placed', [
            'auction_id' => $auctionId,
            'listing_id' => $auction->listing_id,
            'listing_title' => $title,
            'bid_amount' => $bidAmount,
            'seller_id' => $listing->seller_id ?? null,
        ]);
    }

    public function notifyAuctionEnding(int $auctionId): void
    {
        $auction = $this->auctionRepo->getById($auctionId);
        if (!$auction) {
            return;
        }

        $listing = $this->listingRepo->getById($auction->listing_id);
        $title = $listing ? $listing->title : 'Unknown listing';

        $this->log('auction_ending', [
            'auction_id' => $auctionId,
            'listing_id' => $auction->listing_id,
            'listing_title' => $title,
            'end_time' => $auction->end_time,
            'current_bid' => $auction->current_bid,
            'bid_count' => $auction->bid_count,
        ]);
    }

    // =========================================================================
    // Offer Notifications
    // =========================================================================

    public function notifyOfferReceived(int $offerId): void
    {
        $offer = $this->offerRepo->getById($offerId);
        if (!$offer) {
            return;
        }

        $listing = $this->listingRepo->getById($offer->listing_id);
        $title = $listing ? $listing->title : 'Unknown listing';

        $this->log('offer_received', [
            'offer_id' => $offerId,
            'listing_id' => $offer->listing_id,
            'listing_title' => $title,
            'offer_amount' => $offer->offer_amount,
            'buyer_id' => $offer->buyer_id,
            'seller_id' => $listing->seller_id ?? null,
        ]);
    }

    // =========================================================================
    // Transaction Notifications
    // =========================================================================

    public function notifySaleCompleted(int $txnId): void
    {
        $txn = $this->txnRepo->getById($txnId);
        if (!$txn) {
            return;
        }

        $listing = $this->listingRepo->getById($txn->listing_id);
        $title = $listing ? $listing->title : 'Unknown listing';
        $seller = $this->sellerRepo->getById($txn->seller_id);

        $this->log('sale_completed', [
            'transaction_id' => $txnId,
            'transaction_number' => $txn->transaction_number,
            'listing_id' => $txn->listing_id,
            'listing_title' => $title,
            'sale_price' => $txn->sale_price,
            'seller_id' => $txn->seller_id,
            'seller_name' => $seller ? $seller->display_name : null,
            'buyer_id' => $txn->buyer_id,
        ]);
    }

    // =========================================================================
    // Payout Notifications
    // =========================================================================

    public function notifyPayoutProcessed(int $payoutId): void
    {
        $payout = $this->txnRepo->getPayoutById($payoutId);
        if (!$payout) {
            return;
        }

        $seller = $this->sellerRepo->getById($payout->seller_id);

        $this->log('payout_processed', [
            'payout_id' => $payoutId,
            'payout_number' => $payout->payout_number ?? null,
            'seller_id' => $payout->seller_id,
            'seller_name' => $seller ? $seller->display_name : null,
            'amount' => $payout->amount,
            'currency' => $payout->currency,
            'status' => $payout->status,
        ]);
    }

    // =========================================================================
    // Listing Notifications
    // =========================================================================

    public function notifyListingApproved(int $listingId): void
    {
        $listing = $this->listingRepo->getById($listingId);
        if (!$listing) {
            return;
        }

        $seller = $this->sellerRepo->getById($listing->seller_id);

        $this->log('listing_approved', [
            'listing_id' => $listingId,
            'listing_number' => $listing->listing_number,
            'listing_title' => $listing->title,
            'seller_id' => $listing->seller_id,
            'seller_name' => $seller ? $seller->display_name : null,
        ]);
    }

    // =========================================================================
    // Logging Helper
    // =========================================================================

    /**
     * Log a marketplace notification event.
     *
     * This is a placeholder that logs to the PHP error log. Future integrations
     * can hook into email, push notifications, or a dedicated notification table.
     */
    private function log(string $eventType, array $data): void
    {
        // Placeholder — future: write to notification table or send emails
    }
}
