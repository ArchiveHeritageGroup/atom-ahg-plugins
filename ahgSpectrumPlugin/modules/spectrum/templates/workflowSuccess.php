<?php 
decorate_with('layout_1col');
use Illuminate\Database\Capsule\Manager as DB;

// Get workflow config for current procedure
$workflowConfig = DB::table('spectrum_workflow_config')
    ->where('procedure_type', $procedureType)
    ->where('is_active', 1)
    ->first();

$configData = $workflowConfig ? json_decode($workflowConfig->config_json, true) : null;
$steps = $configData['steps'] ?? [];
$states = $configData['states'] ?? [];
$transitions = $configData['transitions'] ?? [];

// Get current state for this object/procedure
$currentState = DB::table('spectrum_workflow_state')
    ->where('record_id', $resource->id)
    ->where('procedure_type', $procedureType)
    ->first();

$currentStateName = $currentState->current_state ?? ($configData['initial_state'] ?? 'pending');

// Get workflow history
$history = DB::table('spectrum_workflow_history')
    ->where('record_id', $resource->id)
    ->where('procedure_type', $procedureType)
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

// Get available transitions from current state
$availableTransitions = [];
foreach ($transitions as $transKey => $transDef) {
    if (in_array($currentStateName, $transDef['from'])) {
        $availableTransitions[$transKey] = $transDef;
    }
}

// Get users for assignment - use username since authorized_form_of_name is empty
$users = DB::table('user')
    ->whereNotNull('username')
    ->where('username', '!=', '')
    ->select('id', 'username', 'email')
    ->orderBy('username')
    ->get();

// Fallback: if no users found, try alternate query
if ($users->isEmpty()) {
    $users = DB::table('actor')
        ->join('actor_i18n', 'actor.id', '=', 'actor_i18n.id')
        ->join('user', 'actor.id', '=', 'user.id')
        ->where('actor_i18n.culture', 'en')
        ->whereNotNull('actor_i18n.authorized_form_of_name')
        ->select('user.id', 'actor_i18n.authorized_form_of_name')
        ->orderBy('actor_i18n.authorized_form_of_name')
        ->get();
}
?>

<?php slot('title'); ?>
<h1><?php echo __('Spectrum Workflow'); ?>: <?php echo esc_entities($resource->title ?? $resource->slug); ?></h1>
<?php end_slot(); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>"><?php echo esc_entities($resource->title ?? $resource->slug); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'index', 'slug' => $resource->slug]); ?>"><?php echo __('Spectrum'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Workflow'); ?></li>
  </ol>
</nav>

<div class="row">
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo __('Procedures'); ?></h5>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($procedures as $procId => $procDef): ?>
                <?php $isActive = $procedureType === $procId; ?>
                <li class="list-group-item <?php echo $isActive ? 'active' : ''; ?>">
                    <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflow', 'slug' => $resource->slug, 'procedure_type' => $procId]); ?>"
                       class="<?php echo $isActive ? 'text-white' : ''; ?> text-decoration-none d-block">
                        <i class="fa <?php echo $procDef['icon'] ?? 'fa-circle'; ?> me-2"></i>
                        <?php echo $procDef['label']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Linked Record -->
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Linked Record'); ?></h6>
            </div>
            <div class="card-body">
                <span class="badge bg-success mb-2"><i class="fas fa-cube me-1"></i><?php echo __('Item-Level Procedure'); ?></span>
                <h6 class="mb-1">
                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>" class="text-decoration-none">
                        <?php echo esc_entities($resource->title ?? $resource->slug); ?>
                    </a>
                </h6>
                <?php if (!empty($resource->identifier)): ?>
                <small class="text-muted"><?php echo esc_entities($resource->identifier); ?></small>
                <?php endif; ?>
                <?php if (!empty($resource->repositoryName)): ?>
                <br><small class="text-muted"><i class="fas fa-university me-1"></i><?php echo esc_entities($resource->repositoryName); ?></small>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $resource->slug]); ?>" class="btn btn-secondary w-100">
            <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to record'); ?>
        </a>
    </div>

    <div class="col-md-9">
        <?php if ($workflowConfig): ?>
        
        <!-- Current Status Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo esc_entities($workflowConfig->name); ?></h5>
                <span class="badge bg-primary fs-6"><?php echo ucwords(str_replace('_', ' ', $currentStateName)); ?></span>
            </div>
            <div class="card-body">
                <!-- Workflow progress = the state machine itself (what the
                     transition actions actually advance). The earlier version
                     stretched the 4 states over a separate 6-step list, so the
                     marker jumped (1→3→5) and never flowed. The named procedure
                     steps are reference metadata and are listed separately below. -->
                <?php if (!empty($states)): ?>
                <div class="mb-4">
                    <h6><?php echo __('Progress'); ?></h6>
                    <div class="d-flex flex-wrap gap-2 align-items-start">
                        <?php
                        $stateIndex = array_search($currentStateName, $states);
                        if ($stateIndex === false) { $stateIndex = 0; }
                        $stateLabels = $configData['state_labels'] ?? [];
                        $lastStateIndex = count($states) - 1;
                        foreach ($states as $sIndex => $stateKey):
                            if ($sIndex < $stateIndex || ($sIndex === $stateIndex && $sIndex === $lastStateIndex)) {
                                $stateStatus = 'completed';
                            } elseif ($sIndex === $stateIndex) {
                                $stateStatus = 'current';
                            } else {
                                $stateStatus = 'pending';
                            }
                            $badgeClass = match($stateStatus) {
                                'completed' => 'bg-success',
                                'current' => 'bg-warning text-dark',
                                default => 'bg-secondary'
                            };
                            $stateLabel = $stateLabels[$stateKey] ?? ucwords(str_replace('_', ' ', $stateKey));
                        ?>
                        <div class="text-center">
                            <span class="badge <?php echo $badgeClass; ?> d-block mb-1" style="min-width: 30px;">
                                <?php echo $sIndex + 1; ?>
                            </span>
                            <small class="d-block" style="max-width: 90px; font-size: 0.7rem;">
                                <?php echo esc_entities($stateLabel); ?>
                            </small>
                        </div>
                        <?php if ($sIndex < $lastStateIndex): ?>
                        <div class="d-flex align-items-center" style="margin-top: -15px;">
                            <i class="fas fa-arrow-right text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Procedure steps checklist (per-record). Two modes: "checklist"
                     (tick any order) or "linear" (ordered ticking + can't finalise
                     the workflow until all steps are done). -->
                <?php if (!empty($steps)):
                    $stepStates = $stepStates ?? [];
                    $stepsLinear = !empty($configData['steps_linear']);
                    $totalSteps = count($steps);
                    $doneCount = 0;
                    foreach ($steps as $s) {
                        if (!empty($stepStates[$s['key']]) && $stepStates[$s['key']]->is_done) { $doneCount++; }
                    }
                ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">
                            <i class="fas fa-list-check me-1"></i><?php echo __('Procedure steps'); ?>
                            <span class="badge <?php echo $stepsLinear ? 'bg-info' : 'bg-light text-dark'; ?> ms-1" style="font-weight:normal;"><?php echo $stepsLinear ? __('Linear') : __('Checklist'); ?></span>
                        </h6>
                        <span class="badge <?php echo ($totalSteps > 0 && $doneCount === $totalSteps) ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $doneCount; ?>/<?php echo $totalSteps; ?> <?php echo __('done'); ?></span>
                    </div>
                    <?php if ($stepsLinear): ?>
                    <p class="small text-muted mb-2"><i class="fas fa-lock me-1"></i><?php echo __('Linear mode: complete steps in order; the workflow cannot be finalised until all steps are done.'); ?></p>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                    <form method="post" action="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflowSteps', 'slug' => $resource->slug]); ?>">
                        <input type="hidden" name="procedure_type" value="<?php echo esc_entities($procedureType); ?>">
                        <?php foreach ($steps as $index => $step):
                            $st = $stepStates[$step['key']] ?? null;
                            $done = $st && $st->is_done;
                            // In linear mode, only "done" steps and the first not-done
                            // step are actionable; later steps are locked until their
                            // predecessor is complete.
                            $locked = $stepsLinear && ($index > $doneCount);
                        ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="steps_done[]" value="<?php echo esc_entities($step['key']); ?>" id="step_<?php echo esc_entities($step['key']); ?>" <?php echo $done ? 'checked' : ''; ?> <?php echo $locked ? 'disabled' : ''; ?>>
                            <label class="form-check-label <?php echo $locked ? 'text-muted' : ''; ?>" for="step_<?php echo esc_entities($step['key']); ?>">
                                <?php echo esc_entities($step['name']); ?>
                                <?php if ($locked): ?><i class="fas fa-lock text-muted ms-1" style="font-size:0.7rem;"></i><?php endif; ?>
                                <?php if ($done && $st->completed_at): ?>
                                <small class="text-muted">&mdash; <?php echo esc_entities(substr((string) $st->completed_at, 0, 10)); ?></small>
                                <?php endif; ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-sm btn-outline-primary mt-2"><i class="fas fa-save me-1"></i><?php echo __('Save steps'); ?></button>
                    </form>
                    <div class="mt-2">
                        <form method="post" action="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflowStepsMode', 'slug' => $resource->slug]); ?>" class="d-inline">
                            <input type="hidden" name="procedure_type" value="<?php echo esc_entities($procedureType); ?>">
                            <button type="submit" class="btn btn-sm btn-link text-muted p-0" onclick="return confirm('<?php echo __('Switch step mode for this procedure?'); ?>');">
                                <i class="fas fa-exchange-alt me-1"></i><?php echo $stepsLinear ? __('Switch to checklist mode (any order)') : __('Switch to linear mode (ordered + gated)'); ?>
                            </button>
                        </form>
                    </div>
                    <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($steps as $step):
                            $done = !empty($stepStates[$step['key']]) && $stepStates[$step['key']]->is_done;
                        ?>
                        <li><i class="fas <?php echo $done ? 'fa-check-square text-success' : 'fa-square text-muted'; ?> me-1"></i><?php echo esc_entities($step['name']); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Available Actions -->
                <?php if ($canEdit && !empty($availableTransitions)): ?>
                <div class="mb-3">
                    <h6><?php echo __('Available Actions'); ?></h6>
                    <form method="post" action="<?php echo url_for(['module' => 'spectrum', 'action' => 'workflowTransition', 'slug' => $resource->slug]); ?>" class="row g-3">
                        <input type="hidden" name="procedure_type" value="<?php echo esc_entities($procedureType); ?>">
                        <input type="hidden" name="from_state" value="<?php echo esc_entities($currentStateName); ?>">
                        
                        <div class="col-md-4">
                            <label class="form-label"><?php echo __('Action'); ?></label>
                            <select name="transition_key" class="form-select" required>
                                <option value=""><?php echo __('Select action...'); ?></option>
                                <?php foreach ($availableTransitions as $transKey => $transDef): ?>
                                <?php $isRestart = ($transKey === 'restart'); ?>
                                <option value="<?php echo esc_entities($transKey); ?>" data-to-state="<?php echo esc_entities($transDef['to']); ?>">
                                    <?php if ($isRestart): ?>
                                        &#x21bb; <?php echo __('Restart'); ?> &rarr; <?php echo ucwords(str_replace('_', ' ', $transDef['to'])); ?>
                                    <?php else: ?>
                                        <?php echo ucwords(str_replace('_', ' ', $transKey)); ?> &rarr; <?php echo ucwords(str_replace('_', ' ', $transDef['to'])); ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label"><?php echo __('Assign to'); ?></label>
                            <select name="assigned_to" class="form-select">
                                <option value=""><?php echo __('Unassigned'); ?></option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->id; ?>">
                                    <?php echo esc_entities($user->username); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label"><?php echo __('Notes'); ?></label>
                            <textarea name="note" class="form-control" rows="3" placeholder="<?php echo __('Optional - describe reason for this action...'); ?>"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <?php
                            $hasRestart = isset($availableTransitions['restart']);
                            $hasOnlyRestart = $hasRestart && count($availableTransitions) === 1;
                            ?>
                            <button type="submit" class="btn <?php echo $hasOnlyRestart ? 'btn-warning' : 'btn-primary'; ?>">
                                <?php if ($hasOnlyRestart): ?>
                                <i class="fas fa-redo me-1"></i> <?php echo __('Restart Procedure'); ?>
                                <?php else: ?>
                                <i class="fas fa-play me-1"></i> <?php echo __('Execute Action'); ?>
                                <?php endif; ?>
                            </button>
                        </div>
                    </form>
                </div>
                <?php elseif (!$canEdit): ?>
                <div class="alert alert-info">
                    <?php echo __('You do not have permission to modify this workflow.'); ?>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo __('This procedure has been completed.'); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- History -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?php echo __('Activity History'); ?></h6>
            </div>
            <div class="card-body">
                <?php if ($history->isEmpty()): ?>
                <div class="alert alert-info mb-0">
                    <?php echo __('No activity recorded yet. Use the actions above to start the workflow.'); ?>
                </div>
                <?php else: ?>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th><?php echo __('Date'); ?></th>
                            <th><?php echo __('Action'); ?></th>
                            <th><?php echo __('From'); ?></th>
                            <th><?php echo __('To'); ?></th>
                            <th><?php echo __('Notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $event): ?>
                        <tr>
                            <td><small><?php echo esc_entities($event->created_at); ?></small></td>
                            <td>
                                <?php if ($event->transition_key === 'restart'): ?>
                                <span class="text-warning"><i class="fas fa-redo me-1"></i><?php echo __('Restart'); ?></span>
                                <?php else: ?>
                                <?php echo ucwords(str_replace('_', ' ', $event->transition_key)); ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $event->from_state)); ?></span></td>
                            <td><span class="badge bg-primary"><?php echo ucwords(str_replace('_', ' ', $event->to_state)); ?></span></td>
                            <td><small><?php echo esc_entities($event->note ?? ''); ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo __('No workflow configuration found for this procedure type.'); ?>
            <br><small><?php echo __('An administrator needs to configure workflow steps for: '); ?><?php echo esc_entities($procedureType); ?></small>
        </div>
        <?php endif; ?>
    </div>
</div>
