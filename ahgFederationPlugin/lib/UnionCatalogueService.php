<?php

namespace AhgFederation;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Union catalogue (#151) — a single view of holdings across the federation.
 *
 * A harvested record is an information_object with a federation_harvest_log
 * entry attributing it to a source peer; everything else is local. This service
 * lists federation members with their contributed record counts, and a unified
 * browse of records with source attribution (Local / peer name).
 */
class UnionCatalogueService
{
    /** Federation members + how many records each has contributed. @return array<int,object> */
    public function members(): array
    {
        $counts = DB::table('federation_harvest_log')
            ->select('peer_id', DB::raw('COUNT(DISTINCT information_object_id) AS c'))
            ->groupBy('peer_id')->pluck('c', 'peer_id');

        $peers = DB::table('federation_peer')->orderBy('name')
            ->get(['id', 'name', 'peer_type', 'base_url', 'is_active', 'last_harvest_at', 'last_harvest_status'])->all();
        foreach ($peers as $p) {
            $p->records = (int) ($counts[$p->id] ?? 0);
        }

        return $peers;
    }

    /** @return array{local:int,harvested:int,total:int,members:int} */
    public function counts(): array
    {
        $total = (int) DB::table('information_object')->where('id', '>', 1)->count();
        $harvested = (int) DB::table('federation_harvest_log')->distinct()->count('information_object_id');

        return [
            'local' => max(0, $total - $harvested),
            'harvested' => $harvested,
            'total' => $total,
            'members' => (int) DB::table('federation_peer')->count(),
        ];
    }

    /**
     * Unified record browse with source attribution.
     * @param array $f keys: peer_id (int), source ('local'|'harvested'), q, page, per
     * @return array{items:array,total:int,page:int,pages:int}
     */
    public function browse(array $f = []): array
    {
        $culture = 'en';
        $base = DB::table('information_object as io')
            ->join('information_object_i18n as i', function ($j) use ($culture) {
                $j->on('i.id', '=', 'io.id')->where('i.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->leftJoin('federation_harvest_log as l', 'l.information_object_id', '=', 'io.id')
            ->leftJoin('federation_peer as p', 'p.id', '=', 'l.peer_id')
            ->where('io.id', '>', 1);

        if (!empty($f['peer_id'])) {
            $base->where('p.id', (int) $f['peer_id']);
        } elseif (($f['source'] ?? '') === 'local') {
            $base->whereNull('l.id');
        } elseif (($f['source'] ?? '') === 'harvested') {
            $base->whereNotNull('l.id');
        }
        if (!empty($f['q'])) {
            $base->where('i.title', 'LIKE', '%' . $f['q'] . '%');
        }

        $total = (int) (clone $base)->distinct()->count('io.id');
        $page = max(1, (int) ($f['page'] ?? 1));
        $per = min(100, max(1, (int) ($f['per'] ?? 50)));

        $items = $base->groupBy('io.id', 'i.title', 's.slug')
            ->orderBy('i.title')
            ->offset(($page - 1) * $per)->limit($per)
            ->get(['io.id', 'i.title', 's.slug', DB::raw('MAX(p.name) AS source_peer')])
            ->all();

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $per)];
    }
}
