<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * DpiaService — GDPR Article 35 Data Protection Impact Assessment workflow.
 *
 * PSIS-parity twin of the Heratio AhgPrivacy\Services\DpiaService (#131).
 * Workflow: draft -> review -> completed (sign-off) -> archived. Sign-off
 * stamps the linked ROPA entry (privacy_processing_activity.dpia_completed /
 * dpia_date) and writes a best-effort audit row. High-risk is auto-flagged
 * from special-category / large-scale / biometric / cross-border indicators.
 *
 * @package ahgPrivacyPlugin
 */
class DpiaService
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED = 'archived';

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_REVIEW, self::STATUS_COMPLETED, self::STATUS_ARCHIVED];
    }

    /** All DPIAs, newest activity first, with linked activity name. */
    public function listAll(): array
    {
        return DB::table('privacy_dpia as d')
            ->leftJoin('privacy_processing_activity as p', 'd.processing_activity_id', '=', 'p.id')
            ->orderByDesc('d.updated_at')
            ->select('d.*', 'p.name as activity_name')
            ->get()->all();
    }

    public function find(int $id): ?object
    {
        return DB::table('privacy_dpia')->where('id', $id)->first();
    }

    /** Active ROPA entries for the linked-activity dropdown. */
    public function listActivities(): array
    {
        $q = DB::table('privacy_processing_activity')->orderBy('name');
        if (DB::schema()->hasColumn('privacy_processing_activity', 'is_active')) {
            $q->where('is_active', 1);
        }

        return $q->select('id', 'name')->get()->all();
    }

    public function create(array $data, ?int $createdByUserId = null): int
    {
        $payload = $this->sanitize($data);
        $payload['created_by_user_id'] = $createdByUserId;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');

        return (int) DB::table('privacy_dpia')->insertGetId($payload);
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->sanitize($data);
        $payload['updated_at'] = date('Y-m-d H:i:s');

        return DB::table('privacy_dpia')->where('id', $id)->update($payload) >= 0;
    }

    public function moveToReview(int $id): bool
    {
        return DB::table('privacy_dpia')->where('id', $id)
            ->update(['status' => self::STATUS_REVIEW, 'updated_at' => date('Y-m-d H:i:s')]) >= 0;
    }

    /**
     * Sign the DPIA off as completed: stamp sign-off fields, mark the linked
     * ROPA entry's DPIA as done, and write a best-effort audit row.
     */
    public function signOff(int $id, int $userId, ?string $note = null): bool
    {
        $dpia = $this->find($id);
        if (!$dpia) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        DB::table('privacy_dpia')->where('id', $id)->update([
            'status' => self::STATUS_COMPLETED,
            'signed_off_by_user_id' => $userId,
            'signed_off_at' => $now,
            'completed_at' => $dpia->completed_at ?: date('Y-m-d'),
            'updated_at' => $now,
        ]);

        // ROPA integration (#131 deliverable 3): completing a DPIA marks the
        // linked processing activity's DPIA as done.
        if (!empty($dpia->processing_activity_id)) {
            $upd = ['dpia_completed' => 1];
            if (DB::schema()->hasColumn('privacy_processing_activity', 'dpia_date')) {
                $upd['dpia_date'] = date('Y-m-d');
            }
            DB::table('privacy_processing_activity')->where('id', $dpia->processing_activity_id)->update($upd);
        }

        $this->writeAudit('dpia.signoff', $id, $userId, $note);

        return true;
    }

    public function archive(int $id, int $userId): bool
    {
        $ok = DB::table('privacy_dpia')->where('id', $id)
            ->update(['status' => self::STATUS_ARCHIVED, 'updated_at' => date('Y-m-d H:i:s')]) >= 0;
        $this->writeAudit('dpia.archive', $id, $userId, null);

        return $ok;
    }

    private function sanitize(array $data): array
    {
        $status = in_array($data['status'] ?? '', self::statuses(), true)
            ? (string) $data['status'] : self::STATUS_DRAFT;

        $payload = [
            'name' => trim((string) ($data['name'] ?? '')),
            'processing_activity_id' => isset($data['processing_activity_id']) && $data['processing_activity_id'] !== ''
                ? (int) $data['processing_activity_id'] : null,
            'description' => $this->nullableText($data['description'] ?? null),
            'necessity_proportionality' => $this->nullableText($data['necessity_proportionality'] ?? null),
            'risks_to_subjects' => $this->nullableText($data['risks_to_subjects'] ?? null),
            'measures_to_mitigate' => $this->nullableText($data['measures_to_mitigate'] ?? null),
            'residual_risks' => $this->nullableText($data['residual_risks'] ?? null),
            'dpo_opinion' => $this->nullableText($data['dpo_opinion'] ?? null),
            'dpo_consulted_at' => $this->nullableDate($data['dpo_consulted_at'] ?? null),
            'completed_at' => $this->nullableDate($data['completed_at'] ?? null),
            'status' => $status,
        ];

        // High-risk auto-flag (#131 deliverable 4): special category, large-scale
        // profiling, biometric, or cross-border transfer indicators. An explicit
        // checkbox can also force it on.
        $payload['high_risk'] = (!empty($data['high_risk']) || $this->detectHighRisk($payload)) ? 1 : 0;

        return $payload;
    }

    private function detectHighRisk(array $p): bool
    {
        $hay = strtolower(implode(' ', [
            $p['description'] ?? '', $p['necessity_proportionality'] ?? '',
            $p['risks_to_subjects'] ?? '',
        ]));
        foreach (['special categor', 'biometric', 'large-scale', 'large scale', 'profiling',
            'cross-border', 'cross border', 'genetic', 'health data', 'criminal'] as $needle) {
            if (str_contains($hay, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function nullableText($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $t = trim((string) $value);

        return $t === '' ? null : $t;
    }

    private function nullableDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $ts = strtotime((string) $value);

        return $ts === false ? null : date('Y-m-d', $ts);
    }

    /** Best-effort audit row; never blocks the workflow. */
    private function writeAudit(string $action, int $dpiaId, int $userId, ?string $note): void
    {
        try {
            if (!DB::schema()->hasTable('privacy_audit_log')) {
                return;
            }
            $row = [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => 'privacy_dpia',
                'entity_id' => $dpiaId,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            if (DB::schema()->hasColumn('privacy_audit_log', 'new_values')) {
                $row['new_values'] = json_encode(['note' => $note], JSON_UNESCAPED_SLASHES);
            }
            DB::table('privacy_audit_log')->insert($row);
        } catch (\Throwable $e) {
            // audit unavailable — ignore
        }
    }
}
