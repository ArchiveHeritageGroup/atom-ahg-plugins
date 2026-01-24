<?php

namespace AhgMultiTenant\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantAccess Service
 *
 * Manages user access to repositories (tenants).
 * Handles user assignment, access checks, and permission management.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantAccess
{
    /**
     * Check if user can access a specific repository
     *
     * @param int $userId User ID
     * @param int $repositoryId Repository ID
     * @return bool
     */
    public static function canAccessRepository(int $userId, int $repositoryId): bool
    {
        // Admins can access all
        if (TenantContext::isAdmin($userId)) {
            return true;
        }

        // Super users can access their assigned repos
        if (TenantContext::isSuperUser($userId, $repositoryId)) {
            return true;
        }

        // Check if user is assigned to this repo
        $userIds = TenantContext::getRepositoryUserIds($repositoryId);
        return in_array($userId, $userIds);
    }

    /**
     * Check if user can assign users to a repository
     *
     * @param int $userId User ID
     * @param int $repositoryId Repository ID
     * @return bool
     */
    public static function canAssignUsers(int $userId, int $repositoryId): bool
    {
        // Only admins and super users of the repo can assign users
        if (TenantContext::isAdmin($userId)) {
            return true;
        }

        return TenantContext::isSuperUser($userId, $repositoryId);
    }

    /**
     * Check if user can manage branding for a repository
     *
     * @param int $userId User ID
     * @param int $repositoryId Repository ID
     * @return bool
     */
    public static function canManageBranding(int $userId, int $repositoryId): bool
    {
        // Same as canAssignUsers - admin or super user
        return self::canAssignUsers($userId, $repositoryId);
    }

    /**
     * Assign a user to a repository
     *
     * @param int $userId User ID to assign
     * @param int $repositoryId Repository ID
     * @param int $assignedBy User ID who is assigning
     * @return array ['success' => bool, 'message' => string]
     */
    public static function assignUserToRepository(int $userId, int $repositoryId, int $assignedBy): array
    {
        // Check permission
        if (!self::canAssignUsers($assignedBy, $repositoryId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to assign users to this repository.'
            ];
        }

        // Verify user exists
        $userExists = DB::table('user')->where('id', $userId)->exists();
        if (!$userExists) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }

        // Verify repository exists
        $repoExists = DB::table('repository')->where('id', $repositoryId)->exists();
        if (!$repoExists) {
            return [
                'success' => false,
                'message' => 'Repository not found.'
            ];
        }

        // Get current users
        $currentUsers = TenantContext::getRepositoryUserIds($repositoryId);

        // Check if already assigned
        if (in_array($userId, $currentUsers)) {
            return [
                'success' => false,
                'message' => 'User is already assigned to this repository.'
            ];
        }

        // Add user
        $currentUsers[] = $userId;
        $value = implode(',', $currentUsers);

        DB::table('ahg_settings')->updateOrInsert(
            ['setting_key' => "tenant_repo_{$repositoryId}_users"],
            [
                'setting_value' => $value,
                'setting_group' => 'multi_tenant',
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        // Log the action
        self::logAction('assign_user', $repositoryId, $userId, $assignedBy);

        // Clear cache
        TenantContext::clearCache();

        return [
            'success' => true,
            'message' => 'User successfully assigned to repository.'
        ];
    }

    /**
     * Remove a user from a repository
     *
     * @param int $userId User ID to remove
     * @param int $repositoryId Repository ID
     * @param int $removedBy User ID who is removing
     * @return array ['success' => bool, 'message' => string]
     */
    public static function removeUserFromRepository(int $userId, int $repositoryId, int $removedBy): array
    {
        // Check permission
        if (!self::canAssignUsers($removedBy, $repositoryId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to remove users from this repository.'
            ];
        }

        // Get current users
        $currentUsers = TenantContext::getRepositoryUserIds($repositoryId);

        // Check if assigned
        if (!in_array($userId, $currentUsers)) {
            return [
                'success' => false,
                'message' => 'User is not assigned to this repository.'
            ];
        }

        // Remove user
        $currentUsers = array_filter($currentUsers, fn($id) => $id !== $userId);
        $value = implode(',', $currentUsers);

        if (empty($value)) {
            DB::table('ahg_settings')
                ->where('setting_key', "tenant_repo_{$repositoryId}_users")
                ->delete();
        } else {
            DB::table('ahg_settings')->updateOrInsert(
                ['setting_key' => "tenant_repo_{$repositoryId}_users"],
                [
                    'setting_value' => $value,
                    'setting_group' => 'multi_tenant',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }

        // Log the action
        self::logAction('remove_user', $repositoryId, $userId, $removedBy);

        // Clear cache
        TenantContext::clearCache();

        return [
            'success' => true,
            'message' => 'User successfully removed from repository.'
        ];
    }

    /**
     * Assign a super user to a repository (admin only)
     *
     * @param int $userId User ID to assign as super user
     * @param int $repositoryId Repository ID
     * @param int $assignedBy User ID who is assigning (must be admin)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function assignSuperUser(int $userId, int $repositoryId, int $assignedBy): array
    {
        // Only admins can assign super users
        if (!TenantContext::isAdmin($assignedBy)) {
            return [
                'success' => false,
                'message' => 'Only administrators can assign super users.'
            ];
        }

        // Verify user exists
        $userExists = DB::table('user')->where('id', $userId)->exists();
        if (!$userExists) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }

        // Verify repository exists
        $repoExists = DB::table('repository')->where('id', $repositoryId)->exists();
        if (!$repoExists) {
            return [
                'success' => false,
                'message' => 'Repository not found.'
            ];
        }

        // Get current super users
        $currentSuperUsers = TenantContext::getRepositorySuperUserIds($repositoryId);

        // Check if already a super user
        if (in_array($userId, $currentSuperUsers)) {
            return [
                'success' => false,
                'message' => 'User is already a super user for this repository.'
            ];
        }

        // Add super user
        $currentSuperUsers[] = $userId;
        $value = implode(',', $currentSuperUsers);

        DB::table('ahg_settings')->updateOrInsert(
            ['setting_key' => "tenant_repo_{$repositoryId}_super_users"],
            [
                'setting_value' => $value,
                'setting_group' => 'multi_tenant',
                'updated_at' => date('Y-m-d H:i:s')
            ]
        );

        // Log the action
        self::logAction('assign_super_user', $repositoryId, $userId, $assignedBy);

        // Clear cache
        TenantContext::clearCache();

        return [
            'success' => true,
            'message' => 'User successfully assigned as super user.'
        ];
    }

    /**
     * Remove a super user from a repository (admin only)
     *
     * @param int $userId User ID to remove as super user
     * @param int $repositoryId Repository ID
     * @param int $removedBy User ID who is removing (must be admin)
     * @return array ['success' => bool, 'message' => string]
     */
    public static function removeSuperUser(int $userId, int $repositoryId, int $removedBy): array
    {
        // Only admins can remove super users
        if (!TenantContext::isAdmin($removedBy)) {
            return [
                'success' => false,
                'message' => 'Only administrators can remove super users.'
            ];
        }

        // Get current super users
        $currentSuperUsers = TenantContext::getRepositorySuperUserIds($repositoryId);

        // Check if is a super user
        if (!in_array($userId, $currentSuperUsers)) {
            return [
                'success' => false,
                'message' => 'User is not a super user for this repository.'
            ];
        }

        // Remove super user
        $currentSuperUsers = array_filter($currentSuperUsers, fn($id) => $id !== $userId);
        $value = implode(',', $currentSuperUsers);

        if (empty($value)) {
            DB::table('ahg_settings')
                ->where('setting_key', "tenant_repo_{$repositoryId}_super_users")
                ->delete();
        } else {
            DB::table('ahg_settings')->updateOrInsert(
                ['setting_key' => "tenant_repo_{$repositoryId}_super_users"],
                [
                    'setting_value' => $value,
                    'setting_group' => 'multi_tenant',
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            );
        }

        // Log the action
        self::logAction('remove_super_user', $repositoryId, $userId, $removedBy);

        // Clear cache
        TenantContext::clearCache();

        return [
            'success' => true,
            'message' => 'Super user successfully removed.'
        ];
    }

    /**
     * Get all users for a repository (both super users and regular users)
     *
     * @param int $repositoryId Repository ID
     * @return array ['super_users' => [...], 'users' => [...]]
     */
    public static function getRepositoryUsers(int $repositoryId): array
    {
        $superUserIds = TenantContext::getRepositorySuperUserIds($repositoryId);
        $userIds = TenantContext::getRepositoryUserIds($repositoryId);

        $superUsers = [];
        $users = [];

        if (!empty($superUserIds)) {
            $superUsers = DB::table('user as u')
                ->join('actor_i18n as ai', function ($join) {
                    $join->on('u.id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->whereIn('u.id', $superUserIds)
                ->select('u.id', 'u.username', 'u.email', 'ai.authorized_form_of_name as name')
                ->get()
                ->toArray();
        }

        if (!empty($userIds)) {
            $users = DB::table('user as u')
                ->join('actor_i18n as ai', function ($join) {
                    $join->on('u.id', '=', 'ai.id')
                        ->where('ai.culture', '=', 'en');
                })
                ->whereIn('u.id', $userIds)
                ->select('u.id', 'u.username', 'u.email', 'ai.authorized_form_of_name as name')
                ->get()
                ->toArray();
        }

        return [
            'super_users' => $superUsers,
            'users' => $users
        ];
    }

    /**
     * Get available users (not yet assigned to repository)
     *
     * @param int $repositoryId Repository ID
     * @return array
     */
    public static function getAvailableUsers(int $repositoryId): array
    {
        $superUserIds = TenantContext::getRepositorySuperUserIds($repositoryId);
        $userIds = TenantContext::getRepositoryUserIds($repositoryId);
        $excludeIds = array_unique(array_merge($superUserIds, $userIds));

        $query = DB::table('user as u')
            ->join('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                    ->where('ai.culture', '=', 'en');
            })
            ->where('u.active', 1)
            ->select('u.id', 'u.username', 'u.email', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name');

        if (!empty($excludeIds)) {
            $query->whereNotIn('u.id', $excludeIds);
        }

        return $query->get()->toArray();
    }

    /**
     * Log tenant access action
     *
     * @param string $action Action type
     * @param int $repositoryId Repository ID
     * @param int $targetUserId Target user ID
     * @param int $actorUserId Actor user ID
     */
    private static function logAction(string $action, int $repositoryId, int $targetUserId, int $actorUserId): void
    {
        try {
            // Use AhgAuditService if available
            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    'TenantAccess',
                    $repositoryId,
                    [
                        'user_id' => $actorUserId,
                        'target_user_id' => $targetUserId,
                        'repository_id' => $repositoryId,
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log('TenantAccess log error: ' . $e->getMessage());
        }
    }
}
