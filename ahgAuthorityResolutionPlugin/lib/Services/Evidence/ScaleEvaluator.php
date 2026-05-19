<?php

/**
 * ScaleEvaluator - place scale dimension for AtoM Heratio
 *
 * Rough scale heuristic. Counts the depth of the candidate place term's
 * ancestor chain in the Places taxonomy: depth 0 = continent/country
 * top-level, depth >= 3 = city/neighbourhood level. Compares against the
 * "scale" of co-occurring places in the document - if the document also
 * mentions other deep-chain places (cities), a city-scale candidate is
 * a better match than a continent-scale one. This is a weak signal but
 * useful when other dimensions are silent.
 *
 * Signal logic:
 *   - No other context places   -> ABSENT
 *   - No ancestor chain at all  -> ABSENT (treated as top-level)
 *   - Candidate depth roughly matches the median co-occurring place depth
 *     (within +/-1)             -> MATCH
 *   - Otherwise                 -> SILENT
 *
 * Mirrors the Laravel-side ScaleEvaluator.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution\Evidence;

use Illuminate\Database\Capsule\Manager as DB;

class ScaleEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['GPE', 'PLACE', 'LOC'];
    private const PLACES_TAXONOMY_ID = 42;
    private const MAX_CHAIN_DEPTH = 16;

    public function dimension(): string
    {
        return 'scale';
    }

    public function supports(string $entityType): bool
    {
        return in_array($entityType, self::SUPPORTED, true);
    }

    public function evaluate(object $mentionRow, object $contextRow, object $candidateRow): array
    {
        $candidateAuthorityId = isset($candidateRow->candidate_authority_id)
            ? (int) $candidateRow->candidate_authority_id
            : 0;
        if ($candidateAuthorityId <= 0) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_local_authority']];
        }

        $contextPlaces = $this->uniquePlaceValues($contextRow);
        $mentionValue = mb_strtolower((string) ($mentionRow->entity_value ?? ''), 'UTF-8');
        $otherPlaces = array_values(array_filter($contextPlaces, function ($p) use ($mentionValue) {
            return mb_strtolower($p, 'UTF-8') !== $mentionValue;
        }));

        if (empty($otherPlaces)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_other_context_places']];
        }

        $candidateDepth = $this->depthOf($candidateAuthorityId);
        $otherDepths = $this->depthsOfNames($otherPlaces);
        if (empty($otherDepths)) {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'candidate_depth' => $candidateDepth,
                    'reason' => 'other_context_places_not_in_local_taxonomy',
                ],
            ];
        }

        sort($otherDepths);
        $median = $otherDepths[intdiv(count($otherDepths), 2)];

        if (abs($candidateDepth - $median) <= 1) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => [
                    'candidate_depth' => $candidateDepth,
                    'median_context_depth' => $median,
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'candidate_depth' => $candidateDepth,
                'median_context_depth' => $median,
                'context_depths' => $otherDepths,
            ],
        ];
    }

    private function uniquePlaceValues(object $contextRow): array
    {
        $payload = $contextRow->nearby_places ?? null;
        if (!$payload) {
            return [];
        }
        $rows = is_string($payload) ? json_decode($payload, true) : $payload;
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        $seen = [];
        foreach ($rows as $row) {
            $v = is_array($row) ? ($row['value'] ?? '') : '';
            if (!is_string($v) || $v === '') {
                continue;
            }
            $k = mb_strtolower($v, 'UTF-8');
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $v;
        }
        return $out;
    }

    private function depthOf(int $termId): int
    {
        $current = $termId;
        for ($depth = 0; $depth < self::MAX_CHAIN_DEPTH; $depth++) {
            $row = DB::table('term')->where('id', $current)->first(['parent_id', 'taxonomy_id']);
            if (!$row || $row->parent_id === null) {
                return $depth;
            }
            $parent = DB::table('term')->where('id', $row->parent_id)->first(['id', 'taxonomy_id']);
            if (!$parent || (int) $parent->taxonomy_id !== self::PLACES_TAXONOMY_ID) {
                return $depth;
            }
            $current = (int) $row->parent_id;
        }
        return self::MAX_CHAIN_DEPTH;
    }

    /**
     * Return depths for each name we can resolve in the Places taxonomy.
     * Names not in the local taxonomy are skipped (no penalty - external
     * places exist).
     */
    private function depthsOfNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }
        $rows = DB::table('term as t')
            ->join('term_i18n as ti', 'ti.id', '=', 't.id')
            ->where('t.taxonomy_id', self::PLACES_TAXONOMY_ID)
            ->whereIn('ti.name', $names)
            ->get(['t.id']);
        $depths = [];
        foreach ($rows as $r) {
            $depths[] = $this->depthOf((int) $r->id);
        }
        return $depths;
    }
}
