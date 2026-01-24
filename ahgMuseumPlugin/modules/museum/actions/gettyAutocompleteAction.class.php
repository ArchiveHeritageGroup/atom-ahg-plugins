<?php

/**
 * Getty Vocabulary Autocomplete Action.
 *
 * AJAX endpoint for searching Getty vocabularies (AAT, TGN, ULAN).
 * Returns JSON results for use in form autocomplete fields.
 *
 * @package ahgMuseumPlugin
 */
class museumGettyAutocompleteAction extends sfAction
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
            $results = $this->searchGetty($query, $vocabulary, $limit);

            return $this->renderText(json_encode([
                'success' => true,
                'query' => $query,
                'vocabulary' => $vocabulary,
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
     * Search Getty vocabulary via SPARQL.
     */
    private function searchGetty(string $term, string $vocabulary, int $limit): array
    {
        $vocabLabels = [
            'aat' => 'Art & Architecture Thesaurus',
            'tgn' => 'Thesaurus of Geographic Names',
            'ulan' => 'Union List of Artist Names',
        ];

        // Escape for SPARQL and regex
        $termEscaped = addslashes($term);
        $termRegex = strtolower(preg_quote($term, '/'));

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
        usort($results, function($a, $b) use ($term) {
            $aLabel = strtolower($a['label']);
            $bLabel = strtolower($b['label']);
            $termLower = strtolower($term);
            
            // Exact match first
            if ($aLabel === $termLower && $bLabel !== $termLower) return -1;
            if ($bLabel === $termLower && $aLabel !== $termLower) return 1;
            
            // Starts with second
            $aStarts = strpos($aLabel, $termLower) === 0;
            $bStarts = strpos($bLabel, $termLower) === 0;
            if ($aStarts && !$bStarts) return -1;
            if ($bStarts && !$aStarts) return 1;
            
            // Alphabetical
            return strcmp($aLabel, $bLabel);
        });

        return array_slice($results, 0, $limit);
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
