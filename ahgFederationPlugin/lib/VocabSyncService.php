<?php

namespace AhgFederation;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;
use ahgCorePlugin\Services\AhgTaxonomyService;

/**
 * Vocabulary Synchronization Service
 *
 * Manages synchronization of taxonomies/vocabularies between federation peers.
 * Supports bidirectional sync, conflict resolution, and change tracking.
 *
 * Configuration values (sync_direction, conflict_resolution, status) are stored
 * in ahg_dropdown table - use AhgTaxonomyService for dropdown values in UI forms.
 */
class VocabSyncService
{
    // Sync direction constants matching ahg_dropdown federation_sync_direction codes
    public const DIRECTION_PULL = 'pull';
    public const DIRECTION_PUSH = 'push';
    public const DIRECTION_BIDIRECTIONAL = 'bidirectional';

    // Conflict resolution constants matching ahg_dropdown federation_conflict_resolution codes
    public const CONFLICT_PREFER_LOCAL = 'prefer_local';
    public const CONFLICT_PREFER_REMOTE = 'prefer_remote';
    public const CONFLICT_SKIP = 'skip';
    public const CONFLICT_MERGE = 'merge';

    // Session status constants matching ahg_dropdown federation_session_status codes
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    // Mapping status constants matching ahg_dropdown federation_mapping_status codes
    public const MAPPING_MATCHED = 'matched';
    public const MAPPING_CREATED = 'created';
    public const MAPPING_CONFLICT = 'conflict';
    public const MAPPING_SKIPPED = 'skipped';

    // Change type constants matching ahg_dropdown federation_change_type codes
    public const CHANGE_TERM_ADDED = 'term_added';
    public const CHANGE_TERM_UPDATED = 'term_updated';
    public const CHANGE_TERM_DELETED = 'term_deleted';
    public const CHANGE_TERM_MOVED = 'term_moved';
    public const CHANGE_RELATION_ADDED = 'relation_added';
    public const CHANGE_RELATION_REMOVED = 'relation_removed';

    protected HarvestClient $client;

    public function __construct()
    {
        $this->client = new HarvestClient();
    }

    // =========================================================================
    // VOCABULARY EXPORT
    // =========================================================================

    /**
     * Export a taxonomy as JSON for sharing with peers
     *
     * @param int $taxonomyId Taxonomy ID to export
     * @param array $options Export options
     * @return array Exported taxonomy data
     */
    public function exportTaxonomy(int $taxonomyId, array $options = []): array
    {
        \AhgCore\Core\AhgDb::init();

        $taxonomy = DB::table('taxonomy as t')
            ->leftJoin('taxonomy_i18n as ti', function ($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.id', $taxonomyId)
            ->select('t.*', 'ti.name')
            ->first();

        if (!$taxonomy) {
            throw new \Exception("Taxonomy not found: $taxonomyId");
        }

        // Get all terms
        $terms = DB::table('term as t')
            ->leftJoin('term_i18n as ti', function ($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', $taxonomyId)
            ->select('t.id', 't.parent_id', 't.code', 'ti.name', 'ti.culture')
            ->orderBy('t.lft')
            ->get();

        // Build hierarchical structure
        $termMap = [];
        $rootTerms = [];

        foreach ($terms as $term) {
            $termData = [
                'id' => $term->id,
                'code' => $term->code,
                'name' => $term->name,
                'parentId' => $term->parent_id,
                'children' => [],
            ];

            // Get all i18n translations
            $translations = DB::table('term_i18n')
                ->where('id', $term->id)
                ->get()
                ->keyBy('culture')
                ->map(fn($t) => $t->name)
                ->toArray();

            $termData['translations'] = $translations;

            // Get related terms (USE, UF, BT, NT, RT)
            $relations = $this->getTermRelations($term->id);
            if (!empty($relations)) {
                $termData['relations'] = $relations;
            }

            $termMap[$term->id] = $termData;

            // Determine root terms (parent is taxonomy root or null)
            $taxonomyRootId = DB::table('term')->where('id', $taxonomyId)->value('id');
            if ($term->parent_id === $taxonomyRootId || $term->parent_id === null) {
                $rootTerms[] = $term->id;
            }
        }

        // Build tree
        foreach ($termMap as $id => &$term) {
            if ($term['parentId'] && isset($termMap[$term['parentId']])) {
                $termMap[$term['parentId']]['children'][] = &$term;
            }
        }

        // Get only root terms with their hierarchies
        $hierarchy = array_filter($termMap, fn($t) => in_array($t['id'], $rootTerms));

        return [
            'taxonomy' => [
                'id' => $taxonomy->id,
                'name' => $taxonomy->name,
                'usage' => $taxonomy->usage ?? null,
            ],
            'terms' => array_values($hierarchy),
            'termCount' => count($terms),
            'exportedAt' => date('c'),
            'exportFormat' => 'heritage-vocab-1.0',
        ];
    }

    /**
     * Get term relations (thesaurus relationships)
     */
    protected function getTermRelations(int $termId): array
    {
        $relations = [];

        // Get relation records
        $relRecords = DB::table('relation as r')
            ->join('relation_i18n as ri', function ($j) {
                $j->on('r.id', '=', 'ri.id')->where('ri.culture', '=', 'en');
            })
            ->where(function ($q) use ($termId) {
                $q->where('r.subject_id', $termId)
                    ->orWhere('r.object_id', $termId);
            })
            ->select('r.*', 'ri.description')
            ->get();

        foreach ($relRecords as $rel) {
            $relatedTermId = $rel->subject_id == $termId ? $rel->object_id : $rel->subject_id;
            $relatedTerm = DB::table('term_i18n')
                ->where('id', $relatedTermId)
                ->where('culture', 'en')
                ->first();

            if ($relatedTerm) {
                $relations[] = [
                    'type' => $this->getRelationType($rel->type_id),
                    'termId' => $relatedTermId,
                    'termName' => $relatedTerm->name,
                ];
            }
        }

        return $relations;
    }

    /**
     * Get relation type name
     */
    protected function getRelationType(int $typeId): string
    {
        $types = [
            \QubitTerm::TERM_RELATION_ASSOCIATIVE_ID ?? 165 => 'RT', // Related Term
        ];

        return $types[$typeId] ?? 'RT';
    }

    // =========================================================================
    // VOCABULARY IMPORT
    // =========================================================================

    /**
     * Import a taxonomy from JSON data
     *
     * @param array $data Taxonomy data from export
     * @param array $options Import options (conflictResolution, targetTaxonomyId)
     * @return VocabSyncResult
     */
    public function importTaxonomy(array $data, array $options = []): VocabSyncResult
    {
        \AhgCore\Core\AhgDb::init();

        $stats = [
            'added' => 0,
            'updated' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'errors' => [],
        ];

        $conflictResolution = $options['conflictResolution'] ?? 'skip';
        $targetTaxonomyId = $options['targetTaxonomyId'] ?? null;

        // Determine target taxonomy
        if (!$targetTaxonomyId) {
            // Try to find matching taxonomy by name
            $taxonomy = DB::table('taxonomy_i18n')
                ->where('name', $data['taxonomy']['name'])
                ->where('culture', 'en')
                ->first();

            if ($taxonomy) {
                $targetTaxonomyId = $taxonomy->id;
            } else {
                // Create new taxonomy
                $targetTaxonomyId = $this->createTaxonomy($data['taxonomy']);
            }
        }

        // Import terms recursively
        $this->importTermsRecursive(
            $data['terms'],
            $targetTaxonomyId,
            null, // parent term id
            $conflictResolution,
            $stats
        );

        return new VocabSyncResult(
            taxonomyId: $targetTaxonomyId,
            taxonomyName: $data['taxonomy']['name'],
            direction: 'import',
            stats: $stats
        );
    }

    /**
     * Import terms recursively
     */
    protected function importTermsRecursive(
        array $terms,
        int $taxonomyId,
        ?int $parentTermId,
        string $conflictResolution,
        array &$stats
    ): void {
        foreach ($terms as $termData) {
            try {
                $result = $this->importTerm($termData, $taxonomyId, $parentTermId, $conflictResolution);

                switch ($result['action']) {
                    case 'created':
                        $stats['added']++;
                        break;
                    case 'updated':
                        $stats['updated']++;
                        break;
                    case 'skipped':
                        $stats['skipped']++;
                        break;
                    case 'conflict':
                        $stats['conflicts']++;
                        break;
                }

                // Import children
                if (!empty($termData['children']) && $result['termId']) {
                    $this->importTermsRecursive(
                        $termData['children'],
                        $taxonomyId,
                        $result['termId'],
                        $conflictResolution,
                        $stats
                    );
                }

            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'term' => $termData['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Import a single term
     */
    protected function importTerm(
        array $termData,
        int $taxonomyId,
        ?int $parentTermId,
        string $conflictResolution
    ): array {
        $name = $termData['name'];

        // Check for existing term
        $existing = DB::table('term as t')
            ->join('term_i18n as ti', function ($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', $taxonomyId)
            ->where('ti.name', $name)
            ->first();

        if ($existing) {
            // Handle conflict
            switch ($conflictResolution) {
                case 'skip':
                    return ['action' => 'skipped', 'termId' => $existing->id];

                case 'prefer_local':
                    return ['action' => 'skipped', 'termId' => $existing->id];

                case 'prefer_remote':
                    $this->updateTerm($existing->id, $termData);
                    return ['action' => 'updated', 'termId' => $existing->id];

                case 'merge':
                    $this->mergeTerm($existing->id, $termData);
                    return ['action' => 'updated', 'termId' => $existing->id];

                default:
                    return ['action' => 'conflict', 'termId' => $existing->id];
            }
        }

        // Create new term
        $term = new \QubitTerm();
        $term->taxonomyId = $taxonomyId;
        $term->name = $name;

        if ($parentTermId) {
            $term->parentId = $parentTermId;
        }

        if (!empty($termData['code'])) {
            $term->code = $termData['code'];
        }

        $term->save();

        // Add translations
        if (!empty($termData['translations'])) {
            foreach ($termData['translations'] as $culture => $translation) {
                if ($culture !== 'en') {
                    $term->setName($translation, ['culture' => $culture]);
                }
            }
            $term->save();
        }

        return ['action' => 'created', 'termId' => $term->id];
    }

    /**
     * Update an existing term
     */
    protected function updateTerm(int $termId, array $termData): void
    {
        $term = \QubitTerm::getById($termId);
        if (!$term) {
            return;
        }

        if (!empty($termData['code'])) {
            $term->code = $termData['code'];
        }

        // Update translations
        if (!empty($termData['translations'])) {
            foreach ($termData['translations'] as $culture => $translation) {
                $term->setName($translation, ['culture' => $culture]);
            }
        }

        $term->save();
    }

    /**
     * Merge term data (add missing translations)
     */
    protected function mergeTerm(int $termId, array $termData): void
    {
        $term = \QubitTerm::getById($termId);
        if (!$term) {
            return;
        }

        // Only add translations that don't exist
        if (!empty($termData['translations'])) {
            foreach ($termData['translations'] as $culture => $translation) {
                $existing = DB::table('term_i18n')
                    ->where('id', $termId)
                    ->where('culture', $culture)
                    ->first();

                if (!$existing) {
                    $term->setName($translation, ['culture' => $culture]);
                }
            }
            $term->save();
        }
    }

    /**
     * Create a new taxonomy
     */
    protected function createTaxonomy(array $data): int
    {
        $taxonomy = new \QubitTaxonomy();
        $taxonomy->name = $data['name'];
        if (!empty($data['usage'])) {
            $taxonomy->usage = $data['usage'];
        }
        $taxonomy->save();

        return $taxonomy->id;
    }

    // =========================================================================
    // PEER SYNCHRONIZATION
    // =========================================================================

    /**
     * Sync vocabulary with a peer
     *
     * @param int $peerId Peer ID
     * @param int $taxonomyId Taxonomy ID
     * @param string $direction pull, push, or bidirectional
     * @return VocabSyncResult
     */
    public function syncWithPeer(int $peerId, int $taxonomyId, string $direction = 'pull'): VocabSyncResult
    {
        \AhgCore\Core\AhgDb::init();

        // Get sync configuration
        $config = DB::table('federation_vocab_sync')
            ->where('peer_id', $peerId)
            ->where('taxonomy_id', $taxonomyId)
            ->first();

        if (!$config) {
            throw new \Exception('Vocabulary sync not configured for this peer/taxonomy');
        }

        // Get peer info
        $peer = DB::table('federation_peer')
            ->where('id', $peerId)
            ->first();

        if (!$peer || !$peer->is_active) {
            throw new \Exception('Peer not found or inactive');
        }

        // Start sync session
        $sessionId = $this->startSyncSession($peerId, $taxonomyId, $direction);

        try {
            $result = null;

            if ($direction === 'pull' || $direction === 'bidirectional') {
                $result = $this->pullFromPeer($peer, $taxonomyId, $config->conflict_resolution);
            }

            if ($direction === 'push' || $direction === 'bidirectional') {
                $pushResult = $this->pushToPeer($peer, $taxonomyId);
                if ($result) {
                    // Merge stats
                    $result = $this->mergeResults($result, $pushResult);
                } else {
                    $result = $pushResult;
                }
            }

            // Complete session
            $this->completeSyncSession($sessionId, 'completed', $result->stats);

            // Update config
            DB::table('federation_vocab_sync')
                ->where('peer_id', $peerId)
                ->where('taxonomy_id', $taxonomyId)
                ->update([
                    'last_sync_at' => date('Y-m-d H:i:s'),
                    'last_sync_status' => 'success',
                    'last_sync_terms_added' => $result->stats['added'],
                    'last_sync_terms_updated' => $result->stats['updated'],
                    'last_sync_conflicts' => $result->stats['conflicts'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $result;

        } catch (\Exception $e) {
            $this->completeSyncSession($sessionId, 'failed', [], $e->getMessage());
            throw $e;
        }
    }

    /**
     * Pull vocabulary from a peer
     */
    protected function pullFromPeer(object $peer, int $taxonomyId, string $conflictResolution): VocabSyncResult
    {
        // Fetch vocabulary from peer's API
        $url = rtrim($peer->base_url, '/') . '/api/federation/vocab/' . $taxonomyId;

        $response = $this->client->fetchUrl($url, [
            'headers' => [
                'Accept: application/json',
                'X-API-Key: ' . ($peer->api_key ?? ''),
            ],
        ]);

        if (!$response || empty($response['taxonomy'])) {
            throw new \Exception('Failed to fetch vocabulary from peer');
        }

        return $this->importTaxonomy($response, [
            'conflictResolution' => $conflictResolution,
            'targetTaxonomyId' => $taxonomyId,
        ]);
    }

    /**
     * Push vocabulary to a peer
     */
    protected function pushToPeer(object $peer, int $taxonomyId): VocabSyncResult
    {
        // Export our vocabulary
        $data = $this->exportTaxonomy($taxonomyId);

        // Send to peer
        $url = rtrim($peer->base_url, '/') . '/api/federation/vocab/import';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: ' . ($peer->api_key ?? ''),
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Failed to push vocabulary to peer: HTTP $httpCode");
        }

        $result = json_decode($response, true);

        return new VocabSyncResult(
            taxonomyId: $taxonomyId,
            taxonomyName: $data['taxonomy']['name'],
            direction: 'push',
            stats: $result['stats'] ?? ['added' => 0, 'updated' => 0, 'skipped' => 0, 'conflicts' => 0, 'errors' => []]
        );
    }

    /**
     * Start a sync session
     */
    protected function startSyncSession(int $peerId, int $taxonomyId, string $direction): int
    {
        $userId = null;
        if (class_exists('sfContext') && \sfContext::hasInstance()) {
            $user = \sfContext::getInstance()->getUser();
            if ($user && $user->isAuthenticated()) {
                $userId = $user->getAttribute('user_id');
            }
        }

        return DB::table('federation_vocab_sync_log')->insertGetId([
            'peer_id' => $peerId,
            'taxonomy_id' => $taxonomyId,
            'sync_direction' => $direction,
            'started_at' => date('Y-m-d H:i:s'),
            'status' => 'running',
            'initiated_by' => $userId,
        ]);
    }

    /**
     * Complete a sync session
     */
    protected function completeSyncSession(int $sessionId, string $status, array $stats = [], ?string $error = null): void
    {
        DB::table('federation_vocab_sync_log')
            ->where('id', $sessionId)
            ->update([
                'completed_at' => date('Y-m-d H:i:s'),
                'status' => $status,
                'terms_added' => $stats['added'] ?? 0,
                'terms_updated' => $stats['updated'] ?? 0,
                'terms_skipped' => $stats['skipped'] ?? 0,
                'conflicts' => $stats['conflicts'] ?? 0,
                'error_message' => $error,
            ]);
    }

    /**
     * Merge two sync results
     */
    protected function mergeResults(VocabSyncResult $a, VocabSyncResult $b): VocabSyncResult
    {
        return new VocabSyncResult(
            taxonomyId: $a->taxonomyId,
            taxonomyName: $a->taxonomyName,
            direction: 'bidirectional',
            stats: [
                'added' => $a->stats['added'] + $b->stats['added'],
                'updated' => $a->stats['updated'] + $b->stats['updated'],
                'skipped' => $a->stats['skipped'] + $b->stats['skipped'],
                'conflicts' => $a->stats['conflicts'] + $b->stats['conflicts'],
                'errors' => array_merge($a->stats['errors'] ?? [], $b->stats['errors'] ?? []),
            ]
        );
    }

    // =========================================================================
    // CHANGE TRACKING
    // =========================================================================

    /**
     * Record a vocabulary change for propagation
     */
    public function recordChange(
        int $taxonomyId,
        ?int $termId,
        string $changeType,
        ?string $oldValue,
        ?string $newValue,
        ?int $userId = null
    ): int {
        \AhgCore\Core\AhgDb::init();

        return DB::table('federation_vocab_change')->insertGetId([
            'taxonomy_id' => $taxonomyId,
            'term_id' => $termId,
            'change_type' => $changeType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get unpropagated changes for a taxonomy
     */
    public function getUnpropagatedChanges(int $taxonomyId, int $peerId): Collection
    {
        \AhgCore\Core\AhgDb::init();

        return DB::table('federation_vocab_change')
            ->where('taxonomy_id', $taxonomyId)
            ->where(function ($q) use ($peerId) {
                $q->whereNull('propagated_to_peers')
                    ->orWhereRaw("NOT JSON_CONTAINS(propagated_to_peers, ?)", [json_encode($peerId)]);
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Mark changes as propagated to a peer
     */
    public function markPropagated(array $changeIds, int $peerId): void
    {
        \AhgCore\Core\AhgDb::init();

        foreach ($changeIds as $changeId) {
            $change = DB::table('federation_vocab_change')
                ->where('id', $changeId)
                ->first();

            $propagatedTo = $change->propagated_to_peers
                ? json_decode($change->propagated_to_peers, true)
                : [];

            if (!in_array($peerId, $propagatedTo)) {
                $propagatedTo[] = $peerId;
            }

            DB::table('federation_vocab_change')
                ->where('id', $changeId)
                ->update(['propagated_to_peers' => json_encode($propagatedTo)]);
        }
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Configure vocabulary sync for a peer
     */
    public function configureSyncForPeer(int $peerId, int $taxonomyId, array $settings): bool
    {
        \AhgCore\Core\AhgDb::init();

        $data = [
            'peer_id' => $peerId,
            'taxonomy_id' => $taxonomyId,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $allowedFields = [
            'sync_direction', 'sync_enabled', 'conflict_resolution', 'sync_interval_hours',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $settings)) {
                $data[$field] = $settings[$field];
            }
        }

        return DB::table('federation_vocab_sync')
            ->updateOrInsert(
                ['peer_id' => $peerId, 'taxonomy_id' => $taxonomyId],
                $data
            );
    }

    /**
     * Get sync configuration for a peer
     */
    public function getSyncConfig(int $peerId, ?int $taxonomyId = null): Collection
    {
        \AhgCore\Core\AhgDb::init();

        $query = DB::table('federation_vocab_sync as vs')
            ->join('taxonomy as t', 'vs.taxonomy_id', '=', 't.id')
            ->leftJoin('taxonomy_i18n as ti', function ($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->where('vs.peer_id', $peerId)
            ->select('vs.*', 'ti.name as taxonomy_name');

        if ($taxonomyId) {
            $query->where('vs.taxonomy_id', $taxonomyId);
        }

        return $query->get();
    }

    /**
     * Get available taxonomies for sync
     */
    public function getAvailableTaxonomies(): Collection
    {
        \AhgCore\Core\AhgDb::init();

        return DB::table('taxonomy as t')
            ->leftJoin('taxonomy_i18n as ti', function ($j) {
                $j->on('t.id', '=', 'ti.id')->where('ti.culture', '=', 'en');
            })
            ->select('t.id', 'ti.name', DB::raw('(SELECT COUNT(*) FROM term WHERE taxonomy_id = t.id) as term_count'))
            ->orderBy('ti.name')
            ->get();
    }

    // =========================================================================
    // DROPDOWN HELPERS - Uses AhgTaxonomyService
    // =========================================================================

    /**
     * Get dropdown choices for sync direction
     */
    public static function getSyncDirectionChoices(bool $includeEmpty = true): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationSyncDirections($includeEmpty);
    }

    /**
     * Get dropdown choices for conflict resolution
     */
    public static function getConflictResolutionChoices(bool $includeEmpty = true): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationConflictResolutions($includeEmpty);
    }

    /**
     * Get dropdown choices for session status
     */
    public static function getSessionStatusChoices(bool $includeEmpty = true): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationSessionStatuses($includeEmpty);
    }

    /**
     * Get session status with display attributes (label, color)
     */
    public static function getSessionStatusesWithColors(): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationSessionStatusesWithColors();
    }

    /**
     * Get dropdown choices for mapping status
     */
    public static function getMappingStatusChoices(bool $includeEmpty = true): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationMappingStatuses($includeEmpty);
    }

    /**
     * Get mapping status with display attributes (label, color)
     */
    public static function getMappingStatusesWithColors(): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationMappingStatusesWithColors();
    }

    /**
     * Get dropdown choices for change types
     */
    public static function getChangeTypeChoices(bool $includeEmpty = true): array
    {
        $service = new AhgTaxonomyService();
        return $service->getFederationChangeTypes($includeEmpty);
    }
}

/**
 * Result of a vocabulary sync operation
 */
class VocabSyncResult
{
    public function __construct(
        public readonly int $taxonomyId,
        public readonly string $taxonomyName,
        public readonly string $direction,
        public readonly array $stats
    ) {}

    public function isSuccessful(): bool
    {
        return empty($this->stats['errors']);
    }

    public function getSummary(): string
    {
        return sprintf(
            '%s sync of "%s": %d added, %d updated, %d skipped, %d conflicts',
            ucfirst($this->direction),
            $this->taxonomyName,
            $this->stats['added'],
            $this->stats['updated'],
            $this->stats['skipped'],
            $this->stats['conflicts']
        );
    }

    public function toArray(): array
    {
        return [
            'taxonomyId' => $this->taxonomyId,
            'taxonomyName' => $this->taxonomyName,
            'direction' => $this->direction,
            'stats' => $this->stats,
            'successful' => $this->isSuccessful(),
            'summary' => $this->getSummary(),
        ];
    }
}
