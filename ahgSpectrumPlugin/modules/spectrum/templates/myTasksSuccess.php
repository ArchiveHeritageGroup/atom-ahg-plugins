<?php decorate_with('layout_1col'); ?>

<?php
// Get raw arrays from escaper
$proceduresRaw = $sf_data->getRaw('procedures');
$workflowConfigsRaw = $sf_data->getRaw('workflowConfigs');
$tasksRaw = $sf_data->getRaw('tasks');
$procedureTypesRaw = $sf_data->getRaw('procedureTypes');
?>

<?php slot('title'); ?>
<h1>
    <i class="fas fa-clipboard-list me-2"></i><?php echo __('My Tasks'); ?>
    <?php if ($unreadCount > 0): ?>
    <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?> <?php echo __('new'); ?></span>
    <?php endif; ?>
</h1>
<?php end_slot(); ?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter me-2"></i><?php echo __('Filter'); ?></h5>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Procedure Type'); ?></label>
                        <select name="procedure_type" class="form-select">
                            <option value=""><?php echo __('All procedures'); ?></option>
                            <?php foreach ($procedureTypesRaw as $type): ?>
                            <option value="<?php echo esc_entities($type); ?>" <?php echo $currentFilter === $type ? 'selected' : ''; ?>>
                                <?php echo esc_entities($proceduresRaw[$type]['label'] ?? ucwords(str_replace('_', ' ', $type))); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i><?php echo __('Filter'); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i><?php echo __('Summary'); ?></h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?php echo __('Total Tasks'); ?></span>
                    <span class="badge bg-primary"><?php echo count($tasksRaw); ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links'); ?></h5>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?php echo url_for(['module' => 'spectrum', 'action' => 'dashboard']); ?>" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt me-2"></i><?php echo __('Workflow Dashboard'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="col-md-9">
        <?php if (empty($tasksRaw)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-check fa-4x text-muted mb-3"></i>
                <h4 class="text-muted"><?php echo __('No tasks assigned'); ?></h4>
                <p class="text-muted"><?php echo __('You have no pending tasks at this time.'); ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i><?php echo __('Assigned Tasks'); ?></h5>
                <span class="badge bg-primary"><?php echo count($tasksRaw); ?> <?php echo __('tasks'); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Object'); ?></th>
                            <th><?php echo __('Procedure'); ?></th>
                            <th><?php echo __('State'); ?></th>
                            <th><?php echo __('Assigned'); ?></th>
                            <th><?php echo __('Actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasksRaw as $task): ?>
                        <?php
                            $procedureLabel = $proceduresRaw[$task->procedure_type]['label'] ?? ucwords(str_replace('_', ' ', $task->procedure_type));
                            $stateLabel = $task->current_state;
                            if (isset($workflowConfigsRaw[$task->procedure_type]['state_labels'][$task->current_state])) {
                                $stateLabel = $workflowConfigsRaw[$task->procedure_type]['state_labels'][$task->current_state];
                            } else {
                                $stateLabel = ucwords(str_replace('_', ' ', $task->current_state));
                            }

                            // Determine state badge color
                            $stateBadge = 'secondary';
                            $state = $task->current_state;
                            if (strpos($state, 'completed') !== false || strpos($state, 'approved') !== false) {
                                $stateBadge = 'success';
                            } elseif (strpos($state, 'review') !== false || strpos($state, 'pending') !== false) {
                                $stateBadge = 'warning';
                            } elseif (strpos($state, 'progress') !== false || strpos($state, 'active') !== false) {
                                $stateBadge = 'primary';
                            } elseif (strpos($state, 'reject') !== false || strpos($state, 'cancel') !== false) {
                                $stateBadge = 'danger';
                            }
                        ?>
                        <tr>
                            <td>
                                <a href="/<?php echo esc_entities($task->slug); ?>" class="text-decoration-none">
                                    <strong><?php echo esc_entities($task->object_title ?: $task->identifier ?: 'Untitled'); ?></strong>
                                </a>
                                <?php if ($task->identifier): ?>
                                <br><small class="text-muted"><?php echo esc_entities($task->identifier); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo esc_entities($procedureLabel); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $stateBadge; ?>"><?php echo esc_entities($stateLabel); ?></span>
                            </td>
                            <td>
                                <?php if ($task->assigned_at): ?>
                                <small>
                                    <?php echo date('d M Y H:i', strtotime($task->assigned_at)); ?>
                                    <?php if ($task->assigned_by_name): ?>
                                    <br><span class="text-muted"><?php echo __('by'); ?> <?php echo esc_entities($task->assigned_by_name); ?></span>
                                    <?php endif; ?>
                                </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="/index.php/spectrum/<?php echo esc_entities($task->slug); ?>/workflow?procedure_type=<?php echo urlencode($task->procedure_type); ?>"
                                   class="btn btn-sm btn-outline-primary" title="<?php echo __('View Workflow'); ?>">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
