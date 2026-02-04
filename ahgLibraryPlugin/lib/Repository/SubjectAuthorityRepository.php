<?php

declare(strict_types=1);

namespace ahgLibraryPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Repository for managing library subject authority records.
 *
 * Provides CRUD operations and query methods for the subject authority
 * system including usage tracking and NER entity linking.
 */
class SubjectAuthorityRepository
{
    protected const TABLE_AUTHORITY = 'library_subject_authority';
    protected const TABLE_ENTITY_MAP = 'library_entity_subject_map';

    /**
     * Find or create a subject authority record.
     *
     * If the subject exists (matched by normalized heading, type, and source),
     * increments usage count. Otherwise creates a new record.
     *
     * @param array $subjectData Subject data with keys: heading, heading_type, source, etc.
     * @return int The authority record ID
     */
    public function findOrCreate(array $subjectData): int
    {
        $heading = trim($subjectData['heading'] ?? '');
        if (empty($heading)) {
            throw new \InvalidArgumentException('Subject heading cannot be empty');
        }

        $normalized = $this->normalizeHeading($heading);
        $headingType = $subjectData['heading_type'] ?? 'topical';
        $source = $subjectData['source'] ?? 'lcsh';

        // Try to find existing record
        $existing = DB::table(self::TABLE_AUTHORITY)
            ->where('heading_normalized', $normalized)
            ->where('heading_type', $headingType)
            ->where('source', $source)
            ->first();

        if ($existing) {
            // Increment usage count
            DB::table(self::TABLE_AUTHORITY)
                ->where('id', $existing->id)
                ->update([
                    'usage_count' => DB::raw('usage_count + 1'),
                    'last_used_at' => date('Y-m-d H:i:s'),
                ]);

            return (int) $existing->id;
        }

        // Create new record
        return (int) DB::table(self::TABLE_AUTHORITY)->insertGetId([
            'heading' => $heading,
            'heading_normalized' => $normalized,
            'heading_type' => $headingType,
            'source' => $source,
            'lcsh_id' => $subjectData['authority_id'] ?? null,
            'lcsh_uri' => $subjectData['lcsh_uri'] ?? null,
            'suggested_dewey' => $subjectData['dewey'] ?? null,
            'suggested_lcc' => $subjectData['lcc'] ?? null,
            'broader_terms' => isset($subjectData['broader_terms'])
                ? json_encode($subjectData['broader_terms'])
                : null,
            'narrower_terms' => isset($subjectData['narrower_terms'])
                ? json_encode($subjectData['narrower_terms'])
                : null,
            'related_terms' => isset($subjectData['related_terms'])
                ? json_encode($subjectData['related_terms'])
                : null,
            'usage_count' => 1,
            'first_used_at' => date('Y-m-d H:i:s'),
            'last_used_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Search for subject authorities (autocomplete).
     *
     * @param string $query Search query
     * @param int $limit Maximum results to return
     * @param string|null $type Optional heading type filter
     * @return array Matching authority records
     */
    public function search(string $query, int $limit = 20, ?string $type = null): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        $dbQuery = DB::table(self::TABLE_AUTHORITY);

        // Use FULLTEXT search for longer queries, LIKE for short ones
        if (strlen($query) >= 3) {
            // Try FULLTEXT first
            $dbQuery->whereRaw('MATCH(heading) AGAINST(? IN NATURAL LANGUAGE MODE)', [$query]);
        } else {
            $dbQuery->where('heading', 'LIKE', $query . '%');
        }

        if ($type) {
            $dbQuery->where('heading_type', $type);
        }

        return $dbQuery
            ->orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($row) => $this->hydrateAuthority($row))
            ->toArray();
    }

    /**
     * Get top subjects by usage count.
     *
     * @param int $limit Maximum results
     * @param string|null $type Optional heading type filter
     * @return array Top authority records
     */
    public function getTopSubjects(int $limit = 50, ?string $type = null): array
    {
        $query = DB::table(self::TABLE_AUTHORITY)
            ->orderBy('usage_count', 'desc')
            ->limit($limit);

        if ($type) {
            $query->where('heading_type', $type);
        }

        return $query
            ->get()
            ->map(fn($row) => $this->hydrateAuthority($row))
            ->toArray();
    }

    /**
     * Get subjects associated with a NER entity.
     *
     * @param string $entityType The entity type (PERSON, ORG, GPE, etc.)
     * @param string $entityValue The entity value
     * @return array Subject authorities linked to this entity
     */
    public function getSubjectsForEntity(string $entityType, string $entityValue): array
    {
        $normalized = $this->normalizeHeading($entityValue);

        return DB::table(self::TABLE_ENTITY_MAP . ' as em')
            ->join(self::TABLE_AUTHORITY . ' as sa', 'em.subject_authority_id', '=', 'sa.id')
            ->where('em.entity_type', $entityType)
            ->where('em.entity_normalized', $normalized)
            ->orderBy('em.co_occurrence_count', 'desc')
            ->orderBy('em.confidence', 'desc')
            ->select([
                'sa.*',
                'em.co_occurrence_count',
                'em.confidence',
            ])
            ->get()
            ->map(fn($row) => $this->hydrateAuthority($row, true))
            ->toArray();
    }

    /**
     * Record a link between a NER entity and a subject.
     *
     * If the link exists, increments the co-occurrence count.
     *
     * @param string $entityType The entity type
     * @param string $entityValue The entity value
     * @param int $authorityId The subject authority ID
     * @param float $confidence Optional confidence score
     */
    public function recordEntitySubjectLink(
        string $entityType,
        string $entityValue,
        int $authorityId,
        float $confidence = 1.0
    ): void {
        $normalized = $this->normalizeHeading($entityValue);

        $existing = DB::table(self::TABLE_ENTITY_MAP)
            ->where('entity_type', $entityType)
            ->where('entity_normalized', $normalized)
            ->where('subject_authority_id', $authorityId)
            ->first();

        if ($existing) {
            // Increment count and update confidence (weighted average)
            $newCount = $existing->co_occurrence_count + 1;
            $newConfidence = (($existing->confidence * $existing->co_occurrence_count) + $confidence) / $newCount;

            DB::table(self::TABLE_ENTITY_MAP)
                ->where('id', $existing->id)
                ->update([
                    'co_occurrence_count' => $newCount,
                    'confidence' => $newConfidence,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            DB::table(self::TABLE_ENTITY_MAP)->insert([
                'entity_type' => $entityType,
                'entity_value' => $entityValue,
                'entity_normalized' => $normalized,
                'subject_authority_id' => $authorityId,
                'co_occurrence_count' => 1,
                'confidence' => $confidence,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Get a subject authority by ID.
     *
     * @param int $id The authority ID
     * @return array|null The authority record or null
     */
    public function find(int $id): ?array
    {
        $row = DB::table(self::TABLE_AUTHORITY)
            ->where('id', $id)
            ->first();

        return $row ? $this->hydrateAuthority($row) : null;
    }

    /**
     * Update a subject authority record.
     *
     * @param int $id The authority ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = [
            'heading', 'heading_type', 'source', 'lcsh_id', 'lcsh_uri',
            'suggested_dewey', 'suggested_lcc', 'broader_terms',
            'narrower_terms', 'related_terms',
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (isset($updateData['heading'])) {
            $updateData['heading_normalized'] = $this->normalizeHeading($updateData['heading']);
        }

        // Encode JSON fields
        foreach (['broader_terms', 'narrower_terms', 'related_terms'] as $jsonField) {
            if (isset($updateData[$jsonField]) && is_array($updateData[$jsonField])) {
                $updateData[$jsonField] = json_encode($updateData[$jsonField]);
            }
        }

        return DB::table(self::TABLE_AUTHORITY)
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Delete a subject authority (cascade deletes entity mappings).
     *
     * @param int $id The authority ID
     * @return bool Success status
     */
    public function delete(int $id): bool
    {
        return DB::table(self::TABLE_AUTHORITY)
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Get statistics about the subject authority database.
     *
     * @return array Statistics array
     */
    public function getStatistics(): array
    {
        $byType = DB::table(self::TABLE_AUTHORITY)
            ->select('heading_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(usage_count) as total_usage'))
            ->groupBy('heading_type')
            ->get()
            ->keyBy('heading_type')
            ->toArray();

        $bySource = DB::table(self::TABLE_AUTHORITY)
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        return [
            'total_authorities' => DB::table(self::TABLE_AUTHORITY)->count(),
            'total_entity_mappings' => DB::table(self::TABLE_ENTITY_MAP)->count(),
            'by_type' => $byType,
            'by_source' => $bySource,
            'average_usage' => DB::table(self::TABLE_AUTHORITY)->avg('usage_count'),
            'most_used' => DB::table(self::TABLE_AUTHORITY)
                ->orderBy('usage_count', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($row) => ['heading' => $row->heading, 'count' => $row->usage_count])
                ->toArray(),
        ];
    }

    /**
     * Normalize a heading for matching purposes.
     *
     * @param string $heading The heading to normalize
     * @return string Normalized heading
     */
    protected function normalizeHeading(string $heading): string
    {
        // Lowercase
        $normalized = mb_strtolower($heading, 'UTF-8');

        // Remove punctuation
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', '', $normalized);

        // Collapse whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Hydrate an authority record from a database row.
     *
     * @param object $row Database row
     * @param bool $includeMapping Include entity mapping data
     * @return array Hydrated record
     */
    protected function hydrateAuthority(object $row, bool $includeMapping = false): array
    {
        $authority = [
            'id' => (int) $row->id,
            'heading' => $row->heading,
            'heading_normalized' => $row->heading_normalized,
            'heading_type' => $row->heading_type,
            'source' => $row->source,
            'lcsh_id' => $row->lcsh_id,
            'lcsh_uri' => $row->lcsh_uri,
            'suggested_dewey' => $row->suggested_dewey,
            'suggested_lcc' => $row->suggested_lcc,
            'broader_terms' => $row->broader_terms ? json_decode($row->broader_terms, true) : null,
            'narrower_terms' => $row->narrower_terms ? json_decode($row->narrower_terms, true) : null,
            'related_terms' => $row->related_terms ? json_decode($row->related_terms, true) : null,
            'usage_count' => (int) $row->usage_count,
            'first_used_at' => $row->first_used_at,
            'last_used_at' => $row->last_used_at,
        ];

        if ($includeMapping && isset($row->co_occurrence_count)) {
            $authority['co_occurrence_count'] = (int) $row->co_occurrence_count;
            $authority['confidence'] = (float) $row->confidence;
        }

        return $authority;
    }
}
