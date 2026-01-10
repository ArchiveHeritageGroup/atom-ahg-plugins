<?php

/**
 * EmbargoHelper - Static helper for embargo access checks in templates
 * 
 * Usage in templates:
 *   if (EmbargoHelper::canAccess($resource->id)) { ... }
 *   if (EmbargoHelper::canViewThumbnail($resource->id)) { ... }
 */

// Require the service class
require_once dirname(__FILE__) . '/Services/EmbargoService.php';

class EmbargoHelper
{
    private static ?\ahgExtendedRightsPlugin\Services\EmbargoService $service = null;

    private static function getService(): \ahgExtendedRightsPlugin\Services\EmbargoService
    {
        if (self::$service === null) {
            self::$service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        }
        return self::$service;
    }

    private static function getUser(): ?object
    {
        if (class_exists('sfContext') && sfContext::hasInstance()) {
            return sfContext::getInstance()->getUser();
        }
        return null;
    }

    /**
     * Check if record can be accessed (not under full embargo)
     */
    public static function canAccess(int $objectId): bool
    {
        return self::getService()->canAccessRecord($objectId, self::getUser());
    }

    /**
     * Check if metadata can be viewed
     */
    public static function canViewMetadata(int $objectId): bool
    {
        return self::getService()->canViewMetadata($objectId, self::getUser());
    }

    /**
     * Check if thumbnail can be viewed
     */
    public static function canViewThumbnail(int $objectId): bool
    {
        return self::getService()->canViewThumbnail($objectId, self::getUser());
    }

    /**
     * Check if full digital object can be viewed
     */
    public static function canViewDigitalObject(int $objectId): bool
    {
        return self::getService()->canViewDigitalObject($objectId, self::getUser());
    }

    /**
     * Check if download is allowed
     */
    public static function canDownload(int $objectId): bool
    {
        return self::getService()->canDownload($objectId, self::getUser());
    }

    /**
     * Get active embargo for object (or null)
     */
    public static function getActiveEmbargo(int $objectId): ?object
    {
        return self::getService()->getActiveEmbargo($objectId);
    }

    /**
     * Get display info for embargo message
     */
    public static function getDisplayInfo(int $objectId): ?array
    {
        return self::getService()->getEmbargoDisplayInfo($objectId);
    }

    /**
     * Check if object has any active embargo
     */
    public static function isEmbargoed(int $objectId): bool
    {
        return self::getService()->isEmbargoed($objectId);
    }

    /**
     * Filter array of IDs to only those accessible
     */
    public static function filterAccessible(array $objectIds): array
    {
        return self::getService()->filterAccessibleIds($objectIds, self::getUser());
    }
}
