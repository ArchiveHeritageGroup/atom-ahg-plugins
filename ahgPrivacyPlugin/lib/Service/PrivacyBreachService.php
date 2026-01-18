<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Data Breach Management Service
 * Extracted from PrivacyService for better separation of concerns.
 */
class PrivacyBreachService
{
    /**
     * Get breach types
     */
    public static function getBreachTypes(): array
    {
        return [
            'confidentiality' => 'Confidentiality Breach (unauthorized disclosure)',
            'integrity' => 'Integrity Breach (unauthorized alteration)',
            'availability' => 'Availability Breach (loss or destruction)'
        ];
    }

    /**
     * Get severity levels
     */
    public static function getSeverityLevels(): array
    {
        return [
            'low' => 'Low - Minor impact, easily contained',
            'medium' => 'Medium - Moderate impact, limited exposure',
            'high' => 'High - Significant risk, many affected',
            'critical' => 'Critical - Severe harm likely'
        ];
    }

    /**
     * Get breach statuses
     */
    public static function getBreachStatuses(): array
    {
        return [
            'detected' => 'Detected',
            'investigating' => 'Investigating',
            'contained' => 'Contained',
            'resolved' => 'Resolved',
            'closed' => 'Closed'
        ];
    }

    /**
     * Get risk levels
     */
    public static function getRiskLevels(): array
    {
        return [
            '' => '-- Select --',
            'unlikely' => 'Unlikely - No significant risk',
            'possible' => 'Possible - Some risk exists',
            'likely' => 'Likely - Risk is probable',
            'high' => 'High - Risk is certain or severe'
        ];
    }

    /**
     * Get breach list with filters
     */
    public function getList(array $filters = []): Collection
    {
        $query = DB::table('privacy_breach as b')
            ->leftJoin('privacy_breach_i18n as bi', function ($j) {
                $j->on('bi.id', '=', 'b.id')->where('bi.culture', '=', 'en');
            })
            ->select(['b.*', 'bi.description', 'bi.impact_assessment', 'bi.remedial_actions']);

        if (!empty($filters['status'])) {
            $query->where('b.status', $filters['status']);
        }
        if (!empty($filters['severity'])) {
            $query->where('b.severity', $filters['severity']);
        }
        if (!empty($filters['jurisdiction'])) {
            $query->where('b.jurisdiction', $filters['jurisdiction']);
        }

        return $query->orderByDesc('b.detected_date')->get();
    }

    /**
     * Get single breach by ID
     */
    public function get(int $id): ?object
    {
        return DB::table('privacy_breach as b')
            ->leftJoin('privacy_breach_i18n as bi', function ($j) {
                $j->on('bi.id', '=', 'b.id')->where('bi.culture', '=', 'en');
            })
            ->where('b.id', $id)
            ->select(['b.*', 'bi.description', 'bi.impact_assessment', 'bi.remedial_actions', 'bi.lessons_learned'])
            ->first();
    }

    /**
     * Create new breach record
     */
    public function create(array $data, ?int $userId = null): int
    {
        $jurisdiction = $data['jurisdiction'] ?? 'popia';

        $breachData = [
            'reference_number' => $this->generateReference($jurisdiction),
            'jurisdiction' => $jurisdiction,
            'breach_type' => $data['breach_type'],
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'detected',
            'detected_date' => $data['detected_date'] ?? date('Y-m-d H:i:s'),
            'occurred_date' => (isset($data['occurred_date']) && $data['occurred_date'] !== '') ? $data['occurred_date'] : null,
            'data_categories_affected' => (isset($data['data_categories']) && $data['data_categories'] !== '') ? $data['data_categories'] : null,
            'data_subjects_affected' => (isset($data['records_affected']) && $data['records_affected'] !== '') ? (int)$data['records_affected'] : null,
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $id = DB::table('privacy_breach')->insertGetId($breachData);

        // Insert i18n
        if (!empty($data['description'])) {
            DB::table('privacy_breach_i18n')->insert([
                'id' => $id,
                'culture' => 'en',
                'description' => $data['description']
            ]);
        }

        return $id;
    }

    /**
     * Update breach record
     */
    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $updates = [
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Main fields
        if (isset($data['breach_type'])) $updates['breach_type'] = $data['breach_type'];
        if (isset($data['severity'])) $updates['severity'] = $data['severity'];
        if (isset($data['status'])) $updates['status'] = $data['status'];
        if (isset($data['risk_to_rights'])) $updates['risk_to_rights'] = $data['risk_to_rights'] ?: null;
        if (isset($data['data_subjects_affected'])) $updates['data_subjects_affected'] = $data['data_subjects_affected'] ?: null;
        if (isset($data['data_categories_affected'])) $updates['data_categories_affected'] = $data['data_categories_affected'];
        if (isset($data['assigned_to'])) $updates['assigned_to'] = $data['assigned_to'] ?: null;

        // Notification flags (checkboxes - 0 if not set)
        $updates['notification_required'] = isset($data['notification_required']) ? 1 : 0;
        $updates['regulator_notified'] = isset($data['regulator_notified']) ? 1 : 0;
        $updates['subjects_notified'] = isset($data['subjects_notified']) ? 1 : 0;

        // Dates
        if (isset($data['occurred_date'])) $updates['occurred_date'] = $data['occurred_date'] ?: null;
        if (isset($data['contained_date'])) $updates['contained_date'] = $data['contained_date'] ?: null;
        if (isset($data['resolved_date'])) $updates['resolved_date'] = $data['resolved_date'] ?: null;
        if (isset($data['regulator_notified_date'])) $updates['regulator_notified_date'] = $data['regulator_notified_date'] ?: null;
        if (isset($data['subjects_notified_date'])) $updates['subjects_notified_date'] = $data['subjects_notified_date'] ?: null;

        $result = DB::table('privacy_breach')->where('id', $id)->update($updates);

        // Update i18n
        $i18nUpdates = [];
        if (isset($data['title'])) $i18nUpdates['title'] = $data['title'];
        if (isset($data['description'])) $i18nUpdates['description'] = $data['description'];
        if (isset($data['cause'])) $i18nUpdates['cause'] = $data['cause'];
        if (isset($data['impact_assessment'])) $i18nUpdates['impact_assessment'] = $data['impact_assessment'];
        if (isset($data['remedial_actions'])) $i18nUpdates['remedial_actions'] = $data['remedial_actions'];
        if (isset($data['lessons_learned'])) $i18nUpdates['lessons_learned'] = $data['lessons_learned'];

        if (!empty($i18nUpdates)) {
            DB::table('privacy_breach_i18n')->updateOrInsert(
                ['id' => $id, 'culture' => 'en'],
                $i18nUpdates
            );
        }

        return $result >= 0;
    }

    /**
     * Generate breach reference number
     */
    public function generateReference(string $jurisdiction = 'popia'): string
    {
        $year = date('Y');
        $month = date('m');
        $jurisdictionCode = strtoupper(substr($jurisdiction, 0, 2));
        $count = DB::table('privacy_breach')
            ->whereYear('created_at', $year)
            ->where('jurisdiction', $jurisdiction)
            ->count() + 1;
        return sprintf('BRE-%s-%s%s-%04d', $jurisdictionCode, $year, $month, $count);
    }

    /**
     * Get breach statistics
     */
    public function getStatistics(?string $jurisdiction = null): array
    {
        $query = DB::table('privacy_breach');
        if ($jurisdiction) {
            $query->where('jurisdiction', $jurisdiction);
        }

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'critical' => (clone $query)
                ->where('severity', 'critical')
                ->whereNotIn('status', ['resolved', 'closed'])
                ->count(),
            'this_year' => (clone $query)
                ->whereYear('detected_date', date('Y'))
                ->count()
        ];
    }
}
