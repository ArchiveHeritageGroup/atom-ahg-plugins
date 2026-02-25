<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class VendorRepository
{
    protected string $table = 'registry_vendor';

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findBySlug(string $slug): ?object
    {
        return DB::table($this->table)->where('slug', $slug)->first();
    }

    public function findAll(array $params = []): array
    {
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($params['type'])) {
            $query->whereRaw("JSON_CONTAINS(vendor_type, ?)", [json_encode($params['type'])]);
        }
        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }
        if (!empty($params['specialization'])) {
            $query->whereRaw("JSON_CONTAINS(specializations, ?)", ['"' . $params['specialization'] . '"']);
        }
        if (isset($params['is_verified']) && $params['is_verified'] !== '') {
            $query->where('is_verified', (int) $params['is_verified']);
        }
        if (isset($params['is_featured']) && $params['is_featured'] !== '') {
            $query->where('is_featured', (int) $params['is_featured']);
        }

        $total = $query->count();

        $sort = $params['sort'] ?? 'name';
        $direction = $params['direction'] ?? 'asc';
        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy($sort, $direction)
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function search(string $term, array $params = []): array
    {
        // Try FULLTEXT search first
        $query = DB::table($this->table)
            ->where('is_active', 1)
            ->whereRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE)", [$term]);

        $total = $query->count();

        // If FULLTEXT returns no results, fall back to LIKE search
        if ($total === 0) {
            $likeTerm = '%' . $term . '%';
            $query = DB::table($this->table)
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('country', 'LIKE', $likeTerm);
                });

            $total = $query->count();

            $limit = $params['limit'] ?? 20;
            $page = $params['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            $items = $query->orderBy('name', 'asc')
                           ->limit($limit)
                           ->offset($offset)
                           ->get();

            return ['items' => $items, 'total' => $total, 'page' => (int) $page];
        }

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderByRaw("MATCH(name, description) AGAINST(? IN BOOLEAN MODE) DESC", [$term])
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function count(array $filters = []): int
    {
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($filters['type'])) {
            $query->whereRaw("JSON_CONTAINS(vendor_type, ?)", [json_encode($filters['type'])]);
        }
        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        if (isset($filters['is_verified']) && $filters['is_verified'] !== '') {
            $query->where('is_verified', (int) $filters['is_verified']);
        }

        return $query->count();
    }

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

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

    public function getFeatured(int $limit = 6): array
    {
        return DB::table($this->table)
            ->where('is_active', 1)
            ->where('is_featured', 1)
            ->orderBy('average_rating', 'desc')
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function updateRatingStats(int $id): void
    {
        $stats = DB::table('registry_review')
            ->where('entity_type', 'vendor')
            ->where('entity_id', $id)
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
}
