<?php

namespace AhgDiscovery\Services;

/**
 * Step 3: Merge & Rank
 *
 * Combines results from all search strategies into a single
 * scored, deduplicated, and grouped result set.
 */
class ResultMerger
{
    /**
     * Strategy weights for final scoring.
     */
    private const WEIGHT_KEYWORD      = 0.35;
    private const WEIGHT_ENTITY       = 0.40;
    private const WEIGHT_HIERARCHICAL = 0.25;

    /**
     * Fixed scores for hierarchical relationships.
     */
    private const SCORE_SIBLING = 0.5;
    private const SCORE_CHILD   = 0.3;

    /**
     * Merge results from all strategies into grouped, ranked output.
     *
     * @param array $keywordResults      From KeywordSearchStrategy
     * @param array $entityResults       From EntitySearchStrategy
     * @param array $hierarchicalResults From HierarchicalStrategy
     * @return array GroupedResults structure
     */
    public function merge(array $keywordResults, array $entityResults, array $hierarchicalResults): array
    {
        // Step 1: Build unified map
        $map = $this->buildResultMap($keywordResults, $entityResults, $hierarchicalResults);

        if (empty($map)) {
            return [
                'total_results' => 0,
                'collections'   => [],
                'flat_results'  => [],
            ];
        }

        // Step 2: Calculate final scores
        $scored = $this->calculateScores($map, $keywordResults, $entityResults);

        // Step 3: Sort by score
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // Step 4: Group by root fonds
        $grouped = $this->groupByFonds($scored);

        return [
            'total_results' => count($scored),
            'collections'   => $grouped,
            'flat_results'  => $scored,
        ];
    }

    /**
     * Build a map of object_id → {sources, reasons, data}.
     */
    private function buildResultMap(array $keyword, array $entity, array $hierarchical): array
    {
        $map = [];

        // Keyword results
        foreach ($keyword as $r) {
            $id = $r['object_id'];
            if (!isset($map[$id])) {
                $map[$id] = ['sources' => [], 'reasons' => [], 'data' => []];
            }
            $map[$id]['sources']['keyword'] = $r;
            $map[$id]['reasons'][] = 'KEYWORD';
            if (!empty($r['highlights'])) {
                $map[$id]['data']['highlights'] = $r['highlights'];
            }
            if (!empty($r['slug'])) {
                $map[$id]['data']['slug'] = $r['slug'];
            }
        }

        // Entity results
        foreach ($entity as $r) {
            $id = $r['object_id'];
            if (!isset($map[$id])) {
                $map[$id] = ['sources' => [], 'reasons' => [], 'data' => []];
            }
            $map[$id]['sources']['entity'] = $r;

            // Add specific entity match reasons
            if (!empty($r['matched_values'])) {
                foreach (array_slice($r['matched_values'], 0, 3) as $val) {
                    $reason = 'ENTITY:' . $val;
                    if (!in_array($reason, $map[$id]['reasons'])) {
                        $map[$id]['reasons'][] = $reason;
                    }
                }
            } else {
                if (!in_array('ENTITY', $map[$id]['reasons'])) {
                    $map[$id]['reasons'][] = 'ENTITY';
                }
            }
        }

        // Hierarchical results
        foreach ($hierarchical as $r) {
            $id = $r['object_id'];
            if (!isset($map[$id])) {
                $map[$id] = ['sources' => [], 'reasons' => [], 'data' => []];
            }
            $map[$id]['sources']['hierarchical'] = $r;

            $type = strtoupper($r['relationship_type'] ?? 'RELATED');
            $map[$id]['reasons'][] = $type;
        }

        return $map;
    }

    /**
     * Calculate weighted final scores for each result.
     */
    private function calculateScores(array $map, array $keywordResults, array $entityResults): array
    {
        // Find max values for normalization
        $maxEsScore = 0;
        foreach ($keywordResults as $r) {
            if ($r['es_score'] > $maxEsScore) {
                $maxEsScore = $r['es_score'];
            }
        }

        $maxEntityCount = 0;
        foreach ($entityResults as $r) {
            if ($r['match_count'] > $maxEntityCount) {
                $maxEntityCount = $r['match_count'];
            }
        }

        $scored = [];

        foreach ($map as $objectId => $entry) {
            // Normalize keyword score (0-1)
            $keywordNorm = 0;
            if (isset($entry['sources']['keyword']) && $maxEsScore > 0) {
                $keywordNorm = $entry['sources']['keyword']['es_score'] / $maxEsScore;
            }

            // Normalize entity score (0-1)
            $entityNorm = 0;
            if (isset($entry['sources']['entity']) && $maxEntityCount > 0) {
                $entityNorm = $entry['sources']['entity']['match_count'] / $maxEntityCount;
            }

            // Hierarchical score (fixed values)
            $hierarchyNorm = 0;
            if (isset($entry['sources']['hierarchical'])) {
                $relType = $entry['sources']['hierarchical']['relationship_type'] ?? '';
                $hierarchyNorm = ($relType === 'sibling') ? self::SCORE_SIBLING : self::SCORE_CHILD;
            }

            // Weighted final score
            $finalScore = ($keywordNorm * self::WEIGHT_KEYWORD)
                        + ($entityNorm * self::WEIGHT_ENTITY)
                        + ($hierarchyNorm * self::WEIGHT_HIERARCHICAL);

            // Bonus: records found by multiple strategies get a boost
            $sourceCount = count($entry['sources']);
            if ($sourceCount > 1) {
                $finalScore *= (1 + ($sourceCount - 1) * 0.1);
            }

            // Deduplicate reasons
            $reasons = array_values(array_unique($entry['reasons']));

            $scored[] = [
                'object_id'     => (int)$objectId,
                'score'         => round($finalScore, 4),
                'match_reasons' => $reasons,
                'highlights'    => $entry['data']['highlights'] ?? [],
                'slug'          => $entry['data']['slug'] ?? null,
            ];
        }

        return $scored;
    }

    /**
     * Group scored results by their root fonds/collection.
     */
    private function groupByFonds(array $scored): array
    {
        $groups = [];

        foreach ($scored as $result) {
            $fonds = HierarchicalStrategy::findRootFonds($result['object_id']);
            $fondsKey = $fonds ? $fonds['id'] : 0;

            if (!isset($groups[$fondsKey])) {
                $groups[$fondsKey] = [
                    'fonds_id'    => $fonds['id'] ?? 0,
                    'fonds_title' => $fonds['title'] ?? 'Ungrouped',
                    'fonds_slug'  => $fonds['slug'] ?? '',
                    'match_count' => 0,
                    'max_score'   => 0,
                    'records'     => [],
                ];
            }

            $groups[$fondsKey]['records'][] = $result;
            $groups[$fondsKey]['match_count']++;
            if ($result['score'] > $groups[$fondsKey]['max_score']) {
                $groups[$fondsKey]['max_score'] = $result['score'];
            }
        }

        // Sort groups by their best match score
        usort($groups, fn($a, $b) => $b['max_score'] <=> $a['max_score']);

        // Sort records within each group by score
        foreach ($groups as &$group) {
            usort($group['records'], fn($a, $b) => $b['score'] <=> $a['score']);
        }

        return array_values($groups);
    }
}
