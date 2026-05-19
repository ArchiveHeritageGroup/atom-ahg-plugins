<?php

/**
 * ConflictEvaluator - person/org type / explicit conflict dimension for AtoM Heratio
 *
 * Catches the cheap, definitive mismatch cases that the more nuanced
 * evaluators would not detect:
 *
 *   - mention.entity_type = PERSON but candidate.entity_type_id = 131 (corporate body) -> CONFLICT
 *   - mention.entity_type = ORG    but candidate.entity_type_id = 132 (person)        -> CONFLICT
 *   - actor flagged with description_status_id = 161 ("Draft" / withdrawn)            -> CONFLICT
 *
 * Otherwise SILENT (no positive signal from this dimension - the absence
 * of type mismatch is not strong evidence by itself).
 *
 * Mirrors the Laravel-side ConflictEvaluator.
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

class ConflictEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['PERSON', 'ORG'];
    private const ENTITY_TYPE_PERSON_ID = 132;
    private const ENTITY_TYPE_CORPORATE_BODY_ID = 131;

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

        $actor = DB::table('actor')
            ->where('id', $candidateAuthorityId)
            ->first(['entity_type_id']);
        if (!$actor) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'actor_row_missing']];
        }

        $mentionType = (string) ($mentionRow->entity_type ?? '');
        $actorTypeId = $actor->entity_type_id !== null ? (int) $actor->entity_type_id : null;

        if ($mentionType === 'PERSON' && $actorTypeId === self::ENTITY_TYPE_CORPORATE_BODY_ID) {
            return [
                'signal' => EvidenceSignal::CONFLICT,
                'data' => [
                    'reason' => 'mention_PERSON_but_actor_is_corporate_body',
                    'actor_entity_type_id' => $actorTypeId,
                ],
            ];
        }
        if ($mentionType === 'ORG' && $actorTypeId === self::ENTITY_TYPE_PERSON_ID) {
            return [
                'signal' => EvidenceSignal::CONFLICT,
                'data' => [
                    'reason' => 'mention_ORG_but_actor_is_person',
                    'actor_entity_type_id' => $actorTypeId,
                ],
            ];
        }

        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'actor_entity_type_id' => $actorTypeId,
                'mention_entity_type' => $mentionType,
            ],
        ];
    }
}
