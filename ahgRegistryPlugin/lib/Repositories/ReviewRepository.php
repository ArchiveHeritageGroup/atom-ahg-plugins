<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class ReviewRepository
{
    protected string $table = 'registry_review';

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findByEntity(string $type, int $id, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('is_visible', 1);

        $total = $query->count();

        $sort = $params['sort'] ?? 'created_at';
        $direction = $params['direction'] ?? 'desc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        return DB::table($this->table)->where('id', $id)->update($data) >= 0;
    }

    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function getAverageRating(string $type, int $id): array
    {
        $query = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('is_visible', 1);

        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = (clone $query)->where('rating', $i)->count();
        }

        return [
            'average' => round((clone $query)->avg('rating') ?? 0, 2),
            'count' => (clone $query)->count(),
            'distribution' => $distribution,
        ];
    }

    public function toggleVisibility(int $id): bool
    {
        $review = $this->findById($id);
        if (!$review) {
            return false;
        }

        $newVisibility = $review->is_visible ? 0 : 1;

        return DB::table($this->table)->where('id', $id)->update(['is_visible' => $newVisibility]) >= 0;
    }
}
