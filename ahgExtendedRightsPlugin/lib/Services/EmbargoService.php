<?php

namespace ahgExtendedRightsPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * EmbargoService - Refactored to use rights_embargo table
 * Consolidated from dual embargo/rights_embargo tables
 */
class EmbargoService
{
    public function getEmbargo(int $embargoId): ?object
    {
        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('ei.id', '=', 'e.id')
                    ->where('ei.culture', '=', 'en');
            })
            ->where('e.id', $embargoId)
            ->select(['e.*', 'ei.reason_note as reason', 'ei.internal_note as notes'])
            ->first();
    }

    public function getObjectEmbargoes(int $objectId): Collection
    {
        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('ei.id', '=', 'e.id')
                    ->where('ei.culture', '=', 'en');
            })
            ->where('e.object_id', $objectId)
            ->orderByDesc('e.created_at')
            ->select(['e.*', 'ei.reason_note as reason', 'ei.internal_note as notes'])
            ->get();
    }

    public function getActiveEmbargo(int $objectId): ?object
    {
        $now = date('Y-m-d');
        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('ei.id', '=', 'e.id')
                    ->where('ei.culture', '=', 'en');
            })
            ->where('e.object_id', $objectId)
            ->where('e.status', 'active')
            ->where('e.start_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('e.end_date')
                    ->orWhere('e.end_date', '>=', $now);
            })
            ->select(['e.*', 'ei.reason_note as reason'])
            ->first();
    }

    public function getActiveEmbargoes(): Collection
    {
        $now = date('Y-m-d');
        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('ei.id', '=', 'e.id')
                    ->where('ei.culture', '=', 'en');
            })
            ->leftJoin('information_object as io', 'e.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('e.status', 'active')
            ->where('e.start_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('e.end_date')
                    ->orWhere('e.end_date', '>=', $now);
            })
            ->select(['e.*', 'ei.reason_note as reason', 'ioi.title as object_title', 'slug.slug as object_slug'])
            ->orderByDesc('e.created_at')
            ->get();
    }

    public function getExpiringEmbargoes(int $days = 30): Collection
    {
        $now = date('Y-m-d');
        $future = date('Y-m-d', strtotime("+{$days} days"));

        return DB::table('rights_embargo as e')
            ->leftJoin('rights_embargo_i18n as ei', function ($join) {
                $join->on('ei.id', '=', 'e.id')
                    ->where('ei.culture', '=', 'en');
            })
            ->leftJoin('information_object as io', 'e.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('e.status', 'active')
            ->where('e.auto_release', true)
            ->whereNotNull('e.end_date')
            ->whereBetween('e.end_date', [$now, $future])
            ->select(['e.*', 'ei.reason_note as reason', 'ioi.title as object_title', 'slug.slug as object_slug'])
            ->orderBy('e.end_date')
            ->get();
    }

    public function isEmbargoed(int $objectId): bool
    {
        return $this->getActiveEmbargo($objectId) !== null;
    }

    public function checkAccess(int $objectId, ?int $userId = null, ?string $ipAddress = null): bool
    {
        $embargo = $this->getActiveEmbargo($objectId);
        if (!$embargo) {
            return true;
        }

        // Check embargo_exception table (still used for granular access)
        $now = date('Y-m-d H:i:s');
        $exceptions = DB::table('embargo_exception')
            ->where('embargo_id', $embargo->id)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->get();

        foreach ($exceptions as $exc) {
            if ($exc->exception_type === 'user' && $userId === $exc->exception_id) {
                return true;
            }
            if ($exc->exception_type === 'ip_range' && $ipAddress) {
                if ($this->isIpInRange($ipAddress, $exc->ip_range_start, $exc->ip_range_end)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function isIpInRange(string $ip, string $start, string $end): bool
    {
        $ipLong = ip2long($ip);
        return $ipLong >= ip2long($start) && $ipLong <= ip2long($end);
    }

    public function createEmbargo(int $objectId, array $data, ?int $userId = null): int
    {
        $now = date('Y-m-d H:i:s');
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $status = strtotime($startDate) <= time() ? 'active' : 'pending';

        // Map embargo_type values
        $embargoType = $data['embargo_type'] ?? 'full';
        if ($embargoType === 'digital_object') {
            $embargoType = 'digital_only';
        }

        // Map reason to enum if possible
        $reasonEnum = $this->mapReasonToEnum($data['reason'] ?? null);

        $embargoId = DB::table('rights_embargo')->insertGetId([
            'object_id' => $objectId,
            'embargo_type' => $embargoType,
            'reason' => $reasonEnum,
            'start_date' => $startDate,
            'end_date' => $data['end_date'] ?? null,
            'auto_release' => !($data['is_perpetual'] ?? false),
            'status' => $status,
            'created_by' => $userId,
            'notify_before_days' => $data['notify_days_before'] ?? 30,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Insert i18n for notes
        if (!empty($data['reason']) || !empty($data['notes'])) {
            DB::table('rights_embargo_i18n')->insert([
                'id' => $embargoId,
                'culture' => 'en',
                'reason_note' => $data['reason'] ?? null,
                'internal_note' => $data['notes'] ?? null,
            ]);
        }

        return $embargoId;
    }

    public function updateEmbargo(int $embargoId, array $data, ?int $userId = null): bool
    {
        $now = date('Y-m-d H:i:s');

        $updateData = [
            'updated_at' => $now,
        ];

        if (isset($data['embargo_type'])) {
            $embargoType = $data['embargo_type'];
            if ($embargoType === 'digital_object') {
                $embargoType = 'digital_only';
            }
            $updateData['embargo_type'] = $embargoType;
        }
        if (isset($data['start_date'])) $updateData['start_date'] = $data['start_date'];
        if (isset($data['end_date'])) $updateData['end_date'] = $data['end_date'];
        if (isset($data['is_perpetual'])) $updateData['auto_release'] = !$data['is_perpetual'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['notify_days_before'])) $updateData['notify_before_days'] = $data['notify_days_before'];
        if (isset($data['reason'])) $updateData['reason'] = $this->mapReasonToEnum($data['reason']);

        $updated = DB::table('rights_embargo')
            ->where('id', $embargoId)
            ->update($updateData) > 0;

        // Update i18n
        if (!empty($data['reason']) || !empty($data['notes'])) {
            DB::table('rights_embargo_i18n')
                ->updateOrInsert(
                    ['id' => $embargoId, 'culture' => 'en'],
                    [
                        'reason_note' => $data['reason'] ?? null,
                        'internal_note' => $data['notes'] ?? null,
                    ]
                );
        }

        return $updated;
    }

    public function liftEmbargo(int $embargoId, ?string $reason = null, ?int $userId = null): bool
    {
        return DB::table('rights_embargo')
            ->where('id', $embargoId)
            ->update([
                'status' => 'lifted',
                'lifted_by' => $userId,
                'lifted_at' => date('Y-m-d H:i:s'),
                'lift_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Map free-text reason to enum value
     */
    protected function mapReasonToEnum(?string $reason): string
    {
        if (empty($reason)) {
            return 'other';
        }
        
        $reason = strtolower($reason);
        $mappings = [
            'donor' => 'donor_restriction',
            'copyright' => 'copyright',
            'privacy' => 'privacy',
            'legal' => 'legal',
            'commercial' => 'commercial',
            'research' => 'research',
            'cultural' => 'cultural',
            'security' => 'security',
        ];
        
        foreach ($mappings as $keyword => $enum) {
            if (strpos($reason, $keyword) !== false) {
                return $enum;
            }
        }
        
        return 'other';
    }

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues, ?string $title = null): void
    {
        try {
            $userId = null;
            $username = null;
            if (class_exists('sfContext') && \sfContext::hasInstance()) {
                $user = \sfContext::getInstance()->getUser();
                if ($user && $user->isAuthenticated()) {
                    $userId = $user->getAttribute('user_id');
                    if ($userId) {
                        $userRecord = \Illuminate\Database\Capsule\Manager::table('user')->where('id', $userId)->first();
                        $username = $userRecord->username ?? null;
                    }
                }
            }
            $changedFields = [];
            foreach ($newValues as $key => $val) {
                if (($oldValues[$key] ?? null) !== $val) $changedFields[] = $key;
            }
            if ($action === 'delete') $changedFields = array_keys($oldValues);
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
            \Illuminate\Database\Capsule\Manager::table('ahg_audit_log')->insert([
                'uuid' => $uuid, 'user_id' => $userId, 'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'session_id' => session_id() ?: null, 'action' => $action,
                'entity_type' => $entityType, 'entity_id' => $entityId, 'entity_title' => $title,
                'module' => $this->auditModule ?? 'ahgExtendedRightsPlugin', 'action_name' => $action,
                'old_values' => !empty($oldValues) ? json_encode($oldValues) : null,
                'new_values' => !empty($newValues) ? json_encode($newValues) : null,
                'changed_fields' => !empty($changedFields) ? json_encode($changedFields) : null,
                'status' => 'success', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log("AUDIT ERROR: " . $e->getMessage());
        }
    }
    protected string $auditModule = 'ahgExtendedRightsPlugin';

    // ==========================================
    // ACCESS CONTROL METHODS
    // ==========================================

    /**
     * Embargo type constants for access control
     */
    public const TYPE_FULL = 'full';
    public const TYPE_METADATA_ONLY = 'metadata_only';
    public const TYPE_DIGITAL_ONLY = 'digital_only';
    public const TYPE_PARTIAL = 'partial';

    /**
     * Cache for embargo lookups (per-request)
     */
    private static array $accessCache = [];

    /**
     * Check if user can access the record at all
     * Returns false for 'full' embargo (unless user has edit permission)
     */
    public function canAccessRecord(int $objectId, ?object $user = null): bool
    {
        if ($this->userCanBypassEmbargo($objectId, $user)) {
            return true;
        }

        $embargo = $this->getActiveEmbargo($objectId);
        
        if (!$embargo) {
            return true;
        }

        // Full embargo blocks all access for public
        return $embargo->embargo_type !== self::TYPE_FULL;
    }

    /**
     * Check if user can view metadata
     */
    public function canViewMetadata(int $objectId, ?object $user = null): bool
    {
        if ($this->userCanBypassEmbargo($objectId, $user)) {
            return true;
        }

        $embargo = $this->getActiveEmbargo($objectId);
        
        if (!$embargo) {
            return true;
        }

        // Full embargo blocks metadata
        return $embargo->embargo_type !== self::TYPE_FULL;
    }

    /**
     * Check if user can view thumbnail
     */
    public function canViewThumbnail(int $objectId, ?object $user = null): bool
    {
        if ($this->userCanBypassEmbargo($objectId, $user)) {
            return true;
        }

        $embargo = $this->getActiveEmbargo($objectId);
        
        if (!$embargo) {
            return true;
        }

        // full and metadata_only block thumbnails
        return !in_array($embargo->embargo_type, [self::TYPE_FULL, self::TYPE_METADATA_ONLY]);
    }

    /**
     * Check if user can view full digital object (master/reference)
     */
    public function canViewDigitalObject(int $objectId, ?object $user = null): bool
    {
        if ($this->userCanBypassEmbargo($objectId, $user)) {
            return true;
        }

        $embargo = $this->getActiveEmbargo($objectId);
        
        if (!$embargo) {
            return true;
        }

        // All embargo types except partial block full digital object
        return $embargo->embargo_type === self::TYPE_PARTIAL;
    }

    /**
     * Check if user can download
     */
    public function canDownload(int $objectId, ?object $user = null): bool
    {
        if ($this->userCanBypassEmbargo($objectId, $user)) {
            return true;
        }

        $embargo = $this->getActiveEmbargo($objectId);
        
        if (!$embargo) {
            return true;
        }

        // All embargo types block downloads
        return false;
    }

    /**
     * Check if user has permission to bypass embargo
     */
    private function userCanBypassEmbargo(int $objectId, ?object $user): bool
    {
        if (!$user) {
            return false;
        }

        // Check if user is authenticated
        if (method_exists($user, 'isAuthenticated') && !$user->isAuthenticated()) {
            return false;
        }

        // Check if user has update permission on the object
        try {
            $resource = \QubitInformationObject::getById($objectId);
            if ($resource && \QubitAcl::check($resource, 'update')) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Check if user is admin
        if (method_exists($user, 'isAdministrator') && $user->isAdministrator()) {
            return true;
        }

        return false;
    }

    /**
     * Get embargo info for display (public-safe)
     */
    public function getEmbargoDisplayInfo(int $objectId): ?array
    {
        $embargo = $this->getActiveEmbargo($objectId);
        
        if (!$embargo) {
            return null;
        }

        $typeLabels = [
            self::TYPE_FULL => 'Full Access Restriction',
            self::TYPE_METADATA_ONLY => 'Digital Content Restricted',
            self::TYPE_DIGITAL_ONLY => 'Download Restricted',
            self::TYPE_PARTIAL => 'Partial Restriction',
        ];

        return [
            'type' => $embargo->embargo_type,
            'type_label' => $typeLabels[$embargo->embargo_type] ?? 'Access Restricted',
            'public_message' => $embargo->reason ?? null,
            'end_date' => $embargo->end_date,
            'is_perpetual' => !$embargo->auto_release,
        ];
    }

    /**
     * Bulk filter - get IDs that are NOT under full embargo
     * Useful for filtering search results
     */
    public function filterAccessibleIds(array $objectIds, ?object $user = null): array
    {
        if (empty($objectIds)) {
            return [];
        }

        // If user can bypass, return all IDs
        if ($user && method_exists($user, 'isAuthenticated') && $user->isAuthenticated()) {
            if (\QubitAcl::check('QubitInformationObject', 'update')) {
                return $objectIds;
            }
        }

        $now = date('Y-m-d');

        // Get IDs under full embargo
        $embargoedIds = DB::table('rights_embargo')
            ->whereIn('object_id', $objectIds)
            ->where('status', 'active')
            ->where('embargo_type', self::TYPE_FULL)
            ->where('start_date', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $now);
            })
            ->pluck('object_id')
            ->toArray();

        // Return IDs NOT under full embargo
        return array_values(array_diff($objectIds, $embargoedIds));
    }

    /**
     * Get embargo statistics for admin dashboard
     */
    public function getStatistics(): array
    {
        $stats = DB::table('rights_embargo')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN embargo_type = 'full' AND status = 'active' THEN 1 ELSE 0 END) as full_active,
                SUM(CASE WHEN embargo_type = 'metadata_only' AND status = 'active' THEN 1 ELSE 0 END) as metadata_only_active,
                SUM(CASE WHEN embargo_type = 'digital_only' AND status = 'active' THEN 1 ELSE 0 END) as digital_only_active,
                SUM(CASE WHEN end_date IS NOT NULL AND end_date < CURDATE() AND status = 'active' THEN 1 ELSE 0 END) as expired_not_lifted
            ")
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'active' => (int) ($stats->active ?? 0),
            'by_type' => [
                'full' => (int) ($stats->full_active ?? 0),
                'metadata_only' => (int) ($stats->metadata_only_active ?? 0),
                'digital_only' => (int) ($stats->digital_only_active ?? 0),
            ],
            'expired_not_lifted' => (int) ($stats->expired_not_lifted ?? 0),
        ];
    }

    /**
     * Clear access cache
     */
    public static function clearCache(): void
    {
        self::$accessCache = [];
    }
}
