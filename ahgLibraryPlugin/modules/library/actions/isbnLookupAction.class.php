<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * libraryIsbnLookupAction — Unified DOI / ISBN / ISSN / ISBNx resolver.
 *
 * Auto-detects identifier type via DoiService::detectType() then resolves:
 *   - DOI     → CrossRef API (https://api.crossref.org)
 *   - ISSN    → CrossRef journals endpoint
 *   - ISBNx   → WorldCatService (primary) → DoiService/CrossRef (fallback)
 *
 * URL: GET /library/isbnLookup?identifier=...
 *       (old ?isbn=... parameter still accepted for backward compat)
 *
 * @package ahgLibraryPlugin
 */
class libraryIsbnLookupAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Support both old ?isbn=... and new ?identifier=... param
        $identifier = trim($request->getParameter('identifier', ''));
        if (empty($identifier)) {
            $identifier = trim($request->getParameter('isbn', ''));
        }

        if (empty($identifier)) {
            return $this->renderJson([
                'success' => false,
                'error' => __('A DOI, ISBN, or ISSN is required.'),
            ]);
        }

        try {
            $rootDir = $this->config('sf_root_dir');

            // Load DoiService (handles DOI, ISSN, and ISBN fallback)
            require_once $rootDir . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/DoiService.php';
            $doiSvc = \ahgLibraryPlugin\Service\DoiService::getInstance();

            $idType = $doiSvc->detectType($identifier);

            // DOI and ISSN: use DoiService directly
            if (in_array($idType, ['doi', 'issn'], true)) {
                return $this->resolveDoiOrIssn($doiSvc, $identifier, $idType);
            }

            // ISBN: try WorldCatService first (has richer book metadata + covers),
            // fall back to DoiService/CrossRef if WorldCat misses
            return $this->resolveIsbn($identifier, $doiSvc, $rootDir);

        } catch (\Exception $e) {
            $this->getContext()->getLogger()->err(
                'Identifier lookup failed: ' . $e->getMessage()
            );
            return $this->renderJson([
                'success' => false,
                'error' => __('An error occurred during lookup.'),
            ]);
        }
    }

    // ========================================================================
    // DOI / ISSN resolution
    // ========================================================================

    protected function resolveDoiOrIssn(
        \ahgLibraryPlugin\Service\DoiService $svc,
        string $identifier,
        string $type
    ): string {
        $result = ($type === 'doi')
            ? $svc->resolveDoi($identifier)
            : $svc->resolveIssn($identifier);

        if (!$result['success']) {
            return $this->renderJson([
                'success' => false,
                'error' => $result['error'] ?? __("$type not found"),
            ]);
        }

        $data = $result['data'];

        // Build unified response
        $response = [
            'success' => true,
            'identifier_type' => $type,
            'source' => 'crossref',
            'data' => [
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'authors' => $this->normalizeAuthors($data['authors'] ?? []),
                'publishers' => $data['publisher'] ? [['name' => $data['publisher']]] : [],
                'publish_date' => $data['year'] ?? substr($data['publication_date'] ?? '', 0, 4),
                'publication_date' => $data['publication_date'] ?? null,
                'publish_places' => [],
                'number_of_pages' => $data['number_of_pages'] ?? null,
                'pagination' => $data['number_of_pages'] ?? null,
                'subjects' => $this->normalizeSubjects($data['subjects'] ?? []),
                'description' => $data['description'] ?? null,
                'edition' => $data['edition_statement'] ?? null,
                'series' => $data['series_title'] ?? null,
                'language' => $data['language'] ?? null,
                'isbn_10' => $data['isbn_10'] ?? null,
                'isbn_13' => $data['isbn_13'] ?? null,
                'issn' => $data['issn'] ?? null,
                'eissn' => $data['eissn'] ?? null,
                'doi' => $data['doi'] ?? null,
                'lccn' => null,
                'oclc_number' => null,
                'url' => $data['url'] ?? null,
                // Serial-specific fields
                'volume' => $data['volume'] ?? null,
                'issue' => $data['issue'] ?? null,
                'container_title' => $data['container_title'] ?? null,
            ],
            'covers' => [],
        ];

        // For books resolved via DOI, try Open Library covers
        if ($type === 'doi' && !empty($data['isbn_13'])) {
            $covers = $this->getOpenLibraryCovers($data['isbn_13']);
            if (!empty($covers)) {
                $response['covers'] = $covers;
            }
        }

        return $this->renderJson($response);
    }

    // ========================================================================
    // ISBN resolution (WorldCat primary, CrossRef fallback)
    // ========================================================================

    protected function resolveIsbn(
        string $isbn,
        \ahgLibraryPlugin\Service\DoiService $doiSvc,
        string $rootDir
    ): string {
        $cleanIsbn = preg_replace('/[\s\-]/', '', $isbn);
        $userId = null;
        if ($this->getUser()->isAuthenticated()) {
            $userId = $this->getUser()->getAttribute('user_id');
        }

        // 1. Try WorldCatService (richer book data + Open Library covers)
        try {
            require_once $rootDir . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Repository/IsbnLookupRepository.php';
            $frameworkPath = $rootDir . '/atom-framework';
            require_once $frameworkPath . '/src/Services/LanguageService.php';
            require_once $rootDir . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/WorldCatService.php';
            require_once $rootDir . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/IsbnMetadataMapper.php';
            require_once $rootDir . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/BookCoverService.php';

            $repository = new \ahgLibraryPlugin\Repository\IsbnLookupRepository();
            $service = new \ahgLibraryPlugin\Service\WorldCatService($repository);
            $mapper = new \ahgLibraryPlugin\Service\IsbnMetadataMapper();

            $result = $service->lookup($isbn, $userId);

            if ($result['success']) {
                $preview = $mapper->getPreviewData($result['data']);
                $covers = \ahgLibraryPlugin\Service\BookCoverService::getAllSizes($cleanIsbn);

                return $this->renderJson([
                    'success' => true,
                    'identifier_type' => 'isbn',
                    'source' => $result['source'],
                    'cached' => $result['cached'] ?? false,
                    'data' => [
                        'title' => $result['data']['title'] ?? null,
                        'subtitle' => $result['data']['subtitle'] ?? null,
                        'authors' => array_map(
                            fn($a) => is_array($a) ? $a : ['name' => $a, 'url' => ''],
                            $result['data']['authors'] ?? []
                        ),
                        'publishers' => $result['data']['publishers'] ?? [],
                        'publish_date' => $result['data']['publish_date'] ?? null,
                        'publish_places' => $result['data']['publish_places'] ?? [],
                        'number_of_pages' => $result['data']['number_of_pages'] ?? null,
                        'pagination' => $result['data']['pagination'] ?? null,
                        'subjects' => array_map(
                            fn($s) => is_array($s) ? $s : ['name' => $s, 'url' => ''],
                            $result['data']['subjects'] ?? []
                        ),
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
            }
        } catch (\Exception $e) {
            // WorldCat failed — fall through to CrossRef fallback
            $this->getContext()->getLogger()->warn(
                'WorldCat lookup failed for ISBN ' . $cleanIsbn . ', trying CrossRef: ' . $e->getMessage()
            );
        }

        // 2. Fallback: CrossRef via DoiService
        $fallback = $doiSvc->resolveIsbn($isbn);
        if ($fallback['success']) {
            $data = $fallback['data'];
            return $this->renderJson([
                'success' => true,
                'identifier_type' => 'isbn',
                'source' => 'crossref',
                'cached' => false,
                'data' => [
                    'title' => $data['title'] ?? null,
                    'subtitle' => $data['subtitle'] ?? null,
                    'authors' => $this->normalizeAuthors($data['authors'] ?? []),
                    'publishers' => $data['publisher'] ? [['name' => $data['publisher']]] : [],
                    'publish_date' => $data['year'] ?? null,
                    'publication_date' => $data['publication_date'] ?? null,
                    'publish_places' => [],
                    'number_of_pages' => $data['number_of_pages'] ?? null,
                    'pagination' => $data['number_of_pages'] ?? null,
                    'subjects' => $this->normalizeSubjects($data['subjects'] ?? []),
                    'description' => $data['description'] ?? null,
                    'edition' => null,
                    'series' => null,
                    'language' => $data['language'] ?? null,
                    'isbn_10' => $data['isbn_10'] ?? null,
                    'isbn_13' => $data['isbn_13'] ?? null,
                    'lccn' => null,
                    'oclc_number' => null,
                ],
                'covers' => $this->getOpenLibraryCovers($data['isbn_13'] ?? $data['isbn_10'] ?? ''),
                'preview' => [],
            ]);
        }

        return $this->renderJson([
            'success' => false,
            'error' => __('ISBN not found in WorldCat or CrossRef.'),
        ]);
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Normalize author arrays to [{name, url}] shape.
     */
    protected function normalizeAuthors(array $authors): array
    {
        return array_map(function ($a) {
            if (is_array($a)) {
                return ['name' => $a['name'] ?? '', 'url' => $a['url'] ?? ''];
            }
            return ['name' => (string) $a, 'url' => ''];
        }, $authors);
    }

    /**
     * Normalize subject arrays to [{name, url}] shape.
     */
    protected function normalizeSubjects(array $subjects): array
    {
        return array_map(function ($s) {
            if (is_array($s)) {
                return ['name' => $s['name'] ?? (string) $s, 'url' => $s['url'] ?? ''];
            }
            return ['name' => (string) $s, 'url' => ''];
        }, $subjects);
    }

    /**
     * Fetch Open Library cover images for a given ISBN.
     */
    protected function getOpenLibraryCovers(string $isbn): array
    {
        try {
            require_once $this->config('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/BookCoverService.php';
            return \ahgLibraryPlugin\Service\BookCoverService::getAllSizes($isbn);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Render a JSON response and return the rendered output string.
     */
    protected function renderJson(array $data, int $status = 200): string
    {
        $this->getResponse()->setStatusCode($status);
        return $this->renderText(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
