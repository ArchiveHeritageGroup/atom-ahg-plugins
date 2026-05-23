<?php

/**
 * SpectrumComplianceService - PSIS Symfony port of heratio Spectrum Phase C.
 *
 * Per-object compliance derivation + heatmap aggregation + cross-procedure
 * chain rules + overdue scan. Mirrors the Heratio Laravel implementation
 * 1:1 in behaviour.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class SpectrumComplianceService
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed';
    public const STATUS_OVERDUE     = 'overdue';
    public const STATUS_REJECTED    = 'rejected';

    public const STATUSES = [
        self::STATUS_NOT_STARTED => 'Not started',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_COMPLETED   => 'Completed',
        self::STATUS_OVERDUE     => 'Overdue',
        self::STATUS_REJECTED    => 'Rejected',
    ];

    public function computeStatus(int $objectId, string $procedure, string $objectType = 'information_object', ?int $overdueDays = null): array
    {
        $tasks = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 'w.id', '=', 't.workflow_id')
            ->where('t.object_id', $objectId)
            ->where('t.object_type', $objectType)
            ->where('w.spectrum_procedure', $procedure)
            ->orderBy('t.created_at')
            ->orderBy('t.id')
            ->get(['t.id', 't.status', 't.decision', 't.created_at', 't.decision_at']);

        if ($tasks->isEmpty()) {
            return ['status' => self::STATUS_NOT_STARTED, 'started_at' => null, 'completed_at' => null, 'last_task_id' => null];
        }

        $startedAt = (string) $tasks->first()->created_at;
        $lastTaskId = (int) $tasks->last()->id;

        $rejected = $tasks->first(function ($t) { return $t->decision === 'rejected'; });
        if ($rejected !== null) {
            return ['status' => self::STATUS_REJECTED, 'started_at' => $startedAt, 'completed_at' => null, 'last_task_id' => $lastTaskId];
        }

        $latest = $tasks->last();
        if ($latest->decision === 'approved' || $latest->status === 'completed') {
            return ['status' => self::STATUS_COMPLETED, 'started_at' => $startedAt, 'completed_at' => (string) ($latest->decision_at ?? $latest->created_at), 'last_task_id' => $lastTaskId];
        }

        $createdTs = strtotime((string) $latest->created_at);
        if ($overdueDays !== null && $overdueDays > 0 && $createdTs !== false) {
            $ageDays = (time() - $createdTs) / 86400;
            if ($ageDays > $overdueDays) {
                return ['status' => self::STATUS_OVERDUE, 'started_at' => $startedAt, 'completed_at' => null, 'last_task_id' => $lastTaskId];
            }
        }

        return ['status' => self::STATUS_IN_PROGRESS, 'started_at' => $startedAt, 'completed_at' => null, 'last_task_id' => $lastTaskId];
    }

    public function heatmap(string $objectType = 'information_object', ?int $overdueDays = 30): array
    {
        $totalObjects = (int) DB::table('object')
            ->where('class_name', $objectType === 'information_object' ? 'QubitInformationObject' : ucfirst($objectType))
            ->count();

        $rows = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 'w.id', '=', 't.workflow_id')
            ->whereNotNull('w.spectrum_procedure')
            ->where('t.object_type', $objectType)
            ->select(
                'w.spectrum_procedure',
                't.object_id',
                DB::raw('GROUP_CONCAT(DISTINCT t.decision) as decisions'),
                DB::raw('GROUP_CONCAT(DISTINCT t.status) as statuses'),
                DB::raw('MAX(t.created_at) as latest_created_at')
            )
            ->groupBy('w.spectrum_procedure', 't.object_id')
            ->get();

        require_once __DIR__.'/SpectrumProcedureCatalog.php';
        $codes = SpectrumProcedureCatalog::all();
        $heatmap = [];
        foreach ($codes as $code => $label) {
            $heatmap[$code] = [
                'label' => $label,
                'totals' => [
                    self::STATUS_NOT_STARTED => 0,
                    self::STATUS_IN_PROGRESS => 0,
                    self::STATUS_COMPLETED   => 0,
                    self::STATUS_OVERDUE     => 0,
                    self::STATUS_REJECTED    => 0,
                ],
                'total_objects' => $totalObjects,
                'percent_completed' => 0,
            ];
        }

        $seenObjectsByProc = [];
        foreach ($rows as $r) {
            $proc = (string) $r->spectrum_procedure;
            if (!isset($heatmap[$proc])) continue;
            $seenObjectsByProc[$proc][] = (int) $r->object_id;
            $decisions = explode(',', (string) $r->decisions);
            $statuses = explode(',', (string) $r->statuses);

            $status = self::STATUS_IN_PROGRESS;
            if (in_array('rejected', $decisions, true)) {
                $status = self::STATUS_REJECTED;
            } elseif (in_array('approved', $decisions, true) || in_array('completed', $statuses, true)) {
                $status = self::STATUS_COMPLETED;
            } elseif ($overdueDays !== null && $overdueDays > 0) {
                $ageDays = (time() - strtotime((string) $r->latest_created_at)) / 86400;
                if ($ageDays > $overdueDays) {
                    $status = self::STATUS_OVERDUE;
                }
            }
            $heatmap[$proc]['totals'][$status]++;
        }

        foreach ($heatmap as $proc => &$row) {
            $touched = count($seenObjectsByProc[$proc] ?? []);
            $row['totals'][self::STATUS_NOT_STARTED] = max(0, $totalObjects - $touched);
            $row['percent_completed'] = $totalObjects > 0
                ? round(($row['totals'][self::STATUS_COMPLETED] / $totalObjects) * 100, 1)
                : 0;
        }
        unset($row);

        return $heatmap;
    }

    public function objectSummary(int $objectId, string $objectType = 'information_object', ?int $overdueDays = 30): array
    {
        require_once __DIR__.'/SpectrumProcedureCatalog.php';
        $codes = SpectrumProcedureCatalog::all();
        $out = [];
        foreach ($codes as $code => $label) {
            $state = $this->computeStatus($objectId, $code, $objectType, $overdueDays);
            $out[$code] = [
                'label'        => $label,
                'status'       => $state['status'],
                'completed_at' => $state['completed_at'],
                'last_task_id' => $state['last_task_id'],
            ];
        }
        return $out;
    }

    // -------- Chain rules --------

    public function applyChainOnTaskApproved(int $taskId): array
    {
        $task = DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 'w.id', '=', 't.workflow_id')
            ->where('t.id', $taskId)
            ->select('t.*', 'w.spectrum_procedure as from_procedure')
            ->first();
        if (!$task || empty($task->from_procedure)) {
            return ['spawned' => 0, 'rules_matched' => 0];
        }

        $rules = DB::table('ahg_spectrum_chain_rule')
            ->where('from_procedure', $task->from_procedure)
            ->where('is_active', 1)
            ->where('trigger_event', 'on_complete')
            ->get();

        $spawned = 0;
        foreach ($rules as $rule) {
            $targetWf = DB::table('ahg_workflow')
                ->where('spectrum_procedure', $rule->to_procedure)
                ->where('is_active', 1)
                ->orderBy('id')
                ->first();
            if (!$targetWf) continue;
            $firstStep = DB::table('ahg_workflow_step')
                ->where('workflow_id', $targetWf->id)
                ->orderBy('step_order')
                ->orderBy('id')
                ->first();
            if (!$firstStep) continue;
            $existing = DB::table('ahg_workflow_task')
                ->where('workflow_id', $targetWf->id)
                ->where('object_id', $task->object_id)
                ->where('object_type', $task->object_type)
                ->exists();
            if ($existing) continue;
            DB::table('ahg_workflow_task')->insert([
                'workflow_id' => $targetWf->id, 'workflow_step_id' => $firstStep->id,
                'object_id' => $task->object_id, 'object_type' => $task->object_type,
                'status' => 'pending', 'priority' => 'normal',
                'submitted_by' => $task->submitted_by ?? 1, 'decision' => 'pending',
                'previous_task_id' => $task->id, 'retry_count' => 0,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $spawned++;
        }
        return ['spawned' => $spawned, 'rules_matched' => count($rules)];
    }

    public function getChainRules()
    {
        return DB::table('ahg_spectrum_chain_rule')
            ->orderBy('from_procedure')->orderBy('to_procedure')->get();
    }

    public function saveChainRule(array $data): int
    {
        require_once __DIR__.'/SpectrumProcedureCatalog.php';
        $id = (int) ($data['id'] ?? 0);
        $payload = [
            'from_procedure' => SpectrumProcedureCatalog::normalize($data['from_procedure'] ?? null),
            'to_procedure'   => SpectrumProcedureCatalog::normalize($data['to_procedure'] ?? null),
            'trigger_event'  => in_array($data['trigger_event'] ?? '', ['on_complete', 'on_approve', 'on_first_step'], true)
                ? $data['trigger_event'] : 'on_complete',
            'is_active'      => !empty($data['is_active']) ? 1 : 0,
            'notes'          => $data['notes'] ?? null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];
        if (empty($payload['from_procedure']) || empty($payload['to_procedure'])) {
            throw new InvalidArgumentException('from_procedure and to_procedure must be valid Spectrum codes.');
        }
        if ($payload['from_procedure'] === $payload['to_procedure']) {
            throw new InvalidArgumentException('from_procedure and to_procedure cannot be the same.');
        }
        if ($id > 0) {
            DB::table('ahg_spectrum_chain_rule')->where('id', $id)->update($payload);
            return $id;
        }
        $payload['created_at'] = date('Y-m-d H:i:s');
        return (int) DB::table('ahg_spectrum_chain_rule')->insertGetId($payload);
    }

    public function deleteChainRule(int $id): bool
    {
        return DB::table('ahg_spectrum_chain_rule')->where('id', $id)->delete() > 0;
    }

    public function findOverdue(int $overdueDays = 14, string $objectType = 'information_object'): array
    {
        return DB::table('ahg_workflow_task as t')
            ->join('ahg_workflow as w', 'w.id', '=', 't.workflow_id')
            ->whereNotNull('w.spectrum_procedure')
            ->where('t.object_type', $objectType)
            ->whereIn('t.status', ['pending', 'claimed', 'in_progress'])
            ->where('t.created_at', '<', date('Y-m-d H:i:s', time() - $overdueDays * 86400))
            ->select(
                't.id as task_id', 't.object_id', 't.object_type', 't.created_at', 't.assigned_to',
                'w.id as workflow_id', 'w.name as workflow_name', 'w.spectrum_procedure'
            )
            ->orderBy('t.created_at')
            ->get()
            ->toArray();
    }
}
