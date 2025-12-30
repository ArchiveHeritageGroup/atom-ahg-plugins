<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Getty;

use arMuseumMetadataPlugin\Contracts\GettyVocabularyInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Getty SPARQL Service.
 *
 * Queries Getty Vocabularies via their public SPARQL endpoint.
 * Supports AAT, TGN, and ULAN vocabularies.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 *
 * @see http://vocab.getty.edu/sparql
 * @see https://www.getty.edu/research/tools/vocabularies/lod/
 */
class GettySparqlService implements GettyVocabularyInterface
{
    /** SPARQL endpoint URL */
    private const SPARQL_ENDPOINT = 'http://vocab.getty.edu/sparql';

    /** Base URI for Getty vocabularies */
    private const BASE_URI = 'http://vocab.getty.edu/';

    /** Vocabulary graph URIs */
    private const GRAPHS = [
        'aat' => 'http://vocab.getty.edu/aat/',
        'tgn' => 'http://vocab.getty.edu/tgn/',
        'ulan' => 'http://vocab.getty.edu/ulan/',
    ];

    /** Vocabulary dataset URIs for SPARQL FROM clause */
    private const DATASETS = [
        'aat' => 'http://vocab.getty.edu/dataset/aat',
        'tgn' => 'http://vocab.getty.edu/dataset/tgn',
        'ulan' => 'http://vocab.getty.edu/dataset/ulan',
    ];

    private LoggerInterface $logger;
    private int $timeout;
    private ?GettyCacheService $cache;

    public function __construct(
        ?LoggerInterface $logger = null,
        int $timeout = 30,
        ?GettyCacheService $cache = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->timeout = $timeout;
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function search(string $query, string $vocabulary, int $limit = 20): array
    {
        $this->validateVocabulary($vocabulary);

        // Check cache first
        $cacheKey = "search_{$vocabulary}_".md5($query)."_{$limit}";
        if ($this->cache && $cached = $this->cache->get($cacheKey)) {
            $this->logger->debug('Getty search cache hit', ['query' => $query, 'vocabulary' => $vocabulary]);

            return $cached;
        }

        $sparql = $this->buildSearchQuery($query, $vocabulary, $limit);

        $this->logger->info('Getty vocabulary search', [
            'query' => $query,
            'vocabulary' => $vocabulary,
            'limit' => $limit,
        ]);

        $results = $this->executeSparql($sparql);
        $formatted = $this->formatSearchResults($results, $vocabulary);

        // Cache results
        if ($this->cache) {
            $this->cache->set($cacheKey, $formatted, 86400); // 24 hours
        }

        return $formatted;
    }

    /**
     * {@inheritDoc}
     */
    public function getTerm(string $identifier, string $vocabulary): ?array
    {
        $this->validateVocabulary($vocabulary);

        $uri = $this->normalizeUri($identifier, $vocabulary);

        // Check cache
        $cacheKey = "term_{$vocabulary}_".md5($uri);
        if ($this->cache && $cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $sparql = $this->buildTermQuery($uri, $vocabulary);
        $results = $this->executeSparql($sparql);

        if (empty($results['results']['bindings'])) {
            return null;
        }

        $term = $this->formatTermResult($results['results']['bindings'], $uri, $vocabulary);

        // Cache for 7 days (terms don't change often)
        if ($this->cache && $term) {
            $this->cache->set($cacheKey, $term, 604800);
        }

        return $term;
    }

    /**
     * {@inheritDoc}
     */
    public function getBroaderTerms(string $uri, string $vocabulary): array
    {
        $this->validateVocabulary($vocabulary);
        $uri = $this->normalizeUri($uri, $vocabulary);

        $sparql = <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT ?broader ?prefLabel
FROM <{$this->getDataset($vocabulary)}>
WHERE {
    <{$uri}> gvp:broaderGeneric ?broader .
    ?broader xl:prefLabel ?labelNode .
    ?labelNode gvp:term ?prefLabel .
    FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
}
LIMIT 50
SPARQL;

        $results = $this->executeSparql($sparql);

        return $this->formatHierarchyResults($results, $vocabulary);
    }

    /**
     * {@inheritDoc}
     */
    public function getNarrowerTerms(string $uri, string $vocabulary): array
    {
        $this->validateVocabulary($vocabulary);
        $uri = $this->normalizeUri($uri, $vocabulary);

        $sparql = <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT ?narrower ?prefLabel
FROM <{$this->getDataset($vocabulary)}>
WHERE {
    ?narrower gvp:broaderGeneric <{$uri}> .
    ?narrower xl:prefLabel ?labelNode .
    ?labelNode gvp:term ?prefLabel .
    FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
}
ORDER BY ?prefLabel
LIMIT 100
SPARQL;

        $results = $this->executeSparql($sparql);

        return $this->formatHierarchyResults($results, $vocabulary);
    }

    /**
     * {@inheritDoc}
     */
    public function getRelatedTerms(string $uri, string $vocabulary): array
    {
        $this->validateVocabulary($vocabulary);
        $uri = $this->normalizeUri($uri, $vocabulary);

        $sparql = <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT ?related ?prefLabel ?relationshipType
FROM <{$this->getDataset($vocabulary)}>
WHERE {
    {
        <{$uri}> skos:related ?related .
        BIND("related" AS ?relationshipType)
    }
    UNION
    {
        <{$uri}> gvp:associatedConcept ?related .
        BIND("associated" AS ?relationshipType)
    }
    ?related xl:prefLabel ?labelNode .
    ?labelNode gvp:term ?prefLabel .
    FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
}
LIMIT 50
SPARQL;

        $results = $this->executeSparql($sparql);

        return $this->formatRelatedResults($results, $vocabulary);
    }

    /**
     * {@inheritDoc}
     */
    public function validateUri(string $uri, string $vocabulary): bool
    {
        $term = $this->getTerm($uri, $vocabulary);

        return null !== $term;
    }

    /**
     * {@inheritDoc}
     */
    public function getPreferredLabel(string $uri, string $language = 'en'): ?string
    {
        // Extract vocabulary from URI
        $vocabulary = $this->extractVocabularyFromUri($uri);
        if (!$vocabulary) {
            return null;
        }

        $sparql = <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT ?prefLabel
FROM <{$this->getDataset($vocabulary)}>
WHERE {
    <{$uri}> gvp:prefLabelGVP ?labelNode .
    ?labelNode gvp:term ?prefLabel .
    FILTER(LANG(?prefLabel) = "{$language}" || LANG(?prefLabel) = "")
}
LIMIT 1
SPARQL;

        $results = $this->executeSparql($sparql);

        if (!empty($results['results']['bindings'][0]['prefLabel']['value'])) {
            return $results['results']['bindings'][0]['prefLabel']['value'];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllLabels(string $uri, ?string $language = null): array
    {
        $vocabulary = $this->extractVocabularyFromUri($uri);
        if (!$vocabulary) {
            return [];
        }

        $langFilter = $language
            ? "FILTER(LANG(?label) = \"{$language}\" || LANG(?label) = \"\")"
            : '';

        $sparql = <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
PREFIX skosxl: <http://www.w3.org/2008/05/skos-xl#>

SELECT ?label ?labelType ?language
FROM <{$this->getDataset($vocabulary)}>
WHERE {
    {
        <{$uri}> xl:prefLabel ?labelNode .
        ?labelNode gvp:term ?label .
        BIND("preferred" AS ?labelType)
    }
    UNION
    {
        <{$uri}> xl:altLabel ?labelNode .
        ?labelNode gvp:term ?label .
        BIND("alternate" AS ?labelType)
    }
    BIND(LANG(?label) AS ?language)
    {$langFilter}
}
ORDER BY ?labelType ?language
SPARQL;

        $results = $this->executeSparql($sparql);
        $labels = [];

        foreach ($results['results']['bindings'] ?? [] as $binding) {
            $labels[] = [
                'label' => $binding['label']['value'] ?? '',
                'type' => $binding['labelType']['value'] ?? 'unknown',
                'language' => $binding['language']['value'] ?? 'en',
            ];
        }

        return $labels;
    }

    /**
     * {@inheritDoc}
     */
    public function getScopeNote(string $uri, string $language = 'en'): ?string
    {
        $vocabulary = $this->extractVocabularyFromUri($uri);
        if (!$vocabulary) {
            return null;
        }

        $sparql = <<<SPARQL
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX gvp: <http://vocab.getty.edu/ontology#>

SELECT ?scopeNote
FROM <{$this->getDataset($vocabulary)}>
WHERE {
    <{$uri}> skos:scopeNote ?noteNode .
    ?noteNode rdf:value ?scopeNote .
    FILTER(LANG(?scopeNote) = "{$language}" || LANG(?scopeNote) = "")
}
LIMIT 1
SPARQL;

        $results = $this->executeSparql($sparql);

        if (!empty($results['results']['bindings'][0]['scopeNote']['value'])) {
            return $results['results']['bindings'][0]['scopeNote']['value'];
        }

        return null;
    }

    /**
     * Build SPARQL query for full-text search.
     */
    private function buildSearchQuery(string $query, string $vocabulary, int $limit): string
    {
        // Escape special characters in query
        $escapedQuery = addslashes($query);
        $dataset = $this->getDataset($vocabulary);

        // Use Lucene full-text search (luc:term)
        return <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX luc: <http://www.ontotext.com/owlim/lucene#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

SELECT DISTINCT ?subject ?prefLabel ?scopeNote ?hierarchy
FROM <{$dataset}>
WHERE {
    ?subject luc:term "{$escapedQuery}" ;
             a gvp:Concept ;
             gvp:prefLabelGVP ?labelNode .
    ?labelNode gvp:term ?prefLabel .
    FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
    
    OPTIONAL {
        ?subject skos:scopeNote ?noteNode .
        ?noteNode rdf:value ?scopeNote .
        FILTER(LANG(?scopeNote) = "en" || LANG(?scopeNote) = "")
    }
    
    OPTIONAL {
        ?subject gvp:parentString ?hierarchy .
    }
}
ORDER BY DESC(?score) ?prefLabel
LIMIT {$limit}
SPARQL;
    }

    /**
     * Build SPARQL query for getting term details.
     */
    private function buildTermQuery(string $uri, string $vocabulary): string
    {
        $dataset = $this->getDataset($vocabulary);

        return <<<SPARQL
PREFIX gvp: <http://vocab.getty.edu/ontology#>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
PREFIX dct: <http://purl.org/dc/terms/>

SELECT ?prefLabel ?altLabel ?scopeNote ?hierarchy ?broader ?narrower ?modified
FROM <{$dataset}>
WHERE {
    <{$uri}> a gvp:Concept .
    
    OPTIONAL {
        <{$uri}> gvp:prefLabelGVP ?prefLabelNode .
        ?prefLabelNode gvp:term ?prefLabel .
        FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
    }
    
    OPTIONAL {
        <{$uri}> xl:altLabel ?altLabelNode .
        ?altLabelNode gvp:term ?altLabel .
        FILTER(LANG(?altLabel) = "en" || LANG(?altLabel) = "")
    }
    
    OPTIONAL {
        <{$uri}> skos:scopeNote ?noteNode .
        ?noteNode rdf:value ?scopeNote .
        FILTER(LANG(?scopeNote) = "en" || LANG(?scopeNote) = "")
    }
    
    OPTIONAL {
        <{$uri}> gvp:parentString ?hierarchy .
    }
    
    OPTIONAL {
        <{$uri}> gvp:broaderGeneric ?broader .
    }
    
    OPTIONAL {
        ?narrower gvp:broaderGeneric <{$uri}> .
    }
    
    OPTIONAL {
        <{$uri}> dct:modified ?modified .
    }
}
SPARQL;
    }

    /**
     * Execute SPARQL query against Getty endpoint.
     */
    private function executeSparql(string $sparql): array
    {
        $url = self::SPARQL_ENDPOINT.'?'.http_build_query([
            'query' => $sparql,
            'format' => 'application/sparql-results+json',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/sparql-results+json',
                    'User-Agent: AtoM-Museum-Plugin/1.0 (https://theahg.co.za)',
                ],
                'timeout' => $this->timeout,
            ],
        ]);

        $this->logger->debug('Executing SPARQL query', [
            'endpoint' => self::SPARQL_ENDPOINT,
            'query_length' => strlen($sparql),
        ]);

        $response = @file_get_contents($url, false, $context);

        if (false === $response) {
            $error = error_get_last();
            $this->logger->error('Getty SPARQL query failed', [
                'error' => $error['message'] ?? 'Unknown error',
                'url' => $url,
            ]);

            return ['results' => ['bindings' => []]];
        }

        $data = json_decode($response, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->logger->error('Getty SPARQL invalid JSON response', [
                'error' => json_last_error_msg(),
            ]);

            return ['results' => ['bindings' => []]];
        }

        $bindingCount = count($data['results']['bindings'] ?? []);
        $this->logger->debug('Getty SPARQL query success', [
            'results_count' => $bindingCount,
        ]);

        return $data;
    }

    /**
     * Format search results into standard array.
     */
    private function formatSearchResults(array $results, string $vocabulary): array
    {
        $formatted = [];

        foreach ($results['results']['bindings'] ?? [] as $binding) {
            $uri = $binding['subject']['value'] ?? '';
            $id = $this->extractIdFromUri($uri);

            $formatted[] = [
                'id' => $id,
                'uri' => $uri,
                'vocabulary' => $vocabulary,
                'prefLabel' => $binding['prefLabel']['value'] ?? '',
                'scopeNote' => $binding['scopeNote']['value'] ?? null,
                'hierarchy' => $binding['hierarchy']['value'] ?? null,
                'humanUrl' => "http://vocab.getty.edu/page/{$vocabulary}/{$id}",
            ];
        }

        return $formatted;
    }

    /**
     * Format term result from multiple bindings.
     */
    private function formatTermResult(array $bindings, string $uri, string $vocabulary): array
    {
        $id = $this->extractIdFromUri($uri);

        $term = [
            'id' => $id,
            'uri' => $uri,
            'vocabulary' => $vocabulary,
            'prefLabel' => null,
            'altLabels' => [],
            'scopeNote' => null,
            'hierarchy' => null,
            'broader' => [],
            'narrower' => [],
            'modified' => null,
            'humanUrl' => "http://vocab.getty.edu/page/{$vocabulary}/{$id}",
        ];

        foreach ($bindings as $binding) {
            if (!$term['prefLabel'] && isset($binding['prefLabel']['value'])) {
                $term['prefLabel'] = $binding['prefLabel']['value'];
            }

            if (isset($binding['altLabel']['value'])) {
                $altLabel = $binding['altLabel']['value'];
                if (!in_array($altLabel, $term['altLabels'])) {
                    $term['altLabels'][] = $altLabel;
                }
            }

            if (!$term['scopeNote'] && isset($binding['scopeNote']['value'])) {
                $term['scopeNote'] = $binding['scopeNote']['value'];
            }

            if (!$term['hierarchy'] && isset($binding['hierarchy']['value'])) {
                $term['hierarchy'] = $binding['hierarchy']['value'];
            }

            if (isset($binding['broader']['value'])) {
                $broaderUri = $binding['broader']['value'];
                if (!in_array($broaderUri, $term['broader'])) {
                    $term['broader'][] = $broaderUri;
                }
            }

            if (isset($binding['narrower']['value'])) {
                $narrowerUri = $binding['narrower']['value'];
                if (!in_array($narrowerUri, $term['narrower'])) {
                    $term['narrower'][] = $narrowerUri;
                }
            }

            if (!$term['modified'] && isset($binding['modified']['value'])) {
                $term['modified'] = $binding['modified']['value'];
            }
        }

        return $term;
    }

    /**
     * Format hierarchy results (broader/narrower).
     */
    private function formatHierarchyResults(array $results, string $vocabulary): array
    {
        $formatted = [];

        foreach ($results['results']['bindings'] ?? [] as $binding) {
            $key = isset($binding['broader']) ? 'broader' : 'narrower';
            $uri = $binding[$key]['value'] ?? ($binding['related']['value'] ?? '');

            if ($uri) {
                $id = $this->extractIdFromUri($uri);
                $formatted[] = [
                    'id' => $id,
                    'uri' => $uri,
                    'vocabulary' => $vocabulary,
                    'prefLabel' => $binding['prefLabel']['value'] ?? '',
                    'humanUrl' => "http://vocab.getty.edu/page/{$vocabulary}/{$id}",
                ];
            }
        }

        return $formatted;
    }

    /**
     * Format related terms results.
     */
    private function formatRelatedResults(array $results, string $vocabulary): array
    {
        $formatted = [];

        foreach ($results['results']['bindings'] ?? [] as $binding) {
            $uri = $binding['related']['value'] ?? '';

            if ($uri) {
                $id = $this->extractIdFromUri($uri);
                $formatted[] = [
                    'id' => $id,
                    'uri' => $uri,
                    'vocabulary' => $vocabulary,
                    'prefLabel' => $binding['prefLabel']['value'] ?? '',
                    'relationshipType' => $binding['relationshipType']['value'] ?? 'related',
                    'humanUrl' => "http://vocab.getty.edu/page/{$vocabulary}/{$id}",
                ];
            }
        }

        return $formatted;
    }

    /**
     * Validate vocabulary identifier.
     *
     * @throws \InvalidArgumentException If vocabulary is not supported
     */
    private function validateVocabulary(string $vocabulary): void
    {
        if (!isset(self::GRAPHS[$vocabulary])) {
            throw new \InvalidArgumentException(
                "Unsupported vocabulary: {$vocabulary}. Supported: ".implode(', ', array_keys(self::GRAPHS))
            );
        }
    }

    /**
     * Get dataset URI for vocabulary.
     */
    private function getDataset(string $vocabulary): string
    {
        return self::DATASETS[$vocabulary] ?? self::DATASETS['aat'];
    }

    /**
     * Normalize identifier to full URI.
     */
    private function normalizeUri(string $identifier, string $vocabulary): string
    {
        // Already a full URI
        if (str_starts_with($identifier, 'http://') || str_starts_with($identifier, 'https://')) {
            return $identifier;
        }

        // Numeric ID only
        if (is_numeric($identifier)) {
            return self::BASE_URI.$vocabulary.'/'.$identifier;
        }

        // Assume it's a partial path
        return self::BASE_URI.ltrim($identifier, '/');
    }

    /**
     * Extract numeric ID from Getty URI.
     */
    private function extractIdFromUri(string $uri): string
    {
        if (preg_match('/\/(\d+)$/', $uri, $matches)) {
            return $matches[1];
        }

        return $uri;
    }

    /**
     * Extract vocabulary from Getty URI.
     */
    private function extractVocabularyFromUri(string $uri): ?string
    {
        foreach (array_keys(self::GRAPHS) as $vocab) {
            if (str_contains($uri, "/{$vocab}/")) {
                return $vocab;
            }
        }

        return null;
    }
}
