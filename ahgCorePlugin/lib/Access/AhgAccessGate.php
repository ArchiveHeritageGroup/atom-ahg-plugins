<?php

namespace AhgCore\Access;

/**
 * AhgAccessGate - Centralized access control for sector plugins
 *
 * Provides a unified interface for checking record access, including embargo status.
 * Decouples sector plugins from directly requiring ahgExtendedRightsPlugin.
 *
 * Usage:
 *   if (!AhgAccessGate::canView($objectId, $action)) {
 *       return sfView::NONE;
 *   }
 */
class AhgAccessGate
{
    private static bool $initialized = false;
    private static bool $embargoServiceAvailable = false;

    /**
     * Initialize the access gate
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Check if EmbargoService is available
        $servicePath = \sfConfig::get('sf_plugins_dir')
            . '/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php';

        if (file_exists($servicePath)) {
            require_once $servicePath;
            self::$embargoServiceAvailable = class_exists('ahgExtendedRightsPlugin\\Services\\EmbargoService');
        }

        self::$initialized = true;
    }

    /**
     * Check if user can view the record
     *
     * @param int $objectId The information object ID
     * @param \sfAction|null $action Optional action for forwarding on block
     * @return bool True if access allowed, false if blocked
     */
    public static function canView(int $objectId, ?\sfAction $action = null): bool
    {
        self::init();

        // If embargo service not available, allow access
        if (!self::$embargoServiceAvailable) {
            return true;
        }

        try {
            $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
            $user = \sfContext::getInstance()->getUser();

            // Check if user can access the record
            if ($service->canAccessRecord($objectId, $user)) {
                return true;
            }

            // If action provided, forward to embargo blocked page
            if ($action !== null) {
                $embargoInfo = $service->getEmbargoDisplayInfo($objectId);
                $action->getRequest()->setAttribute('embargoInfo', $embargoInfo);
                $action->getRequest()->setAttribute('objectId', $objectId);
                $action->forward('extendedRights', 'embargoBlocked');
            }

            return false;
        } catch (\sfStopException $e) {
            // The forward above ends in `throw new sfStopException()` - that is how
            // sfAction::forward halts the action it interrupts. Catching it here
            // swallowed the stop, canView() returned true, and the calling action
            // carried on rendering the record it had just blocked. The embargo was
            // defeated by its own error handler.
            //
            // sfStopException extends sfException extends Exception, so the broad
            // catch below took it silently. It has to travel.
            throw $e;
        } catch (\Exception $e) {
            // Fail CLOSED.
            //
            // This returned true, so any fault inside canAccessRecord() or
            // getEmbargoDisplayInfo() - a missing table, a bad row - made an
            // embargoed record readable. An embargo we cannot evaluate is not an
            // embargo that has lapsed, and the four callers are the museum,
            // library, DAM and gallery record views, where the cost of being wrong
            // is disclosure rather than inconvenience.
            //
            // A record wrongly withheld is visible and complained about. A record
            // wrongly disclosed is neither.
            error_log('AhgAccessGate: embargo check failed, denying access: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Get blocking embargo info if record is embargoed
     *
     * @param int $objectId The information object ID
     * @return array|null Embargo info if blocked, null if accessible
     */
    public static function getBlockingEmbargo(int $objectId): ?array
    {
        self::init();

        if (!self::$embargoServiceAvailable) {
            return null;
        }

        try {
            $service = new \ahgExtendedRightsPlugin\Services\EmbargoService();
            $user = \sfContext::getInstance()->getUser();

            if ($service->canAccessRecord($objectId, $user)) {
                return null;
            }

            return $service->getEmbargoDisplayInfo($objectId);
        } catch (\Exception $e) {
            error_log('AhgAccessGate: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if embargo service is available
     */
    public static function isEmbargoServiceAvailable(): bool
    {
        self::init();
        return self::$embargoServiceAvailable;
    }
}
