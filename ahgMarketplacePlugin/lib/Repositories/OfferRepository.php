<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class OfferRepository
{
    protected string $table = 'marketplace_offer';

    public function getById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function create(array $data): int
    {
        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->table)->where('id', $id)->update($data) >= 0;
    }

    public function getByListing(int $listingId, ?string $status = null): array
    {
        $query = DB::table($this->table)->where('listing_id', $listingId);
        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'DESC')->get()->all();
    }

    public function getBuyerOffers(int $userId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->table . ' as o')
            ->join('marketplace_listing as l', 'o.listing_id', '=', 'l.id')
            ->select('o.*', 'l.title', 'l.slug', 'l.featured_image_path', 'l.price as listing_price')
            ->where('o.buyer_id', $userId);

        $total = $query->count();
        $items = $query->orderBy('o.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getSellerOffers(int $sellerId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->table . ' as o')
            ->join('marketplace_listing as l', 'o.listing_id', '=', 'l.id')
            ->select('o.*', 'l.title', 'l.slug', 'l.featured_image_path', 'l.price as listing_price', 'l.seller_id')
            ->where('l.seller_id', $sellerId);

        if ($status) {
            $query->where('o.status', $status);
        }

        $total = $query->count();
        $items = $query->orderBy('o.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function hasPendingOffer(int $listingId, int $userId): bool
    {
        return DB::table($this->table)
            ->where('listing_id', $listingId)
            ->where('buyer_id', $userId)
            ->whereIn('status', ['pending', 'countered'])
            ->exists();
    }

    public function getExpiredOffers(): array
    {
        return DB::table($this->table)
            ->whereIn('status', ['pending', 'countered'])
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->get()
            ->all();
    }

    public function getOfferWithDetails(int $id): ?object
    {
        return DB::table($this->table . ' as o')
            ->join('marketplace_listing as l', 'o.listing_id', '=', 'l.id')
            ->leftJoin('marketplace_seller as s', 'l.seller_id', '=', 's.id')
            ->select('o.*', 'l.title', 'l.slug', 'l.price as listing_price', 'l.seller_id', 's.display_name as seller_name')
            ->where('o.id', $id)
            ->first();
    }
}
