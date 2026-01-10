<?php

class apiv2SearchAction extends AhgApiAction
{
    public function POST($request, $data = null)
    {
        if (!$this->hasScope('read')) {
            return $this->error(403, 'Forbidden', 'Read scope required');
        }

        $query = $data['query'] ?? '';
        $limit = min($data['limit'] ?? 10, 100);
        $skip = $data['skip'] ?? 0;
        $filters = $data['filters'] ?? [];

        if (empty($query)) {
            return $this->error(400, 'Bad Request', 'query is required');
        }

        try {
            // Use Elasticsearch if available
            $client = QubitSearch::getInstance()->client;
            
            $esQuery = new \Elastica\Query\BoolQuery();
            $esQuery->addMust(new \Elastica\Query\QueryString($query));

            // Apply filters
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
                    'score' => $hit->getScore()
                ];
            }

            return $this->success([
                'total' => $resultSet->getTotalHits(),
                'limit' => $limit,
                'skip' => $skip,
                'results' => $results
            ]);

        } catch (Exception $e) {
            return $this->error(500, 'Search Error', $e->getMessage());
        }
    }
}
