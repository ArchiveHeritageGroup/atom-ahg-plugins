<?php

/**
 * Access Filter Action - Integrates into ISAD detail view
 * Checks access before displaying content
 */
class sfIsadPluginAccessFilterAction extends sfAction
{
    public function execute($request)
    {
        // Get resource from route
        $this->resource = $this->getRoute()->resource;
        
        if (!isset($this->resource)) {
            $this->forward404();
        }

        // Get current user ID
        $userId = $this->getUser()->isAuthenticated() 
            ? $this->getUser()->getAttribute('user_id')
            : null;

        // Check access
        $service = \AtomExtensions\Services\Access\AccessFilterService::getInstance();
        $access = $service->checkAccess($this->resource->id, $userId);

        // Log access attempt
        $service->logAccess($this->resource->id, $userId, 'view', $access);

        // Store in request for templates
        $this->getRequest()->setAttribute('access_check', $access);

        // If completely denied, show access denied page
        if (!$access['granted'] && $access['level'] === 'denied') {
            $this->getUser()->setFlash('error', 'Access denied to this material.');
            $this->forward('accessFilter', 'denied');
        }

        // Otherwise continue with normal view
        return sfView::NONE;
    }
}
