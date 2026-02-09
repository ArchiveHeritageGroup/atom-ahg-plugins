<?php

/**
 * Global search/replace for information object fields.
 * Replaces base AtoM SearchGlobalReplaceAction.
 *
 * This action performs its own ES search rather than extending
 * SearchAdvancedAction (which is theme-dependent).
 */
class ahgSearchGlobalReplaceAction extends sfAction
{
    public function execute($request)
    {
        // Admin-only
        if (!$this->context->user->isAdministrator()) {
            QubitAcl::forwardUnauthorized();
        }

        $culture = $this->context->user->getCulture();
        $service = new \AhgSearch\Services\SearchService($culture);

        $this->title = $this->context->i18n->__('Global search/replace');
        $this->form = new sfForm([], [], false);
        $this->form->getValidatorSchema()->setOption('allow_extra_fields', true);

        $this->addFields($service);

        // Only process on POST
        if (!$request->isMethod('post')) {
            // Run the default search to show results
            $this->doSearch($request, $service, $culture);

            return;
        }

        // Require both pattern and replacement
        if (empty($request->pattern) || empty($request->replacement)) {
            $this->error = $this->context->i18n->__('Both source and replacement fields are required.');
            $this->doSearch($request, $service, $culture);

            return;
        }

        // Run the search to get matching records
        $this->doSearch($request, $service, $culture);

        // Two-step confirmation
        if (!isset($request->confirm)) {
            $this->title = $this->context->i18n->__(
                'Are you sure you want to replace "%1%" with "%2%" in %3%?',
                [
                    '%1%' => $request->pattern,
                    '%2%' => $request->replacement,
                    '%3%' => sfInflector::humanize(sfInflector::underscore($request->column)),
                ]
            );

            return;
        }

        // Execute the replacement
        if (isset($this->pager)) {
            foreach ($this->pager->getResults() as $hit) {
                $data = $hit['_source'] ?? [];
                $id = $hit['_id'] ?? null;
                if (!$id) {
                    continue;
                }

                $io = QubitInformationObject::getById($id);
                if (!$io) {
                    continue;
                }

                // Skip if the column does not exist
                if (!$io->__isset($request->column)) {
                    continue;
                }

                if (isset($request->allowRegex)) {
                    $pattern = '/' . strtr($request->pattern, ['/' => '\/']) . '/';
                    if (!isset($request->caseSensitive)) {
                        $pattern .= 'i';
                    }
                    $replacement = strtr($request->replacement, ['/' => '\/']);
                    $replaced = preg_replace($pattern, $replacement, $io->__get($request->column));
                } elseif (isset($request->caseSensitive)) {
                    $replaced = str_replace($request->pattern, $request->replacement, $io->__get($request->column));
                } else {
                    $replaced = str_ireplace($request->pattern, $request->replacement, $io->__get($request->column));
                }

                $io->__set($request->column, $replaced);
                $io->save();
            }

            // Force refresh of ES index
            QubitSearch::getInstance()->optimize();
        }

        // When complete, redirect to GSR home
        $this->redirect(['module' => 'ahgSearch', 'action' => 'globalReplace']);
    }

    protected function doSearch($request, \AhgSearch\Services\SearchService $service, string $culture)
    {
        $query = $request->query ?? $request->getParameter('sq0') ?? '';
        if (empty($query) && !$request->isMethod('post')) {
            return;
        }

        // If doing a global replace, search for the pattern in all records
        $searchQuery = $request->isMethod('post') && !empty($request->pattern)
            ? $request->pattern
            : $query;

        if (empty($searchQuery)) {
            return;
        }

        $limit = sfConfig::get('app_hits_per_page', 10);
        $page = 1;
        if (isset($request->page) && ctype_digit($request->page)) {
            $page = (int) $request->page;
        }

        $result = $service->searchIndex($searchQuery, [
            'limit' => $limit,
            'page' => $page,
            'repos' => $request->repos ?? null,
            'collection' => $request->collection ?? null,
        ]);

        $this->pager = new AhgSearchPager(
            $result['results'],
            $result['total'],
            $limit,
            $page
        );
    }

    protected function addFields(\AhgSearch\Services\SearchService $service)
    {
        // IO i18n column choices
        $choices = $service->getIoI18nColumns();

        $this->form->setValidator('column', new sfValidatorString());
        $this->form->setWidget('column', new sfWidgetFormSelect(['choices' => $choices], ['style' => 'width: auto']));

        // Search-replace values
        $this->form->setValidator('pattern', new sfValidatorString());
        $this->form->setWidget('pattern', new sfWidgetFormInput());

        $this->form->setValidator('replacement', new sfValidatorString());
        $this->form->setWidget('replacement', new sfWidgetFormInput());

        $this->form->setValidator('caseSensitive', new sfValidatorBoolean());
        $this->form->setWidget('caseSensitive', new sfWidgetFormInputCheckbox());

        $this->form->setValidator('allowRegex', new sfValidatorBoolean());
        $this->form->setWidget('allowRegex', new sfWidgetFormInputCheckbox());

        if ($this->request->isMethod('post') && !isset($this->request->confirm)
            && !empty($this->request->pattern) && !empty($this->request->replacement)) {
            $this->form->setValidator('confirm', new sfValidatorBoolean());
            $this->form->setWidget('confirm', new sfWidgetFormInputHidden([], ['value' => true]));
        }
    }
}
