<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-layer-group me-2"></i>Task Pool</h1>
        <a href="<?php echo url_for('workflow/dashboard') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-1"></i>
        These are unclaimed tasks available for you to work on. Click "Claim" to assign a task to yourself.
    </div>

    <?php if (empty($tasks)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-check-double fa-4x mb-3 opacity-50"></i>
            <h4>No tasks available</h4>
            <p>All tasks are either claimed or you don't have permission to claim them.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($tasks as $task): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'secondary') ?>">
                                <?php echo ucfirst($task->priority) ?>
                            </span>
                            <small class="text-muted"><?php echo date('M j, H:i', strtotime($task->created_at)) ?></small>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo esc_entities($task->object_title ?? "Object #{$task->object_id}") ?></h5>
                            <p class="card-text text-muted small">
                                <strong>Workflow:</strong> <?php echo esc_entities($task->workflow_name) ?><br>
                                <strong>Step:</strong> <?php echo esc_entities($task->step_name) ?> (<?php echo ucfirst($task->step_type) ?>)
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <a href="<?php echo url_for("workflow/task/{$task->id}/claim") ?>" class="btn btn-primary">
                                    <i class="fas fa-hand-paper me-1"></i>Claim Task
                                </a>
                                <a href="<?php echo url_for("workflow/task/{$task->id}") ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
