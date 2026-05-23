<?php

/**
 * WorkflowDiagramService - PSIS Symfony port of Heratio heratio#143 Phases 1+2.
 *
 * Renders an ahg_workflow + its steps as an SVG flow chart. Pure server-side —
 * no JS, no CDN dependency. Phase 2 overlay re-uses the renderer with a
 * step_id => status map derived from task history.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */

use Illuminate\Database\Capsule\Manager as DB;

class WorkflowDiagramService
{
    private const NODE_W = 220;
    private const NODE_H = 70;
    private const V_GAP  = 36;
    private const PAD    = 24;
    private const ARROW  = 8;

    /**
     * @param int   $workflowId
     * @param array $taskStatusByStepId optional step_id => 'completed'|'current'|'pending'|'rejected'
     */
    public function render(int $workflowId, array $taskStatusByStepId = []): string
    {
        $workflow = DB::table('ahg_workflow')->where('id', $workflowId)->first();
        if (!$workflow) {
            return $this->emptyState(__('Workflow not found.'));
        }

        $steps = DB::table('ahg_workflow_step')
            ->where('workflow_id', $workflowId)
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['id', 'name', 'step_order', 'step_type', 'is_optional', 'is_active']);

        if ($steps->isEmpty()) {
            return $this->emptyState(__('This workflow has no steps yet. Add at least one step to see a diagram.'));
        }

        // If edges exist, use topological layout; otherwise fall back to step_order grouping.
        $edgeSvc = new WorkflowEdgeService();
        $stepsById = $steps->keyBy('id');
        if ($edgeSvc->hasEdges($workflowId)) {
            $rankRows = $edgeSvc->topologicalRows($workflowId);
            $edges = $edgeSvc->getEdges($workflowId)->toArray();
            $useExplicitEdges = true;
            $rows = [];
            foreach ($rankRows as $rank => $stepIds) {
                foreach ($stepIds as $sid) {
                    if (isset($stepsById[$sid])) {
                        $rows[$rank][] = $stepsById[$sid];
                    }
                }
            }
        } else {
            $useExplicitEdges = false;
            $edges = [];
            $rows = [];
            foreach ($steps as $step) {
                $rows[(int) $step->step_order][] = $step;
            }
            ksort($rows);
        }

        $maxParallel = max(array_map('count', $rows));
        $svgW = self::PAD * 2 + ($maxParallel * self::NODE_W) + (($maxParallel - 1) * self::V_GAP);
        $svgH = self::PAD * 2 + (count($rows) * self::NODE_H) + ((count($rows) - 1) * (self::V_GAP + self::ARROW * 2));

        $titleId = 'wf-diagram-title-'.$workflowId;
        $descId  = 'wf-diagram-desc-'.$workflowId;

        $out = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" role="img" aria-labelledby="%s %s" class="workflow-diagram" preserveAspectRatio="xMidYMin meet">',
            $svgW, $svgH, $titleId, $descId
        );
        $out .= sprintf('<title id="%s">%s</title>', $titleId, esc_entities($workflow->name));
        $out .= sprintf('<desc id="%s">%s</desc>', $descId, esc_entities(__('Visual diagram showing the steps of workflow "%1%" and the order they execute.', ['%1%' => $workflow->name])));

        $out .= '<defs><marker id="wfdiag-arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">'
              . '<path d="M 0 0 L 10 5 L 0 10 z" fill="currentColor"/></marker></defs>';

        // Position each step.
        $positions = [];
        $rowIdx = 0;
        foreach ($rows as $order => $rowSteps) {
            $count = count($rowSteps);
            $rowWidth = $count * self::NODE_W + ($count - 1) * self::V_GAP;
            $startX = ($svgW - $rowWidth) / 2;
            $y = self::PAD + $rowIdx * (self::NODE_H + self::V_GAP + self::ARROW * 2);

            foreach ($rowSteps as $i => $step) {
                $x = $startX + $i * (self::NODE_W + self::V_GAP);
                $positions[$step->id] = ['x' => $x, 'y' => $y, 'row' => $rowIdx, 'step' => $step];
            }
            $rowIdx++;
        }

        // Edges.
        if ($useExplicitEdges) {
            foreach ($edges as $edge) {
                $fromId = (int) $edge->from_step_id;
                $toId   = (int) $edge->to_step_id;
                if (!isset($positions[$fromId]) || !isset($positions[$toId])) {
                    continue;
                }
                $fromPos = $positions[$fromId];
                $toPos = $positions[$toId];
                $out .= sprintf(
                    '<line x1="%d" y1="%d" x2="%d" y2="%d" class="wfdiag-edge" marker-end="url(#wfdiag-arrow)"/>',
                    $fromPos['x'] + self::NODE_W / 2, $fromPos['y'] + self::NODE_H,
                    $toPos['x']   + self::NODE_W / 2, $toPos['y'] - 2
                );
            }
        } else {
            $rowOrders = array_keys($rows);
            for ($i = 0; $i < count($rowOrders) - 1; $i++) {
                foreach ($rows[$rowOrders[$i]] as $from) {
                    foreach ($rows[$rowOrders[$i + 1]] as $to) {
                        $fromPos = $positions[$from->id];
                        $toPos = $positions[$to->id];
                        $out .= sprintf(
                            '<line x1="%d" y1="%d" x2="%d" y2="%d" class="wfdiag-edge" marker-end="url(#wfdiag-arrow)"/>',
                            $fromPos['x'] + self::NODE_W / 2, $fromPos['y'] + self::NODE_H,
                            $toPos['x']   + self::NODE_W / 2, $toPos['y'] - 2
                        );
                    }
                }
            }
        }

        // Nodes.
        foreach ($positions as $pos) {
            $step = $pos['step'];
            $status = $taskStatusByStepId[(int) $step->id] ?? null;
            $cls = 'wfdiag-node';
            $cls .= $step->is_active ? '' : ' wfdiag-inactive';
            $cls .= $step->is_optional ? ' wfdiag-optional' : '';
            if ($status) {
                $cls .= ' wfdiag-status-'.$status;
            }

            $shape = $step->is_optional
                ? sprintf(
                    '<polygon points="%d,%d %d,%d %d,%d %d,%d" class="%s"/>',
                    $pos['x'] + self::NODE_W / 2, $pos['y'],
                    $pos['x'] + self::NODE_W,     $pos['y'] + self::NODE_H / 2,
                    $pos['x'] + self::NODE_W / 2, $pos['y'] + self::NODE_H,
                    $pos['x'],                    $pos['y'] + self::NODE_H / 2,
                    $cls
                )
                : sprintf(
                    '<rect x="%d" y="%d" width="%d" height="%d" rx="10" ry="10" class="%s"/>',
                    $pos['x'], $pos['y'], self::NODE_W, self::NODE_H, $cls
                );
            $out .= $shape;

            $out .= sprintf(
                '<circle cx="%d" cy="%d" r="11" class="wfdiag-badge"/><text x="%d" y="%d" class="wfdiag-badge-text" text-anchor="middle" dominant-baseline="central">%d</text>',
                $pos['x'] + 14, $pos['y'] + 14,
                $pos['x'] + 14, $pos['y'] + 14,
                (int) $step->step_order
            );

            $name = mb_strlen($step->name) > 28 ? mb_substr($step->name, 0, 26).'…' : $step->name;
            $out .= sprintf(
                '<text x="%d" y="%d" class="wfdiag-node-name" text-anchor="middle" dominant-baseline="central">%s</text>',
                $pos['x'] + self::NODE_W / 2,
                $pos['y'] + self::NODE_H / 2 - 6,
                esc_entities($name)
            );

            $type = ucwords(str_replace('_', ' ', (string) $step->step_type));
            $out .= sprintf(
                '<text x="%d" y="%d" class="wfdiag-node-type" text-anchor="middle" dominant-baseline="central">%s</text>',
                $pos['x'] + self::NODE_W / 2,
                $pos['y'] + self::NODE_H / 2 + 12,
                esc_entities($type)
            );
        }

        $out .= '</svg>';

        return $out;
    }

    /**
     * Phase 2 — task progress overlay.
     */
    public function renderForTask(int $taskId): array
    {
        $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
        if (!$task) {
            return [
                'svg'        => $this->emptyState(__('Task not found.')),
                'statusMap'  => [],
                'task'       => null,
                'workflowId' => null,
            ];
        }

        $statusMap = $this->buildTaskStatusMap(
            (int) $task->workflow_id,
            (int) $task->object_id,
            (string) $task->object_type,
            (int) $task->workflow_step_id
        );

        return [
            'svg'        => $this->render((int) $task->workflow_id, $statusMap),
            'statusMap'  => $statusMap,
            'task'       => $task,
            'workflowId' => (int) $task->workflow_id,
        ];
    }

    private function buildTaskStatusMap(int $workflowId, int $objectId, string $objectType, int $currentStepId): array
    {
        $tasks = DB::table('ahg_workflow_task')
            ->where('workflow_id', $workflowId)
            ->where('object_id', $objectId)
            ->where('object_type', $objectType)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['workflow_step_id', 'status', 'decision']);

        $statusMap = [];
        foreach ($tasks as $t) {
            $stepId = (int) $t->workflow_step_id;
            $decision = (string) $t->decision;
            $status = (string) $t->status;

            if ('rejected' === $decision) {
                $statusMap[$stepId] = 'rejected';
                continue;
            }
            if ('approved' === $decision && ($statusMap[$stepId] ?? null) !== 'rejected') {
                $statusMap[$stepId] = 'completed';
                continue;
            }
            if (in_array($status, ['pending', 'claimed', 'in_progress'], true) && !isset($statusMap[$stepId])) {
                $statusMap[$stepId] = 'current';
            }
        }

        if (!isset($statusMap[$currentStepId])) {
            $statusMap[$currentStepId] = 'current';
        }

        return $statusMap;
    }

    public function textFallback(int $workflowId): array
    {
        $steps = DB::table('ahg_workflow_step')
            ->where('workflow_id', $workflowId)
            ->orderBy('step_order')
            ->orderBy('id')
            ->get(['name', 'step_order', 'step_type', 'is_optional']);

        $out = [];
        foreach ($steps as $s) {
            $marker = $s->is_optional ? ' '.__('(optional)') : '';
            $out[] = sprintf('%d. %s — %s%s', $s->step_order, $s->name, ucwords(str_replace('_', ' ', (string) $s->step_type)), $marker);
        }
        return $out;
    }

    private function emptyState(string $message): string
    {
        return '<div class="alert alert-info workflow-diagram-empty">'.esc_entities($message).'</div>';
    }
}
