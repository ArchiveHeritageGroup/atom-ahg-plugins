<?php

/**
 * EmbargoAccessFilter - Check embargo before allowing access to records
 * 
 * Usage in actions:
 *   if (!EmbargoAccessFilter::checkAccess($this->resource->id, $this)) {
 *       return; // Forward already handled
 *   }
 */

require_once dirname(__FILE__) . '/Services/EmbargoService.php';

class EmbargoAccessFilter
{
    /**
     * Check if user can access the record
     * Returns true if access allowed, false if blocked (and forwards to embargo page)
     */
    public static function checkAccess(int $objectId, sfAction $action): bool
    {
        $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $user = sfContext::getInstance()->getUser();
        
        // Check if user can access the record
        if ($service->canAccessRecord($objectId, $user)) {
            return true;
        }
        
        // Get embargo info for the blocked page
        $embargoInfo = $service->getEmbargoDisplayInfo($objectId);
        
        // Store embargo info in request for the error page
        $action->getRequest()->setAttribute('embargoInfo', $embargoInfo);
        $action->getRequest()->setAttribute('objectId', $objectId);
        
        // Forward to embargo blocked page
        $action->forward('extendedRights', 'embargoBlocked');
        
        return false;
    }
    
    /**
     * Check access and return embargo info if blocked
     * For templates that want to handle it themselves
     */
    public static function getBlockingEmbargo(int $objectId): ?array
    {
        $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $user = sfContext::getInstance()->getUser();
        
        if ($service->canAccessRecord($objectId, $user)) {
            return null;
        }
        
        return $service->getEmbargoDisplayInfo($objectId);
    }
}
