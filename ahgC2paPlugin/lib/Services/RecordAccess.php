<?php
/**
 * PSIS / AtoM-AHG - access gates for the public content-credentials pages.
 *
 * Heratio gates its download path through ODRL; on PSIS the equivalent is the
 * AtoM ACL. A verification page must not reveal a draft record, and the
 * download-with-credentials path streams the MASTER, so it must apply the same
 * rule as digitalobject/view (readMaster, URL-accessible master, the text-master
 * exception, conditional copyright). Every check fails closed.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use Throwable;

final class RecordAccess
{
    /**
     * May the current user see this description (published, or permitted)?
     */
    public static function canRead(int $informationObjectId): bool
    {
        try {
            $resource = \QubitInformationObject::getById($informationObjectId);

            return $resource !== null && \QubitAcl::check($resource, 'read');
        } catch (Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__) . ':' . __LINE__);

            return false;
        }
    }

    /**
     * May the current user download this digital object's master file?
     */
    public static function canReadMaster(int $digitalObjectId): bool
    {
        try {
            $do = \QubitDigitalObject::getById($digitalObjectId);
            if ($do === null || $do->parentId !== null) {
                return false;
            }
            $resource = $do->object;
            if (!$resource instanceof \QubitInformationObject) {
                return false;
            }
            if (!$do->masterAccessibleViaUrl() || !\QubitAcl::check($resource, 'readMaster')) {
                return false;
            }
            // Conditional copyright needs the base pop-up acknowledgement.
            if ($do->hasConditionalCopyright()) {
                return false;
            }
            // Same text-master guard as ahgCorePlugin digitalobject/view.
            if (\QubitTerm::TEXT_ID == $do->mediaTypeId) {
                $allow = false;
                try {
                    $allow = \AtomExtensions\Services\AhgSettingsService::getBool('allow_public_text_masters', false);
                } catch (Throwable) {
                    $allow = false;
                }
                $user = \sfContext::getInstance()->getUser();
                if (!$allow && (!$user->isAuthenticated() || !\QubitGrantedRight::checkPremis($resource->id, 'readMaster'))) {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__) . ':' . __LINE__);

            return false;
        }
    }
}
