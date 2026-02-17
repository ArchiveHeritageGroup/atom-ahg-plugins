<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Favorites Repository - Laravel Query Builder
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FavoritesRepository
{
    /**
     * Get all favorites for a user
     */
    public function getByUserId(int $userId): array
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Paginated browse with search, sort, folder filter
     */
    public function browse(int $userId, array $params = []): array
    {
        $query = DB::table('favorites')->where('user_id', $userId);

        // Folder filter
        if (isset($params['folder_id'])) {
            $query->where('folder_id', (int) $params['folder_id']);
        } elseif (!empty($params['unfiled'])) {
            $query->whereNull('folder_id');
        }

        // Text search (title or reference code)
        if (!empty($params['query'])) {
            $search = '%' . $params['query'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('archival_description', 'LIKE', $search)
                  ->orWhere('reference_code', 'LIKE', $search);
            });
        }

        // Sort
        $sort = $params['sort'] ?? 'created_at';
        $dir = $params['sortDir'] ?? 'desc';
        $allowedSorts = ['created_at', 'archival_description', 'updated_at', 'reference_code'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $total = (clone $query)->count();

        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(10, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $hits = $query->offset($offset)->limit($limit)->get()->toArray();

        return ['hits' => $hits, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * Check if item is in favorites
     */
    public function exists(int $userId, int $objectId): bool
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->exists();
    }

    /**
     * Get favorite by user and object
     */
    public function getByUserAndObject(int $userId, int $objectId): ?object
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->first();
    }

    /**
     * Get favorite by ID
     */
    public function getById(int $id): ?object
    {
        return DB::table('favorites')
            ->where('id', $id)
            ->first();
    }

    /**
     * Add to favorites
     */
    public function add(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('favorites')->insertGetId($data);
    }

    /**
     * Remove from favorites
     */
    public function remove(int $id): bool
    {
        return DB::table('favorites')->where('id', $id)->delete() > 0;
    }

    /**
     * Remove by user and object
     */
    public function removeByUserAndObject(int $userId, int $objectId): bool
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->delete() > 0;
    }

    /**
     * Clear all favorites for user
     */
    public function clearByUser(int $userId): int
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Count favorites for user
     */
    public function countByUser(int $userId): int
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Update notes
     */
    public function updateNotes(int $id, ?string $notes): bool
    {
        return DB::table('favorites')
            ->where('id', $id)
            ->update(['notes' => $notes, 'updated_at' => date('Y-m-d H:i:s')]) >= 0;
    }

    /**
     * Bulk remove by IDs (with user ownership check)
     */
    public function bulkRemove(int $userId, array $ids): int
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->delete();
    }

    /**
     * Move favourites to a folder
     */
    public function moveToFolder(int $userId, array $ids, ?int $folderId): int
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update([
                'folder_id' => $folderId,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }
}
