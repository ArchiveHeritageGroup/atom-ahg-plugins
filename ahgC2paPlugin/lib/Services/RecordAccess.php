<?php
/**
 * PSIS / AtoM-AHG - access gates for the public content-credentials pages.
 *
 * Heratio gates its download path through ODRL; on PSIS the equivalent is the
 * AtoM ACL plus the framework access filter. A public route must not reveal a
 * draft, embargoed, classified, donor-restricted, ICIP or ODRL-restricted record,
 * and the download-with-credentials path streams the MASTER, so it must apply the
 * same rule as digitalobject/view (readMaster, URL-accessible master, the
 * text-master exception, conditional copyright). Every check fails closed.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use AtomExtensions\Services\Search\SearchAccessFilterService;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

final class RecordAccess
{
    private const PUBLICATION_STATUS_TYPE_ID = 158;
    private const PUBLISHED_ID = 160;

    /**
     * May the current user see this description? It must pass the ACL, be
     * published (or the user may view drafts), and not be in the list filter,
     * which unlike isRestricted() includes ICIP and ODRL.
     */
    public static function canRead(int $informationObjectId): bool
    {
        try {
            $resource = \QubitInformationObject::getById($informationObjectId);

            if ($resource === null || !\QubitAcl::check($resource, 'read')) {
                return false;
            }

            $published = DB::table('status')
                ->where('object_id', $informationObjectId)
                ->where('type_id', self::PUBLICATION_STATUS_TYPE_ID)
                ->where('status_id', self::PUBLISHED_ID)
                ->exists();
            if (!$published && !\QubitAcl::check($resource, 'viewDraft')) {
                return false;
            }

            $user = \sfContext::getInstance()->getUser();
            $userId = $user->isAuthenticated() ? (int) $user->getAttribute('user_id') : 0;
            $hidden = SearchAccessFilterService::getInstance()->getRestrictedObjectIds($userId ?: null);

            return !in_array($informationObjectId, array_map('intval', $hidden), true);
        } catch (Throwable $e) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__) . ':' . __LINE__);

            return false;
        }
    }

    /**
     * A manifest row names a record and a file. When the file belongs to another
     * record, that record must be readable too. Call after canRead($ioId).
     */
    public static function canReadManifestFile(object $manifest, int $ioId): bool
    {
        $doId = (int) ($manifest->digital_object_id ?? 0);
        if ($doId <= 0) {
            return true;
        }

        try {
            $do = DB::table('digital_object')->where('id', $doId)->first(['object_id', 'parent_id']);
            if ($do === null) {
                return true;
            }
            $owner = $do->object_id ?? ($do->parent_id === null ? null : DB::table('digital_object')->where('id', $do->parent_id)->value('object_id'));

            return $owner === null || (int) $owner === $ioId || self::canRead((int) $owner);
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
            if (!self::canRead((int) $resource->id)) {
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
