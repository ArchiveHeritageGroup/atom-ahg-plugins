<?php

/**
 * Replaces apps/qubit/modules/informationobject/actions/autocompleteAction.class.php.
 *
 * Same search as base, with two access rules base leaves out. Descriptions hidden
 * from this user are never returned, and drafts are always filtered: base only
 * filtered them when the caller asked it to, so a request could simply not ask.
 * Approved as the one exception to the no-shadowing rule (#232, 23 Nov 2026).
 */
class InformationObjectAutocompleteAction extends sfAction
{
    public function execute($request)
    {
        if (!isset($request->limit)) {
            $request->limit = sfConfig::get('app_hits_per_page');
        }

        $culture = $this->context->user->getCulture();

        $this->query = new \Elastica\Query();
        $this->query->setSize($request->limit);
        $this->query->setSort([
            'levelOfDescriptionId' => 'asc',
            'identifier.untouched' => 'asc',
            'i18n.'.$culture.'.title.alphasort' => 'asc',
        ]);

        $this->queryBool = new \Elastica\Query\BoolQuery();

        if (1 === preg_match('/^[\s\t\r\n]*$/', $request->query)) {
            $this->queryBool->addMust(new \Elastica\Query\MatchAll());
        } else {
            $fields = ['i18n.'.$culture.'.title.autocomplete' => 1];

            // Search for referenceCode or identifier, and title
            if (1 == sfConfig::get('app_inherit_code_informationobject', 1)) {
                $fields['referenceCode.autocomplete'] = 1;

                // Change sort order
                $this->query->setSort([
                    'levelOfDescriptionId' => 'asc',
                    'referenceCode.untouched' => 'asc',
                    'i18n.'.$culture.'.title.alphasort' => 'asc',
                ]);
            } else {
                $fields['identifier'] = 1;
            }

            $this->queryBool->addMust(
                arElasticSearchPluginUtil::generateQueryString($request->query, $fields)
            );
        }

        // Filter results by parent
        if (!empty($request->parent) && ctype_digit($request->parent)) {
            $queryTerm = new \Elastica\Query\Term();
            $queryTerm->setTerm('parentId', $request->parent);

            $this->queryBool->addMust($queryTerm);
        }

        // Filter results by repository
        if (!empty($request->repository) && ctype_digit($request->repository)) {
            $queryTerm = new \Elastica\Query\Term();
            $queryTerm->setTerm('repository.id', $request->repository);

            $this->queryBool->addMust($queryTerm);
        }

        QubitAclSearch::filterDrafts($this->queryBool);
        $this->excludeHidden();

        $this->query->setQuery($this->queryBool);

        $resultSet = QubitSearch::getInstance()->index->getIndex('QubitInformationObject')->search($this->query);

        // Page results
        $this->pager = new QubitSearchPager($resultSet);
        $this->pager->setPage($request->page ? $request->page : 1);
        $this->pager->setMaxPerPage($request->limit);
        $this->pager->init();
    }

    /** Fails closed: if the hidden list cannot be built, nothing is returned. */
    private function excludeHidden(): void
    {
        try {
            $user = $this->getUser();
            $userId = $user->isAuthenticated() ? (int) $user->getAttribute('user_id') : null;
            $hidden = \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                ->getRestrictedObjectIds($userId ?: null);
        } catch (\Throwable $e) {
            error_log('autocomplete.visibility_failed: '.$e->getMessage());
            $this->queryBool->addMustNot(new \Elastica\Query\MatchAll());

            return;
        }

        if ([] !== $hidden) {
            $this->queryBool->addMustNot(new \Elastica\Query\Terms('_id', array_map('strval', $hidden)));
        }
    }
}
