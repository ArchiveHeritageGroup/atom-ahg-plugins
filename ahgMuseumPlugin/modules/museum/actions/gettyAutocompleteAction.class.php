<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Getty Vocabulary Autocomplete Action.
 *
 * AJAX endpoint for searching Getty vocabularies (AAT, TGN, ULAN).
 * Searches local cache first (getty_aat_cache table) for instant results,
 * falls back to live Getty SPARQL API if no local matches found.
 *
 * Local cache populated via: php symfony museum:aat-sync
 *
 * @package ahgMuseumPlugin
 */
class museumGettyAutocompleteAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = trim($request->getParameter('query', $request->getParameter('q', '')));
        $vocabulary = $request->getParameter('vocabulary', 'aat');
        $category = $request->getParameter('category');
        $limit = min((int) $request->getParameter('limit', 10), 25);

        if (strlen($query) < 2) {
            return $this->renderText(json_encode([
                'success' => true,
                'results' => [],
                'message' => 'Query too short (minimum 2 characters)',
            ]));
        }

        try {
            $results = [];
            $source = 'getty';

            // Try local cache first (AAT only — TGN/ULAN are not cached)
            if ($vocabulary === 'aat') {
                $results = $this->searchLocalCache($query, $category, $limit);

                if (!empty($results)) {
                    $source = 'local_cache';
                }
            }

            // Fall back to Getty SPARQL if no local results
            if (empty($results)) {
                $results = $this->searchGetty($query, $vocabulary, $limit);
                $source = 'getty_api';
            }

            return $this->renderText(json_encode([
                'success' => true,
                'query' => $query,
                'vocabulary' => $vocabulary,
                'source' => $source,
                'results' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]));
        }
    }

    /**
     * Search local getty_aat_cache table.
     */
    private function searchLocalCache(string $term, ?string $category, int $limit): array
    {
        // Check table exists
        try {
            $tableCheck = DB::select("SHOW TABLES LIKE 'getty_aat_cache'");
            if (empty($tableCheck)) {
                return [];
            }
        } catch (\Exception $e) {
            return [];
        }

        $q = DB::table('getty_aat_cache')
            ->select('aat_id', 'uri', 'pref_label', 'scope_note', 'broader_label', 'category');

        // Category filter
        if ($category) {
            $q->where('category', $category);
        }

        // Search: try MATCH first for relevance ranking, then LIKE as fallback
        $termSafe = addcslashes($term, '%_');

        // Use LIKE for consistent results (FULLTEXT requires minimum word length)
        $q->where(function ($query) use ($termSafe) {
            $query->where('pref_label', 'LIKE', '%' . $termSafe . '%');
        });

        // Order: exact match first, starts-with second, then alphabetical
        $q->orderByRaw("
            CASE
                WHEN LOWER(pref_label) = LOWER(?) THEN 0
                WHEN LOWER(pref_label) LIKE LOWER(CONCAT(?, '%')) THEN 1
                ELSE 2
            END,
            pref_label ASC
        ", [$term, $term]);

        $rows = $q->limit($limit)->get();

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'id' => $row->aat_id,
                'uri' => $row->uri,
                'label' => $row->pref_label,
                'scopeNote' => $row->scope_note,
                'broader' => $row->broader_label,
                'vocabulary' => 'aat',
                'vocabularyLabel' => 'Art & Architecture Thesaurus',
            ];
        }

        return $results;
    }

    /**
     * Search Getty vocabulary via SPARQL (fallback).
     */
    private function searchGetty(string $term, string $vocabulary, int $limit): array
    {
        $vocabLabels = [
            'aat' => 'Art & Architecture Thesaurus',
            'tgn' => 'Thesaurus of Geographic Names',
            'ulan' => 'Union List of Artist Names',
        ];

        // Escape for SPARQL
        $termEscaped = addslashes($term);

        // Build vocabulary-specific SPARQL query
        $sparql = $this->buildSparqlQuery($vocabulary, $termEscaped, $limit * 3);

        $endpoint = 'http://vocab.getty.edu/sparql';
        $url = $endpoint . '?' . http_build_query([
            'query' => $sparql,
            'format' => 'json',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 15,
                'header' => "Accept: application/sparql-results+json\r\n",
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if (false === $response) {
            error_log("Getty SPARQL error for query: {$term} in {$vocabulary}");
            return [];
        }

        $data = json_decode($response, true);

        if (!isset($data['results']['bindings'])) {
            return [];
        }

        $results = [];
        $seen = [];

        foreach ($data['results']['bindings'] as $binding) {
            $uri = $binding['subject']['value'] ?? '';
            $id = basename($uri);

            // Skip duplicates
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $label = $binding['prefLabel']['value'] ?? $binding['label']['value'] ?? '';

            // Only include English labels
            $labelLang = $binding['prefLabel']['xml:lang'] ?? $binding['label']['xml:lang'] ?? '';
            if ($labelLang && $labelLang !== 'en') {
                continue;
            }

            // Skip if label doesn't contain the search term (case insensitive)
            if (stripos($label, $term) === false) {
                continue;
            }

            $results[] = [
                'id' => $id,
                'uri' => $uri,
                'label' => $label,
                'scopeNote' => $binding['scopeNote']['value'] ?? null,
                'broader' => $binding['broaderLabel']['value'] ?? $binding['parentLabel']['value'] ?? null,
                'vocabulary' => $vocabulary,
                'vocabularyLabel' => $vocabLabels[$vocabulary] ?? $vocabulary,
            ];

            if (count($results) >= $limit) {
                break;
            }
        }

        // Sort by relevance (exact match first, then starts with, then contains)
        usort($results, function ($a, $b) use ($term) {
            $aLabel = strtolower($a['label']);
            $bLabel = strtolower($b['label']);
            $termLower = strtolower($term);

            // Exact match first
            if ($aLabel === $termLower && $bLabel !== $termLower) {
                return -1;
            }
            if ($bLabel === $termLower && $aLabel !== $termLower) {
                return 1;
            }

            // Starts with second
            $aStarts = strpos($aLabel, $termLower) === 0;
            $bStarts = strpos($bLabel, $termLower) === 0;
            if ($aStarts && !$bStarts) {
                return -1;
            }
            if ($bStarts && !$aStarts) {
                return 1;
            }

            // Alphabetical
            return strcmp($aLabel, $bLabel);
        });

        // Cache results locally for future searches (write-through)
        if ($vocabulary === 'aat' && !empty($results)) {
            $this->cacheResults($results);
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * Cache Getty API results in the local table for future searches.
     */
    private function cacheResults(array $results): void
    {
        try {
            $tableCheck = DB::select("SHOW TABLES LIKE 'getty_aat_cache'");
            if (empty($tableCheck)) {
                return;
            }

            foreach ($results as $result) {
                $aatId = $result['id'] ?? '';
                if (empty($aatId) || !is_numeric($aatId)) {
                    continue;
                }

                $exists = DB::table('getty_aat_cache')->where('aat_id', $aatId)->exists();
                if ($exists) {
                    continue;
                }

                DB::table('getty_aat_cache')->insert([
                    'aat_id' => $aatId,
                    'uri' => $result['uri'] ?? 'http://vocab.getty.edu/aat/' . $aatId,
                    'pref_label' => $result['label'] ?? '',
                    'scope_note' => $result['scopeNote'] ?? null,
                    'broader_label' => $result['broader'] ?? null,
                    'broader_id' => null,
                    'category' => 'general',
                    'synced_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            // Silent fail — caching is best-effort
            error_log('Getty cache write error: ' . $e->getMessage());
        }
    }

    /**
     * Build vocabulary-specific SPARQL query.
     */
    private function buildSparqlQuery(string $vocabulary, string $term, int $limit): string
    {
        switch ($vocabulary) {
            case 'tgn':
                // TGN - Geographic names
                return <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>

SELECT DISTINCT ?subject ?label ?scopeNote ?parentLabel WHERE {
  ?subject a gvp:AdminPlaceConcept ;
           gvp:prefLabelGVP/xl:literalForm ?label .
  FILTER(CONTAINS(LCASE(?label), LCASE("{$term}")))
  OPTIONAL { ?subject skos:scopeNote/rdf:value ?scopeNote }
  OPTIONAL {
    ?subject gvp:broaderPreferred ?parent .
    ?parent gvp:prefLabelGVP/xl:literalForm ?parentLabel
  }
  FILTER(lang(?label) = "en" || lang(?label) = "")
}
ORDER BY ?label
LIMIT {$limit}
SPARQL;

            case 'ulan':
                // ULAN - Artist names
                return <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
PREFIX foaf: <http://xmlns.com/foaf/0.1/>

SELECT DISTINCT ?subject ?label ?scopeNote ?bio WHERE {
  ?subject a gvp:PersonConcept ;
           gvp:prefLabelGVP/xl:literalForm ?label .
  FILTER(CONTAINS(LCASE(?label), LCASE("{$term}")))
  OPTIONAL { ?subject skos:scopeNote/rdf:value ?scopeNote }
  OPTIONAL { ?subject gvp:biographyPreferred/gvp:estStart ?bio }
  FILTER(lang(?label) = "en" || lang(?label) = "")
}
ORDER BY ?label
LIMIT {$limit}
SPARQL;

            case 'aat':
            default:
                // AAT - Art & Architecture Thesaurus - search in labels only
                return <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT DISTINCT ?subject ?prefLabel ?scopeNote ?broaderLabel WHERE {
  ?subject a gvp:Concept ;
           skos:inScheme <http://vocab.getty.edu/aat/> ;
           gvp:prefLabelGVP/xl:literalForm ?prefLabel .
  FILTER(CONTAINS(LCASE(?prefLabel), LCASE("{$term}")))
  OPTIONAL { ?subject skos:scopeNote/rdf:value ?scopeNote }
  OPTIONAL {
    ?subject gvp:broaderPreferred ?broader .
    ?broader gvp:prefLabelGVP/xl:literalForm ?broaderLabel
  }
  FILTER(lang(?prefLabel) = "en" || lang(?prefLabel) = "")
}
ORDER BY ?prefLabel
LIMIT {$limit}
SPARQL;
        }
    }
}
