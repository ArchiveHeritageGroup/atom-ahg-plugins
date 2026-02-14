<?php

use AtomFramework\Http\Controllers\AhgApiController;
class apiv2SearchAction extends AhgApiController
{
    public function POST($request, $data = null)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $queryStr = $data['query'] ?? '';
        $limit = min($data['limit'] ?? 10, 100);
        $skip = $data['skip'] ?? 0;
        $filters = $data['filters'] ?? [];

        if (empty($queryStr)) {
            return $this->error(400, 'Bad Request', 'query is required');
        }

        try {
            // Dual-mode: Framework SearchService (standalone) or Elastica (legacy)
            if (class_exists('\\AtomFramework\\Services\\Search\\SearchService')) {
                $page = (int) floor($skip / max($limit, 1)) + 1;
                $searchFilters = [];
                if (!empty($filters['repository'])) {
                    $searchFilters['repository'] = $filters['repository'];
                }
                if (!empty($filters['level'])) {
                    $searchFilters['level'] = (int) $filters['level'];
                }

                $result = \AtomFramework\Services\Search\SearchService::search($queryStr, [
                    'entityType' => 'informationobject',
                    'filters' => $searchFilters,
                    'page' => $page,
                    'limit' => $limit,
                    'publicationStatus' => 'published',
                ]);

                $results = [];
                foreach ($result['hits'] ?? [] as $hit) {
                    $results[] = [
                        'id' => $hit['id'] ?? null,
                        'slug' => $hit['slug'] ?? null,
                        'title' => $hit['title'] ?? null,
                        'level_of_description' => $hit['level'] ?? null,
                        'score' => $hit['_score'] ?? 0,
                    ];
                }

                return $this->success([
                    'total' => $result['total'] ?? 0,
                    'limit' => $limit,
                    'skip' => $skip,
                    'results' => $results,
                ]);
            }

            // Legacy: Elastica via QubitSearch
            $client = QubitSearch::getInstance()->client;

            $esQuery = new \Elastica\Query\BoolQuery();
            $esQuery->addMust(new \Elastica\Query\QueryString($queryStr));

            if (!empty($filters['repository'])) {
                $esQuery->addFilter(new \Elastica\Query\Term(['repository.slug' => $filters['repository']]));
            }
            if (!empty($filters['level'])) {
                $esQuery->addFilter(new \Elastica\Query\Term(['levelOfDescriptionId' => $filters['level']]));
            }

            $query = new \Elastica\Query($esQuery);
            $query->setSize($limit);
            $query->setFrom($skip);

            $resultSet = QubitSearch::getInstance()->index->getType('QubitInformationObject')->search($query);

            $results = [];
            foreach ($resultSet->getResults() as $hit) {
                $source = $hit->getSource();
                $results[] = [
                    'id' => $hit->getId(),
                    'slug' => $source['slug'] ?? null,
                    'title' => $source['i18n']['en']['title'] ?? null,
                    'level_of_description' => $source['levelOfDescription'] ?? null,
                    'score' => $hit->getScore(),
                ];
            }

            return $this->success([
                'total' => $resultSet->getTotalHits(),
                'limit' => $limit,
                'skip' => $skip,
                'results' => $results,
            ]);
        } catch (Exception $e) {
            return $this->error(500, 'Search Error', $e->getMessage());
        }
    }
}
