<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class AuctionRepository
{
    protected string $auctionTable = 'marketplace_auction';
    protected string $bidTable = 'marketplace_bid';

    // =========================================================================
    // Auction CRUD
    // =========================================================================

    public function getById(int $id): ?object
    {
        return DB::table($this->auctionTable)->where('id', $id)->first();
    }

    public function getByListingId(int $listingId): ?object
    {
        return DB::table($this->auctionTable)->where('listing_id', $listingId)->first();
    }

    public function create(array $data): int
    {
        return DB::table($this->auctionTable)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->auctionTable)->where('id', $id)->update($data) >= 0;
    }

    // =========================================================================
    // Auction Queries
    // =========================================================================

    public function getActiveAuctions(int $limit = 24, int $offset = 0): array
    {
        $now = date('Y-m-d H:i:s');

        $query = DB::table($this->auctionTable . ' as a')
            ->join('marketplace_listing as l', 'a.listing_id', '=', 'l.id')
            ->leftJoin('marketplace_seller as s', 'l.seller_id', '=', 's.id')
            ->select('a.*', 'l.title', 'l.slug', 'l.featured_image_path', 'l.sector', 'l.artist_name', 's.display_name as seller_name', 's.slug as seller_slug')
            ->where('a.status', 'active')
            ->where('l.status', 'active')
            ->where('a.end_time', '>', $now);

        $total = $query->count();
        $items = $query->orderBy('a.end_time', 'ASC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getEndingSoon(int $minutes = 60, int $limit = 10): array
    {
        $now = date('Y-m-d H:i:s');
        $cutoff = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));

        return DB::table($this->auctionTable . ' as a')
            ->join('marketplace_listing as l', 'a.listing_id', '=', 'l.id')
            ->select('a.*', 'l.title', 'l.slug', 'l.featured_image_path')
            ->where('a.status', 'active')
            ->where('a.end_time', '>', $now)
            ->where('a.end_time', '<=', $cutoff)
            ->orderBy('a.end_time', 'ASC')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getAuctionsToStart(): array
    {
        $now = date('Y-m-d H:i:s');

        return DB::table($this->auctionTable)
            ->where('status', 'upcoming')
            ->where('start_time', '<=', $now)
            ->get()
            ->all();
    }

    public function getAuctionsToEnd(): array
    {
        $now = date('Y-m-d H:i:s');

        return DB::table($this->auctionTable)
            ->where('status', 'active')
            ->where('end_time', '<=', $now)
            ->get()
            ->all();
    }

    // =========================================================================
    // Bids
    // =========================================================================

    public function placeBid(array $data): int
    {
        return DB::table($this->bidTable)->insertGetId($data);
    }

    public function getBids(int $auctionId, int $limit = 50): array
    {
        return DB::table($this->bidTable)
            ->where('auction_id', $auctionId)
            ->orderBy('bid_amount', 'DESC')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getHighestBid(int $auctionId): ?object
    {
        return DB::table($this->bidTable)
            ->where('auction_id', $auctionId)
            ->orderBy('bid_amount', 'DESC')
            ->first();
    }

    public function getUserBids(int $userId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->bidTable . ' as b')
            ->join($this->auctionTable . ' as a', 'b.auction_id', '=', 'a.id')
            ->join('marketplace_listing as l', 'a.listing_id', '=', 'l.id')
            ->select('b.*', 'a.status as auction_status', 'a.end_time', 'a.current_bid', 'l.title', 'l.slug', 'l.featured_image_path')
            ->where('b.user_id', $userId);

        $total = $query->count();
        $items = $query->orderBy('b.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getUserHighestBidForAuction(int $auctionId, int $userId): ?object
    {
        return DB::table($this->bidTable)
            ->where('auction_id', $auctionId)
            ->where('user_id', $userId)
            ->orderBy('bid_amount', 'DESC')
            ->first();
    }

    public function clearWinningFlags(int $auctionId): void
    {
        DB::table($this->bidTable)
            ->where('auction_id', $auctionId)
            ->update(['is_winning' => 0]);
    }

    public function getProxyBids(int $auctionId): array
    {
        return DB::table($this->bidTable)
            ->where('auction_id', $auctionId)
            ->whereNotNull('max_bid')
            ->where('max_bid', '>', DB::raw('bid_amount'))
            ->orderBy('max_bid', 'DESC')
            ->get()
            ->all();
    }
}
