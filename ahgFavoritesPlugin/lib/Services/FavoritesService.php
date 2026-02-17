<?php

namespace AtomAhgPlugins\ahgFavoritesPlugin\Services;

require_once dirname(__DIR__).'/Repositories/FavoritesRepository.php';

use AtomAhgPlugins\ahgFavoritesPlugin\Repositories\FavoritesRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Favorites Service - Business Logic
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class FavoritesService
{
    private FavoritesRepository $repository;

    public function __construct()
    {
        $this->repository = new FavoritesRepository();
    }

    /**
     * Get the current user culture with fallback
     */
    private function getCulture(): string
    {
        try {
            return \sfContext::getInstance()->getUser()->getCulture() ?: 'en';
        } catch (\Exception $e) {
            return 'en';
        }
    }

    /**
     * Resolve title for an object with multi-culture support
     */
    private function resolveTitle(int $objectId): string
    {
        $culture = $this->getCulture();

        $title = DB::table('information_object_i18n')
            ->where('id', $objectId)
            ->where('culture', $culture)
            ->value('title');

        // Fallback to English if current culture has no title
        if (!$title && $culture !== 'en') {
            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->value('title');
        }

        return $title ?: \__('Untitled');
    }

    /**
     * Browse favourites with pagination, search, sort, folder filter
     */
    public function browse(int $userId, array $params): array
    {
        $result = $this->repository->browse($userId, $params);

        // Enrich each hit with resolved title, slug, and extended metadata
        $culture = $this->getCulture();
        $enriched = [];

        // Type icon map
        $typeIcons = [
            'information_object' => 'fas fa-file-alt',
            'actor' => 'fas fa-user',
            'repository' => 'fas fa-building',
            'accession' => 'fas fa-archive',
            'function' => 'fas fa-sitemap',
            'research_journal' => 'fas fa-journal-whills',
            'research_collection' => 'fas fa-layer-group',
            'research_project' => 'fas fa-project-diagram',
            'research_bibliography' => 'fas fa-book',
            'research_workspace' => 'fas fa-users',
            'research_report' => 'fas fa-file-alt',
        ];

        $researchTypes = ['research_journal', 'research_collection', 'research_project', 'research_bibliography', 'research_workspace', 'research_report'];

        foreach ($result['hits'] as $fav) {
            $objectId = $fav->archival_description_id;
            $objectType = $fav->object_type ?? 'information_object';

            // Research items: use stored title/slug directly, skip AtoM enrichment
            if (in_array($objectType, $researchTypes)) {
                $enriched[] = (object) [
                    'id' => $fav->id,
                    'user_id' => $fav->user_id,
                    'archival_description_id' => $objectId,
                    'title' => $fav->archival_description ?? \__('Untitled'),
                    'slug' => $fav->slug,
                    'notes' => $fav->notes ?? null,
                    'object_type' => $objectType,
                    'reference_code' => null,
                    'level_of_description' => null,
                    'date_range' => null,
                    'repository_name' => null,
                    'has_digital_object' => false,
                    'thumbnail_path' => null,
                    'type_icon' => $typeIcons[$objectType] ?? 'fas fa-file-alt',
                    'item_updated_since' => false,
                    'item_accessible' => true,
                    'folder_id' => $fav->folder_id ?? null,
                    'created_at' => $fav->created_at,
                    'updated_at' => $fav->updated_at ?? null,
                ];
                continue;
            }

            // Check object accessibility
            $itemAccessible = $objectId && DB::table('object')->where('id', $objectId)->exists();

            // Resolve title in user's culture
            $title = null;
            if ($objectId && $itemAccessible) {
                $title = DB::table('information_object_i18n')
                    ->where('id', $objectId)
                    ->where('culture', $culture)
                    ->value('title');

                if (!$title && $culture !== 'en') {
                    $title = DB::table('information_object_i18n')
                        ->where('id', $objectId)
                        ->where('culture', 'en')
                        ->value('title');
                }
            }

            // Resolve current slug
            $slug = DB::table('slug')
                ->where('object_id', $objectId)
                ->value('slug');

            // Get IO details for extended metadata
            $io = null;
            $lod = null;
            $dateRange = '';
            $repositoryName = '';
            $hasDigitalObject = false;
            $thumbnailPath = null;
            $itemUpdatedSince = false;

            if ($objectId && $itemAccessible) {
                $io = DB::table('information_object')
                    ->where('id', $objectId)
                    ->first();

                // Level of description
                if ($io && $io->level_of_description_id) {
                    $lod = DB::table('term_i18n')
                        ->where('id', $io->level_of_description_id)
                        ->where('culture', $culture)
                        ->value('name');
                    if (!$lod && $culture !== 'en') {
                        $lod = DB::table('term_i18n')
                            ->where('id', $io->level_of_description_id)
                            ->where('culture', 'en')
                            ->value('name');
                    }
                }

                // Date range
                if ($io) {
                    $parts = [];
                    if (!empty($io->start_date)) {
                        $parts[] = $io->start_date;
                    }
                    if (!empty($io->end_date)) {
                        $parts[] = $io->end_date;
                    }
                    $dateRange = implode(' - ', $parts);
                }

                // Repository name
                if ($io && $io->repository_id) {
                    $repositoryName = DB::table('actor_i18n')
                        ->where('id', $io->repository_id)
                        ->where('culture', $culture)
                        ->value('authorized_form_of_name') ?? '';
                    if (!$repositoryName && $culture !== 'en') {
                        $repositoryName = DB::table('actor_i18n')
                            ->where('id', $io->repository_id)
                            ->where('culture', 'en')
                            ->value('authorized_form_of_name') ?? '';
                    }
                }

                // Digital object check
                $hasDigitalObject = DB::table('digital_object')
                    ->where('object_id', $objectId)
                    ->exists();

                // Thumbnail path (usage_id 142 = thumbnail)
                if ($hasDigitalObject) {
                    $thumbnailPath = DB::table('digital_object')
                        ->where('object_id', $objectId)
                        ->where('usage_id', 142)
                        ->value('path');
                }

                // Item updated since favourited
                if ($io && isset($io->updated_at) && $fav->created_at) {
                    $itemUpdatedSince = strtotime($io->updated_at) > strtotime($fav->created_at);
                }
            }

            $objectType = $fav->object_type ?? 'information_object';

            $enriched[] = (object) [
                'id' => $fav->id,
                'user_id' => $fav->user_id,
                'archival_description_id' => $objectId,
                'title' => $title ?? $fav->archival_description ?? \__('Untitled'),
                'slug' => $slug ?? $fav->slug,
                'notes' => $fav->notes ?? null,
                'object_type' => $objectType,
                'reference_code' => $fav->reference_code ?? null,
                'level_of_description' => $lod,
                'date_range' => $dateRange,
                'repository_name' => $repositoryName,
                'has_digital_object' => $hasDigitalObject,
                'thumbnail_path' => $thumbnailPath,
                'type_icon' => $typeIcons[$objectType] ?? 'fas fa-file-alt',
                'item_updated_since' => $itemUpdatedSince,
                'item_accessible' => $itemAccessible,
                'folder_id' => $fav->folder_id ?? null,
                'created_at' => $fav->created_at,
                'updated_at' => $fav->updated_at ?? null,
            ];
        }

        return [
            'hits' => $enriched,
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
        ];
    }

    /**
     * Get user's favorites with object details (legacy compat)
     */
    public function getUserFavorites(int $userId): array
    {
        $result = $this->browse($userId, ['limit' => 1000]);

        return $result['hits'];
    }

    /**
     * Add item to favorites
     */
    public function addToFavorites(int $userId, int $objectId, ?string $title = null, ?string $slug = null): array
    {
        if ($this->repository->exists($userId, $objectId)) {
            return ['success' => false, 'message' => \__('Item is already in your favorites.')];
        }

        if (!$title) {
            $title = $this->resolveTitle($objectId);
        }

        if (!$slug) {
            $slug = DB::table('slug')
                ->where('object_id', $objectId)
                ->value('slug');
        }

        // Determine object type
        $objectType = 'information_object';
        if (DB::table('actor')->where('id', $objectId)->exists()) {
            $objectType = 'actor';
        } elseif (DB::table('repository')->where('id', $objectId)->exists()) {
            $objectType = 'repository';
        }

        // Get reference code
        $referenceCode = DB::table('information_object')
            ->where('id', $objectId)
            ->value('identifier');

        $id = $this->repository->add([
            'user_id' => $userId,
            'archival_description_id' => $objectId,
            'archival_description' => $title,
            'slug' => $slug,
            'object_type' => $objectType,
            'reference_code' => $referenceCode,
        ]);

        return ['success' => true, 'message' => \__('Added to favorites.'), 'id' => $id];
    }

    /**
     * Remove item from favorites
     */
    public function removeFromFavorites(int $userId, int $favoriteId): array
    {
        $favorite = $this->repository->getById($favoriteId);

        if (!$favorite) {
            return ['success' => false, 'message' => \__('Favorite not found.')];
        }

        if ($favorite->user_id != $userId) {
            return ['success' => false, 'message' => \__('Access denied.')];
        }

        $this->repository->remove($favoriteId);

        return ['success' => true, 'message' => \__('Removed from favorites.')];
    }

    /**
     * Remove by object ID
     */
    public function removeByObject(int $userId, int $objectId): array
    {
        if (!$this->repository->exists($userId, $objectId)) {
            return ['success' => false, 'message' => \__('Item not in favorites.')];
        }

        $this->repository->removeByUserAndObject($userId, $objectId);

        return ['success' => true, 'message' => \__('Removed from favorites.')];
    }

    /**
     * Toggle favourite (add if not exists, remove if exists)
     */
    public function toggle(int $userId, int $objectId, ?string $slug = null): array
    {
        if ($this->repository->exists($userId, $objectId)) {
            $this->repository->removeByUserAndObject($userId, $objectId);

            return ['success' => true, 'action' => 'removed', 'favorited' => false, 'message' => \__('Removed from favorites.')];
        }

        $result = $this->addToFavorites($userId, $objectId, null, $slug);

        return [
            'success' => $result['success'],
            'action' => 'added',
            'favorited' => true,
            'message' => $result['message'],
            'id' => $result['id'] ?? null,
        ];
    }

    /**
     * Clear all favorites for user
     */
    public function clearAll(int $userId): array
    {
        $count = $this->repository->clearByUser($userId);

        return ['success' => true, 'message' => \__('Cleared %1% favorites.', ['%1%' => $count])];
    }

    /**
     * Check if item is favorited
     */
    public function isFavorited(int $userId, int $objectId): bool
    {
        return $this->repository->exists($userId, $objectId);
    }

    /**
     * Toggle a custom (non-AtoM) entity as favorite, scoped by object_type.
     */
    public function toggleCustom(int $userId, int $objectId, string $objectType, string $title, string $url, ?int $folderId = null): array
    {
        if ($this->repository->existsByType($userId, $objectId, $objectType)) {
            $this->repository->removeByUserObjectType($userId, $objectId, $objectType);
            return ['success' => true, 'action' => 'removed', 'favorited' => false, 'message' => \__('Removed from favorites.')];
        }

        $data = [
            'user_id' => $userId,
            'archival_description_id' => $objectId,
            'archival_description' => $title,
            'slug' => $url,
            'object_type' => $objectType,
            'reference_code' => null,
        ];

        if ($folderId) {
            $data['folder_id'] = $folderId;
        }

        $id = $this->repository->add($data);

        return ['success' => true, 'action' => 'added', 'favorited' => true, 'message' => \__('Added to favorites.'), 'id' => $id];
    }

    /**
     * Check if a custom entity is favorited (scoped by object_type).
     */
    public function isFavoritedCustom(int $userId, int $objectId, string $objectType): bool
    {
        return $this->repository->existsByType($userId, $objectId, $objectType);
    }

    /**
     * Get favorites count
     */
    public function getCount(int $userId): int
    {
        return $this->repository->countByUser($userId);
    }

    /**
     * Update notes for a favorite
     */
    public function updateNotes(int $userId, int $favoriteId, ?string $notes): array
    {
        $favorite = $this->repository->getById($favoriteId);

        if (!$favorite) {
            return ['success' => false, 'message' => \__('Favorite not found.')];
        }

        if ($favorite->user_id != $userId) {
            return ['success' => false, 'message' => \__('Access denied.')];
        }

        $this->repository->updateNotes($favoriteId, $notes);

        return ['success' => true, 'message' => \__('Notes updated.')];
    }

    /**
     * Bulk remove favourites
     */
    public function bulkRemove(int $userId, array $ids): array
    {
        $ids = array_map('intval', $ids);
        $count = $this->repository->bulkRemove($userId, $ids);

        return ['success' => true, 'message' => \__('Removed %1% favorites.', ['%1%' => $count])];
    }

    /**
     * Move favourites to a folder
     */
    public function moveToFolder(int $userId, array $ids, ?int $folderId): array
    {
        $ids = array_map('intval', $ids);

        // Validate folder ownership if moving to a folder
        if ($folderId) {
            require_once dirname(__DIR__).'/Repositories/FolderRepository.php';
            $folderRepo = new \AtomAhgPlugins\ahgFavoritesPlugin\Repositories\FolderRepository();
            $folder = $folderRepo->getById($folderId);
            if (!$folder || $folder->user_id != $userId) {
                return ['success' => false, 'message' => \__('Folder not found or access denied.')];
            }
        }

        $count = $this->repository->moveToFolder($userId, $ids, $folderId);

        return ['success' => true, 'message' => \__('Moved %1% items.', ['%1%' => $count])];
    }
}
