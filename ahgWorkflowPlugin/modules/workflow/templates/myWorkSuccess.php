<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-user-check me-2"></i>My Work</h1>
        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="module" value="workflow">
                <input type="hidden" name="action" value="myWork">
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">Queue</label>
                    <select name="queue_id" class="form-select form-select-sm">
                        <option value="">All Queues</option>
                        <?php foreach (sfOutputEscaper::unescape($queues) as $q): ?>
                            <option value="<?php echo $q->id ?>" <?php echo ($filters['queue_id'] ?? '') == $q->id ? 'selected' : '' ?>><?php echo esc_entities($q->name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="claimed" <?php echo ($filters['status'] ?? '') === 'claimed' ? 'selected' : '' ?>>Claimed</option>
                        <option value="in_progress" <?php echo ($filters['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="returned" <?php echo ($filters['status'] ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">Priority</label>
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="urgent" <?php echo ($filters['priority'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                        <option value="high" <?php echo ($filters['priority'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                        <option value="normal" <?php echo ($filters['priority'] ?? '') === 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="low" <?php echo ($filters['priority'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Task List -->
    <?php $myWorkTasks = sfOutputEscaper::unescape($tasks) ?>
    <?php if (empty($myWorkTasks)): ?>
        <div class="alert alert-info">No tasks assigned to you matching the current filters.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" id="select-all-my"></th>
                        <th>Object</th>
                        <th>Queue</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>SLA</th>
                        <th>Due</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myWorkTasks as $task): ?>
                        <?php
                            $sla = $task->sla ?? null;
                            $slaStatus = $sla['sla_status'] ?? 'unknown';
                            $slaBadge = match ($slaStatus) {
                                'on_track' => 'bg-success',
                                'at_risk' => 'bg-warning text-dark',
                                'overdue' => 'bg-warning',
                                'breached' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        ?>
                        <tr>
                            <td><input type="checkbox" class="task-cb" value="<?php echo $task->id ?>"></td>
                            <td>
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>">
                                    <?php echo esc_entities($task->object_title ?? "Task #{$task->id}") ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($task->queue_name): ?>
                                    <span class="badge bg-light text-dark"><?php echo esc_entities($task->queue_name) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo esc_entities($task->status) ?></span></td>
                            <td>
                                <?php
                                    $priColor = match ($task->priority ?? 'normal') {
                                        'urgent' => 'danger',
                                        'high' => 'warning',
                                        'low' => 'info',
                                        default => 'secondary',
                                    };
                                ?>
                                <span class="badge bg-<?php echo $priColor ?>"><?php echo esc_entities($task->priority ?? 'normal') ?></span>
                            </td>
                            <td>
                                <?php if ($sla): ?>
                                    <span class="badge <?php echo $slaBadge ?>"><?php echo esc_entities(ucwords(str_replace('_', ' ', $slaStatus))) ?></span>
                                    <?php if (!empty($sla['remaining_human'])): ?>
                                        <br><small class="text-muted"><?php echo esc_entities($sla['remaining_human']) ?></small>
                                    <?php endif ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($task->due_date): ?>
                                    <small><?php echo esc_entities(date('d M Y', strtotime($task->due_date))) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php
                                    $created = strtotime($task->created_at);
                                    $days = max(0, floor((time() - $created) / 86400));
                                ?>
                                <small><?php echo $days ?> day<?php echo $days !== 1 ? 's' : '' ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (in_array($task->status, ['claimed', 'in_progress'])): ?>
                                        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'approveTask', 'id' => $task->id]) ?>" class="btn btn-outline-success" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted"><?php echo count($myWorkTasks) ?> task(s)</small>
        </div>
    <?php endif ?>
</div>
