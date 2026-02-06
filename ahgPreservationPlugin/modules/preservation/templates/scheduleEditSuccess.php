<?php slot('title', $schedule ? __('Edit Schedule') : __('New Schedule')); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>
        <i class="fas fa-<?php echo $schedule ? 'edit' : 'plus' ?>"></i>
        <?php echo $schedule ? __('Edit Schedule') : __('New Schedule') ?>
    </h1>
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'scheduler']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Scheduler') ?>
    </a>
</div>

<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Schedule Configuration</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="name" class="form-label">Schedule Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($schedule->name ?? '') ?>"
                                   placeholder="e.g., Daily Format Identification">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="workflow_type" class="form-label">Workflow Type *</label>
                            <select class="form-select" id="workflow_type" name="workflow_type" required>
                                <?php foreach ($workflowTypes as $value => $label): ?>
                                    <option value="<?php echo $value ?>"
                                            <?php echo ($schedule->workflow_type ?? '') === $value ? 'selected' : '' ?>>
                                        <?php echo $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"
                                  placeholder="Optional description of this schedule"><?php echo htmlspecialchars($schedule->description ?? '') ?></textarea>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3"><i class="fas fa-clock"></i> Schedule Timing</h6>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cron_expression" class="form-label">Cron Expression *</label>
                            <input type="text" class="form-control font-monospace" id="cron_expression" name="cron_expression"
                                   value="<?php echo htmlspecialchars($schedule->cron_expression ?? '0 2 * * *') ?>"
                                   placeholder="0 2 * * *">
                            <div class="form-text" id="cron_description">Daily at 02:00</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quick Presets</label>
                            <div class="btn-group d-block" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-sm cron-preset" data-cron="0 1 * * *">1:00 AM</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm cron-preset" data-cron="0 2 * * *">2:00 AM</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm cron-preset" data-cron="0 3 * * *">3:00 AM</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm cron-preset" data-cron="0 * * * *">Hourly</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm cron-preset" data-cron="0 4 * * 0">Sunday 4AM</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm cron-preset" data-cron="0 6 * * 6">Saturday 6AM</button>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3"><i class="fas fa-sliders-h"></i> Execution Settings</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="batch_limit" class="form-label">Batch Limit</label>
                            <input type="number" class="form-control" id="batch_limit" name="batch_limit"
                                   value="<?php echo $schedule->batch_limit ?? 100 ?>" min="1" max="10000">
                            <div class="form-text">Max objects per run</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="timeout_minutes" class="form-label">Timeout (minutes)</label>
                            <input type="number" class="form-control" id="timeout_minutes" name="timeout_minutes"
                                   value="<?php echo $schedule->timeout_minutes ?? 60 ?>" min="1" max="480">
                            <div class="form-text">Max runtime before abort</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled"
                                       <?php echo ($schedule->is_enabled ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_enabled">Enabled</label>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3"><i class="fas fa-bell"></i> Notifications</h6>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notify_on_failure" name="notify_on_failure"
                                       <?php echo ($schedule->notify_on_failure ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notify_on_failure">Notify on Failure</label>
                            </div>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="notify_email" class="form-label">Notification Email</label>
                            <input type="email" class="form-control" id="notify_email" name="notify_email"
                                   value="<?php echo htmlspecialchars($schedule->notify_email ?? '') ?>"
                                   placeholder="admin@example.com">
                        </div>
                    </div>

                    <input type="hidden" name="schedule_type" value="cron">

                    <hr>
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'scheduler']) ?>"
                           class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i><?php echo $schedule ? 'Update Schedule' : 'Create Schedule' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Cron Help -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-question-circle"></i> Cron Format</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-2 rounded small">
 ┌───────────── minute (0 - 59)
 │ ┌───────────── hour (0 - 23)
 │ │ ┌───────────── day of month (1 - 31)
 │ │ │ ┌───────────── month (1 - 12)
 │ │ │ │ ┌───────────── day of week (0 - 6)
 │ │ │ │ │              (Sunday = 0)
 │ │ │ │ │
 * * * * *</pre>
                <p class="small mb-2"><strong>Examples:</strong></p>
                <ul class="small mb-0">
                    <li><code>0 2 * * *</code> - Daily at 2:00 AM</li>
                    <li><code>0 */4 * * *</code> - Every 4 hours</li>
                    <li><code>0 3 * * 0</code> - Sundays at 3:00 AM</li>
                    <li><code>0 6 * * 1-5</code> - Weekdays at 6:00 AM</li>
                    <li><code>0 0 1 * *</code> - 1st of each month</li>
                </ul>
            </div>
        </div>

        <?php if ($schedule && !empty($runs)): ?>
        <!-- Recent Runs -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Runs</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($runs as $run): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <small>
                                <?php echo date('Y-m-d H:i', strtotime($run->started_at)) ?>
                                <br>
                                <span class="text-success"><?php echo $run->objects_succeeded ?></span> /
                                <span class="text-danger"><?php echo $run->objects_failed ?></span> /
                                <?php echo $run->objects_processed ?>
                            </small>
                            <?php
                            $statusBadge = [
                                'completed' => 'bg-success',
                                'running' => 'bg-info',
                                'partial' => 'bg-warning',
                                'failed' => 'bg-danger',
                            ];
                            $badge = $statusBadge[$run->status] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?php echo $badge ?>"><?php echo $run->status ?></span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($schedule): ?>
        <!-- Schedule Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistics</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-6">Total Runs</dt>
                    <dd class="col-6"><?php echo number_format($schedule->total_runs) ?></dd>
                    <dt class="col-6">Total Processed</dt>
                    <dd class="col-6"><?php echo number_format($schedule->total_processed) ?></dd>
                    <dt class="col-6">Created</dt>
                    <dd class="col-6"><small><?php echo $schedule->created_at ? date('Y-m-d', strtotime($schedule->created_at)) : '-' ?></small></dd>
                    <dt class="col-6">Created By</dt>
                    <dd class="col-6"><small><?php echo htmlspecialchars($schedule->created_by ?? 'system') ?></small></dd>
                </dl>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    const cronInput = document.getElementById('cron_expression');
    const cronDesc = document.getElementById('cron_description');

    // Update cron description
    function updateCronDescription() {
        const cron = cronInput.value.trim();
        const parts = cron.split(/\s+/);

        if (parts.length !== 5) {
            cronDesc.textContent = 'Invalid format';
            return;
        }

        const [minute, hour, day, month, weekday] = parts;
        let desc = '';

        // Simple descriptions
        if (minute === '0' && hour !== '*' && day === '*' && month === '*' && weekday === '*') {
            desc = 'Daily at ' + hour.padStart(2, '0') + ':00';
        } else if (minute === '0' && hour !== '*' && day === '*' && month === '*' && weekday === '0') {
            desc = 'Sundays at ' + hour.padStart(2, '0') + ':00';
        } else if (minute === '0' && hour !== '*' && day === '*' && month === '*' && weekday === '6') {
            desc = 'Saturdays at ' + hour.padStart(2, '0') + ':00';
        } else if (minute === '0' && hour.startsWith('*/')) {
            desc = 'Every ' + hour.substring(2) + ' hours';
        } else if (minute === '0' && hour === '*') {
            desc = 'Every hour at :00';
        } else {
            desc = 'Custom schedule';
        }

        cronDesc.textContent = desc;
    }

    cronInput.addEventListener('input', updateCronDescription);
    updateCronDescription();

    // Cron presets
    document.querySelectorAll('.cron-preset').forEach(function(btn) {
        btn.addEventListener('click', function() {
            cronInput.value = this.dataset.cron;
            updateCronDescription();
        });
    });
});
</script>
