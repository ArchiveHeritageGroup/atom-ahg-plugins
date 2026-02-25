<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class ReviewService
{
    protected string $culture;
    protected string $table = 'registry_review';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Queries
    // =========================================================================

    /**
     * Get reviews for an entity with pagination.
     */
    public function findByEntity(string $type, int $id, array $params = []): array
    {
        $query = DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id);

        // By default show only visible reviews (admin can override)
        if (!isset($params['include_hidden']) || !$params['include_hidden']) {
            $query->where('is_visible', 1);
        }

        $total = $query->count();

        $limit = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $items = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return ['items' => $items, 'total' => $total];
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new review. Validates rating 1-5 and recalculates aggregate.
     */
    public function create(array $data): array
    {
        if (empty($data['entity_type']) || empty($data['entity_id'])) {
            return ['success' => false, 'error' => 'Entity type and entity ID are required'];
        }
        if (!in_array($data['entity_type'], ['vendor', 'software'])) {
            return ['success' => false, 'error' => 'Entity type must be vendor or software'];
        }
        if (!isset($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
            return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
        }

        $data['rating'] = (int) $data['rating'];
        $data['is_visible'] = $data['is_visible'] ?? 1;
        $data['is_verified'] = $data['is_verified'] ?? 0;
        $data['created_at'] = date('Y-m-d H:i:s');

        $id = DB::table($this->table)->insertGetId($data);

        // Recalculate aggregate rating on entity
        $this->recalculateRating($data['entity_type'], $data['entity_id']);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Update an existing review.
     */
    public function update(int $id, array $data): array
    {
        $review = DB::table($this->table)->where('id', $id)->first();
        if (!$review) {
            return ['success' => false, 'error' => 'Review not found'];
        }

        if (isset($data['rating'])) {
            $data['rating'] = (int) $data['rating'];
            if ($data['rating'] < 1 || $data['rating'] > 5) {
                return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
            }
        }

        DB::table($this->table)->where('id', $id)->update($data);

        // Recalculate aggregate
        $this->recalculateRating($review->entity_type, $review->entity_id);

        return ['success' => true];
    }

    /**
     * Delete a review and recalculate aggregate.
     */
    public function delete(int $id): array
    {
        $review = DB::table($this->table)->where('id', $id)->first();
        if (!$review) {
            return ['success' => false, 'error' => 'Review not found'];
        }

        $entityType = $review->entity_type;
        $entityId = $review->entity_id;

        DB::table($this->table)->where('id', $id)->delete();

        $this->recalculateRating($entityType, $entityId);

        return ['success' => true];
    }

    // =========================================================================
    // Moderation
    // =========================================================================

    /**
     * Toggle review visibility.
     */
    public function toggleVisibility(int $id): array
    {
        $review = DB::table($this->table)->where('id', $id)->first();
        if (!$review) {
            return ['success' => false, 'error' => 'Review not found'];
        }

        $newVisibility = $review->is_visible ? 0 : 1;

        DB::table($this->table)->where('id', $id)->update([
            'is_visible' => $newVisibility,
        ]);

        // Recalculate rating since visibility changed
        $this->recalculateRating($review->entity_type, $review->entity_id);

        return ['success' => true, 'is_visible' => $newVisibility];
    }

    // =========================================================================
    // Aggregation
    // =========================================================================

    /**
     * Recalculate average rating and count on the entity table.
     */
    public function recalculateRating(string $entityType, int $entityId): void
    {
        $stats = DB::table($this->table)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_visible', 1)
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as cnt')
            ->first();

        $avgRating = $stats->avg_rating ? round($stats->avg_rating, 2) : 0.00;
        $count = $stats->cnt ?? 0;

        $targetTable = $entityType === 'vendor' ? 'registry_vendor' : 'registry_software';

        DB::table($targetTable)->where('id', $entityId)->update([
            'average_rating' => $avgRating,
            'rating_count' => $count,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
