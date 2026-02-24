<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class CollectionRepository
{
    protected string $collectionTable = 'marketplace_collection';
    protected string $itemTable = 'marketplace_collection_item';

    public function getById(int $id): ?object
    {
        return DB::table($this->collectionTable)->where('id', $id)->first();
    }

    public function getBySlug(string $slug): ?object
    {
        return DB::table($this->collectionTable)->where('slug', $slug)->first();
    }

    public function create(array $data): int
    {
        return DB::table($this->collectionTable)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->collectionTable)->where('id', $id)->update($data) >= 0;
    }

    public function delete(int $id): bool
    {
        return DB::table($this->collectionTable)->where('id', $id)->delete() > 0;
    }

    public function getPublicCollections(int $limit = 20, int $offset = 0): array
    {
        $query = DB::table($this->collectionTable)
            ->where('is_public', 1);

        $total = $query->count();
        $items = $query->orderBy('is_featured', 'DESC')
                       ->orderBy('sort_order', 'ASC')
                       ->orderBy('created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getSellerCollections(int $sellerId): array
    {
        return DB::table($this->collectionTable)
            ->where('seller_id', $sellerId)
            ->orderBy('sort_order', 'ASC')
            ->get()
            ->all();
    }

    public function getFeatured(int $limit = 6): array
    {
        return DB::table($this->collectionTable)
            ->where('is_public', 1)
            ->where('is_featured', 1)
            ->orderBy('sort_order', 'ASC')
            ->limit($limit)
            ->get()
            ->all();
    }

    // Collection Items
    public function addItem(int $collectionId, int $listingId, int $sortOrder = 0, ?string $note = null): int
    {
        return DB::table($this->itemTable)->insertGetId([
            'collection_id' => $collectionId,
            'listing_id' => $listingId,
            'sort_order' => $sortOrder,
            'curator_note' => $note,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function removeItem(int $collectionId, int $listingId): bool
    {
        return DB::table($this->itemTable)
            ->where('collection_id', $collectionId)
            ->where('listing_id', $listingId)
            ->delete() > 0;
    }

    public function getItems(int $collectionId, int $limit = 50): array
    {
        return DB::table($this->itemTable . ' as ci')
            ->join('marketplace_listing as l', 'ci.listing_id', '=', 'l.id')
            ->leftJoin('marketplace_seller as s', 'l.seller_id', '=', 's.id')
            ->select('ci.*', 'l.title', 'l.slug', 'l.price', 'l.currency', 'l.featured_image_path', 'l.status', 'l.listing_type', 'l.artist_name', 's.display_name as seller_name', 's.slug as seller_slug')
            ->where('ci.collection_id', $collectionId)
            ->where('l.status', 'active')
            ->orderBy('ci.sort_order', 'ASC')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getItemCount(int $collectionId): int
    {
        return DB::table($this->itemTable)->where('collection_id', $collectionId)->count();
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table($this->collectionTable)->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
