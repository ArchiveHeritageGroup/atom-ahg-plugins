<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Records-management subsystems for ahgIntegrityPlugin:
 *   - Vital records register (review cycles)
 *   - Record declarations workflow (draft -> pending_approval -> declared)
 *   - Destruction certificates
 *   - Retention trigger events
 *
 * Pure Laravel Query Builder; no base-AtoM changes.
 */
class IntegrityRecordsService
{
    protected function culture(): string
    {
        return class_exists('\AtomExtensions\Helpers\CultureHelper')
            ? \AtomExtensions\Helpers\CultureHelper::getCulture()
            : 'en';
    }

    // ------------------------------------------------------------------
    // Vital records
    // ------------------------------------------------------------------

    public function isVital(int $ioId): bool
    {
        return DB::table('vital_record')
            ->where('information_object_id', $ioId)
            ->where('is_active', 1)
            ->exists();
    }

    public function flagAsVital(int $ioId, string $reason, int $reviewCycleDays, int $userId): int
    {
        $reviewCycleDays = $reviewCycleDays > 0 ? $reviewCycleDays : 365;
        $existing = DB::table('vital_record')->where('information_object_id', $ioId)->first();

        $payload = [
            'reason' => $reason ?: null,
            'review_cycle_days' => $reviewCycleDays,
            'next_review_date' => date('Y-m-d', strtotime("+{$reviewCycleDays} days")),
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            DB::table('vital_record')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        $payload['information_object_id'] = $ioId;
        $payload['created_by'] = $userId;
        $payload['created_at'] = date('Y-m-d H:i:s');

        return DB::table('vital_record')->insertGetId($payload);
    }

    public function unflagVital(int $ioId): bool
    {
        return DB::table('vital_record')
            ->where('information_object_id', $ioId)
            ->update(['is_active' => 0, 'updated_at' => date('Y-m-d H:i:s')]) > 0;
    }

    public function reviewVitalRecord(int $id, int $userId): bool
    {
        $vr = DB::table('vital_record')->where('id', $id)->first();
        if (!$vr) {
            return false;
        }
        $cycle = (int) ($vr->review_cycle_days ?: 365);

        return DB::table('vital_record')->where('id', $id)->update([
            'last_reviewed_at' => date('Y-m-d H:i:s'),
            'last_reviewed_by' => $userId,
            'next_review_date' => date('Y-m-d', strtotime("+{$cycle} days")),
            'updated_at' => date('Y-m-d H:i:s'),
        ]) >= 0;
    }

    public function getVitalRecords(bool $activeOnly = true): array
    {
        $q = $this->withIoTitle(DB::table('vital_record as vr'), 'vr.information_object_id')
            ->select('vr.*', 'ioi.title as record_title');
        if ($activeOnly) {
            $q->where('vr.is_active', 1);
        }

        return $q->orderBy('vr.next_review_date')->get()->all();
    }

    public function getOverdueReviews(): array
    {
        return $this->withIoTitle(DB::table('vital_record as vr'), 'vr.information_object_id')
            ->select('vr.*', 'ioi.title as record_title')
            ->where('vr.is_active', 1)
            ->whereNotNull('vr.next_review_date')
            ->whereDate('vr.next_review_date', '<=', date('Y-m-d'))
            ->orderBy('vr.next_review_date')
            ->get()->all();
    }

    // ------------------------------------------------------------------
    // Record declarations
    // ------------------------------------------------------------------

    public function getRecordStatus(int $ioId): ?string
    {
        $row = DB::table('record_declaration')
            ->where('information_object_id', $ioId)
            ->orderByDesc('id')
            ->first();

        return $row->status ?? null;
    }

    public function declareRecord(int $ioId, int $userId, ?string $notes = null, bool $requireApproval = true): int
    {
        $existing = DB::table('record_declaration')->where('information_object_id', $ioId)->orderByDesc('id')->first();
        $status = $requireApproval ? 'pending_approval' : 'declared';

        $payload = [
            'status' => $status,
            'notes' => $notes ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!$requireApproval) {
            $payload['declared_by'] = $userId;
            $payload['declared_at'] = date('Y-m-d H:i:s');
        }

        if ($existing && 'declared' !== $existing->status) {
            DB::table('record_declaration')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }
        if ($existing && 'declared' === $existing->status) {
            return (int) $existing->id;
        }

        $payload['information_object_id'] = $ioId;
        $payload['created_at'] = date('Y-m-d H:i:s');

        return DB::table('record_declaration')->insertGetId($payload);
    }

    public function approveDeclaration(int $id, int $userId): bool
    {
        return DB::table('record_declaration')->where('id', $id)->update([
            'status' => 'declared',
            'declared_by' => $userId,
            'declared_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]) > 0;
    }

    public function getDeclarations(?string $status = null): array
    {
        $q = $this->withIoTitle(DB::table('record_declaration as rd'), 'rd.information_object_id')
            ->select('rd.*', 'ioi.title as record_title');
        if ($status) {
            $q->where('rd.status', $status);
        }

        return $q->orderByDesc('rd.id')->get()->all();
    }

    // ------------------------------------------------------------------
    // Destruction certificates
    // ------------------------------------------------------------------

    public function getCertificateNumber(): string
    {
        $year = date('Y');
        $count = DB::table('destruction_certificate')->whereYear('created_at', $year)->count() + 1;

        return sprintf('DC-%s-%04d', $year, $count);
    }

    public function generateCertificate(array $data, int $userId): int
    {
        $number = $this->getCertificateNumber();
        $date = $data['destruction_date'] ?? date('Y-m-d');
        $hash = hash('sha256', implode('|', [
            $number,
            $data['information_object_id'] ?? '',
            $date,
            $data['destruction_method'] ?? '',
            $data['authorized_by'] ?? $userId,
        ]));

        return DB::table('destruction_certificate')->insertGetId([
            'disposition_queue_id' => $data['disposition_queue_id'] ?? null,
            'information_object_id' => $data['information_object_id'] ?? null,
            'certificate_number' => $number,
            'destruction_date' => $date,
            'destruction_method' => $data['destruction_method'] ?? null,
            'authorized_by' => $data['authorized_by'] ?? $userId,
            'witness' => $data['witness'] ?? null,
            'content_hash' => $hash,
            'pdf_path' => $data['pdf_path'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getCertificates(): array
    {
        return $this->withIoTitle(DB::table('destruction_certificate as dc'), 'dc.information_object_id')
            ->select('dc.*', 'ioi.title as record_title')
            ->orderByDesc('dc.created_at')
            ->get()->all();
    }

    public function getCertificate(int $id): ?object
    {
        return $this->withIoTitle(DB::table('destruction_certificate as dc'), 'dc.information_object_id')
            ->select('dc.*', 'ioi.title as record_title')
            ->where('dc.id', $id)
            ->first();
    }

    // ------------------------------------------------------------------
    // Retention trigger events
    // ------------------------------------------------------------------

    public function getEventTypes(): array
    {
        return ['superseded', 'case_closed', 'employee_left', 'project_ended', 'contract_expired', 'other'];
    }

    public function fireRetentionEvent(int $ioId, string $eventType, ?string $eventDate, int $userId, ?string $notes = null): int
    {
        return DB::table('retention_trigger_event')->insertGetId([
            'information_object_id' => $ioId,
            'event_type' => $eventType,
            'event_date' => $eventDate ?: date('Y-m-d'),
            'triggered_by' => $userId,
            'notes' => $notes ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getRetentionEvents(?int $ioId = null): array
    {
        $q = $this->withIoTitle(DB::table('retention_trigger_event as rte'), 'rte.information_object_id')
            ->select('rte.*', 'ioi.title as record_title');
        if ($ioId) {
            $q->where('rte.information_object_id', $ioId);
        }

        return $q->orderByDesc('rte.id')->limit(500)->get()->all();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Left-join information_object_i18n (current culture) for a record title.
     */
    protected function withIoTitle($query, string $ioColumn)
    {
        $culture = $this->culture();

        return $query->leftJoin('information_object_i18n as ioi', function ($j) use ($ioColumn, $culture) {
            $j->on($ioColumn, '=', 'ioi.id')->where('ioi.culture', '=', $culture);
        });
    }
}
