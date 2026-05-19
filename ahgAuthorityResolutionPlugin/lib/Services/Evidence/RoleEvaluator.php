<?php

/**
 * RoleEvaluator - person/org role-language evidence dimension for AtoM Heratio
 *
 * Checks whether the role-language tokens found near the mention (kinship,
 * witness, location, movement, "other") have analogues in the candidate
 * actor's history / functions / mandates / general_context text.
 *
 * Signal logic:
 *   - No role tokens captured in context -> ABSENT
 *   - No actor history text at all       -> ABSENT
 *   - Any role token appears in actor text -> MATCH
 *   - Actor has biographical text + zero token overlap -> SILENT
 *     (downgrade from CONFLICT: free-text mismatch is too unreliable
 *      to penalise a candidate with -0.3; tokens often imply a relation
 *      not the candidate themselves)
 *
 * Mirrors the Laravel-side RoleEvaluator.
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

class RoleEvaluator implements EvaluatorInterface
{
    private const SUPPORTED = ['PERSON', 'ORG'];

    public function dimension(): string
    {
        return 'role';
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

        $tokens = $this->extractTokens($contextRow);
        if (empty($tokens)) {
            return ['signal' => EvidenceSignal::ABSENT, 'data' => ['reason' => 'no_role_tokens']];
        }

        $bioText = $this->loadActorBiographyText($candidateAuthorityId);
        if ($bioText === '') {
            return [
                'signal' => EvidenceSignal::ABSENT,
                'data' => [
                    'reason' => 'no_actor_biography',
                    'role_tokens' => $tokens,
                ],
            ];
        }

        $hay = mb_strtolower($bioText, 'UTF-8');
        $matches = [];
        foreach ($tokens as $kind => $list) {
            foreach ($list as $token) {
                $needle = mb_strtolower($token, 'UTF-8');
                if ($needle === '') {
                    continue;
                }
                if (mb_strpos($hay, $needle, 0, 'UTF-8') !== false) {
                    $matches[] = ['kind' => $kind, 'token' => $token];
                }
            }
        }

        if (!empty($matches)) {
            return [
                'signal' => EvidenceSignal::MATCH,
                'data' => [
                    'matched_tokens' => $matches,
                ],
            ];
        }
        return [
            'signal' => EvidenceSignal::SILENT,
            'data' => [
                'role_tokens' => $tokens,
                'reason' => 'no_overlap_in_actor_text',
            ],
        ];
    }

    /**
     * Group context tokens by `kind`, returning kind => [unique tokens].
     */
    private function extractTokens(object $contextRow): array
    {
        $payload = $contextRow->role_language_tokens ?? null;
        if (!$payload) {
            return [];
        }
        $rows = is_string($payload) ? json_decode($payload, true) : $payload;
        if (!is_array($rows)) {
            return [];
        }
        $by = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $kind = (string) ($row['kind'] ?? 'other');
            $token = (string) ($row['token'] ?? '');
            if ($token === '') {
                continue;
            }
            $by[$kind][$token] = true;
        }
        $out = [];
        foreach ($by as $k => $tset) {
            $out[$k] = array_keys($tset);
        }
        return $out;
    }

    private function loadActorBiographyText(int $actorId): string
    {
        $row = DB::table('actor_i18n')
            ->where('id', $actorId)
            ->first(['history', 'functions', 'mandates', 'general_context']);
        if (!$row) {
            return '';
        }
        $parts = [];
        foreach (['history', 'functions', 'mandates', 'general_context'] as $col) {
            $v = $row->{$col} ?? null;
            if (is_string($v) && $v !== '') {
                $parts[] = $v;
            }
        }
        return trim(implode("\n", $parts));
    }
}
