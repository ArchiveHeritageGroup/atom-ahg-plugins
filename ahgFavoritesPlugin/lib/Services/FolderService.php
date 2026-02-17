<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Services;

require_once dirname(__DIR__).'/Repositories/FolderRepository.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Repositories\FolderRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Folder Service - Business Logic for favorites folders
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FolderService
{
    private FolderRepository $repository;

    public function __construct()
    {
        $this->repository = new FolderRepository();
    }

    /**
     * Get user's folders with item counts
     */
    public function getUserFolders(int $userId): array
    {
        return $this->repository->getUserFoldersWithCounts($userId);
    }

    /**
     * Get unfiled count
     */
    public function getUnfiledCount(int $userId): int
    {
        return $this->repository->getUnfiledCount($userId);
    }

    /**
     * Create a folder
     */
    public function createFolder(int $userId, string $name, ?string $description = null, ?int $parentId = null): array
    {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'message' => 'Folder name is required.'];
        }

        if (strlen($name) > 255) {
            return ['success' => false, 'message' => 'Folder name is too long (max 255 characters).'];
        }

        // Validate nesting (max 1 level deep)
        if ($parentId) {
            $parent = $this->repository->getById($parentId);
            if (!$parent) {
                return ['success' => false, 'message' => 'Parent folder not found.'];
            }
            if ($parent->user_id != $userId) {
                return ['success' => false, 'message' => 'Access denied.'];
            }
            if ($parent->parent_id) {
                return ['success' => false, 'message' => 'Folders can only be nested one level deep.'];
            }
        }

        // Check uniqueness
        if ($this->repository->nameExists($userId, $name, $parentId)) {
            return ['success' => false, 'message' => 'A folder with this name already exists.'];
        }

        $id = $this->repository->create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'parent_id' => $parentId,
        ]);

        return ['success' => true, 'message' => 'Folder created.', 'id' => $id];
    }

    /**
     * Update a folder
     */
    public function updateFolder(int $userId, int $folderId, array $data): array
    {
        $folder = $this->repository->getById($folderId);
        if (!$folder) {
            return ['success' => false, 'message' => 'Folder not found.'];
        }
        if ($folder->user_id != $userId) {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        $update = [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (empty($name)) {
                return ['success' => false, 'message' => 'Folder name is required.'];
            }
            if ($this->repository->nameExists($userId, $name, $folder->parent_id, $folderId)) {
                return ['success' => false, 'message' => 'A folder with this name already exists.'];
            }
            $update['name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $update['description'] = $data['description'];
        }

        if (array_key_exists('color', $data)) {
            $update['color'] = $data['color'];
        }

        if (array_key_exists('icon', $data)) {
            $update['icon'] = $data['icon'];
        }

        if (!empty($update)) {
            $this->repository->update($folderId, $update);
        }

        return ['success' => true, 'message' => 'Folder updated.'];
    }

    /**
     * Delete a folder (moves items to unfiled)
     */
    public function deleteFolder(int $userId, int $folderId): array
    {
        $folder = $this->repository->getById($folderId);
        if (!$folder) {
            return ['success' => false, 'message' => 'Folder not found.'];
        }
        if ($folder->user_id != $userId) {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        // Move child folders to root
        $children = $this->repository->getChildren($folderId);
        foreach ($children as $child) {
            $this->repository->update($child->id, ['parent_id' => null]);
        }

        // Move items to unfiled
        DB::table('favorites')
            ->where('folder_id', $folderId)
            ->update(['folder_id' => null, 'updated_at' => date('Y-m-d H:i:s')]);

        $this->repository->delete($folderId);

        return ['success' => true, 'message' => 'Folder deleted. Items moved to unfiled.'];
    }

    /**
     * Get folder by ID with ownership check
     */
    public function getFolder(int $userId, int $folderId): ?object
    {
        $folder = $this->repository->getById($folderId);
        if (!$folder || $folder->user_id != $userId) {
            return null;
        }

        return $folder;
    }
}
