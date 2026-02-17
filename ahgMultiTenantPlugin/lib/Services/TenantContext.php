<?php

namespace AhgMultiTenant\Services;

use AhgMultiTenant\Models\Tenant;
use AhgMultiTenant\Models\TenantUser;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantContext Service
 *
 * Manages the current tenant (repository) context for multi-tenancy.
 * Handles session-based repository/tenant selection and user access.
 *
 * User Hierarchy:
 * 1. ADMIN - Sees all repositories/tenants, assigns super users
 * 2. SUPER USER/OWNER - Assigned to tenants, can assign users to their tenants
 * 3. USER - Assigned to tenants, sees only assigned tenants
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantContext
{
    /** @var int|null Current repository ID (null = all repositories for admins) */
    private static ?int $currentRepositoryId = null;

    /** @var int|null Current tenant ID */
    private static ?int $currentTenantId = null;

    /** @var bool Whether "View All" mode is active (admin only) */
    private static bool $viewAllMode = false;

    /** @var array Cache for user repositories */
    private static array $userRepositoriesCache = [];

    /** @var array Cache for user tenants */
    private static array $userTenantsCache = [];

    /** @var bool Whether context was set from domain resolution */
    private static bool $resolvedFromDomain = false;

    /** @var int Administrator group ID in AtoM */
    public const ADMIN_GROUP_ID = 100;

    /**
     * Initialize tenant context from session
     */
    public static function initializeFromSession(): void
    {
        if (!\sfContext::hasInstance()) {
            return;
        }

        $user = \sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) {
            return;
        }

        // Get from session
        $sessionRepoId = $user->getAttribute('tenant_repository_id');
        $sessionTenantId = $user->getAttribute('tenant_id');
        $viewAll = $user->getAttribute('tenant_view_all', false);

        if ($sessionRepoId !== null) {
            self::$currentRepositoryId = (int) $sessionRepoId;
        }
        if ($sessionTenantId !== null) {
            self::$currentTenantId = (int) $sessionTenantId;
        }
        self::$viewAllMode = (bool) $viewAll;
    }

    /**
     * Initialize tenant context from domain resolution
     *
     * This method is called BEFORE session initialization to allow
     * domain-based tenant routing to take precedence.
     *
     * @return bool True if tenant was resolved from domain
     */
    public static function initializeFromDomain(): bool
    {
        try {
            $tenant = TenantResolver::resolveFromHost();

            if ($tenant) {
                self::$currentTenantId = $tenant->id;
                self::$currentRepositoryId = $tenant->repositoryId;
                self::$viewAllMode = false;
                self::$resolvedFromDomain = true;

                // Store in session for subsequent requests (optimization)
                if (\sfContext::hasInstance()) {
                    $user = \sfContext::getInstance()->getUser();
                    $user->setAttribute('tenant_id', $tenant->id);
                    $user->setAttribute('tenant_repository_id', $tenant->repositoryId);
                    $user->setAttribute('tenant_view_all', false);
                    $user->setAttribute('tenant_resolved_from_domain', true);
                }

                return true;
            }
        } catch (\Exception $e) {
            error_log('TenantContext domain resolution error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Check if current context was resolved from domain
     *
     * @return bool
     */
    public static function wasResolvedFromDomain(): bool
    {
        return self::$resolvedFromDomain;
    }

    /**
     * Get the current tenant ID
     *
     * @return int|null Tenant ID or null if viewing all
     */
    public static function getCurrentTenantId(): ?int
    {
        return self::$currentTenantId;
    }

    /**
     * Get the current tenant object
     *
     * @return Tenant|null
     */
    public static function getCurrentTenant(): ?Tenant
    {
        if (self::$currentTenantId === null) {
            return null;
        }
        return Tenant::find(self::$currentTenantId);
    }

    /**
     * Set the current tenant
     *
     * @param int|null $tenantId Tenant ID or null for "all"
     * @return bool Success
     */
    public static function setCurrentTenant(?int $tenantId): bool
    {
        if (!\sfContext::hasInstance()) {
            return false;
        }

        $user = \sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');

        // If null, only admins can view all
        if ($tenantId === null) {
            if (!self::isAdmin($userId)) {
                return false;
            }
            self::$currentTenantId = null;
            self::$currentRepositoryId = null;
            self::$viewAllMode = true;
            $user->setAttribute('tenant_id', null);
            $user->setAttribute('tenant_repository_id', null);
            $user->setAttribute('tenant_view_all', true);
            return true;
        }

        // Check tenant exists and is accessible
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        // Check tenant status
        if (!$tenant->canAccess() && !self::isAdmin($userId)) {
            return false;
        }

        // Check user can access this tenant
        if (!self::canAccessTenant($userId, $tenantId)) {
            return false;
        }

        self::$currentTenantId = $tenantId;
        self::$currentRepositoryId = $tenant->repositoryId;
        self::$viewAllMode = false;

        $user->setAttribute('tenant_id', $tenantId);
        $user->setAttribute('tenant_repository_id', $tenant->repositoryId);
        $user->setAttribute('tenant_view_all', false);

        return true;
    }

    /**
     * Check if user can access a tenant
     *
     * @param int $userId
     * @param int $tenantId
     * @return bool
     */
    public static function canAccessTenant(int $userId, int $tenantId): bool
    {
        // Admins can access all tenants
        if (self::isAdmin($userId)) {
            return true;
        }

        // Check if user is assigned to this tenant
        return TenantUser::findByTenantAndUser($tenantId, $userId) !== null;
    }

    /**
     * Get user's role in a tenant
     *
     * @param int $userId
     * @param int $tenantId
     * @return string|null Role or null if not assigned
     */
    public static function getUserTenantRole(int $userId, int $tenantId): ?string
    {
        if (self::isAdmin($userId)) {
            return TenantUser::ROLE_OWNER; // Admins have full access
        }

        $assignment = TenantUser::findByTenantAndUser($tenantId, $userId);
        return $assignment ? $assignment->role : null;
    }

    /**
     * Get list of tenants the user can access
     *
     * @param int $userId User ID
     * @return array Array of tenant objects with role info
     */
    public static function getUserTenants(int $userId): array
    {
        if (isset(self::$userTenantsCache[$userId])) {
            return self::$userTenantsCache[$userId];
        }

        // Admin sees all active tenants
        if (self::isAdmin($userId)) {
            $tenants = DB::table('heritage_tenant as t')
                ->whereIn('t.status', [Tenant::STATUS_ACTIVE, Tenant::STATUS_TRIAL])
                ->select('t.*')
                ->selectRaw("'owner' as role")
                ->selectRaw('0 as is_primary')
                ->orderBy('t.name')
                ->get()
                ->toArray();

            self::$userTenantsCache[$userId] = $tenants;
            return $tenants;
        }

        // Get tenants user is assigned to (only active/trial tenants)
        $tenants = DB::table('heritage_tenant_user as tu')
            ->join('heritage_tenant as t', 'tu.tenant_id', '=', 't.id')
            ->where('tu.user_id', $userId)
            ->whereIn('t.status', [Tenant::STATUS_ACTIVE, Tenant::STATUS_TRIAL])
            ->select('t.*', 'tu.role', 'tu.is_primary', 'tu.assigned_at')
            ->orderBy('tu.is_primary', 'desc')
            ->orderBy('t.name')
            ->get()
            ->toArray();

        self::$userTenantsCache[$userId] = $tenants;
        return $tenants;
    }

    /**
     * Check if user has minimum role in current tenant
     *
     * @param int $userId
     * @param string $minimumRole
     * @return bool
     */
    public static function hasMinimumRoleInCurrentTenant(int $userId, string $minimumRole): bool
    {
        if (self::isAdmin($userId)) {
            return true;
        }

        if (self::$currentTenantId === null) {
            return false;
        }

        return TenantUser::hasMinimumRole($userId, self::$currentTenantId, $minimumRole);
    }

    /**
     * Get the current repository ID
     *
     * @return int|null Repository ID or null if viewing all
     */
    public static function getCurrentRepositoryId(): ?int
    {
        return self::$currentRepositoryId;
    }

    /**
     * Set the current repository (switch tenant)
     *
     * @param int|null $repositoryId Repository ID or null for "all"
     * @return bool Success
     */
    public static function setCurrentRepository(?int $repositoryId): bool
    {
        if (!\sfContext::hasInstance()) {
            return false;
        }

        $user = \sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) {
            return false;
        }

        $userId = $user->getAttribute('user_id');

        // If null, only admins can view all
        if ($repositoryId === null) {
            if (!self::isAdmin($userId)) {
                return false;
            }
            self::$currentRepositoryId = null;
            self::$viewAllMode = true;
            $user->setAttribute('tenant_repository_id', null);
            $user->setAttribute('tenant_view_all', true);
            return true;
        }

        // Check user can access this repository
        if (!TenantAccess::canAccessRepository($userId, $repositoryId)) {
            return false;
        }

        self::$currentRepositoryId = $repositoryId;
        self::$viewAllMode = false;
        $user->setAttribute('tenant_repository_id', $repositoryId);
        $user->setAttribute('tenant_view_all', false);

        return true;
    }

    /**
     * Get list of repositories the user can access
     *
     * @param int $userId User ID
     * @return array Array of repository objects
     */
    public static function getUserRepositories(int $userId): array
    {
        if (isset(self::$userRepositoriesCache[$userId])) {
            return self::$userRepositoriesCache[$userId];
        }

        // Admin sees all repositories
        // Note: Repository extends Actor, so name is in actor_i18n
        if (self::isAdmin($userId)) {
            $repos = DB::table('repository as r')
                ->leftJoin('actor_i18n as ai', function ($join) {
                    $join->on('r.id', '=', 'ai.id')
                        ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
                })
                ->leftJoin('slug as s', 's.object_id', '=', 'r.id')
                ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name', 's.slug')
                ->orderBy('ai.authorized_form_of_name')
                ->get()
                ->toArray();

            self::$userRepositoriesCache[$userId] = $repos;
            return $repos;
        }

        // Get repos where user is super user
        $superUserRepoIds = self::getSuperUserRepositoryIds($userId);

        // Get repos where user is assigned
        $userRepoIds = self::getAssignedRepositoryIds($userId);

        // Combine unique IDs
        $allRepoIds = array_unique(array_merge($superUserRepoIds, $userRepoIds));

        if (empty($allRepoIds)) {
            self::$userRepositoriesCache[$userId] = [];
            return [];
        }

        $repos = DB::table('repository as r')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('r.id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('slug as s', 's.object_id', '=', 'r.id')
            ->whereIn('r.id', $allRepoIds)
            ->select('r.id', 'r.identifier', 'ai.authorized_form_of_name as name', 's.slug')
            ->orderBy('ai.authorized_form_of_name')
            ->get()
            ->toArray();

        self::$userRepositoriesCache[$userId] = $repos;
        return $repos;
    }

    /**
     * Check if user is an AtoM administrator
     *
     * @param int $userId User ID
     * @return bool
     */
    public static function isAdmin(int $userId): bool
    {
        static $adminCache = [];

        if (isset($adminCache[$userId])) {
            return $adminCache[$userId];
        }

        $isAdmin = DB::table('acl_user_group')
            ->where('user_id', $userId)
            ->where('group_id', self::ADMIN_GROUP_ID)
            ->exists();

        $adminCache[$userId] = $isAdmin;
        return $isAdmin;
    }

    /**
     * Check if user is a super user for a specific repository
     *
     * @param int $userId User ID
     * @param int $repositoryId Repository ID
     * @return bool
     */
    public static function isSuperUser(int $userId, int $repositoryId): bool
    {
        // Admins are super users for all repos
        if (self::isAdmin($userId)) {
            return true;
        }

        $superUserIds = self::getRepositorySuperUserIds($repositoryId);
        return in_array($userId, $superUserIds);
    }

    /**
     * Get repository IDs where user is a super user
     *
     * @param int $userId User ID
     * @return array Repository IDs
     */
    public static function getSuperUserRepositoryIds(int $userId): array
    {
        $repos = [];

        // Get all tenant_repo_{id}_super_users settings
        $settings = DB::table('ahg_settings')
            ->where('setting_key', 'like', 'tenant_repo_%_super_users')
            ->get();

        foreach ($settings as $setting) {
            $userIds = array_filter(array_map('intval', explode(',', $setting->setting_value)));
            if (in_array($userId, $userIds)) {
                // Extract repo ID from key: tenant_repo_{id}_super_users
                preg_match('/tenant_repo_(\d+)_super_users/', $setting->setting_key, $matches);
                if (!empty($matches[1])) {
                    $repos[] = (int) $matches[1];
                }
            }
        }

        return $repos;
    }

    /**
     * Get repository IDs where user is assigned
     *
     * @param int $userId User ID
     * @return array Repository IDs
     */
    public static function getAssignedRepositoryIds(int $userId): array
    {
        $repos = [];

        // Get all tenant_repo_{id}_users settings
        $settings = DB::table('ahg_settings')
            ->where('setting_key', 'like', 'tenant_repo_%_users')
            ->get();

        foreach ($settings as $setting) {
            $userIds = array_filter(array_map('intval', explode(',', $setting->setting_value)));
            if (in_array($userId, $userIds)) {
                // Extract repo ID from key: tenant_repo_{id}_users
                preg_match('/tenant_repo_(\d+)_users/', $setting->setting_key, $matches);
                if (!empty($matches[1])) {
                    $repos[] = (int) $matches[1];
                }
            }
        }

        return $repos;
    }

    /**
     * Get super user IDs for a repository
     *
     * @param int $repositoryId Repository ID
     * @return array User IDs
     */
    public static function getRepositorySuperUserIds(int $repositoryId): array
    {
        $value = DB::table('ahg_settings')
            ->where('setting_key', "tenant_repo_{$repositoryId}_super_users")
            ->value('setting_value');

        if (empty($value)) {
            return [];
        }

        return array_filter(array_map('intval', explode(',', $value)));
    }

    /**
     * Get assigned user IDs for a repository
     *
     * @param int $repositoryId Repository ID
     * @return array User IDs
     */
    public static function getRepositoryUserIds(int $repositoryId): array
    {
        $value = DB::table('ahg_settings')
            ->where('setting_key', "tenant_repo_{$repositoryId}_users")
            ->value('setting_value');

        if (empty($value)) {
            return [];
        }

        return array_filter(array_map('intval', explode(',', $value)));
    }

    /**
     * Check if "View All" mode is active
     *
     * @return bool
     */
    public static function isViewAllMode(): bool
    {
        return self::$viewAllMode;
    }

    /**
     * Get current user ID from session
     *
     * @return int|null
     */
    public static function getCurrentUserId(): ?int
    {
        if (!\sfContext::hasInstance()) {
            return null;
        }

        $user = \sfContext::getInstance()->getUser();
        if (!$user->isAuthenticated()) {
            return null;
        }

        return $user->getAttribute('user_id');
    }

    /**
     * Clear the repositories and tenants cache
     */
    public static function clearCache(): void
    {
        self::$userRepositoriesCache = [];
        self::$userTenantsCache = [];
    }

    /**
     * Apply repository filter to a query builder
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $repositoryColumn Column name for repository_id
     * @return \Illuminate\Database\Query\Builder
     */
    public static function applyRepositoryFilter($query, string $repositoryColumn = 'repository_id')
    {
        // If view all mode or no current repo, don't filter
        if (self::$viewAllMode || self::$currentRepositoryId === null) {
            return $query;
        }

        return $query->where($repositoryColumn, self::$currentRepositoryId);
    }

    /**
     * Get repository name by ID
     *
     * @param int $repositoryId Repository ID
     * @return string|null
     */
    public static function getRepositoryName(int $repositoryId): ?string
    {
        // Repository extends Actor, so name is in actor_i18n
        return DB::table('actor_i18n')
            ->where('id', $repositoryId)
            ->where('culture', \AtomExtensions\Helpers\CultureHelper::getCulture())
            ->value('authorized_form_of_name');
    }
}
