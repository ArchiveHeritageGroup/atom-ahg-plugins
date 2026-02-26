<?php

namespace AhgDiscovery\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Step 2C: Hierarchical Walk
 *
 * For top-scoring results, finds related records via the
 * information_object hierarchy (siblings, children, parents).
 */
class HierarchicalStrategy
{
    /**
     * Find hierarchically related records for the top results.
     *
     * @param array $topResults     Top results from 2A + 2B [{object_id, ...}, ...]
     * @param array $alreadyFound   All object_ids already in results
     * @param int   $topN           How many top results to walk
     * @return array [{object_id, relationship_type, via_object_id}, ...]
     */
    public function search(array $topResults, array $alreadyFound = [], int $topN = 20): array
    {
        $results = [];
        $processed = array_flip($alreadyFound);

        // Only walk the top N results
        $toWalk = array_slice($topResults, 0, $topN);
        if (empty($toWalk)) {
            return [];
        }

        $walkIds = array_column($toWalk, 'object_id');

        try {
            // Batch fetch hierarchy info for all walk candidates
            $nodes = DB::table('information_object')
                ->select('id', 'parent_id', 'level_of_description_id', 'lft', 'rgt')
                ->whereIn('id', $walkIds)
                ->get()
                ->keyBy('id');

            foreach ($nodes as $node) {
                $objectId = (int)$node->id;
                $parentId = (int)$node->parent_id;

                // Skip root node children
                if ($parentId <= 1) {
                    continue;
                }

                // Find siblings (same parent, not already found)
                $siblings = DB::table('information_object')
                    ->select('id')
                    ->where('parent_id', $parentId)
                    ->where('id', '!=', $objectId)
                    ->whereNotIn('id', $alreadyFound)
                    ->limit(5)
                    ->pluck('id')
                    ->toArray();

                foreach ($siblings as $sibId) {
                    $sibId = (int)$sibId;
                    if (!isset($processed[$sibId])) {
                        $results[] = [
                            'object_id'         => $sibId,
                            'relationship_type' => 'sibling',
                            'via_object_id'     => $objectId,
                        ];
                        $processed[$sibId] = true;
                    }
                }

                // If result is at series/fonds level, find children
                $levelId = (int)$node->level_of_description_id;
                if ($this->isHighLevel($levelId)) {
                    $children = DB::table('information_object')
                        ->select('id')
                        ->where('parent_id', $objectId)
                        ->whereNotIn('id', $alreadyFound)
                        ->limit(10)
                        ->pluck('id')
                        ->toArray();

                    foreach ($children as $childId) {
                        $childId = (int)$childId;
                        if (!isset($processed[$childId])) {
                            $results[] = [
                                'object_id'         => $childId,
                                'relationship_type' => 'child',
                                'via_object_id'     => $objectId,
                            ];
                            $processed[$childId] = true;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('[ahgDiscovery] Hierarchical search failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Check if a level_of_description_id represents a high level (fonds, series, sub-fonds).
     */
    private function isHighLevel(int $levelId): bool
    {
        // QubitTerm IDs for high-level descriptions
        // fonds=227, sub-fonds=228, series=231, collection=229
        static $highLevels = null;

        if ($highLevels === null) {
            try {
                $highLevels = DB::table('term')
                    ->join('term_i18n', 'term.id', '=', 'term_i18n.id')
                    ->where('term.taxonomy_id', \QubitTaxonomy::LEVEL_OF_DESCRIPTION_ID)
                    ->whereIn('term_i18n.name', ['Fonds', 'Sub-fonds', 'Series', 'Collection', 'Sub-series'])
                    ->pluck('term.id')
                    ->toArray();
            } catch (\Exception $e) {
                $highLevels = [227, 228, 229, 231]; // Fallback IDs
            }
        }

        return in_array($levelId, $highLevels);
    }

    /**
     * Walk up the hierarchy to find the root fonds/collection for a record.
     *
     * @param int $objectId
     * @return array|null {id, title, slug} of the root fonds
     */
    public static function findRootFonds(int $objectId): ?array
    {
        static $cache = [];

        if (isset($cache[$objectId])) {
            return $cache[$objectId];
        }

        try {
            $current = $objectId;
            $maxDepth = 20; // prevent infinite loops

            while ($maxDepth-- > 0) {
                $node = DB::table('information_object')
                    ->select('id', 'parent_id')
                    ->where('id', $current)
                    ->first();

                if (!$node || (int)$node->parent_id <= 1) {
                    // This is the root (or its parent is ROOT_ID)
                    $title = DB::table('information_object_i18n')
                        ->where('id', $current)
                        ->where('culture', 'en')
                        ->value('title');

                    $slug = DB::table('slug')
                        ->where('object_id', $current)
                        ->value('slug');

                    $result = [
                        'id'    => (int)$current,
                        'title' => $title ?: 'Untitled',
                        'slug'  => $slug ?: '',
                    ];

                    $cache[$objectId] = $result;
                    return $result;
                }

                $current = (int)$node->parent_id;
            }
        } catch (\Exception $e) {
            error_log('[ahgDiscovery] findRootFonds failed: ' . $e->getMessage());
        }

        return null;
    }
}
