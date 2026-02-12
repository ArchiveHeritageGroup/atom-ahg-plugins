<?php

/**
 * DigitalObjectEmbargoFilter - Static filter for download blocking.
 *
 * Checks if a digital object download is allowed based on embargo rules.
 * Used by templates and download components to enforce embargo restrictions.
 *
 * Usage in templates:
 *   $result = DigitalObjectEmbargoFilter::canDownload($digitalObjectId);
 *   if (!$result['allowed']) {
 *       include_partial('ahgExtendedRightsPlugin/downloadBlocked', ['result' => $result]);
 *   }
 */

// Require EmbargoService
require_once dirname(__FILE__) . '/Services/EmbargoService.php';

class DigitalObjectEmbargoFilter
{
    /**
     * Check if download is allowed for a digital object.
     *
     * @param int         $digitalObjectId Digital object ID
     * @param object|null $user            Optional user object
     *
     * @return array [
     *   'allowed' => bool,
     *   'reason' => string|null,
     *   'embargo_info' => array|null,
     *   'can_request_access' => bool
     * ]
     */
    public static function canDownload(int $digitalObjectId, ?object $user = null): array
    {
        // Get information object ID for this digital object
        $objectId = self::getInformationObjectId($digitalObjectId);

        if (!$objectId) {
            // No linked information object - allow download
            return [
                'allowed' => true,
                'reason' => null,
                'embargo_info' => null,
                'can_request_access' => false,
            ];
        }

        return self::canDownloadByObjectId($objectId, $user);
    }

    /**
     * Check if download is allowed for an information object.
     *
     * @param int         $objectId Information object ID
     * @param object|null $user     Optional user object
     *
     * @return array
     */
    public static function canDownloadByObjectId(int $objectId, ?object $user = null): array
    {
        // Get user from context if not provided
        if ($user === null && class_exists('sfContext') && sfContext::hasInstance()) {
            $user = sfContext::getInstance()->getUser();
        }

        // 1. Check if user is admin - admins bypass embargo
        if (self::isAdminUser($user)) {
            return [
                'allowed' => true,
                'reason' => null,
                'embargo_info' => null,
                'can_request_access' => false,
            ];
        }

        // 2. Check if user has update permission on the object
        if (self::userCanUpdate($objectId, $user)) {
            return [
                'allowed' => true,
                'reason' => null,
                'embargo_info' => null,
                'can_request_access' => false,
            ];
        }

        // 3. Check for active embargo
        $embargoService = new \ahgExtendedRightsPlugin\Services\EmbargoService();
        $embargo = $embargoService->getActiveEmbargo($objectId);

        if (!$embargo) {
            // No embargo - allow download
            return [
                'allowed' => true,
                'reason' => null,
                'embargo_info' => null,
                'can_request_access' => false,
            ];
        }

        // 4. Check embargo exceptions for this user
        if (self::hasEmbargoException($embargo->id, $user)) {
            return [
                'allowed' => true,
                'reason' => 'User has exception grant',
                'embargo_info' => self::formatEmbargoInfo($embargo),
                'can_request_access' => false,
            ];
        }

        // 5. Embargo is active and user has no exception - block download
        $embargoInfo = self::formatEmbargoInfo($embargo);

        return [
            'allowed' => false,
            'reason' => self::getBlockedReason($embargo),
            'embargo_info' => $embargoInfo,
            'can_request_access' => self::canUserRequestAccess($user),
        ];
    }

    /**
     * Check if current user can view digital object (master/reference).
     *
     * @param int $objectId Information object ID
     *
     * @return bool
     */
    public static function canViewDigitalObject(int $objectId): bool
    {
        $user = null;
        if (class_exists('sfContext') && sfContext::hasInstance()) {
            $user = sfContext::getInstance()->getUser();
        }

        $result = self::canDownloadByObjectId($objectId, $user);

        return $result['allowed'];
    }

    /**
     * Get information object ID for a digital object.
     *
     * @param int $digitalObjectId Digital object ID
     *
     * @return int|null
     */
    protected static function getInformationObjectId(int $digitalObjectId): ?int
    {
        $result = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('id', $digitalObjectId)
            ->value('object_id');

        return $result ? (int) $result : null;
    }

    /**
     * Check if user is admin.
     *
     * @param object|null $user User object
     *
     * @return bool
     */
    protected static function isAdminUser(?object $user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isAdministrator') && $user->isAdministrator()) {
            return true;
        }

        // Check via database
        if (method_exists($user, 'isAuthenticated') && $user->isAuthenticated()) {
            $userId = self::getUserId($user);
            if ($userId) {
                return \Illuminate\Database\Capsule\Manager::table('acl_user_group')
                    ->where('user_id', $userId)
                    ->where('group_id', 100)
                    ->exists();
            }
        }

        return false;
    }

    /**
     * Check if user can update the object.
     *
     * @param int         $objectId Information object ID
     * @param object|null $user     User object
     *
     * @return bool
     */
    protected static function userCanUpdate(int $objectId, ?object $user): bool
    {
        if (!$user || !method_exists($user, 'isAuthenticated') || !$user->isAuthenticated()) {
            return false;
        }

        try {
            $resource = QubitInformationObject::getById($objectId);
            if ($resource && \AtomExtensions\Services\AclService::check($resource, 'update')) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore ACL check errors
        }

        return false;
    }

    /**
     * Check if user has an exception for this embargo.
     *
     * @param int         $embargoId Embargo ID
     * @param object|null $user      User object
     *
     * @return bool
     */
    protected static function hasEmbargoException(int $embargoId, ?object $user): bool
    {
        if (!$user) {
            return false;
        }

        $userId = self::getUserId($user);
        if (!$userId) {
            return false;
        }

        $now = date('Y-m-d');

        // Check for user exception
        $userException = \Illuminate\Database\Capsule\Manager::table('embargo_exception')
            ->where('embargo_id', $embargoId)
            ->where('exception_type', 'user')
            ->where('exception_id', $userId)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->exists();

        if ($userException) {
            return true;
        }

        // Check for group exceptions
        $userGroups = \Illuminate\Database\Capsule\Manager::table('acl_user_group')
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->toArray();

        if (!empty($userGroups)) {
            $groupException = \Illuminate\Database\Capsule\Manager::table('embargo_exception')
                ->where('embargo_id', $embargoId)
                ->where('exception_type', 'group')
                ->whereIn('exception_id', $userGroups)
                ->where(function ($q) use ($now) {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
                })
                ->exists();

            if ($groupException) {
                return true;
            }
        }

        // Check for IP range exception
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ipAddress) {
            $ipExceptions = \Illuminate\Database\Capsule\Manager::table('embargo_exception')
                ->where('embargo_id', $embargoId)
                ->where('exception_type', 'ip_range')
                ->whereNotNull('ip_range_start')
                ->whereNotNull('ip_range_end')
                ->where(function ($q) use ($now) {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
                })
                ->get();

            $ipLong = ip2long($ipAddress);
            foreach ($ipExceptions as $exc) {
                if ($ipLong >= ip2long($exc->ip_range_start) && $ipLong <= ip2long($exc->ip_range_end)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get user ID from user object.
     *
     * @param object|null $user User object
     *
     * @return int|null
     */
    protected static function getUserId(?object $user): ?int
    {
        if (!$user) {
            return null;
        }

        if (method_exists($user, 'getAttribute')) {
            $userId = $user->getAttribute('user_id');
            if ($userId) {
                return (int) $userId;
            }
        }

        if (isset($user->id)) {
            return (int) $user->id;
        }

        return null;
    }

    /**
     * Format embargo info for display.
     *
     * @param object $embargo Embargo object
     *
     * @return array
     */
    protected static function formatEmbargoInfo(object $embargo): array
    {
        $typeLabels = [
            'full' => 'Full Access Restriction',
            'metadata_only' => 'Digital Content Restricted',
            'digital_only' => 'Download Restricted',
            'partial' => 'Partial Restriction',
        ];

        return [
            'id' => $embargo->id,
            'type' => $embargo->embargo_type,
            'type_label' => $typeLabels[$embargo->embargo_type] ?? 'Access Restricted',
            'end_date' => $embargo->end_date,
            'is_perpetual' => !$embargo->auto_release,
            'reason' => $embargo->reason ?? null,
        ];
    }

    /**
     * Get human-readable blocked reason.
     *
     * @param object $embargo Embargo object
     *
     * @return string
     */
    protected static function getBlockedReason(object $embargo): string
    {
        $reasonMessages = [
            'donor_restriction' => 'This material is restricted by donor agreement',
            'copyright' => 'This material is restricted due to copyright',
            'privacy' => 'This material is restricted for privacy protection',
            'legal' => 'This material is restricted for legal reasons',
            'commercial' => 'This material has commercial restrictions',
            'research' => 'This material is restricted for ongoing research',
            'cultural' => 'This material has cultural sensitivity restrictions',
            'security' => 'This material is restricted for security reasons',
            'other' => 'This material is currently restricted',
        ];

        return $reasonMessages[$embargo->reason] ?? 'This material is currently under embargo';
    }

    /**
     * Check if user can request access.
     *
     * @param object|null $user User object
     *
     * @return bool
     */
    protected static function canUserRequestAccess(?object $user): bool
    {
        // Check if access request plugin is enabled
        $pluginEnabled = \Illuminate\Database\Capsule\Manager::table('atom_plugin')
            ->where('name', 'ahgAccessRequestPlugin')
            ->where('is_enabled', 1)
            ->exists();

        if (!$pluginEnabled) {
            return false;
        }

        // Authenticated users can request access
        if ($user && method_exists($user, 'isAuthenticated') && $user->isAuthenticated()) {
            return true;
        }

        // Check if anonymous requests are allowed
        $allowAnonymous = sfConfig::get('app_access_request_allow_anonymous', false);

        return $allowAnonymous;
    }
}
