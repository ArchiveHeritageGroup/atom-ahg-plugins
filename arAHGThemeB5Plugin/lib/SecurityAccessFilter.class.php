<?php

/**
 * Security Access Filter for Elasticsearch
 * Filters search results AND aggregations based on user security clearance
 */
class SecurityAccessFilter
{
    private static $restrictedIds = null;
    private static $userId = null;

    /**
     * Get restricted IDs (cached for request)
     */
    public static function getRestrictedIds($userId = null)
    {
        if (self::$restrictedIds === null || self::$userId !== $userId) {
            self::$userId = $userId;
            $filterService = \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance();
            self::$restrictedIds = $filterService->getRestrictedObjectIds($userId);
        }
        return self::$restrictedIds;
    }

    /**
     * Filter by access - called directly from browseAction
     * Uses addFilter to ensure aggregations are also filtered
     * 
     * @param \Elastica\Query\BoolQuery $queryBool The bool query to modify
     * @param int|null $userId Current user ID
     * @return void
     */
    public static function filterByAccess($queryBool, $userId = null)
    {
        if ($userId === null) {
            $userId = sfContext::getInstance()->getUser()->isAuthenticated()
                ? sfContext::getInstance()->getUser()->getAttribute('user_id')
                : null;
        }

        $restrictedIds = self::getRestrictedIds($userId);

        error_log("SECURITY FILTER: User {$userId}, restricted count = " . count($restrictedIds));

        if (!empty($restrictedIds)) {
            $restrictedIdStrings = array_map('strval', $restrictedIds);
            
            error_log("SECURITY FILTER: Excluding IDs: " . implode(',', $restrictedIdStrings));
            
            // Use IdsQuery to filter by document _id
            $idsQuery = new \Elastica\Query\Ids();
            $idsQuery->setIds($restrictedIdStrings);
            
            // Add as filter context MustNot (affects aggregations)
            $queryBool->addMustNot($idsQuery);
        }
    }

    /**
     * Apply security filter to the full search object
     * This ensures filter is in the main query context, affecting aggregations
     * 
     * @param arElasticSearchPluginQuery $search The search object
     * @param int|null $userId Current user ID
     * @return void
     */
    public static function applyToSearch($search, $userId = null)
    {
        if ($userId === null) {
            $userId = sfContext::getInstance()->getUser()->isAuthenticated()
                ? sfContext::getInstance()->getUser()->getAttribute('user_id')
                : null;
        }

        $restrictedIds = self::getRestrictedIds($userId);

        error_log("SECURITY FILTER [applyToSearch]: User {$userId}, restricted count = " . count($restrictedIds));

        if (!empty($restrictedIds)) {
            $restrictedIdStrings = array_map('strval', $restrictedIds);
            
            // Use IdsQuery to filter by document _id
            $idsQuery = new \Elastica\Query\Ids();
            $idsQuery->setIds($restrictedIdStrings);
            
            // Add to queryBool as MustNot
            $search->queryBool->addMustNot($idsQuery);
            
            // Also add to filters array if it exists (for aggregation context)
            if (property_exists($search, 'filters')) {
                $search->filters['securityAccess'] = $idsQuery;
            }
        }
    }
}
