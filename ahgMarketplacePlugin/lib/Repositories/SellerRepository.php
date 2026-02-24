<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class SellerRepository
{
    protected string $table = 'marketplace_seller';

    public function getById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function getBySlug(string $slug): ?object
    {
        return DB::table($this->table)->where('slug', $slug)->first();
    }

    public function getByUserId(int $userId): ?object
    {
        return DB::table($this->table)->where('created_by', $userId)->where('is_active', 1)->first();
    }

    public function getByActorId(int $actorId): ?object
    {
        return DB::table($this->table)->where('actor_id', $actorId)->where('is_active', 1)->first();
    }

    public function browse(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($filters['seller_type'])) {
            $query->where('seller_type', $filters['seller_type']);
        }
        if (!empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }
        if (!empty($filters['sector'])) {
            $query->whereRaw("JSON_CONTAINS(sectors, ?)", ['"' . $filters['sector'] . '"']);
        }
        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', 1);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('display_name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('bio', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        $total = $query->count();
        $items = $query->orderBy('is_featured', 'DESC')
                       ->orderBy('total_sales', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
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

    public function incrementSales(int $id, float $amount): void
    {
        DB::table($this->table)->where('id', $id)->increment('total_sales');
        DB::table($this->table)->where('id', $id)->increment('total_revenue', $amount);
    }

    public function updateRating(int $id): void
    {
        $stats = DB::table('marketplace_review')
            ->where('reviewed_seller_id', $id)
            ->where('is_visible', 1)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->first();

        if ($stats) {
            $this->update($id, [
                'average_rating' => round($stats->avg_rating ?? 0, 2),
                'rating_count' => $stats->cnt ?? 0,
            ]);
        }
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = DB::table($this->table)->where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getFollowerCount(int $sellerId): int
    {
        return DB::table('marketplace_follow')->where('seller_id', $sellerId)->count();
    }

    public function getAllForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->table);

        if (!empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }
        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('display_name', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        $total = $query->count();
        $items = $query->orderBy('created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }
}
