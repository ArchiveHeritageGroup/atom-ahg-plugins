<?php

/**
 * API Action for Export Preview
 * Returns statistics and hierarchy for export preview
 * 
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class apiExportPreviewAction extends sfAction
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
            'hierarchy' => $hierarchy
        ]);
    }

    /**
     * Get collection statistics
     */
    protected function getCollectionStats($collection)
    {
        // Count descendants
        $sql = "SELECT COUNT(*) as count 
                FROM information_object 
                WHERE lft >= ? AND rgt <= ?";
        
        $conn = Propel::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute([$collection->lft, $collection->rgt]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalDescriptions = $result['count'];

        // Count digital objects
        $sql = "SELECT COUNT(*) as count 
                FROM digital_object do
                JOIN information_object io ON do.object_id = io.id
                WHERE io.lft >= ? AND io.rgt <= ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$collection->lft, $collection->rgt]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $digitalObjects = $result['count'];

        // Estimate size
        $sql = "SELECT SUM(do.byte_size) as total_size 
                FROM digital_object do
                JOIN information_object io ON do.object_id = io.id
                WHERE io.lft >= ? AND io.rgt <= ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$collection->lft, $collection->rgt]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalSize = $result['total_size'] ?? 0;

        // Estimate CSV size (rough: 500 bytes per record)
        $csvSize = $totalDescriptions * 500;
        $estimatedSize = $totalSize + $csvSize;

        return [
            'totalDescriptions' => number_format($totalDescriptions),
            'digitalObjects' => number_format($digitalObjects),
            'estimatedSize' => AhgCentralHelpers::formatBytes($estimatedSize)
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
                    $this->getHierarchy($child, $maxDepth, $currentDepth + 1) : []
            ];

            $hierarchy[] = $item;

            // Limit number of items at each level
            if (count($hierarchy) >= 10) {
                if (count($children) > 10) {
                    $hierarchy[] = [
                        'title' => sprintf('... and %d more', count($children) - 10),
                        'count' => null,
                        'children' => []
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
