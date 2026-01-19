<?php slot('title', __('Workflow Scheduler')); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clock"></i> Workflow Scheduler</h1>
    <div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'scheduleEdit']) ?>" class="btn btn-success me-2">
            <i class="fas fa-plus me-1"></i><?php echo __('New Schedule') ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
        </a>
    </div>
</div>

<?php if ($sf_user->hasFlash('notice')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('notice') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?php echo $stats['total_schedules'] ?? 0 ?></h3>
                <small>Total Schedules</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo $stats['enabled_schedules'] ?? 0 ?></h3>
                <small>Enabled</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $stats['last_24h_runs'] ?? 0 ?></h3>
                <small>Runs (24h)</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?php echo ($stats['last_24h_failed'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' ?> text-white">
            <div class="card-body text-center">
                <h3><?php echo $stats['last_24h_failed'] ?? 0 ?></h3>
                <small>Failed (24h)</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Schedules List -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Configured Schedules</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($schedules)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                        <p>No schedules configured yet.</p>
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'scheduleEdit']) ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create First Schedule
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Schedule</th>
                                    <th>Type</th>
                                    <th>Timing</th>
                                    <th>Last Run</th>
                                    <th>Next Run</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $schedule): ?>
                                <tr id="schedule-row-<?php echo $schedule->id ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($schedule->name) ?></strong>
                                        <?php if ($schedule->description): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($schedule->description, 0, 60)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $schedule->typeInfo['color'] ?>">
                                            <i class="fas <?php echo $schedule->typeInfo['icon'] ?> me-1"></i>
                                            <?php echo $schedule->typeInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <code><?php echo htmlspecialchars($schedule->cron_expression ?? 'Manual') ?></code>
                                            <br><?php echo $schedule->scheduleDescription ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($schedule->last_run_at): ?>
                                            <small>
                                                <?php echo date('Y-m-d H:i', strtotime($schedule->last_run_at)) ?>
                                                <br>
                                                <?php
                                                $statusBadge = [
                                                    'success' => 'bg-success',
                                                    'completed' => 'bg-success',
                                                    'partial' => 'bg-warning',
                                                    'failed' => 'bg-danger',
                                                    'timeout' => 'bg-danger',
                                                ];
                                                $badge = $statusBadge[$schedule->last_run_status] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $badge ?>"><?php echo $schedule->last_run_status ?></span>
                                                (<?php echo number_format($schedule->last_run_processed) ?>)
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Never</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($schedule->is_enabled && $schedule->next_run_at): ?>
                                            <small id="next-run-<?php echo $schedule->id ?>">
                                                <?php echo date('Y-m-d H:i', strtotime($schedule->next_run_at)) ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input schedule-toggle" type="checkbox"
                                                   data-id="<?php echo $schedule->id ?>"
                                                   <?php echo $schedule->is_enabled ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-success run-schedule"
                                                    data-id="<?php echo $schedule->id ?>"
                                                    title="Run Now">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'scheduleEdit', 'id' => $schedule->id]) ?>"
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger delete-schedule"
                                                    data-id="<?php echo $schedule->id ?>"
                                                    data-name="<?php echo htmlspecialchars($schedule->name) ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Runs -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Runs</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentRuns)): ?>
                    <div class="p-4 text-center text-muted">
                        <p>No workflow runs yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Schedule</th>
                                    <th>Started</th>
                                    <th>Duration</th>
                                    <th>Processed</th>
                                    <th>Status</th>
                                    <th>Triggered By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRuns as $run): ?>
                                <tr class="run-row" data-id="<?php echo $run->id ?>" style="cursor: pointer;">
                                    <td><?php echo htmlspecialchars($run->schedule_name) ?></td>
                                    <td><small><?php echo date('Y-m-d H:i:s', strtotime($run->started_at)) ?></small></td>
                                    <td>
                                        <?php if ($run->duration_ms): ?>
                                            <small><?php echo number_format($run->duration_ms / 1000, 1) ?>s</small>
                                        <?php elseif ($run->status === 'running'): ?>
                                            <small class="text-warning"><i class="fas fa-spinner fa-spin"></i> Running</small>
                                        <?php else: ?>
                                            <small>-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <span class="text-success"><?php echo $run->objects_succeeded ?></span> /
                                            <span class="text-danger"><?php echo $run->objects_failed ?></span> /
                                            <?php echo $run->objects_processed ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = [
                                            'completed' => 'bg-success',
                                            'running' => 'bg-info',
                                            'partial' => 'bg-warning',
                                            'failed' => 'bg-danger',
                                            'timeout' => 'bg-danger',
                                            'cancelled' => 'bg-secondary',
                                        ];
                                        $badge = $statusBadge[$run->status] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badge ?>"><?php echo $run->status ?></span>
                                    </td>
                                    <td><small><?php echo $run->triggered_by ?><?php echo $run->triggered_by_user ? " ({$run->triggered_by_user})" : '' ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Upcoming Schedules -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Upcoming Runs</h5>
            </div>
            <div class="card-body">
                <?php if (empty($stats['upcoming'])): ?>
                    <p class="text-muted mb-0">No upcoming scheduled runs.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stats['upcoming'] as $upcoming): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong><?php echo htmlspecialchars($upcoming->name) ?></strong>
                                <br><small class="text-muted"><?php echo date('M j, H:i', strtotime($upcoming->next_run_at)) ?></small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- CLI Command Reference -->
        <div class="card border-secondary">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-terminal"></i> CLI Command</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">Run the scheduler via cron to execute due workflows:</p>
                <pre class="bg-dark text-light p-3 rounded small"><code># Run every minute (recommended)
* * * * * cd /usr/share/nginx/archive && \
  php symfony preservation:scheduler >> \
  /var/log/atom/scheduler.log 2>&1</code></pre>
                <p class="small text-muted mb-0">Or run individual workflows:</p>
                <pre class="bg-dark text-light p-3 rounded small mb-0"><code>php symfony preservation:identify --limit=500
php symfony preservation:fixity --limit=500
php symfony preservation:virus-scan --limit=200</code></pre>
            </div>
        </div>
    </div>
</div>

<!-- Run Details Modal -->
<div class="modal fade" id="runDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Run Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="runDetailsContent">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle schedule enabled/disabled
    document.querySelectorAll('.schedule-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const id = this.dataset.id;
            const checkbox = this;

            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiScheduleToggle']) ?>?id=' + id, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    checkbox.checked = !checkbox.checked;
                } else {
                    // Update next run display
                    const nextRunEl = document.getElementById('next-run-' + id);
                    if (nextRunEl) {
                        if (data.next_run_at) {
                            nextRunEl.textContent = data.next_run_at.replace(' ', ' ').substring(0, 16);
                        } else {
                            nextRunEl.textContent = '-';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                checkbox.checked = !checkbox.checked;
            });
        });
    });

    // Run schedule manually
    document.querySelectorAll('.run-schedule').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const button = this;

            if (!confirm('Run this workflow now?')) return;

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiScheduleRun']) ?>?id=' + id, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-play"></i>';

                if (data.success) {
                    alert('Workflow completed!\n\nProcessed: ' + data.results.processed +
                          '\nSucceeded: ' + data.results.succeeded +
                          '\nFailed: ' + data.results.failed);
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-play"></i>';
                alert('Error running workflow');
            });
        });
    });

    // Delete schedule
    document.querySelectorAll('.delete-schedule').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;

            if (!confirm('Delete schedule "' + name + '"?\n\nThis will also delete all run history.')) return;

            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'apiScheduleDelete']) ?>?id=' + id, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('schedule-row-' + id).remove();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Error deleting schedule');
            });
        });
    });

    // View run details
    document.querySelectorAll('.run-row').forEach(function(row) {
        row.addEventListener('click', function() {
            const id = this.dataset.id;
            const modal = new bootstrap.Modal(document.getElementById('runDetailsModal'));
            const content = document.getElementById('runDetailsContent');

            content.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            modal.show();

            fetch('<?php echo url_for(['module' => 'preservation', 'action' => 'scheduleRunView']) ?>?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const run = data.run;
                    let html = '<dl class="row">';
                    html += '<dt class="col-sm-3">Schedule</dt><dd class="col-sm-9">' + run.schedule_name + '</dd>';
                    html += '<dt class="col-sm-3">Workflow Type</dt><dd class="col-sm-9">' + run.workflow_type + '</dd>';
                    html += '<dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="badge bg-' +
                            (run.status === 'completed' ? 'success' : (run.status === 'failed' ? 'danger' : 'warning')) +
                            '">' + run.status + '</span></dd>';
                    html += '<dt class="col-sm-3">Started</dt><dd class="col-sm-9">' + run.started_at + '</dd>';
                    html += '<dt class="col-sm-3">Completed</dt><dd class="col-sm-9">' + (run.completed_at || '-') + '</dd>';
                    html += '<dt class="col-sm-3">Duration</dt><dd class="col-sm-9">' +
                            (run.duration_ms ? (run.duration_ms / 1000).toFixed(1) + 's' : '-') + '</dd>';
                    html += '<dt class="col-sm-3">Processed</dt><dd class="col-sm-9">' + run.objects_processed + '</dd>';
                    html += '<dt class="col-sm-3">Succeeded</dt><dd class="col-sm-9 text-success">' + run.objects_succeeded + '</dd>';
                    html += '<dt class="col-sm-3">Failed</dt><dd class="col-sm-9 text-danger">' + run.objects_failed + '</dd>';
                    html += '<dt class="col-sm-3">Triggered By</dt><dd class="col-sm-9">' + run.triggered_by +
                            (run.triggered_by_user ? ' (' + run.triggered_by_user + ')' : '') + '</dd>';

                    if (run.error_message) {
                        html += '<dt class="col-sm-3">Error</dt><dd class="col-sm-9"><pre class="bg-danger text-white p-2 rounded small">' +
                                run.error_message + '</pre></dd>';
                    }

                    if (run.summary) {
                        html += '<dt class="col-sm-3">Summary</dt><dd class="col-sm-9"><pre class="bg-light p-2 rounded small">' +
                                JSON.stringify(run.summary, null, 2) + '</pre></dd>';
                    }

                    html += '</dl>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = '<div class="alert alert-danger">' + (data.error || 'Error loading details') + '</div>';
                }
            })
            .catch(error => {
                content.innerHTML = '<div class="alert alert-danger">Error loading run details</div>';
            });
        });
    });
});
</script>
