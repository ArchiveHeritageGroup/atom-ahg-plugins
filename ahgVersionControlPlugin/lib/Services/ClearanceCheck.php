<?php

namespace AhgVersionControl\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ClearanceCheck — focused single-record clearance lookup for restore guards.
 *
 * Uses the schema established by ahgSecurityClearancePlugin:
 *
 *   security_classification        — code, level (tinyint), name, ...
 *   object_security_classification — links {object_id, classification_id, active}
 *   user_security_clearance        — links {user_id, classification_id, expires_at}
 *
 * Rules (Phase J — aligned with build plan):
 *   1) Administrators always pass.
 *   2) If the entity has NO active classification → no restriction; pass.
 *   3) Otherwise the user's effective clearance level must be >= the entity's
 *      classification level (higher level = stricter). The clearance level
 *      taken from the CURRENT classification of the entity, not from the
 *      historical version being restored — a security upgrade is not
 *      reversible by a lower-cleared user.
 *
 * CLI / system context (userId === null) → always pass; the CLI is admin-equivalent.
 *
 * @phase J
 */
class ClearanceCheck
{
    /** Map entity_type → the column name in object_security_classification. */
    private const ENTITY_OBJECT_TABLE = 'object_security_classification';

    /**
     * Can the user restore this entity to a prior version?
     */
    public function canUserRestore(?int $userId, int $entityId): bool
    {
        if ($userId === null) {
            return true; // CLI/system context.
        }

        // No clearance plugin installed means nothing is classified.
        if (!$this->clearanceSchemaPresent()) {
            return true;
        }

        try {
            $isAdmin = $this->userIsAdministrator($userId);
            if ($isAdmin) {
                return true;
            }

            $entityLevel = $this->resolveEntityClassificationLevel($entityId);
            if ($entityLevel === null) {
                return true; // No classification on the record.
            }

            $userLevel = $this->resolveUserClearanceLevel($userId);
            return $userLevel >= $entityLevel;
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

    /**
     * Returns a friendly reason for denial, or null when allowed.
     */
    public function explainDenial(?int $userId, int $entityId): ?string
    {
        if ($this->canUserRestore($userId, $entityId)) {
            return null;
        }
        try {
            $level = $this->resolveEntityClassificationLevel($entityId);
            $row = DB::table('security_classification')->where('level', $level)->first();
            $entityClass = $row ? "{$row->name} (level {$level})" : "level {$level}";
            $userLevel = $userId !== null ? $this->resolveUserClearanceLevel($userId) : 0;
            return "This record is classified {$entityClass}; your clearance level is {$userLevel}. Restore is not permitted.";
        } catch (\Throwable $e) {
            return 'Insufficient security clearance to restore this record.';
        }
    }

    // ------------------------------------------------------------------

    /** AtoM administrator ACL group id. */
    private const ACL_GROUP_ADMINISTRATOR = 100;

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

    /**
     * Returns null when the entity has no active classification.
     */
    private function resolveEntityClassificationLevel(int $entityId): ?int
    {
        $row = DB::table(self::ENTITY_OBJECT_TABLE . ' as osc')
            ->join('security_classification as sc', 'sc.id', '=', 'osc.classification_id')
            ->where('osc.object_id', $entityId)
            ->where('osc.active', 1)
            ->value('sc.level');
        return $row !== null ? (int) $row : null;
    }

    /**
     * Returns the user's max effective clearance level (0 = no clearance).
     * Expired clearances are excluded.
     */
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
