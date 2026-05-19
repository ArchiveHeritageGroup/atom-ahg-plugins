<?php

/**
 * PlaceConflictEvaluator - place type/taxonomy mismatch dimension for AtoM Heratio
 *
 * Cheap definitive-conflict catcher for place candidates. Mirrors the
 * person-side ConflictEvaluator but on the Places-taxonomy axis:
 *
 *   - candidate_authority_id resolves to a term NOT in taxonomy 42 (Places)
 *     -> CONFLICT (this candidate isn't actually a place)
 *   - mention type is PLACE/GPE/LOC but candidate is in Subjects (35) etc.
 *     -> CONFLICT
 *   - All else -> SILENT
 *
 * Mirrors the Laravel-side PlaceConflictEvaluator.
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

class PlaceConflictEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['GPE', 'PLACE', 'LOC'];
    private const PLACES_TAXONOMY_ID = 42;

    public function dimension(): string
    {
        return 'conflict';
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

        $term = DB::table('term')->where('id', $candidateAuthorityId)->first(['taxonomy_id']);
        if (!$term) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'term_row_missing']];
        }
        $taxonomyId = (int) $term->taxonomy_id;
        if ($taxonomyId !== self::PLACES_TAXONOMY_ID) {
            return [
                'signal' => EvidenceSignal::CONFLICT,
                'data' => [
                    'reason' => 'candidate_not_in_places_taxonomy',
                    'taxonomy_id' => $taxonomyId,
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => ['taxonomy_id' => $taxonomyId],
        ];
    }
}
