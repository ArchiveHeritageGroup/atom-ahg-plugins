<?php

/**
 * authResCacheStatsTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 10 (CLI consolidation) external-authority cache observer. Aggregates
 * ahg_authority_lookup_cache by source (viaf, wikidata, geonames, tgn, gnd,
 * isni, sagnc) with oldest + newest retrieved_at and an entity_type
 * breakdown.
 *
 * Pure SELECT. Read-only. Pairs with auth-res:cache-clear for cache
 * lifecycle: see what's there, evict by source, watch the warm path
 * refill on the next adapter call.
 *
 * Usage:
 *   php symfony auth-res:cache-stats
 *   php symfony auth-res:cache-stats --json
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// No service dependencies. Capsule is enough for a pure SELECT.
use Illuminate\Database\Capsule\Manager as DB;

class authResCacheStatsTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('json', null, sfCommandOption::PARAMETER_NONE, 'Emit JSON instead of the formatted table'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'cache-stats';
        $this->briefDescription = 'Report ahg_authority_lookup_cache contents grouped by source (entity-type breakdown + oldest/newest retrieval).';
        $this->detailedDescription = <<<EOF
Task 10 of the AHG Authority Resolution Engine. Aggregates the
external-authority lookup cache used by adapters (VIAF, Wikidata,
GeoNames, TGN, GND, ISNI, SAGNC) so the operator can see what's hot,
what's cold, and what's stale.

Columns reported per source:
  - rows
  - oldest retrieved_at
  - newest retrieved_at
  - entity-type breakdown (PERSON / ORG / PLACE)

Pure SELECT.

Usage:
  php symfony auth-res:cache-stats
  php symfony auth-res:cache-stats --json
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $perSource = $this->aggregatePerSource();
        $totalRows = 0;
        foreach ($perSource as $row) {
            $totalRows += (int) $row['rows'];
        }

        if (!empty($options['json'])) {
            $payload = [
                'total_rows' => $totalRows,
                'sources' => $perSource,
            ];
            $this->log(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if ($totalRows === 0) {
            $this->log('ahg_authority_lookup_cache is empty.');
            return 0;
        }

        $this->log(sprintf('ahg_authority_lookup_cache: %d row(s) across %d source(s)', $totalRows, count($perSource)));
        $this->log(str_repeat('-', 92));
        $this->log(sprintf('%-12s %6s   %-19s   %-19s   %s', 'source', 'rows', 'oldest', 'newest', 'by entity_type'));
        $this->log(str_repeat('-', 92));
        foreach ($perSource as $row) {
            $this->log(sprintf(
                '%-12s %6d   %-19s   %-19s   %s',
                $row['source'],
                (int) $row['rows'],
                (string) $row['oldest'],
                (string) $row['newest'],
                $this->joinKv($row['by_entity_type'])
            ));
        }
        $this->log(str_repeat('-', 92));
        return 0;
    }

    /**
     * @return array<int, array{source:string, rows:int, oldest:string, newest:string, by_entity_type:array<string,int>}>
     */
    private function aggregatePerSource(): array
    {
        $summary = DB::table('ahg_authority_lookup_cache')
            ->select(
                'source',
                DB::raw('COUNT(*) as rows_count'),
                DB::raw('MIN(retrieved_at) as oldest'),
                DB::raw('MAX(retrieved_at) as newest')
            )
            ->groupBy('source')
            ->orderByDesc('rows_count')
            ->get();

        $byType = DB::table('ahg_authority_lookup_cache')
            ->select('source', 'entity_type', DB::raw('COUNT(*) as c'))
            ->groupBy('source', 'entity_type')
            ->get();

        $typeMap = [];
        foreach ($byType as $r) {
            $src = (string) $r->source;
            $type = $r->entity_type !== null && $r->entity_type !== '' ? (string) $r->entity_type : '(null)';
            if (!isset($typeMap[$src])) {
                $typeMap[$src] = [];
            }
            $typeMap[$src][$type] = (int) $r->c;
        }

        $out = [];
        foreach ($summary as $r) {
            $src = (string) $r->source;
            $out[] = [
                'source' => $src,
                'rows' => (int) $r->rows_count,
                'oldest' => (string) $r->oldest,
                'newest' => (string) $r->newest,
                'by_entity_type' => isset($typeMap[$src]) ? $typeMap[$src] : [],
            ];
        }
        return $out;
    }

    private function joinKv(array $map): string
    {
        if (empty($map)) {
            return '(none)';
        }
        $parts = [];
        foreach ($map as $k => $v) {
            $parts[] = $k . '=' . (int) $v;
        }
        return implode(', ', $parts);
    }
}
