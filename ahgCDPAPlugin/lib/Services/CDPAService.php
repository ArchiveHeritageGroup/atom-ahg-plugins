<?php

namespace ahgCDPAPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CDPA Service - Zimbabwe Cyber and Data Protection Act compliance.
 */
class CDPAService
{
    /**
     * Get compliance dashboard statistics.
     */
    public function getDashboardStats(): array
    {
        $license = $this->getCurrentLicense();
        $dpo = $this->getActiveDPO();

        return [
            'license' => $license,
            'license_status' => $license ? $this->getLicenseStatus($license) : 'not_registered',
            'license_days_remaining' => $license ? $this->daysUntil($license->expiry_date) : null,
            'dpo' => $dpo,
            'dpo_appointed' => (bool) $dpo,
            'requests' => [
                'pending' => DB::table('cdpa_data_subject_request')->where('status', 'pending')->count(),
                'overdue' => DB::table('cdpa_data_subject_request')
                    ->where('status', 'pending')
                    ->where('due_date', '<', date('Y-m-d'))
                    ->count(),
                'total_30_days' => DB::table('cdpa_data_subject_request')
                    ->where('request_date', '>=', date('Y-m-d', strtotime('-30 days')))
                    ->count(),
            ],
            'processing_activities' => DB::table('cdpa_processing_activity')->where('is_active', 1)->count(),
            'dpia' => [
                'pending' => DB::table('cdpa_dpia')->whereIn('status', ['draft', 'in_progress'])->count(),
                'overdue_review' => DB::table('cdpa_dpia')
                    ->where('status', 'completed')
                    ->where('next_review_date', '<', date('Y-m-d'))
                    ->count(),
            ],
            'breaches' => [
                'open' => DB::table('cdpa_breach')->whereIn('status', ['investigating', 'contained', 'ongoing'])->count(),
                'this_year' => DB::table('cdpa_breach')
                    ->whereYear('incident_date', date('Y'))
                    ->count(),
            ],
            'consent' => [
                'active' => DB::table('cdpa_consent')->where('is_active', 1)->whereNull('withdrawal_date')->count(),
                'withdrawn_30_days' => DB::table('cdpa_consent')
                    ->whereNotNull('withdrawal_date')
                    ->where('withdrawal_date', '>=', date('Y-m-d', strtotime('-30 days')))
                    ->count(),
            ],
        ];
    }

    /**
     * Get compliance status summary.
     */
    public function getComplianceStatus(): array
    {
        $issues = [];
        $warnings = [];

        // Check license
        $license = $this->getCurrentLicense();
        if (!$license) {
            $issues[] = 'No POTRAZ license registered';
        } elseif ('expired' === $license->status) {
            $issues[] = 'POTRAZ license has expired';
        } elseif ($this->daysUntil($license->expiry_date) <= 90) {
            $warnings[] = 'POTRAZ license expires in ' . $this->daysUntil($license->expiry_date) . ' days';
        }

        // Check DPO
        $dpo = $this->getActiveDPO();
        if (!$dpo) {
            $issues[] = 'No Data Protection Officer appointed';
        } elseif (!$dpo->form_dp2_submitted) {
            $warnings[] = 'Form DP2 not submitted to POTRAZ';
        }

        // Check overdue requests
        $overdueRequests = DB::table('cdpa_data_subject_request')
            ->where('status', 'pending')
            ->where('due_date', '<', date('Y-m-d'))
            ->count();
        if ($overdueRequests > 0) {
            $issues[] = "{$overdueRequests} data subject request(s) overdue";
        }

        // Check open breaches
        $openBreaches = DB::table('cdpa_breach')
            ->whereIn('status', ['investigating', 'contained', 'ongoing'])
            ->count();
        if ($openBreaches > 0) {
            $warnings[] = "{$openBreaches} breach incident(s) still open";
        }

        // Check DPIA reviews
        $overdueReviews = DB::table('cdpa_dpia')
            ->where('status', 'completed')
            ->where('next_review_date', '<', date('Y-m-d'))
            ->count();
        if ($overdueReviews > 0) {
            $warnings[] = "{$overdueReviews} DPIA review(s) overdue";
        }

        return [
            'status' => empty($issues) ? (empty($warnings) ? 'compliant' : 'warning') : 'non_compliant',
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    // ==========================================
    // License Management
    // ==========================================

    /**
     * Get current active license.
     */
    public function getCurrentLicense()
    {
        return DB::table('cdpa_controller_license')
            ->orderBy('expiry_date', 'desc')
            ->first();
    }

    /**
     * Get license status.
     */
    public function getLicenseStatus($license): string
    {
        if ('suspended' === $license->status) {
            return 'suspended';
        }
        if (strtotime($license->expiry_date) < time()) {
            return 'expired';
        }
        if ($this->daysUntil($license->expiry_date) <= 90) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Save or update license.
     */
    public function saveLicense(array $data): int
    {
        $existing = $this->getCurrentLicense();

        $record = [
            'license_number' => $data['license_number'],
            'tier' => $data['tier'],
            'organization_name' => $data['organization_name'],
            'registration_date' => $data['registration_date'],
            'issue_date' => $data['issue_date'],
            'expiry_date' => $data['expiry_date'],
            'potraz_ref' => $data['potraz_ref'] ?? null,
            'certificate_path' => $data['certificate_path'] ?? null,
            'data_subjects_count' => $data['data_subjects_count'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            DB::table('cdpa_controller_license')->where('id', $existing->id)->update($record);

            return $existing->id;
        }

        $record['created_at'] = date('Y-m-d H:i:s');

        return DB::table('cdpa_controller_license')->insertGetId($record);
    }

    /**
     * Get licenses expiring soon.
     */
    public function getExpiringLicenses(int $days = 90): \Illuminate\Support\Collection
    {
        return DB::table('cdpa_controller_license')
            ->where('expiry_date', '<=', date('Y-m-d', strtotime("+{$days} days")))
            ->where('expiry_date', '>=', date('Y-m-d'))
            ->get();
    }

    // ==========================================
    // DPO Management
    // ==========================================

    /**
     * Get active DPO.
     */
    public function getActiveDPO()
    {
        return DB::table('cdpa_dpo')
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Save DPO record.
     */
    public function saveDPO(array $data): int
    {
        // Deactivate existing DPO
        DB::table('cdpa_dpo')->update(['is_active' => 0]);

        return DB::table('cdpa_dpo')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'qualifications' => $data['qualifications'] ?? null,
            'hit_cert_number' => $data['hit_cert_number'] ?? null,
            'appointment_date' => $data['appointment_date'],
            'term_end_date' => $data['term_end_date'] ?? null,
            'form_dp2_submitted' => $data['form_dp2_submitted'] ?? 0,
            'form_dp2_date' => $data['form_dp2_date'] ?? null,
            'form_dp2_ref' => $data['form_dp2_ref'] ?? null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ==========================================
    // Data Subject Requests
    // ==========================================

    /**
     * Get data subject requests.
     */
    public function getRequests(?string $status = null, int $limit = 50): \Illuminate\Support\Collection
    {
        $query = DB::table('cdpa_data_subject_request')
            ->orderBy('due_date', 'asc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get pending requests.
     */
    public function getPendingRequests(): \Illuminate\Support\Collection
    {
        return $this->getRequests('pending');
    }

    /**
     * Get overdue requests.
     */
    public function getOverdueRequests(): \Illuminate\Support\Collection
    {
        return DB::table('cdpa_data_subject_request')
            ->where('status', 'pending')
            ->where('due_date', '<', date('Y-m-d'))
            ->orderBy('due_date', 'asc')
            ->get();
    }

    /**
     * Create data subject request.
     */
    public function createRequest(array $data): int
    {
        $deadlineDays = $this->getConfig('response_deadline_days', 30);

        return DB::table('cdpa_data_subject_request')->insertGetId([
            'request_type' => $data['request_type'],
            'reference_number' => $this->generateRequestReference(),
            'data_subject_name' => $data['data_subject_name'],
            'data_subject_email' => $data['data_subject_email'] ?? null,
            'data_subject_phone' => $data['data_subject_phone'] ?? null,
            'data_subject_id_number' => $data['data_subject_id_number'] ?? null,
            'request_date' => $data['request_date'] ?? date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime("+{$deadlineDays} days")),
            'description' => $data['description'] ?? null,
            'verification_method' => $data['verification_method'] ?? null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update request status.
     */
    public function updateRequestStatus(int $id, string $status, array $data = []): bool
    {
        $update = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ('completed' === $status) {
            $update['completed_date'] = date('Y-m-d');
            $update['handled_by'] = $data['handled_by'] ?? null;
            $update['response_notes'] = $data['response_notes'] ?? null;
        } elseif ('rejected' === $status) {
            $update['rejection_reason'] = $data['rejection_reason'] ?? null;
            $update['handled_by'] = $data['handled_by'] ?? null;
        } elseif ('extended' === $status) {
            $update['extension_reason'] = $data['extension_reason'] ?? null;
            $update['due_date'] = $data['new_due_date'] ?? date('Y-m-d', strtotime('+30 days'));
        }

        return DB::table('cdpa_data_subject_request')->where('id', $id)->update($update) > 0;
    }

    /**
     * Generate request reference number.
     */
    protected function generateRequestReference(): string
    {
        $year = date('Y');
        $count = DB::table('cdpa_data_subject_request')
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('DSR-%s-%04d', $year, $count);
    }

    // ==========================================
    // Processing Activities
    // ==========================================

    /**
     * Get all processing activities.
     */
    public function getProcessingActivities(bool $activeOnly = true): \Illuminate\Support\Collection
    {
        $query = DB::table('cdpa_processing_activity');

        if ($activeOnly) {
            $query->where('is_active', 1);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get processing activity by ID.
     */
    public function getProcessingActivity(int $id)
    {
        return DB::table('cdpa_processing_activity')->where('id', $id)->first();
    }

    /**
     * Create processing activity.
     */
    public function createProcessingActivity(array $data): int
    {
        return DB::table('cdpa_processing_activity')->insertGetId([
            'name' => $data['name'],
            'category' => $data['category'],
            'data_types' => is_array($data['data_types']) ? json_encode($data['data_types']) : $data['data_types'],
            'purpose' => $data['purpose'],
            'legal_basis' => $data['legal_basis'],
            'storage_location' => $data['storage_location'] ?? 'zimbabwe',
            'international_country' => $data['international_country'] ?? null,
            'retention_period' => $data['retention_period'] ?? null,
            'safeguards' => $data['safeguards'] ?? null,
            'cross_border' => $data['cross_border'] ?? 0,
            'cross_border_safeguards' => $data['cross_border_safeguards'] ?? null,
            'automated_decision' => $data['automated_decision'] ?? 0,
            'children_data' => $data['children_data'] ?? 0,
            'biometric_data' => $data['biometric_data'] ?? 0,
            'health_data' => $data['health_data'] ?? 0,
            'is_active' => 1,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ==========================================
    // DPIA
    // ==========================================

    /**
     * Get DPIAs.
     */
    public function getDPIAs(?string $status = null): \Illuminate\Support\Collection
    {
        $query = DB::table('cdpa_dpia')
            ->leftJoin('cdpa_processing_activity', 'cdpa_dpia.processing_activity_id', '=', 'cdpa_processing_activity.id')
            ->select('cdpa_dpia.*', 'cdpa_processing_activity.name as activity_name');

        if ($status) {
            $query->where('cdpa_dpia.status', $status);
        }

        return $query->orderBy('cdpa_dpia.assessment_date', 'desc')->get();
    }

    /**
     * Get DPIA by ID.
     */
    public function getDPIA(int $id)
    {
        return DB::table('cdpa_dpia')
            ->leftJoin('cdpa_processing_activity', 'cdpa_dpia.processing_activity_id', '=', 'cdpa_processing_activity.id')
            ->select('cdpa_dpia.*', 'cdpa_processing_activity.name as activity_name')
            ->where('cdpa_dpia.id', $id)
            ->first();
    }

    /**
     * Create DPIA.
     */
    public function createDPIA(array $data): int
    {
        $reviewMonths = $this->getConfig('dpia_review_months', 12);

        return DB::table('cdpa_dpia')->insertGetId([
            'name' => $data['name'],
            'processing_activity_id' => $data['processing_activity_id'] ?? null,
            'description' => $data['description'] ?? null,
            'necessity_assessment' => $data['necessity_assessment'] ?? null,
            'risk_level' => $data['risk_level'] ?? 'medium',
            'assessment_date' => $data['assessment_date'] ?? date('Y-m-d'),
            'assessor_name' => $data['assessor_name'] ?? null,
            'next_review_date' => date('Y-m-d', strtotime("+{$reviewMonths} months")),
            'status' => 'draft',
            'created_by' => $data['created_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ==========================================
    // Breach Management
    // ==========================================

    /**
     * Get breaches.
     */
    public function getBreaches(?string $status = null): \Illuminate\Support\Collection
    {
        $query = DB::table('cdpa_breach')
            ->orderBy('incident_date', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Get breach by ID.
     */
    public function getBreach(int $id)
    {
        return DB::table('cdpa_breach')->where('id', $id)->first();
    }

    /**
     * Create breach record.
     */
    public function createBreach(array $data): int
    {
        return DB::table('cdpa_breach')->insertGetId([
            'reference_number' => $this->generateBreachReference(),
            'incident_date' => $data['incident_date'],
            'discovery_date' => $data['discovery_date'],
            'description' => $data['description'],
            'breach_type' => $data['breach_type'],
            'data_affected' => $data['data_affected'] ?? null,
            'records_affected' => $data['records_affected'] ?? null,
            'data_subjects_affected' => $data['data_subjects_affected'] ?? null,
            'severity' => $data['severity'] ?? 'medium',
            'root_cause' => $data['root_cause'] ?? null,
            'status' => 'investigating',
            'reported_by' => $data['reported_by'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Check if POTRAZ notification is overdue (72 hours).
     */
    public function isBreachNotificationOverdue($breach): bool
    {
        if ($breach->potraz_notified) {
            return false;
        }

        $hoursLimit = $this->getConfig('breach_notification_hours', 72);
        $discoveryTime = strtotime($breach->discovery_date);
        $deadline = $discoveryTime + ($hoursLimit * 3600);

        return time() > $deadline;
    }

    /**
     * Generate breach reference number.
     */
    protected function generateBreachReference(): string
    {
        $year = date('Y');
        $count = DB::table('cdpa_breach')
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('BRE-%s-%04d', $year, $count);
    }

    // ==========================================
    // Consent Management
    // ==========================================

    /**
     * Get consent records.
     */
    public function getConsentRecords(bool $activeOnly = true): \Illuminate\Support\Collection
    {
        $query = DB::table('cdpa_consent')
            ->orderBy('consent_date', 'desc');

        if ($activeOnly) {
            $query->where('is_active', 1)->whereNull('withdrawal_date');
        }

        return $query->get();
    }

    /**
     * Record consent.
     */
    public function recordConsent(array $data): int
    {
        return DB::table('cdpa_consent')->insertGetId([
            'data_subject_name' => $data['data_subject_name'],
            'data_subject_email' => $data['data_subject_email'] ?? null,
            'data_subject_id' => $data['data_subject_id'] ?? null,
            'purpose' => $data['purpose'],
            'processing_activity_id' => $data['processing_activity_id'] ?? null,
            'consent_date' => $data['consent_date'] ?? date('Y-m-d H:i:s'),
            'consent_method' => $data['consent_method'] ?? 'electronic',
            'is_biometric' => $data['is_biometric'] ?? 0,
            'is_children' => $data['is_children'] ?? 0,
            'guardian_name' => $data['guardian_name'] ?? null,
            'evidence_path' => $data['evidence_path'] ?? null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Record consent withdrawal.
     */
    public function withdrawConsent(int $id, ?string $reason = null): bool
    {
        return DB::table('cdpa_consent')->where('id', $id)->update([
            'withdrawal_date' => date('Y-m-d H:i:s'),
            'withdrawal_reason' => $reason,
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]) > 0;
    }

    // ==========================================
    // Configuration
    // ==========================================

    /**
     * Get config value.
     */
    public function getConfig(string $key, $default = null)
    {
        $config = DB::table('cdpa_config')
            ->where('setting_key', $key)
            ->first();

        if (!$config) {
            return $default;
        }

        switch ($config->setting_type) {
            case 'integer':
                return (int) $config->setting_value;

            case 'boolean':
                return filter_var($config->setting_value, FILTER_VALIDATE_BOOLEAN);

            case 'json':
                return json_decode($config->setting_value, true);

            default:
                return $config->setting_value;
        }
    }

    /**
     * Set config value.
     */
    public function setConfig(string $key, $value, string $type = 'string'): bool
    {
        if ('json' === $type && is_array($value)) {
            $value = json_encode($value);
        }

        return DB::table('cdpa_config')->updateOrInsert(
            ['setting_key' => $key],
            [
                'setting_value' => $value,
                'setting_type' => $type,
                'updated_at' => date('Y-m-d H:i:s'),
            ]
        );
    }

    // ==========================================
    // Audit Logging
    // ==========================================

    /**
     * Log CDPA action.
     */
    public function logAction(string $actionType, string $entityType, ?int $entityId = null, ?array $details = null, ?int $userId = null): void
    {
        DB::table('cdpa_audit_log')->insert([
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ==========================================
    // Helpers
    // ==========================================

    /**
     * Calculate days until a date.
     */
    protected function daysUntil(string $date): int
    {
        $target = strtotime($date);
        $now = strtotime(date('Y-m-d'));

        return (int) floor(($target - $now) / 86400);
    }
}
