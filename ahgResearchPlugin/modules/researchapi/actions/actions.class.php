<?php

/**
 * Research API Module - REST API Endpoints
 *
 * Provides REST API access to research portal functionality.
 * Authentication: API key via X-API-Key header or api_key parameter.
 *
 * @package ahgResearchPlugin
 * @version 2.0.0
 */
class researchapiActions extends AhgActions
{
    private ?ResearchApiService $apiService = null;
    private int $startTime;

    /**
     * Pre-execute hook - handles authentication for all API actions.
     */
    public function preExecute()
    {
        $this->startTime = (int) (microtime(true) * 1000);

        // Load API service
        $servicePath = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgResearchPlugin/lib/Services/ResearchApiService.php';
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $this->apiService = new ResearchApiService();
    }

    /**
     * Authenticate the request using API key.
     *
     * @return bool True if authenticated
     */
    private function authenticate(): bool
    {
        // Get API key from header or parameter
        $apiKey = $this->getRequest()->getHttpHeader('X-API-Key')
            ?? $this->getRequest()->getParameter('api_key');

        if (!$apiKey) {
            return false;
        }

        $result = $this->apiService->authenticate($apiKey);
        return $result['success'] ?? false;
    }

    /**
     * Send JSON response.
     *
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    private function sendJson(array $data, int $statusCode = 200): string
    {
        $this->getResponse()->setStatusCode($statusCode);
        $this->getResponse()->setContentType('application/json');

        // Log the request
        $endpoint = $this->getRequest()->getPathInfo();
        $method = $this->getRequest()->getMethod();
        $responseTimeMs = (int) (microtime(true) * 1000) - $this->startTime;

        $this->apiService->logRequest($endpoint, $method, $this->getRequest()->getParameterHolder()->getAll(), $statusCode, $responseTimeMs);

        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Send error response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     */
    private function sendError(string $message, int $statusCode = 400): string
    {
        return $this->sendJson(['error' => $message], $statusCode);
    }

    // =========================================================================
    // PROFILE
    // =========================================================================

    /**
     * GET /api/research/profile
     */
    public function executeProfile(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        return $this->sendJson($this->apiService->getProfile());
    }

    // =========================================================================
    // PROJECTS
    // =========================================================================

    /**
     * GET/POST /api/research/projects
     */
    public function executeProjects(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?? $request->getParameterHolder()->getAll();
            $result = $this->apiService->createProject($data);

            if (isset($result['error'])) {
                return $this->sendError($result['error'], 400);
            }

            return $this->sendJson($result, 201);
        }

        $params = [
            'status' => $request->getParameter('status'),
            'type' => $request->getParameter('type'),
            'limit' => $request->getParameter('limit', 50),
            'offset' => $request->getParameter('offset', 0),
        ];

        return $this->sendJson($this->apiService->getProjects($params));
    }

    // =========================================================================
    // COLLECTIONS
    // =========================================================================

    /**
     * GET/POST /api/research/collections
     */
    public function executeCollections(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?? $request->getParameterHolder()->getAll();
            $result = $this->apiService->createCollection($data);

            if (isset($result['error'])) {
                return $this->sendError($result['error'], 400);
            }

            return $this->sendJson($result, 201);
        }

        $params = [
            'limit' => $request->getParameter('limit', 50),
            'offset' => $request->getParameter('offset', 0),
        ];

        return $this->sendJson($this->apiService->getCollections($params));
    }

    /**
     * GET /api/research/collections/:id
     */
    public function executeCollection(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        $collectionId = (int) $request->getParameter('id');
        $result = $this->apiService->getCollection($collectionId);

        if (isset($result['error'])) {
            return $this->sendError($result['error'], 404);
        }

        return $this->sendJson($result);
    }

    // =========================================================================
    // SAVED SEARCHES
    // =========================================================================

    /**
     * GET /api/research/searches
     */
    public function executeSearches(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        return $this->sendJson($this->apiService->getSearches());
    }

    // =========================================================================
    // BOOKINGS
    // =========================================================================

    /**
     * GET/POST /api/research/bookings
     */
    public function executeBookings(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?? $request->getParameterHolder()->getAll();
            $result = $this->apiService->createBooking($data);

            if (isset($result['error'])) {
                return $this->sendError($result['error'], 400);
            }

            return $this->sendJson($result, 201);
        }

        $params = [
            'status' => $request->getParameter('status'),
            'date_from' => $request->getParameter('date_from'),
            'date_to' => $request->getParameter('date_to'),
        ];

        return $this->sendJson($this->apiService->getBookings($params));
    }

    // =========================================================================
    // CITATIONS
    // =========================================================================

    /**
     * GET /api/research/citations/:id/:format
     */
    public function executeCitation(sfWebRequest $request)
    {
        // Citations can be public, but log if authenticated
        $this->authenticate();

        $objectId = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'chicago');

        if (!$objectId) {
            return $this->sendError('Object ID is required', 400);
        }

        $result = $this->apiService->getCitation($objectId, $format);

        if (isset($result['error'])) {
            return $this->sendError($result['error'], 404);
        }

        return $this->sendJson($result);
    }

    // =========================================================================
    // BIBLIOGRAPHIES
    // =========================================================================

    /**
     * GET /api/research/bibliographies
     */
    public function executeBibliographies(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        return $this->sendJson($this->apiService->getBibliographies());
    }

    /**
     * GET /api/research/bibliographies/:id/export/:format
     */
    public function executeExportBibliography(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        $bibliographyId = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'ris');

        $result = $this->apiService->exportBibliography($bibliographyId, $format);

        if (isset($result['error'])) {
            return $this->sendError($result['error'], 400);
        }

        // Return as file download
        $this->getResponse()->setContentType($result['mime_type']);
        $this->getResponse()->setHttpHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');

        return $this->renderText($result['content']);
    }

    // =========================================================================
    // ANNOTATIONS
    // =========================================================================

    /**
     * GET /api/research/annotations
     */
    public function executeAnnotations(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        $params = [
            'object_id' => $request->getParameter('object_id'),
        ];

        return $this->sendJson($this->apiService->getAnnotations($params));
    }

    // =========================================================================
    // STATISTICS
    // =========================================================================

    /**
     * GET /api/research/stats
     */
    public function executeStats(sfWebRequest $request)
    {
        if (!$this->authenticate()) {
            return $this->sendError('Unauthorized', 401);
        }

        return $this->sendJson($this->apiService->getStats());
    }
}
