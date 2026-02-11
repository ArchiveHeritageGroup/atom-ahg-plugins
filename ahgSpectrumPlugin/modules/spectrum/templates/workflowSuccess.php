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
                <!-- Steps Progress -->
                <?php if (!empty($steps)): ?>
                <div class="mb-4">
                    <h6><?php echo __('Steps'); ?></h6>
                    <div class="d-flex flex-wrap gap-2">
                        <?php 
                        $stateIndex = array_search($currentStateName, $states);
                        foreach ($steps as $index => $step): 
                            $stepStatus = 'pending';
                            if ($index < $stateIndex) $stepStatus = 'completed';
                            elseif ($index == $stateIndex) $stepStatus = 'current';
                            
                            $badgeClass = match($stepStatus) {
                                'completed' => 'bg-success',
                                'current' => 'bg-warning',
                                default => 'bg-secondary'
                            };
                        ?>
                        <div class="text-center">
                            <span class="badge <?php echo $badgeClass; ?> d-block mb-1" style="min-width: 30px;">
                                <?php echo $step['order']; ?>
                            </span>
                            <small class="d-block" style="max-width: 80px; font-size: 0.7rem;">
                                <?php echo esc_entities($step['name']); ?>
                            </small>
                        </div>
                        <?php if ($index < count($steps) - 1): ?>
                        <div class="d-flex align-items-center" style="margin-top: -15px;">
                            <i class="fas fa-arrow-right text-muted"></i>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
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
