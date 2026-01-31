<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ICIP Service - Core service for Indigenous Cultural and Intellectual Property management
 *
 * @package ahgICIPPlugin
 */
class ahgICIPService
{
    // Consent status constants
    const CONSENT_NOT_REQUIRED = 'not_required';
    const CONSENT_PENDING = 'pending_consultation';
    const CONSENT_IN_PROGRESS = 'consultation_in_progress';
    const CONSENT_CONDITIONAL = 'conditional_consent';
    const CONSENT_FULL = 'full_consent';
    const CONSENT_RESTRICTED = 'restricted_consent';
    const CONSENT_DENIED = 'denied';
    const CONSENT_UNKNOWN = 'unknown';

    // Consent scope options
    const SCOPE_PRESERVATION_ONLY = 'preservation_only';
    const SCOPE_INTERNAL_ACCESS = 'internal_access';
    const SCOPE_PUBLIC_ACCESS = 'public_access';
    const SCOPE_REPRODUCTION = 'reproduction';
    const SCOPE_COMMERCIAL_USE = 'commercial_use';
    const SCOPE_EDUCATIONAL_USE = 'educational_use';
    const SCOPE_RESEARCH_USE = 'research_use';
    const SCOPE_FULL_RIGHTS = 'full_rights';

    /**
     * Get all consent status options
     */
    public static function getConsentStatusOptions(): array
    {
        return [
            self::CONSENT_NOT_REQUIRED => 'Not Required',
            self::CONSENT_PENDING => 'Pending Consultation',
            self::CONSENT_IN_PROGRESS => 'Consultation in Progress',
            self::CONSENT_CONDITIONAL => 'Conditional Consent',
            self::CONSENT_FULL => 'Full Consent',
            self::CONSENT_RESTRICTED => 'Restricted Consent',
            self::CONSENT_DENIED => 'Denied',
            self::CONSENT_UNKNOWN => 'Unknown',
        ];
    }

    /**
     * Get all consent scope options
     */
    public static function getConsentScopeOptions(): array
    {
        return [
            self::SCOPE_PRESERVATION_ONLY => 'Preservation Only',
            self::SCOPE_INTERNAL_ACCESS => 'Internal Access',
            self::SCOPE_PUBLIC_ACCESS => 'Public Access',
            self::SCOPE_REPRODUCTION => 'Reproduction',
            self::SCOPE_COMMERCIAL_USE => 'Commercial Use',
            self::SCOPE_EDUCATIONAL_USE => 'Educational Use',
            self::SCOPE_RESEARCH_USE => 'Research Use',
            self::SCOPE_FULL_RIGHTS => 'Full Rights',
        ];
    }

    /**
     * Get Australian states/territories
     */
    public static function getStateTerritories(): array
    {
        return [
            'NSW' => 'New South Wales',
            'VIC' => 'Victoria',
            'QLD' => 'Queensland',
            'WA' => 'Western Australia',
            'SA' => 'South Australia',
            'TAS' => 'Tasmania',
            'NT' => 'Northern Territory',
            'ACT' => 'Australian Capital Territory',
            'External' => 'External Territories',
        ];
    }

    /**
     * Get ICIP summary for an information object
     */
    public static function getObjectSummary(int $objectId): ?object
    {
        return DB::table('icip_object_summary')
            ->where('information_object_id', $objectId)
            ->first();
    }

    /**
     * Check if an object has ICIP content
     */
    public static function hasICIPContent(int $objectId): bool
    {
        $summary = self::getObjectSummary($objectId);
        return $summary && $summary->has_icip_content;
    }

    /**
     * Get consent records for an object
     */
    public static function getObjectConsent(int $objectId): array
    {
        return DB::table('icip_consent as c')
            ->leftJoin('icip_community as com', 'c.community_id', '=', 'com.id')
            ->where('c.information_object_id', $objectId)
            ->select([
                'c.*',
                'com.name as community_name',
                'com.language_group',
                'com.state_territory',
            ])
            ->orderBy('c.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get cultural notices for an object
     */
    public static function getObjectNotices(int $objectId): array
    {
        return DB::table('icip_cultural_notice as n')
            ->join('icip_cultural_notice_type as t', 'n.notice_type_id', '=', 't.id')
            ->leftJoin('icip_community as c', 'n.community_id', '=', 'c.id')
            ->where('n.information_object_id', $objectId)
            ->where('t.is_active', 1)
            ->where(function ($query) {
                $query->whereNull('n.start_date')
                    ->orWhere('n.start_date', '<=', date('Y-m-d'));
            })
            ->where(function ($query) {
                $query->whereNull('n.end_date')
                    ->orWhere('n.end_date', '>=', date('Y-m-d'));
            })
            ->select([
                'n.*',
                't.code as notice_code',
                't.name as notice_name',
                't.default_text',
                't.icon',
                't.severity',
                't.requires_acknowledgement',
                't.blocks_access',
                't.display_public',
                't.display_staff',
                'c.name as community_name',
            ])
            ->orderBy('t.display_order')
            ->get()
            ->toArray();
    }

    /**
     * Get TK labels for an object
     */
    public static function getObjectTKLabels(int $objectId): array
    {
        return DB::table('icip_tk_label as l')
            ->join('icip_tk_label_type as t', 'l.label_type_id', '=', 't.id')
            ->leftJoin('icip_community as c', 'l.community_id', '=', 'c.id')
            ->where('l.information_object_id', $objectId)
            ->where('t.is_active', 1)
            ->select([
                'l.*',
                't.code as label_code',
                't.category',
                't.name as label_name',
                't.description',
                't.icon_path',
                't.local_contexts_url',
                'c.name as community_name',
            ])
            ->orderBy('t.display_order')
            ->get()
            ->toArray();
    }

    /**
     * Get access restrictions for an object
     */
    public static function getObjectRestrictions(int $objectId): array
    {
        return DB::table('icip_access_restriction as r')
            ->leftJoin('icip_community as c', 'r.community_id', '=', 'c.id')
            ->where('r.information_object_id', $objectId)
            ->where(function ($query) {
                $query->whereNull('r.start_date')
                    ->orWhere('r.start_date', '<=', date('Y-m-d'));
            })
            ->where(function ($query) {
                $query->whereNull('r.end_date')
                    ->orWhere('r.end_date', '>=', date('Y-m-d'));
            })
            ->select([
                'r.*',
                'c.name as community_name',
            ])
            ->orderBy('r.created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get consultations for an object
     */
    public static function getObjectConsultations(int $objectId): array
    {
        return DB::table('icip_consultation as con')
            ->join('icip_community as c', 'con.community_id', '=', 'c.id')
            ->where('con.information_object_id', $objectId)
            ->where('con.is_confidential', 0)
            ->select([
                'con.*',
                'c.name as community_name',
            ])
            ->orderBy('con.consultation_date', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Check if user has acknowledged a notice
     */
    public static function hasAcknowledged(int $noticeId, int $userId): bool
    {
        return DB::table('icip_notice_acknowledgement')
            ->where('notice_id', $noticeId)
            ->where('user_id', $userId)
            ->exists();
    }

    /**
     * Record user acknowledgement of a notice
     */
    public static function recordAcknowledgement(int $noticeId, int $userId): bool
    {
        try {
            DB::table('icip_notice_acknowledgement')->insertOrIgnore([
                'notice_id' => $noticeId,
                'user_id' => $userId,
                'acknowledged_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user can access an object based on ICIP restrictions
     */
    public static function checkAccess(int $objectId, ?int $userId = null): array
    {
        $result = [
            'allowed' => true,
            'requires_acknowledgement' => false,
            'unacknowledged_notices' => [],
            'blocked_reason' => null,
            'restrictions' => [],
        ];

        // Get active notices that block access or require acknowledgement
        $notices = self::getObjectNotices($objectId);
        foreach ($notices as $notice) {
            if ($notice->blocks_access) {
                if (!$userId || !self::hasAcknowledged($notice->id, $userId)) {
                    $result['allowed'] = false;
                    $result['blocked_reason'] = 'Cultural notice requires acknowledgement before access';
                    $result['unacknowledged_notices'][] = $notice;
                }
            } elseif ($notice->requires_acknowledgement) {
                if (!$userId || !self::hasAcknowledged($notice->id, $userId)) {
                    $result['requires_acknowledgement'] = true;
                    $result['unacknowledged_notices'][] = $notice;
                }
            }
        }

        // Get active restrictions
        $restrictions = self::getObjectRestrictions($objectId);
        foreach ($restrictions as $restriction) {
            $result['restrictions'][] = $restriction;
            if ($restriction->override_security_clearance) {
                // These restrictions block standard access
                if (in_array($restriction->restriction_type, [
                    'community_permission_required',
                    'initiated_only',
                    'repatriation_pending',
                ])) {
                    $result['allowed'] = false;
                    $result['blocked_reason'] = 'ICIP restriction: ' . self::getRestrictionLabel($restriction->restriction_type);
                }
            }
        }

        return $result;
    }

    /**
     * Get human-readable restriction label
     */
    public static function getRestrictionLabel(string $type): string
    {
        $labels = [
            'community_permission_required' => 'Community Permission Required',
            'gender_restricted_male' => 'Men Only (Gender Restricted)',
            'gender_restricted_female' => 'Women Only (Gender Restricted)',
            'initiated_only' => 'Initiated Persons Only',
            'seasonal' => 'Seasonal Restriction',
            'mourning_period' => 'Mourning Period',
            'repatriation_pending' => 'Repatriation Pending',
            'under_consultation' => 'Under Consultation',
            'elder_approval_required' => 'Elder Approval Required',
            'custom' => 'Custom Restriction',
        ];
        return $labels[$type] ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * Get all restriction type options
     */
    public static function getRestrictionTypes(): array
    {
        return [
            'community_permission_required' => 'Community Permission Required',
            'gender_restricted_male' => 'Men Only (Gender Restricted)',
            'gender_restricted_female' => 'Women Only (Gender Restricted)',
            'initiated_only' => 'Initiated Persons Only',
            'seasonal' => 'Seasonal Restriction',
            'mourning_period' => 'Mourning Period',
            'repatriation_pending' => 'Repatriation Pending',
            'under_consultation' => 'Under Consultation',
            'elder_approval_required' => 'Elder Approval Required',
            'custom' => 'Custom Restriction',
        ];
    }

    /**
     * Update ICIP summary for an object
     */
    public static function updateObjectSummary(int $objectId): void
    {
        $consent = DB::table('icip_consent')
            ->where('information_object_id', $objectId)
            ->orderBy('created_at', 'desc')
            ->first();

        $noticeCount = DB::table('icip_cultural_notice')
            ->where('information_object_id', $objectId)
            ->count();

        $labelCount = DB::table('icip_tk_label')
            ->where('information_object_id', $objectId)
            ->count();

        $restrictionCount = DB::table('icip_access_restriction')
            ->where('information_object_id', $objectId)
            ->count();

        // Check if any notices require acknowledgement or block access
        $blockingNotice = DB::table('icip_cultural_notice as n')
            ->join('icip_cultural_notice_type as t', 'n.notice_type_id', '=', 't.id')
            ->where('n.information_object_id', $objectId)
            ->where(function ($query) {
                $query->where('t.requires_acknowledgement', 1)
                    ->orWhere('t.blocks_access', 1);
            })
            ->first();

        // Get community IDs
        $communityIds = [];
        $communities = DB::table('icip_consent')
            ->where('information_object_id', $objectId)
            ->whereNotNull('community_id')
            ->pluck('community_id')
            ->toArray();
        $communityIds = array_merge($communityIds, $communities);

        $communities = DB::table('icip_cultural_notice')
            ->where('information_object_id', $objectId)
            ->whereNotNull('community_id')
            ->pluck('community_id')
            ->toArray();
        $communityIds = array_merge($communityIds, $communities);

        $communityIds = array_unique($communityIds);

        // Get last consultation date
        $lastConsultation = DB::table('icip_consultation')
            ->where('information_object_id', $objectId)
            ->orderBy('consultation_date', 'desc')
            ->value('consultation_date');

        $hasContent = $consent || $noticeCount > 0 || $labelCount > 0 || $restrictionCount > 0;

        DB::table('icip_object_summary')->updateOrInsert(
            ['information_object_id' => $objectId],
            [
                'has_icip_content' => $hasContent ? 1 : 0,
                'consent_status' => $consent ? $consent->consent_status : null,
                'has_cultural_notices' => $noticeCount > 0 ? 1 : 0,
                'cultural_notice_count' => $noticeCount,
                'has_tk_labels' => $labelCount > 0 ? 1 : 0,
                'tk_label_count' => $labelCount,
                'has_restrictions' => $restrictionCount > 0 ? 1 : 0,
                'restriction_count' => $restrictionCount,
                'requires_acknowledgement' => $blockingNotice && $blockingNotice->requires_acknowledgement ? 1 : 0,
                'blocks_access' => $blockingNotice && $blockingNotice->blocks_access ? 1 : 0,
                'community_ids' => !empty($communityIds) ? json_encode($communityIds) : null,
                'last_consultation_date' => $lastConsultation,
                'consent_expiry_date' => $consent ? $consent->consent_expiry_date : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    /**
     * Get config value
     */
    public static function getConfig(string $key, $default = null)
    {
        $config = DB::table('icip_config')
            ->where('config_key', $key)
            ->value('config_value');

        return $config !== null ? $config : $default;
    }

    /**
     * Set config value
     */
    public static function setConfig(string $key, $value): void
    {
        DB::table('icip_config')->updateOrInsert(
            ['config_key' => $key],
            ['config_value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Get dashboard statistics
     */
    public static function getDashboardStats(): array
    {
        $stats = [];

        // Total objects with ICIP content
        $stats['total_icip_objects'] = DB::table('icip_object_summary')
            ->where('has_icip_content', 1)
            ->count();

        // Consent statistics
        $stats['consent_by_status'] = DB::table('icip_consent')
            ->select('consent_status', DB::raw('COUNT(*) as count'))
            ->groupBy('consent_status')
            ->pluck('count', 'consent_status')
            ->toArray();

        // Pending consultations
        $stats['pending_consultations'] = DB::table('icip_consent')
            ->whereIn('consent_status', [
                self::CONSENT_PENDING,
                self::CONSENT_IN_PROGRESS,
            ])
            ->count();

        // Expiring consents (within 90 days)
        $expiryDays = (int) self::getConfig('consent_expiry_warning_days', 90);
        $expiryDate = date('Y-m-d', strtotime("+{$expiryDays} days"));
        $stats['expiring_consents'] = DB::table('icip_consent')
            ->whereNotNull('consent_expiry_date')
            ->where('consent_expiry_date', '<=', $expiryDate)
            ->where('consent_expiry_date', '>=', date('Y-m-d'))
            ->count();

        // Community count
        $stats['total_communities'] = DB::table('icip_community')
            ->where('is_active', 1)
            ->count();

        // Outstanding follow-ups
        $stats['follow_ups_due'] = DB::table('icip_consultation')
            ->where('status', 'follow_up_required')
            ->whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', date('Y-m-d'))
            ->count();

        // TK Labels applied
        $stats['tk_labels_applied'] = DB::table('icip_tk_label')->count();

        // Active restrictions
        $stats['active_restrictions'] = DB::table('icip_access_restriction')
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', date('Y-m-d'));
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', date('Y-m-d'));
            })
            ->count();

        return $stats;
    }

    /**
     * Get records pending consultation
     */
    public static function getPendingConsultation(int $limit = 50): array
    {
        return DB::table('icip_consent as c')
            ->join('information_object as io', 'c.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('icip_community as com', 'c.community_id', '=', 'com.id')
            ->whereIn('c.consent_status', [
                self::CONSENT_PENDING,
                self::CONSENT_IN_PROGRESS,
                self::CONSENT_UNKNOWN,
            ])
            ->select([
                'c.*',
                'ioi.title as object_title',
                'io.identifier',
                's.slug',
                'com.name as community_name',
            ])
            ->orderBy('c.created_at', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get consents expiring soon
     */
    public static function getExpiringConsents(int $days = 90): array
    {
        $expiryDate = date('Y-m-d', strtotime("+{$days} days"));

        return DB::table('icip_consent as c')
            ->join('information_object as io', 'c.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->leftJoin('icip_community as com', 'c.community_id', '=', 'com.id')
            ->whereNotNull('c.consent_expiry_date')
            ->where('c.consent_expiry_date', '<=', $expiryDate)
            ->where('c.consent_expiry_date', '>=', date('Y-m-d'))
            ->select([
                'c.*',
                'ioi.title as object_title',
                'io.identifier',
                's.slug',
                'com.name as community_name',
            ])
            ->orderBy('c.consent_expiry_date', 'asc')
            ->get()
            ->toArray();
    }
}
