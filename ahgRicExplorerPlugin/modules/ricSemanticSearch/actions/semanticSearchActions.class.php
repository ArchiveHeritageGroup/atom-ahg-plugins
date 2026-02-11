<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * RiC Semantic Search Actions
 * 
 * Controller actions for the semantic search interface.
 */

class ricSemanticSearchActions extends AhgController
{
    /**
     * Main search page
     */
    public function executeIndex($request)
    {
        $this->searchApiUrl = $this->config('app_ric_search_api', 'http://localhost:5001/api');
        $this->atomBaseUrl = $this->config('app_siteBaseUrl', '');
    }
    
    /**
     * Embedded widget for sidebar/panel
     */
    public function executeWidget($request)
    {
        $this->searchApiUrl = $this->config('app_ric_search_api', 'http://localhost:5001/api');
    }
    
    /**
     * Proxy search requests (if needed for CORS)
     */
    public function executeProxy($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $apiUrl = $this->config('app_ric_search_api', 'http://localhost:5001/api');
        $endpoint = $request->getParameter('endpoint', 'search');
        $query = $request->getParameter('q', '');
        
        $url = $apiUrl . '/' . $endpoint;
        
        $ch = curl_init();
        
        if ($request->isMethod('POST')) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        } else {
            $url .= '?q=' . urlencode($query);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return $this->renderText(json_encode(['error' => 'Search API unavailable']));
        }
        
        return $this->renderText($response);
    }
    
    /**
     * Get search examples
     */
    public function executeExamples($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $examples = [
            [
                'query' => 'records created by',
                'description' => 'Find records by a specific creator',
                'placeholder' => 'records created by [name]'
            ],
            [
                'query' => 'records about',
                'description' => 'Search by subject matter',
                'placeholder' => 'records about [topic]'
            ],
            [
                'query' => 'records from',
                'description' => 'Find records from a specific place',
                'placeholder' => 'records from [place]'
            ],
            [
                'query' => 'records between 1900-1950',
                'description' => 'Search by date range',
                'placeholder' => 'records between [year]-[year]'
            ],
            [
                'query' => 'all fonds',
                'description' => 'List all top-level fonds'
            ],
            [
                'query' => 'all series',
                'description' => 'List all series'
            ],
            [
                'query' => 'heritage assets',
                'description' => 'Show GRAP heritage assets'
            ],
            [
                'query' => 'items in poor condition',
                'description' => 'Find items needing conservation'
            ],
        ];
        
        return $this->renderText(json_encode(['examples' => $examples]));
    }
}
