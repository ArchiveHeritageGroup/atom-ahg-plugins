<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class ListingRepository
{
    protected string $table = 'marketplace_listing';
    protected string $imageTable = 'marketplace_listing_image';

    // =========================================================================
    // Listing CRUD
    // =========================================================================

    public function getById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function getBySlug(string $slug): ?object
    {
        return DB::table($this->table)->where('slug', $slug)->first();
    }

    public function getByListingNumber(string $number): ?object
    {
        return DB::table($this->table)->where('listing_number', $number)->first();
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

    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    // =========================================================================
    // Browse / Search
    // =========================================================================

    public function browse(array $filters = [], int $limit = 24, int $offset = 0, string $sort = 'newest'): array
    {
        $query = DB::table($this->table . ' as l')
            ->leftJoin('marketplace_seller as s', 'l.seller_id', '=', 's.id')
            ->select('l.*', 's.display_name as seller_name', 's.slug as seller_slug', 's.average_rating as seller_rating', 's.verification_status as seller_verified');

        // Only active listings for public browse
        if (!isset($filters['include_all_statuses'])) {
            $query->where('l.status', 'active');
        }

        if (!empty($filters['sector'])) {
            $query->where('l.sector', $filters['sector']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('l.category_id', $filters['category_id']);
        }
        if (!empty($filters['listing_type'])) {
            $query->where('l.listing_type', $filters['listing_type']);
        }
        if (!empty($filters['seller_id'])) {
            $query->where('l.seller_id', $filters['seller_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('l.status', $filters['status']);
        }
        if (isset($filters['price_min'])) {
            $query->where('l.price', '>=', $filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $query->where('l.price', '<=', $filters['price_max']);
        }
        if (!empty($filters['condition_rating'])) {
            $query->where('l.condition_rating', $filters['condition_rating']);
        }
        if (!empty($filters['medium'])) {
            $query->where('l.medium', 'LIKE', '%' . $filters['medium'] . '%');
        }
        if (!empty($filters['country'])) {
            $query->where('l.shipping_from_country', $filters['country']);
        }
        if (isset($filters['is_digital'])) {
            $query->where('l.is_digital', $filters['is_digital']);
        }
        if (!empty($filters['search'])) {
            $query->whereRaw("MATCH(l.title, l.description, l.artist_name, l.medium) AGAINST(? IN BOOLEAN MODE)", [$filters['search']]);
        }

        $total = $query->count();

        switch ($sort) {
            case 'price_asc':
                $query->orderBy('l.price', 'ASC');
                break;
            case 'price_desc':
                $query->orderBy('l.price', 'DESC');
                break;
            case 'popular':
                $query->orderBy('l.view_count', 'DESC');
                break;
            case 'ending_soon':
                $query->orderBy('l.expires_at', 'ASC');
                break;
            case 'oldest':
                $query->orderBy('l.listed_at', 'ASC');
                break;
            case 'newest':
            default:
                $query->orderBy('l.listed_at', 'DESC');
                break;
        }

        $items = $query->limit($limit)->offset($offset)->get()->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getSellerListings(int $sellerId, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->table)->where('seller_id', $sellerId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $total = $query->count();
        $items = $query->orderBy('created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getFeatured(int $limit = 12): array
    {
        return DB::table($this->table . ' as l')
            ->leftJoin('marketplace_seller as s', 'l.seller_id', '=', 's.id')
            ->select('l.*', 's.display_name as seller_name', 's.slug as seller_slug')
            ->where('l.status', 'active')
            ->where(function ($q) {
                $q->where('s.is_featured', 1)
                  ->orWhere('l.view_count', '>', 50);
            })
            ->orderByRaw('RAND()')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function incrementViewCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->increment('view_count');
    }

    public function incrementFavouriteCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->increment('favourite_count');
    }

    public function decrementFavouriteCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->where('favourite_count', '>', 0)->decrement('favourite_count');
    }

    public function generateListingNumber(): string
    {
        $date = date('Ymd');
        $last = DB::table($this->table)
            ->where('listing_number', 'LIKE', 'MKT-' . $date . '-%')
            ->orderBy('id', 'DESC')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->listing_number);
            $seq = (int) end($parts) + 1;
        }

        return sprintf('MKT-%s-%04d', $date, $seq);
    }

    public function getExpiredListings(): array
    {
        return DB::table($this->table)
            ->where('status', 'active')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->get()
            ->all();
    }

    public function getFacetCounts(array $baseFilters = []): array
    {
        $query = DB::table($this->table)->where('status', 'active');

        if (!empty($baseFilters['sector'])) {
            $query->where('sector', $baseFilters['sector']);
        }

        $sectors = (clone $query)->selectRaw("sector, COUNT(*) as cnt")->groupBy('sector')->pluck('cnt', 'sector')->all();
        $types = (clone $query)->selectRaw("listing_type, COUNT(*) as cnt")->groupBy('listing_type')->pluck('cnt', 'listing_type')->all();
        $conditions = (clone $query)->whereNotNull('condition_rating')->selectRaw("condition_rating, COUNT(*) as cnt")->groupBy('condition_rating')->pluck('cnt', 'condition_rating')->all();

        return [
            'sectors' => $sectors,
            'listing_types' => $types,
            'conditions' => $conditions,
        ];
    }

    // =========================================================================
    // Listing Images
    // =========================================================================

    public function getImages(int $listingId): array
    {
        return DB::table($this->imageTable)
            ->where('listing_id', $listingId)
            ->orderBy('sort_order')
            ->get()
            ->all();
    }

    public function getPrimaryImage(int $listingId): ?object
    {
        return DB::table($this->imageTable)
            ->where('listing_id', $listingId)
            ->where('is_primary', 1)
            ->first()
            ?? DB::table($this->imageTable)
                ->where('listing_id', $listingId)
                ->orderBy('sort_order')
                ->first();
    }

    public function addImage(array $data): int
    {
        return DB::table($this->imageTable)->insertGetId($data);
    }

    public function updateImage(int $imageId, array $data): bool
    {
        return DB::table($this->imageTable)->where('id', $imageId)->update($data) >= 0;
    }

    public function deleteImage(int $imageId): bool
    {
        return DB::table($this->imageTable)->where('id', $imageId)->delete() > 0;
    }

    public function setImagePrimary(int $listingId, int $imageId): void
    {
        DB::table($this->imageTable)->where('listing_id', $listingId)->update(['is_primary' => 0]);
        DB::table($this->imageTable)->where('id', $imageId)->update(['is_primary' => 1]);
    }

    public function getImageCount(int $listingId): int
    {
        return DB::table($this->imageTable)->where('listing_id', $listingId)->count();
    }
}
