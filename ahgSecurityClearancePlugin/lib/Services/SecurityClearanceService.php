<?php
require_once dirname(__FILE__)."/../SecurityConstants.php";

/**
 * Security Clearance Service.
 *
 * Comprehensive security classification and access control service using Laravel Query Builder.
 * Implements hierarchical clearance levels, compartmentalised access, 2FA verification,
 * declassification scheduling, and complete audit logging.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class SecurityClearanceService
{
    /** @var array Cached classification levels */
    private static array $classificationCache = [];

    // =========================================================================
    // Classification Level Management
    // =========================================================================

    /**
     * Get all active classification levels.
     */
    public static function getClassificationLevels(): array
    {
        if (empty(self::$classificationCache)) {
            self::$classificationCache = DB::table('security_classification')
                ->where('active', 1)
                ->orderBy('level', 'asc')
                ->get()
                ->toArray();
        }

        return self::$classificationCache;
    }

    /**
     * Get classification by ID.
     */
    public static function getClassification(int $id): ?object
    {
        return DB::table('security_classification')
            ->where('id', $id)
            ->first();
    }

    /**
     * Get classification by code.
     */
    public static function getClassificationByCode(string $code): ?object
    {
        return DB::table('security_classification')
            ->where('code', $code)
            ->where('active', 1)
            ->first();
    }

    /**
     * Get classification choices for forms.
     */
    public static function getClassificationChoices(): array
    {
        $choices = ['' => '-- Select Classification --'];

        foreach (self::getClassificationLevels() as $level) {
            $choices[$level->id] = $level->name;
        }

        return $choices;
    }

    // =========================================================================
    // User Clearance Management
    // =========================================================================

    /**
     * Get user's current security clearance.
     */
    public static function getUserClearance(int $userId): ?object
    {
        return DB::table('user_security_clearance as usc')
            ->join('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
            ->where('usc.user_id', $userId)
            ->where(function ($query) {
                // Check clearance hasn't expired
                $query->whereNull('usc.expires_at')
                    ->orWhere('usc.expires_at', '>=', date('Y-m-d H:i:s'));
            })
            ->select([
                'usc.*',
                'sc.code',
                'sc.name',
                'sc.name as classificationName',  // Alias for template compatibility
                'sc.level',
                'sc.color',
                'sc.color as classificationColor',  // Alias for template compatibility
                'sc.icon',
                'sc.requires_2fa',
                'sc.watermark_required',
                'sc.download_allowed',
                'sc.print_allowed',
                'sc.copy_allowed',
            ])
            ->first();
    }

    /**
     * Get user's clearance level (integer).
     */
    public static function getUserClearanceLevel(int $userId): int
    {
        $clearance = self::getUserClearance($userId);

        return $clearance ? $clearance->level : 0;
    }

    /**
     * Grant clearance to user.
     */
    public static function grantClearance(int $userId, int $classificationId, array $data, int $grantedBy): bool
    {
        try {
            DB::beginTransaction();

            // Get previous clearance for history
            $previous = DB::table('user_security_clearance')
                ->where('user_id', $userId)
                ->where('active', 1)
                ->first();

            // Deactivate existing clearance
            DB::table('user_security_clearance')
                ->where('user_id', $userId)
                ->update(['active' => 0]);

            // Create new clearance
            DB::table('user_security_clearance')->insert([
                'user_id' => $userId,
                'classification_id' => $classificationId,
                'granted_by' => $grantedBy,
                'granted_date' => $data['granted_date'] ?? date('Y-m-d'),
                'expiry_date' => $data['expiry_date'] ?? null,
                'vetting_reference' => $data['vetting_reference'] ?? null,
                'vetting_date' => $data['vetting_date'] ?? null,
                'vetting_authority' => $data['vetting_authority'] ?? null,
                'notes' => $data['notes'] ?? null,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Determine action type
            $action = 'granted';
            if ($previous) {
                $prevClass = self::getClassification($previous->classification_id);
                $newClass = self::getClassification($classificationId);
                if ($prevClass && $newClass) {
                    $action = $newClass->level > $prevClass->level ? 'upgraded' : 'downgraded';
                }
            }

            // Record history
            self::recordClearanceHistory(
                $userId,
                $previous ? $previous->classification_id : null,
                $classificationId,
                $action,
                $grantedBy,
                $data['notes'] ?? null
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            error_log('SecurityClearance: Grant failed - '.$e->getMessage());

            return false;
        }
    }

    /**
     * Revoke user clearance.
     */
    public static function revokeClearance(int $userId, int $revokedBy, ?string $reason = null): bool
    {
        try {
            DB::beginTransaction();

            $previous = DB::table('user_security_clearance')
                ->where('user_id', $userId)
                ->where('active', 1)
                ->first();

            DB::table('user_security_clearance')
                ->where('user_id', $userId)
                ->update(['active' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

            if ($previous) {
                self::recordClearanceHistory(
                    $userId,
                    $previous->classification_id,
                    null,
                    'revoked',
                    $revokedBy,
                    $reason
                );
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * Process clearance renewal request.
     */
    public static function requestRenewal(int $userId, string $notes = ''): bool
    {
        return DB::table('user_security_clearance')
            ->where('user_id', $userId)
            ->where('active', 1)
            ->update([
                'renewal_requested_date' => date('Y-m-d'),
                'renewal_status' => 'pending',
                'renewal_notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Approve clearance renewal.
     */
    public static function approveRenewal(int $userId, int $approvedBy, ?string $newExpiryDate = null): bool
    {
        try {
            DB::beginTransaction();

            $clearance = DB::table('user_security_clearance')
                ->where('user_id', $userId)
                ->where('active', 1)
                ->first();

            if (!$clearance) {
                DB::rollBack();

                return false;
            }

            // Extend expiry by 1 year if no new date provided
            $expiry = $newExpiryDate ?? date('Y-m-d', strtotime('+1 year'));

            DB::table('user_security_clearance')
                ->where('id', $clearance->id)
                ->update([
                    'expiry_date' => $expiry,
                    'renewal_status' => 'approved',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            self::recordClearanceHistory(
                $userId,
                $clearance->classification_id,
                $clearance->classification_id,
                'renewed',
                $approvedBy,
                "Renewed until $expiry"
            );

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    /**
     * Get expiring clearances.
     */
    public static function getExpiringClearances(int $days = 30): array
    {
        return DB::table('user_security_clearance as usc')
            ->join('user as u', 'usc.user_id', '=', 'u.id')
            ->join('security_classification as sc', 'usc.classification_id', '=', 'sc.id')
            ->where('usc.active', 1)
            ->whereNotNull('usc.expiry_date')
            ->where('usc.expiry_date', '<=', date('Y-m-d', strtotime("+$days days")))
            ->where('usc.expiry_date', '>=', date('Y-m-d'))
            ->select([
                'u.id as user_id',
                'u.username',
                'u.email',
                'sc.code',
                'sc.name as clearance_name',
                'usc.expiry_date',
                'usc.renewal_status',
                DB::raw('DATEDIFF(usc.expiry_date, CURDATE()) as days_remaining'),
            ])
            ->orderBy('usc.expiry_date')
            ->get()
            ->toArray();
    }

    /**
     * Record clearance history.
     */
    private static function recordClearanceHistory(
        int $userId,
        ?int $previousId,
        ?int $newId,
        string $action,
        int $changedBy,
        ?string $reason
    ): void {
        DB::table('security_clearance_history')->insert([
            'user_id' => $userId,
            'previous_classification_id' => $previousId,
            'new_classification_id' => $newId,
            'action' => $action,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // =========================================================================
    // Two-Factor Authentication for Classified Access
    // =========================================================================

    /**
     * Check if user needs 2FA for current clearance level.
     */
    public static function requires2FA(int $userId): bool
    {
        $clearance = self::getUserClearance($userId);

        return $clearance && $clearance->requires_2fa;
    }

    /**
     * Verify user has valid 2FA session.
     */
    public static function has2FASession(int $userId, string $sessionId): bool
    {
        return DB::table('security_2fa_session')
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->exists();
    }

    /**
     * Create 2FA verified session.
     */
    public static function create2FASession(int $userId, string $sessionId, int $hoursValid = 8): bool
    {
        // Remove old sessions for this user
        DB::table('security_2fa_session')
            ->where('user_id', $userId)
            ->delete();

        DB::table('security_2fa_session')->insert([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'verified_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime("+$hoursValid hours")),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Update clearance record
        DB::table('user_security_clearance')
            ->where('user_id', $userId)
            ->where('active', 1)
            ->update([
                'two_factor_verified' => 1,
                'two_factor_verified_at' => date('Y-m-d H:i:s'),
            ]);

        self::logAccess($userId, null, null, null, '2fa_verified', true);

        return true;
    }

    /**
     * Invalidate 2FA session.
     */
    public static function invalidate2FASession(string $sessionId): void
    {
        DB::table('security_2fa_session')
            ->where('session_id', $sessionId)
            ->delete();
    }

    /**
     * Cleanup expired 2FA sessions.
     */
    public static function cleanupExpired2FASessions(): int
    {
        return DB::table('security_2fa_session')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();
    }

    // =========================================================================
    // Object Classification Management
    // =========================================================================

    /**
     * Get object's security classification.
     */
    public static function getObjectClassification(int $objectId): ?object
    {
        return DB::table('object_security_classification as osc')
            ->join('security_classification as sc', 'osc.classification_id', '=', 'sc.id')
            ->where('osc.object_id', $objectId)
            ->select([
                'osc.*',
                'sc.code',
                'sc.name',
                'sc.level',
                'sc.color',
                'sc.icon',
                'sc.requires_2fa',
                'sc.watermark_required',
                'sc.download_allowed',
                'sc.print_allowed',
                'sc.copy_allowed',
            ])
            ->first();
    }

    /**
     * Get effective classification (including inherited).
     */
    public static function getEffectiveClassification(int $objectId): ?object
    {
        // Check direct classification first
        $direct = self::getObjectClassification($objectId);
        if ($direct) {
            return $direct;
        }

        // Check parent hierarchy
        $parent = DB::table('information_object')
            ->where('id', $objectId)
            ->value('parent_id');

        if ($parent && $parent != SecurityConstants::INFORMATION_OBJECT_ROOT_ID) {
            $parentClass = self::getEffectiveClassification($parent);
            if ($parentClass && $parentClass->inherit_to_children) {
                return $parentClass;
            }
        }

        return null;
    }

    /**
     * Classify an object.
     *
     * @return array{success: bool, error: string|null} Result with success flag and optional error message
     */
    public static function classifyObject(int $objectId, int $classificationId, array $data, int $classifiedBy): array
    {
        try {
            // Get the new classification level
            $newClassification = self::getClassification($classificationId);
            if (!$newClassification) {
                return ['success' => false, 'error' => 'Invalid classification level'];
            }

            // ESCALATION CONSTRAINT: Check parent's effective classification
            // Child records cannot have a LOWER classification than their parent
            $parentClassification = self::getParentEffectiveClassification($objectId);
            if ($parentClassification) {
                if ($newClassification->level < $parentClassification->level) {
                    return [
                        'success' => false,
                        'error' => sprintf(
                            'Cannot set classification to "%s" (level %d). Parent record has classification "%s" (level %d). Child records can only escalate to a higher classification level, not lower.',
                            $newClassification->name,
                            $newClassification->level,
                            $parentClassification->name,
                            $parentClassification->level
                        ),
                    ];
                }
            }

            // Remove existing classification
            DB::table('object_security_classification')
                ->where('object_id', $objectId)
                ->delete();

            DB::table('object_security_classification')->insert([
                'object_id' => $objectId,
                'classification_id' => $classificationId,
                'classified_by' => $classifiedBy,
                'classified_date' => date('Y-m-d'),
                'review_date' => $data['review_date'] ?? null,
                'declassify_date' => $data['declassify_date'] ?? null,
                'declassify_to_id' => $data['declassify_to_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'handling_instructions' => $data['handling_instructions'] ?? null,
                'caveats' => $data['caveats'] ?? null,
                'inherit_to_children' => $data['inherit_to_children'] ?? 1,
                'auto_declassify' => $data['auto_declassify'] ?? 0,
                'retention_years' => $data['retention_years'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // Schedule declassification if set
            if (!empty($data['declassify_date']) && !empty($data['auto_declassify'])) {
                self::scheduleDeclassification($objectId, $data['declassify_date'], $classificationId, $data['declassify_to_id']);
            }

            self::logAccess($classifiedBy, $objectId, $classificationId, null, 'classify', true);

            return ['success' => true, 'error' => null];
        } catch (Exception $e) {
            error_log('SecurityClearance: Classification failed - '.$e->getMessage());

            return ['success' => false, 'error' => 'Classification failed: '.$e->getMessage()];
        }
    }

    /**
     * Get the effective classification of an object's parent (for escalation validation).
     * This does NOT include the object's own classification, only its ancestors.
     */
    public static function getParentEffectiveClassification(int $objectId): ?object
    {
        // Get the parent object ID
        $parentId = DB::table('information_object')
            ->where('id', $objectId)
            ->value('parent_id');

        if (!$parentId || $parentId == SecurityConstants::INFORMATION_OBJECT_ROOT_ID) {
            return null; // No parent or parent is root
        }

        // Get parent's effective classification (which may be inherited from grandparent, etc.)
        return self::getEffectiveClassification($parentId);
    }

    /**
     * Declassify an object.
     */
    public static function declassifyObject(int $objectId, ?int $newClassificationId, int $declassifiedBy, ?string $reason = null): bool
    {
        $current = self::getObjectClassification($objectId);

        if (!$current) {
            return false;
        }

        try {
            if ($newClassificationId) {
                // Downgrade to new level
                DB::table('object_security_classification')
                    ->where('object_id', $objectId)
                    ->update([
                        'classification_id' => $newClassificationId,
                        'declassify_date' => null,
                        'auto_declassify' => 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } else {
                // Remove classification entirely
                DB::table('object_security_classification')
                    ->where('object_id', $objectId)
                    ->delete();
            }

            self::logAccess($declassifiedBy, $objectId, $current->classification_id, null, 'declassify', true, $reason);

            // Mark schedule as processed
            DB::table('security_declassification_schedule')
                ->where('object_id', $objectId)
                ->where('processed', 0)
                ->update([
                    'processed' => 1,
                    'processed_at' => date('Y-m-d H:i:s'),
                    'processed_by' => $declassifiedBy,
                ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Schedule automatic declassification.
     */
    public static function scheduleDeclassification(int $objectId, string $scheduledDate, int $fromClassificationId, ?int $toClassificationId = null): void
    {
        // Remove existing schedule
        DB::table('security_declassification_schedule')
            ->where('object_id', $objectId)
            ->where('processed', 0)
            ->delete();

        DB::table('security_declassification_schedule')->insert([
            'object_id' => $objectId,
            'scheduled_date' => $scheduledDate,
            'from_classification_id' => $fromClassificationId,
            'to_classification_id' => $toClassificationId,
            'trigger_type' => 'date',
            'processed' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get objects due for declassification.
     */
    public static function getDueDeclassifications(): array
    {
        return DB::table('security_declassification_schedule as sds')
            ->join('information_object as io', 'sds.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->join('security_classification as sc_from', 'sds.from_classification_id', '=', 'sc_from.id')
            ->leftJoin('security_classification as sc_to', 'sds.to_classification_id', '=', 'sc_to.id')
            ->where('sds.scheduled_date', '<=', date('Y-m-d'))
            ->where('sds.processed', 0)
            ->select([
                'sds.*',
                'io.identifier',
                'ioi.title',
                'sc_from.name as from_classification',
                'sc_to.name as to_classification',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Process due declassifications (for scheduled task).
     */
    public static function processDueDeclassifications(int $systemUserId): int
    {
        $due = self::getDueDeclassifications();
        $processed = 0;

        foreach ($due as $item) {
            if (self::declassifyObject($item->object_id, $item->to_classification_id, $systemUserId, 'Automatic declassification')) {
                ++$processed;
            }
        }

        return $processed;
    }

    // =========================================================================
    // Compartmentalised Access
    // =========================================================================

    /**
     * Get all active compartments.
     */
    public static function getCompartments(): array
    {
        return DB::table('security_compartment')
            ->where('active', 1)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    /**
     * Get user's compartment access.
     */
    public static function getUserCompartments(int $userId): array
    {
        return DB::table('user_compartment_access as uca')
            ->join('security_compartment as sc', 'uca.compartment_id', '=', 'sc.id')
            ->where('uca.user_id', $userId)
            ->where('uca.active', 1)
            ->where(function ($query) {
                $query->whereNull('uca.expiry_date')
                    ->orWhere('uca.expiry_date', '>=', date('Y-m-d'));
            })
            ->select(['uca.*', 'sc.code', 'sc.name', 'sc.requires_briefing'])
            ->get()
            ->toArray();
    }

    /**
     * Check if user has access to compartment.
     */
    public static function hasCompartmentAccess(int $userId, int $compartmentId): bool
    {
        $compartments = self::getUserCompartments($userId);

        foreach ($compartments as $c) {
            if ($c->compartment_id == $compartmentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Grant compartment access.
     */
    public static function grantCompartmentAccess(int $userId, int $compartmentId, array $data, int $grantedBy): bool
    {
        try {
            // Check if user has minimum clearance
            $compartment = DB::table('security_compartment')
                ->where('id', $compartmentId)
                ->first();

            if (!$compartment) {
                return false;
            }

            $userLevel = self::getUserClearanceLevel($userId);
            $minLevel = self::getClassification($compartment->min_clearance_id);

            if ($minLevel && $userLevel < $minLevel->level) {
                return false; // Insufficient clearance
            }

            DB::table('user_compartment_access')->updateOrInsert(
                ['user_id' => $userId, 'compartment_id' => $compartmentId],
                [
                    'granted_by' => $grantedBy,
                    'granted_date' => date('Y-m-d'),
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'briefing_date' => $data['briefing_date'] ?? null,
                    'briefing_reference' => $data['briefing_reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'active' => 1,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Revoke compartment access.
     */
    public static function revokeCompartmentAccess(int $userId, int $compartmentId): bool
    {
        return DB::table('user_compartment_access')
            ->where('user_id', $userId)
            ->where('compartment_id', $compartmentId)
            ->update(['active' => 0, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * Get object's compartments.
     */
    public static function getObjectCompartments(int $objectId): array
    {
        return DB::table('object_compartment as oc')
            ->join('security_compartment as sc', 'oc.compartment_id', '=', 'sc.id')
            ->where('oc.object_id', $objectId)
            ->select(['oc.*', 'sc.code', 'sc.name'])
            ->get()
            ->toArray();
    }

    /**
     * Assign object to compartment.
     */
    public static function assignObjectToCompartment(int $objectId, int $compartmentId, int $assignedBy, ?string $notes = null): bool
    {
        try {
            DB::table('object_compartment')->updateOrInsert(
                ['object_id' => $objectId, 'compartment_id' => $compartmentId],
                [
                    'assigned_by' => $assignedBy,
                    'assigned_date' => date('Y-m-d'),
                    'notes' => $notes,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // Access Control & Checking
    // =========================================================================

    /**
     * Check if user can access object.
     */
    public static function canAccess(int $userId, int $objectId, string $action = 'view'): array
    {
        $result = [
            'allowed' => false,
            'reason' => null,
            'requires_2fa' => false,
            'requires_request' => false,
            'watermark_required' => false,
        ];

        // Get object classification
        $objClass = self::getEffectiveClassification($objectId);

        // No classification = public access
        if (!$objClass) {
            $result['allowed'] = true;

            return $result;
        }

        // Get user clearance
        $userClearance = self::getUserClearance($userId);

        if (!$userClearance) {
            $result['reason'] = 'No security clearance';
            $result['requires_request'] = true;

            return $result;
        }

        // Check clearance level
        if ($userClearance->level < $objClass->level) {
            $result['reason'] = 'Insufficient clearance level';
            $result['requires_request'] = true;

            return $result;
        }

        // Check 2FA requirement
        if ($objClass->requires_2fa) {
            $sessionId = session_id();
            if (!self::has2FASession($userId, $sessionId)) {
                $result['reason'] = 'Two-factor authentication required';
                $result['requires_2fa'] = true;

                return $result;
            }
        }

        // Check compartment access
        $objectCompartments = self::getObjectCompartments($objectId);
        if (!empty($objectCompartments)) {
            $userCompartments = array_column(self::getUserCompartments($userId), 'compartment_id');
            foreach ($objectCompartments as $oc) {
                if (!in_array($oc->compartment_id, $userCompartments)) {
                    $result['reason'] = 'Compartment access required: '.$oc->name;
                    $result['requires_request'] = true;

                    return $result;
                }
            }
        }

        // Check action-specific permissions
        if ('download' === $action && !$objClass->download_allowed) {
            $result['reason'] = 'Downloads not permitted for this classification';

            return $result;
        }

        if ('print' === $action && !$objClass->print_allowed) {
            $result['reason'] = 'Printing not permitted for this classification';

            return $result;
        }

        // Access granted
        $result['allowed'] = true;
        $result['watermark_required'] = (bool) $objClass->watermark_required;

        return $result;
    }

    // =========================================================================
    // Access Request Workflow
    // =========================================================================

    /**
     * Submit access request.
     */
    public static function submitAccessRequest(int $userId, array $data): int
    {
        $id = DB::table('security_access_request')->insertGetId([
            'user_id' => $userId,
            'object_id' => $data['object_id'] ?? null,
            'classification_id' => $data['classification_id'] ?? null,
            'compartment_id' => $data['compartment_id'] ?? null,
            'request_type' => $data['request_type'],
            'justification' => $data['justification'],
            'duration_hours' => $data['duration_hours'] ?? 24,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        self::logAccess($userId, $data['object_id'] ?? null, $data['classification_id'] ?? null, $data['compartment_id'] ?? null, 'access_request', true);

        return $id;
    }

    /**
     * Approve access request.
     */
    public static function approveRequest(int $requestId, int $reviewerId, ?string $notes = null, ?int $durationHours = null): bool
    {
        $request = DB::table('security_access_request')
            ->where('id', $requestId)
            ->first();

        if (!$request || 'pending' !== $request->status) {
            return false;
        }

        $duration = $durationHours ?? $request->duration_hours ?? 24;

        DB::table('security_access_request')
            ->where('id', $requestId)
            ->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
                'access_granted_until' => date('Y-m-d H:i:s', strtotime("+$duration hours")),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        self::logAccess($request->user_id, $request->object_id, $request->classification_id, $request->compartment_id, 'access_granted', true, $notes);

        return true;
    }

    /**
     * Deny access request.
     */
    public static function denyRequest(int $requestId, int $reviewerId, ?string $notes = null): bool
    {
        $request = DB::table('security_access_request')
            ->where('id', $requestId)
            ->first();

        if (!$request || 'pending' !== $request->status) {
            return false;
        }

        DB::table('security_access_request')
            ->where('id', $requestId)
            ->update([
                'status' => 'denied',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        self::logAccess($request->user_id, $request->object_id, $request->classification_id, $request->compartment_id, 'access_denied', false, $notes);

        return true;
    }

    /**
     * Get pending access requests.
     */
    public static function getPendingRequests(): array
    {
        return DB::table('security_access_request as sar')
            ->join('user as u', 'sar.user_id', '=', 'u.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('sar.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('security_classification as sc', 'sar.classification_id', '=', 'sc.id')
            ->leftJoin('security_compartment as scomp', 'sar.compartment_id', '=', 'scomp.id')
            ->where('sar.status', 'pending')
            ->select([
                'sar.*',
                'u.username',
                'u.email',
                'ioi.title as object_title',
                'sc.name as classification_name',
                'scomp.name as compartment_name',
            ])
            ->orderBy('sar.priority', 'desc')
            ->orderBy('sar.created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get user's access requests.
     */
    public static function getUserRequests(int $userId): array
    {
        return DB::table('security_access_request as sar')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('sar.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('security_classification as sc', 'sar.classification_id', '=', 'sc.id')
            ->leftJoin('user as u', 'sar.reviewed_by', '=', 'u.id')
            ->where('sar.user_id', $userId)
            ->select([
                'sar.*',
                'ioi.title as object_title',
                'sc.name as classification_name',
                'u.username as reviewed_by_name',
            ])
            ->orderBy('sar.created_at', 'desc')
            ->get()
            ->toArray();
    }

    // =========================================================================
    // Audit Logging
    // =========================================================================

    /**
     * Log access attempt.
     */
    public static function logAccess(
        int $userId,
        ?int $objectId,
        ?int $classificationId,
        ?int $compartmentId,
        string $action,
        bool $granted,
        ?string $reason = null
    ): void {
        DB::table('security_access_log')->insert([
            'user_id' => $userId,
            'object_id' => $objectId,
            'classification_id' => $classificationId,
            'compartment_id' => $compartmentId,
            'action' => $action,
            'access_granted' => $granted ? 1 : 0,
            'denial_reason' => $granted ? null : $reason,
            'justification' => $granted ? $reason : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'session_id' => session_id() ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get access log for object.
     */
    public static function getObjectAccessLog(int $objectId, int $limit = 100): array
    {
        return DB::table('security_access_log as sal')
            ->join('user as u', 'sal.user_id', '=', 'u.id')
            ->where('sal.object_id', $objectId)
            ->select(['sal.*', 'u.username'])
            ->orderBy('sal.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get access log for user.
     */
    public static function getUserAccessLog(int $userId, int $limit = 100): array
    {
        return DB::table('security_access_log as sal')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('sal.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('sal.user_id', $userId)
            ->select(['sal.*', 'ioi.title as object_title'])
            ->orderBy('sal.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    // =========================================================================
    // Watermarking
    // =========================================================================

    /**
     * Generate watermark code for download.
     */
    public static function generateWatermark(int $userId, int $objectId, ?int $digitalObjectId = null): array
    {
        $user = DB::table('user')->where('id', $userId)->first();
        $code = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 12));

        $watermarkText = sprintf(
            'CONFIDENTIAL - %s - %s - %s',
            $user ? $user->username : 'Unknown',
            date('Y-m-d H:i'),
            $code
        );

        // Log watermark
        DB::table('security_watermark_log')->insert([
            'user_id' => $userId,
            'object_id' => $objectId,
            'digital_object_id' => $digitalObjectId,
            'watermark_type' => 'visible',
            'watermark_text' => $watermarkText,
            'watermark_code' => $code,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'code' => $code,
            'text' => $watermarkText,
        ];
    }

    /**
     * Trace watermark code to download.
     */
    public static function traceWatermark(string $code): ?object
    {
        return DB::table('security_watermark_log as swl')
            ->join('user as u', 'swl.user_id', '=', 'u.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('swl.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('swl.watermark_code', $code)
            ->select([
                'swl.*',
                'u.username',
                'u.email',
                'ioi.title as object_title',
            ])
            ->first();
    }

    // =========================================================================
    // Statistics & Reports
    // =========================================================================

    /**
     * Get comprehensive security statistics.
     */
    public static function getStatistics(): array
    {
        $stats = [];

        // Clearances by level
        $stats['clearances_by_level'] = DB::table('security_classification as sc')
            ->leftJoin('user_security_clearance as usc', function ($join) {
                $join->on('sc.id', '=', 'usc.classification_id')
                    ->where('usc.active', '=', 1);
            })
            ->select(['sc.code', 'sc.name', 'sc.color', DB::raw('COUNT(usc.id) as count')])
            ->groupBy('sc.id', 'sc.code', 'sc.name', 'sc.color')
            ->orderBy('sc.level')
            ->get()
            ->toArray();

        // Objects by classification
        $stats['objects_by_level'] = DB::table('security_classification as sc')
            ->leftJoin('object_security_classification as osc', 'sc.id', '=', 'osc.classification_id')
            ->select(['sc.code', 'sc.name', 'sc.color', DB::raw('COUNT(osc.id) as count')])
            ->groupBy('sc.id', 'sc.code', 'sc.name', 'sc.color')
            ->orderBy('sc.level')
            ->get()
            ->toArray();

        // Pending requests
        $stats['pending_requests'] = DB::table('security_access_request')
            ->where('status', 'pending')
            ->count();

        // Expiring clearances (30 days)
        $stats['expiring_clearances'] = DB::table('user_security_clearance')
            ->where('active', 1)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', date('Y-m-d', strtotime('+30 days')))
            ->where('expiry_date', '>=', date('Y-m-d'))
            ->count();

        // Recent denials (7 days)
        $stats['recent_denials'] = DB::table('security_access_log')
            ->where('access_granted', 0)
            ->where('created_at', '>=', date('Y-m-d', strtotime('-7 days')))
            ->count();

        // Reviews due
        $stats['reviews_due'] = DB::table('object_security_classification')
            ->where('review_date', '<=', date('Y-m-d'))
            ->count();

        // Declassifications due
        $stats['declassifications_due'] = DB::table('security_declassification_schedule')
            ->where('scheduled_date', '<=', date('Y-m-d'))
            ->where('processed', 0)
            ->count();

        // Access activity (last 24 hours)
        $stats['recent_activity'] = DB::table('security_access_log')
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->select([
                DB::raw('SUM(CASE WHEN access_granted = 1 THEN 1 ELSE 0 END) as granted'),
                DB::raw('SUM(CASE WHEN access_granted = 0 THEN 1 ELSE 0 END) as denied'),
            ])
            ->first();

        return $stats;
    }

    /**
     * Generate security audit report.
     */
    public static function generateAuditReport(array $filters = []): array
    {
        $query = DB::table('security_access_log as sal')
            ->join('user as u', 'sal.user_id', '=', 'u.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('sal.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('security_classification as sc', 'sal.classification_id', '=', 'sc.id');

        if (!empty($filters['user_id'])) {
            $query->where('sal.user_id', $filters['user_id']);
        }

        if (!empty($filters['object_id'])) {
            $query->where('sal.object_id', $filters['object_id']);
        }

        if (!empty($filters['classification_id'])) {
            $query->where('sal.classification_id', $filters['classification_id']);
        }

        if (!empty($filters['action'])) {
            $query->where('sal.action', $filters['action']);
        }

        if (!empty($filters['access_granted'])) {
            $query->where('sal.access_granted', 'granted' === $filters['access_granted'] ? 1 : 0);
        }

        if (!empty($filters['date_from'])) {
            $query->where('sal.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('sal.created_at', '<=', $filters['date_to'].' 23:59:59');
        }

        return $query
            ->select([
                'sal.*',
                'u.username',
                'u.email',
                'ioi.title as object_title',
                'sc.name as classification_name',
            ])
            ->orderBy('sal.created_at', 'desc')
            ->limit($filters['limit'] ?? 1000)
            ->get()
            ->toArray();
    }
}
