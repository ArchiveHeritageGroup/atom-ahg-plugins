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
}
