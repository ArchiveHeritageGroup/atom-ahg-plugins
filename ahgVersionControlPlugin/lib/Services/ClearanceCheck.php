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
            // ahgSecurityClearancePlugin not installed — fail OPEN. The plugin
            // documents this in extension.json (suggests, not requires).
            return true;
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
}
