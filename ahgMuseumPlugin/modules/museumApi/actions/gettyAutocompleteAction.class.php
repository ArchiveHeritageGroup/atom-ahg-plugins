<?php

/**
 * Getty Autocomplete Action.
 *
 * AJAX endpoint for searching Getty vocabularies (AAT, TGN, ULAN).
 * Returns JSON results for use in form autocomplete fields.
 */
class gettyAutocompleteAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = trim($request->getParameter('q', ''));
        $vocabulary = $request->getParameter('vocabulary', 'aat');
        $limit = min((int)$request->getParameter('limit', 10), 25);

        if (strlen($query) < 2) {
            echo json_encode(['results' => [], 'error' => 'Query too short']);
            return sfView::NONE;
        }

        $results = $this->searchGetty($query, $vocabulary, $limit);

        echo json_encode([
            'query' => $query,
            'vocabulary' => $vocabulary,
            'results' => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return sfView::NONE;
    }

    /**
     * Search Getty vocabulary via SPARQL.
     */
    private function searchGetty(string $term, string $vocabulary, int $limit): array
    {
        $graphs = [
            'aat' => 'http://vocab.getty.edu/aat/',
            'tgn' => 'http://vocab.getty.edu/tgn/',
            'ulan' => 'http://vocab.getty.edu/ulan/',
        ];

        $graph = $graphs[$vocabulary] ?? $graphs['aat'];
        $termEscaped = str_replace('"', '\\"', $term);

        // SPARQL query
        $sparql = <<<SPARQL
SELECT DISTINCT ?subject ?prefLabel ?scopeNote WHERE {
    ?subject a skos:Concept ;
             skos:inScheme <{$graph}> ;
             gvp:prefLabelGVP/xl:literalForm ?prefLabel .
    OPTIONAL { ?subject skos:scopeNote/rdf:value ?scopeNote }
    FILTER(REGEX(?prefLabel, "{$termEscaped}", "i"))
}
ORDER BY ?prefLabel
LIMIT {$limit}
SPARQL;

        $url = 'https://vocab.getty.edu/sparql?query=' . urlencode($sparql) . '&format=json';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/sparql-results+json\r\nUser-Agent: AtoM-Museum-Plugin/1.0",
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                return [];
            }

            $data = json_decode($response, true);
            if (empty($data['results']['bindings'])) {
                return [];
            }

            $results = [];
            foreach ($data['results']['bindings'] as $binding) {
                $uri = $binding['subject']['value'] ?? '';
                preg_match('/(\d+)$/', $uri, $matches);
                $id = $matches[1] ?? '';

                $results[] = [
                    'uri' => $uri,
                    'id' => $id,
                    'label' => $binding['prefLabel']['value'] ?? '',
                    'scopeNote' => isset($binding['scopeNote']) ? $this->truncate($binding['scopeNote']['value'], 150) : '',
                    'vocabulary' => strtoupper($vocabulary),
                ];
            }

            return $results;
        } catch (Exception $e) {
            return [];
        }
    }

    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - 3) . '...';
    }
}
