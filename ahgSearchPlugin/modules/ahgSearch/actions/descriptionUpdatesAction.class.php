<?php

/**
 * Description updates — list of recently updated records.
 * Replaces base AtoM SearchDescriptionUpdatesAction.
 */
class ahgSearchDescriptionUpdatesAction extends sfAction
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
        // Criteria to fetch user actions
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
        $limit = sfConfig::get('app_hits_per_page');
        $page = (isset($this->request->page) && ctype_digit($this->request->page)) ? $this->request->page : 1;

        $this->pager = new QubitPager('QubitAuditLog');
        $this->pager->setCriteria($criteria);
        $this->pager->setPage($page);
        $this->pager->setMaxPerPage($limit);
        $this->pager->init();
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
        $this->pager = new AhgSearchPager($result['hits'], $result['total'], $limit, $page);
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
                    QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID => QubitTerm::getById(QubitTerm::PUBLICATION_STATUS_PUBLISHED_ID)->name,
                    QubitTerm::PUBLICATION_STATUS_DRAFT_ID => QubitTerm::getById(QubitTerm::PUBLICATION_STATUS_DRAFT_ID)->name,
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

/**
 * Simple pager for ES results to maintain template compatibility.
 */
class AhgSearchPager
{
    protected array $hits;
    protected int $total;
    protected int $maxPerPage;
    protected int $page;

    public function __construct(array $hits, int $total, int $maxPerPage, int $page)
    {
        $this->hits = $hits;
        $this->total = $total;
        $this->maxPerPage = $maxPerPage;
        $this->page = $page;
    }

    public function getNbResults(): int
    {
        return $this->total;
    }

    public function getResults(): array
    {
        return $this->hits;
    }

    public function getMaxPerPage(): int
    {
        return $this->maxPerPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->maxPerPage));
    }

    public function haveToPaginate(): bool
    {
        return $this->total > $this->maxPerPage;
    }

    public function getFirstPage(): int
    {
        return 1;
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->page - 1);
    }

    public function getNextPage(): int
    {
        return min($this->getLastPage(), $this->page + 1);
    }

    public function count(): int
    {
        return $this->total;
    }

    public function getFirstIndice(): int
    {
        if (0 === $this->total) {
            return 0;
        }

        return ($this->page - 1) * $this->maxPerPage + 1;
    }

    public function getLastIndice(): int
    {
        if (0 === $this->total) {
            return 0;
        }

        return min($this->page * $this->maxPerPage, $this->total);
    }

    /**
     * Get array of page numbers for pagination links.
     */
    public function getLinks(int $nbLinks = 5): array
    {
        $lastPage = $this->getLastPage();
        $links = [];

        $start = max(1, $this->page - (int) floor($nbLinks / 2));
        $end = min($lastPage, $start + $nbLinks - 1);
        $start = max(1, $end - $nbLinks + 1);

        for ($i = $start; $i <= $end; ++$i) {
            $links[] = $i;
        }

        return $links;
    }
}
