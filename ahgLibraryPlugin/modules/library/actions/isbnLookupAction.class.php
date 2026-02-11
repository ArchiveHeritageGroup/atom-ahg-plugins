<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Enhanced ISBN Lookup Action for Library Module.
 *
 * Uses WorldCatService with Open Library covers and fallback chain.
 */
class libraryIsbnLookupAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Get ISBN from request
        $isbn = trim($request->getParameter('isbn', ''));

        if (empty($isbn)) {
            return $this->renderJson([
                'success' => false,
                'error' => 'ISBN is required',
            ]);
        }

        try {
            // Load framework services
            $frameworkPath = $this->config('sf_root_dir') . '/atom-framework';
            
            require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Repository/IsbnLookupRepository.php';
            require_once $frameworkPath . '/src/Services/LanguageService.php';
            require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/WorldCatService.php';
            require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/BookCoverService.php';
            require_once $this->config('sf_root_dir') . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/IsbnMetadataMapper.php';

            $repository = new \ahgLibraryPlugin\Repository\IsbnLookupRepository();
            $service = new \ahgLibraryPlugin\Service\WorldCatService($repository);
            $mapper = new \ahgLibraryPlugin\Service\IsbnMetadataMapper();

            // Get user ID for audit
            $userId = null;
            if ($this->getUser()->isAuthenticated()) {
                $userId = $this->getUser()->getAttribute('user_id');
            }

            // Perform lookup
            $result = $service->lookup($isbn, $userId);

            if (!$result['success']) {
                return $this->renderJson([
                    'success' => false,
                    'error' => $result['error'] ?? 'ISBN not found',
                ]);
            }

            // Get preview data
            $preview = $mapper->getPreviewData($result['data']);
            
            // Get cover URLs using Open Library direct URLs
            $cleanIsbn = preg_replace('/[\s-]/', '', $isbn);
            $covers = \ahgLibraryPlugin\Service\BookCoverService::getAllSizes($cleanIsbn);

            return $this->renderJson([
                'success' => true,
                'source' => $result['source'],
                'cached' => $result['cached'] ?? false,
                'data' => [
                    'title' => $result['data']['title'] ?? null,
                    'subtitle' => $result['data']['subtitle'] ?? null,
                    'authors' => array_map(function($a) { return is_array($a) ? $a : ['name' => $a, 'url' => '']; }, $result['data']['authors'] ?? []),
                    'publishers' => $result['data']['publishers'] ?? [],
                    'publish_date' => $result['data']['publish_date'] ?? null,
                    'publish_places' => $result['data']['publish_places'] ?? [],
                    'number_of_pages' => $result['data']['number_of_pages'] ?? null,
                    'subjects' => array_map(function($s) { return is_array($s) ? $s : ['name' => $s, 'url' => '']; }, $result['data']['subjects'] ?? []),
                    'description' => $result['data']['description'] ?? null,
                    'edition' => $result['data']['edition'] ?? null,
                    'series' => $result['data']['series'] ?? null,
                    'language' => $preview['language'] ?? null,
                    'isbn_10' => $result['data']['isbn_10'] ?? null,
                    'isbn_13' => $result['data']['isbn_13'] ?? null,
                    'lccn' => $result['data']['lccn'] ?? null,
                    'oclc_number' => $result['data']['oclc_number'] ?? null,
                ],
                'covers' => $covers,
                'preview' => $preview,
            ]);

        } catch (\Exception $e) {
            $this->getContext()->getLogger()->err(
                'ISBN lookup failed: ' . $e->getMessage()
            );

            return $this->renderJson([
                'success' => false,
                'error' => 'An error occurred during lookup',
            ]);
        }
    }

    /**
     * Render JSON response.
     */
    private function renderJson(array $data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);
        return $this->renderText(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
