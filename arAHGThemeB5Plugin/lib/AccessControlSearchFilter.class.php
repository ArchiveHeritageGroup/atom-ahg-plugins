<?php

class AccessControlSearchFilter
{
    public static function filterQuery(sfEvent $event)
    {
        $query = $event->getSubject();
        
        $userId = sfContext::getInstance()->getUser()->isAuthenticated()
            ? sfContext::getInstance()->getUser()->getAttribute('user_id')
            : null;

        $filterService = \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance();
        $restrictedIds = $filterService->getRestrictedObjectIds($userId);

        if (!empty($restrictedIds)) {
            $query->queryBool->addMustNot(new \Elastica\Query\Terms('id', $restrictedIds));
        }

        return true;
    }
}
