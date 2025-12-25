<?php

/**
 * ISBN Module Actions.
 */
class isbnActions extends sfActions
{
    /**
     * Test page action.
     */
    public function executeTest(sfWebRequest $request)
    {
        // Require authentication
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }
    }

    /**
     * API test endpoint (for CLI testing with cookie).
     */
    public function executeApiTest(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');

        $isbn = trim($request->getParameter('isbn', '9780134685991'));

        try {
            $repository = new \AtomFramework\Repositories\IsbnLookupRepository();
            $service = new \AtomFramework\Services\WorldCatService($repository);
            $mapper = new \AtomFramework\Services\IsbnMetadataMapper();

            $result = $service->lookup($isbn);

            if (!$result['success']) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Lookup failed',
                ], JSON_PRETTY_PRINT));
            }

            $preview = $mapper->getPreviewData($result['data']);

            return $this->renderText(json_encode([
                'success' => true,
                'source' => $result['source'],
                'cached' => $result['cached'] ?? false,
                'preview' => $preview,
            ], JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            return $this->renderText(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Statistics action.
     */
    public function executeStats(sfWebRequest $request)
    {
        if (!$this->context->user->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $repository = new \AtomFramework\Repositories\IsbnLookupRepository();
        $this->stats = $repository->getStatistics(30);
        $this->recentLookups = $repository->getRecentLookups(20);
        $this->providers = $repository->getProviders();
    }
}
