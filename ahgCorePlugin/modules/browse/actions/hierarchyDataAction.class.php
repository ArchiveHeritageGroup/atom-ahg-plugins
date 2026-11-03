<?php

/**
 * AHG stub for browse/hierarchyData action.
 * Replaces apps/qubit/modules/browse/actions/hierarchyDataAction.class.php.
 *
 * Returns JSON data for the browse hierarchy page.
 */
class BrowseHierarchyDataAction extends DefaultFullTreeViewAction
{
    public function execute($request)
    {
        parent::execute($request);

        $this->resource = QubitInformationObject::getRoot();

        // Impose limit to what nodeLimit parameter can be set to
        $maxItemsPerPage = sfConfig::get('app_treeview_items_per_page_max', 10000);
        if (!ctype_digit($request->nodeLimit) || $request->nodeLimit > $maxItemsPerPage) {
            $request->nodeLimit = $maxItemsPerPage;
        }

        // Do ordering during query as we need to page through the results
        $options = [
            'orderColumn' => 'title',
            'memorySort' => true,
            'skip' => $request->skip,
            'limit' => $request->nodeLimit,
        ];

        // Load the children of the root node (top-level descriptions)
        $data = $this->getChildren($this->resource->id, $options);

        return $this->renderText(json_encode($data));
    }

    /**
     * Count children with search()->getTotalHits() instead of base's
     * IndexWrapper::count(), which arOpenSearchPlugin does not have. Same fix
     * as InformationObjectFullWidthTreeViewAction::countChildren() (CH-000104);
     * without it the browse hierarchy page returned an empty body and stayed
     * on "Loading ...".
     *
     * @param mixed $id
     * @param array $options
     *
     * @return int number of children
     */
    protected function countChildren($id, $options = [])
    {
        $term = new \Elastica\Query\Term(['parentId' => $id]);
        $options['limit'] = 0;

        $query = $this->getElasticSearchQuery($term, $options);

        return QubitSearch::getInstance()
            ->index
            ->getIndex('QubitInformationObject')
            ->search($query->getQuery(false, false))
            ->getTotalHits();
    }
}
