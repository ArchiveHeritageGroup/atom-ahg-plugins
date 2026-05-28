<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * ISBN / DOI / ISSN Resolver Module.
 *
 * Actions:
 *   lookup      — ISBN-only (legacy, kept for backward compatibility)
 *   resolve     — Auto-detects DOI / ISSN / ISBN and resolves via CrossRef / WorldCat
 *   doi-lookup  — Dedicated DOI lookup page
 *
 * All actions return JSON for AJAX requests; fall back to HTML page when no identifier is provided.
 */
class isbnActions extends AhgController
{
    /**
     * Statistics page — requires authentication.
     */
    public function executeStats($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $repository = new \AtomFramework\Repositories\IsbnLookupRepository();
        $this->stats = $repository->getStatistics(30);
        $this->recentLookups = $repository->getRecentLookups(20);
        $this->providers = $repository->getProviders();
    }

    // ========================================================================
    // resolve — primary action: auto-detects DOI / ISSN / ISBN
    // ========================================================================

    /**
     * Auto-detecting identifier resolver.
     *
     * GET /isbn/resolve?id=10.1234/foo  → DOI
     * GET /isbn/resolve?id=1234-5678     → ISSN
     * GET /isbn/resolve?id=9780134685991  → ISBN
     *
     * Returns JSON always. Falls back to a help page on GET with no params.
     */
    public function executeResolve($request)
    {
        $identifier = trim($request->getParameter('id', $request->getParameter('identifier', '')));

        // No identifier → show the form page
        if (empty($identifier)) {
            return sfView::SUCCESS;
        }

        $this->getResponse()->setContentType('application/json');

        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/DoiService.php';

            $doiSvc = \ahgLibraryPlugin\Service\DoiService::getInstance();
            $result = $doiSvc->resolve($identifier);

            if (!$result['success']) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'type'    => $result['type'] ?? 'unknown',
                    'identifier' => $identifier,
                    'error'   => $result['error'] ?? 'Resolution failed',
                ], JSON_PRETTY_PRINT));
            }

            // Map to preview data for form population
            $mapper = new \AtomFramework\Services\IsbnMetadataMapper();
            $preview = $mapper->getPreviewData($result['data']);

            return $this->renderText(json_encode([
                'success'    => true,
                'type'       => $result['type'],
                'identifier' => $result['identifier'],
                'source'     => $result['source'] ?? $result['type'],
                'preview'    => $preview,
                'data'       => $result['data'],
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    // ========================================================================
    // lookup — ISBN-only (legacy, kept for backward compatibility)
    // ========================================================================

    /**
     * Legacy ISBN lookup (unchanged behaviour for existing integrations).
     *
     * GET /isbn/lookup?isbn=9780134685991
     */
    public function executeLookup($request)
    {
        $isbn = trim($request->getParameter('isbn', ''));

        // No ISBN → show form
        if (empty($isbn)) {
            return sfView::SUCCESS;
        }

        $this->getResponse()->setContentType('application/json');

        try {
            $repository = new \AtomFramework\Repositories\IsbnLookupRepository();
            $service    = new \AtomFramework\Services\WorldCatService($repository);
            $mapper     = new \AtomFramework\Services\IsbnMetadataMapper();

            $result = $service->lookup($isbn);

            if (!$result['success']) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error'   => $result['error'] ?? 'Lookup failed',
                ], JSON_PRETTY_PRINT));
            }

            $preview = $mapper->getPreviewData($result['data']);

            return $this->renderText(json_encode([
                'success' => true,
                'source'  => $result['source'] ?? 'worldcat',
                'cached'  => $result['cached'] ?? false,
                'preview' => $preview,
                'data'    => $result['data'],
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    // ========================================================================
    // doi-lookup — dedicated DOI resolution page
    // ========================================================================

    /**
     * Dedicated DOI lookup page.
     *
     * GET /isbn/doi-lookup?id=10.1234/foo
     */
    public function executeDoiLookup($request)
    {
        $doi = trim($request->getParameter('doi', $request->getParameter('id', '')));

        if (empty($doi)) {
            return sfView::SUCCESS;
        }

        $this->getResponse()->setContentType('application/json');

        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/DoiService.php';

            $svc = \ahgLibraryPlugin\Service\DoiService::getInstance();
            $result = $svc->resolveDoi($doi);

            if (!$result['success']) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error'   => $result['error'] ?? 'DOI resolution failed',
                ], JSON_PRETTY_PRINT));
            }

            $mapper = new \AtomFramework\Services\IsbnMetadataMapper();
            $preview = $mapper->getPreviewData($result['data']);

            return $this->renderText(json_encode([
                'success'    => true,
                'identifier' => $result['identifier'],
                'source'     => 'crossref',
                'preview'    => $preview,
                'data'       => $result['data'],
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    // ========================================================================
    // api-test — CLI / browser test endpoint
    // ========================================================================

    public function executeApiTest($request)
    {
        $this->getResponse()->setContentType('application/json');

        $isbn = trim($request->getParameter('isbn', '9780134685991'));

        try {
            $repository = new \AtomFramework\Repositories\IsbnLookupRepository();
            $service    = new \AtomFramework\Services\WorldCatService($repository);
            $mapper     = new \AtomFramework\Services\IsbnMetadataMapper();

            $result = $service->lookup($isbn);

            if (!$result['success']) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error'   => $result['error'] ?? 'Lookup failed',
                ], JSON_PRETTY_PRINT));
            }

            $preview = $mapper->getPreviewData($result['data']);

            return $this->renderText(json_encode([
                'success' => true,
                'source'  => $result['source'] ?? 'worldcat',
                'cached'  => $result['cached'] ?? false,
                'preview' => $preview,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Test page — requires authentication.
     */
    public function executeTest($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }
}
