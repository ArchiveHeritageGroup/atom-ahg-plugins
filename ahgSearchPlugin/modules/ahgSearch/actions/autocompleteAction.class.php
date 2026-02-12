<?php

use AtomFramework\Http\Controllers\AhgController;

/**
 * Global autocomplete — multi-type header search using ES _msearch.
 * Replaces base AtoM SearchAutocompleteAction.
 */
class ahgSearchAutocompleteAction extends AhgController
{
    public function execute($request)
    {
        // Store user query string, erase wildcards
        $this->queryString = strtr($request->query, ['*' => '', '?' => '']);

        // If the query is empty, return blank response
        if (1 === preg_match('/^[\s\t\r\n]*$/', $this->queryString)) {
            return sfView::NONE;
        }

        $this->queryString = mb_strtolower($this->queryString);
        $culture = $this->context->user->getCulture();

        $service = new \AhgSearch\Services\SearchService($culture);

        // Optional semantic expansion
        $semanticTerms = [];
        try {
            if (sfContext::getInstance()->getConfiguration()->isPluginEnabled('ahgSemanticSearchPlugin')
                && class_exists('AtomFramework\Services\SemanticSearch\SemanticSearchService')) {
                $semanticService = new \AtomFramework\Services\SemanticSearch\SemanticSearchService();
                $suggestions = $semanticService->getSuggestions($this->queryString, 5);
                foreach ($suggestions as $s) {
                    $semanticTerms[] = $s['term'];
                }
            }
        } catch (\Throwable $e) {
            // Semantic search unavailable — continue without expansion
        }

        $options = ['repos' => null, 'semanticTerms' => $semanticTerms];

        // Repository realm filter
        if (isset($request->repos) && ctype_digit($request->repos)) {
            $options['repos'] = $request->repos;
            $this->context->user->setAttribute('search-realm', $request->repos);
        } elseif (sfConfig::get('app_enable_institutional_scoping')) {
            $this->context->user->removeAttribute('search-realm');
        }

        $results = $service->autocomplete($this->queryString, $options);

        // Map results to template variables (same names as base AtoM for template compat)
        $this->descriptions = $results['descriptions'];
        $this->repositories = $results['repositories'];
        $this->actors = $results['actors'];
        $this->places = $results['places'];
        $this->subjects = $results['subjects'];
        $this->semanticTerms = $semanticTerms;

        // Return blank if no results
        $totalHits = $this->descriptions['total']
            + $this->repositories['total']
            + $this->actors['total']
            + $this->places['total']
            + $this->subjects['total'];

        if (0 === $totalHits) {
            return sfView::NONE;
        }

        // Fix route params for "all matching ..." links
        $this->allMatchingIoParams = $request->getParameterHolder()->getAll();
        $this->allMatchingParams = $this->allMatchingIoParams;
        $this->allMatchingParams['subquery'] = $this->allMatchingParams['query'] ?? '';
        unset($this->allMatchingParams['query'], $this->allMatchingParams['repos']);

        // Preload levels of description
        if ($this->descriptions['total'] > 0) {
            $this->levelsOfDescription = $service->getLevelsOfDescription();
        }
    }
}
