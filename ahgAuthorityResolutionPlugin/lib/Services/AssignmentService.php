<?php

/**
 * AssignmentService - service for AtoM Heratio
 *
 * Task 12 of the AHG Authority Resolution Engine: Assign / Workflow. Lets an
 * archivist assign a mention (from the review screen or the queue) to another
 * archivist. The assignment routes the mention through the existing Workflow
 * plugin (ahgWorkflowPlugin): an ahg_workflow_task is created (or re-used) for
 * the polymorphic object_type='ahg_mention', and the task is assigned to the
 * target archivist via WorkflowService::assignToUser().
 *
 * Pure Capsule (no Laravel app helpers - date('Y-m-d H:i:s'), not now()).
 * ahgWorkflowPlugin's WorkflowService is in the root namespace and has no
 * PSR-4 autoload, so it is brought in via an explicit require_once and
 * guarded with class_exists for graceful degradation - if the Workflow
 * plugin is absent the ahg_mention assignment columns are still written.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AHG Authority Resolution Engine plugin for
 * AtoM Heratio. Licensed under the GNU General Public License v3.0 or later,
 * matching the parent atom-ahg-plugins repository.
 */

namespace AtomFramework\Services\AuthorityResolution;

use Illuminate\Database\Capsule\Manager as DB;

class AssignmentService
{
    /**
     * Fixed id of the "Authority Resolution Review" workflow seeded by
     * database/seed_workflow.sql. Passed explicitly to startWorkflow() so the
     * ahg_mention object never has to satisfy getApplicableWorkflow()'s
     * information_object scope lookup.
     */
    const AUTH_RES_WORKFLOW_ID = 200;

    const OBJECT_TYPE = 'ahg_mention';

    /**
     * ahg_workflow_history action code used for assignment events. Matches the
     * code ahgWorkflowPlugin's WorkflowEventService writes on reassignment
     * (WorkflowEventService::ACTION_REASSIGNED) so the optional reason lands on
     * the same kind of history row the assign already logs.
     */
    const HISTORY_ACTION = 'reassigned';

    /**
     * Locate and load ahgWorkflowPlugin's WorkflowService. Returns a fresh
     * instance or null if the plugin is not installed on this AtoM.
     *
     * WorkflowService is in the root namespace (no namespace declaration) and
     * is not PSR-4 autoloaded, so it must be required explicitly. The path is
     * resolved relative to this file: the two plugins are siblings under
     * atom-ahg-plugins/.
     */
    protected function workflowService()
    {
        if (!\class_exists('WorkflowService', false)) {
            $path = \dirname(__FILE__)
                . '/../../../ahgWorkflowPlugin/lib/Services/WorkflowService.php';
            if (\is_file($path)) {
                require_once $path;
            }
        }

        if (!\class_exists('WorkflowService', false)) {
            return null;
        }

        return new \WorkflowService();
    }

    /**
     * Assign a single mention to an archivist.
     *
     * If the mention already carries a workflow_task_id, the existing task is
     * re-assigned. Otherwise a new task is started for the auth-res workflow
     * and assigned. Either way the ahg_mention assignment columns are updated.
     *
     * The optional $reason is the archivist's free-text "reason / message" for
     * the assignment. It is stored as the workflow task's assignment comment -
     * without touching ahgWorkflowPlugin. ahg_workflow_task carries no generic
     * notes/comment column (only decision_comment), so the reason is recorded
     * as an ahg_workflow_history row (action='reassigned', comment=$reason) -
     * the same row shape the Workflow plugin uses for reassignment events.
     *
     * @param int         $mentionId
     * @param int         $archivistUserId
     * @param int         $byUserId
     * @param string|null $reason  Optional free-text reason / message.
     * @return array{ok:bool,workflow_task_id:?int,error:?string}
     */
    public function assign(int $mentionId, int $archivistUserId, int $byUserId, ?string $reason = null): array
    {
        if ($mentionId <= 0 || $archivistUserId <= 0 || $byUserId <= 0) {
            return ['ok' => false, 'workflow_task_id' => null, 'error' => 'invalid arguments'];
        }

        $mention = DB::table('ahg_mention')->where('id', $mentionId)->first();
        if (!$mention) {
            return ['ok' => false, 'workflow_task_id' => null, 'error' => "mention #{$mentionId} not found"];
        }

        $archivist = DB::table('user')->where('id', $archivistUserId)->first();
        if (!$archivist) {
            return ['ok' => false, 'workflow_task_id' => null, 'error' => "user #{$archivistUserId} not found"];
        }

        $wf = $this->workflowService();
        $now = \date('Y-m-d H:i:s');

        $reason = $reason !== null ? \trim($reason) : null;
        if ($reason === '') {
            $reason = null;
        }

        try {
            $taskId = DB::transaction(function () use ($mention, $mentionId, $archivistUserId, $byUserId, $wf, $now, $reason) {
                $resolvedTaskId = $mention->workflow_task_id ? (int) $mention->workflow_task_id : null;

                if ($wf !== null) {
                    if ($resolvedTaskId !== null) {
                        // Re-assign the existing task. Verify the row still
                        // exists - it may have been purged independently.
                        $taskRow = DB::table('ahg_workflow_task')->where('id', $resolvedTaskId)->first();
                        if ($taskRow) {
                            $wf->assignToUser($resolvedTaskId, $archivistUserId, $byUserId);
                        } else {
                            $resolvedTaskId = null;
                        }
                    }

                    if ($resolvedTaskId === null) {
                        $newTaskId = $wf->startWorkflow(
                            $mentionId,
                            $byUserId,
                            self::OBJECT_TYPE,
                            self::AUTH_RES_WORKFLOW_ID
                        );
                        if ($newTaskId) {
                            $resolvedTaskId = (int) $newTaskId;
                            $wf->assignToUser($resolvedTaskId, $archivistUserId, $byUserId);
                        }
                    }
                }

                DB::table('ahg_mention')->where('id', $mentionId)->update([
                    'assigned_to_user_id' => $archivistUserId,
                    'assigned_by_user_id' => $byUserId,
                    'assigned_at' => $now,
                    'workflow_task_id' => $resolvedTaskId,
                    'updated_at' => $now,
                ]);

                // Optional reason / message: store as the workflow task's
                // assignment comment. ahg_workflow_task has no generic
                // notes/comment column, so write an ahg_workflow_history row
                // matching the columns the Workflow plugin uses.
                if ($reason !== null && $resolvedTaskId !== null) {
                    $this->writeReasonHistory($resolvedTaskId, $mentionId, $byUserId, $reason);
                }

                return $resolvedTaskId;
            });
        } catch (\Exception $e) {
            return ['ok' => false, 'workflow_task_id' => null, 'error' => $e->getMessage()];
        }

        return ['ok' => true, 'workflow_task_id' => $taskId, 'error' => null];
    }

    /**
     * Record the optional assignment reason as an ahg_workflow_history row.
     *
     * Done by AssignmentService itself via Capsule - ahgWorkflowPlugin is NOT
     * modified. The row mirrors WorkflowEventService::emit(): action is the
     * shared 'reassigned' code, comment carries the free-text reason, and
     * workflow_id / workflow_step_id are copied off the task so the NOT NULL
     * workflow_id constraint is satisfied. Best-effort: a missing
     * ahg_workflow_history table (Workflow plugin absent) is swallowed so the
     * assignment itself still succeeds.
     */
    protected function writeReasonHistory(int $taskId, int $mentionId, int $byUserId, string $reason): void
    {
        try {
            $task = DB::table('ahg_workflow_task')->where('id', $taskId)->first();
            if (!$task) {
                return;
            }

            DB::table('ahg_workflow_history')->insert([
                'task_id' => $taskId,
                'workflow_id' => (int) $task->workflow_id,
                'workflow_step_id' => $task->workflow_step_id !== null ? (int) $task->workflow_step_id : null,
                'object_id' => $mentionId,
                'object_type' => self::OBJECT_TYPE,
                'action' => self::HISTORY_ACTION,
                'performed_by' => $byUserId,
                'performed_at' => \date('Y-m-d H:i:s'),
                'comment' => $reason,
                'metadata' => \json_encode(['source' => 'authority_resolution_assign']),
            ]);
        } catch (\Exception $e) {
            \error_log('AssignmentService::writeReasonHistory failed (task=' . $taskId . '): ' . $e->getMessage());
        }
    }

    /**
     * Assign a batch of mentions to a single archivist. Each mention is
     * processed independently - one failure does not abort the rest. The
     * optional $reason is applied to every mention in the batch.
     *
     * @param  int[]       $mentionIds
     * @param  int         $archivistUserId
     * @param  int         $byUserId
     * @param  string|null $reason  Optional reason / message applied to all.
     * @return array{assigned:int,failed:int,results:array<int,array>}
     */
    public function assignBatch(array $mentionIds, int $archivistUserId, int $byUserId, ?string $reason = null): array
    {
        $assigned = 0;
        $failed = 0;
        $results = [];

        foreach (\array_unique(\array_map('intval', $mentionIds)) as $mentionId) {
            if ($mentionId <= 0) {
                $failed++;
                continue;
            }
            $res = $this->assign($mentionId, $archivistUserId, $byUserId, $reason);
            $results[$mentionId] = $res;
            if (!empty($res['ok'])) {
                $assigned++;
            } else {
                $failed++;
            }
        }

        return ['assigned' => $assigned, 'failed' => $failed, 'results' => $results];
    }

    /**
     * List eligible assignees from the user table.
     *
     * Where possible the list is narrowed to users that hold the AtoM
     * 'editor' credential (an archivist can edit descriptions). AtoM stores
     * credentials via acl_user_group -> acl_group; if that join yields no
     * rows (unusual ACL layout) the method falls back to all active users so
     * the picker is never empty.
     *
     * @return array<int,array{id:int,username:string,display:string}>
     */
    public function archivists(): array
    {
        $base = DB::table('user')
            ->leftJoin('actor_i18n', function ($j) {
                $j->on('actor_i18n.id', '=', 'user.id')
                  ->where('actor_i18n.culture', '=', 'en');
            })
            ->select(
                'user.id',
                'user.username',
                'user.email',
                'actor_i18n.authorized_form_of_name as name'
            )
            ->whereNotNull('user.username')
            ->where('user.username', '!=', '');

        // Try the editor-credential filter. AtoM ACL: a user belongs to
        // acl groups via acl_user_group; the group name lives in
        // acl_group_i18n. The conventional editor/administrator/contributor/
        // translator groups carry edit rights.
        $editors = null;
        try {
            $editors = (clone $base)
                ->join('acl_user_group as aug', 'aug.user_id', '=', 'user.id')
                ->join('acl_group_i18n as agi', function ($j) {
                    $j->on('agi.id', '=', 'aug.group_id')
                      ->where('agi.culture', '=', 'en');
                })
                ->whereIn('agi.name', ['editor', 'administrator', 'translator', 'contributor'])
                ->distinct()
                ->orderBy('user.username')
                ->get();
        } catch (\Exception $e) {
            $editors = null;
        }

        $rows = ($editors !== null && \count($editors) > 0)
            ? $editors
            : $base->orderBy('user.username')->get();

        $out = [];
        foreach ($rows as $r) {
            $display = $r->username;
            if (!empty($r->name) && $r->name !== $r->username) {
                $display = $r->name . ' (' . $r->username . ')';
            }
            $out[] = [
                'id' => (int) $r->id,
                'username' => (string) $r->username,
                'display' => (string) $display,
            ];
        }

        return $out;
    }
}
