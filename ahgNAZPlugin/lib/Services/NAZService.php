<?php

/**
 * NAZ Service - National Archives of Zimbabwe Act Compliance
 *
 * Service class for managing NAZ Act compliance:
 * - Closure periods (25-year rule)
 * - Research permits
 * - Researcher registration
 * - Records schedules
 * - Transfers to NAZ
 * - Protected records
 *
 * @package    ahgNAZPlugin
 * @subpackage Services
 */

namespace AhgNAZ\Services;

use Illuminate\Database\Capsule\Manager as DB;

class NAZService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $stats = [];

        // Closure statistics
        $stats['closures'] = [
            'active' => DB::table('naz_closure_period')
                ->where('status', 'active')
                ->count(),
            'expiring_soon' => DB::table('naz_closure_period')
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->whereRaw('end_date <= DATE_ADD(CURDATE(), INTERVAL 1 YEAR)')
                ->count(),
            'expired' => DB::table('naz_closure_period')
                ->where('status', 'expired')
                ->count(),
        ];

        // Researcher statistics
        $stats['researchers'] = [
            'total' => DB::table('naz_researcher')->count(),
            'local' => DB::table('naz_researcher')
                ->where('researcher_type', 'local')
                ->where('status', 'active')
                ->count(),
            'foreign' => DB::table('naz_researcher')
                ->where('researcher_type', 'foreign')
                ->where('status', 'active')
                ->count(),
        ];

        // Permit statistics
        $stats['permits'] = [
            'pending' => DB::table('naz_research_permit')
                ->where('status', 'pending')
                ->count(),
            'active' => DB::table('naz_research_permit')
                ->where('status', 'active')
                ->count(),
            'expiring_soon' => DB::table('naz_research_permit')
                ->where('status', 'active')
                ->whereRaw('end_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)')
                ->count(),
        ];

        // Transfer statistics
        $stats['transfers'] = [
            'pending' => DB::table('naz_transfer')
                ->whereIn('status', ['proposed', 'scheduled'])
                ->count(),
            'in_progress' => DB::table('naz_transfer')
                ->where('status', 'in_transit')
                ->count(),
            'this_year' => DB::table('naz_transfer')
                ->where('status', 'accessioned')
                ->whereYear('actual_date', date('Y'))
                ->count(),
        ];

        // Protected records
        $stats['protected'] = DB::table('naz_protected_record')
            ->where('status', 'active')
            ->count();

        // Schedules
        $stats['schedules'] = DB::table('naz_records_schedule')
            ->where('status', 'approved')
            ->count();

        return $stats;
    }

    /**
     * Check compliance status
     */
    public function getComplianceStatus(): array
    {
        $issues = [];
        $warnings = [];

        // Check for overdue closure reviews
        $overdueReviews = DB::table('naz_closure_period')
            ->where('status', 'active')
            ->whereNotNull('review_date')
            ->whereRaw('review_date < CURDATE()')
            ->count();

        if ($overdueReviews > 0) {
            $issues[] = "{$overdueReviews} closure periods overdue for review";
        }

        // Check for expired closures not processed
        $expiredClosures = DB::table('naz_closure_period')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereRaw('end_date < CURDATE()')
            ->count();

        if ($expiredClosures > 0) {
            $warnings[] = "{$expiredClosures} closure periods have expired and need release";
        }

        // Check for expired permits still active
        $expiredPermits = DB::table('naz_research_permit')
            ->where('status', 'active')
            ->whereRaw('end_date < CURDATE()')
            ->count();

        if ($expiredPermits > 0) {
            $warnings[] = "{$expiredPermits} research permits have expired";
        }

        // Check for pending permit applications
        $pendingPermits = DB::table('naz_research_permit')
            ->where('status', 'pending')
            ->whereRaw('created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->count();

        if ($pendingPermits > 0) {
            $warnings[] = "{$pendingPermits} permit applications pending > 7 days";
        }

        // Check for overdue transfers
        $overdueTransfers = DB::table('naz_transfer')
            ->whereIn('status', ['proposed', 'scheduled'])
            ->whereNotNull('proposed_date')
            ->whereRaw('proposed_date < CURDATE()')
            ->count();

        if ($overdueTransfers > 0) {
            $warnings[] = "{$overdueTransfers} transfers overdue";
        }

        // Determine overall status
        $status = 'compliant';
        if (!empty($issues)) {
            $status = 'non_compliant';
        } elseif (!empty($warnings)) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'issues' => $issues,
            'warnings' => $warnings,
        ];
    }

    // =========================================================================
    // CLOSURE PERIOD MANAGEMENT
    // =========================================================================

    /**
     * Get closure periods with filtering
     */
    public function getClosures(array $filters = [])
    {
        $query = DB::table('naz_closure_period as c')
            ->leftJoin('information_object as io', 'c.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->select([
                'c.*',
                'ioi.title as record_title',
            ]);

        if (!empty($filters['status'])) {
            $query->where('c.status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('c.closure_type', $filters['type']);
        }

        if (!empty($filters['expiring_within_days'])) {
            $days = (int) $filters['expiring_within_days'];
            $query->whereRaw("c.end_date <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)")
                  ->where('c.status', 'active');
        }

        return $query->orderBy('c.end_date', 'asc')->get();
    }

    /**
     * Create closure period
     */
    public function createClosure(array $data): int
    {
        $id = DB::table('naz_closure_period')->insertGetId([
            'information_object_id' => $data['information_object_id'],
            'closure_type' => $data['closure_type'] ?? 'standard',
            'closure_reason' => $data['closure_reason'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'years' => $data['years'] ?? 25,
            'authority_reference' => $data['authority_reference'] ?? null,
            'review_date' => $data['review_date'] ?? null,
            'status' => 'active',
            'created_by' => $data['user_id'],
        ]);

        $this->logAction('closure_created', 'closure_period', $id, $data['user_id'], null, $data);

        return $id;
    }

    /**
     * Release closure period
     */
    public function releaseClosure(int $id, int $userId, ?string $notes = null): bool
    {
        $closure = DB::table('naz_closure_period')->where('id', $id)->first();
        if (!$closure) {
            return false;
        }

        DB::table('naz_closure_period')
            ->where('id', $id)
            ->update([
                'status' => 'released',
                'released_by' => $userId,
                'released_at' => date('Y-m-d H:i:s'),
                'release_notes' => $notes,
            ]);

        $this->logAction('closure_released', 'closure_period', $id, $userId, (array) $closure, ['notes' => $notes]);

        return true;
    }

    /**
     * Check if a record is under closure
     */
    public function isRecordClosed(int $informationObjectId): array
    {
        $closure = DB::table('naz_closure_period')
            ->where('information_object_id', $informationObjectId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', date('Y-m-d'));
            })
            ->first();

        return [
            'closed' => !is_null($closure),
            'closure' => $closure,
        ];
    }

    // =========================================================================
    // RESEARCHER MANAGEMENT
    // =========================================================================

    /**
     * Get researchers with filtering
     */
    public function getResearchers(array $filters = [])
    {
        $query = DB::table('naz_researcher');

        if (!empty($filters['type'])) {
            $query->where('researcher_type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('institution', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * Create researcher
     */
    public function createResearcher(array $data): int
    {
        $id = DB::table('naz_researcher')->insertGetId([
            'user_id' => $data['user_id'] ?? null,
            'researcher_type' => $data['researcher_type'],
            'title' => $data['title'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'passport_number' => $data['passport_number'] ?? null,
            'national_id' => $data['national_id'] ?? null,
            'institution' => $data['institution'] ?? null,
            'position' => $data['position'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'research_interests' => $data['research_interests'] ?? null,
            'registration_date' => date('Y-m-d'),
            'status' => 'active',
            'created_by' => $data['created_by'],
        ]);

        $this->logAction('researcher_registered', 'researcher', $id, $data['created_by'], null, $data);

        return $id;
    }

    /**
     * Get researcher by ID
     */
    public function getResearcher(int $id)
    {
        return DB::table('naz_researcher')->where('id', $id)->first();
    }

    // =========================================================================
    // RESEARCH PERMIT MANAGEMENT
    // =========================================================================

    /**
     * Get permits with filtering
     */
    public function getPermits(array $filters = [])
    {
        $query = DB::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->select([
                'p.*',
                'r.first_name',
                'r.last_name',
                'r.researcher_type',
                'r.institution',
            ]);

        if (!empty($filters['status'])) {
            $query->where('p.status', $filters['status']);
        }

        if (!empty($filters['researcher_id'])) {
            $query->where('p.researcher_id', $filters['researcher_id']);
        }

        if (!empty($filters['expiring_within_days'])) {
            $days = (int) $filters['expiring_within_days'];
            $query->whereRaw("p.end_date <= DATE_ADD(CURDATE(), INTERVAL {$days} DAY)")
                  ->where('p.status', 'active');
        }

        return $query->orderBy('p.created_at', 'desc')->get();
    }

    /**
     * Create permit application
     */
    public function createPermit(array $data): int
    {
        // Generate permit number
        $year = date('Y');
        $count = DB::table('naz_research_permit')
            ->whereYear('created_at', $year)
            ->count() + 1;
        $permitNumber = sprintf('NAZ-RP-%s-%04d', $year, $count);

        // Determine fee based on researcher type
        $researcher = $this->getResearcher($data['researcher_id']);
        $fee = 0;
        if ($researcher && 'foreign' === $researcher->researcher_type) {
            $fee = (float) $this->getConfig('foreign_permit_fee_usd', 200);
        }

        $id = DB::table('naz_research_permit')->insertGetId([
            'permit_number' => $permitNumber,
            'researcher_id' => $data['researcher_id'],
            'permit_type' => $data['permit_type'] ?? 'general',
            'research_topic' => $data['research_topic'],
            'research_purpose' => $data['research_purpose'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'fee_amount' => $fee,
            'fee_currency' => 'USD',
            'fee_paid' => 0,
            'status' => 'pending',
            'collections_access' => json_encode($data['collections_access'] ?? []),
            'restrictions' => $data['restrictions'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        $this->logAction('permit_applied', 'permit', $id, $data['created_by'], null, $data);

        return $id;
    }

    /**
     * Approve permit
     */
    public function approvePermit(int $id, int $userId): bool
    {
        $permit = DB::table('naz_research_permit')->where('id', $id)->first();
        if (!$permit || 'pending' !== $permit->status) {
            return false;
        }

        // Check if fee needs to be paid (foreign researchers)
        $researcher = $this->getResearcher($permit->researcher_id);
        $newStatus = 'approved';
        if ($researcher && 'foreign' === $researcher->researcher_type && !$permit->fee_paid) {
            $newStatus = 'approved'; // Still approved, but they need to pay before it's active
        } else {
            $newStatus = 'active';
        }

        DB::table('naz_research_permit')
            ->where('id', $id)
            ->update([
                'status' => $newStatus,
                'approved_by' => $userId,
                'approved_date' => date('Y-m-d'),
            ]);

        $this->logAction('permit_approved', 'permit', $id, $userId);

        return true;
    }

    /**
     * Record permit payment
     */
    public function recordPermitPayment(int $id, string $receipt, int $userId): bool
    {
        DB::table('naz_research_permit')
            ->where('id', $id)
            ->update([
                'fee_paid' => 1,
                'fee_receipt' => $receipt,
                'payment_date' => date('Y-m-d'),
                'status' => 'active',
            ]);

        $this->logAction('permit_payment', 'permit', $id, $userId, null, ['receipt' => $receipt]);

        return true;
    }

    /**
     * Get permit by ID
     */
    public function getPermit(int $id)
    {
        return DB::table('naz_research_permit as p')
            ->join('naz_researcher as r', 'p.researcher_id', '=', 'r.id')
            ->select([
                'p.*',
                'r.first_name',
                'r.last_name',
                'r.researcher_type',
                'r.institution',
                'r.email as researcher_email',
            ])
            ->where('p.id', $id)
            ->first();
    }

    // =========================================================================
    // RECORDS SCHEDULE MANAGEMENT
    // =========================================================================

    /**
     * Get schedules with filtering
     */
    public function getSchedules(array $filters = [])
    {
        $query = DB::table('naz_records_schedule');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['agency'])) {
            $query->where('agency_name', 'like', "%{$filters['agency']}%");
        }

        if (!empty($filters['disposal_action'])) {
            $query->where('disposal_action', $filters['disposal_action']);
        }

        return $query->orderBy('agency_name')->orderBy('record_series')->get();
    }

    /**
     * Create records schedule
     */
    public function createSchedule(array $data): int
    {
        // Generate schedule number
        $year = date('Y');
        $count = DB::table('naz_records_schedule')
            ->whereYear('created_at', $year)
            ->count() + 1;
        $scheduleNumber = sprintf('RS-%s-%04d', $year, $count);

        $id = DB::table('naz_records_schedule')->insertGetId([
            'schedule_number' => $scheduleNumber,
            'agency_name' => $data['agency_name'],
            'agency_code' => $data['agency_code'] ?? null,
            'record_series' => $data['record_series'],
            'description' => $data['description'] ?? null,
            'retention_period_active' => $data['retention_period_active'],
            'retention_period_semi' => $data['retention_period_semi'] ?? 0,
            'disposal_action' => $data['disposal_action'],
            'legal_authority' => $data['legal_authority'] ?? null,
            'classification' => $data['classification'] ?? 'useful',
            'access_restriction' => $data['access_restriction'] ?? 'open',
            'status' => 'draft',
            'created_by' => $data['created_by'],
        ]);

        $this->logAction('schedule_created', 'schedule', $id, $data['created_by'], null, $data);

        return $id;
    }

    /**
     * Get schedule by ID
     */
    public function getSchedule(int $id)
    {
        return DB::table('naz_records_schedule')->where('id', $id)->first();
    }

    // =========================================================================
    // TRANSFER MANAGEMENT
    // =========================================================================

    /**
     * Get transfers with filtering
     */
    public function getTransfers(array $filters = [])
    {
        $query = DB::table('naz_transfer as t')
            ->leftJoin('naz_records_schedule as s', 't.schedule_id', '=', 's.id')
            ->select([
                't.*',
                's.schedule_number',
                's.record_series',
            ]);

        if (!empty($filters['status'])) {
            $query->where('t.status', $filters['status']);
        }

        if (!empty($filters['agency'])) {
            $query->where('t.transferring_agency', 'like', "%{$filters['agency']}%");
        }

        return $query->orderBy('t.proposed_date', 'desc')->get();
    }

    /**
     * Create transfer
     */
    public function createTransfer(array $data): int
    {
        // Generate transfer number
        $year = date('Y');
        $count = DB::table('naz_transfer')
            ->whereYear('created_at', $year)
            ->count() + 1;
        $transferNumber = sprintf('TR-%s-%04d', $year, $count);

        $id = DB::table('naz_transfer')->insertGetId([
            'transfer_number' => $transferNumber,
            'transferring_agency' => $data['transferring_agency'],
            'agency_contact' => $data['agency_contact'] ?? null,
            'agency_email' => $data['agency_email'] ?? null,
            'agency_phone' => $data['agency_phone'] ?? null,
            'schedule_id' => $data['schedule_id'] ?? null,
            'transfer_type' => $data['transfer_type'] ?? 'scheduled',
            'description' => $data['description'] ?? null,
            'date_range_start' => $data['date_range_start'] ?? null,
            'date_range_end' => $data['date_range_end'] ?? null,
            'quantity_linear_metres' => $data['quantity_linear_metres'] ?? null,
            'quantity_boxes' => $data['quantity_boxes'] ?? null,
            'quantity_items' => $data['quantity_items'] ?? null,
            'contains_restricted' => $data['contains_restricted'] ?? 0,
            'restriction_details' => $data['restriction_details'] ?? null,
            'proposed_date' => $data['proposed_date'] ?? null,
            'status' => 'proposed',
            'created_by' => $data['created_by'],
        ]);

        $this->logAction('transfer_created', 'transfer', $id, $data['created_by'], null, $data);

        return $id;
    }

    /**
     * Update transfer status
     */
    public function updateTransferStatus(int $id, string $status, int $userId, ?array $extraData = null): bool
    {
        $transfer = DB::table('naz_transfer')->where('id', $id)->first();
        if (!$transfer) {
            return false;
        }

        $updateData = ['status' => $status];

        if ('received' === $status || 'accessioned' === $status) {
            $updateData['actual_date'] = date('Y-m-d');
            $updateData['received_by'] = $userId;
        }

        if (!empty($extraData['accession_number'])) {
            $updateData['accession_number'] = $extraData['accession_number'];
        }

        if (!empty($extraData['location_assigned'])) {
            $updateData['location_assigned'] = $extraData['location_assigned'];
        }

        DB::table('naz_transfer')
            ->where('id', $id)
            ->update($updateData);

        $this->logAction("transfer_{$status}", 'transfer', $id, $userId, (array) $transfer, $updateData);

        return true;
    }

    /**
     * Get transfer by ID
     */
    public function getTransfer(int $id)
    {
        return DB::table('naz_transfer as t')
            ->leftJoin('naz_records_schedule as s', 't.schedule_id', '=', 's.id')
            ->select([
                't.*',
                's.schedule_number',
                's.record_series',
                's.agency_name as schedule_agency',
            ])
            ->where('t.id', $id)
            ->first();
    }

    /**
     * Get transfer items
     */
    public function getTransferItems(int $transferId)
    {
        return DB::table('naz_transfer_item')
            ->where('transfer_id', $transferId)
            ->orderBy('id')
            ->get();
    }

    // =========================================================================
    // PROTECTED RECORDS
    // =========================================================================

    /**
     * Get protected records
     */
    public function getProtectedRecords(array $filters = [])
    {
        $query = DB::table('naz_protected_record as pr')
            ->leftJoin('information_object as io', 'pr.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->select([
                'pr.*',
                'ioi.title as record_title',
            ]);

        if (!empty($filters['status'])) {
            $query->where('pr.status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('pr.protection_type', $filters['type']);
        }

        return $query->orderBy('pr.protection_start', 'desc')->get();
    }

    /**
     * Add protection to record
     */
    public function protectRecord(array $data): int
    {
        $id = DB::table('naz_protected_record')->insertGetId([
            'information_object_id' => $data['information_object_id'],
            'protection_type' => $data['protection_type'],
            'protection_reason' => $data['protection_reason'],
            'authority_reference' => $data['authority_reference'] ?? null,
            'protection_start' => $data['protection_start'] ?? date('Y-m-d'),
            'protection_end' => $data['protection_end'] ?? null,
            'review_date' => $data['review_date'] ?? null,
            'status' => 'active',
            'created_by' => $data['created_by'],
        ]);

        $this->logAction('record_protected', 'protected_record', $id, $data['created_by'], null, $data);

        return $id;
    }

    /**
     * Check if record is protected
     */
    public function isRecordProtected(int $informationObjectId): array
    {
        $protection = DB::table('naz_protected_record')
            ->where('information_object_id', $informationObjectId)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('protection_end')
                    ->orWhere('protection_end', '>', date('Y-m-d'));
            })
            ->first();

        return [
            'protected' => !is_null($protection),
            'protection' => $protection,
        ];
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * Get configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        $config = DB::table('naz_config')
            ->where('config_key', $key)
            ->first();

        return $config ? $config->config_value : $default;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value, ?string $description = null): void
    {
        DB::table('naz_config')
            ->updateOrInsert(
                ['config_key' => $key],
                [
                    'config_value' => $value,
                    'description' => $description,
                ]
            );
    }

    /**
     * Get all configuration
     */
    public function getAllConfig(): array
    {
        return DB::table('naz_config')
            ->pluck('config_value', 'config_key')
            ->toArray();
    }

    // =========================================================================
    // AUDIT LOGGING
    // =========================================================================

    /**
     * Log an action
     */
    protected function logAction(
        string $actionType,
        string $entityType,
        int $entityId,
        ?int $userId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?string $notes = null
    ): void {
        DB::table('naz_audit_log')->insert([
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
            'notes' => $notes,
        ]);
    }

    /**
     * Get audit log
     */
    public function getAuditLog(array $filters = [])
    {
        $query = DB::table('naz_audit_log as l')
            ->leftJoin('user as u', 'l.user_id', '=', 'u.id')
            ->select([
                'l.*',
                'u.username',
                'u.email as user_email',
            ]);

        if (!empty($filters['entity_type'])) {
            $query->where('l.entity_type', $filters['entity_type']);
        }

        if (!empty($filters['entity_id'])) {
            $query->where('l.entity_id', $filters['entity_id']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('l.action_type', $filters['action_type']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('l.created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('l.created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('l.created_at', 'desc')->limit(500)->get();
    }

    // =========================================================================
    // RESEARCH VISITS
    // =========================================================================

    /**
     * Log research visit
     */
    public function logVisit(array $data): int
    {
        return DB::table('naz_research_visit')->insertGetId([
            'permit_id' => $data['permit_id'],
            'researcher_id' => $data['researcher_id'],
            'visit_date' => $data['visit_date'] ?? date('Y-m-d'),
            'check_in_time' => $data['check_in_time'] ?? date('H:i:s'),
            'check_out_time' => $data['check_out_time'] ?? null,
            'materials_requested' => $data['materials_requested'] ?? null,
            'materials_provided' => $data['materials_provided'] ?? null,
            'reading_room' => $data['reading_room'] ?? null,
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get visits for a researcher
     */
    public function getResearcherVisits(int $researcherId)
    {
        return DB::table('naz_research_visit')
            ->where('researcher_id', $researcherId)
            ->orderBy('visit_date', 'desc')
            ->get();
    }
}
