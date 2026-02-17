<?php

namespace AhgMultiTenant\Models;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TenantUser Model
 *
 * Represents the relationship between a user and a tenant with role assignment.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class TenantUser
{
    /** @var string Role: owner - full control over tenant */
    public const ROLE_OWNER = 'owner';

    /** @var string Role: super_user - can manage users and settings */
    public const ROLE_SUPER_USER = 'super_user';

    /** @var string Role: editor - can edit content */
    public const ROLE_EDITOR = 'editor';

    /** @var string Role: contributor - can add content */
    public const ROLE_CONTRIBUTOR = 'contributor';

    /** @var string Role: viewer - read-only access */
    public const ROLE_VIEWER = 'viewer';

    /** @var array Valid role values */
    public const VALID_ROLES = [
        self::ROLE_OWNER,
        self::ROLE_SUPER_USER,
        self::ROLE_EDITOR,
        self::ROLE_CONTRIBUTOR,
        self::ROLE_VIEWER,
    ];

    /** @var array Roles that can manage users */
    public const USER_MANAGEMENT_ROLES = [
        self::ROLE_OWNER,
        self::ROLE_SUPER_USER,
    ];

    /** @var array Roles that can manage tenant settings */
    public const SETTINGS_MANAGEMENT_ROLES = [
        self::ROLE_OWNER,
        self::ROLE_SUPER_USER,
    ];

    /** @var array Role hierarchy (higher index = more permissions) */
    public const ROLE_HIERARCHY = [
        self::ROLE_VIEWER => 0,
        self::ROLE_CONTRIBUTOR => 1,
        self::ROLE_EDITOR => 2,
        self::ROLE_SUPER_USER => 3,
        self::ROLE_OWNER => 4,
    ];

    /** @var string Database table name */
    protected static string $table = 'heritage_tenant_user';

    /** @var int|null */
    public ?int $id = null;

    /** @var int */
    public int $tenantId;

    /** @var int */
    public int $userId;

    /** @var string */
    public string $role = self::ROLE_VIEWER;

    /** @var bool */
    public bool $isPrimary = false;

    /** @var string|null */
    public ?string $assignedAt = null;

    /** @var int|null */
    public ?int $assignedBy = null;

    /**
     * Find tenant user by ID
     *
     * @param int $id
     * @return self|null
     */
    public static function find(int $id): ?self
    {
        $row = DB::table(self::$table)->where('id', $id)->first();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find by tenant and user
     *
     * @param int $tenantId
     * @param int $userId
     * @return self|null
     */
    public static function findByTenantAndUser(int $tenantId, int $userId): ?self
    {
        $row = DB::table(self::$table)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();

        return $row ? self::fromRow($row) : null;
    }

    /**
     * Get all tenants for a user
     *
     * @param int $userId
     * @return array Array of tenant objects with role info
     */
    public static function getTenantsForUser(int $userId): array
    {
        return DB::table(self::$table . ' as tu')
            ->join('heritage_tenant as t', 'tu.tenant_id', '=', 't.id')
            ->where('tu.user_id', $userId)
            ->select(
                't.*',
                'tu.role',
                'tu.is_primary',
                'tu.assigned_at'
            )
            ->orderBy('tu.is_primary', 'desc')
            ->orderBy('t.name')
            ->get()
            ->toArray();
    }

    /**
     * Get all users for a tenant
     *
     * @param int $tenantId
     * @param string|null $role Filter by role
     * @return array
     */
    public static function getUsersForTenant(int $tenantId, ?string $role = null): array
    {
        $query = DB::table(self::$table . ' as tu')
            ->join('user as u', 'tu.user_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('tu.tenant_id', $tenantId)
            ->select(
                'tu.id as assignment_id',
                'u.id',
                'u.username',
                'u.email',
                'ai.authorized_form_of_name as name',
                'tu.role',
                'tu.is_primary',
                'tu.assigned_at',
                'tu.assigned_by'
            );

        if ($role) {
            $query->where('tu.role', $role);
        }

        return $query->orderBy('tu.role')->orderBy('ai.authorized_form_of_name')->get()->toArray();
    }

    /**
     * Get tenant IDs where user has specific role or higher
     *
     * @param int $userId
     * @param string $minimumRole
     * @return array
     */
    public static function getTenantIdsWithMinimumRole(int $userId, string $minimumRole): array
    {
        $minLevel = self::ROLE_HIERARCHY[$minimumRole] ?? 0;

        $rolesAtOrAbove = array_keys(
            array_filter(self::ROLE_HIERARCHY, fn($level) => $level >= $minLevel)
        );

        return DB::table(self::$table)
            ->where('user_id', $userId)
            ->whereIn('role', $rolesAtOrAbove)
            ->pluck('tenant_id')
            ->toArray();
    }

    /**
     * Check if user has role or higher in tenant
     *
     * @param int $userId
     * @param int $tenantId
     * @param string $minimumRole
     * @return bool
     */
    public static function hasMinimumRole(int $userId, int $tenantId, string $minimumRole): bool
    {
        $assignment = self::findByTenantAndUser($tenantId, $userId);

        if (!$assignment) {
            return false;
        }

        $userLevel = self::ROLE_HIERARCHY[$assignment->role] ?? 0;
        $requiredLevel = self::ROLE_HIERARCHY[$minimumRole] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Create a TenantUser from a database row
     *
     * @param object $row
     * @return self
     */
    public static function fromRow(object $row): self
    {
        $tenantUser = new self();
        $tenantUser->id = $row->id;
        $tenantUser->tenantId = $row->tenant_id;
        $tenantUser->userId = $row->user_id;
        $tenantUser->role = $row->role;
        $tenantUser->isPrimary = (bool) $row->is_primary;
        $tenantUser->assignedAt = $row->assigned_at;
        $tenantUser->assignedBy = $row->assigned_by;

        return $tenantUser;
    }

    /**
     * Convert to array for database insert/update
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'role' => $this->role,
            'is_primary' => $this->isPrimary ? 1 : 0,
            'assigned_by' => $this->assignedBy,
        ];
    }

    /**
     * Save to database
     *
     * @return bool
     */
    public function save(): bool
    {
        $data = $this->toArray();

        if ($this->id) {
            return DB::table(self::$table)->where('id', $this->id)->update($data) !== false;
        }

        $data['assigned_at'] = date('Y-m-d H:i:s');
        $this->id = DB::table(self::$table)->insertGetId($data);
        return $this->id > 0;
    }

    /**
     * Delete from database
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        return DB::table(self::$table)->where('id', $this->id)->delete() > 0;
    }

    /**
     * Assign user to tenant
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $role
     * @param int|null $assignedBy
     * @param bool $isPrimary
     * @return self|null
     */
    public static function assign(
        int $tenantId,
        int $userId,
        string $role = self::ROLE_VIEWER,
        ?int $assignedBy = null,
        bool $isPrimary = false
    ): ?self {
        // Check if already assigned
        $existing = self::findByTenantAndUser($tenantId, $userId);

        if ($existing) {
            // Update role if different
            if ($existing->role !== $role || $existing->isPrimary !== $isPrimary) {
                $existing->role = $role;
                $existing->isPrimary = $isPrimary;
                $existing->save();
            }
            return $existing;
        }

        // Create new assignment
        $tenantUser = new self();
        $tenantUser->tenantId = $tenantId;
        $tenantUser->userId = $userId;
        $tenantUser->role = $role;
        $tenantUser->isPrimary = $isPrimary;
        $tenantUser->assignedBy = $assignedBy;

        // If setting as primary, unset other primary assignments for this user
        if ($isPrimary) {
            DB::table(self::$table)
                ->where('user_id', $userId)
                ->where('is_primary', 1)
                ->update(['is_primary' => 0]);
        }

        return $tenantUser->save() ? $tenantUser : null;
    }

    /**
     * Remove user from tenant
     *
     * @param int $tenantId
     * @param int $userId
     * @return bool
     */
    public static function remove(int $tenantId, int $userId): bool
    {
        return DB::table(self::$table)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * Update user role in tenant
     *
     * @param int $tenantId
     * @param int $userId
     * @param string $newRole
     * @return bool
     */
    public static function updateRole(int $tenantId, int $userId, string $newRole): bool
    {
        if (!in_array($newRole, self::VALID_ROLES)) {
            return false;
        }

        return DB::table(self::$table)
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->update(['role' => $newRole]) !== false;
    }

    /**
     * Set user's primary tenant
     *
     * @param int $userId
     * @param int $tenantId
     * @return bool
     */
    public static function setPrimaryTenant(int $userId, int $tenantId): bool
    {
        // Unset all primary flags for user
        DB::table(self::$table)
            ->where('user_id', $userId)
            ->update(['is_primary' => 0]);

        // Set the new primary
        return DB::table(self::$table)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->update(['is_primary' => 1]) > 0;
    }

    /**
     * Get user's primary tenant
     *
     * @param int $userId
     * @return object|null
     */
    public static function getPrimaryTenant(int $userId): ?object
    {
        return DB::table(self::$table . ' as tu')
            ->join('heritage_tenant as t', 'tu.tenant_id', '=', 't.id')
            ->where('tu.user_id', $userId)
            ->where('tu.is_primary', 1)
            ->select('t.*', 'tu.role')
            ->first();
    }

    /**
     * Check if user can manage other users in tenant
     *
     * @return bool
     */
    public function canManageUsers(): bool
    {
        return in_array($this->role, self::USER_MANAGEMENT_ROLES);
    }

    /**
     * Check if user can manage tenant settings
     *
     * @return bool
     */
    public function canManageSettings(): bool
    {
        return in_array($this->role, self::SETTINGS_MANAGEMENT_ROLES);
    }

    /**
     * Get role display label
     *
     * @param string $role
     * @return string
     */
    public static function getRoleLabel(string $role): string
    {
        return match ($role) {
            self::ROLE_OWNER => 'Owner',
            self::ROLE_SUPER_USER => 'Super User',
            self::ROLE_EDITOR => 'Editor',
            self::ROLE_CONTRIBUTOR => 'Contributor',
            self::ROLE_VIEWER => 'Viewer',
            default => ucfirst($role),
        };
    }

    /**
     * Get all roles with labels
     *
     * @return array
     */
    public static function getRolesWithLabels(): array
    {
        $result = [];
        foreach (self::VALID_ROLES as $role) {
            $result[$role] = self::getRoleLabel($role);
        }
        return $result;
    }

    /**
     * Get available users not assigned to tenant
     *
     * @param int $tenantId
     * @return array
     */
    public static function getAvailableUsers(int $tenantId): array
    {
        $assignedUserIds = DB::table(self::$table)
            ->where('tenant_id', $tenantId)
            ->pluck('user_id')
            ->toArray();

        $query = DB::table('user as u')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('u.active', 1)
            ->select('u.id', 'u.username', 'u.email', 'ai.authorized_form_of_name as name')
            ->orderBy('ai.authorized_form_of_name');

        if (!empty($assignedUserIds)) {
            $query->whereNotIn('u.id', $assignedUserIds);
        }

        return $query->get()->toArray();
    }
}
