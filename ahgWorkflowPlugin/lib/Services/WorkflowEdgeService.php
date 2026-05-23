<?php

/**
 * WorkflowEdgeService - PSIS Symfony port of Heratio heratio#143 Phase 3.
 *
 * Edge CRUD + DAG validation + topological row layout. When edges exist for
 * a workflow, the diagram renderer uses them; otherwise it falls back to
 * ahg_workflow_step.step_order (linear).
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class WorkflowEdgeService
{
    public function getEdges(int $workflowId)
    {
        return DB::table('ahg_workflow_edge')
            ->where('workflow_id', $workflowId)
            ->orderBy('from_step_id')
            ->orderBy('to_step_id')
            ->get(['id', 'from_step_id', 'to_step_id', 'condition_expr']);
    }

    public function hasEdges(int $workflowId): bool
    {
        return DB::table('ahg_workflow_edge')->where('workflow_id', $workflowId)->exists();
    }

    /**
     * @return array{ok:bool, errors:array<int,string>, written:int}
     */
    public function replaceEdges(int $workflowId, array $edges): array
    {
        $errors = $this->validate($workflowId, $edges);
        if (!empty($errors)) {
            return ['ok' => false, 'errors' => $errors, 'written' => 0];
        }

        $written = 0;
        DB::transaction(function () use ($workflowId, $edges, &$written) {
            DB::table('ahg_workflow_edge')->where('workflow_id', $workflowId)->delete();
            $now = date('Y-m-d H:i:s');
            foreach ($edges as $e) {
                DB::table('ahg_workflow_edge')->insert([
                    'workflow_id'    => $workflowId,
                    'from_step_id'   => (int) $e['from_step_id'],
                    'to_step_id'     => (int) $e['to_step_id'],
                    'condition_expr' => $e['condition_expr'] ?? null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
                $written++;
            }
        });

        return ['ok' => true, 'errors' => [], 'written' => $written];
    }

    public function validate(int $workflowId, array $edges): array
    {
        $errors = [];
        $stepIds = DB::table('ahg_workflow_step')
            ->where('workflow_id', $workflowId)
            ->pluck('id')
            ->map(function ($i) { return (int) $i; })
            ->all();
        $stepSet = array_flip($stepIds);

        $seen = [];
        foreach ($edges as $i => $e) {
            $from = (int) ($e['from_step_id'] ?? 0);
            $to = (int) ($e['to_step_id'] ?? 0);

            if ($from <= 0 || $to <= 0) {
                $errors[] = "Edge #{$i}: from_step_id and to_step_id are required.";
                continue;
            }
            if ($from === $to) {
                $errors[] = "Edge #{$i}: self-loop is not allowed (step #{$from} → step #{$from}).";
                continue;
            }
            if (!isset($stepSet[$from])) {
                $errors[] = "Edge #{$i}: from_step_id {$from} does not belong to this workflow.";
                continue;
            }
            if (!isset($stepSet[$to])) {
                $errors[] = "Edge #{$i}: to_step_id {$to} does not belong to this workflow.";
                continue;
            }
            $key = $from.'->'.$to;
            if (isset($seen[$key])) {
                $errors[] = "Edge #{$i}: duplicate of an earlier edge (step #{$from} → step #{$to}).";
                continue;
            }
            $seen[$key] = true;
        }

        if (!empty($errors)) {
            return $errors;
        }

        $adj = [];
        foreach ($edges as $e) {
            $adj[(int) $e['from_step_id']][] = (int) $e['to_step_id'];
        }
        $visited = [];
        $onStack = [];
        foreach ($stepIds as $stepId) {
            if ($this->hasCycle($stepId, $adj, $visited, $onStack)) {
                $errors[] = "Graph contains a cycle. Workflows must be a DAG (no loops back to earlier steps).";
                break;
            }
        }

        return $errors;
    }

    private function hasCycle(int $node, array $adj, array &$visited, array &$onStack): bool
    {
        if (isset($onStack[$node])) {
            return true;
        }
        if (isset($visited[$node])) {
            return false;
        }

        $visited[$node] = true;
        $onStack[$node] = true;

        foreach ($adj[$node] ?? [] as $next) {
            if ($this->hasCycle($next, $adj, $visited, $onStack)) {
                return true;
            }
        }

        unset($onStack[$node]);
        return false;
    }

    public function topologicalRows(int $workflowId): array
    {
        $stepIds = DB::table('ahg_workflow_step')
            ->where('workflow_id', $workflowId)
            ->orderBy('step_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(function ($i) { return (int) $i; })
            ->all();

        $edges = $this->getEdges($workflowId);

        $adj = [];
        $indeg = array_fill_keys($stepIds, 0);
        foreach ($edges as $e) {
            $from = (int) $e->from_step_id;
            $to = (int) $e->to_step_id;
            $adj[$from][] = $to;
            $indeg[$to] = ($indeg[$to] ?? 0) + 1;
        }

        $rank = array_fill_keys($stepIds, 0);
        $queue = [];
        foreach ($stepIds as $id) {
            if (($indeg[$id] ?? 0) === 0) {
                $queue[] = $id;
            }
        }

        while (!empty($queue)) {
            $node = array_shift($queue);
            foreach ($adj[$node] ?? [] as $next) {
                $rank[$next] = max($rank[$next], $rank[$node] + 1);
                $indeg[$next]--;
                if ($indeg[$next] === 0) {
                    $queue[] = $next;
                }
            }
        }

        $rows = [];
        foreach ($rank as $stepId => $r) {
            $rows[$r][] = $stepId;
        }
        ksort($rows);

        return $rows;
    }
}
