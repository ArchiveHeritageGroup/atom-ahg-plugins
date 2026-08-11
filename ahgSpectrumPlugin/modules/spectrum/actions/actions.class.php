<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

class spectrumActions extends AhgController
{
    /**
     * Initialize AhgDb for Laravel Query Builder.
     */
    public function boot(): void
    {
        $ahgDbFile = $this->config('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        if (file_exists($ahgDbFile)) {
            require_once $ahgDbFile;
        }
    }

    /**
     * Get current user culture
     */
    protected function getCulture(): string
    {
        return $this->culture();
    }

    /**
     * Get resource by slug using Laravel
     */
    protected function getResourceBySlug($slug)
    {
        $culture = $this->getCulture();
        $slugRecord = DB::table('slug')
            ->where('slug', $slug)
            ->first();

        if (!$slugRecord) {
            return null;
        }

        $resource = DB::table('information_object')
            ->where('id', $slugRecord->object_id)
            ->first();

        if ($resource) {
            $resource->slug = $slug;

            // Get i18n data
            $i18n = DB::table('information_object_i18n')
                ->where('id', $resource->id)
                ->where('culture', $culture)
                ->first();
            $resource->title = $i18n ? $i18n->title : null;

            // Get repository info
            if ($resource->repository_id) {
                $repoI18n = DB::table('actor_i18n')
                    ->where('id', $resource->repository_id)
                    ->where('culture', $culture)
                    ->first();
                $resource->repositoryName = $repoI18n ? $repoI18n->authorized_form_of_name : null;
            }
        }

        return $resource;
    }
    
    /**
     * Get or create condition check for an object
     */
    protected function getOrCreateConditionCheck($objectId)
    {
        // Try to get existing condition check
        $conditionCheck = DB::table('spectrum_condition_check')
            ->where('object_id', $objectId)
            ->orderBy('check_date', 'desc')
            ->first();
        
        if (!$conditionCheck) {
            // checked_by is NOT NULL with no default, so omitting it makes the insert
            // throw - which the caller catches and logs, leaving no condition check and
            // silently disabling photo upload.
            $checkedBy = '';
            try {
                $userId = $this->getUser()->getAttribute('user_id');
                if ($userId) {
                    $user = DB::table('user')->where('id', $userId)->first();
                    $checkedBy = (string) ($user->username ?? $user->email ?? '');
                }
            } catch (\Exception $e) {
                // fall through with an empty attribution rather than blocking the check
            }

            // Create a new condition check
            $newId = DB::table('spectrum_condition_check')->insertGetId([
                'object_id' => $objectId,
                'condition_check_reference' => 'CC-' . date('Ymd') . '-' . $objectId,
                'check_date' => date('Y-m-d'),
                'checked_by' => $checkedBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            $conditionCheck = DB::table('spectrum_condition_check')
                ->where('id', $newId)
                ->first();
        }
        
        return $conditionCheck;
    }

    /**
     * Create assignment notification for a user
     */
    protected function createAssignmentNotification($assignedToUserId, $resource, $procedureType, $state, $assignedByUserId)
    {
        // Get assignee user details
        $assignee = DB::table('user')->where('id', $assignedToUserId)->first();
        if (!$assignee) {
            return;
        }

        // Get assigner user details
        $assigner = DB::table('user')->where('id', $assignedByUserId)->first();
        $assignerName = $assigner ? $assigner->username : 'System';

        // Get procedure label
        $procedures = ahgSpectrumWorkflowService::getProcedures();
        $procedureLabel = $procedures[$procedureType] ?? ucwords(str_replace('_', ' ', $procedureType));

        // Get state label from config
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();
        $stateLabel = $state;
        if ($config) {
            $configData = json_decode($config->config_json, true);
            $stateLabel = $configData['state_labels'][$state] ?? ucwords(str_replace('_', ' ', $state));
        }

        $objectTitle = $resource->title ?: $resource->slug;
        $objectLink = '/' . $resource->slug . '/spectrum';

        $subject = "Task Assigned: {$procedureLabel}";
        $message = "You have been assigned a task by {$assignerName}.\n\n" .
                   "Object: {$objectTitle}\n" .
                   "Procedure: {$procedureLabel}\n" .
                   "State: {$stateLabel}\n\n" .
                   "View task: {$objectLink}";

        // Create in-app notification
        DB::table('spectrum_notification')->insert([
            'user_id' => $assignedToUserId,
            'notification_type' => 'task_assignment',
            'subject' => $subject,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Send email notification
        ahgSpectrumNotificationService::sendEmailNotification(
            $assignedToUserId,
            $subject,
            $message
        );
    }

    /**
     * Send email notification for a workflow state transition
     */
    protected function sendTransitionEmailNotification($resource, $procedureType, $fromState, $toState, $transitionKey, $actingUserId, $assignedToInt, $note)
    {
        // Get acting user details
        $actingUser = DB::table('user')->where('id', $actingUserId)->first();
        $actingName = $actingUser ? $actingUser->username : 'System';

        // Get procedure and state labels
        $procedureLabel = $this->getProcedureLabel($procedureType);
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();
        $fromLabel = ucwords(str_replace('_', ' ', $fromState));
        $toLabel = ucwords(str_replace('_', ' ', $toState));
        if ($config) {
            $configData = json_decode($config->config_json, true);
            $fromLabel = $configData['state_labels'][$fromState] ?? $fromLabel;
            $toLabel = $configData['state_labels'][$toState] ?? $toLabel;
        }

        $objectTitle = $resource->title ?: $resource->slug;
        $transitionLabel = ucwords(str_replace('_', ' ', $transitionKey));

        $subject = "Spectrum: {$transitionLabel} — {$procedureLabel}";
        $message = "{$actingName} performed '{$transitionLabel}' on a task.\n\n"
            . "Object: {$objectTitle}\n"
            . "Procedure: {$procedureLabel}\n"
            . "State: {$fromLabel} → {$toLabel}\n";
        if ($note) {
            $message .= "Note: {$note}\n";
        }
        $message .= "\nView task: /{$resource->slug}/spectrum";

        // Determine who to notify (anyone involved except the acting user)
        $notifyUserIds = [];

        // Notify the assigned user (if different from acting user)
        if ($assignedToInt && $assignedToInt !== $actingUserId) {
            $notifyUserIds[] = $assignedToInt;
        }

        // Notify the previous assignee (if task was reassigned)
        $previousState = DB::table('spectrum_workflow_state')
            ->where('record_id', $resource->id)
            ->where('procedure_type', $procedureType)
            ->first();
        if ($previousState && $previousState->assigned_to
            && $previousState->assigned_to !== $actingUserId
            && !in_array($previousState->assigned_to, $notifyUserIds)) {
            $notifyUserIds[] = $previousState->assigned_to;
        }

        // If no specific assignees, notify admins for certain transitions
        if (empty($notifyUserIds) && in_array($transitionKey, ['submit_for_review', 'complete', 'report'])) {
            // Administrators are users in the AtoM administrator ACL group (id 100).
            // (The old code joined a non-existent `user_role_relation` table, which
            // fatalled the whole transition with a 500.)
            $admins = DB::table('user')
                ->join('acl_user_group', 'user.id', '=', 'acl_user_group.user_id')
                ->where('acl_user_group.group_id', 100)
                ->where('user.id', '!=', $actingUserId)
                ->pluck('user.id')
                ->toArray();
            $notifyUserIds = array_merge($notifyUserIds, $admins);
        }

        $notifyUserIds = array_unique($notifyUserIds);

        foreach ($notifyUserIds as $notifyUserId) {
            ahgSpectrumNotificationService::sendEmailNotification(
                $notifyUserId,
                $subject,
                $message
            );
        }
    }

    /**
     * Get procedure label helper
     */
    protected function getProcedureLabel($procedureType)
    {
        $procedures = ahgSpectrumWorkflowService::getProcedures();
        return $procedures[$procedureType] ?? ucwords(str_replace('_', ' ', $procedureType));
    }

    public function executeIndex($request)
    {
        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404();
        }

        // Load museum metadata
        $this->loadMuseumData();
        
        // Load GRAP data
        $this->grapData = null;
        try {
            $grapData = DB::table('grap_heritage_asset')
                ->where('object_id', $this->resource->id)
                ->first();
            if ($grapData) {
                $this->grapData = (array) $grapData;
            }
        } catch (Exception $e) {
            // Table may not exist
        }

        $title = $this->resource->title ?: 'Untitled';
        $this->response->setTitle("{$title} - {$this->response->getTitle()}");
    }

    public function executeWorkflow($request)
    {
        $slug = $request->getParameter('slug');
        $this->procedureType = $request->getParameter('procedure_type', ahgSpectrumWorkflowService::PROC_ACQUISITION);

        $this->resource = $this->getResourceBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        // Get all procedure definitions from service
        $this->procedures = ahgSpectrumWorkflowService::getProcedures();
        
        // Get procedure statuses for this object
        $this->procedureStatuses = ahgSpectrumWorkflowService::getObjectProcedureStatus($this->resource->id);
        
        // Get current procedure status
        $this->currentProcedure = $this->procedureStatuses[$this->procedureType] ?? null;
        
        // Get timeline for this object
        $this->timeline = ahgSpectrumWorkflowService::getObjectTimeline($this->resource->id);
        
        // Filter timeline by current procedure type
        $this->procedureTimeline = array_filter($this->timeline, function($event) {
            return $event['procedure'] === $this->procedureType;
        });
        
        // Get workflow progress
        $this->progress = ahgSpectrumWorkflowService::calculateWorkflowProgress($this->resource->id);
        
        // Status options for update form
        $this->statusOptions = [
            ahgSpectrumWorkflowService::STATUS_NOT_STARTED => 'Not Started',
            ahgSpectrumWorkflowService::STATUS_IN_PROGRESS => 'In Progress',
            ahgSpectrumWorkflowService::STATUS_PENDING_REVIEW => 'Pending Review',
            ahgSpectrumWorkflowService::STATUS_COMPLETED => 'Completed',
            ahgSpectrumWorkflowService::STATUS_ON_HOLD => 'On Hold',
        ];
        
        // Status colors
        $this->statusColors = ahgSpectrumWorkflowService::$statusColors;
        
        // Check if user can edit
        $informationObject = $this->resource;
        $this->canEdit = $this->getUser()->isAuthenticated() && $informationObject && ($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'));

        // Per-record step checklist state (step_key => row). Guarded so the page
        // still renders if the migration hasn't run yet.
        $this->stepStates = [];

        // Evidence for this procedure, grouped by step, so the template can show
        // it beside the checklist without a query per step. Guarded the same way
        // as stepStates above: the page must still render on an install where
        // the migration has not run.
        $this->evidenceByStep = [];

        try {
            if (class_exists('SpectrumEvidenceService')) {
                $this->evidenceByStep = SpectrumEvidenceService::groupedByStep(
                    (string) $this->procedureType,
                    (int) $this->resource->id
                );
            }
        } catch (Throwable $e) {
            error_log('Spectrum evidence lookup failed: '.$e->getMessage());
        }
        try {
            $rows = DB::table('spectrum_workflow_step_state')
                ->where('record_id', $this->resource->id)
                ->where('procedure_type', $this->procedureType)
                ->get();
            foreach ($rows as $r) {
                $this->stepStates[$r->step_key] = $r;
            }
        } catch (\Throwable $e) {
            // spectrum_workflow_step_state not migrated yet — steps render unticked.
        }
    }

    /**
     * Save the per-record procedure step checklist (tick steps off in any order).
     */
    public function executeWorkflowSteps($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $slug = $request->getParameter('slug');
        $resource = $this->getResourceBySlug($slug);
        if (!$resource) {
            $this->forward404();
        }

        if (!$this->getUser()->isAuthenticated() || !($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }

        $procedureType = $request->getParameter('procedure_type');
        $userId = $this->getUser()->getAttribute('user_id');

        // Resolve the full set of step keys from the config so unticked steps are
        // cleared (the form only posts the ticked ones).
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();
        $allStepKeys = [];
        $linear = false;
        if ($config) {
            $cfg = json_decode($config->config_json, true);
            $linear = !empty($cfg['steps_linear']);
            foreach (($cfg['steps'] ?? []) as $s) {
                if (!empty($s['key'])) {
                    $allStepKeys[] = $s['key'];
                }
            }
        }

        $checked = (array) $request->getParameter('steps_done', []);
        $now = date('Y-m-d H:i:s');

        // Linear mode: the completed set must be a contiguous prefix (step N can
        // only be done if every earlier step is done). Once a step is unticked,
        // all later steps are forced unticked regardless of what was posted.
        $prefixBroken = false;
        foreach ($allStepKeys as $stepKey) {
            if ($linear) {
                $checkedThis = in_array($stepKey, $checked, true);
                if ($prefixBroken || !$checkedThis) {
                    $prefixBroken = true;
                    $isDone = 0;
                } else {
                    $isDone = 1;
                }
            } else {
                $isDone = in_array($stepKey, $checked, true) ? 1 : 0;
            }
            $existing = DB::table('spectrum_workflow_step_state')
                ->where('record_id', $resource->id)
                ->where('procedure_type', $procedureType)
                ->where('step_key', $stepKey)
                ->first();

            // Preserve original completed_at/by if it was already done.
            $completedAt = $isDone ? (($existing && $existing->is_done) ? $existing->completed_at : $now) : null;
            $completedBy = $isDone ? (($existing && $existing->is_done) ? $existing->completed_by : $userId) : null;

            $data = [
                'is_done' => $isDone,
                'completed_by' => $completedBy,
                'completed_at' => $completedAt,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('spectrum_workflow_step_state')->where('id', $existing->id)->update($data);
            } else {
                DB::table('spectrum_workflow_step_state')->insert(array_merge($data, [
                    'record_id' => $resource->id,
                    'procedure_type' => $procedureType,
                    'step_key' => $stepKey,
                    'created_at' => $now,
                ]));
            }
        }

        $this->getUser()->setFlash('notice', 'Procedure steps updated.');
        $this->redirect(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $slug, 'procedure_type' => $procedureType]);
    }

    /**
     * Toggle a procedure between "checklist" (tick steps in any order) and
     * "linear" (ordered ticking + can't finalise until all steps done) mode.
     * Procedure-level policy, so admin/editor only.
     */
    public function executeWorkflowStepsMode($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $slug = $request->getParameter('slug');
        $resource = $this->getResourceBySlug($slug);
        if (!$resource) {
            $this->forward404();
        }

        if (!$this->getUser()->isAuthenticated() || !($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }

        $procedureType = $request->getParameter('procedure_type');
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();

        if ($config) {
            $cfg = json_decode($config->config_json, true);
            $cfg['steps_linear'] = empty($cfg['steps_linear']);
            DB::table('spectrum_workflow_config')
                ->where('id', $config->id)
                ->update(['config_json' => json_encode($cfg)]);
            $this->getUser()->setFlash('notice', 'Step mode set to ' . ($cfg['steps_linear'] ? 'linear (ordered + gated)' : 'checklist (any order)') . '.');
        }

        $this->redirect(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $slug, 'procedure_type' => $procedureType]);
    }

    public function executeWorkflowUpdate($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        
        $slug = $request->getParameter('slug');
        $resource = $this->getResourceBySlug($slug);
        
        if (!$resource) {
            $this->forward404();
        }
        
        // Check permissions
        if (!$this->getUser()->isAuthenticated() || !($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }
        
        $procedureType = $request->getParameter('procedure_type');
        $newStatus = $request->getParameter('status');
        $notes = $request->getParameter('notes');
        $userId = $this->getUser()->getAttribute('user_id');
        
        // Update the procedure status
        ahgSpectrumWorkflowService::updateProcedureStatus(
            $resource->id,
            $procedureType,
            $newStatus,
            $notes,
            $userId
        );
        
        // Redirect back to workflow page
        $this->redirect(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $slug, 'procedure_type' => $procedureType]);
    }

    
    public function executeWorkflowTransition($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }
        
        $slug = $request->getParameter('slug');
        $resource = $this->getResourceBySlug($slug);
        
        if (!$resource) {
            $this->forward404();
        }
        
        // Check permissions
        $informationObject = $resource;
        if (!$this->getUser()->isAuthenticated() || !$informationObject || !($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }
        
        $procedureType = $request->getParameter('procedure_type');
        $transitionKey = $request->getParameter('transition_key');
        $fromState = $request->getParameter('from_state');
        $note = $request->getParameter('note');
        $assignedTo = $request->getParameter('assigned_to');
        $userId = $this->getUser()->getAttribute('user_id');
        
        // Get workflow config to validate transition
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();
        
        if (!$config) {
            $this->forward404();
        }
        
        $configData = json_decode($config->config_json, true);
        $transitions = $configData['transitions'] ?? [];
        
        if (!isset($transitions[$transitionKey])) {
            $this->forward404();
        }
        
        $transition = $transitions[$transitionKey];
        $toState = $transition['to'];
        
        // Validate from state
        if (!in_array($fromState, $transition['from'])) {
            $this->forward404();
        }

        // Linear-gated steps: a procedure in linear mode cannot reach its final
        // state until every procedure step is ticked complete.
        if (!empty($configData['steps_linear'])) {
            $states = $configData['states'] ?? [];
            $finalState = !empty($states) ? end($states) : null;
            if ($finalState !== null && $toState === $finalState) {
                $stepKeys = array_column($configData['steps'] ?? [], 'key');
                if (!empty($stepKeys)) {
                    $doneCount = DB::table('spectrum_workflow_step_state')
                        ->where('record_id', $resource->id)
                        ->where('procedure_type', $procedureType)
                        ->where('is_done', 1)
                        ->whereIn('step_key', $stepKeys)
                        ->count();
                    if ($doneCount < count($stepKeys)) {
                        $this->getUser()->setFlash('error', 'Linear mode: all procedure steps must be completed before this workflow can be finalised (' . $doneCount . '/' . count($stepKeys) . ' done).');
                        $this->redirect(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $slug, 'procedure_type' => $procedureType]);
                    }
                }
            }
        }

        // Prepare assignment data
        $assignedToInt = $assignedTo ? (int) $assignedTo : null;

        // On rejection, auto-assign back to the originator (who submitted for review)
        if ($transitionKey === 'reject' && !$assignedToInt) {
            $originator = DB::table('spectrum_workflow_history')
                ->where('procedure_type', $procedureType)
                ->where('record_id', $resource->id)
                ->where('transition_key', 'submit_for_review')
                ->orderBy('created_at', 'desc')
                ->value('user_id');

            if ($originator) {
                $assignedToInt = (int) $originator;
            }
        }

        $assignmentData = [];
        if ($assignedToInt) {
            $assignmentData = [
                'assigned_to' => $assignedToInt,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $userId
            ];
        }

        // Update or create workflow state
        $existingState = DB::table('spectrum_workflow_state')
            ->where('record_id', $resource->id)
            ->where('procedure_type', $procedureType)
            ->first();

        if ($existingState) {
            $updateData = [
                'current_state' => $toState,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            // Update assignment if provided
            if ($assignedToInt) {
                $updateData = array_merge($updateData, $assignmentData);
            }
            DB::table('spectrum_workflow_state')
                ->where('id', $existingState->id)
                ->update($updateData);
        } else {
            $insertData = [
                'procedure_type' => $procedureType,
                'record_id' => $resource->id,
                'current_state' => $toState,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($assignedToInt) {
                $insertData = array_merge($insertData, $assignmentData);
            }
            DB::table('spectrum_workflow_state')->insert($insertData);
        }

        // Record history with assignment
        DB::table('spectrum_workflow_history')->insert([
            'procedure_type' => $procedureType,
            'record_id' => $resource->id,
            'from_state' => $fromState,
            'to_state' => $toState,
            'transition_key' => $transitionKey,
            'user_id' => $userId,
            'assigned_to' => $assignedToInt,
            'note' => $note,
            'metadata' => $assignedToInt ? json_encode(['assigned_to' => $assignedToInt]) : null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Check if target state is a final state
        $isFinalState = ahgSpectrumWorkflowService::isFinalState($procedureType, $toState);

        // Create notification for assignee if task was assigned (but not when transitioning to final state)
        if ($assignedToInt && $assignedToInt !== $userId && !$isFinalState) {
            $this->createAssignmentNotification(
                $assignedToInt,
                $resource,
                $procedureType,
                $toState,
                $userId
            );
        }

        // Send email notification for state transitions to relevant users
        $this->sendTransitionEmailNotification(
            $resource,
            $procedureType,
            $fromState,
            $toState,
            $transitionKey,
            $userId,
            $assignedToInt,
            $note
        );

        // Mark existing notifications as read when task reaches final state
        if ($isFinalState) {
            ahgSpectrumNotificationService::markTaskNotificationsAsReadBySlug($slug, $procedureType);
        }

        // Outcomes: what this procedure produces on reaching this state.
        //
        // Fired after every write above has committed, and wrapped, because an
        // outcome is a CONSEQUENCE of the transition. A broken consequence must
        // not undo the fact that the transition happened - the curator moved the
        // record and that is true whether or not the accounting side is
        // reachable.
        //
        // Nothing is posted here. A proposal is raised for whoever is
        // accountable for the destination to accept. See SpectrumOutcomeService.
        $proposalsRaised = 0;

        try {
            if (class_exists('SpectrumOutcomeService')) {
                // $resource, not $this->resource - this action resolves the
                // record into a local, and the property is never set here. Using
                // the property cast a null to 0, so every proposal was raised
                // against record 0 (the institution-level sentinel) and the
                // evidence check looked in the wrong place.
                $proposalsRaised = SpectrumOutcomeService::onStateEntered(
                    $procedureType,
                    (int) $resource->id,
                    (string) $toState,
                    $userId
                );
            }
        } catch (Throwable $e) {
            error_log('Spectrum outcome dispatch failed: '.$e->getMessage());
        }

        // Confirm the save in the alerts block. The transition was recorded in the
        // activity history but the page came back looking unchanged, so there was
        // nothing to tell the user it had worked.
        $this->getUser()->setFlash('success', $this->context->i18n->__(
            'Moved from %1% to %2%.',
            [
                '%1%' => ucwords(str_replace('_', ' ', (string) $fromState)),
                '%2%' => ucwords(str_replace('_', ' ', (string) $toState)),
            ]
        ));

        // Say so when an outcome was raised, otherwise it happens invisibly and
        // nobody goes to accept it.
        if ($proposalsRaised > 0) {
            $this->getUser()->setFlash('notice', $this->context->i18n->__(
                '%1% outcome awaiting review. Nothing has been posted yet.',
                ['%1%' => $proposalsRaised]
            ));
        }

        // Redirect back
        $this->redirect(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $slug, 'procedure_type' => $procedureType]);
    }

    public function executeLabel($request)
    {
        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404();
        }
        
        $this->labelType = $request->getParameter('type', 'full');
        $this->labelSize = $request->getParameter('size', 'medium');
    }


    /**
     * Render the label as a PNG server-side.
     *
     * The browser-side route (html2canvas) returned a 0x0 canvas from a correctly
     * sized element, which is a fault inside the library and not something the page
     * can work around. Everything on the label is already available here - the text
     * from the record, the barcode and QR as PNGs from BarcodeService - so composing
     * it with GD is deterministic, needs no third-party script, and sidesteps CSP
     * entirely.
     */
    public function executeLabelPng($request)
    {
        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        $size = (int) $request->getParameter('size', 300);
        if (!in_array($size, [200, 300, 400], true)) {
            $size = 300;
        }

        $data = (string) $request->getParameter('data', '');
        if ('' === $data) {
            $data = (string) ($this->resource->identifier ?? $this->resource->slug);
        }

        $title = (string) ($this->resource->title ?? $this->resource->slug);
        $qrUrl = $request->getUriPrefix().'/'.$this->resource->slug;

        $png = $this->composeLabelPng($title, $data, $qrUrl, $size);

        $response = $this->getResponse();
        $response->setContentType('image/png');
        $response->setHttpHeader(
            'Content-Disposition',
            'attachment; filename="label-'.preg_replace('/[^a-z0-9_-]/i', '-', $this->resource->slug).'.png"'
        );
        $response->setContent($png);
        $response->send();

        throw new sfStopException();
    }

    /**
     * Compose the label bitmap: title, barcode, its human-readable value, and QR.
     */
    protected function composeLabelPng(string $title, string $data, string $qrUrl, int $size): string
    {
        $pad = (int) round($size * 0.05);
        $width = $size;

        $barcode = $this->imageFromDataUri(\AtomExtensions\Services\BarcodeService::barcodeDataUri($data));
        $qr = $this->imageFromDataUri(\AtomExtensions\Services\BarcodeService::qrDataUri($qrUrl));

        $font = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $fontBold = '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';
        $hasFont = is_readable($font);
        $titleSize = max(8, (int) round($size / 25));
        $smallSize = max(7, (int) round($size / 33));

        // Wrap the title to the label width before allocating the canvas, so the
        // height matches the content instead of clipping it.
        $lines = $hasFont ? $this->wrapToWidth($title, $fontBold, $titleSize, $width - 2 * $pad) : [$title];
        $lineHeight = (int) round($titleSize * 1.45);

        $barcodeH = $barcode ? (int) round($size / 5) : 0;
        $barcodeW = 0;
        if ($barcode) {
            $barcodeW = min($width - 2 * $pad, (int) round(imagesx($barcode) * ($barcodeH / imagesy($barcode))));
        }
        $qrSide = $qr ? (int) round($size * 0.4) : 0;

        $height = $pad + count($lines) * $lineHeight + $pad
                + ($barcode ? $barcodeH + $smallSize + 10 : 0)
                + ($qr ? $qrSide + $pad : 0) + $pad;

        $im = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);
        $grey = imagecolorallocate($im, 90, 90, 90);
        imagefilledrectangle($im, 0, 0, $width, $height, $white);
        imagerectangle($im, 0, 0, $width - 1, $height - 1, $black);

        $y = $pad + $titleSize;
        foreach ($lines as $line) {
            if ($hasFont) {
                imagettftext($im, $titleSize, 0, $pad, $y, $black, $fontBold, $line);
            } else {
                imagestring($im, 4, $pad, $y - $titleSize, $line, $black);
            }
            $y += $lineHeight;
        }
        $y += $pad;

        if ($barcode) {
            $x = (int) round(($width - $barcodeW) / 2);
            imagecopyresampled($im, $barcode, $x, $y, 0, 0, $barcodeW, $barcodeH, imagesx($barcode), imagesy($barcode));
            $y += $barcodeH + $smallSize + 4;
            if ($hasFont) {
                $box = imagettfbbox($smallSize, 0, $font, $data);
                $tw = abs($box[2] - $box[0]);
                imagettftext($im, $smallSize, 0, (int) round(($width - $tw) / 2), $y, $grey, $font, $data);
            } else {
                imagestring($im, 2, $pad, $y - $smallSize, $data, $grey);
            }
            $y += 6;
        }

        if ($qr) {
            $x = (int) round(($width - $qrSide) / 2);
            imagecopyresampled($im, $qr, $x, $y, 0, 0, $qrSide, $qrSide, imagesx($qr), imagesy($qr));
        }

        ob_start();
        imagepng($im);
        $out = (string) ob_get_clean();

        imagedestroy($im);
        if ($barcode) { imagedestroy($barcode); }
        if ($qr) { imagedestroy($qr); }

        return $out;
    }

    /** Decode a data:image/png;base64,... URI into a GD image, or null. */
    protected function imageFromDataUri(string $uri)
    {
        if ('' === $uri || false === strpos($uri, 'base64,')) {
            return null;
        }
        $raw = base64_decode(substr($uri, strpos($uri, 'base64,') + 7), true);
        if (false === $raw || '' === $raw) {
            return null;
        }
        $im = @imagecreatefromstring($raw);

        return $im ?: null;
    }

    /** Break text into lines that fit $maxWidth at the given font size. */
    protected function wrapToWidth(string $text, string $font, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $try = '' === $current ? $word : $current.' '.$word;
            $box = imagettfbbox($fontSize, 0, $font, $try);
            if (abs($box[2] - $box[0]) > $maxWidth && '' !== $current) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        if ('' !== $current) {
            $lines[] = $current;
        }

        return $lines ?: [$text];
    }

    /**
     * My Tasks - Show tasks assigned to current user
     */
    public function executeMyTasks($request)
    {
        // Require authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $culture = $this->culture();
        $procedureTypeFilter = $request->getParameter('procedure_type');

        // Get workflow configs for state labels. The open-task filtering itself
        // lives in ahgSpectrumWorkflowService so the page, the dashboard tile
        // and the admin-menu badge all count tasks the same way.
        $this->workflowConfigs = [];
        $configs = DB::table('spectrum_workflow_config')
            ->where('is_active', 1)
            ->get();
        foreach ($configs as $config) {
            $this->workflowConfigs[$config->procedure_type] = json_decode($config->config_json, true);
        }

        // Build query for assigned tasks (excluding final states per procedure)
        $query = DB::table('spectrum_workflow_state as sws')
            ->select([
                'sws.*',
                'io.id as object_id',
                'io.identifier',
                'io.repository_id',
                'ioi18n.title as object_title',
                'slug.slug',
                'assigner.username as assigned_by_name'
            ])
            ->leftJoin('information_object as io', 'sws.record_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi18n', function($join) use ($culture) {
                $join->on('io.id', '=', 'ioi18n.id')
                     ->where('ioi18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('user as assigner', 'sws.assigned_by', '=', 'assigner.id')
            ->where('sws.assigned_to', $userId);

        // Exclude final states per procedure (avoids cross-procedure collisions:
        // "documented" is final for disposal but intermediate for object_entry).
        // Uses the same map as the dashboard tile and admin-menu badge, so all
        // three surfaces agree on what counts as an open task.
        $finalStatesByProcedure = ahgSpectrumWorkflowService::getFinalStatesMap();
        if (!empty($finalStatesByProcedure)) {
            $query->where(function ($q) use ($finalStatesByProcedure) {
                foreach ($finalStatesByProcedure as $proc => $finals) {
                    $q->where(function ($inner) use ($proc, $finals) {
                        $inner->where('sws.procedure_type', '!=', $proc)
                              ->orWhereNotIn('sws.current_state', $finals);
                    });
                }
            });
        }

        // Apply procedure type filter
        if ($procedureTypeFilter) {
            $query->where('sws.procedure_type', $procedureTypeFilter);
        }

        // Order by most recently assigned
        $query->orderBy('sws.assigned_at', 'desc');

        $this->tasks = $query->get();

        // Get procedure labels for display
        $this->procedures = ahgSpectrumWorkflowService::getProcedures();

        // Get unread notification count
        $this->unreadCount = DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        // Get procedure types for filter dropdown
        $this->procedureTypes = DB::table('spectrum_workflow_state')
            ->where('assigned_to', $userId)
            ->distinct()
            ->pluck('procedure_type')
            ->toArray();

        $this->currentFilter = $procedureTypeFilter;
    }

    /**
     * General Procedures - institution-level procedures not tied to a specific object.
     * Uses record_id = 0 as sentinel value.
     */
    public function executeGeneral($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->procedures = ahgSpectrumWorkflowService::getProcedures();

        // Get current state for each general procedure (record_id = 0)
        $this->procedureStatuses = [];
        try {
            $states = DB::table('spectrum_workflow_state')
                ->where('record_id', 0)
                ->get();
            foreach ($states as $state) {
                $this->procedureStatuses[$state->procedure_type] = $state->current_state;
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        // Get recent general procedure history
        $this->recentHistory = [];
        try {
            $this->recentHistory = DB::table('spectrum_workflow_history as h')
                ->leftJoin('user as u', 'h.user_id', '=', 'u.id')
                ->where('h.record_id', 0)
                ->select('h.*', 'u.username as user_name')
                ->orderBy('h.created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    /**
     * General Workflow - workflow for institution-level procedures (record_id = 0)
     */
    public function executeGeneralWorkflow($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $this->procedureType = $request->getParameter('procedure_type', ahgSpectrumWorkflowService::PROC_ACQUISITION);
        $this->procedures = ahgSpectrumWorkflowService::getProcedures();
        $this->isGeneral = true;
        $this->recordId = 0;

        // Check permissions
        $this->canEdit = $this->getUser()->isAuthenticated()
            && ($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'));
    }

    /**
     * General Workflow Transition - state transitions for general procedures (record_id = 0)
     */
    public function executeGeneralWorkflowTransition($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        if (!$this->getUser()->isAuthenticated()
            || !($this->getUser()->isAdministrator() || $this->getUser()->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }

        $procedureType = $request->getParameter('procedure_type');
        $transitionKey = $request->getParameter('transition_key');
        $fromState = $request->getParameter('from_state');
        $note = $request->getParameter('note');
        $assignedTo = $request->getParameter('assigned_to');
        $userId = $this->getUser()->getAttribute('user_id');

        // Get workflow config to validate transition
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();

        if (!$config) {
            $this->forward404();
        }

        $configData = json_decode($config->config_json, true);
        $transitions = $configData['transitions'] ?? [];

        if (!isset($transitions[$transitionKey])) {
            $this->forward404();
        }

        $transition = $transitions[$transitionKey];
        $toState = $transition['to'];

        if (!in_array($fromState, $transition['from'])) {
            $this->forward404();
        }

        $assignedToInt = $assignedTo ? (int) $assignedTo : null;
        $assignmentData = [];
        if ($assignedToInt) {
            $assignmentData = [
                'assigned_to' => $assignedToInt,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $userId
            ];
        }

        // Update or create workflow state for record_id = 0
        $existingState = DB::table('spectrum_workflow_state')
            ->where('record_id', 0)
            ->where('procedure_type', $procedureType)
            ->first();

        if ($existingState) {
            $updateData = [
                'current_state' => $toState,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($assignedToInt) {
                $updateData = array_merge($updateData, $assignmentData);
            }
            DB::table('spectrum_workflow_state')
                ->where('id', $existingState->id)
                ->update($updateData);
        } else {
            $insertData = [
                'procedure_type' => $procedureType,
                'record_id' => 0,
                'current_state' => $toState,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if ($assignedToInt) {
                $insertData = array_merge($insertData, $assignmentData);
            }
            DB::table('spectrum_workflow_state')->insert($insertData);
        }

        // Record history
        DB::table('spectrum_workflow_history')->insert([
            'procedure_type' => $procedureType,
            'record_id' => 0,
            'from_state' => $fromState,
            'to_state' => $toState,
            'transition_key' => $transitionKey,
            'user_id' => $userId,
            'assigned_to' => $assignedToInt,
            'note' => $note,
            'metadata' => $assignedToInt ? json_encode(['assigned_to' => $assignedToInt, 'scope' => 'general']) : json_encode(['scope' => 'general']),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->redirect(['module' => 'spectrum', 'action' => 'generalWorkflow', 'procedure_type' => $procedureType]);
    }

    public function executeDashboard($request)
    {
        // Handle repository filter (must be before statistics queries)
        $this->selectedRepository = $request->getParameter('repository', '');
        $repoId = $this->selectedRepository ? (int)$this->selectedRepository : null;

        // Get procedures from service
        $this->procedures = ahgSpectrumWorkflowService::getProcedures();

        // Get workflow statistics
        $this->workflowStats = $this->getWorkflowStatistics($repoId);

        // Get recent activity from workflow history
        $this->recentActivity = $this->getRecentWorkflowActivity($repoId);

        // Get procedure status counts
        $this->procedureStatusCounts = $this->getProcedureStatusCounts($repoId);

        // Calculate overall completion
        $this->overallCompletion = $this->calculateOverallCompletion($repoId);

        // Get repositories for filter
        $this->repositories = $this->getRepositoriesForFilter();
    }

    /**
     * #186: shared staff gate for the JSON photo endpoints. Returns false and
     * writes a 403 body when the caller isn't authenticated staff (editor/admin).
     * These routes are currently unreachable (action-name/route mismatch), but the
     * gate ensures they can never become unauthenticated mutations.
     */
    private function spectrumStaffGate(): bool
    {
        if (!$this->getUser()->isAuthenticated()
            || !$this->getUser()->hasCredential(['editor', 'administrator'], false)) {
            $this->getResponse()->setStatusCode(403);
            echo json_encode(['success' => false, 'error' => 'Forbidden']);

            return false;
        }

        return true;
    }

    public function executeConditionPhotos($request)
    {
        // #185: creates a spectrum_condition_check on GET and uploads a
        // spectrum_condition_photo on POST — it had NO auth. Require staff.
        if (!$this->getUser()->isAuthenticated()
            || !$this->getUser()->hasCredential(['editor', 'administrator'], false)) {
            $this->forward('admin', 'secure');
        }

        $slug = $request->getParameter('slug');
        $this->objectSlug = $slug;
        $photoAction = $request->getParameter('photo_action');
        
        $this->resource = $this->getResourceBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404();
        }
        
        // Photo types for dropdown
        $this->photoTypes = [
            'overall' => 'Overall View',
            'detail' => 'Detail',
            'damage' => 'Damage/Deterioration',
            'before' => 'Before Treatment',
            'after' => 'After Treatment',
            'other' => 'Other',
        ];
        
        // Get or create condition check for this object FIRST
        $this->conditionCheck = null;
        $this->conditionCheckId = $request->getParameter('condition_id');
        
        try {
            if ($this->conditionCheckId) {
                $this->conditionCheck = DB::table('spectrum_condition_check')
                    ->where('id', $this->conditionCheckId)
                    ->first();
            }
            
            if (!$this->conditionCheck) {
                $this->conditionCheck = $this->getOrCreateConditionCheck($this->resource->id);
            }
            
            if ($this->conditionCheck) {
                $this->conditionCheckId = $this->conditionCheck->id;
            }
        } catch (\Exception $e) {
            // Surface it. Logging alone meant a failed condition check looked like a
            // working page that simply refused to upload anything.
            error_log('Condition check error: ' . $e->getMessage());
            $this->getUser()->setFlash(
                'error',
                $this->context->i18n->__('Could not open a condition check for this record: %1%', ['%1%' => $e->getMessage()])
            );
        }
        
        // Handle upload AFTER we have a valid condition check
        if ($photoAction === 'upload' && $request->isMethod('post') && $this->conditionCheckId) {
            $this->handlePhotoUpload($request);
        }
        
        // Convert to array for template
        if ($this->conditionCheck) {
            $this->conditionCheck = (array) $this->conditionCheck;
        } else {
            $this->conditionCheck = [
                'id' => null,
                'condition_check_reference' => 'New Check',
                'check_date' => date('Y-m-d'),
                'object_id' => $this->resource->id,
            ];
        }
        
        // Get photos for this condition check
        $this->photos = [];
        $this->photosByType = [];
        try {
            if ($this->conditionCheckId) {
                $photos = DB::table('spectrum_condition_photo')
                    ->where('condition_check_id', $this->conditionCheckId)
                    ->orderBy('sort_order', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->toArray();
                
                foreach ($photos as $photo) {
                    $photoArray = (array) $photo;
                    $this->photos[] = $photoArray;
                    $type = $photo->photo_type ?? 'other';
                    if (!isset($this->photosByType[$type])) {
                        $this->photosByType[$type] = [];
                    }
                    $this->photosByType[$type][] = $photoArray;
                }
            }
        } catch (\Exception $e) {
            // Table may not exist
        }
        
        // Get all condition checks for this object
        $this->conditionChecks = [];
        try {
            $this->conditionChecks = DB::table('spectrum_condition_check')
                ->where('object_id', $this->resource->id)
                ->orderBy('check_date', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Table may not exist
        }
    }
    
    /**
     * Turn PHP's uploaded-file structure into a plain list of per-file arrays.
     *
     * A multiple file input (name="photos[]") gives the transposed shape
     * ['name' => [...], 'tmp_name' => [...], ...] rather than a list of files, so
     * iterating it directly yields the column arrays and every file is skipped -
     * silently, since each column has no 'tmp_name' key of its own. A single input
     * gives the per-file shape already; both are handled here.
     */
    protected function normaliseUploadedFiles($files): array
    {
        if (!$files || !is_array($files)) {
            return [];
        }

        if (isset($files['tmp_name']) && !is_array($files['tmp_name'])) {
            return [$files];                      // single file input
        }

        if (isset($files['tmp_name']) && is_array($files['tmp_name'])) {
            $out = [];
            foreach (array_keys($files['tmp_name']) as $i) {
                if (UPLOAD_ERR_NO_FILE === ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE)) {
                    continue;                     // empty slot, not a failure
                }
                $out[] = [
                    'name' => $files['name'][$i] ?? '',
                    'type' => $files['type'][$i] ?? '',
                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                    'error' => $files['error'][$i] ?? UPLOAD_ERR_OK,
                    'size' => $files['size'][$i] ?? 0,
                ];
            }

            return $out;
        }

        return array_values(array_filter($files, 'is_array'));   // already a list
    }

    protected function handlePhotoUpload(sfWebRequest $request)
    {
        if (!$this->conditionCheckId) {
            error_log('No condition check ID for photo upload');
            return;
        }
        
        $files = $request->getFiles('photos');
        $photoType = $request->getParameter('photo_type', 'detail');
        $photographer = $request->getParameter('photographer', '');
        $photoDate = $request->getParameter('photo_date', date('Y-m-d'));
        $locationOnObject = $request->getParameter('location_on_object', '');
        
        $files = $this->normaliseUploadedFiles($files);

        if (!$files) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('No photo was selected to upload.'));

            return;
        }

        $uploaded = 0;
        $failed = [];

        $uploadDir = $this->config('sf_upload_dir') . '/condition_photos/' . $this->resource->id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files as $file) {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                if (!empty($file['name'])) {
                    $failed[] = $file['name'];
                }

                continue;
            }

            $originalFilename = $file['name'];
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $newFilename = uniqid('cond_') . '.' . $extension;
            $filePath = $uploadDir . '/' . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Get image dimensions
                $imageInfo = getimagesize($filePath);
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
                $mimeType = $imageInfo['mime'] ?? $file['type'];
                
                // Get user ID
                $userId = null;
                try {
                    $userId = $this->getUser()->getAttribute('user_id');
                } catch (\Exception $e) {
                    // User ID not available
                }
                
                // Insert into database
                DB::table('spectrum_condition_photo')->insert([
                    'condition_check_id' => $this->conditionCheckId,
                    'photo_type' => $photoType,
                    'filename' => $newFilename,
                    'original_filename' => $originalFilename,
                    'file_path' => '/uploads/condition_photos/' . $this->resource->id . '/' . $newFilename,
                    'file_size' => $file['size'],
                    'mime_type' => $mimeType,
                    'width' => $width,
                    'height' => $height,
                    'photographer' => $photographer,
                    'photo_date' => $photoDate ?: date('Y-m-d'),
                    'location_on_object' => $locationOnObject,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $userId,
                ]);

                ++$uploaded;
            } else {
                $failed[] = $originalFilename;
            }
        }

        // Report the outcome in the alerts block. Without this an upload that moved
        // no files was indistinguishable from one that worked: the page simply
        // reloaded with nothing new on it.
        $i18n = $this->context->i18n;
        if ($uploaded > 0) {
            $this->getUser()->setFlash('success', $i18n->__('%1% photo(s) uploaded.', ['%1%' => $uploaded]));
        }
        if ($failed) {
            $this->getUser()->setFlash('error', $i18n->__('Could not upload: %1%', ['%1%' => implode(', ', $failed)]));
        }
        if (0 === $uploaded && !$failed) {
            $this->getUser()->setFlash('error', $i18n->__('No photo was uploaded.'));
        }

        // Redirect back to avoid form resubmission
        $this->redirect(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $this->resource->slug]);
    }

    public function executeInstall($request)
    {
        $this->installed = $this->checkTablesExist();
    }

    public function executeConditionReport($request)
    {
        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);
        
        if (!$this->resource) {
            $this->forward404();
        }
        
        $this->conditionData = null;
        try {
            $this->conditionData = DB::table('spectrum_condition_check')
                ->where('object_id', $this->resource->id)
                ->orderBy('check_date', 'desc')
                ->first();
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    // ================================================================
    // Evidence attached to a procedure step
    // ================================================================

    /**
     * Attach one or more files to a step.
     */
    public function executeEvidenceUpload($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        $this->requireProcedureEditor();

        $procedureType = (string) $request->getParameter('procedure_type');
        $stepKey = (string) $request->getParameter('step_key');
        $i18n = $this->context->i18n;

        $files = $this->normaliseUploadedFiles($_FILES['evidence'] ?? null);

        if (!$files) {
            $this->getUser()->setFlash('error', $i18n->__('No file was chosen.'));
            $this->redirectToWorkflow($slug, $procedureType);

            return;
        }

        $stored = 0;
        $errors = [];

        foreach ($files as $file) {
            if (!isset($file['error']) || UPLOAD_ERR_NO_FILE === $file['error']) {
                continue;
            }

            try {
                SpectrumEvidenceService::store(
                    $procedureType,
                    (int) $this->resource->id,
                    $stepKey,
                    $file,
                    [
                        'evidence_type' => $request->getParameter('evidence_type'),
                        'caption' => $request->getParameter('caption'),
                        'note' => $request->getParameter('note'),
                    ],
                    $this->getUser()->getAttribute('user_id')
                );
                ++$stored;
            } catch (Throwable $e) {
                // Name the file: with several selected, "rejected" alone does not
                // say which one to fix.
                $errors[] = ($file['name'] ?? 'file').' - '.$e->getMessage();
            }
        }

        if ($stored > 0) {
            $this->getUser()->setFlash('success', $i18n->__('%1% file(s) attached.', ['%1%' => $stored]));
        }

        if ($errors) {
            $this->getUser()->setFlash('error', implode(' | ', $errors));
        }

        $this->redirectToWorkflow($slug, $procedureType);
    }

    /**
     * Stream one evidence file.
     *
     * Files are stored outside any served directory, so this is the only route
     * to one. The id is re-resolved and authorisation re-checked here rather
     * than trusted from the link that produced it.
     */
    public function executeEvidenceDownload($request)
    {
        $evidence = SpectrumEvidenceService::get((int) $request->getParameter('id'));

        if (!$evidence) {
            $this->forward404('No such evidence.');
        }

        // Re-authorise against the record the evidence belongs to, not the
        // evidence row - otherwise knowing an id is enough.
        $this->resource = $this->getResourceById((int) $evidence->record_id);
        $this->requireProcedureReader();

        $path = SpectrumEvidenceService::pathFor($evidence);

        // Belt and braces: the path is derived, not stored, but check it still
        // resolves inside the evidence root before streaming anything.
        //
        // The root is passed explicitly rather than using defaultRoots(), which
        // covers sf_upload_dir - the evidence store deliberately sits outside it,
        // so defaultRoots() would reject every legitimate file here.
        if (class_exists('\AtomExtensions\Services\PathGuard')) {
            $safe = \AtomExtensions\Services\PathGuard::within(
                $path,
                [SpectrumEvidenceService::storageRoot()]
            );

            if (null === $safe) {
                $this->forward404('Evidence is not readable.');
            }

            $path = $safe;
        }

        if (!is_file($path)) {
            $this->forward404('The file is missing from storage.');
        }

        // Serve a picture as a picture when the page asks for it, so evidence can
        // be seen on the step it belongs to instead of only downloaded.
        //
        // The allowlist is raster formats only, and SVG is left out deliberately:
        // an SVG rendered inline is a document that can carry script, and this
        // file was uploaded by a user. Anything not on the list keeps the
        // attachment disposition it has always had.
        $inlineTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        $wantsInline = $request->getParameter('inline')
            && in_array(strtolower((string) $evidence->mime_type), $inlineTypes, true);

        $response = $this->getResponse();
        $response->clearHttpHeaders();
        $response->setContentType($evidence->mime_type ?: 'application/octet-stream');
        // RFC 5987, so a name with spaces or non-ASCII survives the round trip.
        $response->setHttpHeader('Content-Disposition', sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $wantsInline ? 'inline' : 'attachment',
            str_replace('"', '', (string) $evidence->original_name),
            rawurlencode((string) $evidence->original_name)
        ));
        $response->setHttpHeader('Content-Length', (string) filesize($path));
        $response->setHttpHeader('X-Content-Type-Options', 'nosniff');
        $response->sendHttpHeaders();
        readfile($path);

        return sfView::NONE;
    }

    public function executeEvidenceDelete($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $evidence = SpectrumEvidenceService::get((int) $request->getParameter('id'));

        if (!$evidence) {
            $this->forward404('No such evidence.');
        }

        $this->resource = $this->getResourceById((int) $evidence->record_id);
        $this->requireProcedureEditor();

        $ok = SpectrumEvidenceService::delete((int) $evidence->id);

        $this->getUser()->setFlash($ok ? 'success' : 'error', $this->context->i18n->__(
            $ok ? 'Evidence removed.' : 'The evidence could not be removed.'
        ));

        $this->redirectToWorkflow(
            $this->resource ? $this->resource->slug : null,
            (string) $evidence->procedure_type
        );
    }

    // ================================================================
    // Outcome proposals
    // ================================================================

    /**
     * Everything a procedure has proposed and nobody has decided yet.
     */
    public function executeOutcomes($request)
    {
        $this->requireProcedureEditor();

        $this->proposals = SpectrumOutcomeService::pending(
            $request->getParameter('procedure_type') ?: null
        );

        $this->decided = \Illuminate\Database\Capsule\Manager::table('spectrum_outcome_proposal')
            ->whereIn('status', ['accepted', 'rejected', 'failed'])
            ->orderByDesc('decided_at')
            ->limit(25)
            ->get()
            ->all();

        $this->titles = $this->titlesFor(array_merge($this->proposals, $this->decided));
    }

    /**
     * Accept or reject. Accepting is the only thing that writes a destination.
     */
    public function executeOutcomeDecide($request)
    {
        if (!$request->isMethod('post')) {
            $this->forward404();
        }

        $this->requireProcedureEditor();

        $id = (int) $request->getParameter('id');
        $note = $request->getParameter('note');
        $i18n = $this->context->i18n;

        if ('accept' === $request->getParameter('decision')) {
            $ok = SpectrumOutcomeService::accept($id, $this->getUser()->getAttribute('user_id'), $note);
            $proposal = SpectrumOutcomeService::get($id);

            if ($ok) {
                $this->getUser()->setFlash('success', $i18n->__('Applied. %1%', [
                    '%1%' => $proposal ? (string) $proposal->result_note : '',
                ]));
            } else {
                $this->getUser()->setFlash('error', $i18n->__('Could not be applied. %1%', [
                    '%1%' => $proposal ? (string) $proposal->result_note : '',
                ]));
            }
        } else {
            SpectrumOutcomeService::reject($id, $this->getUser()->getAttribute('user_id'), $note);
            $this->getUser()->setFlash('notice', $i18n->__('Rejected. Nothing was written.'));
        }

        $this->redirect(['module' => 'spectrum', 'action' => 'outcomes']);
    }

    // ================================================================
    // Shared helpers for the above
    // ================================================================

    protected function requireProcedureEditor(): void
    {
        $user = $this->getUser();

        if (!$user->isAuthenticated()
            || !($user->hasCredential('administrator') || $user->hasCredential('editor'))) {
            $this->forward('admin', 'secure');
        }
    }

    protected function requireProcedureReader(): void
    {
        // Evidence can carry valuations, insurance figures and donor
        // correspondence, so reading it is a staff action, not a public one.
        $this->requireProcedureEditor();
    }

    protected function getResourceById(int $id)
    {
        return \Illuminate\Database\Capsule\Manager::table('information_object as io')
            ->leftJoin('slug as s', 's.object_id', '=', 'io.id')
            ->where('io.id', $id)
            ->select('io.id', 's.slug')
            ->first();
    }

    protected function redirectToWorkflow(?string $slug, string $procedureType): void
    {
        if (!$slug) {
            $this->redirect(['module' => 'spectrum', 'action' => 'dashboard']);

            return;
        }

        $this->redirect([
            'module' => 'spectrum', 'action' => 'workflow',
            'slug' => $slug, 'procedure_type' => $procedureType,
        ]);
    }

    /**
     * Record titles for a set of proposals, in one query rather than N.
     */
    protected function titlesFor(array $rows): array
    {
        $ids = array_values(array_unique(array_map(static fn ($r) => (int) $r->record_id, $rows)));

        if (!$ids) {
            return [];
        }

        return \Illuminate\Database\Capsule\Manager::table('information_object_i18n')
            ->whereIn('id', $ids)
            ->where('culture', 'en')
            ->pluck('title', 'id')
            ->all();
    }

    /**
     * Record a valuation against a record.
     *
     * spectrum_valuation has had eighteen columns and no writer since it was
     * created - every reference in the codebase was a SELECT, and the table was
     * empty on every instance. So the Valuation procedure could be driven to
     * "approved" with no valuation recorded anywhere, and its outcome had
     * nothing to propose.
     *
     * Follows executeObjectEntry, the existing precedent for a procedure with
     * its own data screen.
     */
    public function executeValuation($request)
    {
        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        $this->requireProcedureEditor();
        $i18n = $this->context->i18n;

        if ($request->isMethod('post')) {
            $amount = (float) str_replace([' ', ','], ['', '.'], (string) $request->getParameter('valuation_amount'));
            $date = trim((string) $request->getParameter('valuation_date'));

            if ($amount <= 0 || '' === $date) {
                $this->getUser()->setFlash('error', $i18n->__('A valuation needs an amount and a date.'));
            } else {
                $isCurrent = (bool) $request->getParameter('is_current');

                if ($isCurrent) {
                    // Only one current valuation per record, or "the current
                    // figure" stops meaning anything.
                    DB::table('spectrum_valuation')
                        ->where('object_id', $this->resource->id)
                        ->update(['is_current' => 0]);
                }

                DB::table('spectrum_valuation')->insert([
                    'object_id' => (int) $this->resource->id,
                    'valuation_reference' => $request->getParameter('valuation_reference') ?: null,
                    'valuation_date' => $date,
                    'valuation_type' => $request->getParameter('valuation_type') ?: null,
                    'valuation_amount' => $amount,
                    'valuation_currency' => $request->getParameter('valuation_currency') ?: 'ZAR',
                    'valuer_name' => $request->getParameter('valuer_name') ?: null,
                    'valuer_organization' => $request->getParameter('valuer_organization') ?: null,
                    'valuation_note' => $request->getParameter('valuation_note') ?: null,
                    'renewal_date' => $request->getParameter('renewal_date') ?: null,
                    'is_current' => $isCurrent ? 1 : 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $this->getUser()->getAttribute('user_id'),
                ]);

                $this->getUser()->setFlash('success', $i18n->__('Valuation recorded.'));
                $this->redirect(['module' => 'spectrum', 'action' => 'valuation', 'slug' => $slug]);

                return;
            }
        }

        $this->valuations = DB::table('spectrum_valuation')
            ->where('object_id', $this->resource->id)
            ->orderByDesc('valuation_date')
            ->orderByDesc('id')
            ->get()
            ->all();

        $this->types = [
            'insurance' => $i18n->__('Insurance'),
            'fair_value' => $i18n->__('Fair value'),
            'market' => $i18n->__('Market'),
            'replacement' => $i18n->__('Replacement'),
            'deemed_cost' => $i18n->__('Deemed cost'),
            'nominal' => $i18n->__('Nominal'),
        ];
    }

    public function executeGrapDashboard($request)
    {
        // Heritage accounting is a separate, optional plugin, and this screen
        // reads its tables directly. Without it the route still resolves and the
        // query below fails on a missing table - a 500 for a feature that is
        // simply not installed.
        //
        // Spectrum ships and runs standalone (it does so on the 2.10 test VM
        // today, with no heritage_asset table present), so absence is a normal
        // configuration rather than a fault.
        if (class_exists('SpectrumOutcomeService')
            && !SpectrumOutcomeService::pluginEnabled('ahgHeritageAccountingPlugin')) {
            $this->forward404('Heritage accounting is not installed on this site.');
        }

        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        // Load heritage asset data
        $this->grapData = null;
        $this->totalAssets = 0;
        $this->valuedAssets = 0;
        $this->pendingValuation = 0;
        $this->totalValue = 0;
        $this->categories = [];
        $this->assetRegisterComplete = false;
        $this->valuationsCurrent = false;
        $this->conditionComplete = false;
        $this->depreciationRecorded = false;
        $this->insuranceComplete = false;

        try {
            // heritage_asset, not grap_heritage_asset: the latter is a mysqldump
            // placeholder view (`select 1 AS id, 1 AS object_id, ...`) whose real
            // definition was never applied, so it returns a single row of literal 1s
            // and this page displayed fabricated figures. The real table is owned and
            // installed by ahgHeritageAccountingPlugin, and links on
            // information_object_id - object_id is NULL throughout.
            $this->grapData = DB::table('heritage_asset')
                ->where('information_object_id', $this->resource->id)
                ->orderBy('id', 'desc')
                ->first();

            if ($this->grapData) {
                $this->totalAssets = 1;
                $value = $this->grapData->current_carrying_amount ?? null;
                $valuationDate = $this->grapData->last_valuation_date ?? null;

                $this->valuedAssets = $value ? 1 : 0;
                $this->pendingValuation = $value ? 0 : 1;
                $this->totalValue = $value ?? 0;
                $this->assetRegisterComplete = true;
                $this->valuationsCurrent = $valuationDate
                    && strtotime($valuationDate) > strtotime('-5 years');
                $this->insuranceComplete = !empty($this->grapData->insurance_value);
            }
        } catch (\Exception $e) {
            // Table may not exist - use defaults
        }

        // Handle export
        $export = $request->getParameter('export');
        if ($export) {
            return $this->exportHeritageAssets($export);
        }
    }

    /**
     * Export heritage assets data
     */
    protected function exportHeritageAssets(string $format)
    {
        $data = [
            'title' => $this->resource->title ?? $this->resource->slug,
            'slug' => $this->resource->slug,
            'total_assets' => $this->totalAssets,
            'valued_assets' => $this->valuedAssets,
            'pending_valuation' => $this->pendingValuation,
            'total_value' => $this->totalValue,
            'asset_register_complete' => $this->assetRegisterComplete ? 'Yes' : 'No',
            'valuations_current' => $this->valuationsCurrent ? 'Yes' : 'No',
            'condition_complete' => $this->conditionComplete ? 'Yes' : 'No',
            'insurance_complete' => $this->insuranceComplete ? 'Yes' : 'No',
        ];

        if ($this->grapData) {
            $data['acquisition_date'] = $this->grapData->acquisition_date ?? '';
            $data['acquisition_method'] = $this->grapData->acquisition_method ?? '';
            $data['acquisition_cost'] = $this->grapData->acquisition_cost ?? '';
            $data['current_value'] = $this->grapData->current_value ?? '';
            $data['valuation_date'] = $this->grapData->valuation_date ?? '';
            $data['valuation_method'] = $this->grapData->valuation_method ?? '';
            $data['insurance_value'] = $this->grapData->insurance_value ?? '';
        }

        $filename = 'heritage_assets_' . $this->resource->slug . '_' . date('Ymd');

        switch ($format) {
            case 'csv':
                return $this->exportCsv($data, $filename);
            case 'xlsx':
                return $this->exportXlsx($data, $filename);
            case 'pdf':
                return $this->exportPdf($data, $filename);
            default:
                return sfView::NONE;
        }
    }

    protected function exportCsv(array $data, string $filename)
    {
        $response = $this->getResponse();
        $response->setContentType('text/csv');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        // Header row
        fputcsv($output, array_keys($data));
        // Data row
        fputcsv($output, array_values($data));

        fclose($output);

        return sfView::NONE;
    }

    protected function exportXlsx(array $data, string $filename)
    {
        // Simple Excel XML format (works without PhpSpreadsheet)
        $response = $this->getResponse();
        $response->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.xlsx"');

        // Use simple HTML table that Excel can read
        $response->setContentType('application/vnd.ms-excel');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . $filename . '.xls"');

        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';
        $html .= '<tr>';
        foreach (array_keys($data) as $header) {
            $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $header))) . '</th>';
        }
        $html .= '</tr><tr>';
        foreach (array_values($data) as $value) {
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $html .= '</tr></table></body></html>';

        echo $html;
        return sfView::NONE;
    }

    protected function exportPdf(array $data, string $filename)
    {
        $response = $this->getResponse();
        $response->setContentType('text/html');

        // Generate printable HTML (user can print to PDF)
        $html = '<!DOCTYPE html><html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<title>Heritage Assets Report - ' . htmlspecialchars($data['title']) . '</title>';
        $html .= '<style>';
        $html .= 'body { font-family: Arial, sans-serif; margin: 40px; }';
        $html .= 'h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
        $html .= 'th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }';
        $html .= 'th { background-color: #007bff; color: white; }';
        $html .= 'tr:nth-child(even) { background-color: #f9f9f9; }';
        $html .= '.footer { margin-top: 30px; font-size: 12px; color: #666; }';
        $html .= '@media print { body { margin: 20px; } }';
        $html .= '</style>';
        $html .= '</head><body>';
        $html .= '<h1>Heritage Assets Report</h1>';
        $html .= '<p><strong>Record:</strong> ' . htmlspecialchars($data['title']) . '</p>';
        $html .= '<p><strong>Generated:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $html .= '<table>';
        foreach ($data as $key => $value) {
            if ($key === 'title' || $key === 'slug') continue;
            $html .= '<tr>';
            $html .= '<th>' . htmlspecialchars(ucwords(str_replace('_', ' ', $key))) . '</th>';
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        $html .= '<div class="footer">';
        $html .= '<p>This report complies with international heritage asset accounting standards (IPSAS 17/31, GRAP 103).</p>';
        $html .= '<p>Use your browser\'s Print function (Ctrl+P) to save as PDF.</p>';
        $html .= '</div>';
        $html .= '<script>window.print();</script>';
        $html .= '</body></html>';

        echo $html;
        return sfView::NONE;
    }

    public function executeLoanDashboard($request)
    {
        $this->loans = [];
        try {
            // spectrum_loan_out has loan_status (NOT status); show current loans
            // out, most recent first. (The loanDashboardSuccess template was also
            // missing — both caused the /spectrum/loans 500.)
            $this->loans = DB::table('spectrum_loan_out')
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Table may not exist on older installs
        }
    }

    public function executeProvenanceAjax($request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $objectId = $request->getParameter('object_id');
        
        try {
            $events = DB::table('event')
                ->where('object_id', $objectId)
                ->orderBy('start_date', 'asc')
                ->get()
                ->toArray();
            
            return $this->renderText(json_encode(['success' => true, 'events' => $events]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    protected function getWorkflowSteps($objectId, $procedureType)
    {
        try {
            // Get workflow history for this object and procedure type
            $history = DB::table('spectrum_workflow_history')
                ->where('record_id', $objectId)
                ->where('procedure_type', $procedureType)
                ->orderBy('created_at', 'asc')
                ->get();

            // Transform to expected format for template
            $steps = [];
            foreach ($history as $record) {
                $steps[] = (object)[
                    'step_name' => ucwords(str_replace('_', ' ', $record->transition_key)),
                    'status' => $record->to_state,
                    'completed_date' => $record->created_at,
                    'notes' => $record->note ?? '',
                    'from_state' => $record->from_state,
                    'to_state' => $record->to_state
                ];
            }

            // Also get current state
            $currentState = DB::table('spectrum_workflow_state')
                ->where('record_id', $objectId)
                ->where('procedure_type', $procedureType)
                ->first();

            if ($currentState && empty($steps)) {
                // If no history but has state, show current state
                $steps[] = (object)[
                    'step_name' => 'Current State',
                    'status' => $currentState->current_state,
                    'completed_date' => $currentState->updated_at ?? $currentState->created_at,
                    'notes' => ''
                ];
            }

            return $steps;
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function loadMuseumData()
    {
        $this->museumData = [];

        if (!$this->resource || !$this->resource->id) {
            return;
        }

        try {
            $result = DB::table('museum_metadata')
                ->where('object_id', $this->resource->id)
                ->first();

            if ($result) {
                $this->museumData = (array) $result;
            }
        } catch (Exception $e) {
            // Table may not exist
        }
    }

    protected function getAllProcedures()
    {
        // Use service for consistent procedure keys
        return ahgSpectrumWorkflowService::getProcedures();
    }

    protected function getRecentEvents()
    {
        try {
            return DB::table('spectrum_event')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function checkTablesExist()
    {
        try {
            DB::table('spectrum_event')->first();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Save photo annotations
     */
    public function executeAnnotationSave($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->spectrumStaffGate()) {
            return sfView::NONE;
        }
        
        if (!$request->isMethod('post')) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'POST required']));
        }
        
        $photoId = $request->getParameter('photo_id');
        
        // Get JSON from request body
        $requestBody = file_get_contents('php://input');
        $data = json_decode($requestBody, true);
        $photoId = $data['photo_id'] ?? $photoId;
        $annotations = $data['annotations'] ?? [];
        
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }
        
        try {
            DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->update([
                    'annotations' => json_encode($annotations),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            
            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Get photo annotations
     */
    public function executeAnnotationGet($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->spectrumStaffGate()) {
            return sfView::NONE;
        }
        
        $photoId = $request->getParameter('photo_id');
        
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }
        
        try {
            $photo = DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->first();
            
            if (!$photo) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'Photo not found']));
            }
            
            $annotations = [];
            if ($photo->annotations) {
                $annotations = json_decode($photo->annotations, true) ?: [];
            }
            
            return $this->renderText(json_encode(['success' => true, 'annotations' => $annotations]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Photo delete action
     */
    public function executePhotoDelete($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->spectrumStaffGate()) {
            return sfView::NONE;
        }
        
        $photoId = $request->getParameter('photo_id');
        
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }
        
        try {
            $photo = DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->first();
            
            if ($photo && $photo->file_path) {
                $fullPath = $this->config('sf_web_dir') . $photo->file_path;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                DB::table('spectrum_condition_photo')
                    ->where('id', $photoId)
                    ->delete();
            } else {
                $conn = \Propel::getConnection();
                $stmt = $conn->prepare('DELETE FROM spectrum_condition_photo WHERE id = ?');
                $stmt->execute([$photoId]);
            }

            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Set primary photo
     */
    public function executePhotoSetPrimary($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->spectrumStaffGate()) {
            return sfView::NONE;
        }
        
        $photoId = $request->getParameter('photo_id');
        $conditionId = $request->getParameter('condition_id');
        
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }
        
        try {
            // Clear other primary flags
            if ($conditionId) {
                DB::table('spectrum_condition_photo')
                    ->where('condition_check_id', $conditionId)
                    ->update(['is_primary' => 0]);
            }
            
            // Set this one as primary
            DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->update(['is_primary' => 1]);
            
            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Rotate photo
     */
    public function executePhotoRotate($request)
    {
        $this->getResponse()->setContentType('application/json');
        if (!$this->spectrumStaffGate()) {
            return sfView::NONE;
        }
        
        $photoId = $request->getParameter('photo_id');
        $degrees = (int) $request->getParameter('degrees', 90);
        
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }
        
        try {
            $photo = DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->first();
            
            if (!$photo || !$photo->file_path) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'Photo not found']));
            }
            
            $fullPath = $this->config('sf_web_dir') . $photo->file_path;
            
            if (!file_exists($fullPath)) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'File not found']));
            }
            
            $image = @imagecreatefromstring(file_get_contents($fullPath));
            if (!$image) {
                // Previously this fell through and still reported success, so an
                // unreadable file looked like a rotation that had worked.
                return $this->renderText(json_encode([
                    'success' => false,
                    'error' => 'The image could not be read.',
                ]));
            }

            $isPng = false !== strpos((string) ($photo->mime_type ?: 'image/jpeg'), 'png');

            // Transparent fill for the corners exposed by the rotation, and keep the
            // alpha channel on save - a 0 background turns them black on a PNG.
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            $rotated = imagerotate($image, -$degrees, $transparent);
            imagealphablending($rotated, false);
            imagesavealpha($rotated, true);

            $written = $isPng ? imagepng($rotated, $fullPath) : imagejpeg($rotated, $fullPath, 90);

            $newWidth = imagesx($rotated);
            $newHeight = imagesy($rotated);

            imagedestroy($image);
            imagedestroy($rotated);

            if (!$written) {
                return $this->renderText(json_encode([
                    'success' => false,
                    'error' => 'The rotated image could not be written.',
                ]));
            }

            // Measured from the rotated image rather than swapping the stored values,
            // which is only correct for multiples of 90 degrees.
            DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->update(['width' => $newWidth, 'height' => $newHeight]);

            // The client replaces the tile's src with this. It used to read
            // data.thumbnail_url, which this action never returned, so the src became
            // the string "undefined" and the image broke. Cache-busted because the
            // file is overwritten in place and the browser would otherwise reuse it.
            return $this->renderText(json_encode([
                'success' => true,
                'url' => $photo->file_path.'?v='.time(),
                'width' => $newWidth,
                'height' => $newHeight,
            ]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ========== EXTENSION ACTIONS ==========

    /**
     * Security Compliance Dashboard
     */
    public function executeSecurityCompliance($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $this->stats = [
            'classified_objects' => DB::table('security_classification')->count(),
            'pending_reviews' => 0,
            'cleared_users' => DB::table('security_clearance_history')->where('action', 'granted')->count(),
            'access_logs_today' => DB::table('security_access_log')
                ->whereDate('created_at', date('Y-m-d'))->count(),
        ];
        $this->pendingReviews = [];
        $this->retentionSchedules = DB::table('security_retention_schedule')->get()->toArray();
        $this->recentLogs = DB::table('security_compliance_log')
            ->orderBy('created_at', 'desc')->limit(10)->get()->toArray();
    }

    /**
     * Privacy Compliance Dashboard
     */
    public function executePrivacyCompliance($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $this->complianceScore = 75;
        $this->ropaCount = DB::table('privacy_processing_activity')->count();
        
        $pending = DB::table('privacy_dsar_request')->where('status', 'pending')->count();
        $overdue = DB::table('privacy_dsar_request')
            ->where('status', '!=', 'completed')
            ->where('deadline_date', '<', date('Y-m-d'))->count();
        $this->dsarStats = [
            'total' => DB::table('privacy_dsar_request')->count(),
            'pending' => $pending,
            'overdue' => $overdue,
            'completed' => DB::table('privacy_dsar_request')->where('status', 'completed')->count(),
        ];
        
        $this->breachStats = [
            'total' => DB::table('privacy_breach_incident')->count(),
            'open' => DB::table('privacy_breach_incident')->where('status', 'open')->count(),
            'notified' => DB::table('privacy_breach_incident')->where('regulator_notified', 1)->count(),
            'closed' => DB::table('privacy_breach_incident')->where('status', 'closed')->count(),
        ];
        $this->recentActivity = [];
    }

    /**
     * Privacy ROPA
     */
    public function executePrivacyRopa($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        // Handle POST - create new activity
        if ($request->isMethod('post')) {
            DB::table('privacy_processing_activity')->insert([
                'name' => $request->getParameter('name'),
                'purpose' => $request->getParameter('purpose'),
                'lawful_basis' => $request->getParameter('lawful_basis'),
                'data_categories' => $request->getParameter('data_categories'),
                'data_subjects' => $request->getParameter('data_subjects'),
                'recipients' => $request->getParameter('recipients'),
                'retention_period' => $request->getParameter('retention_period'),
                'security_measures' => $request->getParameter('security_measures'),
                'dpia_required' => $request->getParameter('dpia_required') ? 1 : 0,
                'status' => $request->getParameter('status', 'active'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->redirect('/admin/privacy/ropa');
        }
        
        $this->activities = DB::table('privacy_processing_activity')->orderBy('created_at', 'desc')->get()->toArray();
    }

    /**
     * Privacy DSAR
     */
    public function executePrivacyDsar($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        // Handle POST - create new DSAR
        if ($request->isMethod('post')) {
            $receivedDate = $request->getParameter('received_date');
            $deadlineDate = date('Y-m-d', strtotime($receivedDate . ' +30 days'));
            $reference = 'DSAR-' . date('Ym') . '-' . str_pad(DB::table('privacy_dsar_request')->count() + 1, 4, '0', STR_PAD_LEFT);
            
            DB::table('privacy_dsar_request')->insert([
                'reference' => $reference,
                'request_type' => $request->getParameter('request_type'),
                'data_subject_name' => $request->getParameter('data_subject_name'),
                'data_subject_email' => $request->getParameter('data_subject_email'),
                'data_subject_id_type' => $request->getParameter('data_subject_id_type'),
                'received_date' => $receivedDate,
                'deadline_date' => $deadlineDate,
                'status' => 'pending',
                'notes' => $request->getParameter('notes'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->redirect('/admin/privacy/dsar');
        }
        
        $this->requests = DB::table('privacy_dsar_request')->orderBy('created_at', 'desc')->get()->toArray();
        $this->stats = [
            'total' => count($this->requests),
            'pending' => DB::table('privacy_dsar_request')->where('status', 'pending')->count(),
            'overdue' => DB::table('privacy_dsar_request')
                ->where('status', '!=', 'completed')
                ->where('deadline_date', '<', date('Y-m-d'))->count(),
            'completed' => DB::table('privacy_dsar_request')->where('status', 'completed')->count(),
        ];
    }

    /**
     * Privacy Breaches
     */
    public function executePrivacyBreaches($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        // Handle POST - report new breach
        if ($request->isMethod('post')) {
            $reference = 'BRE-' . date('Y') . '-' . str_pad(DB::table('privacy_breach_incident')->count() + 1, 4, '0', STR_PAD_LEFT);
            
            DB::table('privacy_breach_incident')->insert([
                'reference' => $reference,
                'incident_date' => $request->getParameter('incident_date'),
                'discovered_date' => $request->getParameter('discovered_date'),
                'breach_type' => $request->getParameter('breach_type'),
                'description' => $request->getParameter('description'),
                'data_affected' => $request->getParameter('data_affected'),
                'individuals_affected' => (int)$request->getParameter('individuals_affected'),
                'severity' => $request->getParameter('severity'),
                'root_cause' => $request->getParameter('root_cause'),
                'containment_actions' => $request->getParameter('containment_actions'),
                'status' => 'open',
                'created_by' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->redirect('/admin/privacy/breaches');
        }
        
        $this->breaches = DB::table('privacy_breach_incident')->orderBy('created_at', 'desc')->get()->toArray();
        $this->stats = [];
    }

    /**
     * Condition Admin Dashboard
     */
    public function executeConditionAdmin($request)
    {
        // $culture was referenced by the joins below but never defined here, so the
        // culture filter bound as NULL and no title ever joined.
        $culture = $this->culture();

        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $this->recentEvents = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object as io', 'c.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'io.id', '=', 's.object_id')
            ->select('c.*', 'i18n.title', 's.slug')
            ->orderBy('c.check_date', 'desc')
            ->limit(20)
            ->get()->toArray();
            
        $this->stats = [
            'total_checks' => DB::table('spectrum_condition_check')->count(),
            'critical' => DB::table('spectrum_condition_check')->where('overall_condition', 'critical')->count(),
            'poor' => DB::table('spectrum_condition_check')->where('overall_condition', 'poor')->count(),
        ];
        $this->pendingScheduled = [];
    }

    /**
     * Condition Risk
     */
    public function executeConditionRisk($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        $this->riskItems = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug as s', 'c.object_id', '=', 's.object_id')
            ->whereIn('c.overall_condition', ['critical', 'poor'])
            ->select('c.*', 'i18n.title', 's.slug')
            ->orderBy('c.check_date', 'desc')
            ->get()->toArray();
        $this->riskMatrix = [];
        $this->trends = [];
    }

    /**
     * Privacy DSAR Update (AJAX)
     */
    public function executePrivacyDsarUpdate($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        
        $id = (int)$request->getParameter('id');
        $status = $request->getParameter('status');
        
        if ($id && $status) {
            $update = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            if ($status === 'completed') {
                $update['completed_date'] = date('Y-m-d');
            }
            DB::table('privacy_dsar_request')->where('id', $id)->update($update);
        }
        
        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Privacy Breach Update (AJAX)
     */
    public function executePrivacyBreachUpdate($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderText(json_encode(['error' => 'Not authenticated']));
        }
        
        $id = (int)$request->getParameter('id');
        $update = [];
        
        if ($request->getParameter('status')) {
            $update['status'] = $request->getParameter('status');
        }
        if ($request->getParameter('regulator_notified')) {
            $update['regulator_notified'] = 1;
            $update['notification_date'] = date('Y-m-d H:i:s');
        }
        
        if ($id && !empty($update)) {
            DB::table('privacy_breach_incident')->where('id', $id)->update($update);
        }
        
        return $this->renderText(json_encode(['success' => true]));
    }

    /**
     * Privacy Admin Landing Page
     */
    public function executePrivacyAdmin($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $this->complianceScore = 75; // Calculate based on actual data
        $this->ropaCount = DB::table('privacy_processing_activity')->count();
        
        $this->dsarStats = [
            'pending' => DB::table('privacy_dsar_request')->where('status', 'pending')->count(),
            'overdue' => DB::table('privacy_dsar_request')
                ->where('status', '!=', 'completed')
                ->where('deadline_date', '<', date('Y-m-d'))->count(),
            'completed' => DB::table('privacy_dsar_request')->where('status', 'completed')->count(),
        ];
        
        $this->breachStats = [
            'open' => DB::table('privacy_breach_incident')->where('status', 'open')->count(),
            'closed' => DB::table('privacy_breach_incident')->where('status', 'closed')->count(),
        ];
    }

    /**
     * Privacy Templates Library - with file upload support
     */
    public function executePrivacyTemplates($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $uploadDir = $this->config('sf_upload_dir', $this->config('sf_upload_dir')) . '/privacy_templates/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Handle file upload
        if ($request->isMethod('post') && isset($_FILES['template_file'])) {
            $action = $request->getParameter('form_action');
            $file = $_FILES['template_file'];
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['docx', 'doc'])) {
                    $this->getUser()->setFlash('error', 'Only .docx files are allowed');
                    $this->redirect('/admin/privacy/templates');
                }
                
                $filename = uniqid('tpl_') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
                $filepath = $uploadDir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    if ($action === 'replace') {
                        // Delete old file
                        $old = DB::table('privacy_template')->where('id', $request->getParameter('id'))->first();
                        if ($old && $old->file_path && file_exists($old->file_path)) {
                            unlink($old->file_path);
                        }
                        DB::table('privacy_template')
                            ->where('id', $request->getParameter('id'))
                            ->update([
                                'file_path' => $filepath,
                                'file_name' => $file['name'],
                                'file_size' => $file['size'],
                                'mime_type' => $file['type'],
                            ]);
                    } else {
                        DB::table('privacy_template')->insert([
                            'category' => $request->getParameter('category'),
                            'name' => $request->getParameter('name'),
                            'content' => '',
                            'file_path' => $filepath,
                            'file_name' => $file['name'],
                            'file_size' => $file['size'],
                            'mime_type' => $file['type'],
                            'is_active' => 1,
                            'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
            $this->redirect('/admin/privacy/templates');
        }
        
        $this->templates = DB::table('privacy_template')
            ->where('is_active', 1)
            ->orderBy('category')
            ->orderBy('name')
            ->get()->toArray();
    }

    /**
     * Download privacy template
     */
    public function executePrivacyTemplateDownload($request)
    {
        $id = $request->getParameter('id');
        $template = DB::table('privacy_template')->where('id', $id)->first();
        
        if (!$template || !$template->file_path || !file_exists($template->file_path)) {
            $this->forward404('Template file not found');
        }
        
        $response = $this->getResponse();
        $response->setContentType($template->mime_type ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->setHttpHeader('Content-Disposition', 'attachment; filename="' . ($template->file_name ?: 'template.docx') . '"');
        $response->setHttpHeader('Content-Length', filesize($template->file_path));
        $response->setContent(file_get_contents($template->file_path));
        
        return sfView::NONE;
    }

    /**
     * Delete privacy template
     */
    public function executePrivacyTemplateDelete($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $id = $request->getParameter('id');
        $template = DB::table('privacy_template')->where('id', $id)->first();
        
        if ($template) {
            if ($template->file_path && file_exists($template->file_path)) {
                unlink($template->file_path);
            }
            if (class_exists('\\Illuminate\\Database\\Capsule\\Manager')) {
                DB::table('privacy_template')->where('id', $id)->delete();
            } else {
                $conn = \Propel::getConnection();
                $stmt = $conn->prepare('DELETE FROM privacy_template WHERE id = ?');
                $stmt->execute([$id]);
            }
        }
        
        $this->redirect('/admin/privacy/templates');
    }

    /**
     * Spectrum History Export (PDF/CSV)
     */
    public function executeExport($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }

        $format = $request->getParameter('format', 'csv');
        $type = $request->getParameter('type', 'condition');
        $slug = $request->getParameter('slug');

        if ($request->getParameter('download')) {
            return $this->handleSpectrumDownload($format, $type, $request);
        }

        $this->exportTypes = [
            'condition' => 'Condition Check History',
            'valuation' => 'Valuation History',
            'movement' => 'Movement/Location History',
            'loan' => 'Loan History',
            'workflow' => 'Workflow History',
        ];

        // Get object ID from slug if provided
        $objectId = null;
        $this->identifier = null;
        $this->slug = $slug;

        if ($slug) {
            $slugRecord = DB::table('slug')->where('slug', $slug)->first();
            if ($slugRecord) {
                $objectId = $slugRecord->object_id;
                $object = DB::table('information_object as io')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->where('io.id', $objectId)
                    ->select('io.identifier', 'i18n.title')
                    ->first();
                $this->identifier = $object ? ($object->title ?: $object->identifier) : $slug;
            }
        }

        // Query data for display counts
        $movementQuery = DB::table('spectrum_movement');
        $conditionQuery = DB::table('spectrum_condition_check');
        $valuationQuery = DB::table('spectrum_valuation');
        $loansInQuery = DB::table('spectrum_loan_in');
        $loansOutQuery = DB::table('spectrum_loan_out');

        if ($objectId) {
            $movementQuery->where('object_id', $objectId);
            $conditionQuery->where('object_id', $objectId);
            $valuationQuery->where('object_id', $objectId);
            $loansInQuery->where('object_id', $objectId);
            $loansOutQuery->where('object_id', $objectId);
        }

        $this->movements = $movementQuery->get()->toArray();
        $this->conditions = $conditionQuery->get()->toArray();
        $this->valuations = $valuationQuery->get()->toArray();
        $this->loansIn = $loansInQuery->get()->toArray();
        $this->loansOut = $loansOutQuery->get()->toArray();
        $this->format = $format;
    }

    protected function handleSpectrumDownload($format, $type, $request)
    {
        // Every join below filters information_object_i18n by culture, but $culture
        // was never defined in this scope - so the condition bound as NULL, matched
        // nothing, and object_title came back empty on all five export types.
        $culture = $this->culture();

        $data = [];
        $filename = "spectrum_{$type}_" . date('Y-m-d');
        
        switch ($type) {
            case 'condition':
                $data = DB::table('spectrum_condition_check as c')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->select('c.*', 'i18n.title as object_title')
                    ->orderBy('c.check_date', 'desc')
                    ->get()->toArray();
                break;
            case 'valuation':
                $data = DB::table('spectrum_valuation as v')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('v.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->select('v.*', 'i18n.title as object_title')
                    ->orderBy('v.valuation_date', 'desc')
                    ->get()->toArray();
                break;
            case 'movement':
                $data = DB::table('spectrum_movement as m')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('m.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->select('m.*', 'i18n.title as object_title')
                    ->orderBy('m.movement_date', 'desc')
                    ->get()->toArray();
                break;
            case 'loan':
                $loansIn = DB::table('spectrum_loan_in as l')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->select('l.*', 'i18n.title as object_title', DB::raw("'IN' as direction"))
                    ->get()->toArray();
                $loansOut = DB::table('spectrum_loan_out as l')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->select('l.*', 'i18n.title as object_title', DB::raw("'OUT' as direction"))
                    ->get()->toArray();
                $data = array_merge($loansIn, $loansOut);
                break;
            case 'workflow':
                // record_id, not object_id: that is the column this table actually
                // has, and the mismatch made every workflow export fail with a 500.
                // $culture has to be imported into the closure or it binds as null
                // and the join matches nothing.
                $data = DB::table('spectrum_workflow_history as w')
                    ->leftJoin('information_object_i18n as i18n', function($j) use ($culture) {
                        $j->on('w.record_id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
                    })
                    ->select('w.*', 'i18n.title as object_title')
                    ->orderBy('w.created_at', 'desc')
                    ->get()->toArray();
                break;
        }
        
        // Headers are sent directly rather than through sfWebResponse: the response
        // object's content type was being overridden downstream, so JSON arrived as
        // text/html and opened in the browser instead of downloading.
        $send = function (string $contentType, string $name) {
            if (!headers_sent()) {
                header('Content-Type: '.$contentType);
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('X-Content-Type-Options: nosniff');
            }
        };

        if ('csv' === $format) {
            $send('text/csv; charset=utf-8', $filename.'.csv');

            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, array_keys((array) $data[0]));
                foreach ($data as $row) {
                    fputcsv($output, (array) $row);
                }
            }
            fclose($output);

            return sfView::NONE;
        }

        if ('pdf' === $format) {
            // dompdf ships with the framework and is what ahgFavoritesPlugin and the
            // loan agreement generators already use, so this follows that pattern
            // rather than introducing a second PDF toolchain.
            require_once \sfConfig::get('sf_root_dir').'/atom-framework/vendor/autoload.php';

            $send('application/pdf', $filename.'.pdf');

            $dompdf = new \Dompdf\Dompdf([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
            ]);
            $dompdf->loadHtml($this->buildExportHtml($data, $type));
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            echo $dompdf->output();

            return sfView::NONE;
        }

        $send('application/json; charset=utf-8', $filename.'.json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sfView::NONE;
    }


    /**
     * Table markup for the PDF export: every column the query returned, in order.
     */
    protected function buildExportHtml(array $data, string $type): string
    {
        $title = ucwords(str_replace('_', ' ', $type)).' export';
        $html = '<html><head><meta charset="utf-8"><style>'
              . 'body{font-family:"DejaVu Sans",sans-serif;font-size:8pt;}'
              . 'h1{font-size:13pt;margin:0 0 4pt;}'
              . 'p.meta{color:#555;font-size:8pt;margin:0 0 8pt;}'
              . 'table{width:100%;border-collapse:collapse;}'
              . 'th,td{border:1px solid #999;padding:3pt;text-align:left;vertical-align:top;}'
              . 'th{background:#eee;}'
              . '</style></head><body>'
              . '<h1>'.htmlspecialchars($title, ENT_QUOTES).'</h1>'
              . '<p class="meta">'.htmlspecialchars(date('Y-m-d H:i'), ENT_QUOTES)
              . ' &mdash; '.count($data).' row(s)</p>';

        if (empty($data)) {
            return $html.'<p>No records.</p></body></html>';
        }

        $columns = array_keys((array) $data[0]);
        $html .= '<table><thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>'.htmlspecialchars(ucwords(str_replace('_', ' ', $column)), ENT_QUOTES).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($data as $row) {
            $row = (array) $row;
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = $row[$column] ?? '';
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $html .= '<td>'.htmlspecialchars((string) $value, ENT_QUOTES).'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table></body></html>';
    }


    /**
     * Save the edit-photo modal.
     *
     * There was no handler for this at all: the form posted photo_action=edit, and
     * executeConditionPhotos only ever acted on 'upload', so every edit silently did
     * nothing. Mirrors executePhotoDelete's shape - JSON in, JSON out.
     */
    public function executePhotoUpdate($request)
    {
        $this->getResponse()->setContentType('application/json');

        if (!$this->getUser()->isAuthenticated()
            || !$this->getUser()->hasCredential(['editor', 'administrator'], false)) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Not authorised']));
        }

        $photoId = (int) $request->getParameter('photo_id');
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }

        // Only the fields the modal offers; anything else stays as it was.
        $fields = ['photo_type', 'caption', 'description', 'location_on_object', 'photographer', 'photo_date'];
        $update = [];
        foreach ($fields as $field) {
            $value = $request->getParameter($field);
            if (null !== $value) {
                $update[$field] = '' === $value ? null : $value;
            }
        }

        if (!$update) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'Nothing to update']));
        }

        try {
            DB::table('spectrum_condition_photo')->where('id', $photoId)->update($update);
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }

        return $this->renderText(json_encode(['success' => true]));
    }


    /**
     * Record an Object Entry for a description.
     *
     * spectrum_object_entry existed and was read by the Object Entry report, but
     * nothing anywhere wrote it - so that report has always been empty, on every
     * install. The Spectrum procedure definition lists object_number, entry_date,
     * entry_reason and depositor as required fields; this is the capture form for
     * them. One row per record, updated in place on re-submission.
     */
    public function executeObjectEntry($request)
    {
        if (!$this->getUser()->isAuthenticated()
            || !$this->getUser()->hasCredential(['editor', 'administrator'], false)) {
            $this->forward('admin', 'secure');
        }

        $slug = $request->getParameter('slug');
        $this->resource = $this->getResourceBySlug($slug);

        if (!$this->resource) {
            $this->forward404();
        }

        $this->entry = DB::table('spectrum_object_entry')
            ->where('object_id', $this->resource->id)
            ->first();

        if (!$request->isMethod('post')) {
            return;
        }

        $fields = [
            'entry_number', 'entry_date', 'entry_method', 'entry_reason',
            'depositor_name', 'depositor_contact', 'depositor_address',
            'current_owner', 'owner_contact', 'return_date', 'entry_note',
            'received_by', 'packing_note',
        ];

        $values = [];
        foreach ($fields as $field) {
            $value = $request->getParameter($field);
            $values[$field] = ('' === $value || null === $value) ? null : $value;
        }

        // entry_number and entry_date are NOT NULL with no default, so fall back
        // rather than letting the insert throw where the user left them blank.
        if (empty($values['entry_number'])) {
            $values['entry_number'] = 'OE-'.date('Ymd').'-'.$this->resource->id;
        }
        if (empty($values['entry_date'])) {
            $values['entry_date'] = date('Y-m-d');
        }

        $userId = $this->getUser()->getAttribute('user_id');

        try {
            if ($this->entry) {
                $values['updated_by'] = $userId;
                DB::table('spectrum_object_entry')->where('id', $this->entry->id)->update($values);
                $this->getUser()->setFlash('success', $this->context->i18n->__('Object entry updated.'));
            } else {
                $values['object_id'] = $this->resource->id;
                $values['created_by'] = $userId;
                DB::table('spectrum_object_entry')->insert($values);
                $this->getUser()->setFlash('success', $this->context->i18n->__('Object entry recorded.'));
            }
        } catch (\Exception $e) {
            $this->getUser()->setFlash('error', $this->context->i18n->__('Could not save the object entry: %1%', ['%1%' => $e->getMessage()]));
        }

        $this->redirect(['module' => 'spectrum', 'action' => 'objectEntry', 'slug' => $this->resource->slug]);
    }

    /**
     * GRAP-Spectrum Procedure Linking
     */
    public function executeGrapSpectrumLink($request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $objectId = $request->getParameter('object_id');
        
        // Handle POST - create link
        if ($request->isMethod('post')) {
            DB::table('grap_spectrum_procedure_link')->insert([
                'grap_asset_id' => $request->getParameter('grap_asset_id'),
                'spectrum_procedure' => $request->getParameter('spectrum_procedure'),
                'spectrum_record_id' => $request->getParameter('spectrum_record_id'),
                'link_type' => $request->getParameter('link_type'),
                'link_date' => $request->getParameter('link_date') ?: date('Y-m-d'),
                'financial_impact' => $request->getParameter('financial_impact') ?: null,
                'notes' => $request->getParameter('notes'),
                'created_by' => $this->getUser()->getAttribute('user_id'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $this->redirect($request->getReferer());
        }
        
        // Get existing links for object
        if ($objectId) {
            $this->grapAsset = DB::table('grap_heritage_asset')->where('object_id', $objectId)->first();
            $this->links = DB::table('grap_spectrum_procedure_link as l')
                ->where('l.grap_asset_id', $this->grapAsset->id ?? 0)
                ->orderBy('l.link_date', 'desc')
                ->get()->toArray();
        }
        
        $this->linkTypes = [
            'initial_recognition' => 'Initial Recognition (Acquisition)',
            'subsequent_measurement' => 'Subsequent Measurement (Valuation)',
            'impairment' => 'Impairment',
            'disposal' => 'Disposal/Deaccession',
            'audit' => 'Audit/Condition Check',
        ];
        
        $this->procedures = [
            'acquisition' => 'Acquisition',
            'loan_in' => 'Loan In',
            'loan_out' => 'Loan Out',
            'movement' => 'Movement',
            'valuation' => 'Valuation',
            'condition' => 'Condition Check',
            'deaccession' => 'Deaccession',
        ];
    }

    protected function getWorkflowStatistics($repoId = null)
    {
        $stats = [
            "total_objects" => 0,
            "objects_with_workflows" => 0,
            "completed_procedures" => 0,
            "in_progress_procedures" => 0,
            "pending_procedures" => 0,
        ];
        
        try {
            $stats["total_objects"] = DB::table("information_object")->count();
            $stats["objects_with_workflows"] = DB::table("spectrum_workflow_state")
                ->distinct("record_id")->count("record_id");
            
            $statusCounts = DB::table("spectrum_workflow_state")
                ->select("current_state", DB::raw("COUNT(*) as count"))
                ->groupBy("current_state")->get();
            
            foreach ($statusCounts as $row) {
                if (in_array($row->current_state, ["completed", "verified", "closed", "confirmed"])) {
                    $stats["completed_procedures"] += $row->count;
                } elseif ($row->current_state === "pending") {
                    $stats["pending_procedures"] += $row->count;
                } else {
                    $stats["in_progress_procedures"] += $row->count;
                }
            }
        } catch (\Exception $e) {}
        
        return $stats;
    }
    
    protected function getRecentWorkflowActivity($repoId = null)
    {
        try {
            return DB::table("spectrum_workflow_history as h")
                ->join("slug as s", "h.record_id", "=", "s.object_id")
                ->leftJoin("information_object_i18n as ioi", function($join) {
                    $join->on("h.record_id", "=", "ioi.id")->where("ioi.culture", "=", "en");
                })
                ->leftJoin("user as u", "h.user_id", "=", "u.id")
                ->select("h.*", "s.slug", "ioi.title as object_title", "u.username as user_name")
                ->orderBy("h.created_at", "desc")
                ->limit(20)->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    protected function getProcedureStatusCounts($repoId = null)
    {
        $counts = [];
        try {
            $results = DB::table("spectrum_workflow_state")
                ->select("procedure_type", "current_state", DB::raw("COUNT(*) as count"))
                ->groupBy("procedure_type", "current_state")->get();
            
            foreach ($results as $row) {
                if (!isset($counts[$row->procedure_type])) {
                    $counts[$row->procedure_type] = [];
                }
                $counts[$row->procedure_type][$row->current_state] = $row->count;
            }
        } catch (\Exception $e) {}
        return $counts;
    }
    
    protected function calculateOverallCompletion($repoId = null)
    {
        try {
            $total = DB::table("spectrum_workflow_state")->count();
            if ($total === 0) return ["percentage" => 0, "completed" => 0, "total" => 0];
            
            $completed = DB::table("spectrum_workflow_state")
                ->whereIn("current_state", ["completed", "verified", "closed", "confirmed", "documented"])
                ->count();
            
            return [
                "percentage" => round(($completed / $total) * 100),
                "completed" => $completed,
                "total" => $total
            ];
        } catch (\Exception $e) {
            return ["percentage" => 0, "completed" => 0, "total" => 0];
        }
    }
    
    protected function getRepositoriesForFilter()
    {
        try {
            return DB::table("repository")
                ->join("actor_i18n", "repository.id", "=", "actor_i18n.id")
                ->where("actor_i18n.culture", "en")
                ->whereNotNull("actor_i18n.authorized_form_of_name")
                ->select("repository.id", "actor_i18n.authorized_form_of_name")
                ->orderBy("actor_i18n.authorized_form_of_name")
                ->get()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }


    public function executeDataQuality($request)
    {
        // Get data quality statistics
        $this->totalObjects = DB::table("information_object")->where("id", "!=", 1)->count();
        
        // Objects missing titles
        $this->missingTitles = DB::table("information_object as io")
            ->leftJoin("information_object_i18n as i18n", function($join) {
                $join->on("io.id", "=", "i18n.id")->where("i18n.culture", "=", "en");
            })
            ->where("io.id", "!=", 1)
            ->whereNull("i18n.title")
            ->count();
        
        // Objects missing dates
        $this->missingDates = DB::table("information_object")
            ->where("id", "!=", 1)
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                    ->from("event")
                    ->whereColumn("event.object_id", "information_object.id");
            })
            ->count();
        
        // Objects missing repository
        $this->missingRepository = DB::table("information_object")
            ->where("id", "!=", 1)
            ->whereNull("repository_id")
            ->count();
        
        // Objects missing digital objects
        $this->missingDigitalObjects = DB::table("information_object")
            ->where("id", "!=", 1)
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                    ->from("digital_object")
                    ->whereColumn("digital_object.object_id", "information_object.id");
            })
            ->count();
        
        // Calculate quality score
        $issues = $this->missingTitles + $this->missingDates + $this->missingRepository;
        $this->qualityScore = $this->totalObjects > 0 ? round((1 - ($issues / ($this->totalObjects * 3))) * 100) : 100;
    }
}