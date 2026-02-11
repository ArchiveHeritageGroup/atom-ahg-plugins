<?php

use AtomFramework\Http\Controllers\AhgController;
/*
 * Physical Object module actions — ahgStorageManagePlugin
 *
 * Overrides base and theme physicalobject module to consolidate
 * all CRUD + auxiliary actions under plugin ownership.
 */

class physicalobjectActions extends AhgController
{
    // ---------------------------------------------------------------
    // INDEX (view detail)
    // ---------------------------------------------------------------
    public function executeIndex($request)
    {
        $this->resource = $this->getRoute()->resource;

        if (!isset($this->resource)) {
            $this->forward404();
        }

        // Load framework for extended data
        $frameworkBootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        $repoPath = $this->config('sf_root_dir') . '/atom-framework/src/Repositories/PhysicalObjectExtendedRepository.php';

        if (file_exists($frameworkBootstrap) && file_exists($repoPath)) {
            require_once $frameworkBootstrap;
            require_once $repoPath;

            $this->extendedData = [];
            if ($this->resource->id) {
                $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
                $this->extendedData = $repo->getExtendedData($this->resource->id) ?? [];
            }
        }

        if (1 > strlen($title = $this->resource->__toString())) {
            $title = $this->context->i18n->__('Untitled');
        }

        $this->response->setTitle("{$title} - {$this->response->getTitle()}");
    }

    // ---------------------------------------------------------------
    // EDIT (create / update)
    // ---------------------------------------------------------------
    public function executeEdit($request)
    {
        // Load framework
        $frameworkBootstrap = $this->config('sf_root_dir') . '/atom-framework/bootstrap.php';
        $repoPath = $this->config('sf_root_dir') . '/atom-framework/src/Repositories/PhysicalObjectExtendedRepository.php';

        if (file_exists($frameworkBootstrap) && file_exists($repoPath)) {
            require_once $frameworkBootstrap;
            require_once $repoPath;
        }

        // Set up resource
        $this->resource = new QubitPhysicalObject();
        if (isset($this->getRoute()->resource)) {
            $this->resource = $this->getRoute()->resource;
        }

        // Set page title
        $title = $this->context->i18n->__('Add new physical storage');
        if (isset($this->getRoute()->resource)) {
            if (1 > strlen($title = $this->resource->getName(['cultureFallback' => true]))) {
                $title = $this->context->i18n->__('Untitled');
            }
            $title = $this->context->i18n->__('Edit %1%', ['%1%' => $title]);
        }
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Load extended data
        $this->extendedData = [];
        if ($this->resource->id && class_exists('\AtomFramework\Repositories\PhysicalObjectExtendedRepository')) {
            $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
            $this->extendedData = $repo->getExtendedData($this->resource->id) ?? [];
        }

        // Load type choices for dropdown
        $this->typeChoices = QubitTerm::getIndentedChildTree(QubitTerm::CONTAINER_ID, '&nbsp;', ['returnObjectInstances' => true]);

        // Handle POST
        if ($request->isMethod('post')) {
            $this->resource->name = $request->getParameter('name');
            $this->resource->location = $request->getParameter('location');

            // Handle type
            $typeValue = $request->getParameter('type');
            if ($typeValue) {
                unset($this->resource->type);
                $params = $this->context->routing->parse(Qubit::pathInfo($typeValue));
                $this->resource->type = $params['_sf_route']->resource;
            }

            $this->resource->save();

            // Save extended data
            if (class_exists('\AtomFramework\Repositories\PhysicalObjectExtendedRepository')) {
                $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
                $extendedData = [
                    'building' => $request->getParameter('building'),
                    'floor' => $request->getParameter('floor'),
                    'room' => $request->getParameter('room'),
                    'aisle' => $request->getParameter('aisle'),
                    'bay' => $request->getParameter('bay'),
                    'rack' => $request->getParameter('rack'),
                    'shelf' => $request->getParameter('shelf'),
                    'position' => $request->getParameter('position'),
                    'barcode' => $request->getParameter('barcode'),
                    'reference_code' => $request->getParameter('reference_code'),
                    'width' => $request->getParameter('width') ?: null,
                    'height' => $request->getParameter('height') ?: null,
                    'depth' => $request->getParameter('depth') ?: null,
                    'total_capacity' => $request->getParameter('total_capacity') ?: null,
                    'used_capacity' => $request->getParameter('used_capacity') ?: 0,
                    'capacity_unit' => $request->getParameter('capacity_unit'),
                    'total_linear_metres' => $request->getParameter('total_linear_metres') ?: null,
                    'used_linear_metres' => $request->getParameter('used_linear_metres') ?: 0,
                    'climate_controlled' => $request->getParameter('climate_controlled') ? 1 : 0,
                    'temperature_min' => $request->getParameter('temperature_min') ?: null,
                    'temperature_max' => $request->getParameter('temperature_max') ?: null,
                    'humidity_min' => $request->getParameter('humidity_min') ?: null,
                    'humidity_max' => $request->getParameter('humidity_max') ?: null,
                    'security_level' => $request->getParameter('security_level'),
                    'access_restrictions' => $request->getParameter('access_restrictions'),
                    'status' => $request->getParameter('status') ?: 'active',
                    'notes' => $request->getParameter('notes'),
                ];
                $repo->saveExtendedData($this->resource->id, $extendedData);
            }

            $next = $request->getParameter('next');
            if ($next) {
                $this->redirect($next);
            }

            $this->redirect([$this->resource, 'module' => 'physicalobject']);
        }
    }

    // ---------------------------------------------------------------
    // DELETE
    // ---------------------------------------------------------------
    public function executeDelete($request)
    {
        $this->form = new sfForm();

        $this->resource = $this->getRoute()->resource;

        $criteria = new Criteria();
        $criteria->add(QubitRelation::SUBJECT_ID, $this->resource->id);
        $criteria->addJoin(QubitRelation::OBJECT_ID, QubitInformationObject::ID);
        $this->informationObjects = QubitInformationObject::get($criteria);

        $this->form->setValidator('next', new sfValidatorString());
        $this->form->setWidget('next', new sfWidgetFormInputHidden());

        if ($request->isMethod('delete')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                $this->resource->delete();

                $next = $this->form->getValue('next');
                if (isset($next)) {
                    $this->redirect($next);
                }

                $this->redirect('@homepage');
            }
        }
    }

    // ---------------------------------------------------------------
    // AUTOCOMPLETE
    // ---------------------------------------------------------------
    public function executeAutocomplete($request)
    {
        if (!isset($request->limit)) {
            $request->limit = $this->config('app_hits_per_page');
        }

        $criteria = new Criteria();
        $criteria->addJoin(QubitPhysicalObject::ID, QubitPhysicalObjectI18n::ID);
        $criteria->add(QubitPhysicalObjectI18n::CULTURE, $this->culture());

        if ($this->config('app_markdown_enabled', true)) {
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

    // ---------------------------------------------------------------
    // BOX LIST
    // ---------------------------------------------------------------
    public function executeBoxList($request)
    {
        if (!isset($request->limit)) {
            $request->limit = $this->config('app_hits_per_page');
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

    // ---------------------------------------------------------------
    // HOLDINGS REPORT EXPORT
    // ---------------------------------------------------------------
    public function executeHoldingsReportExport($request)
    {
        $this->form = new sfForm();
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        if ($request->isMethod('post')) {
            $this->form->bind($request->getPostParameters());

            if ($this->form->isValid()) {
                if (empty($request->includeEmpty) && empty($request->includeDescriptions) && empty($request->includeAccessions)) {
                    $message = $this->context->i18n->__('Please check one or more of the export options.');
                    $this->getUser()->setFlash('error', $message);

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

        $this->getUser()->setFlash('notice', $message);
    }
}
