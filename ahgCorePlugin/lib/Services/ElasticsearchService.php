<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Elasticsearch Service
 *
 * Handles direct ES indexing and deletion for entities migrated to Laravel QB.
 * Uses HTTP calls (curl) instead of Propel-based arElasticSearchPlugin.
 */
class ElasticsearchService
{
    protected static ?array $config = null;

    /**
     * Get ES configuration.
     */
    public static function getEsConfig(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        // Read from arElasticSearchPlugin config if available
        $host = 'localhost';
        $port = '9200';
        $index = 'atom';

        // Try to read from search.yml
        $searchYml = \sfConfig::get('sf_root_dir') . '/plugins/arElasticSearchPlugin/config/search.yml';
        if (file_exists($searchYml)) {
            $yaml = \sfYaml::load($searchYml);
            if (isset($yaml['all']['server']['host'])) {
                $host = $yaml['all']['server']['host'];
            }
            if (isset($yaml['all']['server']['port'])) {
                $port = $yaml['all']['server']['port'];
            }
            if (isset($yaml['all']['index']['name'])) {
                $index = $yaml['all']['index']['name'];
            }
        }

        self::$config = [
            'host' => $host,
            'port' => $port,
            'index' => $index,
            'base_url' => "http://{$host}:{$port}",
        ];

        return self::$config;
    }

    /**
     * Index an actor document in Elasticsearch.
     */
    public static function indexActor(int $id, string $culture = 'en'): void
    {
        $actor = DB::table('actor')
            ->where('actor.id', $id)
            ->first();

        if (!$actor) {
            return;
        }

        $i18n = I18nService::getWithFallback('actor_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        $doc = [
            'authorizedFormOfName' => ['en' => $i18n->authorized_form_of_name ?? '', $culture => $i18n->authorized_form_of_name ?? ''],
            'slug' => $slug,
            'entityTypeId' => $actor->entity_type_id,
            'descriptionIdentifier' => $actor->description_identifier,
            'corporateBodyIdentifiers' => $actor->corporate_body_identifiers,
            'datesOfExistence' => ['en' => $i18n->dates_of_existence ?? '', $culture => $i18n->dates_of_existence ?? ''],
            'createdAt' => $actor->created_at ?? date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
        ];

        self::putDocument('qubitactor', $id, $doc);
    }

    /**
     * Index a repository document in Elasticsearch.
     */
    public static function indexRepository(int $id, string $culture = 'en'): void
    {
        $repo = DB::table('repository')
            ->join('actor', 'actor.id', '=', 'repository.id')
            ->where('repository.id', $id)
            ->first();

        if (!$repo) {
            return;
        }

        $actorI18n = I18nService::getWithFallback('actor_i18n', $id, $culture);
        $repoI18n = I18nService::getWithFallback('repository_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        $contact = DB::table('contact_information')
            ->leftJoin('contact_information_i18n', function ($j) use ($culture) {
                $j->on('contact_information.id', '=', 'contact_information_i18n.id')
                    ->where('contact_information_i18n.culture', '=', $culture);
            })
            ->where('contact_information.actor_id', $id)
            ->first();

        $doc = [
            'authorizedFormOfName' => ['en' => $actorI18n->authorized_form_of_name ?? '', $culture => $actorI18n->authorized_form_of_name ?? ''],
            'slug' => $slug,
            'identifier' => $repo->identifier,
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
        ];

        if ($contact) {
            $doc['contactInformations'] = [[
                'city' => ['en' => $contact->city ?? ''],
                'region' => ['en' => $contact->region ?? ''],
                'countryCode' => $contact->country_code ?? '',
            ]];
        }

        self::putDocument('qubitrepository', $id, $doc);
    }

    /**
     * Index an accession document in Elasticsearch.
     */
    public static function indexAccession(int $id, string $culture = 'en'): void
    {
        $acc = DB::table('accession')
            ->where('id', $id)
            ->first();

        if (!$acc) {
            return;
        }

        $i18n = I18nService::getWithFallback('accession_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        $doc = [
            'identifier' => $acc->identifier,
            'title' => ['en' => $i18n->title ?? '', $culture => $i18n->title ?? ''],
            'scopeAndContent' => ['en' => $i18n->scope_and_content ?? '', $culture => $i18n->scope_and_content ?? ''],
            'slug' => $slug,
            'date' => $acc->date,
            'createdAt' => $acc->created_at,
            'updatedAt' => $acc->updated_at,
        ];

        // Add donors
        $donors = DB::table('relation')
            ->join('actor_i18n', function ($j) use ($culture) {
                $j->on('relation.object_id', '=', 'actor_i18n.id')
                    ->where('actor_i18n.culture', '=', $culture);
            })
            ->where('relation.subject_id', $id)
            ->where('relation.type_id', \QubitTerm::DONOR_ID)
            ->select('actor_i18n.authorized_form_of_name')
            ->get();

        if ($donors->isNotEmpty()) {
            $doc['donors'] = $donors->map(fn ($d) => [
                'authorizedFormOfName' => ['en' => $d->authorized_form_of_name ?? ''],
            ])->all();
        }

        self::putDocument('qubitaccession', $id, $doc);
    }

    /**
     * Index a term document in Elasticsearch.
     */
    public static function indexTerm(int $id, string $culture = 'en'): void
    {
        $term = DB::table('term')
            ->where('id', $id)
            ->first();

        if (!$term) {
            return;
        }

        $i18n = I18nService::getWithFallback('term_i18n', $id, $culture);
        $slug = ObjectService::getSlug($id);

        // Count usage
        $useCount = DB::table('object_term_relation')
            ->where('term_id', $id)
            ->count();

        $doc = [
            'name' => ['en' => $i18n->name ?? '', $culture => $i18n->name ?? ''],
            'slug' => $slug,
            'taxonomyId' => $term->taxonomy_id,
            'isProtected' => false,
            'numberOfDescendants' => DB::table('term')
                ->where('parent_id', $id)
                ->count(),
            'createdAt' => date('Y-m-d\TH:i:s\Z'),
            'updatedAt' => date('Y-m-d\TH:i:s\Z'),
            'useCount' => $useCount,
        ];

        self::putDocument('qubitterm', $id, $doc);
    }

    /**
     * Delete a document from Elasticsearch.
     */
    public static function deleteDocument(string $type, int $id): void
    {
        $config = self::getEsConfig();
        $url = "{$config['base_url']}/{$config['index']}_{$type}/{$type}/{$id}";

        self::httpRequest('DELETE', $url);
    }

    /**
     * PUT a document into Elasticsearch.
     */
    protected static function putDocument(string $type, int $id, array $doc): void
    {
        $config = self::getEsConfig();
        $url = "{$config['base_url']}/{$config['index']}_{$type}/{$type}/{$id}";

        self::httpRequest('PUT', $url, $doc);
    }

    /**
     * Execute an HTTP request to Elasticsearch.
     */
    protected static function httpRequest(string $method, string $url, ?array $body = null): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            // Silently fail — ES indexing should not break CRUD operations
            error_log("ElasticsearchService: {$method} {$url} returned HTTP {$httpCode}");

            return null;
        }

        return json_decode($response, true);
    }
}
