<?php

/**
 * NMMZ Service - National Museums and Monuments of Zimbabwe
 *
 * Service class for managing heritage protection under the NMMZ Act:
 * - National monuments management
 * - Antiquities register
 * - Export permits
 * - Archaeological sites
 * - Heritage impact assessments
 *
 * @package    ahgNMMZPlugin
 * @subpackage Services
 */

namespace AhgNMMZ\Services;

use Illuminate\Database\Capsule\Manager as DB;

class NMMZService
{
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $stats = [];

        // Monuments
        $stats['monuments'] = [
            'total' => DB::table('nmmz_monument')->count(),
            'gazetted' => DB::table('nmmz_monument')
                ->where('legal_status', 'gazetted')->count(),
            'at_risk' => DB::table('nmmz_monument')
                ->where('status', 'at_risk')->count(),
            'world_heritage' => DB::table('nmmz_monument')
                ->where('world_heritage_status', 'inscribed')->count(),
        ];

        // Antiquities
        $stats['antiquities'] = [
            'total' => DB::table('nmmz_antiquity')->count(),
            'in_collection' => DB::table('nmmz_antiquity')
                ->where('status', 'in_collection')->count(),
            'missing' => DB::table('nmmz_antiquity')
                ->where('status', 'missing')->count(),
        ];

        // Export permits
        $stats['permits'] = [
            'pending' => DB::table('nmmz_export_permit')
                ->where('status', 'pending')->count(),
            'active' => DB::table('nmmz_export_permit')
                ->whereIn('status', ['approved', 'issued'])->count(),
            'this_year' => DB::table('nmmz_export_permit')
                ->whereYear('created_at', date('Y'))->count(),
        ];

        // Archaeological sites
        $stats['sites'] = [
            'total' => DB::table('nmmz_archaeological_site')->count(),
            'at_risk' => DB::table('nmmz_archaeological_site')
                ->where('protection_status', 'at_risk')->count(),
        ];

        // HIAs
        $stats['hia'] = [
            'pending' => DB::table('nmmz_heritage_impact_assessment')
                ->where('status', 'under_review')->count(),
            'this_year' => DB::table('nmmz_heritage_impact_assessment')
                ->whereYear('created_at', date('Y'))->count(),
        ];

        return $stats;
    }

    /**
     * Get compliance status
     */
    public function getComplianceStatus(): array
    {
        $issues = [];
        $warnings = [];

        // Check for monuments at risk
        $atRisk = DB::table('nmmz_monument')
            ->where('status', 'at_risk')->count();
        if ($atRisk > 0) {
            $issues[] = "{$atRisk} national monuments at risk";
        }

        // Check for overdue inspections
        $overdueInspections = DB::table('nmmz_monument')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('last_inspection_date')
                  ->orWhereRaw('last_inspection_date < DATE_SUB(CURDATE(), INTERVAL 2 YEAR)');
            })->count();
        if ($overdueInspections > 0) {
            $warnings[] = "{$overdueInspections} monuments overdue for inspection";
        }

        // Check for pending permits
        $pendingPermits = DB::table('nmmz_export_permit')
            ->where('status', 'pending')
            ->whereRaw('created_at < DATE_SUB(NOW(), INTERVAL 14 DAY)')
            ->count();
        if ($pendingPermits > 0) {
            $warnings[] = "{$pendingPermits} export permits pending > 14 days";
        }

        // Check for missing antiquities
        $missing = DB::table('nmmz_antiquity')
            ->where('status', 'missing')->count();
        if ($missing > 0) {
            $issues[] = "{$missing} antiquities reported missing";
        }

        // Check for pending HIAs
        $pendingHIA = DB::table('nmmz_heritage_impact_assessment')
            ->where('status', 'under_review')
            ->whereRaw('created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)')
            ->count();
        if ($pendingHIA > 0) {
            $warnings[] = "{$pendingHIA} HIAs pending > 30 days";
        }

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
    // MONUMENTS
    // =========================================================================

    public function getMonuments(array $filters = [])
    {
        $query = DB::table('nmmz_monument as m')
            ->leftJoin('nmmz_monument_category as c', 'm.category_id', '=', 'c.id')
            ->select(['m.*', 'c.name as category_name', 'c.code as category_code']);

        if (!empty($filters['category_id'])) {
            $query->where('m.category_id', $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('m.status', $filters['status']);
        }
        if (!empty($filters['province'])) {
            $query->where('m.province', $filters['province']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('m.name', 'like', "%{$search}%")
                  ->orWhere('m.monument_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('m.name')->get();
    }

    public function createMonument(array $data): int
    {
        // Generate monument number
        $year = date('Y');
        $count = DB::table('nmmz_monument')
            ->whereYear('created_at', $year)->count() + 1;
        $monumentNumber = sprintf('NM-%s-%04d', $year, $count);

        $id = DB::table('nmmz_monument')->insertGetId([
            'monument_number' => $monumentNumber,
            'information_object_id' => $data['information_object_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'historical_significance' => $data['historical_significance'] ?? null,
            'province' => $data['province'] ?? null,
            'district' => $data['district'] ?? null,
            'location_description' => $data['location_description'] ?? null,
            'gps_latitude' => $data['gps_latitude'] ?? null,
            'gps_longitude' => $data['gps_longitude'] ?? null,
            'area_hectares' => $data['area_hectares'] ?? null,
            'protection_level' => $data['protection_level'] ?? 'national',
            'legal_status' => $data['legal_status'] ?? 'proposed',
            'ownership_type' => $data['ownership_type'] ?? 'state',
            'owner_name' => $data['owner_name'] ?? null,
            'condition_rating' => $data['condition_rating'] ?? 'good',
            'status' => 'active',
            'created_by' => $data['user_id'],
        ]);

        $this->logAction('monument_created', 'monument', $id, $data['user_id'], null, $data);

        return $id;
    }

    public function getMonument(int $id)
    {
        return DB::table('nmmz_monument as m')
            ->leftJoin('nmmz_monument_category as c', 'm.category_id', '=', 'c.id')
            ->select(['m.*', 'c.name as category_name'])
            ->where('m.id', $id)->first();
    }

    public function getCategories()
    {
        return DB::table('nmmz_monument_category')
            ->where('is_active', 1)->orderBy('name')->get();
    }

    // =========================================================================
    // ANTIQUITIES
    // =========================================================================

    public function getAntiquities(array $filters = [])
    {
        $query = DB::table('nmmz_antiquity');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['object_type'])) {
            $query->where('object_type', $filters['object_type']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('registration_number')->get();
    }

    public function createAntiquity(array $data): int
    {
        $year = date('Y');
        $count = DB::table('nmmz_antiquity')
            ->whereYear('created_at', $year)->count() + 1;
        $regNumber = sprintf('ANT-%s-%04d', $year, $count);

        $id = DB::table('nmmz_antiquity')->insertGetId([
            'registration_number' => $regNumber,
            'information_object_id' => $data['information_object_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'object_type' => $data['object_type'] ?? null,
            'material' => $data['material'] ?? null,
            'estimated_age_years' => $data['estimated_age_years'] ?? null,
            'provenance' => $data['provenance'] ?? null,
            'find_location' => $data['find_location'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'condition_rating' => $data['condition_rating'] ?? 'good',
            'current_location' => $data['current_location'] ?? null,
            'repository_id' => $data['repository_id'] ?? null,
            'ownership_type' => $data['ownership_type'] ?? 'state',
            'export_restricted' => 1,
            'estimated_value' => $data['estimated_value'] ?? null,
            'status' => 'in_collection',
            'created_by' => $data['user_id'],
        ]);

        $this->logAction('antiquity_registered', 'antiquity', $id, $data['user_id'], null, $data);

        return $id;
    }

    public function getAntiquity(int $id)
    {
        return DB::table('nmmz_antiquity')->where('id', $id)->first();
    }

    // =========================================================================
    // EXPORT PERMITS
    // =========================================================================

    public function getPermits(array $filters = [])
    {
        $query = DB::table('nmmz_export_permit as p')
            ->leftJoin('nmmz_antiquity as a', 'p.antiquity_id', '=', 'a.id')
            ->select(['p.*', 'a.registration_number as antiquity_number', 'a.name as antiquity_name']);

        if (!empty($filters['status'])) {
            $query->where('p.status', $filters['status']);
        }

        return $query->orderBy('p.created_at', 'desc')->get();
    }

    public function createPermit(array $data): int
    {
        $year = date('Y');
        $count = DB::table('nmmz_export_permit')
            ->whereYear('created_at', $year)->count() + 1;
        $permitNumber = sprintf('EXP-%s-%04d', $year, $count);

        $validityDays = (int) $this->getConfig('export_permit_validity_days', 90);
        $validityEnd = date('Y-m-d', strtotime("+{$validityDays} days"));

        $id = DB::table('nmmz_export_permit')->insertGetId([
            'permit_number' => $permitNumber,
            'applicant_name' => $data['applicant_name'],
            'applicant_address' => $data['applicant_address'] ?? null,
            'applicant_email' => $data['applicant_email'] ?? null,
            'applicant_phone' => $data['applicant_phone'] ?? null,
            'applicant_type' => $data['applicant_type'],
            'antiquity_id' => $data['antiquity_id'] ?? null,
            'object_description' => $data['object_description'],
            'quantity' => $data['quantity'] ?? 1,
            'estimated_value' => $data['estimated_value'] ?? null,
            'export_purpose' => $data['export_purpose'],
            'purpose_details' => $data['purpose_details'] ?? null,
            'destination_country' => $data['destination_country'],
            'destination_institution' => $data['destination_institution'] ?? null,
            'application_date' => date('Y-m-d'),
            'export_date_proposed' => $data['export_date_proposed'] ?? null,
            'return_date' => $data['return_date'] ?? null,
            'validity_end' => $validityEnd,
            'fee_amount' => (float) $this->getConfig('export_permit_fee_usd', 50),
            'fee_currency' => 'USD',
            'status' => 'pending',
            'created_by' => $data['user_id'],
        ]);

        $this->logAction('permit_applied', 'export_permit', $id, $data['user_id'], null, $data);

        return $id;
    }

    public function approvePermit(int $id, int $userId, ?string $conditions = null): bool
    {
        $permit = DB::table('nmmz_export_permit')->where('id', $id)->first();
        if (!$permit || 'pending' !== $permit->status) {
            return false;
        }

        DB::table('nmmz_export_permit')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'reviewed_by' => $userId,
                'review_date' => date('Y-m-d'),
                'approval_conditions' => $conditions,
            ]);

        $this->logAction('permit_approved', 'export_permit', $id, $userId);

        return true;
    }

    public function getPermit(int $id)
    {
        return DB::table('nmmz_export_permit as p')
            ->leftJoin('nmmz_antiquity as a', 'p.antiquity_id', '=', 'a.id')
            ->select(['p.*', 'a.registration_number as antiquity_number', 'a.name as antiquity_name'])
            ->where('p.id', $id)->first();
    }

    // =========================================================================
    // ARCHAEOLOGICAL SITES
    // =========================================================================

    public function getSites(array $filters = [])
    {
        $query = DB::table('nmmz_archaeological_site as s')
            ->leftJoin('nmmz_monument as m', 's.monument_id', '=', 'm.id')
            ->select(['s.*', 'm.monument_number', 'm.name as monument_name']);

        if (!empty($filters['province'])) {
            $query->where('s.province', $filters['province']);
        }
        if (!empty($filters['protection_status'])) {
            $query->where('s.protection_status', $filters['protection_status']);
        }

        return $query->orderBy('s.site_number')->get();
    }

    public function createSite(array $data): int
    {
        $year = date('Y');
        $count = DB::table('nmmz_archaeological_site')
            ->whereYear('created_at', $year)->count() + 1;
        $siteNumber = sprintf('SITE-%s-%04d', $year, $count);

        $id = DB::table('nmmz_archaeological_site')->insertGetId([
            'site_number' => $siteNumber,
            'information_object_id' => $data['information_object_id'] ?? null,
            'monument_id' => $data['monument_id'] ?? null,
            'name' => $data['name'],
            'site_type' => $data['site_type'] ?? null,
            'description' => $data['description'] ?? null,
            'province' => $data['province'] ?? null,
            'district' => $data['district'] ?? null,
            'location_description' => $data['location_description'] ?? null,
            'gps_latitude' => $data['gps_latitude'] ?? null,
            'gps_longitude' => $data['gps_longitude'] ?? null,
            'period' => $data['period'] ?? null,
            'discovery_date' => $data['discovery_date'] ?? null,
            'discovered_by' => $data['discovered_by'] ?? null,
            'protection_status' => $data['protection_status'] ?? 'unprotected',
            'research_potential' => $data['research_potential'] ?? 'medium',
            'status' => 'active',
            'created_by' => $data['user_id'],
        ]);

        $this->logAction('site_registered', 'archaeological_site', $id, $data['user_id'], null, $data);

        return $id;
    }

    public function getSite(int $id)
    {
        return DB::table('nmmz_archaeological_site as s')
            ->leftJoin('nmmz_monument as m', 's.monument_id', '=', 'm.id')
            ->select(['s.*', 'm.monument_number', 'm.name as monument_name'])
            ->where('s.id', $id)->first();
    }

    // =========================================================================
    // HERITAGE IMPACT ASSESSMENTS
    // =========================================================================

    public function getHIAs(array $filters = [])
    {
        $query = DB::table('nmmz_heritage_impact_assessment');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['province'])) {
            $query->where('province', $filters['province']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function createHIA(array $data): int
    {
        $year = date('Y');
        $count = DB::table('nmmz_heritage_impact_assessment')
            ->whereYear('created_at', $year)->count() + 1;
        $refNumber = sprintf('HIA-%s-%04d', $year, $count);

        $id = DB::table('nmmz_heritage_impact_assessment')->insertGetId([
            'reference_number' => $refNumber,
            'project_name' => $data['project_name'],
            'project_type' => $data['project_type'] ?? null,
            'project_description' => $data['project_description'] ?? null,
            'project_location' => $data['project_location'] ?? null,
            'province' => $data['province'] ?? null,
            'district' => $data['district'] ?? null,
            'developer_name' => $data['developer_name'],
            'developer_contact' => $data['developer_contact'] ?? null,
            'developer_email' => $data['developer_email'] ?? null,
            'assessor_name' => $data['assessor_name'] ?? null,
            'assessor_qualification' => $data['assessor_qualification'] ?? null,
            'assessment_date' => $data['assessment_date'] ?? null,
            'impact_level' => $data['impact_level'] ?? 'moderate',
            'impact_description' => $data['impact_description'] ?? null,
            'mitigation_measures' => $data['mitigation_measures'] ?? null,
            'recommendation' => $data['recommendation'] ?? 'further_study',
            'status' => 'submitted',
            'created_by' => $data['user_id'],
        ]);

        $this->logAction('hia_submitted', 'hia', $id, $data['user_id'], null, $data);

        return $id;
    }

    // =========================================================================
    // INSPECTIONS
    // =========================================================================

    public function createInspection(array $data): int
    {
        $monument = $this->getMonument($data['monument_id']);

        $id = DB::table('nmmz_monument_inspection')->insertGetId([
            'monument_id' => $data['monument_id'],
            'inspection_date' => $data['inspection_date'],
            'inspector_name' => $data['inspector_name'],
            'condition_rating' => $data['condition_rating'],
            'previous_rating' => $monument ? $monument->condition_rating : null,
            'structural_condition' => $data['structural_condition'] ?? null,
            'vegetation_encroachment' => $data['vegetation_encroachment'] ?? 0,
            'vandalism_observed' => $data['vandalism_observed'] ?? 0,
            'erosion_observed' => $data['erosion_observed'] ?? 0,
            'other_damage' => $data['other_damage'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'urgent_action_required' => $data['urgent_action_required'] ?? 0,
            'follow_up_date' => $data['follow_up_date'] ?? null,
        ]);

        // Update monument's condition and last inspection date
        DB::table('nmmz_monument')
            ->where('id', $data['monument_id'])
            ->update([
                'condition_rating' => $data['condition_rating'],
                'last_inspection_date' => $data['inspection_date'],
                'status' => 'critical' === $data['condition_rating'] ? 'at_risk' : 'active',
            ]);

        return $id;
    }

    public function getMonumentInspections(int $monumentId)
    {
        return DB::table('nmmz_monument_inspection')
            ->where('monument_id', $monumentId)
            ->orderBy('inspection_date', 'desc')
            ->get();
    }

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    public function getConfig(string $key, $default = null)
    {
        $config = DB::table('nmmz_config')
            ->where('config_key', $key)->first();

        return $config ? $config->config_value : $default;
    }

    public function setConfig(string $key, $value): void
    {
        DB::table('nmmz_config')
            ->updateOrInsert(
                ['config_key' => $key],
                ['config_value' => $value]
            );
    }

    public function getAllConfig(): array
    {
        return DB::table('nmmz_config')
            ->pluck('config_value', 'config_key')
            ->toArray();
    }

    // =========================================================================
    // AUDIT
    // =========================================================================

    protected function logAction(
        string $actionType,
        string $entityType,
        int $entityId,
        ?int $userId = null,
        ?array $oldValue = null,
        ?array $newValue = null
    ): void {
        DB::table('nmmz_audit_log')->insert([
            'action_type' => $actionType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'old_value' => $oldValue ? json_encode($oldValue) : null,
            'new_value' => $newValue ? json_encode($newValue) : null,
        ]);
    }
}
