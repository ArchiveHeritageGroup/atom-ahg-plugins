<?php

namespace AhgAuthority\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Service #2: Authority Lookup Service (#202)
 *
 * Server-side proxy for external authority source APIs.
 * Searches Wikidata, VIAF, ULAN, LCNAF and returns results.
 * Avoids CORS issues and enables caching/validation.
 */
class AuthorityLookupService
{
    protected int $timeout = 10;

    /**
     * Get plugin config value.
     */
    protected function getConfig(string $key, string $default = ''): string
    {
        $row = DB::table('ahg_authority_config')
            ->where('config_key', $key)
            ->first();

        return $row ? ($row->config_value ?? $default) : $default;
    }

    /**
     * Check if a source is enabled.
     */
    public function isSourceEnabled(string $source): bool
    {
        return $this->getConfig($source . '_enabled', '0') === '1';
    }

    // =========================================================================
    // WIKIDATA
    // =========================================================================

    /**
     * Search Wikidata for authority records.
     */
    public function searchWikidata(string $query, string $language = 'en', int $limit = 10): array
    {
        if (!$this->isSourceEnabled('wikidata')) {
            return ['error' => 'Wikidata is not enabled'];
        }

        $url = 'https://www.wikidata.org/w/api.php?' . http_build_query([
            'action'   => 'wbsearchentities',
            'search'   => $query,
            'language' => $language,
            'limit'    => $limit,
            'format'   => 'json',
            'type'     => 'item',
        ]);

        $response = $this->httpGet($url);
        if (!$response) {
            return ['error' => 'Failed to connect to Wikidata'];
        }

        $data = json_decode($response, true);
        if (!isset($data['search'])) {
            return ['results' => []];
        }

        $results = [];
        foreach ($data['search'] as $item) {
            $results[] = [
                'id'          => $item['id'] ?? '',
                'label'       => $item['label'] ?? '',
                'description' => $item['description'] ?? '',
                'uri'         => $item['concepturi'] ?? sprintf('https://www.wikidata.org/wiki/%s', $item['id']),
                'source'      => 'wikidata',
            ];
        }

        return ['results' => $results];
    }

    // =========================================================================
    // VIAF
    // =========================================================================

    /**
     * Search VIAF for authority records.
     */
    public function searchViaf(string $query, int $limit = 10): array
    {
        if (!$this->isSourceEnabled('viaf')) {
            return ['error' => 'VIAF is not enabled'];
        }

        $url = 'https://viaf.org/viaf/AutoSuggest?' . http_build_query([
            'query' => $query,
        ]);

        $response = $this->httpGet($url);
        if (!$response) {
            return ['error' => 'Failed to connect to VIAF'];
        }

        $data = json_decode($response, true);
        if (!isset($data['result'])) {
            return ['results' => []];
        }

        $results = [];
        foreach (array_slice($data['result'], 0, $limit) as $item) {
            $viafId = $item['viafid'] ?? '';
            $results[] = [
                'id'          => $viafId,
                'label'       => $item['term'] ?? '',
                'description' => $item['nametype'] ?? '',
                'uri'         => 'https://viaf.org/viaf/' . $viafId,
                'source'      => 'viaf',
            ];
        }

        return ['results' => $results];
    }

    // =========================================================================
    // ULAN (Getty Union List of Artist Names)
    // =========================================================================

    /**
     * Search ULAN via Getty SPARQL endpoint.
     */
    public function searchUlan(string $query, int $limit = 10): array
    {
        if (!$this->isSourceEnabled('ulan')) {
            return ['error' => 'ULAN is not enabled'];
        }

        $sparql = sprintf(
            'SELECT ?subject ?name ?bio WHERE {
                ?subject a gvp:PersonConcept ;
                         gvp:prefLabelGVP/xl:literalForm ?name ;
                         foaf:focus/gvp:biographyPreferred/schema:description ?bio .
                FILTER(CONTAINS(LCASE(?name), "%s"))
            } LIMIT %d',
            strtolower(addslashes($query)),
            $limit
        );

        $url = 'https://vocab.getty.edu/sparql.json?' . http_build_query([
            'query' => $sparql,
        ]);

        $response = $this->httpGet($url);
        if (!$response) {
            return ['error' => 'Failed to connect to ULAN'];
        }

        $data = json_decode($response, true);
        $bindings = $data['results']['bindings'] ?? [];

        $results = [];
        foreach ($bindings as $item) {
            $uri = $item['subject']['value'] ?? '';
            $id  = basename($uri);
            $results[] = [
                'id'          => $id,
                'label'       => $item['name']['value'] ?? '',
                'description' => $item['bio']['value'] ?? '',
                'uri'         => $uri,
                'source'      => 'ulan',
            ];
        }

        return ['results' => $results];
    }

    // =========================================================================
    // LCNAF (Library of Congress Name Authority File)
    // =========================================================================

    /**
     * Search LCNAF via id.loc.gov suggest API.
     */
    public function searchLcnaf(string $query, int $limit = 10): array
    {
        if (!$this->isSourceEnabled('lcnaf')) {
            return ['error' => 'LCNAF is not enabled'];
        }

        $url = 'https://id.loc.gov/authorities/names/suggest2?' . http_build_query([
            'q'      => $query,
            'count'  => $limit,
        ]);

        $response = $this->httpGet($url);
        if (!$response) {
            return ['error' => 'Failed to connect to LCNAF'];
        }

        $data = json_decode($response, true);
        $hits = $data['hits'] ?? [];

        $results = [];
        foreach ($hits as $item) {
            $results[] = [
                'id'          => $item['token'] ?? basename($item['uri'] ?? ''),
                'label'       => $item['aLabel'] ?? '',
                'description' => $item['vLabel'] ?? '',
                'uri'         => $item['uri'] ?? '',
                'source'      => 'lcnaf',
            ];
        }

        return ['results' => $results];
    }

    // =========================================================================
    // ISNI (International Standard Name Identifier)
    // =========================================================================

    /**
     * Search ISNI for authority records.
     */
    public function searchIsni(string $query, int $limit = 10): array
    {
        if (!$this->isSourceEnabled('isni')) {
            return ['error' => 'ISNI is not enabled'];
        }

        $url = 'https://isni.org/isni/search?' . http_build_query([
            'query'  => 'pall all "' . $query . '"',
            'format' => 'json',
        ]);

        $response = $this->httpGet($url);
        if (!$response) {
            return ['error' => 'Failed to connect to ISNI'];
        }

        $data = json_decode($response, true);
        $records = $data['ISNIMetadata'] ?? [];

        $results = [];
        foreach (array_slice($records, 0, $limit) as $item) {
            $isni = $item['ISNIAssigned'] ?? ($item['ISNIUnassigned'] ?? '');
            $personal = $item['identity']['personOrFiction']['personalName'] ?? null;
            $orgName = $item['identity']['organisation']['organisationName']['mainName'] ?? null;
            $label = '';
            if ($personal) {
                $label = trim(($personal['forename'] ?? '') . ' ' . ($personal['surname'] ?? ''));
            } elseif ($orgName) {
                $label = $orgName;
            }
            if (!$label || !$isni) {
                continue;
            }
            $results[] = [
                'id'          => $isni,
                'label'       => $label,
                'description' => '',
                'uri'         => 'https://isni.org/isni/' . $isni,
                'source'      => 'isni',
            ];
        }

        return ['results' => $results];
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Simple HTTP GET with timeout.
     */
    protected function httpGet(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => $this->timeout,
                'header'  => "Accept: application/json\r\nUser-Agent: AtoM-Heratio/2.8\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $ctx);

        return $result !== false ? $result : null;
    }
}
