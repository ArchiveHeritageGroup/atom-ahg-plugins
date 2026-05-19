<?php

/**
 * PriorEvaluator - place document-prior evidence dimension for AtoM Heratio
 *
 * Asks: "for the fonds this mention sits in, is the candidate term among
 * the top-3 most-resolved places already?". If yes, the mention is more
 * likely to be that candidate by Bayesian prior. Uses
 * DocumentPriorService for the cached fonds distribution.
 *
 * Signal logic:
 *   - DocumentPriorService returns empty top list -> ABSENT
 *   - Candidate term appears in fonds top-3       -> MATCH
 *   - Top-3 is non-empty + candidate is absent    -> SILENT
 *     (a candidate being outside the top-3 is not strong enough to
 *      flip to CONFLICT; the top-3 is a soft prior, not a closed set)
 *
 * Mirrors the Laravel-side PriorEvaluator.
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

class PriorEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['GPE', 'PLACE', 'LOC'];

    /** @var DocumentPriorService */
    private $prior;

    public function __construct(DocumentPriorService $prior)
    {
        $this->prior = $prior;
    }

    public function dimension(): string
    {
        return 'document_prior';
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

        $objectId = isset($mentionRow->object_id) ? (int) $mentionRow->object_id : 0;
        if ($objectId <= 0) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_object_id']];
        }

        $prior = $this->prior->priorForObject($objectId);
        $top = $prior['top'] ?? [];

        if (empty($top)) {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'reason' => 'no_fonds_prior',
                    'fonds_id' => $prior['fonds_id'] ?? null,
                ],
            ];
        }

        foreach ($top as $entry) {
            $aid = (int) ($entry['linked_actor_id'] ?? 0);
            if ($aid === $candidateAuthorityId) {
                return [
                    'signal' => EvidenceSignal::MATCH,
                    'data' => [
                        'fonds_id' => $prior['fonds_id'] ?? null,
                        'count' => (int) ($entry['count'] ?? 0),
                        'top' => $top,
                    ],
                ];
            }
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'fonds_id' => $prior['fonds_id'] ?? null,
                'top' => $top,
                'reason' => 'candidate_not_in_top_3',
            ],
        ];
    }
}
