<?php

namespace AhgShareLink\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ClearanceCheck — record-level security clearance lookup for share-link
 * issuance. Self-contained — no dependency on ahgVersionControlPlugin or
 * ahgSecurityClearancePlugin classes, only on the schema if present.
 *
 * Rule: user clearance level must be >= entity classification level.
 * Administrators bypass. Unclassified records pass. Schema-missing → fail open.
 *
 * @phase C
 */
class ClearanceCheck
{
    private const ACL_GROUP_ADMINISTRATOR = 100;

    public function canUserIssueLink(?int $userId, int $entityId): bool
    {
        if ($userId === null) {
            return false; // Anonymous never issues.
        }
        // No clearance plugin installed means nothing is classified.
        if (!$this->clearanceSchemaPresent()) {
            return true;
        }

        try {
            if ($this->userIsAdministrator($userId)) {
                return true;
            }
            $entityLevel = $this->resolveEntityClassificationLevel($entityId);
            if ($entityLevel === null) {
                return true; // Unclassified record.
            }
            return $this->resolveUserClearanceLevel($userId) >= $entityLevel;
        } catch (\Throwable $e) {
            // Fail CLOSED.
            //
            // The absent-plugin case is handled above by
            // clearanceSchemaPresent(), so reaching here means the schema
            // exists and the lookup failed anyway - a bad row, a dropped
            // column, a connection lost mid-query. That is a fault, and a
            // clearance check that cannot complete has not granted anything.
            error_log('ClearanceCheck: clearance lookup failed, denying: '.$e->getMessage());

            return false;
        }
    }

    public function resolveEntityClassificationLevel(int $entityId): ?int
    {
        $level = DB::table('object_security_classification as osc')
            ->join('security_classification as sc', 'sc.id', '=', 'osc.classification_id')
            ->where('osc.object_id', $entityId)
            ->where('osc.active', 1)
            ->value('sc.level');
        return $level !== null ? (int) $level : null;
    }

    public function explainDenial(?int $userId, int $entityId): string
    {
        try {
            $level = $this->resolveEntityClassificationLevel($entityId);
            $row = DB::table('security_classification')->where('level', $level)->first();
            $entityClass = $row ? "{$row->name} (level {$level})" : "level {$level}";
            $userLevel = $userId !== null ? $this->resolveUserClearanceLevel($userId) : 0;
            return "This record is classified {$entityClass}; your clearance level is {$userLevel}. You cannot issue a share link for it.";
        } catch (\Throwable $e) {
            return 'Insufficient security clearance to issue a share link for this record.';
        }
    }

    private function userIsAdministrator(int $userId): bool
    {
        try {
            return DB::table('acl_user_group')
                ->where('user_id', $userId)
                ->where('group_id', self::ACL_GROUP_ADMINISTRATOR)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function resolveUserClearanceLevel(int $userId): int
    {
        $today = date('Y-m-d');
        $level = DB::table('user_security_clearance as usc')
            ->join('security_classification as sc', 'sc.id', '=', 'usc.classification_id')
            ->where('usc.user_id', $userId)
            ->where(function ($q) use ($today) {
                $q->whereNull('usc.expires_at')->orWhere('usc.expires_at', '>=', $today);
            })
            ->max('sc.level');
        return (int) ($level ?? 0);
    }

    /**
     * Is the clearance schema present at all?
     *
     * ahgSecurityClearancePlugin is a suggested dependency, not a required one.
     * Where it is absent nothing is classified, so allowing is correct. Where it
     * is present, a failure is a fault and must not be read as permission.
     *
     * Checked explicitly rather than inferred from an exception, because the
     * broad catch this replaces could not tell the two apart: a missing table
     * and a genuine bug both returned "allowed".
     */
    private function clearanceSchemaPresent(): bool
    {
        static $present = null;

        if (null !== $present) {
            return $present;
        }

        try {
            $schema = DB::schema();
            $present = $schema->hasTable('security_classification')
                && $schema->hasTable(self::ENTITY_OBJECT_TABLE)
                && $schema->hasTable('user_security_clearance');
        } catch (\Throwable $e) {
            // Cannot even ask. Treat as absent: this is the same position as a
            // site without the plugin, and refusing everything because the
            // schema inspector failed would be its own outage.
            $present = false;
        }

        return $present;
    }
}
