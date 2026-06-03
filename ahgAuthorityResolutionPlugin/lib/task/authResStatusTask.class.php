<?php

/**
 * authResStatusTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 10 (CLI consolidation) status dashboard. Aggregates the
 * authority-resolution working set into a single human-readable summary so
 * an operator can see at a glance how much of the queue is parked, how many
 * candidates have been generated, how many decisions have been provenance-
 * written to Fuseki, and how warm the external-authority lookup cache is.
 *
 * Pure SELECT against MySQL plus two SPARQL COUNT calls against the
 * configured Fuseki query endpoint (resolved via FusekiUpdateService so the
 * existing ahg_settings keys stay the single source of truth).
 *
 * Usage:
 *   php symfony auth-res:status
 *   php symfony auth-res:status --json
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// Explicit requires: Symfony 1.4 has no PSR-4 autoloader for our namespaced
// plugin classes. Mirror the pattern from authResScoreEvidenceTask.
require_once __DIR__ . '/../Services/FusekiUpdateService.php';

use AtomFramework\Services\AuthorityResolution\FusekiUpdateService;
use Illuminate\Database\Capsule\Manager as DB;

class authResStatusTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('json', null, sfCommandOption::PARAMETER_NONE, 'Emit machine-readable JSON instead of the formatted table'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'status';
        $this->briefDescription = 'Summarise the authority-resolution working set (mentions, candidates, decisions, parked, feedback, cache, Fuseki).';
        $this->detailedDescription = <<<EOF
Task 10 of the AHG Authority Resolution Engine. Aggregates:

  - ahg_mention rows by state + entity_type
  - ahg_mention_candidate count + avg per mention
  - ahg_mention_decision rows by decision_type
  - ahg_mention_park rows + new_candidate_available flag count
  - ahg_ner_feedback rows + unexported count
  - ahg_authority_lookup_cache rows by source
  - Fuseki named-graph triple counts:
      urn:atom:auth-res:graph:decisions
      urn:atom:auth-res:graph:field-provenance

Pure SELECT against MySQL. SPARQL COUNT against the Fuseki query endpoint
(resolved from ahg_settings via FusekiUpdateService).

Usage:
  php symfony auth-res:status
  php symfony auth-res:status --json
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $data = [
            'mention_by_state' => $this->countBy('ahg_mention', 'state'),
            'mention_by_entity_type' => $this->countBy('ahg_mention', 'entity_type'),
            'mention_candidates' => $this->candidateStats(),
            'decision_by_type' => $this->countBy('ahg_mention_decision', 'decision_type'),
            'park' => $this->parkStats(),
            'ner_feedback' => $this->feedbackStats(),
            'cache_by_source' => $this->cacheBySource(),
            'fuseki' => $this->fusekiCounts(),
        ];

        if (!empty($options['json'])) {
            $this->log(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->renderHuman($data);
        return 0;
    }

    /**
     * GROUP BY $column -> ordered map. Empty result returns an empty array.
     *
     * @return array<string,int>
     */
    private function countBy(string $table, string $column): array
    {
        $rows = DB::table($table)
            ->select($column, DB::raw('COUNT(*) as c'))
            ->groupBy($column)
            ->orderByDesc('c')
            ->get();
        $out = [];
        foreach ($rows as $r) {
            $key = $r->{$column} !== null && $r->{$column} !== '' ? (string) $r->{$column} : '(null)';
            $out[$key] = (int) $r->c;
        }
        return $out;
    }

    /**
     * @return array{count:int, mentions_with_candidates:int, avg_per_mention:float}
     */
    private function candidateStats(): array
    {
        $count = (int) DB::table('ahg_mention_candidate')->count();
        $mentions = (int) DB::table('ahg_mention_candidate')
            ->distinct()
            ->count('mention_id');
        $avg = $mentions > 0 ? round($count / $mentions, 2) : 0.0;
        return [
            'count' => $count,
            'mentions_with_candidates' => $mentions,
            'avg_per_mention' => $avg,
        ];
    }

    /**
     * @return array{count:int, new_candidate_available:int}
     */
    private function parkStats(): array
    {
        $count = (int) DB::table('ahg_mention_park')->count();
        $newCand = (int) DB::table('ahg_mention_park')
            ->where('new_candidate_available', '=', 1)
            ->count();
        return ['count' => $count, 'new_candidate_available' => $newCand];
    }

    /**
     * @return array{count:int, unexported:int}
     */
    private function feedbackStats(): array
    {
        $count = (int) DB::table('ahg_ner_feedback')->count();
        $unexp = (int) DB::table('ahg_ner_feedback')
            ->where('training_exported', '=', 0)
            ->count();
        return ['count' => $count, 'unexported' => $unexp];
    }

    /**
     * @return array{count:int, by_source:array<string,int>}
     */
    private function cacheBySource(): array
    {
        $rows = DB::table('ahg_authority_lookup_cache')
            ->select('source', DB::raw('COUNT(*) as c'))
            ->groupBy('source')
            ->orderByDesc('c')
            ->get();
        $bySource = [];
        $total = 0;
        foreach ($rows as $r) {
            $key = $r->source !== null && $r->source !== '' ? (string) $r->source : '(null)';
            $bySource[$key] = (int) $r->c;
            $total += (int) $r->c;
        }
        return ['count' => $total, 'by_source' => $bySource];
    }

    /**
     * @return array{decisions_graph:array{uri:string, triples:?int, error:?string}, field_provenance_graph:array{uri:string, triples:?int, error:?string}}
     */
    private function fusekiCounts(): array
    {
        $decisionsGraph = $this->setting('authority_resolution.decisions_graph_uri', 'urn:atom:auth-res:graph:decisions');
        $fieldGraph = $this->setting('authority_resolution.field_provenance_graph_uri', 'urn:atom:auth-res:graph:field-provenance');

        $fuseki = new FusekiUpdateService();

        return [
            'decisions_graph' => $this->graphCount($fuseki, (string) $decisionsGraph),
            'field_provenance_graph' => $this->graphCount($fuseki, (string) $fieldGraph),
        ];
    }

    /**
     * @return array{uri:string, triples:?int, error:?string}
     */
    private function graphCount(FusekiUpdateService $fuseki, string $graphUri): array
    {
        $query = sprintf(
            'SELECT (COUNT(*) AS ?c) WHERE { GRAPH <%s> { ?s ?p ?o } }',
            $this->escapeIri($graphUri)
        );
        $res = $fuseki->executeQuery($query);
        if (!$res['ok']) {
            return ['uri' => $graphUri, 'triples' => null, 'error' => $res['error']];
        }
        $count = null;
        if (isset($res['json']['results']['bindings'][0]['c']['value'])) {
            $count = (int) $res['json']['results']['bindings'][0]['c']['value'];
        }
        return ['uri' => $graphUri, 'triples' => $count, 'error' => null];
    }

    private function escapeIri(string $iri): string
    {
        // SPARQL IRI escaping: forbid <, >, ", {, }, |, ^, `, \, and whitespace.
        return str_replace(['<', '>', '"', '\\', "\n", "\r", "\t"], '', $iri);
    }

    private function setting(string $key, $default = null)
    {
        try {
            $row = DB::table('ahg_settings')->where('setting_key', $key)->value('setting_value');
            return ($row !== null && $row !== '') ? $row : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function renderHuman(array $data): void
    {
        $this->log('Authority Resolution status @ ' . date('Y-m-d H:i:s'));
        $this->log(str_repeat('=', 60));

        $this->log('ahg_mention rows by state:');
        $this->logMap($data['mention_by_state']);

        $this->log('');
        $this->log('ahg_mention rows by entity_type:');
        $this->logMap($data['mention_by_entity_type']);

        $this->log('');
        $cand = $data['mention_candidates'];
        $this->log(sprintf(
            'ahg_mention_candidate rows: %d (avg %s per mention, across %d mention(s) with candidates)',
            $cand['count'],
            number_format($cand['avg_per_mention'], 2),
            $cand['mentions_with_candidates']
        ));

        $this->log('');
        $this->log('ahg_mention_decision rows by type:');
        $this->logMap($data['decision_by_type']);

        $this->log('');
        $park = $data['park'];
        $this->log(sprintf(
            'ahg_mention_park rows: %d (new_candidate_available: %d)',
            $park['count'],
            $park['new_candidate_available']
        ));

        $fb = $data['ner_feedback'];
        $this->log(sprintf(
            'ahg_ner_feedback rows: %d (unexported: %d)',
            $fb['count'],
            $fb['unexported']
        ));

        $cache = $data['cache_by_source'];
        $this->log(sprintf(
            'ahg_authority_lookup_cache rows: %d (by source: %s)',
            $cache['count'],
            $this->joinKv($cache['by_source'])
        ));

        $this->log('');
        $this->log('Fuseki named-graph triple counts:');
        foreach (['decisions_graph' => 'decisions', 'field_provenance_graph' => 'field-provenance'] as $k => $label) {
            $g = $data['fuseki'][$k];
            if ($g['triples'] === null) {
                $this->log(sprintf('    %s (%s): ERROR (%s)', $label, $g['uri'], $g['error'] ?? 'unknown'));
            } else {
                $this->log(sprintf('    %s (%s): %d triples', $label, $g['uri'], $g['triples']));
            }
        }
    }

    private function logMap(array $map): void
    {
        if (empty($map)) {
            $this->log('    (none)');
            return;
        }
        foreach ($map as $k => $v) {
            $this->log(sprintf('    %s: %d', $k, (int) $v));
        }
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
