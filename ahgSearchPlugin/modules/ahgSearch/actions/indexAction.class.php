<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Search index XHR action — used by treeview search.
 * Replaces base AtoM SearchIndexAction.
 * Returns JSON: {results: [...], more: "html"}
 */
class ahgSearchIndexAction extends AhgController
{
    public function execute($request)
    {
        $culture = $this->culture();
        $service = new \AhgSearch\Services\SearchService($culture);

        $query = $request->query;
        if (empty($query)) {
            $this->forward404();

            return;
        }

        // Optional semantic expansion
        $options = [
            'repos' => null,
            'collection' => null,
            'limit' => $this->config('app_hits_per_page', 10),
            'page' => 1,
        ];

        if (isset($request->repos) && ctype_digit($request->repos)) {
            $options['repos'] = $request->repos;
            $this->getUser()->setAttribute('search-realm', $request->repos);
        }

        if (isset($request->collection) && ctype_digit($request->collection)) {
            $options['collection'] = $request->collection;
        }

        $result = $service->searchIndex($query, $options);

        if ($result['total'] < 1) {
            $this->forward404();

            return;
        }
$response = ['results' => []];
        foreach ($result['results'] as $hit) {
            $data = $hit['_source'] ?? [];
            $levelOfDescription = isset($data['levelOfDescriptionId']) ? term_name($data['levelOfDescriptionId']) : null;

            $title = get_search_i18n($data, 'title', ['allowEmpty' => false]);

            $responseItem = [
                'url' => url_for(['module' => 'informationobject', 'slug' => $data['slug'] ?? '']),
                'title' => render_title($title),
                'identifier' => isset($data['identifier']) && !empty($data['identifier']) ? render_value_inline($data['identifier']) . ' - ' : '',
                'level' => $levelOfDescription ?: '',
            ];

            $response['results'][] = $responseItem;
        }

        // "Browse all descriptions" link
        $urlParams = [
            'module' => 'informationobject',
            'action' => 'browse',
            'query' => $request->query,
            'topLod' => '0',
        ];

        if (isset($request->collection)) {
            $urlParams['collection'] = $request->collection;
        }

        if ($this->config('app_enable_institutional_scoping') && $this->getUser()->hasAttribute('search-realm')) {
            $urlParams['repos'] = $this->getUser()->getAttribute('search-realm');
        }

        $url = url_for($urlParams);
        $link = $this->context->i18n->__('Browse all descriptions');
        $response['more'] = <<<EOF
<div class="more">
  <a href="{$url}">
    <i class="fa fa-search"></i>
    {$link}
  </a>
</div>
EOF;

        $this->getResponse()->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($response));
    }
}
