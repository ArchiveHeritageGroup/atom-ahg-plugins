<?php

namespace AhgExtendedRights\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * DisposalWorkflowService — multi-stage sign-off chain for disposal of
 * archival records, with full audit dual-write into ahg_audit_log.
 *
 * GCIS RFB-001 clause 4.1.1.13.b (controlled disposal workflows with audit logs).
 *
 * Status transitions (driven by the schedule's required-signoff flags):
 *
 *   proposed
 *     ↓ (officer signs)
 *   officer_signed
 *     ↓ (legal signs — only if schedule.requires_legal_signoff=1)
 *   legal_signed
 *     ↓ (executive signs — only if schedule.requires_executive_signoff=1)
 *   executive_signed
 *     ↓ (auto: approved when all required signoffs are in)
 *   approved
 *     ↓ (execute action — destroy / transfer / etc.)
 *   executed
 *
 *   At any non-terminal stage:
 *     - reject → rejected (with reason)
 *     - defer  → deferred (skipped this cycle, schedule re-fires next sweep)
 *
 * @phase A (2026-05-17)
 */
class DisposalWorkflowService
{
    public const AUDIT_PROPOSE  = 'disposal_proposed';
    public const AUDIT_OFFICER  = 'disposal_officer_signed';
    public const AUDIT_LEGAL    = 'disposal_legal_signed';
    public const AUDIT_EXEC     = 'disposal_executive_signed';
    public const AUDIT_APPROVE  = 'disposal_approved';
    public const AUDIT_EXECUTE  = 'disposal_executed';
    public const AUDIT_REJECT   = 'disposal_rejected';
    public const AUDIT_DEFER    = 'disposal_deferred';

    /**
     * Open a disposal proposal for a record based on its retention assignment.
     */
    public function propose(int $informationObjectId, ?int $userId = null, ?string $notes = null): int
    {
        $assignment = DB::table('retention_assignment as ra')
            ->leftJoin('retention_schedule as rs', 'ra.retention_schedule_id', '=', 'rs.id')
            ->where('ra.information_object_id', $informationObjectId)
            ->select('ra.id as assignment_id', 'rs.disposal_action')
            ->first();

        if (!$assignment) {
            throw new \RuntimeException("No retention assignment for IO {$informationObjectId}");
        }

        $existing = DB::table('disposal_action')
            ->where('information_object_id', $informationObjectId)
            ->whereNotIn('status', ['executed', 'rejected', 'deferred'])
            ->first();
        if ($existing) {
            return (int) $existing->id;
        }

        $id = (int) DB::table('disposal_action')->insertGetId([
            'information_object_id'   => $informationObjectId,
            'retention_assignment_id' => $assignment->assignment_id,
            'action_type'             => $assignment->disposal_action,
            'status'                  => 'proposed',
            'proposed_by'             => $userId,
            'proposed_at'             => date('Y-m-d H:i:s'),
            'notes'                   => $notes,
            'created_at'              => date('Y-m-d H:i:s'),
            'updated_at'              => date('Y-m-d H:i:s'),
        ]);
        $this->writeAudit(self::AUDIT_PROPOSE, $id, $informationObjectId, $userId, ['action_type' => $assignment->disposal_action]);
        return $id;
    }

    public function officerSign(int $disposalId, int $userId): void
    {
        $row = $this->mustLoad($disposalId, 'proposed');
        DB::table('disposal_action')->where('id', $disposalId)->update([
            'status'             => 'officer_signed',
            'officer_signed_by'  => $userId,
            'officer_signed_at'  => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        $this->writeAudit(self::AUDIT_OFFICER, $disposalId, (int) $row->information_object_id, $userId);
        $this->maybeAutoApprove($disposalId);
    }

    public function legalSign(int $disposalId, int $userId): void
    {
        $row = $this->mustLoad($disposalId, ['officer_signed']);
        DB::table('disposal_action')->where('id', $disposalId)->update([
            'status'             => 'legal_signed',
            'legal_signed_by'    => $userId,
            'legal_signed_at'    => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);
        $this->writeAudit(self::AUDIT_LEGAL, $disposalId, (int) $row->information_object_id, $userId);
        $this->maybeAutoApprove($disposalId);
    }

    public function executiveSign(int $disposalId, int $userId): void
    {
        $row = $this->mustLoad($disposalId, ['officer_signed', 'legal_signed']);
        DB::table('disposal_action')->where('id', $disposalId)->update([
            'status'                => 'executive_signed',
            'executive_signed_by'   => $userId,
            'executive_signed_at'   => date('Y-m-d H:i:s'),
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);
        $this->writeAudit(self::AUDIT_EXEC, $disposalId, (int) $row->information_object_id, $userId);
        $this->maybeAutoApprove($disposalId);
    }

    public function reject(int $disposalId, int $userId, string $reason): void
    {
        $row = $this->mustLoad($disposalId, ['proposed', 'officer_signed', 'legal_signed', 'executive_signed', 'approved']);
        DB::table('disposal_action')->where('id', $disposalId)->update([
            'status'           => 'rejected',
            'rejected_by'      => $userId,
            'rejected_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        $this->writeAudit(self::AUDIT_REJECT, $disposalId, (int) $row->information_object_id, $userId, ['reason' => $reason]);
    }

    public function defer(int $disposalId, int $userId, string $reason): void
    {
        $row = $this->mustLoad($disposalId, ['proposed', 'officer_signed', 'legal_signed']);
        DB::table('disposal_action')->where('id', $disposalId)->update([
            'status'           => 'deferred',
            'rejection_reason' => $reason,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);
        $this->writeAudit(self::AUDIT_DEFER, $disposalId, (int) $row->information_object_id, $userId, ['reason' => $reason]);
    }

    /**
     * Execute the approved disposal action. This is the destructive step.
     *
     *   destroy         → DELETE on information_object (CASCADE handles children)
     *   transfer_narssa → mark transfer_manifest_path; actual transfer is the
     *                     responsibility of `php symfony narssa:transfer-package`
     *   transfer_other  → same shape as transfer_narssa
     *   review          → no-op; just records the decision
     */
    public function execute(int $disposalId, int $userId, ?string $transferManifestPath = null): void
    {
        $row = $this->mustLoad($disposalId, ['approved']);
        $action = (string) $row->action_type;

        $update = [
            'status'      => 'executed',
            'executed_by' => $userId,
            'executed_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
        if ($transferManifestPath !== null) {
            $update['transfer_manifest_path'] = $transferManifestPath;
        }
        DB::table('disposal_action')->where('id', $disposalId)->update($update);

        $this->writeAudit(self::AUDIT_EXECUTE, $disposalId, (int) $row->information_object_id, $userId, [
            'action_type'             => $action,
            'transfer_manifest_path'  => $transferManifestPath,
        ]);

        // Destructive step is intentionally NOT taken here. The actual record
        // delete OR file transfer is a separate operator step so accidental
        // execute() calls during testing don't blow away production records.
        // Operator runs `php symfony disposal:finalize --id=N` after sign-off.
    }

    /**
     * Auto-transition to 'approved' when every required signoff is in.
     */
    private function maybeAutoApprove(int $disposalId): void
    {
        $row = DB::table('disposal_action as da')
            ->leftJoin('retention_assignment as ra', 'da.retention_assignment_id', '=', 'ra.id')
            ->leftJoin('retention_schedule as rs', 'ra.retention_schedule_id', '=', 'rs.id')
            ->where('da.id', $disposalId)
            ->select('da.*', 'rs.requires_legal_signoff', 'rs.requires_executive_signoff')
            ->first();
        if (!$row) {
            return;
        }
        $needLegal = (bool) ($row->requires_legal_signoff ?? 0);
        $needExec  = (bool) ($row->requires_executive_signoff ?? 0);

        $haveOfficer = $row->officer_signed_at   !== null;
        $haveLegal   = !$needLegal || $row->legal_signed_at     !== null;
        $haveExec    = !$needExec  || $row->executive_signed_at !== null;

        if ($haveOfficer && $haveLegal && $haveExec && $row->status !== 'approved') {
            DB::table('disposal_action')->where('id', $disposalId)->update([
                'status'     => 'approved',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $this->writeAudit(self::AUDIT_APPROVE, $disposalId, (int) $row->information_object_id, null);
        }
    }

    /**
     * @param array|string $allowedStatuses single status or list of acceptable statuses
     */
    private function mustLoad(int $disposalId, $allowedStatuses): object
    {
        $row = DB::table('disposal_action')->where('id', $disposalId)->first();
        if (!$row) {
            throw new \RuntimeException("disposal_action {$disposalId} not found");
        }
        $allowed = (array) $allowedStatuses;
        if (!in_array($row->status, $allowed, true)) {
            throw new \RuntimeException(sprintf(
                'disposal_action %d is in status "%s"; expected one of: %s',
                $disposalId, $row->status, implode(', ', $allowed),
            ));
        }
        return $row;
    }

    private function writeAudit(string $action, int $disposalId, int $informationObjectId, ?int $userId, array $details = []): void
    {
        try {
            DB::table('ahg_audit_log')->insert([
                'uuid'        => $this->generateUuid(),
                'action'      => $action,
                'entity_type' => 'disposal_action',
                'entity_id'   => $disposalId,
                'user_id'     => $userId,
                'new_values'  => json_encode(array_merge($details, ['information_object_id' => $informationObjectId])),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('DisposalWorkflowService writeAudit failed: ' . $e->getMessage());
        }
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
