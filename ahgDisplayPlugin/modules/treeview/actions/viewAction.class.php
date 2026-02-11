<?php

use AtomFramework\Http\Controllers\AhgController;
/**
 * Treeview data action — returns JSON for sidebar and full-width treeview.
 * Replaces base AtoM InformationObjectTreeViewAction.
 *
 * Three modes:
 *   - prevSiblings: get previous siblings of the resource
 *   - nextSiblings: get next siblings of the resource
 *   - item (default): get children of the resource
 */
class treeviewViewAction extends AhgController
{
    public function execute($request)
    {
        $this->resource = $this->getRoute()->resource;

        // Number of siblings shown above and below the current node.
        // Keep small since getTreeViewSiblings can be slow with title sorting.
        $numberOfPreviousOrNextSiblings = 4;
        $this->siblingCountNext = 0;
        $this->siblingCountPrev = 0;

        switch ($request->show) {
            case 'prevSiblings':
                $this->items = $this->resource->getTreeViewSiblings(
                    ['limit' => $numberOfPreviousOrNextSiblings + 1, 'position' => 'previous'],
                    $this->siblingCountPrev
                );
                $this->hasPrevSiblings = count($this->items) > $numberOfPreviousOrNextSiblings;

                if ($this->hasPrevSiblings) {
                    array_pop($this->items);
                }

                // Reverse array
                $this->items = array_reverse($this->items);

                break;

            case 'nextSiblings':
                $this->items = $this->resource->getTreeViewSiblings(
                    ['limit' => $numberOfPreviousOrNextSiblings + 1, 'position' => 'next'],
                    $this->siblingCountNext
                );
                $this->hasNextSiblings = count($this->items) > $numberOfPreviousOrNextSiblings;

                if ($this->hasNextSiblings) {
                    array_pop($this->items);
                }

                break;

            case 'item':
            default:
                list($this->items, $this->hasNextSiblings) = $this->resource->getTreeViewChildren(
                    ['numberOfPreviousOrNextSiblings' => $numberOfPreviousOrNextSiblings],
                    $this->siblingCountNext
                );
        }
    }
}
