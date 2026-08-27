<?php

use AtomFramework\Http\Controllers\AhgController;
class accessionManageActions extends AhgController
{
    public function executeBrowse($request)
    {
        // Access control: accessions require authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->getUser()->setFlash('notice', $this->context->i18n->__('You must be logged in to view accessions.'));
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $culture = $this->culture();

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
            $sortSetting = $this->config('app_sort_browser_user', 'lastUpdated');
        } else {
            $sortSetting = $this->config('app_sort_browser_anonymous', 'lastUpdated');
        }

        $sort = $request->getParameter('sort', $sortSetting);
        $sortDir = 'asc';
        if (in_array($sort, ['lastUpdated', 'relevance'])) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        $limit = (int) ($request->limit ?: $this->config('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Max result window guard
        $maxResultWindow = (int) $this->config('app_opensearch_max_result_window', 10000);
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

    public function executeDashboard($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->getUser()->setFlash('notice', $this->context->i18n->__('You must be logged in to view the accession dashboard.'));
            $this->redirect(['module' => 'user', 'action' => 'login']);
        }

        $this->stats = \AhgAccessionManage\Services\AccessionCrudService::getDashboardStats();

        try {
            $intakeService = new \AhgAccessionManage\Services\AccessionIntakeService();
            $this->queueStats = $intakeService->getQueueStats();
        } catch (\Exception $e) {
            $this->queueStats = [];
        }

        try {
            $appraisalService = new \AhgAccessionManage\Services\AccessionAppraisalService();
            $this->valuationReport = $appraisalService->getValuationReport();
        } catch (\Exception $e) {
            $this->valuationReport = [];
        }

        // Use Success.php template (Symfony default)
    }

    /**
     * Identifier-availability check for the accession edit form.
     *
     * Lives here rather than in modules/accession because base AtoM's
     * qtAccessionPlugin is enabled from the hardcoded $corePlugins list in
     * ProjectConfiguration, which array_merge()s core BEFORE anything read from
     * atom_plugin. Its modules/accession therefore always wins resolution and
     * plugin load_order cannot change that, so our override of that module is
     * never reached. accessionManage is a module base does not ship, so this is.
     *
     * What base does here is call QubitAcl::check($this->resource, ...) - but
     * $this->resource is never populated for this action, because it is reached
     * directly rather than through a route carrying a resource. The null lands in
     * QubitAcl::checkAccessByClass(), which calls get_class() on it and dies:
     * "Argument #1 ($object) must be of type object, null given". Every
     * availability check was a 500.
     */
    public function executeCheckIdentifierAvailable($request)
    {
        // Resolve a real subject: the accession being edited, or a new one while
        // the record is still an unsaved add.
        $subject = null;

        if (!empty($request->accession_id)) {
            $subject = \QubitAccession::getById($request->accession_id);
        }

        if (null === $subject) {
            $subject = \AtomFramework\Services\Write\WriteServiceFactory::accession()->newAccession();
        }

        $this->resource = $subject;

        if (!\AtomExtensions\Services\AclService::check($subject, 'create')
            && !\AtomExtensions\Services\AclService::check($subject, 'update')) {
            $this->getResponse()->setStatusCode(401);

            return \sfView::NONE;
        }

        $this->getResponse()->setContentType('application/json');

        $valid = $this->identifierIsAvailable($request->identifier, $subject);
        $this->getResponse()->setContent(json_encode([
            'allowable' => $valid,
            'message' => $valid
                ? $this->context->i18n->__('Identifier available.')
                : $this->context->i18n->__('Identifier unavailable.'),
        ]));

        return \sfView::NONE;
    }

    private function identifierIsAvailable($identifier, $resource): bool
    {
        $validator = new \QubitValidatorAccessionIdentifier(['required' => true, 'resource' => $resource]);

        try {
            $validator->clean($identifier);

            return true;
        } catch (\sfValidatorError $e) {
            return false;
        }
    }
}
