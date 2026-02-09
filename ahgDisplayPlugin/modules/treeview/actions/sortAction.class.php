<?php

/**
 * Treeview sort action — drag-drop reordering via moveAfter/moveBefore.
 * Replaces base AtoM InformationObjectTreeViewSortAction.
 */
class treeviewSortAction extends sfAction
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Validate move parameter
        if (!in_array($request->move, ['moveAfter', 'moveBefore'])) {
            $this->forward404();
        }

        // ACL check + protect root object
        if (
            QubitInformationObject::ROOT_ID == $this->resource->id
            || !QubitAcl::check($this->resource, 'update')
        ) {
            QubitAcl::forwardUnauthorized();
        }

        // Parse the target object reference
        $params = $this->context->routing->parse(Qubit::pathInfo($request->target));

        if (!isset($params['_sf_route'])) {
            $this->forward404();
        }
        $target = $params['_sf_route']->resource;

        // Execute the move — delegated to ORM MPTT operations
        switch ($request->move) {
            case 'moveAfter':
                $this->resource->moveToNextSiblingOf($target);
                echo 'after';
                break;

            case 'moveBefore':
                $this->resource->moveToPrevSiblingOf($target);
                echo 'before';
                break;
        }

        return sfView::NONE;
    }
}
