<?php

class storageManageActions extends AhgActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text', 'Date']);
    }

    public function executeBrowse(sfWebRequest $request)
    {
        $culture = $this->context->user->getCulture();

        $label = sfConfig::get('app_ui_label_physicalobject', 'Physical storage');
        $this->response->setTitle(__('Browse %1%', ['%1%' => $label]) . ' - ' . $this->response->getTitle());

        // Institutional scoping
        if (sfConfig::get('app_enable_institutional_scoping')) {
            $this->context->user->removeAttribute('search-realm');
        }

        $sort = $request->getParameter('sort', 'nameUp');
        $limit = (int) ($request->limit ?: sfConfig::get('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Handle global search redirect: ?query=X -> subquery=X
        $subquery = $request->getParameter('subquery', '');
        if (empty($subquery) && !empty($request->getParameter('query'))) {
            $subquery = $request->getParameter('query');
        }

        $service = new \AhgStorageManage\Services\StorageBrowseService($culture);

        $browseResult = $service->browse([
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'subquery' => $subquery,
        ]);

        $this->pager = new \AhgStorageManage\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );
    }

    public function executeAutocomplete(sfWebRequest $request)
    {
        if (!isset($request->limit)) {
            $request->limit = sfConfig::get('app_hits_per_page');
        }

        $criteria = new Criteria();
        $criteria->addJoin(QubitPhysicalObject::ID, QubitPhysicalObjectI18n::ID);
        $criteria->add(QubitPhysicalObjectI18n::CULTURE, $this->context->user->getCulture());

        if (sfConfig::get('app_markdown_enabled', true)) {
            $criteria->add(QubitPhysicalObjectI18n::NAME, "%{$request->query}%", Criteria::LIKE);
        } else {
            $criteria->add(QubitPhysicalObjectI18n::NAME, "{$request->query}%", Criteria::LIKE);
        }

        $criteria->addAscendingOrderByColumn(QubitPhysicalObjectI18n::NAME);

        $this->pager = new QubitPager('QubitPhysicalObject');
        $this->pager->setCriteria($criteria);
        $this->pager->setMaxPerPage($request->limit);
        $this->pager->setPage(1);

        $this->physicalObjects = $this->pager->getResults();
    }

    public function executeBoxList(sfWebRequest $request)
    {
        if (!isset($request->limit)) {
            $request->limit = sfConfig::get('app_hits_per_page');
        }

        $this->resource = $this->getRoute()->resource;

        $criteria = new Criteria();
        $criteria->add(QubitRelation::SUBJECT_ID, $this->resource->id);
        $criteria->add(QubitRelation::TYPE_ID, QubitTerm::HAS_PHYSICAL_OBJECT_ID);
        $criteria->addJoin(QubitRelation::OBJECT_ID, QubitInformationObject::ID);

        $this->informationObjects = QubitInformationObject::get($criteria);

        $c2 = clone $criteria;
        $this->foundcount = BasePeer::doCount($c2)->fetchColumn(0);
    }

    public function executeHoldingsReportExport(sfWebRequest $request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                if (empty($request->includeEmpty) && empty($request->includeDescriptions) && empty($request->includeAccessions)) {
                    $message = $this->context->i18n->__('Please check one or more of the export options.');
                    $this->context->user->setFlash('error', $message);

                    $this->redirect(['module' => 'physicalobject', 'action' => 'holdingsReportExport']);
                } else {
                    $this->doBackgroundExport($request);

                    $this->redirect(['module' => 'physicalobject', 'action' => 'browse']);
                }
            }
        }
    }

    protected function doBackgroundExport($request)
    {
        $options = ['suppressEmpty' => empty($request->includeEmpty)];

        if (!empty($request->includeAccessions) && empty($request->includeDescriptions)) {
            $options['holdingType'] = 'QubitAccession';
        }

        if (empty($request->includeAccessions) && !empty($request->includeDescriptions)) {
            $options['holdingType'] = 'QubitInformationObject';
        }

        if (empty($request->includeAccessions) && empty($request->includeDescriptions)) {
            $options['holdingType'] = 'none';
        }

        $job = QubitJob::runJob('arPhysicalObjectCsvHoldingsReportJob', $options);

        $jobAdminUrl = $this->context->routing->generate(null, ['module' => 'jobs', 'action' => 'browse']);
        $messageParams = [
            '%1%' => '<strong>',
            '%2%' => '</strong>',
            '%3%' => sprintf('<a class="alert-link" href="%s">', $jobAdminUrl),
            '%4%' => '</a>',
        ];

        $message = $this->context->i18n->__(
            '%1%Export initiated.%2% Check %3%job management%4% page to download the results when it has completed.',
            $messageParams
        );

        $this->context->user->setFlash('notice', $message);
    }
}
