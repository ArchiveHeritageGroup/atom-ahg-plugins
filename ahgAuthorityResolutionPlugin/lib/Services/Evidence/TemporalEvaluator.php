<?php

/**
 * TemporalEvaluator - person/org temporal evidence dimension for AtoM Heratio
 *
 * Compares the candidate authority's known active dates (event.start_date /
 * event.end_date for events with actor_id = candidate, plus the free-text
 * actor_i18n.dates_of_existence as a fallback) against any DATE / ISAD_DATE
 * entities collected in ahg_mention_context.nearby_dates.
 *
 * Signal logic:
 *   - No nearby dates parseable -> ABSENT
 *   - No actor dates known      -> ABSENT
 *   - Any context date inside any actor interval                -> MATCH
 *   - All context dates fall OUTSIDE all actor intervals,
 *     AND there is at least one actor interval                  -> CONFLICT
 *   - Else (overlap is ambiguous, e.g. only one side has a year) -> SILENT
 *
 * Mirrors the Laravel-side TemporalEvaluator in
 * ahg-authority-resolution. Year-level granularity only - AtoM
 * dates_of_existence is free text, and document dates are NER strings.
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

class TemporalEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['PERSON', 'ORG'];

    public function dimension(): string
    {
        return 'temporal';
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

        $contextYears = $this->extractContextYears($contextRow);
        $actorIntervals = $this->loadActorIntervals($candidateAuthorityId);

        if (empty($contextYears)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_context_dates', 'actor_intervals' => $actorIntervals]];
        }
        if (empty($actorIntervals)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_actor_dates', 'context_years' => $contextYears]];
        }

        $matchedYears = [];
        $outsideYears = [];
        foreach ($contextYears as $year) {
            $isInside = false;
            foreach ($actorIntervals as $iv) {
                if ($this->yearInsideInterval($year, $iv)) {
                    $isInside = true;
                    break;
                }
            }
            if ($isInside) {
                $matchedYears[] = $year;
            } else {
                $outsideYears[] = $year;
            }
        }

        if (!empty($matchedYears)) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => [
                    'matched_years' => $matchedYears,
                    'actor_intervals' => $actorIntervals,
                ],
            ];
        }
        if (!empty($outsideYears)) {
            return [
                'signal' => EvidenceSignal::CONFLICT,
                'data' => [
                    'outside_years' => $outsideYears,
                    'actor_intervals' => $actorIntervals,
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'context_years' => $contextYears,
                'actor_intervals' => $actorIntervals,
                'reason' => 'unparseable_overlap',
            ],
        ];
    }

    /**
     * Pull 4-digit years out of ahg_mention_context.nearby_dates JSON.
     */
    private function extractContextYears(object $contextRow): array
    {
        $payload = $contextRow->nearby_dates ?? null;
        if (!$payload) {
            return [];
        }
        $rows = is_string($payload) ? json_decode($payload, true) : $payload;
        if (!is_array($rows)) {
            return [];
        }
        $years = [];
        foreach ($rows as $row) {
            $value = is_array($row) ? ($row['value'] ?? '') : '';
            if (!is_string($value) || $value === '') {
                continue;
            }
            if (preg_match_all('/\b(1[5-9]\d{2}|20\d{2})\b/', $value, $m)) {
                foreach ($m[1] as $y) {
                    $years[] = (int) $y;
                }
            }
        }
        return array_values(array_unique($years));
    }

    /**
     * Load { start_year, end_year } intervals for the candidate actor.
     * Prefers event.start_date/end_date; falls back to a 4-digit-year
     * scan of actor_i18n.dates_of_existence.
     */
    private function loadActorIntervals(int $actorId): array
    {
        $intervals = [];

        $events = DB::table('event')
            ->where('actor_id', $actorId)
            ->get(['start_date', 'end_date']);
        foreach ($events as $ev) {
            $s = $this->yearFromDate($ev->start_date ?? null);
            $e = $this->yearFromDate($ev->end_date ?? null);
            if ($s === null && $e === null) {
                continue;
            }
            $intervals[] = ['start' => $s, 'end' => $e ?? $s];
        }

        if (empty($intervals)) {
            $row = DB::table('actor_i18n')->where('id', $actorId)->first(['dates_of_existence']);
            if ($row && !empty($row->dates_of_existence)) {
                if (preg_match_all('/\b(1[5-9]\d{2}|20\d{2})\b/', (string) $row->dates_of_existence, $m)) {
                    $years = array_map('intval', $m[1]);
                    if (count($years) >= 2) {
                        sort($years);
                        $intervals[] = ['start' => $years[0], 'end' => $years[count($years) - 1]];
                    } elseif (count($years) === 1) {
                        $intervals[] = ['start' => $years[0], 'end' => $years[0]];
                    }
                }
            }
        }
        return $intervals;
    }

    private function yearFromDate(?string $date): ?int
    {
        if (!$date) {
            return null;
        }
        if (preg_match('/(1[5-9]\d{2}|20\d{2})/', $date, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    private function yearInsideInterval(int $year, array $interval): bool
    {
        $s = $interval['start'] ?? null;
        $e = $interval['end'] ?? null;
        if ($s !== null && $e !== null) {
            return $year >= $s && $year <= $e;
        }
        if ($s !== null) {
            return $year >= $s;
        }
        if ($e !== null) {
            return $year <= $e;
        }
        return false;
    }
}
