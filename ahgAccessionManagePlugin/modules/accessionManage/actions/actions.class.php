<?php

class accessionManageActions extends AhgActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);
    }

    public function executeBrowse(sfWebRequest $request)
    {
        // Access control: accessions require authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->getUser()->setFlash('notice', $this->context->i18n->__('You must be logged in to view accessions.'));
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $culture = $this->context->user->getCulture();

        // Page title
        $this->response->setTitle(__('Browse accessions') . ' - ' . $this->response->getTitle());

        // Sort options
        $this->sortOptions = [
            'lastUpdated' => $this->context->i18n->__('Date modified'),
            'accessionNumber' => $this->context->i18n->__('Accession number'),
            'title' => $this->context->i18n->__('Title'),
            'acquisitionDate' => $this->context->i18n->__('Acquisition date'),
        ];

        // Sort defaults
        if (array_key_exists('query', $request->getGetParameters())
            || !empty($request->getParameter('subquery'))) {
            $sortSetting = 'relevance';
        } elseif ($this->getUser()->isAuthenticated()) {
            $sortSetting = sfConfig::get('app_sort_browser_user', 'lastUpdated');
        } else {
            $sortSetting = sfConfig::get('app_sort_browser_anonymous', 'lastUpdated');
        }

        $sort = $request->getParameter('sort', $sortSetting);
        $sortDir = 'asc';
        if (in_array($sort, ['lastUpdated', 'relevance'])) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        $limit = (int) ($request->limit ?: sfConfig::get('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Max result window guard
        $maxResultWindow = (int) sfConfig::get('app_opensearch_max_result_window', 10000);
        if ($limit * $page > $maxResultWindow) {
            $message = $this->context->i18n->__(
                "We've redirected you to the first page of results. To avoid using vast amounts of memory, AtoM limits pagination to %1% records. To view the last records in the current result set, try changing the sort direction.",
                ['%1%' => $maxResultWindow]
            );
            $this->getUser()->setFlash('notice', $message);

            $params = $request->getParameterHolder()->getAll();
            unset($params['page']);
            $this->redirect($params);
        }

        // Handle global search redirect: ?query=X -> subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        // Add relevance sort when searching
        if (!empty($subquery)) {
            $this->sortOptions['relevance'] = $this->context->i18n->__('Relevance');
        }

        // Create service
        $service = new \AhgAccessionManage\Services\AccessionBrowseService($culture);

        // Execute browse
        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
        ]);

        // Build pager
        $this->pager = new \AhgAccessionManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );

        // Service reference for template i18n helpers
        $this->browseService = $service;

        // Selected culture for template
        $this->selectedCulture = $culture;
    }
}
