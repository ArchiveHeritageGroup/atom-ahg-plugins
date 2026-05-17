<?php

namespace AhgExtendedRights\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * RetentionScheduleService — manages File-Plan retention schedules and the
 * (record × schedule) assignments that drive disposal due-dates.
 *
 * GCIS RFB-001 clauses 4.1.1.8 (configure per File Plan) and 4.1.1.13.a
 * (automated/manual enforcement of retention schedules).
 *
 * @phase A (2026-05-17)
 */
class RetentionScheduleService
{
    public const TRIGGER_EVENTS = [
        'creation_date'   => 'Record creation date',
        'file_closure'    => 'File closure date',
        'fiscal_year_end' => 'End of fiscal year',
        'contract_end'    => 'Contract end date',
        'employment_end'  => 'Employment end date',
    ];

    public const DISPOSAL_ACTIONS = [
        'destroy'         => 'Destroy (controlled disposal)',
        'transfer_narssa' => 'Transfer to NARSSA (National Archives)',
        'transfer_other'  => 'Transfer to other archive',
        'review'          => 'Manual review (re-appraise)',
        'permanent'       => 'Permanent retention (never dispose)',
    ];

    public function listSchedules(bool $activeOnly = true): array
    {
        $q = DB::table('retention_schedule')->orderBy('code');
        if ($activeOnly) {
            $q->where('is_active', 1);
        }
        return $q->get()->map(fn ($r) => (array) $r)->all();
    }

    public function getByCode(string $code): ?object
    {
        return DB::table('retention_schedule')->where('code', $code)->first();
    }

    public function get(int $id): ?object
    {
        return DB::table('retention_schedule')->where('id', $id)->first();
    }

    public function create(array $data): int
    {
        $this->validateAction($data['disposal_action'] ?? '');
        $this->validateTrigger($data['trigger_event'] ?? '');
        return DB::table('retention_schedule')->insertGetId([
            'code'                       => $data['code'],
            'title'                      => $data['title'],
            'description'                => $data['description']                ?? null,
            'active_period_years'        => (int) ($data['active_period_years']  ?? 5),
            'dormant_period_years'       => (int) ($data['dormant_period_years'] ?? 0),
            'trigger_event'              => $data['trigger_event']              ?? 'creation_date',
            'disposal_action'            => $data['disposal_action']            ?? 'review',
            'legal_basis'                => $data['legal_basis']                ?? null,
            'requires_legal_signoff'     => !empty($data['requires_legal_signoff'])     ? 1 : 0,
            'requires_executive_signoff' => !empty($data['requires_executive_signoff']) ? 1 : 0,
            'is_active'                  => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'created_at'                 => date('Y-m-d H:i:s'),
            'updated_at'                 => date('Y-m-d H:i:s'),
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $allowed = ['title', 'description', 'active_period_years', 'dormant_period_years',
                    'trigger_event', 'disposal_action', 'legal_basis',
                    'requires_legal_signoff', 'requires_executive_signoff', 'is_active'];
        $upd = array_intersect_key($data, array_flip($allowed));
        if (isset($upd['disposal_action'])) { $this->validateAction($upd['disposal_action']); }
        if (isset($upd['trigger_event']))   { $this->validateTrigger($upd['trigger_event']); }
        if (empty($upd)) {
            return false;
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        return (bool) DB::table('retention_schedule')->where('id', $id)->update($upd);
    }

    public function delete(int $id): bool
    {
        if (DB::table('retention_assignment')->where('retention_schedule_id', $id)->exists()) {
            throw new \RuntimeException('Cannot delete a schedule that is in use by retention_assignment rows');
        }
        return (bool) DB::table('retention_schedule')->where('id', $id)->delete();
    }

    /**
     * Assign a record to a retention schedule. Calculates the disposal-due date
     * from the schedule + supplied trigger event date.
     */
    public function assign(int $informationObjectId, int $scheduleId, string $triggerEventDate, ?int $userId = null, ?string $notes = null): int
    {
        $schedule = $this->get($scheduleId);
        if (!$schedule) {
            throw new \InvalidArgumentException("Schedule {$scheduleId} not found");
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $triggerEventDate)) {
            throw new \InvalidArgumentException("trigger_event_date must be YYYY-MM-DD");
        }
        $dispose = new \DateTimeImmutable($triggerEventDate);
        $dispose = $dispose->modify('+' . (int) $schedule->active_period_years . ' years');
        $dispose = $dispose->modify('+' . (int) $schedule->dormant_period_years . ' years');

        $existing = DB::table('retention_assignment')->where('information_object_id', $informationObjectId)->first();
        if ($existing) {
            DB::table('retention_assignment')->where('id', $existing->id)->update([
                'retention_schedule_id'    => $scheduleId,
                'trigger_event_date'       => $triggerEventDate,
                'calculated_disposal_due'  => $dispose->format('Y-m-d'),
                'assigned_by'              => $userId,
                'notes'                    => $notes,
                'updated_at'               => date('Y-m-d H:i:s'),
            ]);
            return (int) $existing->id;
        }
        return (int) DB::table('retention_assignment')->insertGetId([
            'information_object_id'   => $informationObjectId,
            'retention_schedule_id'   => $scheduleId,
            'trigger_event_date'      => $triggerEventDate,
            'calculated_disposal_due' => $dispose->format('Y-m-d'),
            'assigned_by'             => $userId,
            'notes'                   => $notes,
            'created_at'              => date('Y-m-d H:i:s'),
            'updated_at'              => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAssignment(int $informationObjectId): ?object
    {
        return DB::table('retention_assignment as ra')
            ->leftJoin('retention_schedule as rs', 'ra.retention_schedule_id', '=', 'rs.id')
            ->where('ra.information_object_id', $informationObjectId)
            ->select('ra.*', 'rs.code as schedule_code', 'rs.title as schedule_title',
                     'rs.disposal_action', 'rs.requires_legal_signoff', 'rs.requires_executive_signoff', 'rs.legal_basis')
            ->first();
    }

    /**
     * Records due for disposal (calculated_disposal_due <= today + look-ahead days).
     * Excludes records that already have a non-terminal disposal_action row.
     */
    public function dueRecords(int $lookAheadDays = 30, int $limit = 200): array
    {
        $cutoff = (new \DateTimeImmutable('+' . $lookAheadDays . ' days'))->format('Y-m-d');
        return DB::table('retention_assignment as ra')
            ->join('retention_schedule as rs', 'ra.retention_schedule_id', '=', 'rs.id')
            ->leftJoin('disposal_action as da', function ($j) {
                $j->on('ra.information_object_id', '=', 'da.information_object_id')
                  ->whereIn('da.status', ['proposed', 'officer_signed', 'legal_signed', 'executive_signed', 'approved']);
            })
            ->whereNull('da.id')
            ->where('rs.disposal_action', '!=', 'permanent')
            ->where('ra.calculated_disposal_due', '<=', $cutoff)
            ->orderBy('ra.calculated_disposal_due')
            ->limit($limit)
            ->select(
                'ra.information_object_id',
                'ra.calculated_disposal_due',
                'ra.trigger_event_date',
                'rs.code as schedule_code',
                'rs.title as schedule_title',
                'rs.disposal_action',
                'rs.requires_legal_signoff',
                'rs.requires_executive_signoff'
            )
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    private function validateAction(string $action): void
    {
        if (!isset(self::DISPOSAL_ACTIONS[$action])) {
            throw new \InvalidArgumentException("Unknown disposal_action: {$action}");
        }
    }
    private function validateTrigger(string $trigger): void
    {
        if (!isset(self::TRIGGER_EVENTS[$trigger])) {
            throw new \InvalidArgumentException("Unknown trigger_event: {$trigger}");
        }
    }
}
