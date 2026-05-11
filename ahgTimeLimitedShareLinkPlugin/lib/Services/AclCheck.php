<?php

namespace AhgShareLink\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AclCheck — share-link permission helper.
 *
 * Five action constants:
 *   share_link.create                       — issue a basic share link
 *   share_link.create_classified            — issue a link for a classified record
 *   share_link.create_unlimited_expiry      — bypass the max-expiry cap
 *   share_link.list_all                     — admin view of all users' links
 *   share_link.revoke_others                — revoke links issued by other users
 *
 * Resolution rules — administrator bypass → user-scoped grant → group-scoped
 * grant → group allow-all → deny. Mirror of F2's AclCheck pattern.
 *
 * @phase C
 */
class AclCheck
{
    public const ACTION_CREATE                  = 'share_link.create';
    public const ACTION_CREATE_CLASSIFIED       = 'share_link.create_classified';
    public const ACTION_CREATE_UNLIMITED_EXPIRY = 'share_link.create_unlimited_expiry';
    public const ACTION_LIST_ALL                = 'share_link.list_all';
    public const ACTION_REVOKE_OTHERS           = 'share_link.revoke_others';

    private const ACL_GROUP_ADMINISTRATOR = 100;

    private static array $groupCache = [];

    public function canUserDo(?int $userId, string $action): bool
    {
        if ($userId === null) {
            return false; // Anonymous can never issue / list / revoke.
        }
        try {
            $groups = $this->getUserGroups($userId);
            if (in_array(self::ACL_GROUP_ADMINISTRATOR, $groups, true)) {
                return true;
            }
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
            $groupGrant = DB::table('acl_permission')
                ->whereIn('group_id', $groups)
                ->where('action', $action)
                ->where('grant_deny', 1)
                ->exists();
            if ($groupGrant) {
                return true;
            }
            return DB::table('acl_permission')
                ->whereIn('group_id', $groups)
                ->whereNull('action')
                ->where('grant_deny', 1)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return array<int,int>
     */
    private function getUserGroups(int $userId): array
    {
        if (isset(self::$groupCache[$userId])) {
            return self::$groupCache[$userId];
        }
        try {
            $rows = DB::table('acl_user_group')->where('user_id', $userId)->pluck('group_id')->all();
            self::$groupCache[$userId] = array_map('intval', $rows);
        } catch (\Throwable $e) {
            self::$groupCache[$userId] = [];
        }
        return self::$groupCache[$userId];
    }
}
