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
}
