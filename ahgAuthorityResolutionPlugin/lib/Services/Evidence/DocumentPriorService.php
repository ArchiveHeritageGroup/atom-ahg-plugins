<?php

/**
 * DocumentPriorService - fonds-level place distribution cache for AtoM Heratio
 *
 * For a given information_object (mention's object_id) walks up
 * information_object.parent_id to the fonds (root_id = 1 by Qubit
 * convention), then counts resolved place mentions across every
 * descendant of that fonds. Returns a top-3 list of
 * { linked_actor_id, count } pairs - this is the "this fonds usually
 * talks about X" prior used by PriorEvaluator.
 *
 * Cached for 24h in ahg_settings under key
 * `authority_resolution.prior.<fonds_id>` as a JSON payload with a
 * computed_at timestamp. Re-derived on cache miss / TTL expiry.
 *
 * Mirrors the Laravel-side DocumentPriorService.
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

class DocumentPriorService
{
    private const TTL_SECONDS = 86400;
    private const TOP_N = 3;
    private const PLACE_TYPES = ['GPE', 'PLACE', 'LOC'];
    private const ROOT_OBJECT_ID = 1;

    /**
     * Returns ['fonds_id' => int, 'top' => [{linked_actor_id, count}, ...]].
     */
    public function priorForObject(int $objectId): array
    {
        $fondsId = $this->resolveFondsId($objectId);
        if ($fondsId === null) {
            return ['fonds_id' => null, 'top' => []];
        }

        $cached = $this->readCache($fondsId);
        if ($cached !== null) {
            return ['fonds_id' => $fondsId, 'top' => $cached, 'from_cache' => true];
        }

        $top = $this->computeFondsPlaceDistribution($fondsId);
        $this->writeCache($fondsId, $top);
        return ['fonds_id' => $fondsId, 'top' => $top, 'from_cache' => false];
    }

    /**
     * Walk information_object.parent_id chain until parent_id = ROOT_OBJECT_ID,
     * or we hit a NULL parent. The hit is the fonds (top-level descendant of
     * the synthetic root). Defensive against cycles via a fixed iteration cap.
     */
    private function resolveFondsId(int $objectId): ?int
    {
        $current = $objectId;
        for ($i = 0; $i < 32; $i++) {
            $row = DB::table('information_object')->where('id', $current)->first(['id', 'parent_id']);
            if (!$row) {
                return null;
            }
            if ($row->parent_id === null || (int) $row->parent_id === self::ROOT_OBJECT_ID) {
                return (int) $row->id;
            }
            $current = (int) $row->parent_id;
        }
        return null;
    }

    private function readCache(int $fondsId): ?array
    {
        $row = DB::table('ahg_settings')
            ->where('setting_key', 'authority_resolution.prior.' . $fondsId)
            ->first(['setting_value']);
        if (!$row || $row->setting_value === null || $row->setting_value === '') {
            return null;
        }
        $decoded = json_decode((string) $row->setting_value, true);
        if (!is_array($decoded) || !isset($decoded['top'], $decoded['computed_at'])) {
            return null;
        }
        $age = time() - (int) $decoded['computed_at'];
        if ($age > self::TTL_SECONDS || $age < 0) {
            return null;
        }
        return is_array($decoded['top']) ? $decoded['top'] : [];
    }

    private function writeCache(int $fondsId, array $top): void
    {
        $payload = json_encode([
            'computed_at' => time(),
            'top' => $top,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $now = date('Y-m-d H:i:s');
        $existing = DB::table('ahg_settings')
            ->where('setting_key', 'authority_resolution.prior.' . $fondsId)
            ->first(['id']);
        if ($existing) {
            DB::table('ahg_settings')
                ->where('id', $existing->id)
                ->update([
                    'setting_value' => $payload,
                    'setting_group' => 'authority_resolution',
                    'setting_type' => 'json',
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('ahg_settings')->insert([
                'setting_key' => 'authority_resolution.prior.' . $fondsId,
                'setting_value' => $payload,
                'setting_group' => 'authority_resolution',
                'setting_type' => 'json',
                'description' => 'Cached fonds-level place distribution for authority resolution prior',
                'is_sensitive' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }
    }

    /**
     * Find all descendant info_object IDs of $fondsId, then bucket
     * ahg_ner_entity rows with entity_type IN places and linked_actor_id
     * NOT NULL, grouped by linked_actor_id. Returns top N.
     */
    private function computeFondsPlaceDistribution(int $fondsId): array
    {
        $fonds = DB::table('information_object')->where('id', $fondsId)->first(['lft', 'rgt']);
        if (!$fonds || $fonds->lft === null || $fonds->rgt === null) {
            return [];
        }

        $descendantIds = DB::table('information_object')
            ->where('lft', '>=', $fonds->lft)
            ->where('rgt', '<=', $fonds->rgt)
            ->pluck('id')
            ->all();
        if (empty($descendantIds)) {
            return [];
        }

        $counts = DB::table('ahg_ner_entity')
            ->whereIn('object_id', array_map('intval', $descendantIds))
            ->whereIn('entity_type', self::PLACE_TYPES)
            ->whereNotNull('linked_actor_id')
            ->select('linked_actor_id', DB::raw('COUNT(*) as n'))
            ->groupBy('linked_actor_id')
            ->orderByRaw('n DESC')
            ->limit(self::TOP_N)
            ->get();

        $out = [];
        foreach ($counts as $row) {
            $out[] = [
                'linked_actor_id' => (int) $row->linked_actor_id,
                'count' => (int) $row->n,
            ];
        }
        return $out;
    }
}
