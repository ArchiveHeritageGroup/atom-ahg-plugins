<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EntityResolutionService - Cross-Collection Entity Resolution
 *
 * Manages entity matching proposals across collections, enabling
 * researchers to identify and resolve duplicate or related entities
 * (actors, places, subjects) that appear in different archival holdings.
 *
 * Table: research_entity_resolution
 *
 * @package ahgResearchPlugin
 * @version 3.0.0
 */
class EntityResolutionService
{
    /**
     * Valid resolution statuses.
     */
    private const VALID_STATUSES = ['proposed', 'accepted', 'rejected'];

    // =========================================================================
    // MATCH PROPOSALS
    // =========================================================================

    /**
     * Propose a match between two entities.
     *
     * @param array $data Keys: entity_a_type, entity_a_id, entity_b_type,
     *                     entity_b_id, confidence, match_method, notes
     * @return int The new resolution ID
     */
    public function proposeMatch(array $data): int
    {
        return DB::table('research_entity_resolution')->insertGetId([
            'entity_a_type' => $data['entity_a_type'],
            'entity_a_id' => $data['entity_a_id'],
            'entity_b_type' => $data['entity_b_type'],
            'entity_b_id' => $data['entity_b_id'],
            'confidence' => $data['confidence'] ?? null,
            'match_method' => $data['match_method'] ?? null,
            'status' => 'proposed',
            'notes' => $data['notes'] ?? null,
            'evidence_json' => isset($data['evidence']) ? json_encode($data['evidence']) : null,
            'relationship_type' => $data['relationship_type'] ?? 'sameAs',
            'proposer_id' => $data['proposer_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get paginated entity resolution proposals.
     *
     * @param array $filters Filters: status, entity_type
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @return array Paginated result with items, total, page, limit
     */
    public function getProposals(array $filters = [], int $page = 1, int $limit = 25): array
    {
        $query = DB::table('research_entity_resolution as er')
            ->leftJoin('research_researcher as r', 'er.resolver_id', '=', 'r.id');

        if (!empty($filters['status'])) {
            $query->where('er.status', $filters['status']);
        }

        if (!empty($filters['entity_type'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('er.entity_a_type', $filters['entity_type'])
                    ->orWhere('er.entity_b_type', $filters['entity_type']);
            });
        }

        if (!empty($filters['relationship_type'])) {
            $query->where('er.relationship_type', $filters['relationship_type']);
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;

        $items = $query->select(
            'er.*',
            'r.first_name as resolver_first_name',
            'r.last_name as resolver_last_name'
        )
            ->orderBy('er.created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        // Enrich items with entity labels
        foreach ($items as &$item) {
            $item->entity_a_label = $this->getEntityLabel($item->entity_a_type, (int) $item->entity_a_id);
            $item->entity_b_label = $this->getEntityLabel($item->entity_b_type, (int) $item->entity_b_id);
            $item->evidence = $item->evidence_json ? json_decode($item->evidence_json, true) : [];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Resolve a match proposal (accept or reject).
     *
     * @param int $id The resolution ID
     * @param string $status New status (accepted or rejected)
     * @param int $resolverId The researcher resolving the match
     * @return bool Success status
     */
    public function resolveMatch(int $id, string $status, int $resolverId): bool
    {
        if (!in_array($status, ['accepted', 'rejected'], true)) {
            return false;
        }

        $resolution = DB::table('research_entity_resolution')
            ->where('id', $id)
            ->first();

        if (!$resolution) {
            return false;
        }

        $updated = DB::table('research_entity_resolution')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'resolver_id' => $resolverId,
                'resolved_at' => date('Y-m-d H:i:s'),
            ]) >= 0;

        // On acceptance with sameAs, create a research assertion
        if ($updated && $status === 'accepted' && ($resolution->relationship_type ?? 'sameAs') === 'sameAs') {
            try {
                $assertionServicePath = \sfConfig::get('sf_plugins_dir') . '/ahgResearchPlugin/lib/Services/AssertionService.php';
                if (file_exists($assertionServicePath)) {
                    require_once $assertionServicePath;
                    $assertionService = new \AssertionService();

                    // Determine project_id from resolver's context (use first active project)
                    $projectId = DB::table('research_project_collaborator')
                        ->where('researcher_id', $resolverId)
                        ->where('status', 'accepted')
                        ->value('project_id');

                    if ($projectId) {
                        $assertionService->createAssertion([
                            'project_id' => $projectId,
                            'researcher_id' => $resolverId,
                            'subject_type' => $resolution->entity_a_type,
                            'subject_id' => $resolution->entity_a_id,
                            'predicate' => 'sameAs',
                            'object_type' => $resolution->entity_b_type,
                            'object_id' => $resolution->entity_b_id,
                            'assertion_type' => 'identity',
                            'confidence' => $resolution->confidence,
                            'evidence_json' => $resolution->evidence_json,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                error_log('EntityResolution sameAs assertion error: ' . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Find candidate matches for an entity by name similarity.
     *
     * For actors, queries actor_i18n for similar authorized_form_of_name
     * using LIKE with the entity's name. Returns candidates with a
     * computed similarity score.
     *
     * @param string $entityType The entity type (e.g. 'actor')
     * @param int $entityId The entity ID
     * @return array List of candidate matches with similarity scores
     */
    public function findCandidates(string $entityType, int $entityId): array
    {
        $candidates = [];

        if ($entityType === 'actor') {
            // Get the source actor's name
            $source = DB::table('actor_i18n')
                ->where('id', $entityId)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->first();

            if (!$source || empty($source->authorized_form_of_name)) {
                return [];
            }

            $sourceName = $source->authorized_form_of_name;

            // Extract name parts for fuzzy matching
            $nameParts = preg_split('/[\s,]+/', $sourceName);
            $nameParts = array_filter($nameParts, function ($part) {
                return mb_strlen($part) >= 3;
            });

            if (empty($nameParts)) {
                return [];
            }

            // Build LIKE query for each significant name part
            $query = DB::table('actor_i18n as ai')
                ->where('ai.id', '!=', $entityId)
                ->where('ai.culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->whereNotNull('ai.authorized_form_of_name')
                ->where('ai.authorized_form_of_name', '!=', '');

            $query->where(function ($q) use ($nameParts) {
                foreach ($nameParts as $part) {
                    $q->orWhere('ai.authorized_form_of_name', 'like', '%' . $part . '%');
                }
            });

            $matches = $query->select('ai.id', 'ai.authorized_form_of_name')
                ->limit(50)
                ->get();

            foreach ($matches as $match) {
                // Compute similarity score (0.0 - 1.0)
                $similarity = 0;
                similar_text(
                    mb_strtolower($sourceName),
                    mb_strtolower($match->authorized_form_of_name),
                    $similarity
                );
                $similarity = round($similarity / 100, 4);

                // Only include candidates above a minimum threshold
                if ($similarity >= 0.3) {
                    $candidates[] = (object) [
                        'entity_type' => 'actor',
                        'entity_id' => $match->id,
                        'label' => $match->authorized_form_of_name,
                        'similarity' => $similarity,
                    ];
                }
            }

            // Sort by similarity descending
            usort($candidates, function ($a, $b) {
                return $b->similarity <=> $a->similarity;
            });
        }

        return $candidates;
    }

    /**
     * Get a single resolution record by ID.
     *
     * @param int $id The resolution ID
     * @return object|null The resolution record or null
     */
    public function getResolution(int $id): ?object
    {
        return DB::table('research_entity_resolution as er')
            ->leftJoin('research_researcher as r', 'er.resolver_id', '=', 'r.id')
            ->where('er.id', $id)
            ->select(
                'er.*',
                'r.first_name as resolver_first_name',
                'r.last_name as resolver_last_name',
                'r.email as resolver_email'
            )
            ->first();
    }

    /**
     * Delete a resolution record.
     *
     * @param int $id The resolution ID
     * @return bool Success status
     */
    public function deleteResolution(int $id): bool
    {
        return DB::table('research_entity_resolution')
            ->where('id', $id)
            ->delete() > 0;
    }

    /**
     * Find assertions that conflict with a proposed entity resolution.
     *
     * Checks if there are existing assertions between the two entities that
     * contradict the proposed relationship (e.g. a "differentFrom" assertion
     * when proposing "sameAs").
     *
     * @param int $resolutionId The resolution ID
     * @return array List of conflicting assertions
     */
    public function getConflictingAssertions(int $resolutionId): array
    {
        $resolution = DB::table('research_entity_resolution')
            ->where('id', $resolutionId)
            ->first();

        if (!$resolution) {
            return [];
        }

        // Find assertions involving both entities
        try {
            $conflicts = DB::table('research_assertion')
                ->where(function ($q) use ($resolution) {
                    $q->where(function ($inner) use ($resolution) {
                        $inner->where('subject_type', $resolution->entity_a_type)
                            ->where('subject_id', $resolution->entity_a_id)
                            ->where('object_type', $resolution->entity_b_type)
                            ->where('object_id', $resolution->entity_b_id);
                    })->orWhere(function ($inner) use ($resolution) {
                        $inner->where('subject_type', $resolution->entity_b_type)
                            ->where('subject_id', $resolution->entity_b_id)
                            ->where('object_type', $resolution->entity_a_type)
                            ->where('object_id', $resolution->entity_a_id);
                    });
                })
                ->whereIn('status', ['proposed', 'accepted'])
                ->get()
                ->toArray();

            // Filter to only genuinely conflicting predicates
            $proposedRelType = $resolution->relationship_type ?? 'sameAs';
            $conflictingPredicates = $this->getConflictingPredicates($proposedRelType);

            return array_filter($conflicts, function ($a) use ($conflictingPredicates) {
                return in_array($a->predicate ?? '', $conflictingPredicates, true);
            });
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get predicates that conflict with a given relationship type.
     */
    private function getConflictingPredicates(string $relationshipType): array
    {
        $conflicts = [
            'sameAs' => ['differentFrom', 'supersedes', 'replacedBy'],
            'relatedTo' => [],
            'partOf' => ['differentFrom'],
            'memberOf' => ['differentFrom'],
        ];

        return $conflicts[$relationshipType] ?? [];
    }

    /**
     * Get accepted entity resolution links for a given entity.
     *
     * Returns the network of sameAs/relatedTo connections for an entity,
     * useful for displaying entity relationship networks.
     *
     * @param string $entityType The entity type (e.g. 'actor')
     * @param int $entityId The entity ID
     * @return array List of linked entities with relationship info
     */
    public function getEntityLinks(string $entityType, int $entityId): array
    {
        $links = DB::table('research_entity_resolution')
            ->where('status', 'accepted')
            ->where(function ($q) use ($entityType, $entityId) {
                $q->where(function ($inner) use ($entityType, $entityId) {
                    $inner->where('entity_a_type', $entityType)
                        ->where('entity_a_id', $entityId);
                })->orWhere(function ($inner) use ($entityType, $entityId) {
                    $inner->where('entity_b_type', $entityType)
                        ->where('entity_b_id', $entityId);
                });
            })
            ->orderBy('resolved_at', 'desc')
            ->get()
            ->toArray();

        // Enrich each link with the "other" entity's label
        foreach ($links as &$link) {
            if ($link->entity_a_type === $entityType && (int) $link->entity_a_id === $entityId) {
                $link->linked_type = $link->entity_b_type;
                $link->linked_id = $link->entity_b_id;
            } else {
                $link->linked_type = $link->entity_a_type;
                $link->linked_id = $link->entity_a_id;
            }
            $link->linked_label = $this->getEntityLabel($link->linked_type, (int) $link->linked_id);
        }

        return $links;
    }

    /**
     * Get a human-readable label for an entity.
     */
    private function getEntityLabel(string $type, int $id): string
    {
        if ($type === 'actor') {
            $name = DB::table('actor_i18n')
                ->where('id', $id)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('authorized_form_of_name');
            return $name ?: "Actor #{$id}";
        }

        if ($type === 'information_object') {
            $title = DB::table('information_object_i18n')
                ->where('id', $id)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('title');
            return $title ?: "Object #{$id}";
        }

        if ($type === 'repository') {
            $name = DB::table('actor_i18n')
                ->where('id', $id)
                ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
                ->value('authorized_form_of_name');
            return $name ?: "Repository #{$id}";
        }

        return ucfirst(str_replace('_', ' ', $type)) . " #{$id}";
    }
}
