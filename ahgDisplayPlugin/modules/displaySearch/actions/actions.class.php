<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Display Search Actions
 * 
 * Browse and search by object type using Elasticsearch
 */

class displaySearchActions extends AhgController
{
    protected $esService;
    protected $adapter;
    
    public function boot(): void
    {
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Elasticsearch/DisplayElasticsearchService.php';
        require_once $this->config('sf_plugins_dir') . '/ahgDisplayPlugin/lib/Elasticsearch/DisplaySearchResultAdapter.php';
        
        $this->esService = new DisplayElasticsearchService();
        $this->adapter = new DisplaySearchResultAdapter();
    }
    
    /**
     * Main search action with display facets
     */
    public function executeSearch($request)
    {
        $params = [
            'query' => $request->getParameter('query', '*'),
            'object_type' => $request->getParameter('object_type'),
            'has_digital_object' => $request->getParameter('has_digital') !== null 
                ? (bool) $request->getParameter('has_digital') 
                : null,
            'media_type' => $request->getParameter('media_type'),
            'subjects' => $request->getParameter('subjects') 
                ? (array) $request->getParameter('subjects') 
                : [],
            'sort' => $request->getParameter('sort', '_score'),
            'from' => (int) $request->getParameter('from', 0),
            'size' => (int) $request->getParameter('size', 20),
        ];
        
        $this->results = $this->esService->search($params);
        $this->params = $params;
        $this->layout = $request->getParameter('layout', 'card');
        $this->adapter = $this->adapter;
    }
    
    /**
     * Browse by object type
     */
    public function executeBrowse($request)
    {
        $objectType = $request->getParameter('type', 'archive');
        
        $validTypes = ['archive', 'museum', 'gallery', 'library', 'dam'];
        if (!in_array($objectType, $validTypes)) {
            $objectType = 'archive';
        }
        
        $params = [
            'query' => $request->getParameter('query', '*'),
            'has_digital_object' => $request->getParameter('has_digital') !== null 
                ? (bool) $request->getParameter('has_digital') 
                : null,
            'media_type' => $request->getParameter('media_type'),
            'sort' => $request->getParameter('sort', 'title_asc'),
            'from' => (int) $request->getParameter('from', 0),
            'size' => (int) $request->getParameter('size', 24),
        ];
        
        $this->objectType = $objectType;
        $this->results = $this->esService->browseByType($objectType, $params);
        $this->params = $params;
        $this->adapter = $this->adapter;
        
        // Set default layout based on type
        $this->layout = $request->getParameter('layout') ?? match($objectType) {
            'dam' => 'grid',
            'gallery' => 'masonry',
            'library' => 'list',
            default => 'card',
        };
        
        // Type-specific metadata
        $this->typeInfo = match($objectType) {
            'archive' => [
                'title' => 'Archives',
                'icon' => 'fa-archive',
                'color' => 'primary',
                'description' => 'Archival fonds, series, files and items following ISAD(G) standards',
            ],
            'museum' => [
                'title' => 'Museum Objects',
                'icon' => 'fa-landmark',
                'color' => 'success',
                'description' => 'Museum objects and specimens following Spectrum standards',
            ],
            'gallery' => [
                'title' => 'Artworks',
                'icon' => 'fa-palette',
                'color' => 'warning',
                'description' => 'Artworks, prints, and artist archives',
            ],
            'library' => [
                'title' => 'Book Collection',
                'icon' => 'fa-book',
                'color' => 'info',
                'description' => 'Books, periodicals, maps, and printed materials',
            ],
            'dam' => [
                'title' => 'Photo Archive',
                'icon' => 'fa-images',
                'color' => 'danger',
                'description' => 'Photographs, negatives, slides, and digital assets',
            ],
            default => [
                'title' => 'Browse',
                'icon' => 'fa-folder',
                'color' => 'secondary',
                'description' => '',
            ],
        };
    }
    
    /**
     * AJAX autocomplete
     */
    public function executeAutocomplete($request)
    {
        $query = $request->getParameter('query', '');
        
        if (strlen($query) < 2) {
            return $this->renderText(json_encode([]));
        }
        
        $results = $this->esService->autocomplete($query, 10);
        
        return $this->renderText(json_encode($results));
    }
    
    /**
     * Get facets only (for AJAX updates)
     */
    public function executeFacets($request)
    {
        $filters = [
            'object_type' => $request->getParameter('object_type'),
            'query' => $request->getParameter('query', '*'),
        ];
        
        $facets = $this->esService->getFacets($filters);
        
        $this->getResponse()->setContentType('application/json');
        return $this->renderText(json_encode($facets));
    }
    
    /**
     * ES Admin - reindex display data
     */
    public function executeReindex($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        
        if ($request->isMethod('post')) {
            $batchSize = (int) $request->getParameter('batch_size', 100);
            
            // Start reindex
            $count = $this->esService->reindexDisplayData($batchSize);
            
            $this->getUser()->setFlash('success', 'Reindexed display data for ' . $count . ' objects');
            $this->redirect('display/index');
        }
        
        // Show form
        $this->hasMapping = $this->esService->hasDisplayMapping();
    }
    
    /**
     * Update ES mapping
     */
    public function executeUpdateMapping($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
        
        $success = $this->esService->updateMapping();
        
        if ($success) {
            $this->getUser()->setFlash('success', 'Elasticsearch mapping updated successfully');
        } else {
            $this->getUser()->setFlash('error', 'Failed to update Elasticsearch mapping');
        }
        
        $this->redirect('display/reindex');
    }
}
