<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * ISBN Lookup Action for Information Objects.
 *
 * Handles AJAX requests for ISBN metadata lookup from WorldCat,
 * Open Library, and Google Books APIs.
 */
class InformationobjectIsbnLookupAction extends AhgController
{
    public function execute($request)
    {
        // Disable layout for AJAX
        $this->getResponse()->setContentType('application/json');

        // Check authentication
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson([
                'success' => false,
                'error' => 'Authentication required',
            ], 401);
        }

        // Get ISBN from request
        $isbn = trim($request->getParameter('isbn', ''));

        if (empty($isbn)) {
            return $this->renderJson([
                'success' => false,
                'error' => 'ISBN is required',
            ]);
        }

        // Get optional object ID for audit
        $objectId = $request->getParameter('object_id');

        try {
            // Initialize services
            $repository = new \ahgLibraryPlugin\Repository\IsbnLookupRepository();
            $service = new \ahgLibraryPlugin\Service\WorldCatService($repository);
            $mapper = new \ahgLibraryPlugin\Service\IsbnMetadataMapper();

            // Perform lookup
            $result = $service->lookup(
                $isbn,
                $this->getUser()->getAttribute('user_id'),
                $objectId ? (int) $objectId : null
            );

            if (!$result['success']) {
                return $this->renderJson([
                    'success' => false,
                    'error' => $result['error'] ?? 'Lookup failed',
                ]);
            }

            // Get preview data and field mapping
            $preview = $mapper->getPreviewData($result['data']);
            $mapped = $mapper->mapToAtom($result['data']);

            return $this->renderJson([
                'success' => true,
                'source' => $result['source'],
                'cached' => $result['cached'] ?? false,
                'preview' => $preview,
                'mapping' => $mapped,
                'raw' => $result['data'],
            ]);

        } catch (\Exception $e) {
            // Log error
            error_log('[ISBN DEBUG] ' . date('Y-m-d H:i:s') . ' ISBN=' . $isbn . ' Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
            $this->getContext()->getLogger()->err(
                'ISBN lookup failed: '.$e->getMessage()
            );

            return $this->renderJson([
                'success' => false,
                'error' => 'An error occurred during lookup',
            ], 500);
        }
    }

    /**
     * Render JSON response.
     */
    private function renderJson(array $data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);

        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT));
    }
}
