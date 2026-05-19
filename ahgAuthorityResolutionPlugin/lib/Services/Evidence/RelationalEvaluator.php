<?php

/**
 * RelationalEvaluator - person/org relational evidence dimension for AtoM Heratio
 *
 * Checks whether any co-occurring PERSON/ORG entity in the mention's
 * paragraph also appears as a related actor of the candidate (relation
 * table, either side: subject_id ↔ object_id). This is the strongest
 * non-name signal we have for person/org disambiguation: if document
 * names Frederick Douglass alongside Mark Twain and the candidate actor
 * for "Frederick Douglass" is already linked to a "Mark Twain" actor in
 * AtoM's authority graph, that is direct corroboration.
 *
 * Signal logic:
 *   - No co-occurring person/org entities -> ABSENT
 *   - No relations on candidate actor     -> ABSENT
 *   - Any name match between related actors and co-occurring entities -> MATCH
 *   - Relations exist + co-occurring exist + zero overlap -> SILENT
 *     (rare to be a real "conflict" - the relation graph is too sparse
 *      to call non-overlap conflicting; downgrade to silent)
 *
 * Mirrors the Laravel-side RelationalEvaluator.
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

class RelationalEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['PERSON', 'ORG'];

    public function dimension(): string
    {
        return 'relational';
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

        $coOccurringNames = $this->extractCoOccurringActorNames($contextRow);
        if (empty($coOccurringNames)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_co_occurring_actors']];
        }

        $relatedActorIds = $this->loadRelatedActorIds($candidateAuthorityId);
        if (empty($relatedActorIds)) {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'reason' => 'no_relations_on_candidate',
                    'co_occurring_actors' => $coOccurringNames,
                ],
            ];
        }

        $relatedNames = $this->loadActorNames($relatedActorIds);
        if (empty($relatedNames)) {
            return [
                'signal' => EvidenceSignal::SILENT,
                'data' => [
                    'reason' => 'related_actors_have_no_names',
                    'related_actor_ids' => $relatedActorIds,
                ],
            ];
        }

        $matches = [];
        $relatedLower = array_map(function ($n) {
            return mb_strtolower((string) $n, 'UTF-8');
        }, $relatedNames);
        foreach ($coOccurringNames as $name) {
            $needle = mb_strtolower($name, 'UTF-8');
            foreach ($relatedLower as $i => $rn) {
                if ($rn !== '' && (strpos($rn, $needle) !== false || strpos($needle, $rn) !== false)) {
                    $matches[] = [
                        'co_occurring' => $name,
                        'related_actor' => $relatedNames[$i],
                    ];
                    break;
                }
            }
        }

        if (!empty($matches)) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => [
                    'matches' => $matches,
                    'related_actor_count' => count($relatedNames),
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'co_occurring_actors' => $coOccurringNames,
                'related_actor_count' => count($relatedNames),
                'reason' => 'no_overlap',
            ],
        ];
    }

    private function extractCoOccurringActorNames(object $contextRow): array
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
            $type = $row['type'] ?? '';
            $value = $row['value'] ?? '';
            if (!in_array($type, ['PERSON', 'ORG'], true)) {
                continue;
            }
            if (!is_string($value) || $value === '') {
                continue;
            }
            $key = mb_strtolower($value, 'UTF-8');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $names[] = $value;
        }
        return $names;
    }

    private function loadRelatedActorIds(int $actorId): array
    {
        $subjects = DB::table('relation')->where('subject_id', $actorId)->pluck('object_id')->all();
        $objects = DB::table('relation')->where('object_id', $actorId)->pluck('subject_id')->all();
        $merged = array_unique(array_merge(array_map('intval', $subjects), array_map('intval', $objects)));
        $merged = array_values(array_filter($merged, function ($id) use ($actorId) {
            return $id > 0 && $id !== $actorId;
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
