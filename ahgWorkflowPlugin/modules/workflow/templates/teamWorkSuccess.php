<?php use_helper('Date') ?>

<?php include_partial('workflow/accessibilityHelpers') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-users me-2"></i>Team Work</h1>
        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="module" value="workflow">
                <input type="hidden" name="action" value="teamWork">
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">Team</label>
                    <select name="group_id" class="form-select form-select-sm">
                        <option value="">Select team...</option>
                        <?php foreach (sfOutputEscaper::unescape($roles) as $role): ?>
                            <option value="<?php echo $role->id ?>" <?php echo ($currentGroupId ?? 0) == $role->id ? 'selected' : '' ?>><?php echo esc_entities($role->name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
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
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Team Tasks -->
    <?php $teamTasks = sfOutputEscaper::unescape($tasks) ?>
    <?php if (empty($teamTasks)): ?>
        <div class="alert alert-info">
            <?php if (!$currentGroupId): ?>
                Select a team to view tasks.
            <?php else: ?>
                No tasks found for this team.
            <?php endif ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" id="select-all-team"></th>
                        <th>Object</th>
                        <th>Assigned To</th>
                        <th>Queue</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>SLA</th>
                        <th>Due</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teamTasks as $task): ?>
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
                                <?php if ($task->assignee_name): ?>
                                    <i class="fas fa-user me-1 text-muted small"></i><?php echo esc_entities($task->assignee_name) ?>
                                <?php else: ?>
                                    <span class="text-warning"><i class="fas fa-exclamation-circle me-1"></i>Unassigned</span>
                                <?php endif ?>
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
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted"><?php echo count($teamTasks) ?> task(s)</small>
        </div>
    <?php endif ?>
</div>
