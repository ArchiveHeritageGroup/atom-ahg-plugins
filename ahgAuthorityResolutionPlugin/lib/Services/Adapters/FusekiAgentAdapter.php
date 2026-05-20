<?php

/**
 * FusekiAgentAdapter - service for AtoM Heratio
 *
 * Candidate adapter that searches the AHG Fuseki dataset (/openric-model) for
 * RiC agent nodes - rico:Agent and its subclasses rico:Person /
 * rico:CorporateBody / rico:Group / rico:Family - whose name contains the
 * query string.
 *
 * RiC models names two ways and this adapter accepts both, plus generic
 * label fallbacks, via a UNION:
 *   - reified name objects: ?agent rico:hasOrHadName|rico:hasOrHadAgentName
 *       ?nameNode . ?nameNode rico:textualValue ?name
 *   - the direct rico:name datatype property: ?agent rico:name ?name
 *   - rdfs:label / skos:prefLabel as a last resort
 *
 * As of this writing the /openric-model dataset holds only the RiC ontology
 * definition (no agent instances), so search() returns [] cleanly. Once
 * agent instances are loaded the same query will surface them with no code
 * change. Fuseki being down or the dataset being empty both yield [] - the
 * adapter never throws.
 *
 * Scoring is NOT done here; the adapter emits raw candidate rows and
 * CandidateGeneratorService computes similarity uniformly so MySQL and
 * Fuseki candidates rank against each other on the same scale.
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

require_once __DIR__ . '/../FusekiUpdateService.php';

use AtomFramework\Services\AuthorityResolution\FusekiUpdateService;

class FusekiAgentAdapter implements CandidateAdapterInterface
{
    private const SUPPORTED_TYPES = ['PERSON', 'ORG'];

    public function supports(string $entityType): bool
    {
        return in_array($entityType, self::SUPPORTED_TYPES, true);
    }

    /**
     * Query the Fuseki dataset for RiC agent candidates whose name contains
     * $query (case-insensitive). Returns at most $limit rows. Any failure
     * (Fuseki unreachable, empty dataset, malformed JSON) yields [].
     *
     * @return array<int, array{source:string, authority_id:?int, fuseki_uri:?string, display_name:string}>
     */
    public function search(string $query, string $entityType, int $limit): array
    {
        if (!$this->supports($entityType)) {
            return [];
        }
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $limit = $limit > 0 ? $limit : 10;

        $needle = $this->escapeSparqlString($query);

        $sparql = <<<SPARQL
PREFIX rico: <https://www.ica.org/standards/RiC/ontology#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
SELECT DISTINCT ?s ?name WHERE {
  ?s a ?cls .
  VALUES ?cls { rico:Agent rico:Person rico:CorporateBody rico:Group rico:Family }
  {
    ?s rico:hasOrHadAgentName ?nameNode .
    ?nameNode rico:textualValue ?name .
  } UNION {
    ?s rico:hasOrHadName ?nameNode .
    ?nameNode rico:textualValue ?name .
  } UNION {
    ?s rico:name ?name .
  } UNION {
    ?s rdfs:label ?name .
  } UNION {
    ?s skos:prefLabel ?name .
  }
  FILTER(CONTAINS(LCASE(STR(?name)), LCASE("{$needle}")))
}
ORDER BY STRLEN(STR(?name))
LIMIT {$limit}
SPARQL;

        try {
            $fuseki = new FusekiUpdateService();
            $res = $fuseki->executeQuery($sparql);
            if (empty($res['ok']) || !isset($res['json']['results']['bindings'])) {
                return [];
            }

            $out = [];
            $seen = [];
            foreach ($res['json']['results']['bindings'] as $binding) {
                $uri = $binding['s']['value'] ?? null;
                $name = $binding['name']['value'] ?? null;
                if ($uri === null || $name === null || $name === '') {
                    continue;
                }
                if (isset($seen[$uri])) {
                    continue;
                }
                $seen[$uri] = true;
                $out[] = [
                    'source' => 'fuseki_agent',
                    'authority_id' => null,
                    'fuseki_uri' => (string) $uri,
                    'display_name' => (string) $name,
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Escape a user-supplied string for safe interpolation inside a
     * double-quoted SPARQL string literal.
     */
    private function escapeSparqlString(string $value): string
    {
        return str_replace(
            ['\\', '"', "\n", "\r", "\t"],
            ['\\\\', '\\"', '\\n', '\\r', '\\t'],
            $value
        );
    }
}
