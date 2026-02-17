<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Folder Repository - Laravel Query Builder
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FolderRepository
{
    /**
     * Get all folders for a user ordered by sort_order
     */
    public function getByUserId(int $userId): array
    {
        return DB::table('favorites_folder')
            ->where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get folder by ID
     */
    public function getById(int $id): ?object
    {
        return DB::table('favorites_folder')
            ->where('id', $id)
            ->first();
    }

    /**
     * Create a folder
     */
    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('favorites_folder')->insertGetId($data);
    }

    /**
     * Update a folder
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('favorites_folder')
            ->where('id', $id)
            ->update($data) >= 0;
    }

    /**
     * Delete a folder
     */
    public function delete(int $id): bool
    {
        return DB::table('favorites_folder')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Get child folders for a parent
     */
    public function getChildren(int $parentId): array
    {
        return DB::table('favorites_folder')
            ->where('parent_id', $parentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Count favourites in a folder
     */
    public function getItemCount(int $folderId): int
    {
        return DB::table('favorites')
            ->where('folder_id', $folderId)
            ->count();
    }

    /**
     * Check if folder name is unique for user within same parent
     */
    public function nameExists(int $userId, string $name, ?int $parentId = null, ?int $excludeId = null): bool
    {
        $query = DB::table('favorites_folder')
            ->where('user_id', $userId)
            ->where('name', $name);

        if ($parentId) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get folders with item counts for sidebar rendering
     */
    public function getUserFoldersWithCounts(int $userId): array
    {
        return DB::table('favorites_folder')
            ->leftJoin('favorites', 'favorites_folder.id', '=', 'favorites.folder_id')
            ->where('favorites_folder.user_id', $userId)
            ->groupBy('favorites_folder.id', 'favorites_folder.user_id', 'favorites_folder.name',
                'favorites_folder.description', 'favorites_folder.color', 'favorites_folder.icon',
                'favorites_folder.visibility', 'favorites_folder.sort_order', 'favorites_folder.parent_id',
                'favorites_folder.share_token', 'favorites_folder.share_expires_at',
                'favorites_folder.created_at', 'favorites_folder.updated_at')
            ->orderBy('favorites_folder.sort_order')
            ->orderBy('favorites_folder.name')
            ->select('favorites_folder.*', DB::raw('COUNT(favorites.id) as item_count'))
            ->get()
            ->toArray();
    }

    /**
     * Get count of unfiled favourites (no folder)
     */
    public function getUnfiledCount(int $userId): int
    {
        return DB::table('favorites')
            ->where('user_id', $userId)
            ->whereNull('folder_id')
            ->count();
    }
}
