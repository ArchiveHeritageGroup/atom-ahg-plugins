<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Services;

require_once dirname(__DIR__) . '/Repositories/CollectionRepository.php';

use AtomAhgPlugins\ahgMarketplacePlugin\Repositories\CollectionRepository;

class CollectionService
{
    private CollectionRepository $collectionRepo;

    public function __construct()
    {
        $this->collectionRepo = new CollectionRepository();
    }

    // =========================================================================
    // Collection CRUD
    // =========================================================================

    public function createCollection(int $sellerId, array $data): array
    {
        if (empty($data['title'])) {
            return ['success' => false, 'error' => 'Collection title is required'];
        }

        $slug = $this->generateSlug($data['title']);

        $id = $this->collectionRepo->create([
            'seller_id' => $sellerId,
            'title' => $data['title'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'cover_image_path' => $data['cover_image_path'] ?? null,
            'is_public' => $data['is_public'] ?? 1,
            'is_featured' => 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'id' => $id, 'slug' => $slug];
    }

    public function updateCollection(int $id, array $data): array
    {
        $collection = $this->collectionRepo->getById($id);
        if (!$collection) {
            return ['success' => false, 'error' => 'Collection not found'];
        }

        $updateData = [];
        $allowedFields = ['title', 'description', 'cover_image_path', 'is_public', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['title']) && $data['title'] !== $collection->title) {
            $updateData['slug'] = $this->generateSlug($data['title']);
        }

        $this->collectionRepo->update($id, $updateData);

        return ['success' => true];
    }

    public function deleteCollection(int $id): array
    {
        $collection = $this->collectionRepo->getById($id);
        if (!$collection) {
            return ['success' => false, 'error' => 'Collection not found'];
        }

        $this->collectionRepo->delete($id);

        return ['success' => true];
    }

    // =========================================================================
    // Collection Items
    // =========================================================================

    public function addItem(int $collectionId, int $listingId, int $sortOrder = 0, ?string $note = null): array
    {
        $collection = $this->collectionRepo->getById($collectionId);
        if (!$collection) {
            return ['success' => false, 'error' => 'Collection not found'];
        }

        $itemId = $this->collectionRepo->addItem($collectionId, $listingId, $sortOrder, $note);

        // Update item count
        $count = $this->collectionRepo->getItemCount($collectionId);
        $this->collectionRepo->update($collectionId, ['item_count' => $count]);

        return ['success' => true, 'id' => $itemId];
    }

    public function removeItem(int $collectionId, int $listingId): array
    {
        $collection = $this->collectionRepo->getById($collectionId);
        if (!$collection) {
            return ['success' => false, 'error' => 'Collection not found'];
        }

        $removed = $this->collectionRepo->removeItem($collectionId, $listingId);
        if (!$removed) {
            return ['success' => false, 'error' => 'Item not found in collection'];
        }

        // Update item count
        $count = $this->collectionRepo->getItemCount($collectionId);
        $this->collectionRepo->update($collectionId, ['item_count' => $count]);

        return ['success' => true];
    }

    // =========================================================================
    // Collection Queries
    // =========================================================================

    public function getCollection(string $slug): ?array
    {
        $collection = $this->collectionRepo->getBySlug($slug);
        if (!$collection) {
            return null;
        }

        $items = $this->collectionRepo->getItems($collection->id);

        return [
            'collection' => $collection,
            'items' => $items,
        ];
    }

    public function getPublicCollections(int $limit = 20, int $offset = 0): array
    {
        return $this->collectionRepo->getPublicCollections($limit, $offset);
    }

    public function getSellerCollections(int $sellerId): array
    {
        return $this->collectionRepo->getSellerCollections($sellerId);
    }

    public function getFeatured(int $limit = 6): array
    {
        return $this->collectionRepo->getFeatured($limit);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $slug = preg_replace('/-+/', '-', $slug);

        $baseSlug = $slug;
        $counter = 1;
        while ($this->collectionRepo->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
