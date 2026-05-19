<?php

/**
 * GeographicEvaluator - person/org geographic evidence dimension for AtoM Heratio
 *
 * Cross-checks the candidate actor's known geographic footprint against
 * the place names collected in ahg_mention_context.nearby_places.
 *
 * Actor footprint sources (lowest cost first):
 *   1. actor_i18n.places (free text, e.g. "London; Manchester; Birmingham")
 *   2. actor_i18n.history substring scan for nearby_place names
 *   3. event/event_i18n.date strings (rare to contain place names but cheap)
 *
 * Signal logic:
 *   - No nearby_places                           -> ABSENT
 *   - No actor place data at all                 -> ABSENT
 *   - Any context place appears in actor data    -> MATCH
 *   - Actor has places + none overlap            -> CONFLICT
 *   - Actor has places but the comparison cannot
 *     produce a definitive answer (e.g. only
 *     hierarchical-implication is possible)      -> SILENT
 *
 * Mirrors the Laravel-side GeographicEvaluator in ahg-authority-resolution.
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

class GeographicEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['PERSON', 'ORG'];

    public function dimension(): string
    {
        return 'geographic';
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
        if (empty($contextPlaces)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_context_places']];
        }

        $actorPlacesText = $this->loadActorPlacesText($candidateAuthorityId);
        if ($actorPlacesText === '') {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'reason' => 'no_actor_places',
                    'context_places' => $contextPlaces,
                ],
            ];
        }

        $matched = [];
        $haystack = mb_strtolower($actorPlacesText, 'UTF-8');
        foreach ($contextPlaces as $place) {
            $needle = mb_strtolower($place, 'UTF-8');
            if ($needle !== '' && mb_strpos($haystack, $needle, 0, 'UTF-8') !== false) {
                $matched[] = $place;
            }
        }

        if (!empty($matched)) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => [
                    'matched_places' => $matched,
                    'context_places' => $contextPlaces,
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::CONFLICT,
            'data' => [
                'context_places' => $contextPlaces,
                'reason' => 'actor_has_places_no_overlap',
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
        $seen = [];
        $out = [];
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

    private function loadActorPlacesText(int $actorId): string
    {
        $row = DB::table('actor_i18n')->where('id', $actorId)->first(['places', 'history']);
        $places = is_object($row) ? (string) ($row->places ?? '') : '';
        $history = is_object($row) ? (string) ($row->history ?? '') : '';
        return trim($places . "\n" . $history);
    }
}
