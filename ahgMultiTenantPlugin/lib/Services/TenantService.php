<?php

namespace AhgMultiTenant\Services;

use AhgMultiTenant\Models\Tenant;
use AhgMultiTenant\Models\TenantUser;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantService
 *
 * Handles tenant CRUD operations and status management.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantService
{
    /** @var int Default trial period in days */
    public const DEFAULT_TRIAL_DAYS = 14;

    /**
     * Create a new tenant
     *
     * @param array $data Tenant data
     * @param int|null $createdBy User ID who is creating
     * @return array ['success' => bool, 'message' => string, 'tenant' => Tenant|null]
     */
    public static function create(array $data, ?int $createdBy = null): array
    {
        // Validate required fields
        if (empty($data['name'])) {
            return [
                'success' => false,
                'message' => 'Tenant name is required.',
                'tenant' => null,
            ];
        }

        // Generate or validate code
        $code = $data['code'] ?? Tenant::generateCode($data['name']);

        if (!Tenant::isCodeUnique($code)) {
            return [
                'success' => false,
                'message' => 'Tenant code already exists.',
                'tenant' => null,
            ];
        }

        // Check domain uniqueness
        if (!empty($data['domain'])) {
            $existing = Tenant::findByDomain($data['domain']);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Domain is already in use by another tenant.',
                    'tenant' => null,
                ];
            }
        }

        // Check subdomain uniqueness
        if (!empty($data['subdomain'])) {
            $existing = Tenant::findBySubdomain($data['subdomain']);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'Subdomain is already in use by another tenant.',
                    'tenant' => null,
                ];
            }
        }

        // Create tenant
        $tenant = new Tenant();
        $tenant->code = $code;
        $tenant->name = $data['name'];
        $tenant->domain = $data['domain'] ?? null;
        $tenant->subdomain = $data['subdomain'] ?? null;
        $tenant->settings = $data['settings'] ?? null;
        $tenant->status = $data['status'] ?? Tenant::STATUS_TRIAL;
        $tenant->repositoryId = $data['repository_id'] ?? null;
        $tenant->contactName = $data['contact_name'] ?? null;
        $tenant->contactEmail = $data['contact_email'] ?? null;
        $tenant->createdBy = $createdBy;

        // Set trial end date if status is trial
        if ($tenant->status === Tenant::STATUS_TRIAL) {
            $trialDays = $data['trial_days'] ?? self::DEFAULT_TRIAL_DAYS;
            $tenant->trialEndsAt = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
        }

        if (!$tenant->save()) {
            return [
                'success' => false,
                'message' => 'Failed to create tenant.',
                'tenant' => null,
            ];
        }

        // Log the action
        self::logAction('create_tenant', $tenant->id, $createdBy, [
            'code' => $tenant->code,
            'name' => $tenant->name,
            'status' => $tenant->status,
        ]);

        return [
            'success' => true,
            'message' => 'Tenant created successfully.',
            'tenant' => $tenant,
        ];
    }

    /**
     * Update a tenant
     *
     * @param int $tenantId
     * @param array $data
     * @param int|null $updatedBy
     * @return array ['success' => bool, 'message' => string, 'tenant' => Tenant|null]
     */
    public static function update(int $tenantId, array $data, ?int $updatedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return [
                'success' => false,
                'message' => 'Tenant not found.',
                'tenant' => null,
            ];
        }

        // Validate code uniqueness if changing
        if (isset($data['code']) && $data['code'] !== $tenant->code) {
            if (!Tenant::isCodeUnique($data['code'], $tenant->id)) {
                return [
                    'success' => false,
                    'message' => 'Tenant code already exists.',
                    'tenant' => null,
                ];
            }
            $tenant->code = $data['code'];
        }

        // Check domain uniqueness if changing
        if (isset($data['domain']) && $data['domain'] !== $tenant->domain) {
            if (!empty($data['domain'])) {
                $existing = Tenant::findByDomain($data['domain']);
                if ($existing && $existing->id !== $tenant->id) {
                    return [
                        'success' => false,
                        'message' => 'Domain is already in use by another tenant.',
                        'tenant' => null,
                    ];
                }
            }
            $tenant->domain = $data['domain'] ?: null;
        }

        // Check subdomain uniqueness if changing
        if (isset($data['subdomain']) && $data['subdomain'] !== $tenant->subdomain) {
            if (!empty($data['subdomain'])) {
                $existing = Tenant::findBySubdomain($data['subdomain']);
                if ($existing && $existing->id !== $tenant->id) {
                    return [
                        'success' => false,
                        'message' => 'Subdomain is already in use by another tenant.',
                        'tenant' => null,
                    ];
                }
            }
            $tenant->subdomain = $data['subdomain'] ?: null;
        }

        // Update other fields
        if (isset($data['name'])) {
            $tenant->name = $data['name'];
        }

        if (isset($data['settings'])) {
            $tenant->settings = $data['settings'];
        }

        if (isset($data['repository_id'])) {
            $tenant->repositoryId = $data['repository_id'] ?: null;
        }

        if (isset($data['contact_name'])) {
            $tenant->contactName = $data['contact_name'];
        }

        if (isset($data['contact_email'])) {
            $tenant->contactEmail = $data['contact_email'];
        }

        if (!$tenant->save()) {
            return [
                'success' => false,
                'message' => 'Failed to update tenant.',
                'tenant' => null,
            ];
        }

        // Log the action
        self::logAction('update_tenant', $tenant->id, $updatedBy, $data);

        return [
            'success' => true,
            'message' => 'Tenant updated successfully.',
            'tenant' => $tenant,
        ];
    }

    /**
     * Activate a tenant
     *
     * @param int $tenantId
     * @param int|null $activatedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function activate(int $tenantId, ?int $activatedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        if ($tenant->isActive()) {
            return ['success' => false, 'message' => 'Tenant is already active.'];
        }

        $previousStatus = $tenant->status;
        $tenant->status = Tenant::STATUS_ACTIVE;
        $tenant->suspendedAt = null;
        $tenant->suspendedReason = null;

        if (!$tenant->save()) {
            return ['success' => false, 'message' => 'Failed to activate tenant.'];
        }

        // Log the action
        self::logAction('activate_tenant', $tenant->id, $activatedBy, [
            'previous_status' => $previousStatus,
        ]);

        return ['success' => true, 'message' => 'Tenant activated successfully.'];
    }

    /**
     * Suspend a tenant
     *
     * @param int $tenantId
     * @param string|null $reason
     * @param int|null $suspendedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function suspend(int $tenantId, ?string $reason = null, ?int $suspendedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        if ($tenant->isSuspended()) {
            return ['success' => false, 'message' => 'Tenant is already suspended.'];
        }

        $previousStatus = $tenant->status;
        $tenant->status = Tenant::STATUS_SUSPENDED;
        $tenant->suspendedAt = date('Y-m-d H:i:s');
        $tenant->suspendedReason = $reason;

        if (!$tenant->save()) {
            return ['success' => false, 'message' => 'Failed to suspend tenant.'];
        }

        // Log the action
        self::logAction('suspend_tenant', $tenant->id, $suspendedBy, [
            'previous_status' => $previousStatus,
            'reason' => $reason,
        ]);

        return ['success' => true, 'message' => 'Tenant suspended successfully.'];
    }

    /**
     * Start trial for a tenant
     *
     * @param int $tenantId
     * @param int $trialDays
     * @param int|null $startedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function startTrial(int $tenantId, int $trialDays = self::DEFAULT_TRIAL_DAYS, ?int $startedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        $previousStatus = $tenant->status;
        $tenant->status = Tenant::STATUS_TRIAL;
        $tenant->trialEndsAt = date('Y-m-d H:i:s', strtotime("+{$trialDays} days"));
        $tenant->suspendedAt = null;
        $tenant->suspendedReason = null;

        if (!$tenant->save()) {
            return ['success' => false, 'message' => 'Failed to start trial.'];
        }

        // Log the action
        self::logAction('start_trial', $tenant->id, $startedBy, [
            'previous_status' => $previousStatus,
            'trial_days' => $trialDays,
            'trial_ends_at' => $tenant->trialEndsAt,
        ]);

        return ['success' => true, 'message' => "Trial started for {$trialDays} days."];
    }

    /**
     * Extend trial for a tenant
     *
     * @param int $tenantId
     * @param int $additionalDays
     * @param int|null $extendedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function extendTrial(int $tenantId, int $additionalDays, ?int $extendedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        if (!$tenant->isTrial()) {
            return ['success' => false, 'message' => 'Tenant is not on trial.'];
        }

        $currentEnd = strtotime($tenant->trialEndsAt ?? 'now');
        $newEnd = max($currentEnd, time()) + ($additionalDays * 86400);
        $tenant->trialEndsAt = date('Y-m-d H:i:s', $newEnd);

        if (!$tenant->save()) {
            return ['success' => false, 'message' => 'Failed to extend trial.'];
        }

        // Log the action
        self::logAction('extend_trial', $tenant->id, $extendedBy, [
            'additional_days' => $additionalDays,
            'new_trial_ends_at' => $tenant->trialEndsAt,
        ]);

        return ['success' => true, 'message' => "Trial extended by {$additionalDays} days."];
    }

    /**
     * Delete a tenant
     *
     * @param int $tenantId
     * @param int|null $deletedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function delete(int $tenantId, ?int $deletedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        // Check if tenant has users
        $userCount = $tenant->getUserCount();
        if ($userCount > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete tenant with {$userCount} assigned user(s). Remove all users first.",
            ];
        }

        $tenantData = [
            'code' => $tenant->code,
            'name' => $tenant->name,
        ];

        if (!$tenant->delete()) {
            return ['success' => false, 'message' => 'Failed to delete tenant.'];
        }

        // Log the action
        self::logAction('delete_tenant', $tenantId, $deletedBy, $tenantData);

        return ['success' => true, 'message' => 'Tenant deleted successfully.'];
    }

    /**
     * Assign user to tenant
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $role
     * @param int|null $assignedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function assignUser(
        int $tenantId,
        int $userId,
        string $role = TenantUser::ROLE_VIEWER,
        ?int $assignedBy = null
    ): array {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        // Verify user exists
        $userExists = DB::table('user')->where('id', $userId)->exists();
        if (!$userExists) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Validate role
        if (!in_array($role, TenantUser::VALID_ROLES)) {
            return ['success' => false, 'message' => 'Invalid role.'];
        }

        $assignment = TenantUser::assign($tenantId, $userId, $role, $assignedBy);

        if (!$assignment) {
            return ['success' => false, 'message' => 'Failed to assign user to tenant.'];
        }

        // Log the action
        self::logAction('assign_user', $tenantId, $assignedBy, [
            'user_id' => $userId,
            'role' => $role,
        ]);

        // Clear context cache
        TenantContext::clearCache();

        return ['success' => true, 'message' => 'User assigned to tenant successfully.'];
    }

    /**
     * Remove user from tenant
     *
     * @param int $tenantId
     * @param int $userId
     * @param int|null $removedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function removeUser(int $tenantId, int $userId, ?int $removedBy = null): array
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'message' => 'Tenant not found.'];
        }

        $assignment = TenantUser::findByTenantAndUser($tenantId, $userId);

        if (!$assignment) {
            return ['success' => false, 'message' => 'User is not assigned to this tenant.'];
        }

        // Prevent removing the last owner
        if ($assignment->role === TenantUser::ROLE_OWNER) {
            $ownerCount = count(TenantUser::getUsersForTenant($tenantId, TenantUser::ROLE_OWNER));
            if ($ownerCount <= 1) {
                return ['success' => false, 'message' => 'Cannot remove the last owner from tenant.'];
            }
        }

        if (!TenantUser::remove($tenantId, $userId)) {
            return ['success' => false, 'message' => 'Failed to remove user from tenant.'];
        }

        // Log the action
        self::logAction('remove_user', $tenantId, $removedBy, [
            'user_id' => $userId,
            'previous_role' => $assignment->role,
        ]);

        // Clear context cache
        TenantContext::clearCache();

        return ['success' => true, 'message' => 'User removed from tenant successfully.'];
    }

    /**
     * Update user role in tenant
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $newRole
     * @param int|null $updatedBy
     * @return array ['success' => bool, 'message' => string]
     */
    public static function updateUserRole(
        int $tenantId,
        int $userId,
        string $newRole,
        ?int $updatedBy = null
    ): array {
        $assignment = TenantUser::findByTenantAndUser($tenantId, $userId);

        if (!$assignment) {
            return ['success' => false, 'message' => 'User is not assigned to this tenant.'];
        }

        if (!in_array($newRole, TenantUser::VALID_ROLES)) {
            return ['success' => false, 'message' => 'Invalid role.'];
        }

        // Prevent demoting the last owner
        if ($assignment->role === TenantUser::ROLE_OWNER && $newRole !== TenantUser::ROLE_OWNER) {
            $ownerCount = count(TenantUser::getUsersForTenant($tenantId, TenantUser::ROLE_OWNER));
            if ($ownerCount <= 1) {
                return ['success' => false, 'message' => 'Cannot demote the last owner.'];
            }
        }

        $previousRole = $assignment->role;

        if (!TenantUser::updateRole($tenantId, $userId, $newRole)) {
            return ['success' => false, 'message' => 'Failed to update user role.'];
        }

        // Log the action
        self::logAction('update_user_role', $tenantId, $updatedBy, [
            'user_id' => $userId,
            'previous_role' => $previousRole,
            'new_role' => $newRole,
        ]);

        return ['success' => true, 'message' => 'User role updated successfully.'];
    }

    /**
     * Get tenant statistics
     *
     * @return array
     */
    public static function getStatistics(): array
    {
        return [
            'total' => DB::table('heritage_tenant')->count(),
            'active' => DB::table('heritage_tenant')->where('status', Tenant::STATUS_ACTIVE)->count(),
            'suspended' => DB::table('heritage_tenant')->where('status', Tenant::STATUS_SUSPENDED)->count(),
            'trial' => DB::table('heritage_tenant')->where('status', Tenant::STATUS_TRIAL)->count(),
            'trial_expiring_soon' => DB::table('heritage_tenant')
                ->where('status', Tenant::STATUS_TRIAL)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '<=', date('Y-m-d H:i:s', strtotime('+7 days')))
                ->where('trial_ends_at', '>', date('Y-m-d H:i:s'))
                ->count(),
            'trial_expired' => DB::table('heritage_tenant')
                ->where('status', Tenant::STATUS_TRIAL)
                ->whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '<', date('Y-m-d H:i:s'))
                ->count(),
        ];
    }

    /**
     * Get setting value for tenant with fallback to global default
     *
     * @param int $tenantId
     * @param string $settingKey
     * @param mixed $default
     * @return mixed
     */
    public static function getSetting(int $tenantId, string $settingKey, $default = null)
    {
        // First check tenant-specific override
        $override = DB::table('heritage_tenant_settings_override')
            ->where('tenant_id', $tenantId)
            ->where('setting_key', $settingKey)
            ->value('setting_value');

        if ($override !== null) {
            return $override;
        }

        // Check tenant's embedded settings
        $tenant = Tenant::find($tenantId);
        if ($tenant && isset($tenant->settings[$settingKey])) {
            return $tenant->settings[$settingKey];
        }

        // Fall back to global setting
        $global = DB::table('ahg_settings')
            ->where('setting_key', $settingKey)
            ->value('setting_value');

        return $global ?? $default;
    }

    /**
     * Set setting value for tenant
     *
     * @param int $tenantId
     * @param string $settingKey
     * @param mixed $value
     * @param int|null $updatedBy
     * @return bool
     */
    public static function setSetting(int $tenantId, string $settingKey, $value, ?int $updatedBy = null): bool
    {
        return DB::table('heritage_tenant_settings_override')->updateOrInsert(
            ['tenant_id' => $tenantId, 'setting_key' => $settingKey],
            [
                'setting_value' => is_array($value) ? json_encode($value) : $value,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $updatedBy,
            ]
        );
    }

    /**
     * Delete setting override for tenant (reverts to global default)
     *
     * @param int $tenantId
     * @param string $settingKey
     * @return bool
     */
    public static function deleteSetting(int $tenantId, string $settingKey): bool
    {
        return DB::table('heritage_tenant_settings_override')
            ->where('tenant_id', $tenantId)
            ->where('setting_key', $settingKey)
            ->delete() > 0;
    }

    /**
     * Log tenant action
     *
     * @param string $action
     * @param int $tenantId
     * @param int|null $userId
     * @param array $data
     */
    private static function logAction(string $action, int $tenantId, ?int $userId, array $data = []): void
    {
        try {
            // Use AhgAuditService if available
            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    'TenantService',
                    $tenantId,
                    array_merge(['user_id' => $userId, 'tenant_id' => $tenantId], $data)
                );
            }
        } catch (\Exception $e) {
            error_log('TenantService log error: ' . $e->getMessage());
        }
    }
}
