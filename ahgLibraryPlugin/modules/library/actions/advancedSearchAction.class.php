<?php

declare(strict_types=1);

/**
 * libraryAdvancedSearchAction — Lucene-style advanced search for the library catalogue.
 *
 * GET  /library/advanced-search   → show search builder UI
 * GET  /library/advanced-search?query=... → execute query and show results
 *
 * Query syntax supported:
 *   field:value          → exact match on field
 *   "phrase"             → phrase match
 *   term1 AND term2      → both terms required
 *   term1 OR term2       → either term
 *   term1 NOT term2      → term1 without term2
 *   (term1 OR term2) AND field:value
 *   field:[start TO end] → range query (dates, call numbers)
 *
 * Available fields:
 *   title, author, creator, subject, keyword,
 *   isbn, issn, doi, lccn, oclc_number,
 *   publisher, year, material_type, language, call_number
 *
 * @package ahgLibraryPlugin
 */
class libraryAdvancedSearchAction extends AhgController
{
    /** @var string|null */
    public $query;

    /** @var array */
    public $results = [];

    /** @var int */
    public $total = 0;

    /** @var int */
    public $page = 1;

    /** @var int */
    public $perPage = 20;

    /** @var string|null */
    public $error;

    /** @var array */
    public $filters = [];

    public function execute($request)
    {
        $this->query = trim($request->getParameter('query', ''));
        $this->page  = max(1, (int) $request->getParameter('page', 1));

        // Build active filters from query string
        $this->filters = [
            'query'         => $this->query,
            'material_type' => trim($request->getParameter('material_type', '')),
            'language'      => trim($request->getParameter('language', '')),
            'date_from'     => trim($request->getParameter('date_from', '')),
            'date_to'       => trim($request->getParameter('date_to', '')),
            'publisher'     => trim($request->getParameter('publisher', '')),
            'sort'          => trim($request->getParameter('sort', 'relevance')),
        ];
        $this->filters = array_filter($this->filters);

        if ($this->query || !empty($this->filters['material_type']) || !empty($this->filters['language'])) {
            $this->executeSearch();
        }

        return sfView::SUCCESS;
    }

    protected function executeSearch(): void
    {
        try {
            require_once sfConfig::get('sf_root_dir')
                . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/SearchService.php';

            $svc = \ahgLibraryPlugin\Service\SearchService::getInstance();

            $params = $this->filters;
            $params['page'] = $this->page;
            $params['limit'] = $this->perPage;

            $searchResult = $svc->advancedSearch($params);

            $this->results = $searchResult['results'] ?? [];
            $this->total   = $searchResult['total'] ?? 0;

        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->results = [];
            $this->total = 0;
        }
    }

    public function getTotalPages(): int
    {
        return $this->total > 0 ? (int) ceil($this->total / $this->perPage) : 1;
    }
}
