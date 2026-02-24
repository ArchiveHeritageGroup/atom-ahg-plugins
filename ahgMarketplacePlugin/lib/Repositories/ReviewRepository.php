<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class ReviewRepository
{
    protected string $table = 'marketplace_review';

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

    public function getSellerReviews(int $sellerId, int $limit = 20, int $offset = 0): array
    {
        $query = DB::table($this->table)
            ->where('reviewed_seller_id', $sellerId)
            ->where('review_type', 'buyer_to_seller')
            ->where('is_visible', 1);

        $total = $query->count();
        $items = $query->orderBy('created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function hasReviewed(int $transactionId, int $userId): bool
    {
        return DB::table($this->table)
            ->where('transaction_id', $transactionId)
            ->where('reviewer_id', $userId)
            ->exists();
    }

    public function getSellerRatingStats(int $sellerId): array
    {
        $reviews = DB::table($this->table)
            ->where('reviewed_seller_id', $sellerId)
            ->where('is_visible', 1)
            ->where('review_type', 'buyer_to_seller');

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = (clone $reviews)->where('rating', $i)->count();
        }

        return [
            'average' => (clone $reviews)->avg('rating') ?? 0,
            'count' => (clone $reviews)->count(),
            'distribution' => $distribution,
        ];
    }

    public function getAllForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->table . ' as r')
            ->leftJoin('marketplace_seller as s', 'r.reviewed_seller_id', '=', 's.id')
            ->select('r.*', 's.display_name as seller_name');

        if (!empty($filters['flagged'])) {
            $query->where('r.flagged', 1);
        }
        if (isset($filters['is_visible'])) {
            $query->where('r.is_visible', $filters['is_visible']);
        }

        $total = $query->count();
        $items = $query->orderBy('r.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }
}
