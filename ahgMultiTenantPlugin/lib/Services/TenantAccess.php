<?php

namespace AhgMultiTenant\Services;

use AhgMultiTenant\Models\Tenant;
use AhgMultiTenant\Models\TenantUser;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantAccess Service
 *
 * Manages user access to repositories (tenants).
 * Handles user assignment, access checks, and permission management.
 *
 * Now supports both legacy repository-based access (ahg_settings)
 * and new tenant-based access (heritage_tenant tables).
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantAccess
{
    /**
     * Check if tenant tables exist (for migration compatibility)
     *
     * @return bool
     */
    private static function tenantTablesExist(): bool
    {
        static $exists = null;
        if ($exists === null) {
            try {
                $exists = DB::getSchemaBuilder()->hasTable('heritage_tenant');
            } catch (\Exception $e) {
                $exists = false;
            }
        }
        return $exists;
    }

    /**
     * Check if user can access a tenant (new table structure)
     *
     * @param int $userId
     * @param int $tenantId
     * @return bool
     */
    public static function canAccessTenant(int $userId, int $tenantId): bool
    {
        // Admins can access all
        if (TenantContext::isAdmin($userId)) {
            return true;
        }

        // Check tenant exists and is accessible
        $tenant = Tenant::find($tenantId);
        if (!$tenant || !$tenant->canAccess()) {
            return false;
        }

        // Check if user is assigned to this tenant
        return TenantUser::findByTenantAndUser($tenantId, $userId) !== null;
    }

    /**
     * Check if user can manage users in a tenant
     *
     * @param int $userId
     * @param int $tenantId
     * @return bool
     */
    public static function canManageTenantUsers(int $userId, int $tenantId): bool
    {
        if (TenantContext::isAdmin($userId)) {
            return true;
        }

        return TenantUser::hasMinimumRole($userId, $tenantId, TenantUser::ROLE_SUPER_USER);
    }

    /**
     * Check if user can manage tenant settings
     *
     * @param int $userId
     * @param int $tenantId
     * @return bool
     */
    public static function canManageTenantSettings(int $userId, int $tenantId): bool
    {
        if (TenantContext::isAdmin($userId)) {
            return true;
        }

        return TenantUser::hasMinimumRole($userId, $tenantId, TenantUser::ROLE_SUPER_USER);
    }

    /**
     * Check if user can manage tenant status (activate, suspend)
     * Only admins can manage tenant status
     *
     * @param int $userId
     * @return bool
     */
    public static function canManageTenantStatus(int $userId): bool
    {
        return TenantContext::isAdmin($userId);
    }

    /**
     * Assign user to tenant with role
     *
     * @param int $userId User to assign
     * @param int $tenantId Tenant ID
     * @param string $role Role to assign
     * @param int $assignedBy User performing the assignment
     * @return array ['success' => bool, 'message' => string]
     */
    public static function assignUserToTenant(
        int $userId,
        int $tenantId,
        string $role,
        int $assignedBy
    ): array {
        // Check permission
        if (!self::canManageTenantUsers($assignedBy, $tenantId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to assign users to this tenant.'
            ];
        }

        // Check if assigner can assign this role
        if (!TenantContext::isAdmin($assignedBy)) {
            // Non-admins cannot assign owner role
            if ($role === TenantUser::ROLE_OWNER) {
                return [
                    'success' => false,
                    'message' => 'Only administrators can assign the owner role.'
                ];
            }
        }

        // Verify user exists
        $userExists = DB::table('user')->where('id', $userId)->exists();
        if (!$userExists) {
            return [
                'success' => false,
                'message' => 'User not found.'
            ];
        }

        // Verify tenant exists
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return [
                'success' => false,
                'message' => 'Tenant not found.'
            ];
        }

        // Assign user
        $assignment = TenantUser::assign($tenantId, $userId, $role, $assignedBy);

        if (!$assignment) {
            return [
                'success' => false,
                'message' => 'Failed to assign user to tenant.'
            ];
        }

        // Log the action
        self::logAction('assign_user_to_tenant', $tenantId, $userId, $assignedBy, [
            'role' => $role,
        ]);

        // Clear cache
        TenantContext::clearCache();

        return [
            'success' => true,
            'message' => 'User successfully assigned to tenant.'
        ];
    }

    /**
     * Remove user from tenant
     *
     * @param int $userId User to remove
     * @param int $tenantId Tenant ID
     * @param int $removedBy User performing the removal
     * @return array ['success' => bool, 'message' => string]
     */
    public static function removeUserFromTenant(int $userId, int $tenantId, int $removedBy): array
    {
        // Check permission
        if (!self::canManageTenantUsers($removedBy, $tenantId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to remove users from this tenant.'
            ];
        }

        $assignment = TenantUser::findByTenantAndUser($tenantId, $userId);

        if (!$assignment) {
            return [
                'success' => false,
                'message' => 'User is not assigned to this tenant.'
            ];
        }

        // Prevent removing the last owner
        if ($assignment->role === TenantUser::ROLE_OWNER) {
            $ownerCount = count(TenantUser::getUsersForTenant($tenantId, TenantUser::ROLE_OWNER));
            if ($ownerCount <= 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot remove the last owner from tenant.'
                ];
            }
        }

        // Non-admins cannot remove owners
        if (!TenantContext::isAdmin($removedBy) && $assignment->role === TenantUser::ROLE_OWNER) {
            return [
                'success' => false,
                'message' => 'Only administrators can remove owners.'
            ];
        }

        if (!TenantUser::remove($tenantId, $userId)) {
            return [
                'success' => false,
                'message' => 'Failed to remove user from tenant.'
            ];
        }

        // Log the action
        self::logAction('remove_user_from_tenant', $tenantId, $userId, $removedBy, [
            'previous_role' => $assignment->role,
        ]);

        // Clear cache
        TenantContext::clearCache();

        return [
            'success' => true,
            'message' => 'User successfully removed from tenant.'
        ];
    }

    /**
     * Update user role in tenant
     *
     * @param int $userId
     * @param int $tenantId
     * @param string $newRole
     * @param int $updatedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function updateUserTenantRole(
        int $userId,
        int $tenantId,
        string $newRole,
        int $updatedBy
    ): array {
        // Check permission
        if (!self::canManageTenantUsers($updatedBy, $tenantId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to manage users in this tenant.'
            ];
        }

        $assignment = TenantUser::findByTenantAndUser($tenantId, $userId);

        if (!$assignment) {
            return [
                'success' => false,
                'message' => 'User is not assigned to this tenant.'
            ];
        }

        // Non-admins cannot promote to or demote from owner
        if (!TenantContext::isAdmin($updatedBy)) {
            if ($newRole === TenantUser::ROLE_OWNER || $assignment->role === TenantUser::ROLE_OWNER) {
                return [
                    'success' => false,
                    'message' => 'Only administrators can change owner roles.'
                ];
            }
        }

        // Prevent demoting the last owner
        if ($assignment->role === TenantUser::ROLE_OWNER && $newRole !== TenantUser::ROLE_OWNER) {
            $ownerCount = count(TenantUser::getUsersForTenant($tenantId, TenantUser::ROLE_OWNER));
            if ($ownerCount <= 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot demote the last owner.'
                ];
            }
        }

        $previousRole = $assignment->role;

        if (!TenantUser::updateRole($tenantId, $userId, $newRole)) {
            return [
                'success' => false,
                'message' => 'Failed to update user role.'
            ];
        }

        // Log the action
        self::logAction('update_user_tenant_role', $tenantId, $userId, $updatedBy, [
            'previous_role' => $previousRole,
            'new_role' => $newRole,
        ]);

        return [
            'success' => true,
            'message' => 'User role updated successfully.'
        ];
    }

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
     * @param int $objectId Object ID (repository or tenant)
     * @param int $targetUserId Target user ID
     * @param int $actorUserId Actor user ID
     * @param array $additionalData Additional data to log
     */
    private static function logAction(
        string $action,
        int $objectId,
        int $targetUserId,
        int $actorUserId,
        array $additionalData = []
    ): void {
        try {
            // Use AhgAuditService if available
            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    'TenantAccess',
                    $objectId,
                    array_merge([
                        'user_id' => $actorUserId,
                        'target_user_id' => $targetUserId,
                        'object_id' => $objectId,
                    ], $additionalData)
                );
            }
        } catch (\Exception $e) {
            error_log('TenantAccess log error: ' . $e->getMessage());
        }
    }
}
