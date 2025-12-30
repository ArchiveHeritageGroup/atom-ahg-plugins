<?php
/**
 * CCO Vocabulary Search API
 * 
 * Provides REST endpoint for vocabulary autocomplete.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class apiVocabularySearchAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $vocabulary = $request->getParameter('vocabulary');
        $query = $request->getParameter('q', '');
        $limit = min((int) $request->getParameter('limit', 20), 50);

        if (strlen($query) < 2) {
            echo json_encode(['results' => [], 'error' => 'Query too short']);
            return sfView::NONE;
        }

        try {
            $service = ahgCCOVocabularyService::getInstance();
            $results = $service->search($vocabulary, $query, $limit);

            echo json_encode([
                'results' => $results,
                'vocabulary' => $vocabulary,
                'query' => $query,
                'count' => count($results)
            ]);

        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);
            echo json_encode([
                'error' => 'Search failed',
                'message' => $e->getMessage()
            ]);
        }

        return sfView::NONE;
    }
}
