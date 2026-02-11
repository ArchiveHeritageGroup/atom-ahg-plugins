<?php

namespace AhgInformationObjectManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Treeview service for information object hierarchy.
 *
 * Replaces base AtoM's QubitInformationObject::getTreeViewChildren() and
 * getTreeViewSiblings() Propel queries with Laravel Query Builder.
 */
class TreeviewService
{
    public const ROOT_ID = 1;

    /**
     * Get ancestors of a node (ordered from root to parent).
     *
     * @return array Array of node objects with id, title, slug, lft, rgt, levelOfDescription
     */
    public static function getAncestors(int $id, string $culture, bool $isAuthenticated = false): array
    {
        $node = DB::table('information_object')
            ->where('id', $id)
            ->select('lft', 'rgt')
            ->first();

        if (!$node) {
            return [];
        }

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.lft', '<', $node->lft)
            ->where('io.rgt', '>', $node->rgt)
            ->where('io.id', '!=', self::ROOT_ID)
            ->select(
                'io.id',
                'io.identifier',
                'io.lft',
                'io.rgt',
                'io.parent_id',
                'io.level_of_description_id',
                'ioi.title',
                's.slug'
            )
            ->orderBy('io.lft');

        return $query->get()->map(function ($row) {
            return self::formatNode($row);
        })->all();
    }

    /**
     * Get children of a node for the treeview.
     *
     * @param int    $parentId       Parent node ID
     * @param string $culture        User culture
     * @param bool   $isAuthenticated Whether user is logged in
     * @param int    $limit          Max children to return
     * @param string $sort           Sort mode: 'none' (lft), 'title', 'identifierTitle'
     *
     * @return array ['items' => [...], 'hasMore' => bool, 'totalChildren' => int]
     */
    public static function getChildren(
        int $parentId,
        string $culture,
        bool $isAuthenticated = false,
        int $limit = 5,
        string $sort = 'none'
    ): array {
        $query = self::buildChildQuery($parentId, $culture, $isAuthenticated, $sort);

        // Get total count
        $totalChildren = (clone $query)->count();

        // Get limited results
        $items = $query->limit($limit)
            ->get()
            ->map(function ($row) use ($parentId) {
                $node = self::formatNode($row);
                $node['parentId'] = $parentId;

                return $node;
            })
            ->all();

        return [
            'items' => $items,
            'hasMore' => $totalChildren > $limit,
            'totalChildren' => $totalChildren,
        ];
    }

    /**
     * Get siblings of a node (previous or next).
     *
     * @param int    $nodeId         Current node ID
     * @param string $position       'previous' or 'next'
     * @param string $culture        User culture
     * @param bool   $isAuthenticated Whether user is logged in
     * @param int    $limit          Max siblings to return
     * @param string $sort           Sort mode
     *
     * @return array ['items' => [...], 'hasMore' => bool, 'remaining' => int]
     */
    public static function getSiblings(
        int $nodeId,
        string $position,
        string $culture,
        bool $isAuthenticated = false,
        int $limit = 5,
        string $sort = 'none'
    ): array {
        $node = DB::table('information_object')
            ->where('id', $nodeId)
            ->select('id', 'parent_id', 'lft', 'rgt', 'identifier')
            ->first();

        if (!$node || $node->parent_id == self::ROOT_ID) {
            return ['items' => [], 'hasMore' => false, 'remaining' => 0];
        }

        // Get title for sort comparison
        $nodeTitle = '';
        if ($sort !== 'none') {
            $nodeTitle = DB::table('information_object_i18n')
                ->where('id', $nodeId)
                ->where('culture', $culture)
                ->value('title') ?? '';
        }

        $query = self::buildSiblingQuery(
            $node,
            $nodeTitle,
            $position,
            $culture,
            $isAuthenticated,
            $sort
        );

        // Get total count (for remaining indicator)
        $totalCount = (clone $query)->count();

        // Get limited results (+1 to detect hasMore)
        $items = $query->limit($limit + 1)
            ->get()
            ->map(function ($row) use ($node) {
                $n = self::formatNode($row);
                $n['parentId'] = $node->parent_id;

                return $n;
            })
            ->all();

        $hasMore = count($items) > $limit;
        if ($hasMore) {
            array_pop($items);
        }

        // Reverse for previous siblings (queried in descending order)
        if ($position === 'previous') {
            $items = array_reverse($items);
        }

        return [
            'items' => $items,
            'hasMore' => $hasMore,
            'remaining' => max(0, $totalCount - $limit),
        ];
    }

    /**
     * Get the full initial treeview data for a node (ancestors + siblings + children).
     *
     * This is used for the sidebar treeview initial render.
     *
     * @return array Complete treeview data structure
     */
    public static function getTreeViewData(
        int $nodeId,
        string $culture,
        bool $isAuthenticated = false,
        string $sort = 'none'
    ): array {
        $numberOfSiblings = 4;

        $node = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', $nodeId)
            ->select(
                'io.id', 'io.identifier', 'io.lft', 'io.rgt',
                'io.parent_id', 'io.level_of_description_id',
                'ioi.title', 's.slug'
            )
            ->first();

        if (!$node) {
            return ['error' => 'Node not found'];
        }

        $ancestors = self::getAncestors($nodeId, $culture, $isAuthenticated);
        $currentNode = self::formatNode($node);

        $hasChildren = DB::table('information_object')
            ->where('parent_id', $nodeId)
            ->exists();

        $children = [];
        $prevSiblings = [];
        $nextSiblings = [];
        $hasPrevSiblings = false;
        $hasNextSiblings = false;

        if ($hasChildren) {
            $childResult = self::getChildren($nodeId, $culture, $isAuthenticated, $numberOfSiblings + 1, $sort);
            $children = $childResult['items'];
            $hasNextSiblings = count($children) > $numberOfSiblings;
            if ($hasNextSiblings) {
                array_pop($children);
            }
        } elseif ($node->parent_id != self::ROOT_ID) {
            // Show siblings
            $prevResult = self::getSiblings($nodeId, 'previous', $culture, $isAuthenticated, $numberOfSiblings, $sort);
            $prevSiblings = $prevResult['items'];
            $hasPrevSiblings = $prevResult['hasMore'];

            $nextResult = self::getSiblings($nodeId, 'next', $culture, $isAuthenticated, $numberOfSiblings, $sort);
            $nextSiblings = $nextResult['items'];
            $hasNextSiblings = $nextResult['hasMore'];
        }

        return [
            'ancestors' => $ancestors,
            'current' => $currentNode,
            'hasChildren' => $hasChildren,
            'children' => $children,
            'prevSiblings' => $prevSiblings,
            'nextSiblings' => $nextSiblings,
            'hasPrevSiblings' => $hasPrevSiblings,
            'hasNextSiblings' => $hasNextSiblings,
            'sort' => $sort,
        ];
    }

    /**
     * Move a node after another node (drag-drop sort).
     *
     * Updates the lft/rgt values of the target node to be positioned
     * after the reference node. Only works when sort='none' (manual mode).
     */
    public static function moveAfter(int $nodeId, int $afterNodeId): bool
    {
        $node = DB::table('information_object')
            ->where('id', $nodeId)
            ->select('id', 'lft', 'rgt', 'parent_id')
            ->first();

        $after = DB::table('information_object')
            ->where('id', $afterNodeId)
            ->select('id', 'lft', 'rgt', 'parent_id')
            ->first();

        if (!$node || !$after || $node->parent_id !== $after->parent_id) {
            return false;
        }

        $width = $node->rgt - $node->lft + 1;
        $newPos = $after->rgt + 1;

        DB::beginTransaction();

        try {
            // Step 1: Temporarily set node values to negative (to avoid conflicts)
            DB::table('information_object')
                ->where('lft', '>=', $node->lft)
                ->where('rgt', '<=', $node->rgt)
                ->update([
                    'lft' => DB::raw('lft * -1'),
                    'rgt' => DB::raw('rgt * -1'),
                ]);

            // Step 2: Close the gap left by the moved node
            DB::table('information_object')
                ->where('lft', '>', $node->rgt)
                ->decrement('lft', $width);

            DB::table('information_object')
                ->where('rgt', '>', $node->rgt)
                ->decrement('rgt', $width);

            // Recalculate newPos if it was affected by the gap closure
            if ($newPos > $node->rgt) {
                $newPos -= $width;
            }

            // Step 3: Open gap at the new position
            DB::table('information_object')
                ->where('lft', '>=', $newPos)
                ->increment('lft', $width);

            DB::table('information_object')
                ->where('rgt', '>=', $newPos)
                ->increment('rgt', $width);

            // Step 4: Move the node to the new position
            $offset = $newPos - $node->lft;
            DB::table('information_object')
                ->where('lft', '<', 0)
                ->update([
                    'lft' => DB::raw('(lft * -1) + ' . $offset),
                    'rgt' => DB::raw('(rgt * -1) + ' . $offset),
                ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * Get full-width treeview data (flat list of all descendants).
     *
     * @return array ['items' => [...], 'total' => int]
     */
    public static function getFullWidthTree(
        int $rootId,
        string $culture,
        bool $isAuthenticated = false,
        int $limit = 8000,
        int $offset = 0,
        string $sort = 'none'
    ): array {
        $root = DB::table('information_object')
            ->where('id', $rootId)
            ->select('lft', 'rgt')
            ->first();

        if (!$root) {
            return ['items' => [], 'total' => 0];
        }

        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.lft', '>', $root->lft)
            ->where('io.rgt', '<', $root->rgt)
            ->select(
                'io.id', 'io.identifier', 'io.lft', 'io.rgt',
                'io.parent_id', 'io.level_of_description_id',
                'ioi.title', 's.slug'
            );

        if (!$isAuthenticated) {
            $query->join('status as st', function ($j) {
                $j->on('io.id', '=', 'st.object_id')
                    ->where('st.type_id', '=', 158);
            })->where('st.status_id', 160); // PUBLICATION_STATUS_PUBLISHED_ID
        }

        self::applySortToQuery($query, $sort, $culture);

        $total = (clone $query)->count();

        $items = $query->offset($offset)
            ->limit($limit)
            ->get()
            ->map(function ($row) use ($root) {
                $node = self::formatNode($row);
                // Calculate depth relative to root
                $node['depth'] = 0; // Will be calculated client-side from parent chain

                return $node;
            })
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    // ────────────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────────────

    /**
     * Build query for children of a parent node.
     */
    private static function buildChildQuery(
        int $parentId,
        string $culture,
        bool $isAuthenticated,
        string $sort
    ) {
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.parent_id', $parentId)
            ->select(
                'io.id', 'io.identifier', 'io.lft', 'io.rgt',
                'io.parent_id', 'io.level_of_description_id',
                'ioi.title', 's.slug'
            );

        if (!$isAuthenticated) {
            $query->join('status as st', function ($j) {
                $j->on('io.id', '=', 'st.object_id')
                    ->where('st.type_id', '=', 158);
            })->where('st.status_id', 160);
        }

        self::applySortToQuery($query, $sort, $culture);

        return $query;
    }

    /**
     * Build query for siblings of a node.
     */
    private static function buildSiblingQuery(
        object $node,
        string $nodeTitle,
        string $position,
        string $culture,
        bool $isAuthenticated,
        string $sort
    ) {
        $query = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($j) use ($culture) {
                $j->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.parent_id', $node->parent_id)
            ->where('io.id', '!=', $node->id)
            ->select(
                'io.id', 'io.identifier', 'io.lft', 'io.rgt',
                'io.parent_id', 'io.level_of_description_id',
                'ioi.title', 's.slug'
            );

        if (!$isAuthenticated) {
            $query->join('status as st', function ($j) {
                $j->on('io.id', '=', 'st.object_id')
                    ->where('st.type_id', '=', 158);
            })->where('st.status_id', 160);
        }

        // Apply position filter and sort
        switch ($sort) {
            case 'title':
                $concat = $nodeTitle . str_pad((string) $node->lft, 12, '0', STR_PAD_LEFT);
                if ($position === 'next') {
                    $query->whereRaw(
                        "CONVERT(CONCAT(COALESCE(ioi.title, ''), LPAD(io.lft, 12, '0')), CHAR) > ?",
                        [$concat]
                    );
                    $query->orderBy('ioi.title')->orderBy('io.lft');
                } else {
                    $query->whereRaw(
                        "CONVERT(CONCAT(COALESCE(ioi.title, ''), LPAD(io.lft, 12, '0')), CHAR) < ?",
                        [$concat]
                    );
                    $query->orderByDesc('ioi.title')->orderByDesc('io.lft');
                }

                break;

            case 'identifierTitle':
                $concat = ($node->identifier ? str_pad($node->identifier, 12, '0', STR_PAD_RIGHT) : ' ')
                    . $nodeTitle
                    . str_pad((string) $node->lft, 12, '0', STR_PAD_LEFT);
                if ($position === 'next') {
                    $query->whereRaw(
                        "CONVERT(CONCAT(RPAD(COALESCE(io.identifier, ' '), 12, '0'), COALESCE(ioi.title, ''), LPAD(io.lft, 12, '0')), CHAR) > ?",
                        [$concat]
                    );
                    $query->orderBy('io.identifier')->orderBy('ioi.title')->orderBy('io.lft');
                } else {
                    $query->whereRaw(
                        "CONVERT(CONCAT(RPAD(COALESCE(io.identifier, ' '), 12, '0'), COALESCE(ioi.title, ''), LPAD(io.lft, 12, '0')), CHAR) < ?",
                        [$concat]
                    );
                    $query->orderByDesc('io.identifier')->orderByDesc('ioi.title')->orderByDesc('io.lft');
                }

                break;

            default: // 'none' — sort by lft
                if ($position === 'next') {
                    $query->where('io.lft', '>', $node->lft);
                    $query->orderBy('io.lft');
                } else {
                    $query->where('io.lft', '<', $node->lft);
                    $query->orderByDesc('io.lft');
                }
        }

        return $query;
    }

    /**
     * Apply sort criteria to a query.
     */
    private static function applySortToQuery($query, string $sort, string $culture): void
    {
        switch ($sort) {
            case 'title':
                $query->orderBy('ioi.title')->orderBy('io.lft');

                break;

            case 'identifierTitle':
                $query->orderBy('io.identifier')->orderBy('ioi.title')->orderBy('io.lft');

                break;

            default:
                $query->orderBy('io.lft');
        }
    }

    /**
     * Format a database row into a treeview node.
     */
    private static function formatNode(object $row): array
    {
        $hasChildren = ($row->rgt - $row->lft) > 1;

        return [
            'id' => (int) $row->id,
            'title' => $row->title ?? '',
            'slug' => $row->slug ?? '',
            'identifier' => $row->identifier ?? '',
            'levelOfDescriptionId' => $row->level_of_description_id ? (int) $row->level_of_description_id : null,
            'parentId' => isset($row->parent_id) ? (int) $row->parent_id : null,
            'lft' => (int) $row->lft,
            'rgt' => (int) $row->rgt,
            'hasChildren' => $hasChildren,
        ];
    }
}
