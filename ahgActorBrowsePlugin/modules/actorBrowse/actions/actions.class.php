<?php

class actorBrowseActions extends sfActions
{
    protected $service;

    public function preExecute()
    {
        // Ensure I18N helper is available for the service
        sfContext::getInstance()->getConfiguration()->loadHelpers('I18N');

        // Bootstrap Laravel if not already loaded
        $bootstrapFile = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgActorBrowsePlugin/lib/Services/ActorBrowseService.php';
        require_once sfConfig::get('sf_plugins_dir') . '/ahgActorBrowsePlugin/lib/SimplePager.php';
    }

    public function executeBrowse(sfWebRequest $request)
    {
        $culture = $this->getUser()->getCulture();
        $this->service = new \AhgActorBrowse\Services\ActorBrowseService($culture);

        // Collect all request params
        $params = [
            'page' => $request->getParameter('page', 1),
            'limit' => $request->getParameter('limit', 30),
            'sort' => $request->getParameter('sort', sfConfig::get('app_sort_browser_user', 'alphabetic')),
            'sortDir' => $request->getParameter('sortDir', 'asc'),
            'subquery' => $request->getParameter('subquery', ''),
            'subqueryField' => $request->getParameter('subqueryField', ''),

            // Facet filters
            'languages' => $request->getParameter('languages'),
            'entityType' => $request->getParameter('entityType'),
            'repository' => $request->getParameter('repository'),
            'occupation' => $request->getParameter('occupation'),
            'place' => $request->getParameter('place'),
            'subject' => $request->getParameter('subject'),
            'mediatypes' => $request->getParameter('mediatypes'),
            'hasDigitalObject' => $request->getParameter('hasDigitalObject'),

            // Advanced search
            'emptyField' => $request->getParameter('emptyField'),
            'relatedType' => $request->getParameter('relatedType'),
            'relatedAuthority' => $request->getParameter('relatedAuthority'),
        ];

        // Collect advanced search criteria (sq0/sf0/so0 ...)
        for ($i = 0; $i < 20; $i++) {
            $sq = $request->getParameter("sq{$i}");
            if (null !== $sq) {
                $params["sq{$i}"] = $sq;
                $params["sf{$i}"] = $request->getParameter("sf{$i}", '');
                $params["so{$i}"] = $request->getParameter("so{$i}", 'and');
            }
        }

        // Handle global search redirect: ?query=X → subquery=X
        if (empty($params['subquery']) && !empty($request->getParameter('query'))) {
            $params['subquery'] = $request->getParameter('query');
        }

        // Execute browse
        $browseResult = $this->service->browse($params);

        // Build pager (compatible with theme's default/pager partial)
        $this->pager = new \AhgActorBrowse\SimplePager(
            $browseResult['hits'],
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );

        // Aggregations for sidebar facets
        $this->aggs = $browseResult['aggs'];

        // Build a search-like object for the template
        // The theme's aggregation partial expects $search->filters
        $this->search = new stdClass();
        $this->search->filters = $browseResult['filters'];
        $this->search->criteria = $this->service->parseCriteria($params);

        // Selected culture for i18n field extraction
        $this->selectedCulture = $culture;

        // Batch resolve entity type names for hit display
        $this->entityTypeNames = $this->service->resolveHitEntityTypes($browseResult['hits']);

        // Service reference for template helpers
        $this->browseService = $this->service;

        // Filter tags
        $this->filterTags = $this->service->buildFilterTags($params);

        // Advanced search form (minimal sfForm with dropdown fields)
        $this->form = $this->buildAdvancedSearchForm();

        // Field options for advanced search
        $this->fieldOptions = $this->service->getFieldOptions();

        // Hidden fields to preserve in advanced search form submission
        $this->hiddenFields = $this->buildHiddenFields($params);

        // Default: hide advanced search panel
        $this->showAdvanced = $this->hasAdvancedParams($params);
    }

    public function executeAutocomplete(sfWebRequest $request)
    {
        $culture = $this->getUser()->getCulture();
        $this->service = new \AhgActorBrowse\Services\ActorBrowseService($culture);

        $query = $request->getParameter('query', '');
        $limit = min(50, max(1, (int) $request->getParameter('limit', 10)));

        $results = $this->service->autocomplete($query, $limit);

        $this->getResponse()->setContentType('application/json');

        return $this->renderText(json_encode($results));
    }

    /**
     * Build minimal sfForm for the advanced search partial.
     * Only provides 3 select widgets populated via Laravel QB.
     */
    protected function buildAdvancedSearchForm(): sfForm
    {
        $choices = $this->service->getFormChoices();

        $form = new sfForm();

        $form->setWidgets([
            'repository' => new sfWidgetFormSelect(['choices' => $choices['repository']]),
            'hasDigitalObject' => new sfWidgetFormSelect(['choices' => $choices['hasDigitalObject']]),
            'entityType' => new sfWidgetFormSelect(['choices' => $choices['entityType']]),
            'emptyField' => new sfWidgetFormSelect(['choices' => $choices['emptyField']]),
            'relatedType' => new sfWidgetFormSelect(['choices' => $choices['relatedType']]),
            'relatedAuthority' => new sfWidgetFormInput(),
        ]);

        $form->setValidators([
            'repository' => new sfValidatorPass(),
            'hasDigitalObject' => new sfValidatorPass(),
            'entityType' => new sfValidatorPass(),
            'emptyField' => new sfValidatorPass(),
            'relatedType' => new sfValidatorPass(),
            'relatedAuthority' => new sfValidatorPass(),
        ]);

        // Disable CSRF for GET form
        $form->disableCSRFProtection();

        return $form;
    }

    /**
     * Build hidden fields to preserve current state in advanced search.
     */
    protected function buildHiddenFields(array $params): array
    {
        $hidden = [];
        $preserve = ['sort', 'sortDir', 'languages', 'view'];
        foreach ($preserve as $name) {
            if (!empty($params[$name])) {
                $hidden[$name] = $params[$name];
            }
        }

        return $hidden;
    }

    /**
     * Check if any advanced search params are active.
     */
    protected function hasAdvancedParams(array $params): bool
    {
        // Check for advanced criteria
        for ($i = 0; $i < 20; $i++) {
            if (!empty($params["sq{$i}"])) {
                return true;
            }
        }

        $advanced = ['emptyField', 'relatedType', 'relatedAuthority'];
        foreach ($advanced as $name) {
            if (!empty($params[$name])) {
                return true;
            }
        }

        return false;
    }
}
