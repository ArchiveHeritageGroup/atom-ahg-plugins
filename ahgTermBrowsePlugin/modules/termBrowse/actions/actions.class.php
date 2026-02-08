<?php

class termBrowseActions extends sfActions
{
    public function preExecute()
    {
        parent::preExecute();

        sfContext::getInstance()->getConfiguration()->loadHelpers(['I18N', 'Url', 'Qubit', 'Text']);

        // Bootstrap Laravel DB if not already done
        if (!class_exists('Illuminate\Database\Capsule\Manager')) {
            $frameworkBoot = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBoot)) {
                require_once $frameworkBoot;
            }
        }
    }

    public function executeIndex(sfWebRequest $request)
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
            $this->culture = $this->context->user->getCulture();
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
        $service = new \AhgTermBrowse\Services\TermBrowseService($this->culture);

        // Handle XHR requests (treeview tab pagination)
        if ($request->isXmlHttpRequest()) {
            return $this->handleXhrRequest($request, $service);
        }

        // Collect browse params
        $params = [
            'page' => $request->getParameter('page', 1),
            'limit' => sfConfig::get('app_hits_per_page', 30),
            'sort' => $request->getParameter('sort', 'lastUpdated'),
            'sortDir' => $request->getParameter('sf_culture') ?: ($request->getParameter('sortDir', 'desc')),
            'onlyDirect' => $request->getParameter('onlyDirect'),
            'languages' => $request->getParameter('languages'),
            'places' => $request->getParameter('places'),
            'subjects' => $request->getParameter('subjects'),
            'genres' => $request->getParameter('genres'),
        ];

        // Fix sortDir — the param name can collide
        $params['sortDir'] = $request->getParameter('sortDir', 'desc');

        // Browse information objects for this term
        $browseResult = $service->browse(
            (int) $this->resource->id,
            (int) $this->resource->taxonomyId,
            $params
        );

        // Wrap hits as SearchHit objects for template compatibility
        $searchHits = [];
        foreach ($browseResult['hits'] as $doc) {
            $searchHits[] = new \AhgTermBrowse\SearchHit($doc);
        }

        // Build pager
        $this->pager = new \AhgTermBrowse\SimplePager(
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
            'listLimit' => $request->getParameter('listLimit', sfConfig::get('app_hits_per_page', 30)),
        ];
        $listResult = $service->loadListTerms(
            (int) $this->resource->taxonomyId,
            $listParams
        );

        // Build list pager for sidebar
        $listHits = [];
        foreach ($listResult['hits'] as $doc) {
            $listHits[] = new \AhgTermBrowse\SearchHit($doc);
        }
        $this->listPager = new \AhgTermBrowse\SimplePager(
            $listHits,
            $listResult['total'],
            $listResult['page'],
            $listResult['limit']
        );
    }

    /**
     * Handle XHR requests for treeview tab pagination.
     */
    protected function handleXhrRequest(sfWebRequest $request, \AhgTermBrowse\Services\TermBrowseService $service)
    {
        $listParams = [
            'listPage' => $request->getParameter('listPage', 1),
            'listLimit' => $request->getParameter('listLimit', sfConfig::get('app_hits_per_page', 30)),
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
            $hit = new \AhgTermBrowse\SearchHit($doc);
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
            $listHits[] = new \AhgTermBrowse\SearchHit($doc);
        }
        $listPager = new \AhgTermBrowse\SimplePager(
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
