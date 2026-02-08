<?php

namespace AhgAccessionManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class AccessionBrowseService
{
    protected string $culture;
    protected string $esHost;
    protected int $esPort;
    protected string $indexName;

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
        $this->esHost = \sfConfig::get('app_opensearch_host', 'localhost');
        $this->esPort = (int) \sfConfig::get('app_opensearch_port', 9200);

        $indexName = \sfConfig::get('app_opensearch_index_name', '');
        if (empty($indexName)) {
            try {
                $dbName = DB::connection()->getDatabaseName();
            } catch (\Exception $e) {
                $dbName = 'archive';
            }
            $indexName = $dbName;
        }
        $this->indexName = $indexName . '_qubitaccession';
    }

    public function browse(array $params): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = max(1, min(100, (int) ($params['limit'] ?? \sfConfig::get('app_hits_per_page', 30))));
        $skip = ($page - 1) * $limit;

        $maxResultWindow = (int) \sfConfig::get('app_opensearch_max_result_window', 10000);
        if ($skip + $limit > $maxResultWindow) {
            $skip = max(0, $maxResultWindow - $limit);
            $page = (int) floor($skip / $limit) + 1;
        }

        $c = $this->culture;

        $must = [];

        // Text search
        $subquery = trim($params['subquery'] ?? '');
        if ('' !== $subquery) {
            $fields = [
                "identifier^10",
                "donors.i18n.{$c}.authorizedFormOfName^10",
                "i18n.{$c}.title^10",
                "i18n.{$c}.scopeAndContent^10",
                "i18n.{$c}.locationInformation^5",
                "i18n.{$c}.processingNotes^5",
                "i18n.{$c}.sourceOfAcquisition^5",
                "i18n.{$c}.archivalHistory^5",
                "i18n.{$c}.appraisal",
                "i18n.{$c}.physicalCharacteristics",
                "i18n.{$c}.receivedExtentUnits",
                "acquisitionType.i18n.{$c}.name",
                "processingPriority.i18n.{$c}.name",
                "processingStatus.i18n.{$c}.name",
                "resourceType.i18n.{$c}.name",
                "alternativeIdentifiers.i18n.{$c}.name",
                "creators.i18n.{$c}.authorizedFormOfName",
                "alternativeIdentifiers.i18n.{$c}.note",
                "alternativeIdentifiers.type.i18n.{$c}.name",
                "accessionEvents.i18n.{$c}.agent",
                "accessionEvents.type.i18n.{$c}.name",
                "accessionEvents.notes.i18n.{$c}.content",
                "donors.contactInformations.contactPerson",
                "accessionEvents.dateString",
            ];

            $must[] = [
                'query_string' => [
                    'query' => $subquery,
                    'fields' => $fields,
                    'default_operator' => 'AND',
                ],
            ];
        }

        // Build bool query
        $boolQuery = [];
        if (!empty($must)) {
            $boolQuery['must'] = $must;
        } else {
            $boolQuery['must'] = [['match_all' => new \stdClass()]];
        }

        // Sort
        $sort = $this->buildSort(
            $params['sort'] ?? 'lastUpdated',
            $params['sortDir'] ?? 'desc'
        );

        // Source fields
        $source = [
            'slug',
            'identifier',
            'date',
            'updatedAt',
            'i18n',
        ];

        $body = [
            'size' => $limit,
            'from' => $skip,
            '_source' => $source,
            'query' => ['bool' => $boolQuery],
            'sort' => $sort,
        ];

        $response = $this->esRequest($this->indexName, '/_search', $body);

        if (!$response) {
            return [
                'hits' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ];
        }

        $hits = [];
        foreach ($response['hits']['hits'] ?? [] as $hit) {
            $doc = $hit['_source'] ?? [];
            $doc['_id'] = $hit['_id'];
            $hits[] = $doc;
        }

        $total = $response['hits']['total']['value'] ?? 0;

        return [
            'hits' => $hits,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    protected function buildSort(string $sort, string $dir): array
    {
        $c = $this->culture;
        $direction = ('asc' === strtolower($dir)) ? 'asc' : 'desc';

        return match ($sort) {
            'identifier', 'accessionNumber' => [
                ['identifier.untouched' => ['order' => $direction, 'unmapped_type' => 'keyword']],
            ],
            'title', 'alphabetic' => [
                ["i18n.{$c}.title.alphasort" => ['order' => $direction, 'unmapped_type' => 'keyword']],
            ],
            'acquisitionDate' => [
                ['date' => ['order' => $direction, 'missing' => '_last']],
            ],
            'relevance' => [],
            default => [
                ['updatedAt' => ['order' => $direction]],
            ],
        };
    }

    public function extractI18nField(array $doc, string $field): string
    {
        $cultures = [$this->culture, 'en', 'fr', 'es'];
        foreach ($cultures as $c) {
            if (!empty($doc['i18n'][$c][$field])) {
                return $doc['i18n'][$c][$field];
            }
        }

        return '';
    }

    protected function esRequest(string $index, string $endpoint, array $body): ?array
    {
        $url = sprintf('http://%s:%d/%s%s', $this->esHost, $this->esPort, $index, $endpoint);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 !== $httpCode || false === $response) {
            error_log(sprintf(
                'ahgAccessionManagePlugin ES error: HTTP %d, URL: %s',
                $httpCode,
                $url
            ));

            return null;
        }

        return json_decode($response, true);
    }
}
