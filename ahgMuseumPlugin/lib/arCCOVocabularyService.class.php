<?php

/**
 * CCO Vocabulary Service
 *
 * Provides vocabulary lookup and autocomplete for CCO fields.
 * Integrates with Getty Vocabularies (AAT, TGN, ULAN) and local taxonomies.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

use Illuminate\Database\Capsule\Manager as DB;

class arCCOVocabularyService
{
    // Getty SPARQL endpoints
    const GETTY_AAT_SPARQL = 'http://vocab.getty.edu/sparql';
    const GETTY_TGN_SPARQL = 'http://vocab.getty.edu/sparql';
    const GETTY_ULAN_SPARQL = 'http://vocab.getty.edu/sparql';

    // Cache timeout (seconds)
    const CACHE_TIMEOUT = 86400; // 24 hours

    // Known taxonomy IDs (from AtoM)
    const TAXONOMY_SUBJECT_ID = 35;
    const TAXONOMY_PLACE_ID = 42;
    const TAXONOMY_NAME_ID = 36;
    const TAXONOMY_GENRE_ID = 78;
    const TAXONOMY_FUNCTION_ID = 73;
    const TAXONOMY_MATERIAL_TYPE_ID = 50;
    const TAXONOMY_LEVEL_OF_DESCRIPTION_ID = 34;

    protected static $instance;
    protected $cache = [];

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Search vocabulary
     */
    public function search($vocabulary, $query, $limit = 20): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        // Check cache
        $cacheKey = md5($vocabulary . $query . $limit);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $results = [];

        switch ($vocabulary) {
            case 'aat_object_types':
                $results = $this->searchAATObjectTypes($query, $limit);
                break;
            case 'aat_materials':
                $results = $this->searchAATMaterials($query, $limit);
                break;
            case 'aat_techniques':
                $results = $this->searchAATTechniques($query, $limit);
                break;
            case 'aat_styles':
                $results = $this->searchAATStyles($query, $limit);
                break;
            case 'aat_creator_roles':
                $results = $this->searchAATRoles($query, $limit);
                break;
            case 'aat_cultures':
                $results = $this->searchAATCultures($query, $limit);
                break;
            case 'tgn':
                $results = $this->searchTGN($query, $limit);
                break;
            case 'ulan':
                $results = $this->searchULAN($query, $limit);
                break;
            case 'iconclass':
                $results = $this->searchIconclass($query, $limit);
                break;
            default:
                // Local taxonomy lookup
                $results = $this->searchLocalTaxonomy($vocabulary, $query, $limit);
        }

        $this->cache[$cacheKey] = $results;
        return $results;
    }

    /**
     * Search AAT for object types
     */
    protected function searchAATObjectTypes($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?subject ?label ?scopeNote WHERE {
                ?subject a gvp:Concept ;
                    skos:inScheme <http://vocab.getty.edu/aat/> ;
                    gvp:broaderGeneric* <http://vocab.getty.edu/aat/300264092> ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql);
    }

    /**
     * Search AAT for materials
     */
    protected function searchAATMaterials($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?subject ?label ?scopeNote WHERE {
                ?subject a gvp:Concept ;
                    skos:inScheme <http://vocab.getty.edu/aat/> ;
                    gvp:broaderGeneric* <http://vocab.getty.edu/aat/300010358> ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql);
    }

    /**
     * Search AAT for techniques
     */
    protected function searchAATTechniques($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?subject ?label ?scopeNote WHERE {
                ?subject a gvp:Concept ;
                    skos:inScheme <http://vocab.getty.edu/aat/> ;
                    gvp:broaderGeneric* <http://vocab.getty.edu/aat/300053001> ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql);
    }

    /**
     * Search AAT for styles/periods
     */
    protected function searchAATStyles($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?subject ?label ?scopeNote WHERE {
                ?subject a gvp:Concept ;
                    skos:inScheme <http://vocab.getty.edu/aat/> ;
                    gvp:broaderGeneric* <http://vocab.getty.edu/aat/300264088> ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql);
    }

    /**
     * Search AAT for roles
     */
    protected function searchAATRoles($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?subject ?label ?scopeNote WHERE {
                ?subject a gvp:Concept ;
                    skos:inScheme <http://vocab.getty.edu/aat/> ;
                    gvp:broaderGeneric* <http://vocab.getty.edu/aat/300024979> ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql);
    }

    /**
     * Search AAT for cultures
     */
    protected function searchAATCultures($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?subject ?label ?scopeNote WHERE {
                ?subject a gvp:Concept ;
                    skos:inScheme <http://vocab.getty.edu/aat/> ;
                    gvp:broaderGeneric* <http://vocab.getty.edu/aat/300111155> ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql);
    }

    /**
     * Search TGN for places
     */
    protected function searchTGN($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            PREFIX wgs84: <http://www.w3.org/2003/01/geo/wgs84_pos#>

            SELECT ?subject ?label ?lat ?long ?parentString WHERE {
                ?subject a gvp:AdminPlaceConcept ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en' || lang(?lit) = '')
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject wgs84:lat ?lat ; wgs84:long ?long }
                OPTIONAL { ?subject gvp:parentString ?parentString }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql, 'tgn');
    }

    /**
     * Search ULAN for artists
     */
    protected function searchULAN($query, $limit): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
            PREFIX foaf: <http://xmlns.com/foaf/0.1/>
            PREFIX schema: <http://schema.org/>

            SELECT ?subject ?label ?bio ?birthDate ?deathDate ?nationality WHERE {
                ?subject a gvp:PersonConcept ;
                    xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(CONTAINS(LCASE(?lit), LCASE(\"$query\")))
                OPTIONAL { ?subject foaf:focus/gvp:biographyPreferred/schema:description ?bio }
                OPTIONAL { ?subject foaf:focus/gvp:biographyPreferred/gvp:estStart ?birthDate }
                OPTIONAL { ?subject foaf:focus/gvp:biographyPreferred/gvp:estEnd ?deathDate }
                OPTIONAL { ?subject foaf:focus/gvp:nationalityPreferred/gvp:prefLabelGVP/xl:literalForm ?nationality }
            }
            LIMIT $limit
        ";

        return $this->executeGettySparql($sparql, 'ulan');
    }

    /**
     * Search Iconclass (using Iconclass API)
     */
    protected function searchIconclass($query, $limit): array
    {
        $url = 'http://iconclass.org/api/search?q=' . urlencode($query) . '&size=' . $limit;

        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            $results = [];
            if (isset($data['records'])) {
                foreach ($data['records'] as $record) {
                    $results[] = [
                        'id' => $record['n'],
                        'label' => $record['txt']['en'] ?? $record['txt'][array_key_first($record['txt'])],
                        'notation' => $record['n'],
                    ];
                }
            }
            return $results;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Search local AtoM taxonomy
     */
    protected function searchLocalTaxonomy($taxonomyName, $query, $limit): array
    {
        // Map vocabulary name to taxonomy ID
        $taxonomyId = $this->getTaxonomyIdByName($taxonomyName);

        if (!$taxonomyId) {
            return [];
        }

        // Search AtoM taxonomy using Laravel
        $terms = DB::table('term as t')
            ->join('term_i18n as ti', 't.id', '=', 'ti.id')
            ->where('t.taxonomy_id', $taxonomyId)
            ->where('ti.name', 'LIKE', '%' . $query . '%')
            ->where(function ($q) {
                $q->where('ti.culture', 'en')
                    ->orWhere('ti.culture', sfConfig::get('sf_default_culture', 'en'));
            })
            ->select('t.id', 'ti.name', 'ti.culture')
            ->orderBy('ti.name')
            ->limit($limit)
            ->get();

        $results = [];
        $seenIds = [];

        foreach ($terms as $term) {
            // Avoid duplicates from multiple cultures
            if (isset($seenIds[$term->id])) {
                continue;
            }
            $seenIds[$term->id] = true;

            $results[] = [
                'id' => $term->id,
                'label' => $term->name,
            ];
        }

        return $results;
    }

    /**
     * Get taxonomy ID by name/slug
     */
    protected function getTaxonomyIdByName(string $name): ?int
    {
        // Direct mapping for known vocabularies
        $directMap = [
            'cco_title_types' => null, // Custom - check database
            'cco_attribution' => null, // Custom - check database
            'subjects' => self::TAXONOMY_SUBJECT_ID,
            'places' => self::TAXONOMY_PLACE_ID,
            'names' => self::TAXONOMY_NAME_ID,
            'genres' => self::TAXONOMY_GENRE_ID,
            'functions' => self::TAXONOMY_FUNCTION_ID,
            'material_types' => self::TAXONOMY_MATERIAL_TYPE_ID,
            'levels_of_description' => self::TAXONOMY_LEVEL_OF_DESCRIPTION_ID,
        ];

        if (isset($directMap[$name])) {
            return $directMap[$name];
        }

        // Try to find by taxonomy name in database
        $taxonomy = DB::table('taxonomy as t')
            ->leftJoin('taxonomy_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->where(function ($query) use ($name) {
                $query->where('ti.name', $name)
                    ->orWhere('ti.name', str_replace('_', ' ', $name))
                    ->orWhere('ti.name', ucwords(str_replace('_', ' ', $name)));
            })
            ->select('t.id')
            ->first();

        return $taxonomy ? $taxonomy->id : null;
    }

    /**
     * Execute Getty SPARQL query
     */
    protected function executeGettySparql($sparql, $vocabulary = 'aat'): array
    {
        $endpoint = self::GETTY_AAT_SPARQL;

        $url = $endpoint . '?query=' . urlencode($sparql) . '&format=json';

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'header' => 'Accept: application/sparql-results+json',
                ],
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return [];
            }

            $data = json_decode($response, true);

            if (!isset($data['results']['bindings'])) {
                return [];
            }

            $results = [];
            foreach ($data['results']['bindings'] as $binding) {
                $result = [
                    'id' => $binding['subject']['value'] ?? null,
                    'label' => $binding['label']['value'] ?? null,
                ];

                // Add additional fields based on vocabulary
                if (isset($binding['scopeNote'])) {
                    $result['definition'] = $binding['scopeNote']['value'];
                }
                if (isset($binding['lat'])) {
                    $result['latitude'] = $binding['lat']['value'];
                }
                if (isset($binding['long'])) {
                    $result['longitude'] = $binding['long']['value'];
                }
                if (isset($binding['parentString'])) {
                    $result['hierarchy'] = $binding['parentString']['value'];
                }
                if (isset($binding['birthDate'])) {
                    $result['birthDate'] = $binding['birthDate']['value'];
                }
                if (isset($binding['deathDate'])) {
                    $result['deathDate'] = $binding['deathDate']['value'];
                }
                if (isset($binding['nationality'])) {
                    $result['nationality'] = $binding['nationality']['value'];
                }

                $results[] = $result;
            }

            return $results;
        } catch (Exception $e) {
            error_log('Getty SPARQL error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get term details by ID
     */
    public function getTermById($vocabulary, $id): ?array
    {
        // Check if this is a Getty URI
        if (strpos($id, 'vocab.getty.edu') !== false) {
            return $this->getGettyTermById($id);
        }

        // Local term lookup
        $term = DB::table('term as t')
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->where('t.id', $id)
            ->select('t.id', 't.taxonomy_id', 't.parent_id', 'ti.name')
            ->first();

        if (!$term) {
            return null;
        }

        return [
            'id' => $term->id,
            'label' => $term->name,
            'taxonomy_id' => $term->taxonomy_id,
            'parent_id' => $term->parent_id,
        ];
    }

    /**
     * Get Getty term by URI
     */
    protected function getGettyTermById(string $uri): ?array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>

            SELECT ?label ?scopeNote WHERE {
                <{$uri}> xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
                OPTIONAL { <{$uri}> skos:scopeNote ?scopeNote . FILTER(lang(?scopeNote) = 'en') }
            }
            LIMIT 1
        ";

        $results = $this->executeGettySparql($sparql);

        if (empty($results)) {
            return null;
        }

        $result = $results[0];
        $result['id'] = $uri;

        return $result;
    }

    /**
     * Get term hierarchy
     */
    public function getTermHierarchy($vocabulary, $id): array
    {
        // Check if this is a Getty URI
        if (strpos($id, 'vocab.getty.edu') !== false) {
            return $this->getGettyTermHierarchy($id);
        }

        // Local term hierarchy
        $hierarchy = [];
        $currentId = $id;

        while ($currentId) {
            $term = DB::table('term as t')
                ->leftJoin('term_i18n as ti', function ($join) {
                    $join->on('t.id', '=', 'ti.id')
                        ->where('ti.culture', '=', 'en');
                })
                ->where('t.id', $currentId)
                ->select('t.id', 't.parent_id', 'ti.name')
                ->first();

            if (!$term) {
                break;
            }

            array_unshift($hierarchy, [
                'id' => $term->id,
                'label' => $term->name,
            ]);

            $currentId = $term->parent_id;

            // Avoid infinite loops
            if (count($hierarchy) > 20) {
                break;
            }
        }

        return $hierarchy;
    }

    /**
     * Get Getty term hierarchy
     */
    protected function getGettyTermHierarchy(string $uri): array
    {
        $sparql = "
            PREFIX gvp: <http://vocab.getty.edu/ontology#>
            PREFIX xl: <http://www.w3.org/2008/05/skos-xl#>

            SELECT ?ancestor ?label WHERE {
                <{$uri}> gvp:broaderGeneric* ?ancestor .
                ?ancestor xl:prefLabel ?xlLabel .
                ?xlLabel gvp:term ?label ;
                    xl:literalForm ?lit .
                FILTER(lang(?lit) = 'en')
            }
        ";

        $results = $this->executeGettySparql($sparql);

        return array_map(function ($r) {
            return [
                'id' => $r['id'],
                'label' => $r['label'],
            ];
        }, $results);
    }

    /**
     * Create or find local term
     */
    public function findOrCreateLocalTerm(int $taxonomyId, string $name, ?int $parentId = null): int
    {
        // Check if term exists
        $existing = DB::table('term as t')
            ->join('term_i18n as ti', 't.id', '=', 'ti.id')
            ->where('t.taxonomy_id', $taxonomyId)
            ->where('ti.name', $name)
            ->where('ti.culture', 'en')
            ->select('t.id')
            ->first();

        if ($existing) {
            return $existing->id;
        }

        // Get taxonomy root for parent
        if (!$parentId) {
            $root = DB::table('term')
                ->where('taxonomy_id', $taxonomyId)
                ->whereNull('parent_id')
                ->first();
            $parentId = $root ? $root->id : null;
        }

        // Create object entry
        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitTerm',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Create term
        DB::table('term')->insert([
            'id' => $objectId,
            'taxonomy_id' => $taxonomyId,
            'parent_id' => $parentId,
        ]);

        // Create i18n entry
        DB::table('term_i18n')->insert([
            'id' => $objectId,
            'culture' => 'en',
            'name' => $name,
        ]);

        return $objectId;
    }

    /**
     * Get all terms for a taxonomy
     */
    public function getTaxonomyTerms(int $taxonomyId, ?int $parentId = null): array
    {
        $query = DB::table('term as t')
            ->leftJoin('term_i18n as ti', function ($join) {
                $join->on('t.id', '=', 'ti.id')
                    ->where('ti.culture', '=', 'en');
            })
            ->where('t.taxonomy_id', $taxonomyId)
            ->select('t.id', 't.parent_id', 'ti.name')
            ->orderBy('ti.name');

        if ($parentId !== null) {
            $query->where('t.parent_id', $parentId);
        }

        $terms = $query->get();

        $results = [];
        foreach ($terms as $term) {
            $results[] = [
                'id' => $term->id,
                'label' => $term->name,
                'parent_id' => $term->parent_id,
            ];
        }

        return $results;
    }

    /**
     * Cache vocabulary results to database
     */
    public function cacheResults(string $vocabulary, string $query, array $results): void
    {
        try {
            DB::table('vocabulary_cache')->updateOrInsert(
                [
                    'vocabulary' => $vocabulary,
                    'query_hash' => md5($query),
                ],
                [
                    'query_text' => $query,
                    'results' => json_encode($results),
                    'expires_at' => date('Y-m-d H:i:s', time() + self::CACHE_TIMEOUT),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        } catch (Exception $e) {
            // Cache table might not exist
        }
    }

    /**
     * Get cached results
     */
    public function getCachedResults(string $vocabulary, string $query): ?array
    {
        try {
            $cached = DB::table('vocabulary_cache')
                ->where('vocabulary', $vocabulary)
                ->where('query_hash', md5($query))
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->first();

            if ($cached) {
                return json_decode($cached->results, true);
            }
        } catch (Exception $e) {
            // Cache table might not exist
        }

        return null;
    }

    /**
     * Ensure cache table exists
     */
    public static function ensureCacheTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS vocabulary_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vocabulary VARCHAR(100) NOT NULL,
            query_hash VARCHAR(32) NOT NULL,
            query_text VARCHAR(500),
            results MEDIUMTEXT,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_vocab_query (vocabulary, query_hash),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            DB::statement($sql);
        } catch (Exception $e) {
            // Table might already exist
        }
    }
}