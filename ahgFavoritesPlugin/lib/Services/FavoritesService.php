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
     * Get user's favorites with object details
     */
    public function getUserFavorites(int $userId): array
    {
        $favorites = $this->repository->getByUserId($userId);
        $result = [];

        foreach ($favorites as $favorite) {
            // Get information object details
            $io = DB::table('information_object')
                ->where('id', $favorite->archival_description_id)
                ->first();

            $title = null;
            if ($io) {
                $title = DB::table('information_object_i18n')
                    ->where('id', $io->id)
                    ->where('culture', 'en')
                    ->value('title');
            }

            $slug = DB::table('slug')
                ->where('object_id', $favorite->archival_description_id)
                ->value('slug');

            $result[] = (object) [
                'id' => $favorite->id,
                'user_id' => $favorite->user_id,
                'archival_description_id' => $favorite->archival_description_id,
                'title' => $title ?? $favorite->archival_description ?? 'Untitled',
                'slug' => $slug ?? $favorite->slug,
                'notes' => $favorite->notes ?? null,
                'created_at' => $favorite->created_at,
            ];
        }

        return $result;
    }

    /**
     * Add item to favorites
     */
    public function addToFavorites(int $userId, int $objectId, ?string $title = null, ?string $slug = null): array
    {
        // Check if already exists
        if ($this->repository->exists($userId, $objectId)) {
            return ['success' => false, 'message' => 'Item is already in your favorites.'];
        }

        // Get title if not provided
        if (!$title) {
            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->value('title') ?? 'Untitled';
        }

        // Get slug if not provided
        if (!$slug) {
            $slug = DB::table('slug')
                ->where('object_id', $objectId)
                ->value('slug');
        }

        $id = $this->repository->add([
            'user_id' => $userId,
            'archival_description_id' => $objectId,
            'archival_description' => $title,
            'slug' => $slug,
        ]);

        return ['success' => true, 'message' => 'Added to favorites.', 'id' => $id];
    }

    /**
     * Remove item from favorites
     */
    public function removeFromFavorites(int $userId, int $favoriteId): array
    {
        $favorite = $this->repository->getById($favoriteId);

        if (!$favorite) {
            return ['success' => false, 'message' => 'Favorite not found.'];
        }

        if ($favorite->user_id != $userId) {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        $this->repository->remove($favoriteId);

        return ['success' => true, 'message' => 'Removed from favorites.'];
    }

    /**
     * Remove by object ID
     */
    public function removeByObject(int $userId, int $objectId): array
    {
        if (!$this->repository->exists($userId, $objectId)) {
            return ['success' => false, 'message' => 'Item not in favorites.'];
        }

        $this->repository->removeByUserAndObject($userId, $objectId);

        return ['success' => true, 'message' => 'Removed from favorites.'];
    }

    /**
     * Clear all favorites for user
     */
    public function clearAll(int $userId): array
    {
        $count = $this->repository->clearByUser($userId);
        return ['success' => true, 'message' => "Cleared {$count} favorites."];
    }

    /**
     * Check if item is favorited
     */
    public function isFavorited(int $userId, int $objectId): bool
    {
        return $this->repository->exists($userId, $objectId);
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
            return ['success' => false, 'message' => 'Favorite not found.'];
        }

        if ($favorite->user_id != $userId) {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        $this->repository->updateNotes($favoriteId, $notes);

        return ['success' => true, 'message' => 'Notes updated.'];
    }
}
