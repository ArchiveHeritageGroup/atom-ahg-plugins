<?php

declare(strict_types=1);

/**
 * ahgLibraryPlugin Actions
 *
 * Main actions class including API endpoints
 *
 * @package    ahgLibraryPlugin
 * @subpackage actions
 */

class libraryActions extends sfActions
{
    /**
     * Index action - delegated to indexAction.class.php
     */
    public function executeIndex(sfWebRequest $request)
    {
        $action = new ahgLibraryPluginIndexAction($this->context, 'ahgLibraryPlugin', 'index');
        return $action->execute($request);
    }

    /**
     * Edit action - delegated to editAction.class.php
     */
    public function executeEdit(sfWebRequest $request)
    {
        $action = new ahgLibraryPluginEditAction($this->context, 'ahgLibraryPlugin', 'edit');
        return $action->execute($request);
    }

    /**
     * Add action - alias for edit with no slug
     */
    public function executeAdd(sfWebRequest $request)
    {
        $action = new ahgLibraryPluginEditAction($this->context, 'ahgLibraryPlugin', 'add');
        return $action->execute($request);
    }

    /**
     * Browse action - delegated to browseAction.class.php
     */
    public function executeBrowse(sfWebRequest $request)
    {
        $action = new ahgLibraryPluginBrowseAction($this->context, 'ahgLibraryPlugin', 'browse');
        return $action->execute($request);
    }

    /**
     * API: Search library items
     */
    public function executeApiSearch(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');

        $service = LibraryService::getInstance();

        $params = [
            'query' => $request->getParameter('q', ''),
            'material_type' => $request->getParameter('type', ''),
            'call_number' => $request->getParameter('call', ''),
            'isbn' => $request->getParameter('isbn', ''),
            'issn' => $request->getParameter('issn', ''),
            'publisher' => $request->getParameter('publisher', ''),
            'status' => $request->getParameter('status', ''),
            'sort' => $request->getParameter('sort', 'title'),
            'sort_dir' => $request->getParameter('dir', 'asc'),
            'limit' => min((int) $request->getParameter('limit', 20), 100),
            'offset' => (int) $request->getParameter('offset', 0),
        ];

        $results = $service->search($params);

        // Format results for API
        $items = array_map(function ($item) {
            return [
                'id' => $item->id,
                'information_object_id' => $item->information_object_id,
                'material_type' => $item->material_type,
                'call_number' => $item->call_number,
                'isbn' => $item->isbn,
                'issn' => $item->issn,
                'publisher' => $item->publisher,
                'publication_date' => $item->publication_date,
                'circulation_status' => $item->circulation_status,
                'primary_creator' => $item->getPrimaryCreator(),
                'creators' => $item->creators,
            ];
        }, $results['results']);

        return $this->renderText(json_encode([
            'success' => true,
            'data' => $items,
            'total' => $results['total'],
            'limit' => $results['limit'],
            'offset' => $results['offset'],
        ], JSON_PRETTY_PRINT));
    }

    /**
     * ISBN lookup (for edit form AJAX calls)
     * Alias for executeApiIsbnLookup
     */
    public function executeIsbnLookup(sfWebRequest $request)
    {
        return $this->executeApiIsbnLookup($request);
    }

    /**
     * API: ISBN lookup via Open Library
     */
    public function executeApiIsbnLookup(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');

        $isbn = $request->getParameter('isbn');

        if (empty($isbn)) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'ISBN is required',
            ]));
        }

        $service = LibraryService::getInstance();

        // Validate ISBN format
        if (!$service->validateIsbn($isbn)) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'Invalid ISBN format',
            ]));
        }

        // Check if already in database
        $existing = (new LibraryRepository())->findByIsbn($isbn);
        if ($existing) {
            return $this->renderText(json_encode([
                'success' => true,
                'source' => 'local',
                'data' => [
                    'id' => $existing->id,
                    'information_object_id' => $existing->information_object_id,
                    'isbn' => $existing->isbn,
                    'publisher' => $existing->publisher,
                    'publication_date' => $existing->publication_date,
                ],
            ]));
        }

        // Lookup via Open Library
        $bookData = $service->lookupIsbn($isbn);

        if (!$bookData) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => 'ISBN not found in Open Library',
            ]));
        }

        return $this->renderText(json_encode([
            'success' => true,
            'source' => 'openlibrary',
            'data' => $bookData,
        ], JSON_PRETTY_PRINT));
    }

    /**
     * API: Validate ISBN
     */
    public function executeApiValidateIsbn(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');

        $isbn = $request->getParameter('isbn');
        $service = LibraryService::getInstance();

        $valid = $service->validateIsbn($isbn);

        return $this->renderText(json_encode([
            'success' => true,
            'valid' => $valid,
            'formatted' => $valid ? $service->formatIsbn($isbn) : null,
            'isbn13' => $valid && strlen($service->cleanIsbn($isbn)) === 10
                ? $service->isbn10To13($isbn)
                : null,
        ]));
    }

    /**
     * API: Get statistics
     */
    public function executeApiStatistics(sfWebRequest $request)
    {
        $this->response->setContentType('application/json');

        $service = LibraryService::getInstance();
        $stats = $service->getStatistics();

        return $this->renderText(json_encode([
            'success' => true,
            'data' => $stats,
        ], JSON_PRETTY_PRINT));
    }
}
