<?php

/**
 * MysqlActorAdapter - service for AtoM Heratio
 *
 * Candidate adapter that searches the local actor authority store for
 * PERSON / ORG matches via authorized_form_of_name LIKE.
 *
 * Mapping (term IDs verified against AtoM "Actor entity types" taxonomy):
 *   PERSON -> actor.entity_type_id = 132
 *   ORG    -> actor.entity_type_id = 131  (Corporate body)
 *
 * Scoring is NOT done here; the adapter only emits raw candidate rows
 * and CandidateGeneratorService computes similarity uniformly so MySQL
 * and (future) Fuseki candidates rank against each other on the same
 * scale.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution\Adapters;

use Illuminate\Database\Capsule\Manager as DB;

class MysqlActorAdapter implements CandidateAdapterInterface
{
    private const ENTITY_TYPE_MAP = [
        'PERSON' => 132,
        'ORG' => 131,
    ];

    public function supports(string $entityType): bool
    {
        return isset(self::ENTITY_TYPE_MAP[$entityType]);
    }

    public function search(string $query, string $entityType, int $limit): array
    {
        if (!$this->supports($entityType)) {
            return [];
        }
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $entityTypeId = self::ENTITY_TYPE_MAP[$entityType];

        $rows = DB::table('actor as a')
            ->join('actor_i18n as ai', 'ai.id', '=', 'a.id')
            ->where('a.entity_type_id', $entityTypeId)
            ->where('ai.authorized_form_of_name', 'like', '%' . $query . '%')
            ->whereNotNull('ai.authorized_form_of_name')
            ->where('ai.authorized_form_of_name', '!=', '')
            ->groupBy('a.id', 'ai.authorized_form_of_name')
            ->orderByRaw('LENGTH(ai.authorized_form_of_name)')
            ->limit($limit)
            ->get(['a.id as authority_id', 'ai.authorized_form_of_name as display_name']);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'source' => 'mysql_actor',
                'authority_id' => (int) $r->authority_id,
                'fuseki_uri' => null,
                'display_name' => (string) $r->display_name,
            ];
        }
        return $out;
    }
}
