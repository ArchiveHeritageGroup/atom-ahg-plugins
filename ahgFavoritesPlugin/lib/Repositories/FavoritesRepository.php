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
            ->update(['notes' => $notes, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }
}
