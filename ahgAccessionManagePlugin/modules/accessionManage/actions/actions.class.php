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
            'repository' => (int) $request->getParameter('repository'),
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

        // CAAIS 1.1 repository (#203): the filter's options, and whether the
        // CAAIS export of a selection is offered (admin only, as in Heratio,
        // because the export carries donor contact details).
        require_once dirname(__DIR__, 3).'/lib/Services/CaaisProfileService.php';
        $caais = new \AhgAccessionManage\Services\CaaisProfileService($culture);
        $this->caaisInstalled = $caais->installed();
        $this->repositoryOptions = $this->caaisInstalled ? $caais->repositoryOptions() : [];
        $this->selectedRepository = (int) $request->getParameter('repository');
        // The route check covers the window after a deploy in which this file
        // is live but the plugin configuration that registers the route is not.
        $this->canExportCaais = $this->caaisInstalled && $this->getUser()->isAdministrator()
            && $this->context->getRouting()->hasRouteName('accession_caais_export');
    }

    /**
     * CAAIS 1.0 export of one accession (id=N) or a selection (ids[]=N...), as
     * Heratio's JSON (format=json, the default), CSV or XML. external=1
     * withholds sources marked confidential (CAAIS 2.1.6), for sharing.
     *
     * Administrators only, like Heratio's export: the full record carries
     * donor contact details.
     */
    public function executeCaaisExport($request)
    {
        if (!$this->getUser()->isAdministrator()) {
            $this->forward('admin', 'secure');
        }

        require_once dirname(__DIR__, 3).'/lib/Services/CaaisProfileService.php';
        $service = new \AhgAccessionManage\Services\CaaisProfileService($this->culture());

        $ids = $request->getParameter('ids', $request->getParameter('id'));
        $ids = array_values(array_unique(array_filter(
            array_map('intval', (array) $ids),
            fn ($id) => $id > 0
        )));
        // ponytail: a selection is held in memory; 1000 accessions is far past
        // any page of the browse list. Exporting a whole register would need
        // Heratio's streamed caaisExportAll.
        $ids = array_slice($ids, 0, 1000);

        if (!$service->installed() || [] === $ids) {
            $this->forward404();
        }

        $external = (bool) $request->getParameter('external');
        $records = [];
        foreach ($ids as $id) {
            if (null !== $record = $service->exportRecord($id, $external)) {
                $records[] = $record;
            }
        }
        if ([] === $records) {
            $this->forward404();
        }

        $format = $request->getParameter('format', 'json');
        if (!in_array($format, ['json', 'csv', 'xml'], true)) {
            $format = 'json';
        }

        if (1 === count($ids)) {
            $identifier = (string) $records[0]['identity']['identifiers'][0]['value'];
            $name = 'caais-'.preg_replace('/[^A-Za-z0-9._-]+/', '_', '' !== $identifier ? $identifier : (string) $ids[0]);
        } else {
            $name = 'caais-accessions-'.date('Y-m-d');
        }

        if ('csv' === $format) {
            $type = 'text/csv; charset=utf-8';
            $body = \AhgAccessionManage\Services\CaaisProfileService::toCsv($records);
        } elseif ('xml' === $format) {
            $type = 'application/xml; charset=utf-8';
            $body = $service->toXml($service->envelope($records));
        } else {
            $type = 'application/json; charset=utf-8';
            $body = json_encode($service->envelope($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $response = $this->getResponse();
        $response->setContentType($type);
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="'.$name.'.'.$format.'"');
        $response->setHttpHeader('X-Content-Type-Options', 'nosniff');

        return $this->renderText($body);
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
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            return false;
        }
    }
}
