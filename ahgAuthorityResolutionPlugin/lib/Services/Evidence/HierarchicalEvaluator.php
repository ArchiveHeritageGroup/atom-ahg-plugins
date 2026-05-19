<?php

/**
 * HierarchicalEvaluator - place hierarchy evidence dimension for AtoM Heratio
 *
 * Walks the candidate place term's parent_id chain in the Places taxonomy
 * (taxonomy_id = 42) and checks whether any ancestor's name appears among
 * the other nearby_places captured in ahg_mention_context. If "Bristol" is
 * the mention and the candidate Bristol term has ancestor "England" which
 * appears in nearby_places, that's strong corroboration that this is the
 * UK Bristol rather than e.g. Bristol, Tennessee.
 *
 * Signal logic:
 *   - No nearby_places besides the mention itself -> ABSENT
 *   - No parent chain on candidate term           -> ABSENT
 *   - Any ancestor name appears in nearby_places  -> MATCH
 *   - Ancestors exist + zero overlap              -> SILENT
 *     (Place names are too noisy across language/locale to call a
 *      mismatch a CONFLICT here. Real conflicts are emitted by
 *      PlaceConflictEvaluator instead.)
 *
 * Mirrors the Laravel-side HierarchicalEvaluator.
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

class HierarchicalEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['GPE', 'PLACE', 'LOC'];
    private const PLACES_TAXONOMY_ID = 42;
    private const MAX_CHAIN_DEPTH = 16;

    public function dimension(): string
    {
        return 'hierarchical';
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
        $contextPlaces = array_values(array_filter($contextPlaces, function ($p) use ($mentionValue) {
            return mb_strtolower($p, 'UTF-8') !== $mentionValue;
        }));

        if (empty($contextPlaces)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_other_context_places']];
        }

        $ancestors = $this->loadAncestorNames($candidateAuthorityId);
        if (empty($ancestors)) {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'reason' => 'no_ancestor_chain',
                    'context_places' => $contextPlaces,
                ],
            ];
        }

        $matches = [];
        $ancestorsLower = array_map(function ($n) {
            return mb_strtolower((string) $n, 'UTF-8');
        }, $ancestors);
        foreach ($contextPlaces as $place) {
            $needle = mb_strtolower($place, 'UTF-8');
            foreach ($ancestorsLower as $i => $aL) {
                if ($aL !== '' && $aL === $needle) {
                    $matches[] = ['context_place' => $place, 'ancestor' => $ancestors[$i]];
                    break;
                }
            }
        }

        if (!empty($matches)) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => [
                    'matches' => $matches,
                    'ancestor_chain' => $ancestors,
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'ancestor_chain' => $ancestors,
                'context_places' => $contextPlaces,
                'reason' => 'no_overlap',
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

    private function loadAncestorNames(int $termId): array
    {
        $ancestors = [];
        $current = $termId;
        for ($i = 0; $i < self::MAX_CHAIN_DEPTH; $i++) {
            $row = DB::table('term as t')
                ->leftJoin('term_i18n as ti', 'ti.id', '=', 't.id')
                ->where('t.id', $current)
                ->first(['t.id', 't.parent_id', 't.taxonomy_id', 'ti.name']);
            if (!$row) {
                break;
            }
            if ((int) $row->id !== $termId && !empty($row->name)) {
                $ancestors[] = (string) $row->name;
            }
            if ($row->parent_id === null) {
                break;
            }
            $parent = DB::table('term')->where('id', $row->parent_id)->first(['id', 'taxonomy_id']);
            if (!$parent || (int) $parent->taxonomy_id !== self::PLACES_TAXONOMY_ID) {
                break;
            }
            $current = (int) $row->parent_id;
        }
        return $ancestors;
    }
}
