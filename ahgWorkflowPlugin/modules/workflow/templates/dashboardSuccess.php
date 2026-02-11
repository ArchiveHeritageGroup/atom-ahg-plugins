<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-tasks me-2"></i>Workflow Dashboard</h1>
        <?php if ($isAdmin): ?>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>" class="btn btn-outline-primary">
                <i class="fas fa-cog me-1"></i>Manage Workflows
            </a>
        <?php endif ?>
    </div>

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

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">My Tasks</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['my_tasks'] ?? 0 ?></h2>
                        </div>
                        <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'myTasks']) ?>" class="text-white text-decoration-none small">
                        View all <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-1 text-dark-50">Pool Tasks</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['pending_tasks'] ?? 0 ?></h2>
                        </div>
                        <i class="fas fa-inbox fa-2x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0">
                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'pool']) ?>" class="text-dark text-decoration-none small">
                        Browse pool <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">Completed Today</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['completed_today'] ?? 0 ?></h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-subtitle mb-1 text-white-50">Overdue</h6>
                            <h2 class="card-title mb-0"><?php echo $stats['overdue_tasks'] ?? 0 ?></h2>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- My Tasks -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>My Tasks</h5>
                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'myTasks']) ?>" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myTasks)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-2 opacity-50"></i>
                            <p class="mb-0">No tasks assigned to you</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice(sfOutputEscaper::unescape($myTasks), 0, 5) as $task): ?>
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo esc_entities($task->object_title ?? "Object #{$task->object_id}") ?></h6>
                                            <small class="text-muted">
                                                <?php echo esc_entities($task->workflow_name) ?> &rarr; <?php echo esc_entities($task->step_name) ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'secondary') ?>">
                                            <?php echo ucfirst($task->priority) ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- Task Pool -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>Available Tasks</h5>
                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'pool']) ?>" class="btn btn-sm btn-outline-secondary">Browse Pool</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($poolTasks)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-double fa-3x mb-2 opacity-50"></i>
                            <p class="mb-0">No tasks available to claim</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice(sfOutputEscaper::unescape($poolTasks), 0, 5) as $task): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo esc_entities($task->object_title ?? "Object #{$task->object_id}") ?></h6>
                                            <small class="text-muted">
                                                <?php echo esc_entities($task->step_name) ?>
                                            </small>
                                        </div>
                                        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'claimTask', 'id' => $task->id]) ?>" class="btn btn-sm btn-primary">
                                            Claim
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'history']) ?>" class="btn btn-sm btn-outline-secondary">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentActivity)): ?>
                <div class="text-center text-muted py-4">
                    <p class="mb-0">No recent activity</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Object</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivity as $activity): ?>
                                <tr>
                                    <td class="text-nowrap">
                                        <small><?php echo date('M j, H:i', strtotime($activity->performed_at)) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php
                                            echo match($activity->action) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'claimed' => 'primary',
                                                'started' => 'info',
                                                'returned' => 'warning',
                                                default => 'secondary'
                                            };
                                        ?>"><?php echo ucfirst($activity->action) ?></span>
                                    </td>
                                    <td><?php echo esc_entities($activity->object_title ?? "#{$activity->object_id}") ?></td>
                                    <td><small><?php echo esc_entities($activity->username ?? 'Unknown') ?></small></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
