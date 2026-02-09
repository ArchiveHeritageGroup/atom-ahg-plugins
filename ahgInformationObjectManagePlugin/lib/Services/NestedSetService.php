<?php

namespace AhgInformationObjectManage\Services;

use Illuminate\Database\Capsule\Manager as DB;

class NestedSetService
{
    /**
     * Insert a new node under a parent.
     *
     * Opens a gap of 2 at parent's rgt, then sets the new node's lft/rgt
     * so it becomes the last child of the parent.
     */
    public static function insertUnder(int $parentId, int $newId): void
    {
        $parent = DB::table('information_object')
            ->where('id', $parentId)
            ->select('rgt')
            ->first();

        if (!$parent) {
            throw new \RuntimeException("Parent information object #{$parentId} not found.");
        }

        $parentRgt = $parent->rgt;

        // Open gap: shift all nodes with lft >= parentRgt by +2
        DB::table('information_object')
            ->where('lft', '>=', $parentRgt)
            ->increment('lft', 2);

        DB::table('information_object')
            ->where('rgt', '>=', $parentRgt)
            ->increment('rgt', 2);

        // Set new node's lft/rgt
        DB::table('information_object')
            ->where('id', $newId)
            ->update([
                'lft' => $parentRgt,
                'rgt' => $parentRgt + 1,
            ]);
    }

    /**
     * Remove a LEAF node from the tree and close the gap.
     *
     * Only works for leaf nodes (rgt - lft == 1). For nodes with children,
     * delete children first.
     */
    public static function removeNode(int $id): void
    {
        $node = DB::table('information_object')
            ->where('id', $id)
            ->select('lft', 'rgt')
            ->first();

        if (!$node) {
            return;
        }

        $width = $node->rgt - $node->lft + 1;

        // Close gap: shift nodes after this node
        DB::table('information_object')
            ->where('lft', '>', $node->rgt)
            ->decrement('lft', $width);

        DB::table('information_object')
            ->where('rgt', '>', $node->rgt)
            ->decrement('rgt', $width);
    }

    /**
     * Check if a node has children (is not a leaf).
     */
    public static function hasChildren(int $id): bool
    {
        return DB::table('information_object')
            ->where('parent_id', $id)
            ->exists();
    }
}
