<?php

/**
 * Authority Linkage Service
 *
 * Provides integration with external authority sources for actor records:
 * - Getty ULAN (Union List of Artist Names)
 * - Wikidata
 * - VIAF (Virtual International Authority File)
 * - ISNI (International Standard Name Identifier)
 * - Library of Congress Name Authority File (LCNAF)
 *
 * Supports ISAAR(CPF) and CIDOC-CRM actor models.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgAuthorityLinkageService
{
    // Authority source identifiers
    const SOURCE_ULAN = 'ulan';
    const SOURCE_WIKIDATA = 'wikidata';
    const SOURCE_VIAF = 'viaf';
    const SOURCE_ISNI = 'isni';
    const SOURCE_LCNAF = 'lcnaf';
    const SOURCE_GND = 'gnd';  // German National Library
    const SOURCE_SNAC = 'snac'; // Social Networks and Archival Context

    // CIDOC-CRM Actor Types
    const CIDOC_PERSON = 'E21_Person';
    const CIDOC_GROUP = 'E74_Group';
    const CIDOC_ACTOR = 'E39_Actor';

    // ISAAR Entity Type Term IDs (from AtoM)
    const TERM_PERSON_ID = 131;
    const TERM_CORPORATE_BODY_ID = 132;
    const TERM_FAMILY_ID = 133;

    // API Endpoints
    const ULAN_SPARQL = 'http://vocab.getty.edu/sparql';
    const WIKIDATA_API = 'https://www.wikidata.org/w/api.php';
    const WIKIDATA_SPARQL = 'https://query.wikidata.org/sparql';
    const VIAF_API = 'https://www.viaf.org/viaf/search';
    const VIAF_DATA = 'https://www.viaf.org/viaf/';
    const ISNI_API = 'https://isni.org/isni/';
    const LCNAF_API = 'https://id.loc.gov/authorities/names/suggest2';

    // Property names for storing authority IDs
    public static $propertyNames = [
        self::SOURCE_ULAN => 'ulanId',
        self::SOURCE_WIKIDATA => 'wikidataId',
        self::SOURCE_VIAF => 'viafId',
        self::SOURCE_ISNI => 'isniId',
        self::SOURCE_LCNAF => 'lcnafId',
        self::SOURCE_GND => 'gndId',
        self::SOURCE_SNAC => 'snacId',
    ];

    // Authority source metadata
    public static $sources = [
        self::SOURCE_ULAN => [
            'label' => 'Getty ULAN',
            'fullName' => 'Union List of Artist Names',
            'url' => 'http://vocab.getty.edu/ulan/',
            'icon' => 'fa-paint-brush',
            'description' => 'Getty vocabulary for artists and cultural figures',
            'types' => ['person', 'corporate_body'],
        ],
        self::SOURCE_WIKIDATA => [
            'label' => 'Wikidata',
            'fullName' => 'Wikidata Knowledge Base',
            'url' => 'https://www.wikidata.org/wiki/',
            'icon' => 'fa-wikipedia-w',
            'description' => 'Collaborative structured data repository',
            'types' => ['person', 'corporate_body', 'family'],
        ],
        self::SOURCE_VIAF => [
            'label' => 'VIAF',
            'fullName' => 'Virtual International Authority File',
            'url' => 'https://viaf.org/viaf/',
            'icon' => 'fa-globe',
            'description' => 'International authority file linking national libraries',
            'types' => ['person', 'corporate_body'],
        ],
        self::SOURCE_ISNI => [
            'label' => 'ISNI',
            'fullName' => 'International Standard Name Identifier',
            'url' => 'https://isni.org/isni/',
            'icon' => 'fa-id-card',
            'description' => 'ISO standard identifier for public identities',
            'types' => ['person', 'corporate_body'],
        ],
        self::SOURCE_LCNAF => [
            'label' => 'LC NAF',
            'fullName' => 'Library of Congress Name Authority File',
            'url' => 'https://id.loc.gov/authorities/names/',
            'icon' => 'fa-university',
            'description' => 'US Library of Congress name authorities',
            'types' => ['person', 'corporate_body', 'family'],
        ],
        self::SOURCE_GND => [
            'label' => 'GND',
            'fullName' => 'Gemeinsame Normdatei',
            'url' => 'https://d-nb.info/gnd/',
            'icon' => 'fa-book',
            'description' => 'German National Library authority file',
            'types' => ['person', 'corporate_body'],
        ],
        self::SOURCE_SNAC => [
            'label' => 'SNAC',
            'fullName' => 'Social Networks and Archival Context',
            'url' => 'https://snaccooperative.org/view/',
            'icon' => 'fa-archive',
            'description' => 'Archival authority cooperative',
            'types' => ['person', 'corporate_body', 'family'],
        ],
    ];

    protected $timeout = 10;

    /**
     * Search ULAN for matching actors
     */
    public function searchULAN($query, $type = null, $limit = 20): array
    {
        $typeFilter = '';
        if ($type === 'person') {
            $typeFilter = 'FILTER(?type = gvp:PersonConcept)';
        } elseif ($type === 'corporate_body') {
            $typeFilter = 'FILTER(?type = gvp:GroupConcept)';
        }

        $sparql = sprintf('
            SELECT ?subject ?prefLabel ?scopeNote ?birthDate ?deathDate ?nationality ?type
            WHERE {
                ?subject a ?type ;
                         skos:prefLabel ?prefLabel ;
                         skos:inScheme ulan: .
                FILTER(CONTAINS(LCASE(?prefLabel), LCASE("%s")))
                FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
                %s
                OPTIONAL { ?subject gvp:biographyPreferred/schema:description ?scopeNote }
                OPTIONAL { ?subject gvp:biographyPreferred/gvp:estStart ?birthDate }
                OPTIONAL { ?subject gvp:biographyPreferred/gvp:estEnd ?deathDate }
                OPTIONAL { ?subject foaf:focus/gvp:nationalityPreferred/gvp:prefLabelGVP/xl:literalForm ?nationality }
            }
            LIMIT %d
        ', addslashes($query), $typeFilter, $limit);

        $results = $this->executeSparql(self::ULAN_SPARQL, $sparql);

        return $this->parseULANResults($results);
    }

    /**
     * Get ULAN record by ID
     */
    public function getULANRecord($ulanId): ?array
    {
        $sparql = sprintf('
            SELECT ?prefLabel ?scopeNote ?birthDate ?deathDate ?birthPlace ?deathPlace 
                   ?nationality ?gender ?type ?related ?relatedLabel ?relationType
            WHERE {
                ulan:%s skos:prefLabel ?prefLabel ;
                        a ?type .
                FILTER(LANG(?prefLabel) = "en" || LANG(?prefLabel) = "")
                OPTIONAL { ulan:%s gvp:biographyPreferred/schema:description ?scopeNote }
                OPTIONAL { ulan:%s gvp:biographyPreferred/gvp:estStart ?birthDate }
                OPTIONAL { ulan:%s gvp:biographyPreferred/gvp:estEnd ?deathDate }
                OPTIONAL { ulan:%s foaf:focus/gvp:placeOfBirth/gvp:prefLabelGVP/xl:literalForm ?birthPlace }
                OPTIONAL { ulan:%s foaf:focus/gvp:placeOfDeath/gvp:prefLabelGVP/xl:literalForm ?deathPlace }
                OPTIONAL { ulan:%s foaf:focus/gvp:nationalityPreferred/gvp:prefLabelGVP/xl:literalForm ?nationality }
                OPTIONAL { ulan:%s foaf:focus/schema:gender ?gender }
            }
            LIMIT 1
        ', $ulanId, $ulanId, $ulanId, $ulanId, $ulanId, $ulanId, $ulanId, $ulanId);

        $results = $this->executeSparql(self::ULAN_SPARQL, $sparql);

        if (!empty($results['results']['bindings'])) {
            $binding = $results['results']['bindings'][0];
            return [
                'id' => $ulanId,
                'uri' => 'http://vocab.getty.edu/ulan/' . $ulanId,
                'source' => self::SOURCE_ULAN,
                'label' => $binding['prefLabel']['value'] ?? null,
                'biography' => $binding['scopeNote']['value'] ?? null,
                'birthDate' => $binding['birthDate']['value'] ?? null,
                'deathDate' => $binding['deathDate']['value'] ?? null,
                'birthPlace' => $binding['birthPlace']['value'] ?? null,
                'deathPlace' => $binding['deathPlace']['value'] ?? null,
                'nationality' => $binding['nationality']['value'] ?? null,
                'gender' => $binding['gender']['value'] ?? null,
                'type' => $this->mapULANType($binding['type']['value'] ?? null),
            ];
        }

        return null;
    }

    /**
     * Search Wikidata for matching actors
     */
    public function searchWikidata($query, $type = null, $limit = 20): array
    {
        // Use Wikidata API for search
        $params = [
            'action' => 'wbsearchentities',
            'search' => $query,
            'language' => 'en',
            'limit' => $limit,
            'format' => 'json',
            'type' => 'item',
        ];

        $url = self::WIKIDATA_API . '?' . http_build_query($params);
        $response = $this->httpGet($url);

        if (!$response) {
            return [];
        }

        $data = json_decode($response, true);
        $results = [];

        foreach ($data['search'] ?? [] as $item) {
            // Get more details for each item
            $details = $this->getWikidataBasicInfo($item['id']);

            // Filter by type if specified
            if ($type && $details) {
                if ($type === 'person' && $details['type'] !== 'person') {
                    continue;
                }
                if ($type === 'corporate_body' && $details['type'] !== 'corporate_body') {
                    continue;
                }
            }

            $results[] = [
                'id' => $item['id'],
                'uri' => 'https://www.wikidata.org/wiki/' . $item['id'],
                'source' => self::SOURCE_WIKIDATA,
                'label' => $item['label'] ?? null,
                'description' => $item['description'] ?? null,
                'type' => $details['type'] ?? 'unknown',
                'birthDate' => $details['birthDate'] ?? null,
                'deathDate' => $details['deathDate'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * Get Wikidata record by QID
     */
    public function getWikidataRecord($qid): ?array
    {
        $sparql = sprintf('
            SELECT ?itemLabel ?itemDescription ?birthDate ?deathDate ?birthPlaceLabel 
                   ?deathPlaceLabel ?nationalityLabel ?genderLabel ?image ?viaf ?isni ?ulan ?lcnaf
            WHERE {
                BIND(wd:%s AS ?item)
                OPTIONAL { ?item wdt:P569 ?birthDate }
                OPTIONAL { ?item wdt:P570 ?deathDate }
                OPTIONAL { ?item wdt:P19 ?birthPlace }
                OPTIONAL { ?item wdt:P20 ?deathPlace }
                OPTIONAL { ?item wdt:P27 ?nationality }
                OPTIONAL { ?item wdt:P21 ?gender }
                OPTIONAL { ?item wdt:P18 ?image }
                OPTIONAL { ?item wdt:P214 ?viaf }
                OPTIONAL { ?item wdt:P213 ?isni }
                OPTIONAL { ?item wdt:P245 ?ulan }
                OPTIONAL { ?item wdt:P244 ?lcnaf }
                SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
            }
            LIMIT 1
        ', $qid);

        $results = $this->executeSparql(self::WIKIDATA_SPARQL, $sparql);

        if (!empty($results['results']['bindings'])) {
            $binding = $results['results']['bindings'][0];

            // Get entity type
            $typeInfo = $this->getWikidataBasicInfo($qid);

            return [
                'id' => $qid,
                'uri' => 'https://www.wikidata.org/wiki/' . $qid,
                'source' => self::SOURCE_WIKIDATA,
                'label' => $binding['itemLabel']['value'] ?? null,
                'description' => $binding['itemDescription']['value'] ?? null,
                'birthDate' => $this->formatWikidataDate($binding['birthDate']['value'] ?? null),
                'deathDate' => $this->formatWikidataDate($binding['deathDate']['value'] ?? null),
                'birthPlace' => $binding['birthPlaceLabel']['value'] ?? null,
                'deathPlace' => $binding['deathPlaceLabel']['value'] ?? null,
                'nationality' => $binding['nationalityLabel']['value'] ?? null,
                'gender' => $binding['genderLabel']['value'] ?? null,
                'image' => $binding['image']['value'] ?? null,
                'type' => $typeInfo['type'] ?? 'unknown',
                'linkedAuthorities' => [
                    'viaf' => $binding['viaf']['value'] ?? null,
                    'isni' => $binding['isni']['value'] ?? null,
                    'ulan' => $binding['ulan']['value'] ?? null,
                    'lcnaf' => $binding['lcnaf']['value'] ?? null,
                ],
            ];
        }

        return null;
    }

    /**
     * Get basic Wikidata info (type) for an item
     */
    protected function getWikidataBasicInfo($qid): array
    {
        $sparql = sprintf('
            SELECT ?type
            WHERE {
                wd:%s wdt:P31 ?type .
            }
            LIMIT 10
        ', $qid);

        $results = $this->executeSparql(self::WIKIDATA_SPARQL, $sparql);

        $personTypes = ['Q5', 'Q15632617']; // human, fictional human
        $orgTypes = ['Q43229', 'Q4830453', 'Q783794']; // organization, business, company

        foreach ($results['results']['bindings'] ?? [] as $binding) {
            $typeUri = $binding['type']['value'] ?? '';
            $typeId = str_replace('http://www.wikidata.org/entity/', '', $typeUri);

            if (in_array($typeId, $personTypes)) {
                return ['type' => 'person'];
            }
            if (in_array($typeId, $orgTypes)) {
                return ['type' => 'corporate_body'];
            }
        }

        return ['type' => 'unknown'];
    }

    /**
     * Search VIAF for matching actors
     */
    public function searchVIAF($query, $type = null, $limit = 20): array
    {
        $cqlType = '';
        if ($type === 'person') {
            $cqlType = ' and local.mainHeadingEl = "personalNames"';
        } elseif ($type === 'corporate_body') {
            $cqlType = ' and local.mainHeadingEl = "corporateNames"';
        }

        $params = [
            'query' => sprintf('local.mainHeadingEl any "%s"%s', $query, $cqlType),
            'maximumRecords' => $limit,
            'httpAccept' => 'application/json',
            'sortKeys' => 'holdingscount',
        ];

        $url = self::VIAF_API . '?' . http_build_query($params);
        $response = $this->httpGet($url);

        if (!$response) {
            return [];
        }

        $data = json_decode($response, true);
        $results = [];

        foreach ($data['searchRetrieveResponse']['records'] ?? [] as $record) {
            $recordData = $record['record']['recordData'] ?? [];

            $viafId = $recordData['viafID'] ?? null;
            if (!$viafId) {
                continue;
            }

            $mainHeading = $recordData['mainHeadings']['data'] ?? [];
            if (!is_array($mainHeading)) {
                $mainHeading = [$mainHeading];
            }

            $label = '';
            foreach ($mainHeading as $heading) {
                if (is_array($heading) && isset($heading['text'])) {
                    $label = $heading['text'];
                    break;
                } elseif (is_string($heading)) {
                    $label = $heading;
                    break;
                }
            }

            $results[] = [
                'id' => $viafId,
                'uri' => 'https://viaf.org/viaf/' . $viafId,
                'source' => self::SOURCE_VIAF,
                'label' => $label,
                'type' => $this->detectVIAFType($recordData),
            ];
        }

        return $results;
    }

    /**
     * Get VIAF record by ID
     */
    public function getVIAFRecord($viafId): ?array
    {
        $url = self::VIAF_DATA . $viafId . '/viaf.json';
        $response = $this->httpGet($url);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        // Extract main heading
        $mainHeading = '';
        $headings = $data['mainHeadings']['data'] ?? [];
        if (!is_array($headings) || isset($headings['text'])) {
            $headings = [$headings];
        }
        foreach ($headings as $heading) {
            if (isset($heading['text'])) {
                $mainHeading = $heading['text'];
                break;
            }
        }

        // Extract dates
        $birthDate = $data['birthDate'] ?? null;
        $deathDate = $data['deathDate'] ?? null;

        // Extract linked authorities
        $linkedIds = [];
        foreach ($data['sources']['source'] ?? [] as $source) {
            if (isset($source['@nsid'])) {
                $nsid = $source['@nsid'];
                if (strpos($nsid, 'LC|') === 0) {
                    $linkedIds['lcnaf'] = str_replace('LC|', '', $nsid);
                } elseif (strpos($nsid, 'DNB|') === 0) {
                    $linkedIds['gnd'] = str_replace('DNB|', '', $nsid);
                } elseif (strpos($nsid, 'WKP|') === 0) {
                    $linkedIds['wikidata'] = str_replace('WKP|', '', $nsid);
                }
            }
        }

        return [
            'id' => $viafId,
            'uri' => 'https://viaf.org/viaf/' . $viafId,
            'source' => self::SOURCE_VIAF,
            'label' => $mainHeading,
            'birthDate' => $birthDate,
            'deathDate' => $deathDate,
            'type' => $this->detectVIAFType($data),
            'linkedAuthorities' => $linkedIds,
        ];
    }

    /**
     * Search Library of Congress Name Authority File
     */
    public function searchLCNAF($query, $limit = 20): array
    {
        $params = [
            'q' => $query,
            'count' => $limit,
        ];

        $url = self::LCNAF_API . '?' . http_build_query($params);
        $response = $this->httpGet($url);

        if (!$response) {
            return [];
        }

        $data = json_decode($response, true);
        $results = [];

        foreach ($data['hits'] ?? [] as $hit) {
            $uri = $hit['uri'] ?? '';
            $id = basename($uri);

            $results[] = [
                'id' => $id,
                'uri' => $uri,
                'source' => self::SOURCE_LCNAF,
                'label' => $hit['suggestLabel'] ?? $hit['aLabel'] ?? null,
                'type' => 'unknown',
            ];
        }

        return $results;
    }

    /**
     * Unified search across all authority sources
     */
    public function searchAllSources($query, $type = null, $sources = null, $limit = 10): array
    {
        if ($sources === null) {
            $sources = [self::SOURCE_ULAN, self::SOURCE_WIKIDATA, self::SOURCE_VIAF];
        }

        $allResults = [];

        foreach ($sources as $source) {
            try {
                switch ($source) {
                    case self::SOURCE_ULAN:
                        $results = $this->searchULAN($query, $type, $limit);
                        break;
                    case self::SOURCE_WIKIDATA:
                        $results = $this->searchWikidata($query, $type, $limit);
                        break;
                    case self::SOURCE_VIAF:
                        $results = $this->searchVIAF($query, $type, $limit);
                        break;
                    case self::SOURCE_LCNAF:
                        $results = $this->searchLCNAF($query, $limit);
                        break;
                    default:
                        $results = [];
                }

                $allResults[$source] = $results;
            } catch (Exception $e) {
                $allResults[$source] = ['error' => $e->getMessage()];
            }
        }

        return $allResults;
    }

    /**
     * Link authority to actor
     */
    public function linkAuthorityToActor($actorId, $source, $authorityId): int
    {
        if (!isset(self::$propertyNames[$source])) {
            throw new Exception('Unknown authority source: ' . $source);
        }

        $propertyName = self::$propertyNames[$source];

        // Check if property already exists
        $existing = DB::table('property')
            ->where('object_id', $actorId)
            ->where('name', $propertyName)
            ->first();

        if ($existing) {
            // Update existing i18n value
            DB::table('property_i18n')
                ->updateOrInsert(
                    ['id' => $existing->id, 'culture' => 'en'],
                    ['value' => $authorityId]
                );

            return $existing->id;
        }

        // Create new property
        $propertyId = DB::table('property')->insertGetId([
            'object_id' => $actorId,
            'name' => $propertyName,
        ]);

        // Create i18n entry
        DB::table('property_i18n')->insert([
            'id' => $propertyId,
            'culture' => 'en',
            'value' => $authorityId,
        ]);

        return $propertyId;
    }

    /**
     * Unlink authority from actor
     */
    public function unlinkAuthorityFromActor($actorId, $source): bool
    {
        if (!isset(self::$propertyNames[$source])) {
            throw new Exception('Unknown authority source: ' . $source);
        }

        $propertyName = self::$propertyNames[$source];

        $property = DB::table('property')
            ->where('object_id', $actorId)
            ->where('name', $propertyName)
            ->first();

        if ($property) {
            // Delete i18n entries
            DB::table('property_i18n')
                ->where('id', $property->id)
                ->delete();

            // Delete property
            DB::table('property')
                ->where('id', $property->id)
                ->delete();

            return true;
        }

        return false;
    }

    /**
     * Get all linked authorities for an actor
     */
    public function getActorAuthorities($actorId): array
    {
        $authorities = [];

        foreach (self::$propertyNames as $source => $propertyName) {
            $property = DB::table('property as p')
                ->leftJoin('property_i18n as pi', function ($join) {
                    $join->on('p.id', '=', 'pi.id')
                        ->where('pi.culture', '=', 'en');
                })
                ->where('p.object_id', $actorId)
                ->where('p.name', $propertyName)
                ->select('p.id', 'pi.value')
                ->first();

            if ($property && $property->value) {
                $id = $property->value;
                $authorities[$source] = [
                    'id' => $id,
                    'uri' => self::$sources[$source]['url'] . $id,
                    'source' => $source,
                    'label' => self::$sources[$source]['label'],
                ];
            }
        }

        return $authorities;
    }

    /**
     * Enrich actor record with authority data
     */
    public function enrichActorFromAuthority($actorId, $source): ?array
    {
        $authorities = $this->getActorAuthorities($actorId);

        if (!isset($authorities[$source])) {
            return null;
        }

        $authorityId = $authorities[$source]['id'];
        $data = null;

        switch ($source) {
            case self::SOURCE_ULAN:
                $data = $this->getULANRecord($authorityId);
                break;
            case self::SOURCE_WIKIDATA:
                $data = $this->getWikidataRecord($authorityId);
                break;
            case self::SOURCE_VIAF:
                $data = $this->getVIAFRecord($authorityId);
                break;
        }

        return $data;
    }

    /**
     * Map actor type to CIDOC-CRM class
     */
    public function mapToCIDOCClass($type): string
    {
        switch ($type) {
            case 'person':
                return self::CIDOC_PERSON;
            case 'corporate_body':
            case 'family':
                return self::CIDOC_GROUP;
            default:
                return self::CIDOC_ACTOR;
        }
    }

    /**
     * Map actor type to ISAAR entity type term ID
     */
    public function mapToISAARType($type): ?int
    {
        switch ($type) {
            case 'person':
                return self::TERM_PERSON_ID;
            case 'corporate_body':
                return self::TERM_CORPORATE_BODY_ID;
            case 'family':
                return self::TERM_FAMILY_ID;
            default:
                return null;
        }
    }

    /**
     * Get ISAAR type name from term ID
     */
    public function getISAARTypeName(int $termId): ?string
    {
        $term = DB::table('term_i18n')
            ->where('id', $termId)
            ->where('culture', 'en')
            ->first();

        return $term ? $term->name : null;
    }

    /**
     * Sync all linked authorities for an actor
     * Returns merged data from all sources
     */
    public function syncAllAuthorities($actorId): array
    {
        $authorities = $this->getActorAuthorities($actorId);
        $mergedData = [];

        foreach ($authorities as $source => $authority) {
            $data = $this->enrichActorFromAuthority($actorId, $source);
            if ($data) {
                // Merge data, preferring more complete entries
                foreach ($data as $key => $value) {
                    if ($value && (!isset($mergedData[$key]) || empty($mergedData[$key]))) {
                        $mergedData[$key] = $value;
                    }
                }

                // Handle linked authorities specially
                if (isset($data['linkedAuthorities'])) {
                    foreach ($data['linkedAuthorities'] as $linkedSource => $linkedId) {
                        if ($linkedId && !isset($authorities[$linkedSource])) {
                            // Found a new linked authority - optionally link it
                            $mergedData['suggestedLinks'][$linkedSource] = $linkedId;
                        }
                    }
                }
            }
        }

        $mergedData['sources'] = array_keys($authorities);

        return $mergedData;
    }

    /**
     * Find potential authority matches for an actor
     */
    public function findPotentialMatches($actorId): array
    {
        // Get actor data
        $actor = DB::table('actor as a')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('a.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->where('a.id', $actorId)
            ->select('a.id', 'a.entity_type_id', 'ai.authorized_form_of_name', 'ai.dates_of_existence')
            ->first();

        if (!$actor || !$actor->authorized_form_of_name) {
            return [];
        }

        // Determine type filter
        $type = null;
        if ($actor->entity_type_id == self::TERM_PERSON_ID) {
            $type = 'person';
        } elseif ($actor->entity_type_id == self::TERM_CORPORATE_BODY_ID) {
            $type = 'corporate_body';
        }

        // Search all sources
        return $this->searchAllSources($actor->authorized_form_of_name, $type, null, 5);
    }

    /**
     * Execute SPARQL query
     */
    protected function executeSparql($endpoint, $query): array
    {
        $params = [
            'query' => $query,
            'format' => 'json',
        ];

        $url = $endpoint . '?' . http_build_query($params);
        $response = $this->httpGet($url);

        if (!$response) {
            return ['results' => ['bindings' => []]];
        }

        return json_decode($response, true) ?: ['results' => ['bindings' => []]];
    }

    /**
     * HTTP GET request
     */
    protected function httpGet($url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeout,
                'header' => [
                    'Accept: application/json',
                    'User-Agent: AtoM-ahgMuseumPlugin/2.0',
                ],
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        return $response !== false ? $response : null;
    }

    /**
     * Parse ULAN search results
     */
    protected function parseULANResults($results): array
    {
        $parsed = [];

        foreach ($results['results']['bindings'] ?? [] as $binding) {
            $uri = $binding['subject']['value'] ?? '';
            $id = str_replace('http://vocab.getty.edu/ulan/', '', $uri);

            $parsed[] = [
                'id' => $id,
                'uri' => $uri,
                'source' => self::SOURCE_ULAN,
                'label' => $binding['prefLabel']['value'] ?? null,
                'biography' => $binding['scopeNote']['value'] ?? null,
                'birthDate' => $binding['birthDate']['value'] ?? null,
                'deathDate' => $binding['deathDate']['value'] ?? null,
                'nationality' => $binding['nationality']['value'] ?? null,
                'type' => $this->mapULANType($binding['type']['value'] ?? null),
            ];
        }

        return $parsed;
    }

    /**
     * Map ULAN type URI to simple type
     */
    protected function mapULANType($typeUri): string
    {
        if (strpos($typeUri, 'PersonConcept') !== false) {
            return 'person';
        }
        if (strpos($typeUri, 'GroupConcept') !== false) {
            return 'corporate_body';
        }
        return 'unknown';
    }

    /**
     * Detect VIAF record type
     */
    protected function detectVIAFType($data): string
    {
        $nameType = $data['nameType'] ?? '';

        if ($nameType === 'Personal') {
            return 'person';
        }
        if ($nameType === 'Corporate') {
            return 'corporate_body';
        }

        return 'unknown';
    }

    /**
     * Format Wikidata date
     */
    protected function formatWikidataDate($dateStr): ?string
    {
        if (!$dateStr) {
            return null;
        }

        // Wikidata dates are in ISO format with timezone
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $dateStr, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }

        return $dateStr;
    }
}