<?php

/**
 * CoOccurringPersonEvaluator - place co-occurring person evidence dimension
 * for AtoM Heratio
 *
 * Examines persons/orgs co-occurring with the place mention, and checks
 * whether any of those entities have an actor authority that is linked
 * to the candidate place term via the AtoM relation table. E.g. for the
 * place mention "London" alongside co-occurring "Frederick Douglass", if
 * Frederick Douglass's actor row has a relation pointing at the London
 * term, that supports the candidate.
 *
 * Signal logic:
 *   - No co-occurring person/org entities -> ABSENT
 *   - Candidate term has no incoming relations -> ABSENT
 *   - Any overlap between candidate-related actor names and co-occurring
 *     entity values -> MATCH
 *   - Relations + co-occurring present, zero overlap -> SILENT
 *
 * Mirrors the Laravel-side CoOccurringPersonEvaluator.
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

class CoOccurringPersonEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['GPE', 'PLACE', 'LOC'];

    public function dimension(): string
    {
        return 'co_occurring';
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

        $coOccurring = $this->extractActorCoOccurring($contextRow);
        if (empty($coOccurring)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_co_occurring_actors']];
        }

        $relatedActorIds = $this->loadRelatedActorIds($candidateAuthorityId);
        if (empty($relatedActorIds)) {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'reason' => 'no_relations_on_candidate_term',
                    'co_occurring_actors' => $coOccurring,
                ],
            ];
        }

        $relatedNames = $this->loadActorNames($relatedActorIds);
        if (empty($relatedNames)) {
            return [
                'signal' => EvidenceSignal::SILENT,
                'data' => [
                    'related_actor_ids' => $relatedActorIds,
                    'reason' => 'related_actors_have_no_names',
                ],
            ];
        }

        $matches = [];
        $relatedLower = array_map(function ($n) {
            return mb_strtolower((string) $n, 'UTF-8');
        }, $relatedNames);
        foreach ($coOccurring as $name) {
            $needle = mb_strtolower($name, 'UTF-8');
            foreach ($relatedLower as $i => $rn) {
                if ($rn !== '' && (strpos($rn, $needle) !== false || strpos($needle, $rn) !== false)) {
                    $matches[] = ['co_occurring' => $name, 'related_actor' => $relatedNames[$i]];
                    break;
                }
            }
        }

        if (!empty($matches)) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => ['matches' => $matches],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'co_occurring_actors' => $coOccurring,
                'related_actor_count' => count($relatedNames),
                'reason' => 'no_overlap',
            ],
        ];
    }

    private function extractActorCoOccurring(object $contextRow): array
    {
        $payload = $contextRow->co_occurring_entities ?? null;
        if (!$payload) {
            return [];
        }
        $rows = is_string($payload) ? json_decode($payload, true) : $payload;
        if (!is_array($rows)) {
            return [];
        }
        $names = [];
        $seen = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $t = $row['type'] ?? '';
            $v = $row['value'] ?? '';
            if (!in_array($t, ['PERSON', 'ORG'], true)) {
                continue;
            }
            if (!is_string($v) || $v === '') {
                continue;
            }
            $k = mb_strtolower($v, 'UTF-8');
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $names[] = $v;
        }
        return $names;
    }

    private function loadRelatedActorIds(int $termId): array
    {
        $subjects = DB::table('relation')->where('subject_id', $termId)->pluck('object_id')->all();
        $objects = DB::table('relation')->where('object_id', $termId)->pluck('subject_id')->all();
        $merged = array_unique(array_merge(array_map('intval', $subjects), array_map('intval', $objects)));
        $merged = array_values(array_filter($merged, function ($id) {
            return $id > 0;
        }));
        if (empty($merged)) {
            return [];
        }
        $existing = DB::table('actor')->whereIn('id', $merged)->pluck('id')->all();
        return array_map('intval', $existing);
    }

    private function loadActorNames(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $rows = DB::table('actor_i18n')
            ->whereIn('id', $ids)
            ->whereNotNull('authorized_form_of_name')
            ->where('authorized_form_of_name', '!=', '')
            ->get(['id', 'authorized_form_of_name']);
        $names = [];
        foreach ($rows as $r) {
            $names[] = (string) $r->authorized_form_of_name;
        }
        return $names;
    }
}
