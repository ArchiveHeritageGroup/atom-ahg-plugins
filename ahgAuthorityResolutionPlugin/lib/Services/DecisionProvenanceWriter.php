<?php

/**
 * DecisionProvenanceWriter — service for AtoM Heratio
 *
 * Writes RDF-Star provenance for every authority-resolution decision to the
 * AtoM Heratio Fuseki dataset (default named graph
 * urn:atom:auth-res:graph:decisions). The reified assertion captures the
 * outcome (mention → linkedTo → actor/term, or mention → rejected, etc.),
 * with PROV-O triples annotating who/when and auth_res:* predicates
 * carrying the system's original confidence + candidates visible at
 * decision time.
 *
 * Mirror of the Laravel-side AhgAuthorityResolution\Services\DecisionProvenanceWriter;
 * RDF-Star shape kept identical so federation queries can UNION across the
 * two datasets if both sides write to the same Fuseki instance.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later.
 */

namespace AtomFramework\Services\AuthorityResolution;

use Illuminate\Database\Capsule\Manager as DB;

class DecisionProvenanceWriter
{
    public const DEFAULT_GRAPH_URI = 'urn:atom:auth-res:graph:decisions';
    public const NS_PROV = 'http://www.w3.org/ns/prov#';
    public const NS_AUTH_RES = 'https://heratio.theahg.co.za/ontology/auth-res#';

    private const PLACE_TYPES = ['GPE', 'LOC', 'PLACE', 'ISAD_PLACE'];

    /** @var FusekiUpdateService */
    private $sparql;

    /** @var string Base URI for atom-side entities (actor, place, mention, user) */
    private $baseUri;

    public function __construct(FusekiUpdateService $sparql, ?string $baseUri = null)
    {
        $this->sparql = $sparql;
        $this->baseUri = $baseUri ?: $this->resolveBaseUri();
    }

    public function write(int $decisionId, ?string $graphUri = null): array
    {
        $decision = $this->loadDecision($decisionId);
        if (!$decision) {
            return ['ok' => false, 'error' => "decision #{$decisionId} not found"];
        }

        $graphUri = $graphUri ?: $this->loadGraphUri();
        $turtle = $this->buildTurtleBody($decision);
        $sparqlUpdate = $this->buildPrefixes() . "\nINSERT DATA {\n  GRAPH <{$graphUri}> {\n{$turtle}\n  }\n}";

        $result = $this->sparql->executeUpdate($sparqlUpdate);

        if (!empty($result['ok'])) {
            DB::table('ahg_mention_decision')
                ->where('id', $decisionId)
                ->update(['fuseki_graph_uri' => $graphUri]);
            return [
                'ok' => true,
                'graph' => $graphUri,
                'turtle' => $turtle,
                'status' => $result['status'] ?? 200,
            ];
        }

        return [
            'ok' => false,
            'graph' => $graphUri,
            'turtle' => $turtle,
            'status' => $result['status'] ?? 0,
            'error' => $result['error'] ?? 'unknown',
        ];
    }

    public function buildTurtleBody(object $decision): string
    {
        $base = rtrim($this->baseUri, '/');
        $mentionUri = "<{$base}/auth-res/mention/{$decision->mention_id}>";
        $userUri = "<{$base}/user/{$decision->archivist_user_id}>";
        $assertion = $this->buildAssertion($decision, $mentionUri, $base);
        $timestamp = $this->formatTimestamp((string) $decision->decided_at);

        $reified = "<< {$assertion} >>";

        $lines = [];
        $lines[] = "{$reified}";
        $lines[] = "    prov:wasAttributedTo {$userUri} ;";
        $lines[] = "    prov:generatedAtTime \"{$timestamp}\"^^xsd:dateTime ;";
        $lines[] = "    auth_res:decisionType " . $this->literal($decision->decision_type) . " ;";
        $lines[] = "    auth_res:mentionValue " . $this->literal($decision->entity_value ?? '') . " ;";
        $hasConfidence = ($decision->original_system_top_score !== null);
        $lines[] = "    auth_res:mentionEntityType " . $this->literal($decision->entity_type ?? '') . ($hasConfidence ? ' ;' : ' .');
        if ($hasConfidence) {
            $lines[] = "    auth_res:originalSystemConfidence \"{$decision->original_system_top_score}\"^^xsd:decimal .";
        }

        $candidates = $this->decodeJson($decision->candidates_visible_snapshot ?? null);
        $candidatesTurtle = '';
        if (is_array($candidates) && !empty($candidates)) {
            $candidateUris = [];
            foreach ($candidates as $c) {
                $cid = $c['candidate_id'] ?? $c['id'] ?? null;
                if (!$cid) {
                    continue;
                }
                $candidateUris[] = "<{$base}/auth-res/candidate/{$cid}>";
            }
            if (!empty($candidateUris)) {
                $lines[count($lines) - 1] = rtrim($lines[count($lines) - 1], '.') . ';';
                $lines[] = '    auth_res:hadCandidate ' . implode(', ', $candidateUris) . ' .';
            }
            $candidatesTurtle = "\n" . $this->buildCandidateTriples($candidates, $base);
        }

        $evidence = $this->decodeJson($decision->evidence_snapshot ?? null);
        $evidenceTurtle = '';
        if (is_array($evidence) && !empty($evidence)) {
            $evidenceTurtle = "\n{$reified}\n    auth_res:evidenceSnapshot " . $this->literal(json_encode($evidence, JSON_UNESCAPED_UNICODE)) . " .";
        }

        return implode("\n", $lines) . $candidatesTurtle . $evidenceTurtle;
    }

    private function buildAssertion(object $decision, string $mentionUri, string $base): string
    {
        switch ($decision->decision_type) {
            case 'link':
            case 'link_different':
                $authorityUri = $this->authorityUri($decision, $base);
                $predicate = $decision->decision_type === 'link_different'
                    ? 'auth_res:linkedToDifferent'
                    : 'auth_res:linkedTo';
                return "{$mentionUri} {$predicate} {$authorityUri}";
            case 'create_new':
                $authorityUri = $this->authorityUri($decision, $base);
                return "{$mentionUri} auth_res:linkedToNew {$authorityUri}";
            case 'park':
                return "{$mentionUri} auth_res:parked \"true\"^^xsd:boolean";
            case 'reject':
                return "{$mentionUri} auth_res:rejected \"true\"^^xsd:boolean";
            default:
                return "{$mentionUri} auth_res:decision " . $this->literal($decision->decision_type);
        }
    }

    private function authorityUri(object $decision, string $base): string
    {
        $id = $decision->chosen_authority_id;
        if ($id === null) {
            return "<{$base}/auth-res/null-authority>";
        }
        if (in_array($decision->entity_type, self::PLACE_TYPES, true)) {
            return "<{$base}/place/{$id}>";
        }
        return "<{$base}/actor/{$id}>";
    }

    private function buildCandidateTriples(array $candidates, string $base): string
    {
        $blocks = [];
        foreach ($candidates as $c) {
            $cid = $c['candidate_id'] ?? $c['id'] ?? null;
            if (!$cid) {
                continue;
            }
            $uri = "<{$base}/auth-res/candidate/{$cid}>";
            $parts = [];
            if (isset($c['rank']) || isset($c['rank_position'])) {
                $rank = (int) ($c['rank'] ?? $c['rank_position']);
                $parts[] = "auth_res:rank \"{$rank}\"^^xsd:integer";
            }
            if (!empty($c['display_name'])) {
                $parts[] = 'auth_res:displayName ' . $this->literal((string) $c['display_name']);
            }
            if (!empty($c['source']) || !empty($c['candidate_source'])) {
                $src = (string) ($c['source'] ?? $c['candidate_source']);
                $parts[] = 'auth_res:source ' . $this->literal($src);
            }
            if (isset($c['name_similarity_score']) || isset($c['nameSimilarity'])) {
                $score = (float) ($c['name_similarity_score'] ?? $c['nameSimilarity']);
                $parts[] = "auth_res:nameSimilarity \"{$score}\"^^xsd:decimal";
            }
            if (empty($parts)) {
                continue;
            }
            $blocks[] = $uri . "\n    " . implode(" ;\n    ", $parts) . " .";
        }
        return implode("\n\n", $blocks);
    }

    private function buildPrefixes(): string
    {
        return implode("\n", [
            'PREFIX prov: <' . self::NS_PROV . '>',
            'PREFIX auth_res: <' . self::NS_AUTH_RES . '>',
            'PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>',
        ]);
    }

    private function loadDecision(int $decisionId)
    {
        return DB::table('ahg_mention_decision as d')
            ->join('ahg_mention as m', 'm.id', '=', 'd.mention_id')
            ->join('ahg_ner_entity as n', 'n.id', '=', 'm.ner_entity_id')
            ->where('d.id', $decisionId)
            ->first([
                'd.id',
                'd.mention_id',
                'd.decision_type',
                'd.chosen_candidate_id',
                'd.chosen_authority_id',
                'd.original_system_top_score',
                'd.archivist_user_id',
                'd.decided_at',
                'd.fuseki_graph_uri',
                'd.evidence_snapshot',
                'd.candidates_visible_snapshot',
                'm.entity_type',
                'n.entity_value',
            ]);
    }

    private function loadGraphUri(): string
    {
        try {
            $row = DB::table('ahg_settings')
                ->where('setting_key', 'authority_resolution.decisions_graph_uri')
                ->value('setting_value');
            if (is_string($row) && trim($row) !== '') {
                return $row;
            }
        } catch (\Throwable $e) {
            // fall through to default
        }
        return self::DEFAULT_GRAPH_URI;
    }

    /** Pull base URI from settings, falling back to a sensible localhost default. */
    private function resolveBaseUri(): string
    {
        try {
            $row = DB::table('ahg_settings')
                ->where('setting_key', 'site_base_url')
                ->value('setting_value');
            if (is_string($row) && trim($row) !== '') {
                return rtrim($row, '/');
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return 'https://psis.theahg.co.za';
    }

    private function decodeJson(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function formatTimestamp(string $mysqlDateTime): string
    {
        try {
            $dt = new \DateTimeImmutable($mysqlDateTime, new \DateTimeZone('UTC'));
            return $dt->format('Y-m-d\TH:i:s\Z');
        } catch (\Throwable $e) {
            return gmdate('Y-m-d\TH:i:s\Z');
        }
    }

    private function literal(?string $s): string
    {
        if ($s === null) {
            return '""';
        }
        $escaped = str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $s
        );
        return '"' . $escaped . '"';
    }
}
