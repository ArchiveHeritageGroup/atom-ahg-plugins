<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>">Workflow</a></li>
            <li class="breadcrumb-item active">Task #<?php echo $task->id ?></li>
        </ol>
    </nav>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>
    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $sf_user->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        <?php echo esc_entities($task->object->title ?? "Object #{$task->object_id}") ?>
                    </h5>
                    <span class="badge bg-<?php
                        echo match($task->status) {
                            'pending' => 'warning',
                            'claimed', 'in_progress' => 'primary',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'returned' => 'info',
                            default => 'secondary'
                        };
                    ?> fs-6"><?php echo ucfirst($task->status) ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Workflow:</strong> <?php echo esc_entities($task->workflow_name) ?></p>
                            <p class="mb-1"><strong>Step:</strong> <?php echo esc_entities($task->step_name) ?> (<?php echo ucfirst($task->step_type) ?>)</p>
                            <p class="mb-1"><strong>Priority:</strong>
                                <span class="badge bg-<?php echo $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'secondary') ?>">
                                    <?php echo ucfirst($task->priority) ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Submitted by:</strong> <?php echo esc_entities($task->submitted_user->username ?? 'Unknown') ?></p>
                            <p class="mb-1"><strong>Submitted:</strong> <?php echo date('M j, Y H:i', strtotime($task->created_at)) ?></p>
                            <?php if ($task->due_date): ?>
                                <p class="mb-1"><strong>Due:</strong>
                                    <span class="<?php echo $task->due_date < date('Y-m-d') ? 'text-danger fw-bold' : '' ?>">
                                        <?php echo date('M j, Y', strtotime($task->due_date)) ?>
                                    </span>
                                </p>
                            <?php endif ?>
                        </div>
                    </div>

                    <?php if ($task->instructions): ?>
                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="fas fa-info-circle me-1"></i>Instructions</h6>
                            <?php echo nl2br(esc_entities($task->instructions)) ?>
                        </div>
                    <?php endif ?>

                    <?php if ($task->object): ?>
                        <div class="mb-3">
                            <a href="<?php echo url_for(['slug' => $task->object->slug, 'module' => 'informationobject']) ?>" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-external-link-alt me-1"></i>View Record
                            </a>
                        </div>
                    <?php endif ?>

                    <?php if ($task->checklist): ?>
                        <?php $checklist = json_decode($task->checklist, true) ?: []; ?>
                        <?php $completed = json_decode($task->checklist_completed ?? '{}', true) ?: []; ?>
                        <div class="card bg-light mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-tasks me-1"></i>Review Checklist</h6>
                            </div>
                            <div class="card-body">
                                <?php foreach ($checklist as $idx => $item): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="checklist[<?php echo $idx ?>]" id="check_<?php echo $idx ?>"
                                            <?php echo isset($completed[$idx]) ? 'checked' : '' ?>
                                            <?php echo !$canAct ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="check_<?php echo $idx ?>">
                                            <?php echo esc_entities($item) ?>
                                        </label>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endif ?>
                </div>

                <!-- Action Buttons -->
                <?php if ($canClaim): ?>
                    <div class="card-footer">
                        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'claimTask', 'id' => $task->id]) ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-hand-paper me-1"></i>Claim This Task
                        </a>
                    </div>
                <?php elseif ($canAct): ?>
                    <div class="card-footer">
                        <form method="post" class="d-inline" id="taskForm">
                            <input type="hidden" name="id" value="<?php echo $task->id ?>">

                            <div class="mb-3">
                                <label for="comment" class="form-label">Comment</label>
                                <textarea name="comment" id="comment" class="form-control" rows="3" placeholder="Add a comment (required for rejection)..."></textarea>
                            </div>

                            <div class="btn-group">
                                <button type="submit" formaction="<?php echo url_for(['module' => 'workflow', 'action' => 'approveTask', 'id' => $task->id]) ?>" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-1"></i>Approve
                                </button>
                                <button type="submit" formaction="<?php echo url_for(['module' => 'workflow', 'action' => 'rejectTask', 'id' => $task->id]) ?>" class="btn btn-danger btn-lg" onclick="return confirm('Are you sure you want to reject this task?');">
                                    <i class="fas fa-times me-1"></i>Reject
                                </button>
                            </div>

                            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'releaseTask', 'id' => $task->id]) ?>" class="btn btn-outline-secondary btn-lg ms-2">
                                <i class="fas fa-undo me-1"></i>Release
                            </a>
                        </form>
                    </div>
                <?php elseif ($canResubmit): ?>
                    <div class="card-footer">
                        <div class="alert alert-warning mb-3">
                            <strong>Returned:</strong> <?php echo esc_entities($task->decision_comment) ?>
                        </div>
                        <form method="post" action="<?php echo url_for(['module' => 'workflow', 'action' => 'resubmitTask', 'id' => $task->id]) ?>">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-1"></i>Resubmit for Review
                            </button>
                        </form>
                    </div>
                <?php endif ?>
            </div>

            <!-- History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Task History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Action</th>
                                    <th>User</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($task->history as $entry): ?>
                                    <tr>
                                        <td class="text-nowrap">
                                            <small><?php echo date('M j, H:i', strtotime($entry->performed_at)) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo match($entry->action) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'claimed' => 'primary',
                                                    'started' => 'info',
                                                    'returned' => 'warning',
                                                    'released' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>"><?php echo ucfirst($entry->action) ?></span>
                                        </td>
                                        <td><?php echo esc_entities($entry->username ?? 'Unknown') ?></td>
                                        <td><small><?php echo esc_entities($entry->comment ?? '-') ?></small></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>Task Details</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Task ID</span>
                        <strong>#<?php echo $task->id ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Object Type</span>
                        <strong><?php echo ucfirst(str_replace('_', ' ', $task->object_type)) ?></strong>
                    </li>
                    <?php if ($task->assigned_to): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Assigned To</span>
                            <strong><?php echo esc_entities($task->assigned_user->username ?? 'Unknown') ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Claimed At</span>
                            <strong><?php echo date('M j, H:i', strtotime($task->claimed_at)) ?></strong>
                        </li>
                    <?php endif ?>
                    <?php if ($task->retry_count > 0): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Retry Count</span>
                            <strong class="text-warning"><?php echo $task->retry_count ?></strong>
                        </li>
                    <?php endif ?>
                </ul>
            </div>

            <div class="d-grid gap-2">
                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'myTasks']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to My Tasks
                </a>
                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'pool']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-layer-group me-1"></i>Browse Task Pool
                </a>
            </div>
        </div>
    </div>
</div>
