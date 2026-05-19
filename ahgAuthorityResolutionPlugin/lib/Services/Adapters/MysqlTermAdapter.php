<?php

/**
 * MysqlTermAdapter - service for AtoM Heratio
 *
 * Candidate adapter that searches the local Places taxonomy (taxonomy_id = 42)
 * for GPE / PLACE / LOC matches via term_i18n.name LIKE.
 *
 * The Places taxonomy is the canonical local geographic authority in AtoM;
 * GeoNames / TGN are external and will be wired as separate adapters in a
 * later task. Hierarchy walk (parent_id) is NOT done here - candidate
 * generation only needs the leaf name; spatial scoring lives in Task 4.
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

class MysqlTermAdapter implements CandidateAdapterInterface
{
    private const PLACES_TAXONOMY_ID = 42;

    private const SUPPORTED_TYPES = ['GPE', 'PLACE', 'LOC'];

    public function supports(string $entityType): bool
    {
        return in_array($entityType, self::SUPPORTED_TYPES, true);
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

        $rows = DB::table('term as t')
            ->join('term_i18n as ti', 'ti.id', '=', 't.id')
            ->where('t.taxonomy_id', self::PLACES_TAXONOMY_ID)
            ->where('ti.name', 'like', '%' . $query . '%')
            ->whereNotNull('ti.name')
            ->where('ti.name', '!=', '')
            ->groupBy('t.id', 'ti.name')
            ->orderByRaw('LENGTH(ti.name)')
            ->limit($limit)
            ->get(['t.id as authority_id', 'ti.name as display_name']);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'source' => 'mysql_term',
                'authority_id' => (int) $r->authority_id,
                'fuseki_uri' => null,
                'display_name' => (string) $r->display_name,
            ];
        }
        return $out;
    }
}
