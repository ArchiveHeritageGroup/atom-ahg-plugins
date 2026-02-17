<?php

namespace AhgMultiTenant\Models;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Tenant Model
 *
 * Represents a tenant organization in the multi-tenancy system.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class Tenant
{
    /** @var string Tenant status: active */
    public const STATUS_ACTIVE = 'active';

    /** @var string Tenant status: suspended */
    public const STATUS_SUSPENDED = 'suspended';

    /** @var string Tenant status: trial */
    public const STATUS_TRIAL = 'trial';

    /** @var array Valid status values */
    public const VALID_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_TRIAL,
    ];

    /** @var string Database table name */
    protected static string $table = 'heritage_tenant';

    /** @var int|null */
    public ?int $id = null;

    /** @var string */
    public string $code;

    /** @var string */
    public string $name;

    /** @var string|null */
    public ?string $domain = null;

    /** @var string|null */
    public ?string $subdomain = null;

    /** @var array|null */
    public ?array $settings = null;

    /** @var string */
    public string $status = self::STATUS_TRIAL;

    /** @var string|null */
    public ?string $trialEndsAt = null;

    /** @var string|null */
    public ?string $suspendedAt = null;

    /** @var string|null */
    public ?string $suspendedReason = null;

    /** @var int|null */
    public ?int $repositoryId = null;

    /** @var string|null */
    public ?string $contactName = null;

    /** @var string|null */
    public ?string $contactEmail = null;

    /** @var string|null */
    public ?string $createdAt = null;

    /** @var string|null */
    public ?string $updatedAt = null;

    /** @var int|null */
    public ?int $createdBy = null;

    /**
     * Find tenant by ID
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
     * Find tenant by code
     *
     * @param string $code
     * @return self|null
     */
    public static function findByCode(string $code): ?self
    {
        $row = DB::table(self::$table)->where('code', $code)->first();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find tenant by repository ID
     *
     * @param int $repositoryId
     * @return self|null
     */
    public static function findByRepository(int $repositoryId): ?self
    {
        $row = DB::table(self::$table)->where('repository_id', $repositoryId)->first();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find tenant by domain
     *
     * @param string $domain
     * @return self|null
     */
    public static function findByDomain(string $domain): ?self
    {
        $row = DB::table(self::$table)->where('domain', $domain)->first();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Find tenant by subdomain
     *
     * @param string $subdomain
     * @return self|null
     */
    public static function findBySubdomain(string $subdomain): ?self
    {
        $row = DB::table(self::$table)->where('subdomain', $subdomain)->first();
        return $row ? self::fromRow($row) : null;
    }

    /**
     * Get all tenants
     *
     * @param array $filters Optional filters (status, search)
     * @return array
     */
    public static function all(array $filters = []): array
    {
        $query = DB::table(self::$table)->orderBy('name');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search)
                  ->orWhere('contact_email', 'like', $search);
            });
        }

        $rows = $query->get()->toArray();
        return array_map(fn($row) => self::fromRow($row), $rows);
    }

    /**
     * Get all active tenants
     *
     * @return array
     */
    public static function getActive(): array
    {
        return self::all(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Create a Tenant from a database row
     *
     * @param object $row
     * @return self
     */
    public static function fromRow(object $row): self
    {
        $tenant = new self();
        $tenant->id = $row->id;
        $tenant->code = $row->code;
        $tenant->name = $row->name;
        $tenant->domain = $row->domain;
        $tenant->subdomain = $row->subdomain;
        $tenant->settings = $row->settings ? json_decode($row->settings, true) : null;
        $tenant->status = $row->status;
        $tenant->trialEndsAt = $row->trial_ends_at;
        $tenant->suspendedAt = $row->suspended_at;
        $tenant->suspendedReason = $row->suspended_reason;
        $tenant->repositoryId = $row->repository_id;
        $tenant->contactName = $row->contact_name;
        $tenant->contactEmail = $row->contact_email;
        $tenant->createdAt = $row->created_at;
        $tenant->updatedAt = $row->updated_at;
        $tenant->createdBy = $row->created_by;

        return $tenant;
    }

    /**
     * Convert to array for database insert/update
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'domain' => $this->domain,
            'subdomain' => $this->subdomain,
            'settings' => $this->settings ? json_encode($this->settings) : null,
            'status' => $this->status,
            'trial_ends_at' => $this->trialEndsAt,
            'suspended_at' => $this->suspendedAt,
            'suspended_reason' => $this->suspendedReason,
            'repository_id' => $this->repositoryId,
            'contact_name' => $this->contactName,
            'contact_email' => $this->contactEmail,
            'created_by' => $this->createdBy,
        ];
    }

    /**
     * Save tenant to database
     *
     * @return bool
     */
    public function save(): bool
    {
        $data = $this->toArray();

        if ($this->id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return DB::table(self::$table)->where('id', $this->id)->update($data) !== false;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->id = DB::table(self::$table)->insertGetId($data);
        return $this->id > 0;
    }

    /**
     * Delete tenant from database
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
     * Check if tenant is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant is suspended
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if tenant is on trial
     *
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL;
    }

    /**
     * Check if trial has expired
     *
     * @return bool
     */
    public function isTrialExpired(): bool
    {
        if (!$this->isTrial() || !$this->trialEndsAt) {
            return false;
        }

        return strtotime($this->trialEndsAt) < time();
    }

    /**
     * Check if tenant can be accessed (active or valid trial)
     *
     * @return bool
     */
    public function canAccess(): bool
    {
        if ($this->isActive()) {
            return true;
        }

        if ($this->isTrial() && !$this->isTrialExpired()) {
            return true;
        }

        return false;
    }

    /**
     * Get a specific setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set a specific setting value
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setSetting(string $key, $value): self
    {
        if ($this->settings === null) {
            $this->settings = [];
        }
        $this->settings[$key] = $value;
        return $this;
    }

    /**
     * Get user count for this tenant
     *
     * @return int
     */
    public function getUserCount(): int
    {
        return DB::table('heritage_tenant_user')
            ->where('tenant_id', $this->id)
            ->count();
    }

    /**
     * Get users for this tenant
     *
     * @param string|null $role Filter by role
     * @return array
     */
    public function getUsers(?string $role = null): array
    {
        $query = DB::table('heritage_tenant_user as tu')
            ->join('user as u', 'tu.user_id', '=', 'u.id')
            ->leftJoin('actor_i18n as ai', function ($join) {
                $join->on('u.id', '=', 'ai.id')
                    ->where('ai.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->where('tu.tenant_id', $this->id)
            ->select(
                'tu.id as assignment_id',
                'u.id',
                'u.username',
                'u.email',
                'ai.authorized_form_of_name as name',
                'tu.role',
                'tu.is_primary',
                'tu.assigned_at'
            );

        if ($role) {
            $query->where('tu.role', $role);
        }

        return $query->orderBy('tu.role')->orderBy('ai.authorized_form_of_name')->get()->toArray();
    }

    /**
     * Check if code is unique
     *
     * @param string $code
     * @param int|null $excludeId
     * @return bool
     */
    public static function isCodeUnique(string $code, ?int $excludeId = null): bool
    {
        $query = DB::table(self::$table)->where('code', $code);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Generate a unique code from name
     *
     * @param string $name
     * @return string
     */
    public static function generateCode(string $name): string
    {
        // Convert to lowercase, replace spaces with hyphens, remove special chars
        $code = strtolower(trim($name));
        $code = preg_replace('/[^a-z0-9]+/', '-', $code);
        $code = trim($code, '-');

        // Ensure uniqueness
        $baseCode = $code;
        $counter = 1;

        while (!self::isCodeUnique($code)) {
            $code = $baseCode . '-' . $counter;
            $counter++;
        }

        return $code;
    }
}
