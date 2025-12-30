<?php
/**
 * Authority Search API Action
 * 
 * Provides AJAX endpoint for searching external authorities.
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 * @package ahgMuseumPlugin
 */

class apiAuthoritySearchAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        $query = $request->getParameter('q');
        $source = $request->getParameter('source', 'all');
        $type = $request->getParameter('type');
        $limit = min(intval($request->getParameter('limit', 10)), 50);

        if (!$query || strlen($query) < 2) {
            echo json_encode([
                'error' => 'Query must be at least 2 characters',
                'results' => []
            ]);
            return sfView::NONE;
        }

        try {
            $service = new arAuthorityLinkageService();
            
            if ($source === 'all') {
                $results = $service->searchAllSources($query, $type, null, $limit);
            } else {
                $sources = [$source];
                $results = $service->searchAllSources($query, $type, $sources, $limit);
            }

            echo json_encode([
                'query' => $query,
                'source' => $source,
                'results' => $results
            ]);

        } catch (Exception $e) {
            $this->getResponse()->setStatusCode(500);
            echo json_encode([
                'error' => $e->getMessage(),
                'results' => []
            ]);
        }

        return sfView::NONE;
    }
}
