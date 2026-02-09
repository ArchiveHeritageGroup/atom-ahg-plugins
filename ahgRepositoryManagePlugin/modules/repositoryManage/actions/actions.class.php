<?php

class repositoryManageActions extends AhgActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);
    }

    public function executeBrowse(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();

        // Page title
        $title = sfConfig::get('app_ui_label_repository', 'Archival institution');
        $this->response->setTitle("{$title} browse - {$this->response->getTitle()}");

        // Sort defaults
        if (array_key_exists('query', $request->getGetParameters())) {
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

        // Handle global search redirect: ?query=X → subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        // Institutional scoping
        if (sfConfig::get('app_enable_institutional_scoping')) {
            if (isset($request->repos) && ctype_digit($request->repos)) {
                $this->context->user->setAttribute('search-realm', $request->repos);
            } else {
                $this->context->user->removeAttribute('search-realm');
            }
        }

        // Create service
        $service = new \AhgRepositoryManage\Services\RepositoryBrowseService($culture);

        // Collect browse params
        $browseParams = [
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $subquery,
            'languages' => $request->getParameter('languages'),
            'types' => $request->getParameter('types'),
            'regions' => $request->getParameter('regions'),
            'geographicSubregions' => $request->getParameter('geographicSubregions'),
            'locality' => $request->getParameter('locality'),
            'thematicAreas' => $request->getParameter('thematicAreas'),
        ];

        // Determine selected culture (may be overridden by language filter)
        $this->selectedCulture = $culture;
        if (!empty($browseParams['languages'])) {
            $this->selectedCulture = $browseParams['languages'];
        }

        // Execute browse
        $browseResult = $service->browse($browseParams);

        // Build pager
        $this->pager = new \AhgRepositoryManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );

        // Aggregations for sidebar facets
        $this->aggs = $browseResult['aggs'];

        // Build search-like object for template (aggregation partial expects $search->filters)
        $this->search = new \stdClass();
        $this->search->filters = $browseResult['filters'];

        // View mode (card/table) with DisplayModeService
        $this->cardView = 'card';
        $this->tableView = 'table';

        $allowedViews = [$this->cardView, $this->tableView];
        if (isset($request->view) && in_array($request->view, $allowedViews)) {
            $this->view = $request->view;
        } else {
            $this->view = sfConfig::get('app_default_repository_browse_view', 'card');
        }

        // Advanced filter dropdown data
        $advFilterData = $service->getAdvancedFilterTerms();
        $this->thematicAreas = $advFilterData['thematicAreas'];
        $this->repositoryTypes = $advFilterData['repositoryTypes'];
        $this->regions = $advFilterData['regions'];

        // Hidden fields for advanced filters form
        $this->hiddenFields = $this->buildHiddenFields($request);

        // Show advanced filters panel?
        $this->show = !empty($request->thematicAreas) || !empty($request->types) || !empty($request->regions);

        // Service reference for template helpers
        $this->browseService = $service;

        // Batch resolve thematic area term IDs for table view
        $allThematicIds = [];
        foreach ($browseResult['hits'] as $doc) {
            if (!empty($doc['thematicAreas'])) {
                foreach ($doc['thematicAreas'] as $tid) {
                    $allThematicIds[] = (int) $tid;
                }
            }
        }
        $this->thematicAreaNames = [];
        if (!empty($allThematicIds)) {
            $this->thematicAreaNames = $service->resolveTermNames(array_unique($allThematicIds));
        }
    }

    /**
     * Build hidden fields for advanced filters form.
     */
    protected function buildHiddenFields(sfWebRequest $request): array
    {
        $hidden = [];
        $allowed = ['languages', 'types', 'regions', 'geographicSubregions', 'locality', 'thematicAreas', 'view', 'sort', 'subquery'];
        $ignored = ['thematicAreas', 'types', 'regions'];

        foreach ($request->getGetParameters() as $key => $value) {
            if (!in_array($key, $allowed) || in_array($key, $ignored)) {
                continue;
            }
            $hidden[$key] = $value;
        }

        return $hidden;
    }
}
