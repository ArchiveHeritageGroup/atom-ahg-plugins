<?php use_helper('Date') ?>

<?php include_partial('workflow/accessibilityHelpers') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Overdue Dashboard</h1>
            <p class="text-muted mb-0">SLA monitoring and escalation overview</p>
        </div>
        <div>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'overdue', 'format' => 'csv']) ?>" class="btn btn-sm btn-outline-secondary me-1" title="Export CSV">
                <i class="fas fa-file-csv me-1"></i>Export CSV
            </a>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- SLA Overview Banner -->
    <?php $overview = sfOutputEscaper::unescape($slaOverview) ?>
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-2">
                    <div class="h4 mb-0"><?php echo $overview['total_open'] ?? 0 ?></div>
                    <small class="text-muted">Open Tasks</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(40,167,69,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-success"><?php echo $overview['on_track'] ?? 0 ?></div>
                    <small class="text-muted">On Track</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(255,193,7,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-warning"><?php echo $overview['at_risk'] ?? 0 ?></div>
                    <small class="text-muted">At Risk</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(253,126,20,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0" style="color:#fd7e14"><?php echo $overview['overdue'] ?? 0 ?></div>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0" style="background-color: rgba(220,53,69,0.1);">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-danger"><?php echo $overview['breached'] ?? 0 ?></div>
                    <small class="text-muted">Breached</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-2">
                    <div class="h4 mb-0"><?php echo $overview['health_pct'] ?? 0 ?>%</div>
                    <small class="text-muted">SLA Health</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Queue Breakdown -->
    <?php $queueStats = sfOutputEscaper::unescape($statsByQueue) ?>
    <?php if (!empty($queueStats)): ?>
        <div class="card mb-4">
            <div class="card-header"><strong>SLA by Queue</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Queue</th>
                            <th class="text-center">Open</th>
                            <th class="text-center text-success">On Track</th>
                            <th class="text-center text-warning">At Risk</th>
                            <th class="text-center" style="color:#fd7e14">Overdue</th>
                            <th class="text-center text-danger">Breached</th>
                            <th class="text-center">Health</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($queueStats as $qs): ?>
                            <tr>
                                <td><strong><?php echo esc_entities($qs['queue_name'] ?? 'Unknown') ?></strong></td>
                                <td class="text-center"><?php echo $qs['total'] ?? 0 ?></td>
                                <td class="text-center"><?php echo $qs['on_track'] ?? 0 ?></td>
                                <td class="text-center"><?php echo $qs['at_risk'] ?? 0 ?></td>
                                <td class="text-center"><?php echo $qs['overdue'] ?? 0 ?></td>
                                <td class="text-center"><?php echo $qs['breached'] ?? 0 ?></td>
                                <td class="text-center">
                                    <?php
                                        $total = ($qs['total'] ?? 0);
                                        $healthy = ($qs['on_track'] ?? 0);
                                        $pct = $total > 0 ? round(($healthy / $total) * 100) : 100;
                                        $barColor = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <div class="progress" style="height: 6px; min-width: 60px;">
                                        <div class="progress-bar <?php echo $barColor ?>" style="width: <?php echo $pct ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $pct ?>%</small>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <input type="hidden" name="module" value="workflow">
                <input type="hidden" name="action" value="overdue">
                <div class="col-auto">
                    <label class="form-label form-label-sm mb-0">Queue</label>
                    <select name="queue_id" class="form-select form-select-sm">
                        <option value="">All Queues</option>
                        <?php foreach (sfOutputEscaper::unescape($queues) as $q): ?>
                            <option value="<?php echo $q->id ?>" <?php echo ($filters['queue_id'] ?? '') == $q->id ? 'selected' : '' ?>><?php echo esc_entities($q->name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php if ($isAdmin): ?>
                    <div class="col-auto">
                        <label class="form-label form-label-sm mb-0">Assigned To</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">All Users</option>
                            <?php foreach (sfOutputEscaper::unescape($users) as $u): ?>
                                <option value="<?php echo $u->id ?>" <?php echo ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' ?>><?php echo esc_entities($u->name) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                <?php endif ?>
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

    <!-- Overdue Tasks -->
    <?php $overdueTasks = sfOutputEscaper::unescape($tasks) ?>
    <?php if (empty($overdueTasks)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i>No overdue tasks. All SLAs are on track.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Object</th>
                        <th>Assigned To</th>
                        <th>Queue</th>
                        <th>Priority</th>
                        <th>SLA Status</th>
                        <th>Due Date</th>
                        <th>Overdue By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdueTasks as $task): ?>
                        <?php
                            $sla = $task->sla ?? [];
                            $slaStatus = $sla['sla_status'] ?? 'overdue';
                            $slaBadge = match ($slaStatus) {
                                'at_risk' => 'bg-warning text-dark',
                                'overdue' => 'bg-warning',
                                'breached' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>">
                                    <?php echo esc_entities($task->object_title ?? "Task #{$task->id}") ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($task->assignee_name): ?>
                                    <?php echo esc_entities($task->assignee_name) ?>
                                <?php else: ?>
                                    <span class="text-warning">Unassigned</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($task->queue_name): ?>
                                    <span class="badge bg-light text-dark"><?php echo esc_entities($task->queue_name) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
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
                            <td><span class="badge <?php echo $slaBadge ?>"><?php echo esc_entities(ucwords(str_replace('_', ' ', $slaStatus))) ?></span></td>
                            <td>
                                <?php if ($task->due_date): ?>
                                    <?php echo esc_entities(date('d M Y', strtotime($task->due_date))) ?>
                                <?php else: ?>
                                    <span class="text-muted">No due date</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if (!empty($sla['remaining_human'])): ?>
                                    <span class="text-danger fw-bold"><?php echo esc_entities($sla['remaining_human']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'viewTask', 'id' => $task->id]) ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'timeline', 'object_id' => $task->object_id]) ?>" class="btn btn-outline-secondary" title="Timeline">
                                        <i class="fas fa-stream"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted"><?php echo count($overdueTasks) ?> overdue task(s)</small>
        </div>
    <?php endif ?>
</div>
