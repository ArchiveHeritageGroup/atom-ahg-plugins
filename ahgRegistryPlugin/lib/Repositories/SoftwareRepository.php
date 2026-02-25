<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class SoftwareRepository
{
    protected string $table = 'registry_software';
    protected string $releaseTable = 'registry_software_release';

    // -------------------------------------------------------
    // Software
    // -------------------------------------------------------

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

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (!empty($params['vendor'])) {
            $query->where('vendor_id', $params['vendor']);
        }
        if (!empty($params['license'])) {
            $query->where('license', $params['license']);
        }
        if (!empty($params['pricing'])) {
            $query->where('pricing_model', $params['pricing']);
        }
        if (!empty($params['sector'])) {
            $query->whereRaw("JSON_CONTAINS(glam_sectors, ?)", ['"' . $params['sector'] . '"']);
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
                      ->orWhere('category', 'LIKE', $likeTerm);
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

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }
        if (!empty($filters['vendor'])) {
            $query->where('vendor_id', $filters['vendor']);
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
            ->where('entity_type', 'software')
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

    // -------------------------------------------------------
    // Releases
    // -------------------------------------------------------

    public function findReleaseById(int $id): ?object
    {
        return DB::table($this->releaseTable)->where('id', $id)->first();
    }

    public function findReleasesBySoftware(int $softwareId, array $params = []): array
    {
        $query = DB::table($this->releaseTable)->where('software_id', $softwareId);

        $total = $query->count();

        $sort = $params['sort'] ?? 'released_at';
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

    public function createRelease(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->releaseTable)->insertGetId($data);
    }

    public function updateRelease(int $id, array $data): bool
    {
        return DB::table($this->releaseTable)->where('id', $id)->update($data) >= 0;
    }

    public function deleteRelease(int $id): bool
    {
        return DB::table($this->releaseTable)->where('id', $id)->delete() > 0;
    }

    public function getLatestRelease(int $softwareId): ?object
    {
        return DB::table($this->releaseTable)
            ->where('software_id', $softwareId)
            ->where('is_latest', 1)
            ->first();
    }

    public function setLatestRelease(int $softwareId, int $releaseId): void
    {
        // Clear existing latest flag for this software
        DB::table($this->releaseTable)
            ->where('software_id', $softwareId)
            ->where('is_latest', 1)
            ->update(['is_latest' => 0]);

        // Set new latest
        DB::table($this->releaseTable)
            ->where('id', $releaseId)
            ->update(['is_latest' => 1]);

        // Update software latest_version
        $release = $this->findReleaseById($releaseId);
        if ($release) {
            $this->update($softwareId, ['latest_version' => $release->version]);
        }
    }

    public function incrementDownloadCount(int $releaseId): void
    {
        DB::table($this->releaseTable)->where('id', $releaseId)->increment('download_count');

        // Also increment the software total download count
        $release = $this->findReleaseById($releaseId);
        if ($release) {
            DB::table($this->table)->where('id', $release->software_id)->increment('download_count');
        }
    }
}
