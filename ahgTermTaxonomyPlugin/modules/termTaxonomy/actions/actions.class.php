<?php

use AtomFramework\Http\Controllers\AhgController;
class termTaxonomyActions extends AhgController
{
    // -----------------------------------------------------------------------
    // Term browse: /term/:slug (moved from ahgTermBrowsePlugin)
    // -----------------------------------------------------------------------

    public function executeIndex($request)
    {
        // Get the resource from the route (QubitResourceRoute resolves slug)
        $this->resource = $this->getRoute()->resource;

        // Must be a QubitTerm
        if (!$this->resource instanceof QubitTerm) {
            $this->forward404();

            return;
        }

        // Must have a parent (not root)
        if (!isset($this->resource->parent)) {
            $this->forward404();

            return;
        }

        // Disallow access to locked taxonomies
        if (in_array($this->resource->taxonomyId, QubitTaxonomy::$lockedTaxonomies)) {
            $this->getResponse()->setStatusCode(403);

            return sfView::NONE;
        }

        // Set culture
        if (isset($request->languages)) {
            $this->culture = $request->languages;
        } else {
            $this->culture = $this->culture();
        }

        // Page title
        $title = $this->resource->__toString();
        if (strlen($title) < 1) {
            $title = $this->context->i18n->__('Untitled');
        }
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");

        // Duplicate name check (errorSchema for editors)
        $this->errorSchema = null;
        if (QubitAcl::check($this->resource, 'update')) {
            $validatorSchema = new sfValidatorSchema();
            $values = [];

            $validatorSchema->name = new sfValidatorCallback(['callback' => [$this, 'checkForRepeatedNames']]);
            $values['name'] = $this->resource->getName(['cultureFallback' => true]);

            try {
                $validatorSchema->clean($values);
            } catch (sfValidatorErrorSchema $e) {
                $this->errorSchema = $e;
            }
        }

        // Only add browse elements for places, subjects, and genres
        $this->addBrowseElements = (
            QubitTaxonomy::PLACE_ID == $this->resource->taxonomyId
            || QubitTaxonomy::SUBJECT_ID == $this->resource->taxonomyId
            || QubitTaxonomy::GENRE_ID == $this->resource->taxonomyId
        );

        if (!$this->addBrowseElements) {
            return;
        }

        // Create service
        $service = new \AhgTermTaxonomy\Services\TermTaxonomyService($this->culture);

        // Handle XHR requests (treeview tab pagination)
        if ($request->isXmlHttpRequest()) {
            return $this->handleTermXhrRequest($request, $service);
        }

        // Collect browse params
        $params = [
            'page' => $request->getParameter('page', 1),
            'limit' => $this->config('app_hits_per_page', 30),
            'sort' => $request->getParameter('sort', 'lastUpdated'),
            'sortDir' => $request->getParameter('sortDir', 'desc'),
            'onlyDirect' => $request->getParameter('onlyDirect'),
            'languages' => $request->getParameter('languages'),
            'places' => $request->getParameter('places'),
            'subjects' => $request->getParameter('subjects'),
            'genres' => $request->getParameter('genres'),
        ];

        // Browse information objects for this term
        $browseResult = $service->browse(
            (int) $this->resource->id,
            (int) $this->resource->taxonomyId,
            $params
        );

        // Wrap hits as SearchHit objects for template compatibility
        $searchHits = [];
        foreach ($browseResult['hits'] as $doc) {
            $searchHits[] = new \AhgTermTaxonomy\SearchHit($doc);
        }

        // Build pager
        $this->pager = new \AhgTermTaxonomy\SimplePager(
            $searchHits,
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );

        // Aggregations
        $this->aggs = $browseResult['aggs'];
        $this->aggs['direct'] = [
            'doc_count' => $browseResult['direct'],
        ];

        // Build search object with filters (for sidebar aggregation partial)
        $this->search = new \stdClass();
        $this->search->filters = $browseResult['filters'];

        // Load term list for sidebar treeview
        $listParams = [
            'listPage' => $request->getParameter('listPage', 1),
            'listLimit' => $request->getParameter('listLimit', $this->config('app_hits_per_page', 30)),
        ];
        $listResult = $service->loadListTerms(
            (int) $this->resource->taxonomyId,
            $listParams
        );

        // Build list pager for sidebar
        $listHits = [];
        foreach ($listResult['hits'] as $doc) {
            $listHits[] = new \AhgTermTaxonomy\SearchHit($doc);
        }
        $this->listPager = new \AhgTermTaxonomy\SimplePager(
            $listHits,
            $listResult['total'],
            $listResult['page'],
            $listResult['limit']
        );
    }

    // -----------------------------------------------------------------------
    // Taxonomy browse: /taxonomy/:id (NEW — replaces base AtoM indexAction)
    // -----------------------------------------------------------------------

    public function executeTaxonomyIndex($request)
    {
        if ($this->config('app_enable_institutional_scoping')) {
            $this->getUser()->removeAttribute('search-realm');
        }

        // Resolve taxonomy by ID
        $this->resource = QubitTaxonomy::getById($request->id);

        if (!$this->resource instanceof QubitTaxonomy) {
            $this->redirect(['module' => 'taxonomy', 'action' => 'list']);
        }

        // Explicitly add resource to sf_route for treeView component
        $request->getAttribute('sf_route')->resource = $this->resource;

        // Disallow access to locked taxonomies
        if (in_array($this->resource->id, QubitTaxonomy::$lockedTaxonomies)) {
            $this->getResponse()->setStatusCode(403);

            return sfView::NONE;
        }

        // Check that this isn't the root
        if (!isset($this->resource->parent)) {
            $this->forward404();
        }

        // Restrict access (except to places, subjects, and genres)
        $unrestrictedTaxonomies = [QubitTaxonomy::GENRE_ID, QubitTaxonomy::PLACE_ID, QubitTaxonomy::SUBJECT_ID];
        $allowedGroups = [QubitAclGroup::EDITOR_ID, QubitAclGroup::ADMINISTRATOR_ID];

        if (
            !in_array($this->resource->id, $unrestrictedTaxonomies)
            && !$this->getUser()->hasGroup($allowedGroups)
        ) {
            $this->getResponse()->setStatusCode(403);

            return sfView::HEADER_ONLY;
        }

        // Culture
        $culture = $this->culture();

        // Per-taxonomy settings
        $this->icon = null;
        $this->addIoCountColumn = false;
        $this->addActorCountColumn = false;

        switch ($this->resource->id) {
            case QubitTaxonomy::PLACE_ID:
                $this->icon = 'map-marker-alt';
                $this->addIoCountColumn = true;
                $this->addActorCountColumn = true;

                $title = $this->context->i18n->__('Places');
                $this->response->setTitle("{$title} - {$this->response->getTitle()}");
                break;

            case QubitTaxonomy::SUBJECT_ID:
                $this->icon = 'tag';
                $this->addIoCountColumn = true;
                $this->addActorCountColumn = true;

                $title = $this->context->i18n->__('Subjects');
                $this->response->setTitle("{$title} - {$this->response->getTitle()}");
                break;

            case QubitTaxonomy::GENRE_ID:
                $this->addIoCountColumn = true;
                $this->addActorCountColumn = true;

                $title = $this->context->i18n->__('Genres');
                $this->response->setTitle("{$title} - {$this->response->getTitle()}");
                break;

            default:
                $title = $this->context->i18n->__(ucwords(str_replace('-', ' ', $this->request->slug ?? '')));
                $this->response->setTitle("{$title} - {$this->response->getTitle()}");
        }

        // Pagination
        $limit = (int) ($request->limit ?: $this->config('app_hits_per_page', 30));
        $page = (int) ($request->page ?: 1);

        // Avoid pagination over ES max result window
        $maxResultWindow = (int) $this->config('app_opensearch_max_result_window', 10000);
        if ($limit * $page > $maxResultWindow) {
            if ($request->isXmlHttpRequest()) {
                return;
            }

            $message = $this->context->i18n->__(
                "We've redirected you to the first page of results. To avoid using vast amounts of memory, AtoM limits pagination to %1% records. To view the last records in the current result set, try changing the sort direction.",
                ['%1%' => $maxResultWindow]
            );
            $this->getUser()->setFlash('notice', $message);

            $params = $request->getParameterHolder()->getAll();
            unset($params['page']);
            $this->redirect($params);
        }

        // Sort defaults
        $sortSetting = $this->getUser()->isAuthenticated()
            ? $this->config('app_sort_browser_user', 'lastUpdated')
            : $this->config('app_sort_browser_anonymous', 'lastUpdated');

        $sort = $request->sort ?: $sortSetting;
        $sortDir = 'asc';
        if (in_array($sort, ['lastUpdated', 'relevance'])) {
            $sortDir = 'desc';
        }
        if ($request->sortDir && in_array($request->sortDir, ['asc', 'desc'])) {
            $sortDir = $request->sortDir;
        }

        // Create service
        $service = new \AhgTermTaxonomy\Services\TermTaxonomyService($culture);

        // Handle XHR requests (treeview tab pagination)
        if ($request->isXmlHttpRequest()) {
            return $this->handleTaxonomyXhrRequest($request, $service);
        }

        // Browse taxonomy terms
        $browseParams = [
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'sortDir' => $sortDir,
            'subquery' => $request->subquery,
            'subqueryField' => $request->subqueryField,
        ];

        $browseResult = $service->browseTaxonomy((int) $this->resource->id, $browseParams);

        // Wrap hits as SearchHit objects
        $searchHits = [];
        $termIds = [];
        foreach ($browseResult['hits'] as $doc) {
            $hit = new \AhgTermTaxonomy\SearchHit($doc);
            $searchHits[] = $hit;
            $termIds[] = (int) $hit->getId();
        }

        // Build pager
        $this->pager = new \AhgTermTaxonomy\SimplePager(
            $searchHits,
            $browseResult['total'],
            $browseResult['page'],
            $browseResult['limit']
        );

        // Batch count related IOs and actors (single query each, NOT per-row)
        $this->ioCounts = [];
        $this->actorCounts = [];

        if (!empty($termIds)) {
            if ($this->addIoCountColumn) {
                $this->ioCounts = $service->batchCountRelatedIOs($termIds);
            }
            if ($this->addActorCountColumn) {
                $this->actorCounts = $service->batchCountRelatedActors($termIds);
            }
        }
    }

    // -----------------------------------------------------------------------
    // XHR handlers
    // -----------------------------------------------------------------------

    /**
     * Handle XHR requests for term browse treeview tab pagination.
     */
    protected function handleTermXhrRequest(sfWebRequest $request, \AhgTermTaxonomy\Services\TermTaxonomyService $service)
    {
        $listParams = [
            'listPage' => $request->getParameter('listPage', 1),
            'listLimit' => $request->getParameter('listLimit', $this->config('app_hits_per_page', 30)),
        ];

        $listResult = $service->loadListTerms(
            (int) $this->resource->taxonomyId,
            $listParams
        );

        if ($listResult['total'] < 1) {
            $this->forward404();

            return;
        }

        $response = ['results' => []];
        foreach ($listResult['hits'] as $doc) {
            $hit = new \AhgTermTaxonomy\SearchHit($doc);
            $data = $hit->getData();

            $result = [
                'url' => url_for(['module' => 'term', 'slug' => $data['slug']]),
                'title' => render_title(get_search_i18n($data, 'name')),
            ];

            $response['results'][] = $result;
        }

        // Build list pager for pagination
        $listHits = [];
        foreach ($listResult['hits'] as $doc) {
            $listHits[] = new \AhgTermTaxonomy\SearchHit($doc);
        }
        $listPager = new \AhgTermTaxonomy\SimplePager(
            $listHits,
            $listResult['total'],
            $listResult['page'],
            $listResult['limit']
        );

        if ($listPager->haveToPaginate()) {
            $resultCount = $this->context->i18n->__('Results %1% to %2% of %3%', [
                '%1%' => $listPager->getFirstIndice(),
                '%2%' => $listPager->getLastIndice(),
                '%3%' => $listPager->getNbResults(),
            ]);

            $previous = $next = '';
            if (1 < $listPager->getPage()) {
                $url = url_for([$this->resource, 'module' => 'term', 'listPage' => $listPager->getPage() - 1, 'listLimit' => $request->listLimit]);
                $link = '&laquo; ' . $this->context->i18n->__('Previous');

                $previous = <<<EOF
<li class="previous">
  <a href="{$url}">
    {$link}
  </a>
</li>
EOF;
            }

            if ($listPager->getLastPage() > $listPager->getPage()) {
                $url = url_for([$this->resource, 'module' => 'term', 'listPage' => $listPager->getPage() + 1, 'listLimit' => $request->listLimit]);
                $link = $this->context->i18n->__('Next') . ' &raquo;';

                $next = <<<EOF
<li class="next">
  <a href="{$url}">
    {$link}
  </a>
</li>
EOF;
            }

            $response['more'] = <<<EOF
<section>
  <div class="result-count">
    {$resultCount}
  </div>
  <div>
    <div class="pager">
      <ul>
        {$previous}
        {$next}
      </ul>
    </div>
  </div>
</section>
EOF;
        }

        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($response));
    }

    /**
     * Handle XHR requests for taxonomy browse (treeview + autocomplete).
     */
    protected function handleTaxonomyXhrRequest(sfWebRequest $request, \AhgTermTaxonomy\Services\TermTaxonomyService $service)
    {
        $culture = $this->culture();

        $browseParams = [
            'page' => $request->getParameter('page', 1),
            'limit' => $request->getParameter('limit', $this->config('app_hits_per_page', 30)),
            'sort' => 'alphabetic',
            'sortDir' => 'asc',
            'subquery' => $request->subquery,
            'subqueryField' => $request->subqueryField,
        ];

        $browseResult = $service->browseTaxonomy((int) $this->resource->id, $browseParams);

        $total = $browseResult['total'];
        if (1 > $total) {
            $this->forward404();

            return;
        }

        $response = ['results' => []];
        foreach ($browseResult['hits'] as $doc) {
            $hit = new \AhgTermTaxonomy\SearchHit($doc);
            $data = $hit->getData();

            $result = [
                'url' => url_for(['module' => 'term', 'slug' => $data['slug']]),
                'title' => render_title(get_search_i18n($data, 'name')),
                'identifier' => '',
                'level' => '',
            ];

            $response['results'][] = $result;
        }

        $url = url_for([$this->resource, 'module' => 'taxonomy', 'subquery' => $request->subquery]);
        $link = $this->context->i18n->__('Browse all terms');
        $response['more'] = <<<EOF
<div class="more">
  <a href="{$url}">
    <i class="fa fa-search"></i>
    {$link}
  </a>
</div>
EOF;

        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($response));
    }

    // -----------------------------------------------------------------------
    // Validators
    // -----------------------------------------------------------------------

    /**
     * Check for duplicate term names (validator callback).
     */
    public function checkForRepeatedNames($validator, $value)
    {
        $criteria = new Criteria();
        $criteria->add(QubitTerm::ID, $this->resource->id, Criteria::NOT_EQUAL);
        $criteria->add(QubitTerm::TAXONOMY_ID, $this->resource->taxonomyId);
        $criteria->addJoin(QubitTerm::ID, QubitTermI18n::ID);
        $criteria->add(QubitTermI18n::CULTURE, $this->culture);
        $criteria->add(QubitTermI18n::NAME, $value);

        if (0 < intval(BasePeer::doCount($criteria)->fetchColumn(0))) {
            throw new sfValidatorError($validator, $this->context->i18n->__('Name - A term with this name already exists.'));
        }
    }
}
