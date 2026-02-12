<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * API Action for Export Preview
 * Returns statistics and hierarchy for export preview
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class apiExportPreviewAction extends AhgController
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->context->user->isAuthenticated()) {
            return $this->renderJson(['error' => 'Unauthorized'], 401);
        }

        $collectionId = $request->getParameter('collection');

        if (!$collectionId) {
            return $this->renderJson(['error' => 'Collection ID required'], 400);
        }

        $collection = QubitInformationObject::getById($collectionId);

        if (!$collection) {
            return $this->renderJson(['error' => 'Collection not found'], 404);
        }

        // Get statistics
        $stats = $this->getCollectionStats($collection);

        // Get hierarchy (limited depth for preview)
        $hierarchy = $this->getHierarchy($collection, 3);

        return $this->renderJson([
            'totalDescriptions' => $stats['totalDescriptions'],
            'digitalObjects' => $stats['digitalObjects'],
            'estimatedSize' => $stats['estimatedSize'],
            'hierarchy' => $hierarchy,
        ]);
    }

    /**
     * Get collection statistics
     */
    protected function getCollectionStats($collection)
    {
        // Count descendants
        $totalDescriptions = DB::table('information_object')
            ->where('lft', '>=', $collection->lft)
            ->where('rgt', '<=', $collection->rgt)
            ->count();

        // Count digital objects
        $digitalObjects = DB::table('digital_object as do')
            ->join('information_object as io', 'do.object_id', '=', 'io.id')
            ->where('io.lft', '>=', $collection->lft)
            ->where('io.rgt', '<=', $collection->rgt)
            ->count();

        // Estimate size
        $totalSize = DB::table('digital_object as do')
            ->join('information_object as io', 'do.object_id', '=', 'io.id')
            ->where('io.lft', '>=', $collection->lft)
            ->where('io.rgt', '<=', $collection->rgt)
            ->sum('do.byte_size') ?? 0;

        // Estimate CSV size (rough: 500 bytes per record)
        $csvSize = $totalDescriptions * 500;
        $estimatedSize = $totalSize + $csvSize;

        return [
            'totalDescriptions' => number_format($totalDescriptions),
            'digitalObjects' => number_format($digitalObjects),
            'estimatedSize' => AhgCentralHelpers::formatBytes($estimatedSize),
        ];
    }

    /**
     * Get hierarchy for preview
     */
    protected function getHierarchy($parent, $maxDepth, $currentDepth = 0)
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $hierarchy = [];
        $children = $parent->getChildren();

        foreach ($children as $child) {
            $childCount = $this->getChildCount($child);

            $item = [
                'title' => $child->getTitle(['cultureFallback' => true]),
                'identifier' => $child->identifier,
                'level' => $child->getLevelOfDescription() ?
                    $child->getLevelOfDescription()->getName(['cultureFallback' => true]) : '',
                'count' => $childCount > 0 ? $childCount : null,
                'children' => $childCount > 0 ?
                    $this->getHierarchy($child, $maxDepth, $currentDepth + 1) : [],
            ];

            $hierarchy[] = $item;

            // Limit number of items at each level
            if (count($hierarchy) >= 10) {
                if (count($children) > 10) {
                    $hierarchy[] = [
                        'title' => sprintf('... and %d more', count($children) - 10),
                        'count' => null,
                        'children' => [],
                    ];
                }
                break;
            }
        }

        return $hierarchy;
    }

    /**
     * Get child count for item
     */
    protected function getChildCount($item)
    {
        return (($item->rgt - $item->lft) - 1) / 2;
    }

    /**
     * Render JSON response
     */
    protected function renderJson($data, $statusCode = 200)
    {
        $this->getResponse()->setStatusCode($statusCode);
        echo json_encode($data);

        return sfView::NONE;
    }
}
