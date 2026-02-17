<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

// Include related services that are in same namespace
require_once __DIR__ . '/PrivacyBreachService.php';
require_once __DIR__ . '/PrivacyJurisdictionService.php';
require_once __DIR__ . '/PrivacyNotificationService.php';

/**
 * Privacy Management Service - Main Facade
 * Delegates to specialized services for better separation of concerns.
 *
 * Extracted services:
 * - PrivacyJurisdictionService: Lawful bases and jurisdiction configs
 * - PrivacyBreachService: Data breach management
 * - PrivacyDsarService: DSAR management (standalone service)
 * - PrivacyNotificationService: Notifications and ROPA approval workflow
 */
class PrivacyService
{
    protected string $auditModule = 'ahgPrivacyPlugin';

    // =====================
    // Jurisdiction Delegation (backward compatible)
    // =====================

    public static function getJurisdictions(): array
    {
        return PrivacyJurisdictionService::getJurisdictions();
    }

    public static function getAfricanJurisdictions(): array
    {
        return PrivacyJurisdictionService::getAfricanJurisdictions();
    }

    public static function getJurisdictionConfig(string $code): ?array
    {
        return PrivacyJurisdictionService::getJurisdictionConfig($code);
    }

    public static function getPOPIALawfulBases(): array
    {
        return PrivacyJurisdictionService::getPOPIALawfulBases();
    }

    public static function getPOPIASpecialCategories(): array
    {
        return PrivacyJurisdictionService::getPOPIASpecialCategories();
    }

    public static function getPAIARequestTypes(): array
    {
        return PrivacyJurisdictionService::getPAIARequestTypes();
    }

    public static function getNARSSARequirements(): array
    {
        return PrivacyJurisdictionService::getNARSSARequirements();
    }

    public static function getNDPALawfulBases(): array
    {
        return PrivacyJurisdictionService::getNDPALawfulBases();
    }

    public static function getNDPARights(): array
    {
        return PrivacyJurisdictionService::getNDPARights();
    }

    public static function getKenyaLawfulBases(): array
    {
        return PrivacyJurisdictionService::getKenyaLawfulBases();
    }

    public static function getGDPRLawfulBases(): array
    {
        return PrivacyJurisdictionService::getGDPRLawfulBases();
    }

    public static function getGDPRSpecialCategories(): array
    {
        return PrivacyJurisdictionService::getGDPRSpecialCategories();
    }

    public static function getRequestTypes(string $jurisdiction = 'popia'): array
    {
        return PrivacyJurisdictionService::getRequestTypes($jurisdiction);
    }

    public static function getLawfulBases(string $jurisdiction = 'popia'): array
    {
        return PrivacyJurisdictionService::getLawfulBases($jurisdiction);
    }

    public static function getIdTypes(): array
    {
        return PrivacyJurisdictionService::getIdTypes();
    }

    // =====================
    // Breach Delegation (backward compatible)
    // =====================

    public static function getBreachTypes(): array
    {
        return PrivacyBreachService::getBreachTypes();
    }

    public static function getSeverityLevels(): array
    {
        return PrivacyBreachService::getSeverityLevels();
    }

    public static function getBreachStatuses(): array
    {
        return PrivacyBreachService::getBreachStatuses();
    }

    public static function getRiskLevels(): array
    {
        return PrivacyBreachService::getRiskLevels();
    }

    public function getBreachList(array $filters = []): Collection
    {
        return (new PrivacyBreachService())->getList($filters);
    }

    public function getBreach(int $id): ?object
    {
        return (new PrivacyBreachService())->get($id);
    }

    public function createBreach(array $data, ?int $userId = null): int
    {
        $id = (new PrivacyBreachService())->create($data, $userId);
        $this->logAudit('create', 'PrivacyBreach', $id, [], $data, $data['reference'] ?? null);
        return $id;
    }

    public function updateBreach(int $id, array $data, ?int $userId = null): bool
    {
        return (new PrivacyBreachService())->update($id, $data, $userId);
    }

    // =====================
    // Configuration
    // =====================

    public function getEnabledJurisdictions(): array
    {
        $enabled = DB::table('privacy_config')
            ->where('is_active', 1)
            ->pluck('jurisdiction')
            ->toArray();

        if (empty($enabled)) {
            return ['popia' => self::getJurisdictions()['popia']];
        }

        return array_filter(
            self::getJurisdictions(),
            fn($code) => in_array($code, $enabled),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function getConfig(string $jurisdiction = null, bool $includeInactive = false): ?object
    {
        $query = DB::table('privacy_config');
        if (!$includeInactive) {
            $query->where('is_active', 1);
        }
        if ($jurisdiction) {
            $query->where('jurisdiction', $jurisdiction);
        }
        return $query->first();
    }

    public function saveConfig(array $data): bool
    {
        $existing = DB::table('privacy_config')
            ->where('jurisdiction', $data['jurisdiction'] ?? 'popia')
            ->first();

        $record = [
            'jurisdiction' => $data['jurisdiction'] ?? 'popia',
            'organization_name' => $data['organization_name'] ?? null,
            'registration_number' => (!empty($data['registration_number'])) ? $data['registration_number'] : null,
            'data_protection_email' => $data['data_protection_email'] ?? null,
            'dsar_response_days' => $data['dsar_response_days'] ?? 30,
            'breach_notification_hours' => $data['breach_notification_hours'] ?? 72,
            'retention_default_years' => $data['retention_default_years'] ?? 5,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'settings' => isset($data['settings']) ? json_encode($data['settings']) : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            return DB::table('privacy_config')
                ->where('id', $existing->id)
                ->update($record) >= 0;
        }

        $record['created_at'] = date('Y-m-d H:i:s');
        return DB::table('privacy_config')->insert($record);
    }

    // =====================
    // Reference Generation
    // =====================

    public function generateReference(string $prefix = 'DSAR', string $jurisdiction = 'popia'): string
    {
        $year = date('Y');
        $month = date('m');
        $jurisdictionCode = strtoupper(substr($jurisdiction, 0, 2));
        $table = $prefix === 'DSAR' ? 'privacy_dsar' : 'privacy_breach';
        $count = DB::table($table)
            ->whereYear('created_at', $year)
            ->where('jurisdiction', $jurisdiction)
            ->count() + 1;
        return sprintf('%s-%s-%s%s-%04d', $prefix, $jurisdictionCode, $year, $month, $count);
    }

    // =====================
    // Privacy Officers
    // =====================

    public function getOfficers(string $jurisdiction = null): Collection
    {
        $query = DB::table('privacy_officer')->where('is_active', 1);
        if ($jurisdiction && $jurisdiction !== 'all') {
            $query->where(function($q) use ($jurisdiction) {
                $q->where('jurisdiction', $jurisdiction)
                  ->orWhere('jurisdiction', 'all');
            });
        }
        return $query->orderBy('name')->get();
    }

    public function getOfficer(int $id): ?object
    {
        return DB::table('privacy_officer')->where('id', $id)->first();
    }

    public function saveOfficer(array $data, ?int $id = null): int
    {
        $record = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => (!empty($data['phone'])) ? $data['phone'] : null,
            'title' => (!empty($data['title'])) ? $data['title'] : null,
            'jurisdiction' => $data['jurisdiction'] ?? 'all',
            'registration_number' => (!empty($data['registration_number'])) ? $data['registration_number'] : null,
            'appointed_date' => (!empty($data['appointed_date'])) ? $data['appointed_date'] : null,
            'user_id' => (!empty($data['user_id'])) ? (int)$data['user_id'] : null,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            DB::table('privacy_officer')->where('id', $id)->update($record);
            return $id;
        }

        $record['created_at'] = date('Y-m-d H:i:s');
        return DB::table('privacy_officer')->insertGetId($record);
    }

    // =====================
    // DSAR Management
    // =====================

    public function getDsarList(array $filters = []): Collection
    {
        $query = DB::table('privacy_dsar as d')
            ->leftJoin('privacy_dsar_i18n as di', function ($j) {
                $j->on('di.id', '=', 'd.id')->where('di.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as u', 'u.id', '=', 'd.assigned_to')
            ->select([
                'd.*',
                'di.description',
                'di.notes',
                'di.response_summary',
                'u.username as assigned_username'
            ]);

        if (!empty($filters['status'])) {
            $query->where('d.status', $filters['status']);
        }
        if (!empty($filters['jurisdiction'])) {
            $query->where('d.jurisdiction', $filters['jurisdiction']);
        }
        if (!empty($filters['request_type'])) {
            $query->where('d.request_type', $filters['request_type']);
        }
        if (!empty($filters['overdue'])) {
            $query->where('d.due_date', '<', date('Y-m-d'))
                  ->whereNotIn('d.status', ['completed', 'rejected', 'withdrawn']);
        }
        if (!empty($filters['assigned_to'])) {
            $query->where('d.assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        return $query->orderByDesc('d.created_at')->get();
    }

    public function getDsar(int $id): ?object
    {
        return DB::table('privacy_dsar as d')
            ->leftJoin('privacy_dsar_i18n as di', function ($j) {
                $j->on('di.id', '=', 'd.id')->where('di.culture', '=', \AtomExtensions\Helpers\CultureHelper::getCulture());
            })
            ->leftJoin('user as u', 'u.id', '=', 'd.assigned_to')
            ->where('d.id', $id)
            ->select([
                'd.*',
                'di.description',
                'di.notes',
                'di.response_summary',
                'u.username as assigned_username'
            ])
            ->first();
    }

    public function createDsar(array $data, ?int $userId = null): int
    {
        $jurisdiction = $data['jurisdiction'] ?? 'popia';
        $config = $this->getConfig($jurisdiction);
        $jurisdictionInfo = self::getJurisdictionConfig($jurisdiction);
        $responseDays = $config->dsar_response_days ?? $jurisdictionInfo['dsar_days'] ?? 30;
        $receivedDate = $data['received_date'] ?? date('Y-m-d');

        $dsarData = [
            'reference_number' => $this->generateReference('DSAR', $jurisdiction),
            'jurisdiction' => $jurisdiction,
            'request_type' => $data['request_type'],
            'requestor_name' => $data['requestor_name'],
            'requestor_email' => $data['requestor_email'] ?? null,
            'requestor_phone' => $data['requestor_phone'] ?? null,
            'requestor_id_type' => $data['requestor_id_type'] ?? null,
            'requestor_id_number' => $data['requestor_id_number'] ?? null,
            'requestor_address' => $data['requestor_address'] ?? null,
            'status' => 'received',
            'priority' => $data['priority'] ?? 'normal',
            'received_date' => $receivedDate,
            'due_date' => date('Y-m-d', strtotime($receivedDate . " + {$responseDays} days")),
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $id = DB::table('privacy_dsar')->insertGetId($dsarData);

        if (!empty($data['description']) || !empty($data['notes'])) {
            DB::table('privacy_dsar_i18n')->insert([
                'id' => $id,
                'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture(),
                'description' => $data['description'] ?? null,
                'notes' => $data['notes'] ?? null
            ]);
        }

        $this->logDsarActivity($id, 'created', 'DSAR request created', $userId);
        $this->logAudit('create', 'PrivacyDsar', $id, [], $data, $dsarData['reference_number']);

        return $id;
    }

    public function updateDsar(int $id, array $data, ?int $userId = null): bool
    {
        $oldValues = (array)(DB::table('privacy_dsar')->where('id', $id)->first() ?? []);
        $updates = array_filter([
            'status' => $data['status'] ?? null,
            'priority' => $data['priority'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'outcome' => $data['outcome'] ?? null,
            'refusal_reason' => $data['refusal_reason'] ?? null,
            'is_verified' => $data['is_verified'] ?? null,
            'fee_required' => $data['fee_required'] ?? null,
            'fee_paid' => $data['fee_paid'] ?? null,
        ], fn($v) => $v !== null);

        $updates['updated_at'] = date('Y-m-d H:i:s');

        if (isset($data['status']) && $data['status'] === 'completed') {
            $updates['completed_date'] = date('Y-m-d');
        }

        if (isset($data['is_verified']) && $data['is_verified']) {
            $updates['verified_at'] = date('Y-m-d H:i:s');
            $updates['verified_by'] = $userId;
        }

        $result = DB::table('privacy_dsar')->where('id', $id)->update($updates);

        if (!empty($data['notes']) || !empty($data['response_summary'])) {
            DB::table('privacy_dsar_i18n')->updateOrInsert(
                ['id' => $id, 'culture' => \AtomExtensions\Helpers\CultureHelper::getCulture()],
                array_filter([
                    'notes' => $data['notes'] ?? null,
                    'response_summary' => $data['response_summary'] ?? null
                ], fn($v) => $v !== null)
            );
        }

        if (isset($data['status'])) {
            $this->logDsarActivity($id, 'status_changed', "Status changed to {$data['status']}", $userId);
        }

        return $result >= 0;
    }

    public function logDsarActivity(int $dsarId, string $action, string $details, ?int $userId = null): void
    {
        DB::table('privacy_dsar_log')->insert([
            'dsar_id' => $dsarId,
            'action' => $action,
            'details' => $details,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getDsarLogs(int $dsarId): Collection
    {
        return DB::table('privacy_dsar_log as l')
            ->leftJoin('user as u', 'u.id', '=', 'l.user_id')
            ->where('l.dsar_id', $dsarId)
            ->select(['l.*', 'u.username'])
            ->orderByDesc('l.created_at')
            ->get();
    }

    public static function getDsarStatuses(): array
    {
        return [
            'received' => 'Received',
            'verified' => 'Verified',
            'in_progress' => 'In Progress',
            'pending_info' => 'Pending Information',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn'
        ];
    }

    public static function getDsarOutcomes(): array
    {
        return [
            '' => '-- Select Outcome --',
            'granted' => 'Granted',
            'partially_granted' => 'Partially Granted',
            'refused' => 'Refused',
            'not_applicable' => 'Not Applicable'
        ];
    }

    // =====================
    // ROPA (Processing Activities)
    // =====================

    public function getRopaList(array $filters = []): Collection
    {
        $query = DB::table('privacy_processing_activity');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['lawful_basis'])) {
            $query->where('lawful_basis', $filters['lawful_basis']);
        }
        if (!empty($filters['jurisdiction'])) {
            $query->where('jurisdiction', $filters['jurisdiction']);
        }

        return $query->orderBy('name')->get();
    }

    public function getRopa(int $id): ?object
    {
        return DB::table('privacy_processing_activity')->where('id', $id)->first();
    }

    public function saveRopa(array $data, ?int $id = null, ?int $userId = null): int
    {
        $record = [
            'name' => $data['name'],
            'purpose' => $data['purpose'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? 'popia',
            'lawful_basis' => $data['lawful_basis'] ?? null,
            'lawful_basis_code' => $data['lawful_basis_code'] ?? null,
            'data_categories' => is_array($data['data_categories'] ?? null)
                ? json_encode($data['data_categories'])
                : ($data['data_categories'] ?? null),
            'data_subjects' => $data['data_subjects'] ?? null,
            'recipients' => $data['recipients'] ?? null,
            'third_countries' => (!empty($data['third_countries'])) ? (is_array($data['third_countries']) ? json_encode($data['third_countries']) : $data['third_countries']) : null,
            'transfers' => $data['cross_border_safeguards'] ?? null,
            'retention_period' => $data['retention_period'] ?? null,
            'security_measures' => $data['security_measures'] ?? null,
            'dpia_required' => $data['dpia_required'] ?? 0,
            'dpia_completed' => $data['dpia_completed'] ?? 0,
            'dpia_date' => (!empty($data['dpia_date'])) ? $data['dpia_date'] : null,
            'owner' => $data['responsible_person'] ?? null,
            'department' => $data['department'] ?? null,
            'assigned_officer_id' => !empty($data['assigned_officer_id']) ? (int)$data['assigned_officer_id'] : null,
            'status' => $data['status'] ?? 'draft',
            'next_review_date' => (!empty($data['next_review_date'])) ? $data['next_review_date'] : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($id) {
            DB::table('privacy_processing_activity')->where('id', $id)->update($record);
            return $id;
        }

        $record['created_by'] = $userId;
        $record['created_at'] = date('Y-m-d H:i:s');
        return DB::table('privacy_processing_activity')->insertGetId($record);
    }

    // =====================
    // Consent Records
    // =====================

    public function getConsentRecords(array $filters = []): Collection
    {
        $query = DB::table('privacy_consent_record');

        if (!empty($filters['purpose'])) {
            $query->where('purpose', $filters['purpose']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('consent_date')->get();
    }

    public function recordConsent(array $data, ?int $userId = null): int
    {
        return DB::table('privacy_consent_record')->insertGetId([
            'subject_name' => $data['subject_name'],
            'subject_email' => $data['subject_email'] ?? null,
            'subject_identifier' => $data['subject_identifier'] ?? null,
            'purpose' => $data['purpose'],
            'processing_activity_id' => $data['processing_activity_id'] ?? null,
            'consent_date' => $data['consent_date'] ?? date('Y-m-d'),
            'consent_method' => $data['consent_method'] ?? 'form',
            'consent_text' => $data['consent_text'] ?? null,
            'jurisdiction' => $data['jurisdiction'] ?? 'popia',
            'status' => 'active',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function withdrawConsent(int $id, ?string $reason = null, ?int $userId = null): bool
    {
        return DB::table('privacy_consent_record')
            ->where('id', $id)
            ->update([
                'status' => 'withdrawn',
                'withdrawn_date' => date('Y-m-d'),
                'withdrawal_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]) > 0;
    }

    // =====================
    // PAIA Requests (South Africa specific)
    // =====================

    public function getPaiaRequests(array $filters = []): Collection
    {
        $query = DB::table('privacy_paia_request');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['section'])) {
            $query->where('paia_section', $filters['section']);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function createPaiaRequest(array $data, ?int $userId = null): int
    {
        return DB::table('privacy_paia_request')->insertGetId([
            'reference_number' => $this->generateReference('PAIA', 'popia'),
            'paia_section' => $data['paia_section'],
            'requestor_name' => $data['requestor_name'],
            'requestor_email' => $data['requestor_email'] ?? null,
            'requestor_phone' => $data['requestor_phone'] ?? null,
            'requestor_id_number' => $data['requestor_id_number'] ?? null,
            'requestor_address' => $data['requestor_address'] ?? null,
            'record_description' => $data['record_description'] ?? null,
            'access_form' => $data['access_form'] ?? 'copy',
            'status' => 'received',
            'received_date' => $data['received_date'] ?? date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    // =====================
    // Dashboard & Statistics
    // =====================

    public function getDashboardStats(string $jurisdiction = null): array
    {
        $now = date('Y-m-d');
        $breachStats = (new PrivacyBreachService())->getStatistics($jurisdiction);

        $dsarQuery = DB::table('privacy_dsar');
        $ropaQuery = DB::table('privacy_processing_activity');
        $consentQuery = DB::table('privacy_consent_record');
        $complaintQuery = DB::table('privacy_complaint');

        if ($jurisdiction) {
            $dsarQuery->where('jurisdiction', $jurisdiction);
            $ropaQuery->where('jurisdiction', $jurisdiction);
            $consentQuery->where('jurisdiction', $jurisdiction);
            $complaintQuery->where('jurisdiction', $jurisdiction);
        }

        return [
            'dsar' => [
                'total' => (clone $dsarQuery)->count(),
                'pending' => (clone $dsarQuery)
                    ->whereNotIn('status', ['completed', 'rejected', 'withdrawn'])
                    ->count(),
                'overdue' => (clone $dsarQuery)
                    ->where('due_date', '<', $now)
                    ->whereNotIn('status', ['completed', 'rejected', 'withdrawn'])
                    ->count(),
                'completed_this_month' => (clone $dsarQuery)
                    ->where('status', 'completed')
                    ->whereMonth('completed_date', date('m'))
                    ->whereYear('completed_date', date('Y'))
                    ->count()
            ],
            'breach' => $breachStats,
            'ropa' => [
                'total' => (clone $ropaQuery)->count(),
                'approved' => (clone $ropaQuery)->where('status', 'approved')->count(),
                'requiring_dpia' => (clone $ropaQuery)
                    ->where('dpia_required', 1)
                    ->where('dpia_completed', 0)
                    ->count(),
                'review_due' => (clone $ropaQuery)
                    ->where('next_review_date', '<=', date('Y-m-d', strtotime('+30 days')))
                    ->count()
            ],
            'consent' => [
                'active' => (clone $consentQuery)->where('status', 'active')->count(),
                'withdrawn_this_month' => (clone $consentQuery)
                    ->where('status', 'withdrawn')
                    ->whereMonth('withdrawn_date', date('m'))
                    ->whereYear('withdrawn_date', date('Y'))
                    ->count()
            ],
            'complaint' => [
                'total' => (clone $complaintQuery)->count(),
                'open' => (clone $complaintQuery)
                    ->whereNotIn('status', ['resolved', 'closed'])
                    ->count(),
                'received_this_month' => (clone $complaintQuery)
                    ->whereMonth('created_at', date('m'))
                    ->whereYear('created_at', date('Y'))
                    ->count()
            ],
            'compliance_score' => $this->calculateComplianceScore($jurisdiction)
        ];
    }

    public function calculateComplianceScore(string $jurisdiction = null): int
    {
        $score = 0;
        $ropaQuery = DB::table('privacy_processing_activity');
        $dsarQuery = DB::table('privacy_dsar');
        $breachQuery = DB::table('privacy_breach');

        if ($jurisdiction) {
            $ropaQuery->where('jurisdiction', $jurisdiction);
            $dsarQuery->where('jurisdiction', $jurisdiction);
            $breachQuery->where('jurisdiction', $jurisdiction);
        }

        // ROPA completeness (30 points)
        $ropaTotal = (clone $ropaQuery)->count();
        $ropaApproved = (clone $ropaQuery)->where('status', 'approved')->count();
        $score += $ropaTotal > 0 ? round(($ropaApproved / $ropaTotal) * 30) : 0;

        // DSAR response rate (30 points)
        $dsarTotal = (clone $dsarQuery)->where('status', 'completed')->count();
        $dsarOnTime = (clone $dsarQuery)
            ->where('status', 'completed')
            ->whereColumn('completed_date', '<=', 'due_date')
            ->count();
        $score += $dsarTotal > 0 ? round(($dsarOnTime / $dsarTotal) * 30) : 30;

        // Breach handling (20 points)
        $breachOpen = (clone $breachQuery)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
        $score += $breachOpen === 0 ? 20 : max(0, 20 - ($breachOpen * 5));

        // DPIA completion (20 points)
        $dpiaRequired = (clone $ropaQuery)->where('dpia_required', 1)->count();
        $dpiaCompleted = DB::table('privacy_processing_activity')
            ->where('dpia_required', 1)
            ->where('dpia_completed', 1);
        if ($jurisdiction) {
            $dpiaCompleted->where('jurisdiction', $jurisdiction);
        }
        $dpiaCompletedCount = $dpiaCompleted->count();
        $score += $dpiaRequired > 0 ? round(($dpiaCompletedCount / $dpiaRequired) * 20) : 20;

        return min($score, 100);
    }

    // =====================
    // Notifications
    // =====================

    public function createNotification(int $userId, string $entityType, int $entityId, string $type, string $subject, ?string $message = null, ?string $link = null, ?int $createdBy = null): int
    {
        return DB::table('privacy_notification')->insertGetId([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'notification_type' => $type,
            'subject' => $subject,
            'message' => $message,
            'link' => $link,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getUnreadNotifications(int $userId, int $limit = 10): Collection
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getNotificationCount(int $userId): int
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    public function markNotificationRead(int $id, int $userId): bool
    {
        return DB::table('privacy_notification')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function markAllNotificationsRead(int $userId): int
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    // =====================
    // ROPA Approval Workflow
    // =====================

    public function submitRopaForApproval(int $id, int $userId, ?int $assignedOfficerId = null): bool
    {
        $activity = $this->getRopa($id);
        if (!$activity || $activity->status !== 'draft') {
            return false;
        }

        if (!$assignedOfficerId) {
            $officer = DB::table('privacy_officer')
                ->where('is_active', 1)
                ->where('is_primary', 1)
                ->first();
            $assignedOfficerId = $officer->user_id ?? null;
        }

        DB::table('privacy_processing_activity')
            ->where('id', $id)
            ->update([
                'status' => 'pending_review',
                'submitted_at' => date('Y-m-d H:i:s'),
                'submitted_by' => $userId,
                'assigned_officer_id' => $assignedOfficerId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->logApprovalAction($id, 'ropa', 'submitted', 'draft', 'pending_review', null, $userId);

        if ($assignedOfficerId) {
            $this->createNotification(
                $assignedOfficerId,
                'ropa',
                $id,
                'submitted',
                'ROPA Submitted for Review: ' . $activity->name,
                'A processing activity has been submitted for your review.',
                '/privacyAdmin/ropaView/id/' . $id,
                $userId
            );
            $this->sendApprovalEmail($assignedOfficerId, 'submitted', $activity);
        }

        return true;
    }

    public function approveRopa(int $id, int $userId, ?string $comment = null): bool
    {
        $activity = $this->getRopa($id);
        if (!$activity || $activity->status !== 'pending_review') {
            return false;
        }

        DB::table('privacy_processing_activity')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->logApprovalAction($id, 'ropa', 'approved', 'pending_review', 'approved', $comment, $userId);

        if ($activity->created_by) {
            $this->createNotification(
                $activity->created_by,
                'ropa',
                $id,
                'approved',
                'ROPA Approved: ' . $activity->name,
                $comment ?: 'Your processing activity has been approved.',
                '/privacyAdmin/ropaView/id/' . $id,
                $userId
            );
            $this->sendApprovalEmail($activity->created_by, 'approved', $activity, $comment);
        }

        return true;
    }

    public function rejectRopa(int $id, int $userId, string $reason): bool
    {
        $activity = $this->getRopa($id);
        if (!$activity || $activity->status !== 'pending_review') {
            return false;
        }

        DB::table('privacy_processing_activity')
            ->where('id', $id)
            ->update([
                'status' => 'draft',
                'rejected_at' => date('Y-m-d H:i:s'),
                'rejected_by' => $userId,
                'rejection_reason' => $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        $this->logApprovalAction($id, 'ropa', 'rejected', 'pending_review', 'draft', $reason, $userId);

        if ($activity->created_by) {
            $this->createNotification(
                $activity->created_by,
                'ropa',
                $id,
                'rejected',
                'ROPA Requires Changes: ' . $activity->name,
                'Reason: ' . $reason,
                '/privacyAdmin/ropaEdit/id/' . $id,
                $userId
            );
            $this->sendApprovalEmail($activity->created_by, 'rejected', $activity, $reason);
        }

        return true;
    }

    protected function logApprovalAction(int $entityId, string $entityType, string $action, ?string $oldStatus, ?string $newStatus, ?string $comment, int $userId): int
    {
        return DB::table('privacy_approval_log')->insertGetId([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getApprovalHistory(int $entityId, string $entityType = 'ropa'): Collection
    {
        return DB::table('privacy_approval_log as l')
            ->leftJoin('user as u', 'u.id', '=', 'l.user_id')
            ->where('l.entity_type', $entityType)
            ->where('l.entity_id', $entityId)
            ->select(['l.*', 'u.username', 'u.email'])
            ->orderByDesc('l.created_at')
            ->get();
    }

    public function getPrivacyOfficers(): Collection
    {
        return DB::table('privacy_officer')
            ->where('is_active', 1)
            ->whereNotNull('user_id')
            ->get();
    }

    public function isPrivacyOfficer(int $userId): bool
    {
        return DB::table('privacy_officer')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->exists();
    }

    protected function sendApprovalEmail(int $userId, string $action, $activity, ?string $comment = null): void
    {
        $user = DB::table('user')->find($userId);
        if (!$user || empty($user->email)) {
            return;
        }

        $subjects = [
            'submitted' => 'ROPA Submitted for Review: ' . $activity->name,
            'approved' => 'ROPA Approved: ' . $activity->name,
            'rejected' => 'ROPA Requires Changes: ' . $activity->name
        ];

        $subject = $subjects[$action] ?? 'ROPA Update: ' . $activity->name;

        try {
            $baseUrl = \sfConfig::get('app_siteBaseUrl', '');
            $link = $baseUrl . '/privacyAdmin/ropaView/id/' . $activity->id;

            $body = $this->buildApprovalEmailBody($action, $activity, $comment, $user, $link);

            $mailer = \sfContext::getInstance()->getMailer();
            $message = $mailer->compose(
                \sfConfig::get('app_mail_from', 'noreply@example.com'),
                $user->email,
                $subject,
                $body
            );
            $message->setContentType('text/html');
            $mailer->send($message);

            DB::table('privacy_notification')
                ->where('user_id', $userId)
                ->where('entity_type', 'ropa')
                ->where('entity_id', $activity->id)
                ->where('notification_type', $action)
                ->orderByDesc('created_at')
                ->limit(1)
                ->update(['email_sent' => 1, 'email_sent_at' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            error_log('Privacy email failed: ' . $e->getMessage());
        }
    }

    protected function buildApprovalEmailBody(string $action, $activity, ?string $comment, $user, string $link): string
    {
        $html = '<html><body style="font-family: Arial, sans-serif;">';
        $html .= '<h2>Processing Activity Update</h2>';
        $html .= '<p>Dear ' . htmlspecialchars($user->username ?? 'User') . ',</p>';

        switch ($action) {
            case 'submitted':
                $html .= '<p>A processing activity has been submitted for your review:</p>';
                break;
            case 'approved':
                $html .= '<p>Your processing activity has been <strong style="color: green;">approved</strong>:</p>';
                break;
            case 'rejected':
                $html .= '<p>Your processing activity requires <strong style="color: red;">changes</strong>:</p>';
                break;
        }

        $html .= '<div style="background: #f5f5f5; padding: 15px; margin: 15px 0; border-radius: 5px;">';
        $html .= '<strong>' . htmlspecialchars($activity->name) . '</strong><br>';
        $html .= '<small>Purpose: ' . htmlspecialchars(substr($activity->purpose ?? '', 0, 100)) . '...</small>';
        $html .= '</div>';

        if ($comment) {
            $html .= '<p><strong>Comment:</strong><br>' . nl2br(htmlspecialchars($comment)) . '</p>';
        }

        $html .= '<p><a href="' . $link . '" style="display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px;">View Details</a></p>';
        $html .= '<p style="color: #666; font-size: 12px;">This is an automated message from the Privacy Management System.</p>';
        $html .= '</body></html>';

        return $html;
    }

    // =====================
    // Audit Logging
    // =====================

    protected function logAudit(string $action, string $entityType, int $entityId, array $oldValues, array $newValues, ?string $title = null): void
    {
        try {
            $auditServicePath = \sfConfig::get('sf_root_dir') . '/plugins/ahgAuditTrailPlugin/lib/Services/AhgAuditService.php';
            if (file_exists($auditServicePath)) {
                require_once $auditServicePath;
            }

            if (class_exists('AhgAuditTrail\\Services\\AhgAuditService')) {
                $changedFields = [];
                foreach ($newValues as $key => $val) {
                    if (($oldValues[$key] ?? null) !== $val) {
                        $changedFields[] = $key;
                    }
                }
                if ($action === 'delete') {
                    $changedFields = array_keys($oldValues);
                }

                \AhgAuditTrail\Services\AhgAuditService::logAction(
                    $action,
                    $entityType,
                    $entityId,
                    [
                        'title' => $title,
                        'module' => $this->auditModule,
                        'action_name' => $action,
                        'old_values' => $oldValues,
                        'new_values' => $newValues,
                        'changed_fields' => $changedFields,
                    ]
                );
            }
        } catch (\Exception $e) {
            error_log("AUDIT ERROR: " . $e->getMessage());
        }
    }
}
