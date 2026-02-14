<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-clock text-primary me-2"></i><?php echo __('Report Schedule'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>
<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $sf_user->getFlash('success'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $sf_user->getFlash('error'); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'index']); ?>"><?php echo __('Report Builder'); ?></a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'edit', 'id' => $report->id]); ?>"><?php echo htmlspecialchars($report->name); ?></a></li>
        <li class="breadcrumb-item active"><?php echo __('Schedule'); ?></li>
    </ol>
</nav>

<div class="row">
    <!-- Create Schedule Form -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle me-2"></i><?php echo __('Create New Schedule'); ?>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'schedule', 'id' => $report->id]); ?>">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Schedule Type'); ?></label>
                        <select class="form-select" name="schedule_type" id="scheduleType">
                            <option value="recurring"><?php echo __('Recurring'); ?></option>
                            <option value="trigger"><?php echo __('Trigger-based'); ?></option>
                        </select>
                    </div>

                    <div id="recurringOptions">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Frequency'); ?></label>
                        <select class="form-select" name="frequency" id="frequency" required>
                            <option value="daily"><?php echo __('Daily'); ?></option>
                            <option value="weekly"><?php echo __('Weekly'); ?></option>
                            <option value="monthly"><?php echo __('Monthly'); ?></option>
                            <option value="quarterly"><?php echo __('Quarterly'); ?></option>
                        </select>
                    </div>

                    <div class="mb-3" id="weeklyOptions" style="display: none;">
                        <label class="form-label"><?php echo __('Day of Week'); ?></label>
                        <select class="form-select" name="day_of_week">
                            <option value="1"><?php echo __('Monday'); ?></option>
                            <option value="2"><?php echo __('Tuesday'); ?></option>
                            <option value="3"><?php echo __('Wednesday'); ?></option>
                            <option value="4"><?php echo __('Thursday'); ?></option>
                            <option value="5"><?php echo __('Friday'); ?></option>
                            <option value="6"><?php echo __('Saturday'); ?></option>
                            <option value="0"><?php echo __('Sunday'); ?></option>
                        </select>
                    </div>

                    <div class="mb-3" id="monthlyOptions" style="display: none;">
                        <label class="form-label"><?php echo __('Day of Month'); ?></label>
                        <select class="form-select" name="day_of_month">
                            <?php for ($i = 1; $i <= 28; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="form-text"><?php echo __('Days 29-31 will use the last day of the month if not available.'); ?></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Time of Day'); ?></label>
                        <input type="time" class="form-control" name="time_of_day" value="08:00" required>
                    </div>

                    </div><!-- end recurringOptions -->

                    <div id="triggerOptions" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Trigger Event'); ?></label>
                            <select class="form-select" name="trigger_event">
                                <option value="new_accession"><?php echo __('New Accession Created'); ?></option>
                                <option value="status_change"><?php echo __('Report Status Changed'); ?></option>
                                <option value="threshold"><?php echo __('Record Count Threshold'); ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Threshold Value'); ?></label>
                            <input type="number" class="form-control" name="trigger_threshold" min="1" placeholder="<?php echo __('e.g., 100'); ?>">
                            <div class="form-text"><?php echo __('Only used for threshold triggers.'); ?></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Output Format'); ?></label>
                        <select class="form-select" name="output_format">
                            <option value="pdf">PDF</option>
                            <option value="docx">Word (DOCX)</option>
                            <option value="xlsx">Excel (XLSX)</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Email Recipients'); ?></label>
                        <textarea class="form-control" name="email_recipients" rows="2"
                                  placeholder="<?php echo __('email1@example.com, email2@example.com'); ?>"></textarea>
                        <div class="form-text"><?php echo __('Comma-separated list of email addresses to receive the report.'); ?></div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i><?php echo __('Create Schedule'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Existing Schedules -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-2"></i><?php echo __('Existing Schedules'); ?></span>
                <span class="badge bg-secondary"><?php echo count($schedules); ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Frequency'); ?></th>
                            <th><?php echo __('Time'); ?></th>
                            <th><?php echo __('Format'); ?></th>
                            <th><?php echo __('Next Run'); ?></th>
                            <th><?php echo __('Status'); ?></th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <?php echo __('No schedules configured.'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-info"><?php echo ucfirst($schedule->frequency); ?></span>
                                    <?php if ($schedule->frequency === 'weekly' && $schedule->day_of_week !== null): ?>
                                        <br><small class="text-muted">
                                            <?php
                                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                            echo $days[(int)$schedule->day_of_week];
                                            ?>
                                        </small>
                                    <?php elseif ($schedule->frequency === 'monthly' && $schedule->day_of_month): ?>
                                        <br><small class="text-muted"><?php echo __('Day'); ?> <?php echo $schedule->day_of_month; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo substr($schedule->time_of_day, 0, 5); ?></td>
                                <td><span class="badge bg-secondary"><?php echo strtoupper($schedule->output_format); ?></span></td>
                                <td>
                                    <?php if ($schedule->next_run): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($schedule->next_run)); ?>
                                    <?php else: ?>
                                        <span class="text-muted">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($schedule->is_active): ?>
                                        <span class="badge bg-success"><?php echo __('Active'); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo __('Paused'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'reportBuilder', 'action' => 'scheduleDelete', 'id' => $report->id, 'scheduleId' => $schedule->id]); ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('<?php echo __('Delete this schedule?'); ?>');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cron Setup Info -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-terminal me-2"></i><?php echo __('Server Setup'); ?>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-2"><?php echo __('Add this cron entry to enable scheduled reports:'); ?></p>
                <code class="d-block p-2 bg-light rounded small">
                    0 * * * * cd ' . sfConfig::get('sf_root_dir') . ' && php plugins/ahgReportBuilderPlugin/bin/run-scheduled-reports.php >> /var/log/atom-reports.log 2>&1
                </code>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('frequency').addEventListener('change', function() {
    document.getElementById('weeklyOptions').style.display = this.value === 'weekly' ? 'block' : 'none';
    document.getElementById('monthlyOptions').style.display = this.value === 'monthly' ? 'block' : 'none';
});
document.getElementById('scheduleType').addEventListener('change', function() {
    var isRecurring = this.value === 'recurring';
    document.getElementById('recurringOptions').style.display = isRecurring ? 'block' : 'none';
    document.getElementById('triggerOptions').style.display = isRecurring ? 'none' : 'block';
    document.getElementById('frequency').required = isRecurring;
});
</script>
<?php end_slot() ?>
