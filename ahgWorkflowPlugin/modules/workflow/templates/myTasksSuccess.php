<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-clipboard-list me-2"></i>My Tasks</h1>
        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo !$currentStatus ? 'active' : '' ?>" href="<?php echo url_for(['module' => 'workflow', 'action' => 'myTasks']) ?>">
                All Active
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $currentStatus === 'claimed' ? 'active' : '' ?>" href="<?php echo url_for(['module' => 'workflow', 'action' => 'myTasks', 'status' => 'claimed']) ?>">
                Claimed
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $currentStatus === 'in_progress' ? 'active' : '' ?>" href="<?php echo url_for(['module' => 'workflow', 'action' => 'myTasks', 'status' => 'in_progress']) ?>">
                In Progress
            </a>
        </li>
    </ul>

    <?php if (empty($tasks)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fa-4x mb-3 opacity-50"></i>
            <h4>No tasks assigned to you</h4>
            <p>Browse the <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'pool']) ?>">task pool</a> to claim available tasks.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Object</th>
                        <th>Workflow</th>
                        <th>Step</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr class="<?php echo ($task->due_date && $task->due_date < date('Y-m-d')) ? 'table-danger' : '' ?>">
                            <td>
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>">
                                    <?php echo esc_entities($task->object_title ?? "Object #{$task->object_id}") ?>
                                </a>
                            </td>
                            <td><?php echo esc_entities($task->workflow_name) ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo esc_entities($task->step_name) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'secondary') ?>">
                                    <?php echo ucfirst($task->priority) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task->due_date): ?>
                                    <span class="<?php echo $task->due_date < date('Y-m-d') ? 'text-danger fw-bold' : '' ?>">
                                        <?php echo date('M j, Y', strtotime($task->due_date)) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php
                                    echo match($task->status) {
                                        'claimed' => 'primary',
                                        'in_progress' => 'info',
                                        default => 'secondary'
                                    };
                                ?>"><?php echo ucfirst($task->status) ?></span>
                            </td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
