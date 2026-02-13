<?php

use AtomFramework\Http\Controllers\AhgController;
use AtomFramework\Services\Pagination\PaginationService;
use AtomFramework\Services\Pagination\SimplePager;

/**
 * Description updates — list of recently updated records.
 * Replaces base AtoM SearchDescriptionUpdatesAction.
 */
class ahgSearchDescriptionUpdatesAction extends AhgController
{
    public static $NAMES = [
        'className',
        'startDate',
        'endDate',
        'dateOf',
        'publicationStatus',
        'repository',
        'user',
    ];

    public function execute($request)
    {
        $title = $this->context->i18n->__('Newest additions');
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Store user and user URL for convenience
        if (!empty($userUrl = $request->getGetParameter('user'))) {
            $params = $this->context->routing->parse($userUrl);
            $this->user = $params['_sf_route']->resource;
        }

        // Create form (without CSRF protection)
        $this->form = new sfForm([], [], false);
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $culture = $this->context->user->getCulture();
        $service = new \AhgSearch\Services\SearchService($culture);

        foreach (self::$NAMES as $name) {
            $this->addField($name, $service);
        }

        $defaults = [
            'className' => 'QubitInformationObject',
            'startDate' => date('Y-m-d', strtotime('-1 month')),
            'endDate' => date('Y-m-d'),
            'dateOf' => 'CREATED_AT',
            'publicationStatus' => 'all',
            'repository' => null,
            'user' => null,
        ];

        $this->form->bind($request->getGetParameters() + $defaults);

        if ($this->form->isValid()) {
            $this->className = $this->form->getValue('className');
            $nameColumnDisplay = $this->className === 'QubitInformationObject' ? 'Title' : 'Name';
            $this->nameColumnDisplay = $this->context->i18n->__($nameColumnDisplay);
            $this->doSearch($service);
        }

        $this->showForm = $this->request->getParameter('showForm');
    }

    public function doAuditLogSearch()
    {
        $limit = sfConfig::get('app_hits_per_page');
        $page = (isset($this->request->page) && ctype_digit($this->request->page)) ? $this->request->page : 1;

        if (class_exists('QubitPager')) {
            // === Propel mode ===
            $criteria = new Criteria();
            $criteria->addJoin(QubitAuditLog::OBJECT_ID, QubitInformationObject::ID);

            // Publication status filtering
            if ('all' != $this->form->getValue('publicationStatus')) {
                $criteria->addJoin(QubitAuditLog::OBJECT_ID, QubitStatus::OBJECT_ID);
                $criteria->add(QubitStatus::STATUS_ID, $this->form->getValue('publicationStatus'));
            }

            // User action type filtering
            if ('both' != $this->form->getValue('dateOf')) {
                switch ($this->form->getValue('dateOf')) {
                    case 'CREATED_AT':
                        $criteria->add(QubitAuditLog::ACTION_TYPE_ID, QubitTerm::USER_ACTION_CREATION_ID);
                        break;

                    case 'UPDATED_AT':
                        $criteria->add(QubitAuditLog::ACTION_TYPE_ID, QubitTerm::USER_ACTION_MODIFICATION_ID);
                        break;
                }
            }

            // Repository restriction
            if (null !== $this->form->getValue('repository')) {
                $criteria->add(QubitInformationObject::REPOSITORY_ID, $this->form->getValue('repository'));
            }

            // User restriction
            if (isset($this->user) && $this->user instanceof QubitUser) {
                $criteria->add(QubitAuditLog::USER_ID, $this->user->getId());
            }

            // Date restriction
            $criteria->add(QubitAuditLog::CREATED_AT, $this->form->getValue('startDate'), Criteria::GREATER_EQUAL);
            $endDateTime = new DateTime($this->form->getValue('endDate'));
            $criteria->addAnd(QubitAuditLog::CREATED_AT, $endDateTime->modify('+1 day')->format('Y-m-d'), Criteria::LESS_THAN);

            // Sort in reverse chronological order
            $criteria->addDescendingOrderByColumn(QubitAuditLog::CREATED_AT);

            // Page results
            $this->pager = new QubitPager('QubitAuditLog');
            $this->pager->setCriteria($criteria);
            $this->pager->setPage($page);
            $this->pager->setMaxPerPage($limit);
            $this->pager->init();
        } else {
            // === Standalone mode ===
            $options = [
                'join' => [
                    'information_object' => ['audit_log.object_id', '=', 'information_object.id'],
                ],
                'where' => [],
                'orderBy' => ['audit_log.created_at' => 'desc'],
            ];

            // Publication status
            if ('all' != $this->form->getValue('publicationStatus')) {
                $options['join']['status'] = ['audit_log.object_id', '=', 'status.object_id'];
                $options['where'][] = ['status.status_id', '=', $this->form->getValue('publicationStatus')];
            }

            // Action type
            if ('both' != $this->form->getValue('dateOf')) {
                switch ($this->form->getValue('dateOf')) {
                    case 'CREATED_AT':
                        $options['where'][] = ['audit_log.action_type_id', '=', QubitTerm::USER_ACTION_CREATION_ID];
                        break;

                    case 'UPDATED_AT':
                        $options['where'][] = ['audit_log.action_type_id', '=', QubitTerm::USER_ACTION_MODIFICATION_ID];
                        break;
                }
            }

            // Repository
            if (null !== $this->form->getValue('repository')) {
                $options['where'][] = ['information_object.repository_id', '=', $this->form->getValue('repository')];
            }

            // User
            if (isset($this->user) && $this->user instanceof \QubitUser) {
                $options['where'][] = ['audit_log.user_id', '=', $this->user->getId()];
            }

            // Date range
            $options['where'][] = ['audit_log.created_at', '>=', $this->form->getValue('startDate')];
            $endDateTime = new DateTime($this->form->getValue('endDate'));
            $options['where'][] = ['audit_log.created_at', '<', $endDateTime->modify('+1 day')->format('Y-m-d')];

            $this->pager = PaginationService::paginate('audit_log', $options, (int) $page, (int) $limit);
        }
    }

    public function doSearch(\AhgSearch\Services\SearchService $service)
    {
        if ('QubitInformationObject' === $this->className && sfConfig::get('app_audit_log_enabled', false)) {
            return $this->doAuditLogSearch();
        }

        $limit = sfConfig::get('app_hits_per_page', 10);
        if (isset($this->request->limit) && ctype_digit($this->request->limit)) {
            $limit = (int) $this->request->limit;
        }

        $page = 1;
        if (isset($this->request->page) && ctype_digit($this->request->page)) {
            $page = (int) $this->request->page;
        }

        // Avoid pagination over ES max result window
        $maxResultWindow = (int) sfConfig::get('app_opensearch_max_result_window', 10000);

        if ($limit * $page > $maxResultWindow) {
            $message = $this->context->i18n->__(
                "We've redirected you to the first page of results. To avoid using vast amounts of memory, AtoM limits pagination to %1% records. Please, narrow down your results.",
                ['%1%' => $maxResultWindow]
            );
            $this->getUser()->setFlash('notice', $message);

            $params = $this->request->getParameterHolder()->getAll();
            unset($params['page']);
            $this->redirect($params);
        }

        $searchParams = [
            'className' => $this->form->getValue('className'),
            'dateOf' => $this->form->getValue('dateOf'),
            'startDate' => $this->form->getValue('startDate'),
            'endDate' => $this->form->getValue('endDate'),
            'publicationStatus' => $this->form->getValue('publicationStatus'),
            'repository' => $this->form->getValue('repository'),
            'limit' => $limit,
            'page' => $page,
        ];

        $result = $service->descriptionUpdates($searchParams);

        // Build a pager-compatible object for templates
        $this->pager = new SimplePager($result['hits'], $result['total'], $page, $limit);
    }

    protected function addField($name, \AhgSearch\Services\SearchService $service)
    {
        switch ($name) {
            case 'className':
                $choices = [
                    'QubitInformationObject' => sfConfig::get('app_ui_label_informationobject'),
                    'QubitActor' => sfConfig::get('app_ui_label_actor'),
                    'QubitRepository' => sfConfig::get('app_ui_label_repository'),
                    'QubitTerm' => sfConfig::get('app_ui_label_term'),
                    'QubitFunctionObject' => sfConfig::get('app_ui_label_function'),
                ];

                $this->form->setValidator($name, new sfValidatorString());
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));
                break;

            case 'startDate':
                $this->form->setValidator($name, new sfValidatorDate([], ['invalid' => $this->context->i18n->__('Invalid start date')]));
                $this->form->setWidget($name, new sfWidgetFormInput());
                break;

            case 'endDate':
                $this->form->setValidator($name, new sfValidatorDate([], ['invalid' => $this->context->i18n->__('Invalid end date')]));
                $this->form->setWidget($name, new sfWidgetFormInput());
                break;

            case 'dateOf':
                $choices = [
                    'CREATED_AT' => $this->context->i18n->__('Creation'),
                    'UPDATED_AT' => $this->context->i18n->__('Revision'),
                    'both' => $this->context->i18n->__('Both'),
                ];

                $this->form->setValidator($name, new sfValidatorChoice(['choices' => array_keys($choices)]));
                $this->form->setWidget($name, new arWidgetFormSelectRadio(['choices' => $choices, 'class' => 'radio inline']));
                break;

            case 'publicationStatus':
                $choices = [
                    QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID => term_name(QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID),
                    QubitTerm::PUBLICATION_STATUS_DRAFT_ID => term_name(QubitTerm::PUBLICATION_STATUS_DRAFT_ID),
                    'all' => $this->context->i18n->__('All'),
                ];

                $this->form->setValidator($name, new sfValidatorChoice(['choices' => array_keys($choices)]));
                $this->form->setWidget($name, new arWidgetFormSelectRadio(['choices' => $choices, 'class' => 'radio inline']));
                break;

            case 'repository':
                $choices = $service->getRepositoryList();

                $this->form->setValidator($name, new sfValidatorChoice(['choices' => array_keys($choices)]));
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => $choices]));
                break;

            case 'user':
                $this->form->setValidator($name, new sfValidatorString());
                $this->form->setWidget($name, new sfWidgetFormSelect(['choices' => []], ['class' => 'form-autocomplete']));
                break;
        }
    }
}
