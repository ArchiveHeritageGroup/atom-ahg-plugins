<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/AuctionRepository.php';
require_once dirname(__DIR__) . '/Repositories/ListingRepository.php';
require_once dirname(__DIR__) . '/Repositories/SettingsRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\AuctionRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\ListingRepository;
use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SettingsRepository;

class AuctionService
{
    private AuctionRepository $auctionRepo;
    private ListingRepository $listingRepo;
    private SettingsRepository $settingsRepo;

    public function __construct()
    {
        $this->auctionRepo = new AuctionRepository();
        $this->listingRepo = new ListingRepository();
        $this->settingsRepo = new SettingsRepository();
    }

    public function createAuction(int $listingId, array $data): array
    {
        $listing = $this->listingRepo->getById($listingId);
        if (!$listing || $listing->listing_type !== 'auction') {
            return ['success' => false, 'error' => 'Listing is not an auction type'];
        }

        $existing = $this->auctionRepo->getByListingId($listingId);
        if ($existing) {
            return ['success' => false, 'error' => 'Auction already exists for this listing'];
        }

        $defaults = [
            'listing_id' => $listingId,
            'auction_type' => $data['auction_type'] ?? 'english',
            'status' => 'upcoming',
            'starting_bid' => $data['starting_bid'] ?? $listing->starting_bid ?? 1.00,
            'reserve_price' => $data['reserve_price'] ?? $listing->reserve_price,
            'bid_increment' => $data['bid_increment'] ?? 1.00,
            'buy_now_price' => $data['buy_now_price'] ?? $listing->buy_now_price,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'auto_extend_minutes' => (int) $this->settingsRepo->get('auction_auto_extend_minutes', 5),
            'max_extensions' => (int) $this->settingsRepo->get('auction_max_extensions', 10),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $id = $this->auctionRepo->create($defaults);

        return ['success' => true, 'id' => $id];
    }

    public function placeBid(int $auctionId, int $userId, float $amount, ?float $maxBid = null): array
    {
        $auction = $this->auctionRepo->getById($auctionId);
        if (!$auction) {
            return ['success' => false, 'error' => 'Auction not found'];
        }

        if ($auction->status !== 'active') {
            return ['success' => false, 'error' => 'Auction is not active'];
        }

        $now = date('Y-m-d H:i:s');
        if ($now > $auction->end_time) {
            return ['success' => false, 'error' => 'Auction has ended'];
        }

        // Check bid amount
        $minBid = $auction->current_bid
            ? $auction->current_bid + $auction->bid_increment
            : $auction->starting_bid;

        if ($amount < $minBid) {
            return ['success' => false, 'error' => "Minimum bid is " . number_format($minBid, 2)];
        }

        // Check seller cannot bid on own item
        $listing = $this->listingRepo->getById($auction->listing_id);
        if ($listing) {
            $sellerRepo = new \AtomAhgPlugins\ahgMarketplacePlugin\Repositories\SellerRepository();
            $seller = $sellerRepo->getById($listing->seller_id);
            if ($seller && $seller->created_by == $userId) {
                return ['success' => false, 'error' => 'You cannot bid on your own listing'];
            }
        }

        // Place the bid
        $this->auctionRepo->clearWinningFlags($auctionId);

        $bidId = $this->auctionRepo->placeBid([
            'auction_id' => $auctionId,
            'user_id' => $userId,
            'bid_amount' => $amount,
            'max_bid' => $maxBid,
            'is_auto_bid' => false,
            'is_winning' => true,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update auction current bid
        $this->auctionRepo->update($auctionId, [
            'current_bid' => $amount,
            'current_bidder_id' => $userId,
            'bid_count' => $auction->bid_count + 1,
        ]);

        // Anti-sniping: extend if bid in last N minutes
        $endTime = strtotime($auction->end_time);
        $timeLeft = $endTime - time();
        $extendMinutes = $auction->auto_extend_minutes * 60;

        if ($timeLeft < $extendMinutes && $auction->extension_count < $auction->max_extensions) {
            $newEndTime = date('Y-m-d H:i:s', time() + $extendMinutes);
            $this->auctionRepo->update($auctionId, [
                'end_time' => $newEndTime,
                'extension_count' => $auction->extension_count + 1,
            ]);
        }

        // Process proxy bids from other users
        $this->processProxyBids($auctionId, $userId, $amount);

        return ['success' => true, 'bid_id' => $bidId];
    }

    private function processProxyBids(int $auctionId, int $excludeUserId, float $currentBid): void
    {
        $auction = $this->auctionRepo->getById($auctionId);
        $proxyBids = $this->auctionRepo->getProxyBids($auctionId);

        foreach ($proxyBids as $proxy) {
            if ($proxy->user_id == $excludeUserId) {
                continue;
            }

            $autoBidAmount = $currentBid + $auction->bid_increment;
            if ($autoBidAmount <= $proxy->max_bid) {
                $this->auctionRepo->clearWinningFlags($auctionId);

                $this->auctionRepo->placeBid([
                    'auction_id' => $auctionId,
                    'user_id' => $proxy->user_id,
                    'bid_amount' => $autoBidAmount,
                    'max_bid' => $proxy->max_bid,
                    'is_auto_bid' => true,
                    'is_winning' => true,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $this->auctionRepo->update($auctionId, [
                    'current_bid' => $autoBidAmount,
                    'current_bidder_id' => $proxy->user_id,
                    'bid_count' => $auction->bid_count + 1,
                ]);

                break; // Only one auto-bid per round
            }
        }
    }

    public function buyNow(int $auctionId, int $userId): array
    {
        $auction = $this->auctionRepo->getById($auctionId);
        if (!$auction || $auction->status !== 'active') {
            return ['success' => false, 'error' => 'Auction is not active'];
        }

        if (!$auction->buy_now_price) {
            return ['success' => false, 'error' => 'Buy Now is not available for this auction'];
        }

        // If there are existing bids, buy now may be disabled (depending on policy)
        // For now, allow it as long as auction is active

        $this->auctionRepo->update($auctionId, [
            'status' => 'ended',
            'winner_id' => $userId,
            'winning_bid' => $auction->buy_now_price,
        ]);

        return ['success' => true, 'price' => $auction->buy_now_price];
    }

    public function endAuction(int $auctionId): array
    {
        $auction = $this->auctionRepo->getById($auctionId);
        if (!$auction) {
            return ['success' => false, 'error' => 'Auction not found'];
        }

        $highestBid = $this->auctionRepo->getHighestBid($auctionId);

        $updateData = ['status' => 'ended'];

        if ($highestBid) {
            $reserveMet = !$auction->reserve_price || $highestBid->bid_amount >= $auction->reserve_price;

            if ($reserveMet) {
                $updateData['winner_id'] = $highestBid->user_id;
                $updateData['winning_bid'] = $highestBid->bid_amount;
            }
        }

        $this->auctionRepo->update($auctionId, $updateData);

        return [
            'success' => true,
            'has_winner' => isset($updateData['winner_id']),
            'winner_id' => $updateData['winner_id'] ?? null,
            'winning_bid' => $updateData['winning_bid'] ?? null,
        ];
    }

    public function getAuctionStatus(int $auctionId): ?array
    {
        $auction = $this->auctionRepo->getById($auctionId);
        if (!$auction) {
            return null;
        }

        return [
            'id' => $auction->id,
            'status' => $auction->status,
            'current_bid' => $auction->current_bid,
            'bid_count' => $auction->bid_count,
            'end_time' => $auction->end_time,
            'reserve_met' => $auction->reserve_price ? ($auction->current_bid >= $auction->reserve_price) : true,
            'buy_now_price' => $auction->buy_now_price,
            'time_remaining' => max(0, strtotime($auction->end_time) - time()),
        ];
    }

    public function getActiveAuctions(int $limit = 24, int $offset = 0): array
    {
        return $this->auctionRepo->getActiveAuctions($limit, $offset);
    }

    public function getEndingSoon(int $minutes = 60): array
    {
        return $this->auctionRepo->getEndingSoon($minutes);
    }

    public function getBidHistory(int $auctionId, int $limit = 50): array
    {
        return $this->auctionRepo->getBids($auctionId, $limit);
    }

    public function getUserBids(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->auctionRepo->getUserBids($userId, $limit, $offset);
    }

    /**
     * Process auction lifecycle: start upcoming, end expired.
     */
    public function processAuctionLifecycle(): array
    {
        $started = 0;
        $ended = 0;

        // Start upcoming auctions
        foreach ($this->auctionRepo->getAuctionsToStart() as $auction) {
            $this->auctionRepo->update($auction->id, ['status' => 'active']);
            $this->listingRepo->update($auction->listing_id, ['status' => 'active']);
            $started++;
        }

        // End expired auctions
        foreach ($this->auctionRepo->getAuctionsToEnd() as $auction) {
            $this->endAuction($auction->id);
            $ended++;
        }

        return ['started' => $started, 'ended' => $ended];
    }
}
