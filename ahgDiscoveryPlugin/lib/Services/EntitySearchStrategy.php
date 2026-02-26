<?php

namespace AhgDiscovery\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Step 2B: NER Entity Match
 *
 * Searches the ahg_ner_entity table for records that contain
 * entities matching the expanded query terms.
 */
class EntitySearchStrategy
{
    /**
     * Search for records with matching NER entities.
     *
     * @param array $expanded ExpandedQuery from QueryExpander
     * @param int   $limit    Max results to return
     * @return array [{object_id, match_count, entity_types, matched_values}, ...]
     */
    public function search(array $expanded, int $limit = 200): array
    {
        $searchTerms = $this->buildSearchTerms($expanded);

        if (empty($searchTerms)) {
            return [];
        }

        try {
            // Check table exists
            $exists = DB::select("SHOW TABLES LIKE 'ahg_ner_entity'");
            if (empty($exists)) {
                return [];
            }

            $query = DB::table('ahg_ner_entity')
                ->select(
                    'object_id',
                    DB::raw('COUNT(*) as match_count'),
                    DB::raw("GROUP_CONCAT(DISTINCT entity_type SEPARATOR ',') as entity_types"),
                    DB::raw("GROUP_CONCAT(DISTINCT entity_value SEPARATOR '||') as matched_values")
                )
                ->where(function ($q) use ($searchTerms) {
                    foreach ($searchTerms as $term) {
                        $q->orWhere('entity_value', 'LIKE', '%' . $term . '%');
                    }
                })
                ->whereIn('status', ['approved', 'pending'])
                ->groupBy('object_id')
                ->orderByDesc('match_count')
                ->limit($limit)
                ->get();

            return $query->map(function ($row) {
                return [
                    'object_id'      => (int)$row->object_id,
                    'match_count'    => (int)$row->match_count,
                    'entity_types'   => $row->entity_types,
                    'matched_values' => explode('||', $row->matched_values),
                ];
            })->toArray();
        } catch (\Exception $e) {
            error_log('[ahgDiscovery] Entity search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find records sharing entities with a specific record.
     * Used for the "Related Content" sidebar.
     *
     * @param int $objectId  The record to find related content for
     * @param int $limit     Max results
     * @return array [{object_id, match_count, shared_entities}, ...]
     */
    public function findRelated(int $objectId, int $limit = 10): array
    {
        try {
            $exists = DB::select("SHOW TABLES LIKE 'ahg_ner_entity'");
            if (empty($exists)) {
                return [];
            }

            // Get entities for this record
            $entities = DB::table('ahg_ner_entity')
                ->where('object_id', $objectId)
                ->whereIn('status', ['approved', 'pending'])
                ->pluck('entity_value')
                ->toArray();

            if (empty($entities)) {
                return [];
            }

            // Find other records with overlapping entities
            $query = DB::table('ahg_ner_entity')
                ->select(
                    'object_id',
                    DB::raw('COUNT(*) as match_count'),
                    DB::raw("GROUP_CONCAT(DISTINCT entity_value SEPARATOR '||') as shared_entities")
                )
                ->where('object_id', '!=', $objectId)
                ->where(function ($q) use ($entities) {
                    foreach (array_slice($entities, 0, 20) as $entity) {
                        $q->orWhere('entity_value', $entity);
                    }
                })
                ->whereIn('status', ['approved', 'pending'])
                ->groupBy('object_id')
                ->orderByDesc('match_count')
                ->limit($limit)
                ->get();

            return $query->map(function ($row) {
                return [
                    'object_id'       => (int)$row->object_id,
                    'match_count'     => (int)$row->match_count,
                    'shared_entities' => explode('||', $row->shared_entities),
                ];
            })->toArray();
        } catch (\Exception $e) {
            error_log('[ahgDiscovery] Related entity search failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Build the list of terms to search for in ahg_ner_entity.
     */
    private function buildSearchTerms(array $expanded): array
    {
        $terms = [];

        // Entity terms from query (proper nouns, detected phrases)
        if (!empty($expanded['entityTerms'])) {
            foreach ($expanded['entityTerms'] as $entity) {
                $terms[] = $entity['value'];
            }
        }

        // Phrases (quoted or multi-word)
        if (!empty($expanded['phrases'])) {
            foreach ($expanded['phrases'] as $phrase) {
                if (!in_array($phrase, $terms)) {
                    $terms[] = $phrase;
                }
            }
        }

        // Keywords (single words, useful for matching person/org names)
        if (!empty($expanded['keywords'])) {
            foreach ($expanded['keywords'] as $keyword) {
                // Only include keywords that look like they could be entity names
                // (capitalized in original, or longer than 4 chars)
                if (strlen($keyword) > 4 && !in_array($keyword, $terms)) {
                    $terms[] = $keyword;
                }
            }
        }

        // Synonyms that might match entities
        if (!empty($expanded['synonyms'])) {
            foreach ($expanded['synonyms'] as $synonym) {
                if (!in_array($synonym, $terms)) {
                    $terms[] = $synonym;
                }
            }
        }

        return array_values(array_unique($terms));
    }
}
