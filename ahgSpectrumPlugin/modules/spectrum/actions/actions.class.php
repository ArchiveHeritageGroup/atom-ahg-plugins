<?php

use Illuminate\Database\Capsule\Manager as DB;

class spectrumActions extends sfActions
{
    /**
     * Get resource by slug using Laravel
     */
    protected function getResourceBySlug($slug)
    {
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
                ->where('culture', 'en')
                ->first();
            $resource->title = $i18n ? $i18n->title : null;
            
            // Get repository info
            if ($resource->repository_id) {
                $repoI18n = DB::table('actor_i18n')
                    ->where('id', $resource->repository_id)
                    ->where('culture', 'en')
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
            // Create a new condition check
            $newId = DB::table('spectrum_condition_check')->insertGetId([
                'object_id' => $objectId,
                'condition_check_reference' => 'CC-' . date('Ymd') . '-' . $objectId,
                'check_date' => date('Y-m-d'),
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

        // Create notification
        DB::table('spectrum_notification')->insert([
            'user_id' => $assignedToUserId,
            'notification_type' => 'task_assignment',
            'subject' => $subject,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get procedure label helper
     */
    protected function getProcedureLabel($procedureType)
    {
        $procedures = ahgSpectrumWorkflowService::getProcedures();
        return $procedures[$procedureType] ?? ucwords(str_replace('_', ' ', $procedureType));
    }

    public function executeIndex(sfWebRequest $request)
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

    public function executeWorkflow(sfWebRequest $request)
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
    }
    
    public function executeWorkflowUpdate(sfWebRequest $request)
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

    
    public function executeWorkflowTransition(sfWebRequest $request)
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
        
        // Prepare assignment data
        $assignedToInt = $assignedTo ? (int) $assignedTo : null;
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

        // Mark existing notifications as read when task reaches final state
        if ($isFinalState) {
            ahgSpectrumNotificationService::markTaskNotificationsAsReadBySlug($slug, $procedureType);
        }

        // Redirect back
        $this->redirect(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $slug, 'procedure_type' => $procedureType]);
    }

    public function executeLabel(sfWebRequest $request)
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
     * My Tasks - Show tasks assigned to current user
     */
    public function executeMyTasks(sfWebRequest $request)
    {
        // Require authentication
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('user/login');
        }

        $userId = $this->getUser()->getAttribute('user_id');
        $procedureTypeFilter = $request->getParameter('procedure_type');

        // Get workflow configs for state labels and final states
        $this->workflowConfigs = [];
        $allFinalStates = [];
        $configs = DB::table('spectrum_workflow_config')
            ->where('is_active', 1)
            ->get();
        foreach ($configs as $config) {
            $configData = json_decode($config->config_json, true);
            $this->workflowConfigs[$config->procedure_type] = $configData;
            // Collect final states for this procedure
            $finalStates = ahgSpectrumWorkflowService::getFinalStates($config->procedure_type);
            $allFinalStates = array_merge($allFinalStates, $finalStates);
        }
        $allFinalStates = array_unique($allFinalStates);

        // Build query for assigned tasks (excluding final states)
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
            ->leftJoin('information_object_i18n as ioi18n', function($join) {
                $join->on('io.id', '=', 'ioi18n.id')
                     ->where('ioi18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->leftJoin('user as assigner', 'sws.assigned_by', '=', 'assigner.id')
            ->where('sws.assigned_to', $userId);

        // Exclude all final states from all procedures
        if (!empty($allFinalStates)) {
            $query->whereNotIn('sws.current_state', $allFinalStates);
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

    public function executeDashboard(sfWebRequest $request)
    {
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
        
        // Handle repository filter
        $this->selectedRepository = $request->getParameter('repository', '');
        $repoId = $this->selectedRepository ? (int)$this->selectedRepository : null;
    }

    public function executeConditionPhotos(sfWebRequest $request)
    {
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
            // Log error
            error_log('Condition check error: ' . $e->getMessage());
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
        
        if (!$files || !is_array($files)) {
            return;
        }
        
        $uploadDir = sfConfig::get('sf_upload_dir') . '/condition_photos/' . $this->resource->id;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files as $file) {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
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
            }
        }
        
        // Redirect back to avoid form resubmission
        $this->redirect(['module' => 'spectrum', 'action' => 'conditionPhotos', 'slug' => $this->resource->slug]);
    }

    public function executeInstall(sfWebRequest $request)
    {
        $this->installed = $this->checkTablesExist();
    }

    public function executeConditionReport(sfWebRequest $request)
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

    public function executeGrapDashboard(sfWebRequest $request)
    {
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
            $this->grapData = DB::table('grap_heritage_asset')
                ->where('object_id', $this->resource->id)
                ->first();

            if ($this->grapData) {
                $this->totalAssets = 1;
                $this->valuedAssets = $this->grapData->current_value ? 1 : 0;
                $this->pendingValuation = $this->grapData->current_value ? 0 : 1;
                $this->totalValue = $this->grapData->current_value ?? 0;
                $this->assetRegisterComplete = true;
                $this->valuationsCurrent = $this->grapData->valuation_date &&
                    strtotime($this->grapData->valuation_date) > strtotime('-5 years');
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

    public function executeLoanDashboard(sfWebRequest $request)
    {
        $this->loans = [];
        try {
            $this->loans = DB::table('spectrum_loan_out')
                ->where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    public function executeProvenanceAjax(sfWebRequest $request)
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
    public function executeAnnotationSave(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
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
    public function executeAnnotationGet(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
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
    public function executePhotoDelete(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
        $photoId = $request->getParameter('photo_id');
        
        if (!$photoId) {
            return $this->renderText(json_encode(['success' => false, 'error' => 'No photo ID']));
        }
        
        try {
            $photo = DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->first();
            
            if ($photo && $photo->file_path) {
                $fullPath = sfConfig::get('sf_web_dir') . $photo->file_path;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            DB::table('spectrum_condition_photo')
                ->where('id', $photoId)
                ->delete();
            
            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }
    
    /**
     * Set primary photo
     */
    public function executePhotoSetPrimary(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
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
    public function executePhotoRotate(sfWebRequest $request)
    {
        $this->getResponse()->setContentType('application/json');
        
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
            
            $fullPath = sfConfig::get('sf_web_dir') . $photo->file_path;
            
            if (!file_exists($fullPath)) {
                return $this->renderText(json_encode(['success' => false, 'error' => 'File not found']));
            }
            
            $image = imagecreatefromstring(file_get_contents($fullPath));
            if ($image) {
                $rotated = imagerotate($image, -$degrees, 0);
                
                $mime = $photo->mime_type ?: 'image/jpeg';
                if (strpos($mime, 'png') !== false) {
                    imagepng($rotated, $fullPath);
                } else {
                    imagejpeg($rotated, $fullPath, 90);
                }
                
                imagedestroy($image);
                imagedestroy($rotated);
                
                // Swap width/height in DB
                DB::table('spectrum_condition_photo')
                    ->where('id', $photoId)
                    ->update([
                        'width' => $photo->height,
                        'height' => $photo->width,
                    ]);
            }
            
            return $this->renderText(json_encode(['success' => true]));
        } catch (\Exception $e) {
            return $this->renderText(json_encode(['success' => false, 'error' => $e->getMessage()]));
        }
    }

    // ========== EXTENSION ACTIONS ==========

    /**
     * Security Compliance Dashboard
     */
    public function executeSecurityCompliance(sfWebRequest $request)
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
    public function executePrivacyCompliance(sfWebRequest $request)
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
    public function executePrivacyRopa(sfWebRequest $request)
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
    public function executePrivacyDsar(sfWebRequest $request)
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
    public function executePrivacyBreaches(sfWebRequest $request)
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
    public function executeConditionAdmin(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $this->recentEvents = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object as io', 'c.object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
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
    public function executeConditionRisk(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        $this->riskItems = DB::table('spectrum_condition_check as c')
            ->leftJoin('information_object_i18n as i18n', function($j) {
                $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
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
    public function executePrivacyDsarUpdate(sfWebRequest $request)
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
    public function executePrivacyBreachUpdate(sfWebRequest $request)
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
    public function executePrivacyAdmin(sfWebRequest $request)
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
    public function executePrivacyTemplates(sfWebRequest $request)
    {
        if (!$this->getUser()->isAuthenticated()) {
            $this->redirect('/user/login');
        }
        
        $uploadDir = sfConfig::get('sf_upload_dir', sfConfig::get('sf_upload_dir')) . '/privacy_templates/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Handle file upload
        if ($request->isMethod('post') && isset($_FILES['template_file'])) {
            $action = $request->getParameter('action');
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
    public function executePrivacyTemplateDownload(sfWebRequest $request)
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
    public function executePrivacyTemplateDelete(sfWebRequest $request)
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
            DB::table('privacy_template')->where('id', $id)->delete();
        }
        
        $this->redirect('/admin/privacy/templates');
    }

    /**
     * Spectrum History Export (PDF/CSV)
     */
    public function executeExport(sfWebRequest $request)
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
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
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
        $data = [];
        $filename = "spectrum_{$type}_" . date('Y-m-d');
        
        switch ($type) {
            case 'condition':
                $data = DB::table('spectrum_condition_check as c')
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('c.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->select('c.*', 'i18n.title as object_title')
                    ->orderBy('c.check_date', 'desc')
                    ->get()->toArray();
                break;
            case 'valuation':
                $data = DB::table('spectrum_valuation as v')
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('v.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->select('v.*', 'i18n.title as object_title')
                    ->orderBy('v.valuation_date', 'desc')
                    ->get()->toArray();
                break;
            case 'movement':
                $data = DB::table('spectrum_movement as m')
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('m.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->select('m.*', 'i18n.title as object_title')
                    ->orderBy('m.movement_date', 'desc')
                    ->get()->toArray();
                break;
            case 'loan':
                $loansIn = DB::table('spectrum_loan_in as l')
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->select('l.*', 'i18n.title as object_title', DB::raw("'IN' as direction"))
                    ->get()->toArray();
                $loansOut = DB::table('spectrum_loan_out as l')
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('l.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->select('l.*', 'i18n.title as object_title', DB::raw("'OUT' as direction"))
                    ->get()->toArray();
                $data = array_merge($loansIn, $loansOut);
                break;
            case 'workflow':
                $data = DB::table('spectrum_workflow_history as w')
                    ->leftJoin('information_object_i18n as i18n', function($j) {
                        $j->on('w.object_id', '=', 'i18n.id')->where('i18n.culture', '=', 'en');
                    })
                    ->select('w.*', 'i18n.title as object_title')
                    ->orderBy('w.created_at', 'desc')
                    ->get()->toArray();
                break;
        }
        
        if ($format === 'csv') {
            $this->getResponse()->setContentType('text/csv');
            $this->getResponse()->setHttpHeader('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
            
            $output = fopen('php://output', 'w');
            if (!empty($data)) {
                fputcsv($output, array_keys((array)$data[0]));
                foreach ($data as $row) {
                    fputcsv($output, (array)$row);
                }
            }
            fclose($output);
            return sfView::NONE;
        } else {
            // JSON format
            $this->getResponse()->setContentType('application/json');
            $this->getResponse()->setHttpHeader('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
            echo json_encode($data, JSON_PRETTY_PRINT);
            return sfView::NONE;
        }
    }

    /**
     * GRAP-Spectrum Procedure Linking
     */
    public function executeGrapSpectrumLink(sfWebRequest $request)
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


    public function executeDataQuality(sfWebRequest $request)
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