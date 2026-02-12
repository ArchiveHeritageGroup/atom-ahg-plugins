<?php

/**
 * Treeview component — sidebar vs full-width mode, ancestor loading.
 * Replaces base AtoM InformationObjectTreeViewComponent.
 */
class treeviewViewComponent extends AhgComponents
{
    public function execute($request)
    {
        $this->resource = $request->getAttribute('sf_route')->resource;

        $this->treeviewType = sfConfig::get('app_treeview_type__source', 'sidebar');
        if ('sidebar' !== $this->treeviewType) {
            $this->collapsible = sfConfig::get('app_treeview_allow_full_width_collapse');
            $this->itemsPerPage = sfConfig::get('app_treeview_full_items_per_page', 8000);

            return sfView::SUCCESS;
        }

        // Sidebar mode: only sortable when sorting by lft and user has update access
        $this->sortable = 'none' === sfConfig::get('app_sort_treeview_informationobject')
            && QubitAcl::check($this->resource, 'update');

        // Load ancestors (no ACL check needed)
        $this->ancestors = $this->resource->getAncestors()->orderBy('lft');

        // Number of siblings shown above and below the current node
        $numberOfPreviousOrNextSiblings = 4;

        $this->hasPrevSiblings = false;
        $this->hasNextSiblings = false;
        $this->siblingCountNext = 0;
        $this->siblingCountPrev = 0;

        // Child descriptions
        if ($this->resource->hasChildren()) {
            list($this->children, $this->hasNextSiblings) = $this->resource->getTreeViewChildren(
                ['numberOfPreviousOrNextSiblings' => $numberOfPreviousOrNextSiblings],
                $this->siblingCountNext
            );
        }
        // Show siblings if no children, but not for root descriptions
        elseif (QubitInformationObject::ROOT_ID != $this->resource->parentId) {
            // Previous siblings — get extra to detect the "+" button
            $this->prevSiblings = $this->resource->getTreeViewSiblings(
                ['limit' => $numberOfPreviousOrNextSiblings + 1, 'position' => 'previous'],
                $this->siblingCountPrev
            );
            $this->hasPrevSiblings = count($this->prevSiblings) > $numberOfPreviousOrNextSiblings;

            if ($this->hasPrevSiblings) {
                array_pop($this->prevSiblings);
            }

            $this->prevSiblings = array_reverse($this->prevSiblings);

            // Next siblings
            $this->nextSiblings = $this->resource->getTreeViewSiblings(
                ['limit' => $numberOfPreviousOrNextSiblings + 1, 'position' => 'next'],
                $this->siblingCountNext
            );
            $this->hasNextSiblings = count($this->nextSiblings) > $numberOfPreviousOrNextSiblings;

            if ($this->hasNextSiblings) {
                array_pop($this->nextSiblings);
            }
        }
    }
}
