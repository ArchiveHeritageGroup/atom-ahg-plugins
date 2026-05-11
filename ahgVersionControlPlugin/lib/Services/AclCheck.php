<?php

namespace AhgVersionControl\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AclCheck — version-control permission helper.
 *
 * Permissions (action strings):
 *   version.list                — see the version history of a record
 *   version.diff                — compare two versions
 *   version.restore             — restore a non-classified record
 *   version.restore_classified  — additional gate to restore a classified record
 *                                 (composes with Phase J clearance check)
 *
 * Resolution rules (in priority order):
 *   1) Administrator group (acl_group.id = 100) → allowed.
 *   2) A user-scoped grant in acl_permission (user_id = ?, action = ?, grant_deny = 1) → allowed.
 *   3) A group-scoped grant for ANY of the user's groups → allowed.
 *   4) A group-scoped allow-all (action = NULL, grant_deny = 1) for any of the user's groups → allowed.
 *   5) Otherwise → denied.
 *
 * Phase K — class-action ACL only. The Phase J clearance check runs in parallel:
 *   - version.restore_classified MUST be granted AND clearance must be sufficient.
 *
 * @phase K
 */
class AclCheck
{
    public const ACTION_LIST                = 'version.list';
    public const ACTION_DIFF                = 'version.diff';
    public const ACTION_RESTORE             = 'version.restore';
    public const ACTION_RESTORE_CLASSIFIED  = 'version.restore_classified';

    private const ACL_GROUP_ADMINISTRATOR = 100;

    /** Cache of user → group_id list for the lifetime of the request. */
    private static array $groupCache = [];

    public function canUserDo(?int $userId, string $action): bool
    {
        if ($userId === null) {
            return true; // CLI / system context.
        }
        try {
            $groups = $this->getUserGroups($userId);
            if (in_array(self::ACL_GROUP_ADMINISTRATOR, $groups, true)) {
                return true;
            }
            // 2. User-scoped explicit grant
            $userGrant = DB::table('acl_permission')
                ->where('user_id', $userId)
                ->where('action', $action)
                ->where('grant_deny', 1)
                ->exists();
            if ($userGrant) {
                return true;
            }
            if (empty($groups)) {
                return false;
            }
            // 3. Group-scoped grant for this exact action
            $groupGrant = DB::table('acl_permission')
                ->whereIn('group_id', $groups)
                ->where('action', $action)
                ->where('grant_deny', 1)
                ->exists();
            if ($groupGrant) {
                return true;
            }
            // 4. Group-scoped allow-all (action IS NULL)
            $allowAll = DB::table('acl_permission')
                ->whereIn('group_id', $groups)
                ->whereNull('action')
                ->where('grant_deny', 1)
                ->exists();
            return $allowAll;
        } catch (\Throwable $e) {
            // ACL tables not present → fail closed (deny).
            return false;
        }
    }

    /**
     * Returns the list of acl_group ids the user belongs to (cached).
     *
     * @return array<int,int>
     */
    private function getUserGroups(int $userId): array
    {
        if (isset(self::$groupCache[$userId])) {
            return self::$groupCache[$userId];
        }
        try {
            $rows = DB::table('acl_user_group')
                ->where('user_id', $userId)
                ->pluck('group_id')
                ->all();
            self::$groupCache[$userId] = array_map('intval', $rows);
        } catch (\Throwable $e) {
            self::$groupCache[$userId] = [];
        }
        return self::$groupCache[$userId];
    }
}
