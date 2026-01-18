<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * DSAR (Data Subject Access Request) Management Service
 * Extracted from PrivacyService for better separation of concerns.
 */
class PrivacyDsarService
{
    /**
     * Generate a unique DSAR reference number
     */
    public function generateReference(string $prefix = 'DSAR', string $jurisdiction = 'popia'): string
    {
        $year = date('Y');
        $jurisdictionPrefix = strtoupper(substr($jurisdiction, 0, 2));

        $lastRef = DB::table('privacy_dsar')
            ->where('reference', 'LIKE', "{$prefix}-{$jurisdictionPrefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->value('reference');

        $sequence = 1;
        if ($lastRef && preg_match('/-(\d+)$/', $lastRef, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%s-%s-%04d', $prefix, $jurisdictionPrefix, $year, $sequence);
    }

    /**
     * Get DSAR list with filters
     */
    public function getList(array $filters = []): Collection
    {
        $query = DB::table('privacy_dsar as d')
            ->leftJoin('user as u', 'd.assigned_to', '=', 'u.id')
            ->select(
                'd.*',
                'u.username as assigned_username',
                'u.email as assigned_email'
            );

        if (!empty($filters['jurisdiction'])) {
            $query->where('d.jurisdiction', $filters['jurisdiction']);
        }

        if (!empty($filters['status'])) {
            $query->where('d.status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('d.assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('d.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('d.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('d.reference', 'LIKE', $search)
                    ->orWhere('d.subject_name', 'LIKE', $search)
                    ->orWhere('d.subject_email', 'LIKE', $search);
            });
        }

        return $query->orderBy('d.created_at', 'desc')->get();
    }

    /**
     * Get single DSAR by ID
     */
    public function get(int $id): ?object
    {
        $dsar = DB::table('privacy_dsar as d')
            ->leftJoin('user as u', 'd.assigned_to', '=', 'u.id')
            ->leftJoin('user as c', 'd.created_by', '=', 'c.id')
            ->where('d.id', $id)
            ->select(
                'd.*',
                'u.username as assigned_username',
                'u.email as assigned_email',
                'c.username as created_by_username'
            )
            ->first();

        return $dsar ?: null;
    }

    /**
     * Create new DSAR
     */
    public function create(array $data, ?int $userId = null): int
    {
        $jurisdiction = $data['jurisdiction'] ?? 'popia';
        $reference = $data['reference'] ?? $this->generateReference('DSAR', $jurisdiction);

        $jurisdictions = PrivacyService::getJurisdictions();
        $config = $jurisdictions[$jurisdiction] ?? null;
        $dsarDays = $config['dsar_days'] ?? 30;
        $dueDate = date('Y-m-d', strtotime("+{$dsarDays} days"));

        $id = DB::table('privacy_dsar')->insertGetId([
            'reference' => $reference,
            'jurisdiction' => $jurisdiction,
            'request_type' => $data['request_type'] ?? 'access',
            'status' => $data['status'] ?? 'new',
            'subject_name' => $data['subject_name'] ?? null,
            'subject_email' => $data['subject_email'] ?? null,
            'subject_phone' => $data['subject_phone'] ?? null,
            'subject_id_type' => $data['subject_id_type'] ?? null,
            'subject_id_number' => $data['subject_id_number'] ?? null,
            'description' => $data['description'] ?? null,
            'data_categories' => isset($data['data_categories']) ? json_encode($data['data_categories']) : null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'due_date' => $data['due_date'] ?? $dueDate,
            'priority' => $data['priority'] ?? 'normal',
            'source' => $data['source'] ?? 'manual',
            'created_by' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logActivity($id, 'created', 'DSAR created', $userId);

        return $id;
    }

    /**
     * Update DSAR
     */
    public function update(int $id, array $data, ?int $userId = null): bool
    {
        $existing = $this->get($id);
        if (!$existing) {
            return false;
        }

        $updateData = [
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $allowedFields = [
            'status', 'request_type', 'subject_name', 'subject_email', 'subject_phone',
            'subject_id_type', 'subject_id_number', 'description', 'assigned_to',
            'due_date', 'priority', 'response_sent_at', 'outcome', 'outcome_notes',
            'extended_deadline', 'extension_reason'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['data_categories'])) {
            $updateData['data_categories'] = json_encode($data['data_categories']);
        }

        if (isset($data['status']) && $data['status'] !== $existing->status) {
            if ($data['status'] === 'completed') {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }
            $this->logActivity($id, 'status_changed', "Status changed from {$existing->status} to {$data['status']}", $userId);
        }

        DB::table('privacy_dsar')
            ->where('id', $id)
            ->update($updateData);

        return true;
    }

    /**
     * Log DSAR activity
     */
    public function logActivity(int $dsarId, string $action, string $details, ?int $userId = null): void
    {
        DB::table('privacy_dsar_log')->insert([
            'dsar_id' => $dsarId,
            'action' => $action,
            'details' => $details,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get DSAR activity logs
     */
    public function getLogs(int $dsarId): Collection
    {
        return DB::table('privacy_dsar_log as l')
            ->leftJoin('user as u', 'l.user_id', '=', 'u.id')
            ->where('l.dsar_id', $dsarId)
            ->select('l.*', 'u.username')
            ->orderBy('l.created_at', 'desc')
            ->get();
    }

    /**
     * Get DSAR statuses
     */
    public static function getStatuses(): array
    {
        return [
            'new' => ['label' => 'New', 'color' => 'primary'],
            'acknowledged' => ['label' => 'Acknowledged', 'color' => 'info'],
            'in_progress' => ['label' => 'In Progress', 'color' => 'warning'],
            'pending_verification' => ['label' => 'Pending Verification', 'color' => 'secondary'],
            'extended' => ['label' => 'Extended', 'color' => 'dark'],
            'completed' => ['label' => 'Completed', 'color' => 'success'],
            'rejected' => ['label' => 'Rejected', 'color' => 'danger'],
        ];
    }

    /**
     * Get DSAR outcomes
     */
    public static function getOutcomes(): array
    {
        return [
            'fulfilled' => 'Request Fulfilled',
            'partially_fulfilled' => 'Partially Fulfilled',
            'denied_exemption' => 'Denied (Exemption Applies)',
            'denied_identity' => 'Denied (Identity Not Verified)',
            'denied_excessive' => 'Denied (Manifestly Excessive)',
            'withdrawn' => 'Withdrawn by Subject',
        ];
    }
}
