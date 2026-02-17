<?php

namespace ahgDedupePlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * DedupeService - Core service for duplicate detection and management.
 *
 * Features:
 * - Multiple detection algorithms (Levenshtein, Jaro-Winkler, checksum)
 * - Real-time checking during data entry
 * - Batch scanning for existing records
 * - Record merging with audit trail
 */
class DedupeService
{
    // =========================================
    // DETECTION
    // =========================================

    /**
     * Check for duplicates of a record.
     *
     * @param array    $recordData   Record data to check
     * @param int|null $excludeId    Exclude this record ID (when editing)
     * @param int|null $repositoryId Repository ID for context
     *
     * @return array Potential duplicates with scores
     */
    public function checkForDuplicates(array $recordData, ?int $excludeId = null, ?int $repositoryId = null): array
    {
        $rules = $this->getActiveRules($repositoryId);
        $duplicates = [];

        foreach ($rules as $rule) {
            $matches = $this->runRule($rule, $recordData, $excludeId);

            foreach ($matches as $match) {
                $recordId = $match['record_id'];

                if (!isset($duplicates[$recordId])) {
                    $duplicates[$recordId] = [
                        'record_id' => $recordId,
                        'scores' => [],
                        'methods' => [],
                        'is_blocking' => false,
                    ];
                }

                $duplicates[$recordId]['scores'][] = $match['score'];
                $duplicates[$recordId]['methods'][] = $rule->rule_type;

                if ($rule->is_blocking && $match['score'] >= $rule->threshold) {
                    $duplicates[$recordId]['is_blocking'] = true;
                }
            }
        }

        // Calculate combined scores
        foreach ($duplicates as &$dup) {
            $dup['combined_score'] = array_sum($dup['scores']) / count($dup['scores']);
            $dup['max_score'] = max($dup['scores']);
        }

        // Sort by max score descending
        usort($duplicates, function ($a, $b) {
            return $b['max_score'] <=> $a['max_score'];
        });

        // Enrich with record details
        return $this->enrichDuplicateResults($duplicates);
    }

    /**
     * Check duplicates in real-time as user types.
     *
     * @param string   $title        Title being entered
     * @param int|null $repositoryId Repository context
     * @param int|null $excludeId    Exclude ID
     *
     * @return array Quick matches
     */
    public function realtimeCheck(string $title, ?int $repositoryId = null, ?int $excludeId = null): array
    {
        if (strlen($title) < 5) {
            return [];
        }

        // Quick title similarity check
        $normalizedTitle = $this->normalizeText($title);

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', '!=', 1);

        if ($excludeId) {
            $query->where('io.id', '!=', $excludeId);
        }

        if ($repositoryId) {
            $query->where('io.repository_id', $repositoryId);
        }

        $candidates = $query->select(['io.id', 'ioi.title', 'slug.slug'])
            ->limit(1000)
            ->get();

        $matches = [];
        foreach ($candidates as $candidate) {
            if (empty($candidate->title)) {
                continue;
            }

            $candidateNormalized = $this->normalizeText($candidate->title);
            $score = $this->calculateLevenshteinSimilarity($normalizedTitle, $candidateNormalized);

            if ($score >= 0.75) {
                $matches[] = [
                    'record_id' => $candidate->id,
                    'title' => $candidate->title,
                    'slug' => $candidate->slug,
                    'score' => round($score, 4),
                ];
            }
        }

        usort($matches, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($matches, 0, 5);
    }

    /**
     * Run a specific detection rule.
     *
     * @param object $rule       Rule definition
     * @param array  $recordData Record to check
     * @param int|null $excludeId Exclude ID
     *
     * @return array Matches
     */
    protected function runRule(object $rule, array $recordData, ?int $excludeId = null): array
    {
        $config = json_decode($rule->config_json ?: '{}', true);

        switch ($rule->rule_type) {
            case 'title_similarity':
                return $this->checkTitleSimilarity($recordData, $rule->threshold, $config, $excludeId);

            case 'identifier_exact':
                return $this->checkIdentifierExact($recordData, $config, $excludeId);

            case 'identifier_fuzzy':
                return $this->checkIdentifierFuzzy($recordData, $rule->threshold, $config, $excludeId);

            case 'date_creator':
                return $this->checkDateCreator($recordData, $rule->threshold, $config, $excludeId);

            case 'checksum':
                return $this->checkFileChecksum($recordData, $config, $excludeId);

            case 'combined':
                return $this->checkCombined($recordData, $rule->threshold, $config, $excludeId);

            default:
                return [];
        }
    }

    /**
     * Check title similarity.
     *
     * @param array    $recordData Record data
     * @param float    $threshold  Minimum score
     * @param array    $config     Rule config
     * @param int|null $excludeId  Exclude ID
     *
     * @return array Matches
     */
    protected function checkTitleSimilarity(array $recordData, float $threshold, array $config, ?int $excludeId = null): array
    {
        $title = $recordData['title'] ?? '';
        if (empty($title) || strlen($title) < ($config['min_length'] ?? 5)) {
            return [];
        }

        $normalizedTitle = $this->normalizeText($title, $config);

        $candidates = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', '!=', 1)
            ->whereNotNull('ioi.title');

        if ($excludeId) {
            $candidates->where('io.id', '!=', $excludeId);
        }

        $records = $candidates->select(['io.id', 'ioi.title'])->get();
        $matches = [];

        foreach ($records as $record) {
            $candidateNormalized = $this->normalizeText($record->title, $config);

            $algorithm = $config['algorithm'] ?? 'levenshtein';
            $score = 0;

            switch ($algorithm) {
                case 'levenshtein':
                    $score = $this->calculateLevenshteinSimilarity($normalizedTitle, $candidateNormalized);
                    break;
                case 'jaro_winkler':
                    $score = $this->calculateJaroWinkler($normalizedTitle, $candidateNormalized);
                    break;
                case 'soundex':
                    $score = soundex($normalizedTitle) === soundex($candidateNormalized) ? 1.0 : 0.0;
                    break;
            }

            if ($score >= $threshold) {
                $matches[] = [
                    'record_id' => $record->id,
                    'score' => $score,
                    'details' => ['matched_title' => $record->title],
                ];
            }
        }

        return $matches;
    }

    /**
     * Check exact identifier match.
     *
     * @param array    $recordData Record data
     * @param array    $config     Rule config
     * @param int|null $excludeId  Exclude ID
     *
     * @return array Matches
     */
    protected function checkIdentifierExact(array $recordData, array $config, ?int $excludeId = null): array
    {
        $identifier = $recordData['identifier'] ?? '';
        if (empty($identifier)) {
            return [];
        }

        $query = DB::table('information_object as io')
            ->where('io.identifier', $identifier)
            ->where('io.id', '!=', 1);

        if ($excludeId) {
            $query->where('io.id', '!=', $excludeId);
        }

        $matches = [];
        foreach ($query->pluck('io.id') as $id) {
            $matches[] = [
                'record_id' => $id,
                'score' => 1.0,
                'details' => ['matched_identifier' => $identifier],
            ];
        }

        return $matches;
    }

    /**
     * Check fuzzy identifier match.
     *
     * @param array    $recordData Record data
     * @param float    $threshold  Minimum score
     * @param array    $config     Rule config
     * @param int|null $excludeId  Exclude ID
     *
     * @return array Matches
     */
    protected function checkIdentifierFuzzy(array $recordData, float $threshold, array $config, ?int $excludeId = null): array
    {
        $identifier = $recordData['identifier'] ?? '';
        if (empty($identifier)) {
            return [];
        }

        $normalizedId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $identifier));

        $query = DB::table('information_object as io')
            ->whereNotNull('io.identifier')
            ->where('io.id', '!=', 1);

        if ($excludeId) {
            $query->where('io.id', '!=', $excludeId);
        }

        $records = $query->select(['io.id', 'io.identifier'])->get();
        $matches = [];

        foreach ($records as $record) {
            $candidateNormalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $record->identifier));
            $score = $this->calculateJaroWinkler($normalizedId, $candidateNormalized);

            if ($score >= $threshold) {
                $matches[] = [
                    'record_id' => $record->id,
                    'score' => $score,
                    'details' => ['matched_identifier' => $record->identifier],
                ];
            }
        }

        return $matches;
    }

    /**
     * Check date range + creator combination.
     *
     * @param array    $recordData Record data
     * @param float    $threshold  Minimum score
     * @param array    $config     Rule config
     * @param int|null $excludeId  Exclude ID
     *
     * @return array Matches
     */
    protected function checkDateCreator(array $recordData, float $threshold, array $config, ?int $excludeId = null): array
    {
        $creator = $recordData['creator'] ?? '';
        $startDate = $recordData['start_date'] ?? '';
        $endDate = $recordData['end_date'] ?? '';

        if (empty($creator) || (empty($startDate) && empty($endDate))) {
            return [];
        }

        // Find records with overlapping date ranges
        $query = DB::table('information_object as io')
            ->leftJoin('event', function ($join) {
                $join->on('io.id', '=', 'event.object_id')
                    ->where('event.type_id', 111); // Creation event
            })
            ->leftJoin('event_i18n as ei', function ($join) {
                $join->on('event.id', '=', 'ei.id')
                    ->where('ei.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('event.actor_id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('io.id', '!=', 1);

        if ($excludeId) {
            $query->where('io.id', '!=', $excludeId);
        }

        $records = $query->select([
            'io.id',
            'event.start_date as event_start',
            'event.end_date as event_end',
            'ai.authorized_form_of_name as creator_name',
        ])->get();

        $matches = [];
        $normalizedCreator = $this->normalizeText($creator);

        foreach ($records as $record) {
            if (empty($record->creator_name)) {
                continue;
            }

            // Check date overlap
            $dateScore = 0;
            if ($this->datesOverlap($startDate, $endDate, $record->event_start, $record->event_end)) {
                $dateScore = 0.5;
            }

            // Check creator similarity
            $creatorScore = $this->calculateLevenshteinSimilarity(
                $normalizedCreator,
                $this->normalizeText($record->creator_name)
            );

            $combinedScore = ($dateScore + $creatorScore) / 2;

            if ($combinedScore >= $threshold) {
                $matches[] = [
                    'record_id' => $record->id,
                    'score' => $combinedScore,
                    'details' => [
                        'matched_creator' => $record->creator_name,
                        'date_overlap' => $dateScore > 0,
                    ],
                ];
            }
        }

        return $matches;
    }

    /**
     * Check file checksum for exact duplicates.
     *
     * @param array    $recordData Record data
     * @param array    $config     Rule config
     * @param int|null $excludeId  Exclude ID
     *
     * @return array Matches
     */
    protected function checkFileChecksum(array $recordData, array $config, ?int $excludeId = null): array
    {
        $checksum = $recordData['checksum_sha256'] ?? $recordData['checksum_md5'] ?? '';
        if (empty($checksum)) {
            return [];
        }

        $field = strlen($checksum) === 64 ? 'checksum_sha256' : 'checksum_md5';

        $query = DB::table('ahg_file_checksum as fc')
            ->where("fc.{$field}", $checksum);

        if ($excludeId) {
            $query->where('fc.information_object_id', '!=', $excludeId);
        }

        $matches = [];
        foreach ($query->get() as $record) {
            $matches[] = [
                'record_id' => $record->information_object_id,
                'score' => 1.0,
                'details' => [
                    'digital_object_id' => $record->digital_object_id,
                    'file_name' => $record->file_name,
                ],
            ];
        }

        return $matches;
    }

    /**
     * Check combined multi-factor analysis.
     *
     * @param array    $recordData Record data
     * @param float    $threshold  Minimum score
     * @param array    $config     Rule config
     * @param int|null $excludeId  Exclude ID
     *
     * @return array Matches
     */
    protected function checkCombined(array $recordData, float $threshold, array $config, ?int $excludeId = null): array
    {
        $weights = $config['weights'] ?? [
            'title' => 0.4,
            'identifier' => 0.3,
            'date' => 0.15,
            'creator' => 0.15,
        ];

        // Run individual checks
        $titleMatches = $this->checkTitleSimilarity($recordData, 0.5, [], $excludeId);
        $idMatches = $this->checkIdentifierFuzzy($recordData, 0.7, [], $excludeId);

        // Combine scores
        $combined = [];

        foreach ($titleMatches as $match) {
            $id = $match['record_id'];
            if (!isset($combined[$id])) {
                $combined[$id] = ['title' => 0, 'identifier' => 0, 'date' => 0, 'creator' => 0];
            }
            $combined[$id]['title'] = max($combined[$id]['title'], $match['score']);
        }

        foreach ($idMatches as $match) {
            $id = $match['record_id'];
            if (!isset($combined[$id])) {
                $combined[$id] = ['title' => 0, 'identifier' => 0, 'date' => 0, 'creator' => 0];
            }
            $combined[$id]['identifier'] = max($combined[$id]['identifier'], $match['score']);
        }

        // Calculate weighted scores
        $matches = [];
        foreach ($combined as $recordId => $scores) {
            $weightedScore = 0;
            foreach ($weights as $factor => $weight) {
                $weightedScore += ($scores[$factor] ?? 0) * $weight;
            }

            if ($weightedScore >= $threshold) {
                $matches[] = [
                    'record_id' => $recordId,
                    'score' => $weightedScore,
                    'details' => $scores,
                ];
            }
        }

        return $matches;
    }

    // =========================================
    // BATCH SCANNING
    // =========================================

    /**
     * Start a batch scan for duplicates.
     *
     * @param int|null $repositoryId Repository to scan
     *
     * @return int Scan job ID
     */
    public function startScan(?int $repositoryId = null): int
    {
        // Count records to scan
        $query = DB::table('information_object')
            ->where('id', '!=', 1);

        if ($repositoryId) {
            $query->where('repository_id', $repositoryId);
        }

        $totalRecords = $query->count();

        return DB::table('ahg_dedupe_scan')->insertGetId([
            'repository_id' => $repositoryId,
            'status' => 'pending',
            'total_records' => $totalRecords,
            'started_by' => $this->getCurrentUserId(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Run scan job (for CLI).
     *
     * @param int $scanId Scan job ID
     * @param callable|null $progress Progress callback
     *
     * @return array Results
     */
    public function runScan(int $scanId, ?callable $progress = null): array
    {
        $scan = DB::table('ahg_dedupe_scan')->where('id', $scanId)->first();
        if (!$scan) {
            throw new \Exception("Scan job not found: {$scanId}");
        }

        // Mark as running
        DB::table('ahg_dedupe_scan')
            ->where('id', $scanId)
            ->update([
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
            ]);

        $results = ['processed' => 0, 'duplicates_found' => 0, 'errors' => []];

        try {
            $query = DB::table('information_object as io')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('io.id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->where('io.id', '!=', 1);

            if ($scan->repository_id) {
                $query->where('io.repository_id', $scan->repository_id);
            }

            $records = $query->select(['io.id', 'io.identifier', 'ioi.title'])->get();

            foreach ($records as $record) {
                $duplicates = $this->checkForDuplicates([
                    'title' => $record->title,
                    'identifier' => $record->identifier,
                ], $record->id);

                foreach ($duplicates as $dup) {
                    // Only store if score is high enough and not already recorded
                    if ($dup['max_score'] >= 0.75) {
                        $this->recordDuplicate($record->id, $dup['record_id'], $dup['max_score'], $dup['methods'][0] ?? 'combined', $dup);
                        $results['duplicates_found']++;
                    }
                }

                $results['processed']++;

                // Update progress
                if ($results['processed'] % 100 === 0) {
                    DB::table('ahg_dedupe_scan')
                        ->where('id', $scanId)
                        ->update(['processed_records' => $results['processed']]);

                    if ($progress) {
                        $progress($results['processed'], $scan->total_records);
                    }
                }
            }

            // Mark complete
            DB::table('ahg_dedupe_scan')
                ->where('id', $scanId)
                ->update([
                    'status' => 'completed',
                    'processed_records' => $results['processed'],
                    'duplicates_found' => $results['duplicates_found'],
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);
        } catch (\Exception $e) {
            DB::table('ahg_dedupe_scan')
                ->where('id', $scanId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            throw $e;
        }

        return $results;
    }

    // =========================================
    // RECORD MANAGEMENT
    // =========================================

    /**
     * Record a detected duplicate pair.
     *
     * @param int    $recordA  First record ID
     * @param int    $recordB  Second record ID
     * @param float  $score    Similarity score
     * @param string $method   Detection method
     * @param array  $details  Additional details
     *
     * @return int|null Detection ID
     */
    public function recordDuplicate(int $recordA, int $recordB, float $score, string $method, array $details = []): ?int
    {
        // Normalize order (smaller ID first)
        if ($recordA > $recordB) {
            [$recordA, $recordB] = [$recordB, $recordA];
        }

        // Check if already recorded
        $existing = DB::table('ahg_duplicate_detection')
            ->where('record_a_id', $recordA)
            ->where('record_b_id', $recordB)
            ->first();

        if ($existing) {
            // Update if higher score
            if ($score > $existing->similarity_score) {
                DB::table('ahg_duplicate_detection')
                    ->where('id', $existing->id)
                    ->update([
                        'similarity_score' => $score,
                        'detection_method' => $method,
                        'detection_details' => json_encode($details),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            }

            return $existing->id;
        }

        return DB::table('ahg_duplicate_detection')->insertGetId([
            'record_a_id' => $recordA,
            'record_b_id' => $recordB,
            'similarity_score' => $score,
            'detection_method' => $method,
            'detection_details' => json_encode($details),
            'status' => 'pending',
            'detected_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Dismiss a duplicate as false positive.
     *
     * @param int         $detectionId Detection ID
     * @param string|null $notes       Review notes
     *
     * @return bool
     */
    public function dismissDuplicate(int $detectionId, ?string $notes = null): bool
    {
        return DB::table('ahg_duplicate_detection')
            ->where('id', $detectionId)
            ->update([
                'status' => 'dismissed',
                'reviewed_by' => $this->getCurrentUserId(),
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
            ]) > 0;
    }

    /**
     * Merge two records.
     *
     * @param int   $primaryId   Record to keep
     * @param int   $mergedId    Record to merge
     * @param array $fieldChoices Which fields to take from merged record
     * @param string|null $notes Notes
     *
     * @return bool
     */
    public function mergeRecords(int $primaryId, int $mergedId, array $fieldChoices = [], ?string $notes = null): bool
    {
        // Get slugs from merged record
        $mergedSlugs = DB::table('slug')
            ->where('object_id', $mergedId)
            ->pluck('slug')
            ->toArray();

        // Get digital objects from merged record
        $mergedDigitalObjects = DB::table('digital_object')
            ->where('object_id', $mergedId)
            ->pluck('id')
            ->toArray();

        // Move digital objects to primary
        if (!empty($mergedDigitalObjects)) {
            DB::table('digital_object')
                ->whereIn('id', $mergedDigitalObjects)
                ->update(['object_id' => $primaryId]);
        }

        // Update detection record if exists
        $detection = DB::table('ahg_duplicate_detection')
            ->where(function ($q) use ($primaryId, $mergedId) {
                $q->where(function ($q2) use ($primaryId, $mergedId) {
                    $q2->where('record_a_id', $primaryId)->where('record_b_id', $mergedId);
                })->orWhere(function ($q2) use ($primaryId, $mergedId) {
                    $q2->where('record_a_id', $mergedId)->where('record_b_id', $primaryId);
                });
            })
            ->first();

        if ($detection) {
            DB::table('ahg_duplicate_detection')
                ->where('id', $detection->id)
                ->update(['status' => 'merged']);
        }

        // Log the merge
        DB::table('ahg_merge_log')->insert([
            'primary_id' => $primaryId,
            'merged_id' => $mergedId,
            'detection_id' => $detection->id ?? null,
            'field_choices_json' => json_encode($fieldChoices),
            'slugs_redirected' => json_encode($mergedSlugs),
            'digital_objects_moved' => json_encode($mergedDigitalObjects),
            'merged_by' => $this->getCurrentUserId(),
            'merged_at' => date('Y-m-d H:i:s'),
            'notes' => $notes,
        ]);

        // Soft delete the merged record (or delete completely based on config)
        // For now, we'll just update slug to redirect
        foreach ($mergedSlugs as $slug) {
            // The redirect would be handled by the routing system
            // Here we just record it
        }

        return true;
    }

    // =========================================
    // RULES
    // =========================================

    /**
     * Get active rules.
     *
     * @param int|null $repositoryId Repository ID
     *
     * @return Collection
     */
    public function getActiveRules(?int $repositoryId = null): Collection
    {
        return DB::table('ahg_duplicate_rule')
            ->where('is_enabled', 1)
            ->where(function ($q) use ($repositoryId) {
                $q->whereNull('repository_id');
                if ($repositoryId) {
                    $q->orWhere('repository_id', $repositoryId);
                }
            })
            ->orderByDesc('priority')
            ->get();
    }

    /**
     * Get all rules.
     *
     * @return Collection
     */
    public function getRules(): Collection
    {
        return DB::table('ahg_duplicate_rule')
            ->orderByDesc('priority')
            ->get();
    }

    // =========================================
    // STATISTICS
    // =========================================

    /**
     * Get duplicate statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = DB::table('ahg_duplicate_detection')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed,
                SUM(CASE WHEN status = 'merged' THEN 1 ELSE 0 END) as merged,
                AVG(similarity_score) as avg_score
            ")
            ->first();

        $byMethod = DB::table('ahg_duplicate_detection')
            ->where('status', 'pending')
            ->selectRaw('detection_method, COUNT(*) as count')
            ->groupBy('detection_method')
            ->pluck('count', 'detection_method')
            ->toArray();

        $recentMerges = DB::table('ahg_merge_log')
            ->where('merged_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->count();

        return [
            'total' => (int) ($stats->total ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'confirmed' => (int) ($stats->confirmed ?? 0),
            'dismissed' => (int) ($stats->dismissed ?? 0),
            'merged' => (int) ($stats->merged ?? 0),
            'avg_score' => round($stats->avg_score ?? 0, 4),
            'by_method' => $byMethod,
            'recent_merges' => $recentMerges,
        ];
    }

    // =========================================
    // SIMILARITY ALGORITHMS
    // =========================================

    /**
     * Calculate Levenshtein-based similarity.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     *
     * @return float Score 0-1
     */
    protected function calculateLevenshteinSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $distance = levenshtein($str1, $str2);
        $maxLen = max($len1, $len2);

        return 1 - ($distance / $maxLen);
    }

    /**
     * Calculate Jaro-Winkler similarity.
     *
     * @param string $str1 First string
     * @param string $str2 Second string
     *
     * @return float Score 0-1
     */
    protected function calculateJaroWinkler(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);

        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $matchDistance = (int) floor(max($len1, $len2) / 2) - 1;

        $str1Matches = array_fill(0, $len1, false);
        $str2Matches = array_fill(0, $len2, false);

        $matches = 0;
        $transpositions = 0;

        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end = min($i + $matchDistance + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if ($str2Matches[$j] || $str1[$i] !== $str2[$j]) {
                    continue;
                }
                $str1Matches[$i] = true;
                $str2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (!$str1Matches[$i]) {
                continue;
            }
            while (!$str2Matches[$k]) {
                $k++;
            }
            if ($str1[$i] !== $str2[$k]) {
                $transpositions++;
            }
            $k++;
        }

        $jaro = (($matches / $len1) + ($matches / $len2) + (($matches - $transpositions / 2) / $matches)) / 3;

        // Winkler modification
        $prefix = 0;
        for ($i = 0; $i < min(4, min($len1, $len2)); $i++) {
            if ($str1[$i] === $str2[$i]) {
                $prefix++;
            } else {
                break;
            }
        }

        return $jaro + ($prefix * 0.1 * (1 - $jaro));
    }

    /**
     * Normalize text for comparison.
     *
     * @param string $text   Text to normalize
     * @param array  $config Normalization options
     *
     * @return string
     */
    protected function normalizeText(string $text, array $config = []): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Check if two date ranges overlap.
     *
     * @param string|null $start1 First range start
     * @param string|null $end1   First range end
     * @param string|null $start2 Second range start
     * @param string|null $end2   Second range end
     *
     * @return bool
     */
    protected function datesOverlap(?string $start1, ?string $end1, ?string $start2, ?string $end2): bool
    {
        if (empty($start1) && empty($end1)) {
            return false;
        }
        if (empty($start2) && empty($end2)) {
            return false;
        }

        $s1 = strtotime($start1 ?: '0001-01-01');
        $e1 = strtotime($end1 ?: '9999-12-31');
        $s2 = strtotime($start2 ?: '0001-01-01');
        $e2 = strtotime($end2 ?: '9999-12-31');

        return $s1 <= $e2 && $e1 >= $s2;
    }

    /**
     * Enrich duplicate results with record details.
     *
     * @param array $duplicates Duplicate matches
     *
     * @return array
     */
    protected function enrichDuplicateResults(array $duplicates): array
    {
        if (empty($duplicates)) {
            return [];
        }

        $ids = array_column($duplicates, 'record_id');

        $records = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->whereIn('io.id', $ids)
            ->select(['io.id', 'io.identifier', 'ioi.title', 'slug.slug'])
            ->get()
            ->keyBy('id');

        foreach ($duplicates as &$dup) {
            $record = $records->get($dup['record_id']);
            if ($record) {
                $dup['title'] = $record->title;
                $dup['identifier'] = $record->identifier;
                $dup['slug'] = $record->slug;
            }
        }

        return $duplicates;
    }

    /**
     * Get current user ID.
     *
     * @return int|null
     */
    protected function getCurrentUserId(): ?int
    {
        if (class_exists('sfContext') && \sfContext::hasInstance()) {
            $user = \sfContext::getInstance()->getUser();
            if ($user && method_exists($user, 'getAttribute')) {
                return $user->getAttribute('user_id');
            }
        }

        return null;
    }
}
