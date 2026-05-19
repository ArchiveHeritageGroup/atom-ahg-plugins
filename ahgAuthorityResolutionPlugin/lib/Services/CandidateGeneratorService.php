<?php

/**
 * CandidateGeneratorService - service for AtoM Heratio
 *
 * Orchestrates candidate generation for a single ahg_mention. Loads the
 * mention's entity_value + entity_type, dispatches to every configured
 * adapter whose supports($entityType) is true, scores each returned row
 * by name similarity, sorts desc, trims to top-N, and writes the result
 * into ahg_mention_candidate (DELETE + INSERT inside one transaction so
 * a mention's candidate list is always rank-coherent).
 *
 * Scoring algorithm is shared verbatim with the Laravel-side
 * Heratio\Packages\AhgAuthorityResolution\Services\CandidateGeneratorService
 * so the same mention/value pair ranks identically on either platform.
 *
 *   1. Lowercase + trim both query and candidate.
 *   2. similar_text() percent / 100.
 *   3. +0.05 substring bonus, capped at 1.0.
 *   4. Exact-match = 1.0.
 *   5. round(4).
 *
 * Tie-break is (composite_score desc, display_name asc) for deterministic
 * ordering when candidates share a score.
 *
 * Top-N is read from ahg_settings.authority_resolution.candidate_top_n,
 * with a 5 fallback. composite_score for Task 3 is just the name
 * similarity; Task 4 will overwrite it with the multi-dimensional
 * weighted aggregate.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution;

use AtomFramework\Services\AuthorityResolution\Adapters\CandidateAdapterInterface;
use Illuminate\Database\Capsule\Manager as DB;

class CandidateGeneratorService
{
    private const DEFAULT_TOP_N = 5;

    private const PER_ADAPTER_LIMIT = 50;

    /** @var CandidateAdapterInterface[] */
    private $adapters;

    /**
     * @param CandidateAdapterInterface[] $adapters
     */
    public function __construct(array $adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * Generate candidates for a mention and persist the top-N to
     * ahg_mention_candidate. Returns inserted candidate IDs in rank order
     * (rank 1 first).
     *
     * @return int[]
     */
    public function generate(int $mentionId, ?int $topN = null): array
    {
        $mention = DB::table('ahg_mention as m')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('m.id', $mentionId)
            ->first(['m.id', 'm.entity_type', 'n.entity_value']);

        if (!$mention) {
            return [];
        }

        $topN = $topN !== null ? max(1, $topN) : $this->resolveTopN();
        $entityType = (string) $mention->entity_type;
        $entityValue = trim((string) $mention->entity_value);

        if ($entityValue === '') {
            return $this->persist($mentionId, []);
        }

        $blended = $this->gather($entityValue, $entityType);
        $scored = $this->score($entityValue, $blended);
        $top = $this->trim($scored, $topN);

        return $this->persist($mentionId, $top);
    }

    /**
     * Run every supporting adapter and concatenate. Duplicate suppression
     * (same authority_id from the same source) keeps each authority record
     * at one row even if name LIKE returns duplicates for any reason.
     *
     * @return array<int, array{source:string, authority_id:?int, fuseki_uri:?string, display_name:string}>
     */
    private function gather(string $query, string $entityType): array
    {
        $rows = [];
        $seen = [];
        foreach ($this->adapters as $adapter) {
            if (!$adapter->supports($entityType)) {
                continue;
            }
            $candidates = $adapter->search($query, $entityType, self::PER_ADAPTER_LIMIT);
            foreach ($candidates as $c) {
                $key = ($c['source'] ?? '?') . '|' .
                    (string) ($c['authority_id'] ?? '') . '|' .
                    (string) ($c['fuseki_uri'] ?? '') . '|' .
                    (string) ($c['display_name'] ?? '');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $rows[] = $c;
            }
        }
        return $rows;
    }

    /**
     * Apply the shared name-similarity algorithm to every candidate.
     * Returns the rows enriched with a 'score' float.
     */
    private function score(string $mentionValue, array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $row['score'] = $this->nameSimilarity($mentionValue, (string) $row['display_name']);
            $out[] = $row;
        }
        return $out;
    }

    /**
     * SHARED scoring algorithm. Keep byte-for-byte aligned with the
     * Laravel-side Heratio implementation.
     */
    public function nameSimilarity(string $mentionValue, string $candidateDisplayName): float
    {
        $q = trim(mb_strtolower($mentionValue, 'UTF-8'));
        $c = trim(mb_strtolower($candidateDisplayName, 'UTF-8'));
        if ($q === '' || $c === '') {
            return 0.0;
        }
        similar_text($q, $c, $percent);
        $score = $percent / 100.0;
        if (strpos($c, $q) !== false) {
            $score = min(1.0, $score + 0.05);
        }
        if ($q === $c) {
            $score = 1.0;
        }
        return round($score, 4);
    }

    /**
     * Sort by score desc, display_name asc, then trim to top-N.
     */
    private function trim(array $scored, int $topN): array
    {
        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp((string) $a['display_name'], (string) $b['display_name']);
            }
            return ($a['score'] < $b['score']) ? 1 : -1;
        });
        return array_slice($scored, 0, $topN);
    }

    /**
     * Single-transaction wipe + reinsert. Returns inserted candidate IDs
     * in rank order.
     *
     * @return int[]
     */
    private function persist(int $mentionId, array $top): array
    {
        return DB::transaction(function () use ($mentionId, $top) {
            DB::table('ahg_mention_candidate')->where('mention_id', $mentionId)->delete();

            $ids = [];
            $rank = 1;
            $now = date('Y-m-d H:i:s');
            foreach ($top as $row) {
                $id = DB::table('ahg_mention_candidate')->insertGetId([
                    'mention_id' => $mentionId,
                    'rank_position' => $rank,
                    'candidate_source' => (string) $row['source'],
                    'candidate_authority_id' => isset($row['authority_id']) ? $row['authority_id'] : null,
                    'candidate_fuseki_uri' => isset($row['fuseki_uri']) ? $row['fuseki_uri'] : null,
                    'candidate_display_name' => (string) $row['display_name'],
                    'name_similarity_score' => (float) $row['score'],
                    'evidence_signals' => null,
                    'evidence_data' => null,
                    'composite_score' => (float) $row['score'],
                    'computed_at' => $now,
                ]);
                $ids[] = (int) $id;
                $rank++;
            }
            return $ids;
        });
    }

    private function resolveTopN(): int
    {
        $row = DB::table('ahg_settings')
            ->where('setting_key', 'authority_resolution.candidate_top_n')
            ->first(['setting_value']);
        if (!$row || $row->setting_value === null || $row->setting_value === '') {
            return self::DEFAULT_TOP_N;
        }
        $val = (int) $row->setting_value;
        return $val > 0 ? $val : self::DEFAULT_TOP_N;
    }
}
