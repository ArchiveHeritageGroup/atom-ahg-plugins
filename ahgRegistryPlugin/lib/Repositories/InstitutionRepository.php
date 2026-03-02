<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class InstitutionRepository
{
    protected string $table = 'registry_institution';

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
        $query = DB::table($this->table);
        if (empty($params['include_inactive'])) {
            $query->where('is_active', 1);
        }

        if (!empty($params['type'])) {
            $query->where('institution_type', $params['type']);
        }
        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }
        if (!empty($params['sector'])) {
            $query->whereRaw("JSON_CONTAINS(glam_sectors, ?)", ['"' . $params['sector'] . '"']);
        }
        if (!empty($params['size'])) {
            $query->where('size', $params['size']);
        }
        if (!empty($params['governance'])) {
            $query->where('governance', $params['governance']);
        }
        if (isset($params['uses_atom']) && $params['uses_atom'] !== '') {
            $query->where('uses_atom', (int) $params['uses_atom']);
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

        $items = $query->orderBy('is_featured', 'desc')
                       ->orderBy($sort, $direction)
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
            ->whereRaw("MATCH(name, description, collection_summary) AGAINST(? IN BOOLEAN MODE)", [$term]);

        $total = $query->count();

        // If FULLTEXT returns no results, fall back to LIKE search
        if ($total === 0) {
            $likeTerm = '%' . $term . '%';
            $query = DB::table($this->table)
                ->where('is_active', 1)
                ->where(function ($q) use ($likeTerm) {
                    $q->where('name', 'LIKE', $likeTerm)
                      ->orWhere('description', 'LIKE', $likeTerm)
                      ->orWhere('collection_summary', 'LIKE', $likeTerm)
                      ->orWhere('city', 'LIKE', $likeTerm)
                      ->orWhere('country', 'LIKE', $likeTerm);
                });

            $total = $query->count();

            $limit = $params['limit'] ?? 20;
            $page = $params['page'] ?? 1;
            $offset = ($page - 1) * $limit;

            $items = $query->orderBy('is_featured', 'desc')
                           ->orderBy('name', 'asc')
                           ->limit($limit)
                           ->offset($offset)
                           ->get();

            return ['items' => $items, 'total' => $total, 'page' => (int) $page];
        }

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('is_featured', 'desc')
                       ->orderByRaw("MATCH(name, description, collection_summary) AGAINST(? IN BOOLEAN MODE) DESC", [$term])
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total, 'page' => (int) $page];
    }

    public function count(array $filters = []): int
    {
        $query = DB::table($this->table)->where('is_active', 1);

        if (!empty($filters['type'])) {
            $query->where('institution_type', $filters['type']);
        }
        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }
        if (isset($filters['is_verified'])) {
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
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function getByCountry(string $country, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('is_active', 1)
            ->where('country', $country);

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('name', 'asc')
                       ->limit($limit)
                       ->offset($offset)
                       ->get();

        return ['items' => $items, 'total' => $total];
    }

    public function getForMap(array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('is_active', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('id', 'name', 'slug', 'institution_type', 'city', 'country', 'latitude', 'longitude', 'logo_path', 'is_verified', 'is_featured');

        if (!empty($params['type'])) {
            $query->where('institution_type', $params['type']);
        }
        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }
        if (isset($params['uses_atom']) && $params['uses_atom'] !== '') {
            $query->where('uses_atom', (int) $params['uses_atom']);
        }

        return $query->get()->all();
    }
}
