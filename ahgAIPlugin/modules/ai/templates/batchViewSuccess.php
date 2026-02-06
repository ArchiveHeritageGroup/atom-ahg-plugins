<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
<div class="sidebar-content">
    <section class="sidebar-section">
        <h4><?php echo __('AI Job Queue'); ?></h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo url_for(['module' => 'ai', 'action' => 'batch']); ?>">
                    <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Queue'); ?>
                </a>
            </li>
        </ul>
    </section>
    <section class="sidebar-section">
        <h4><?php echo __('Batch Actions'); ?></h4>
        <div class="d-grid gap-2">
            <?php if ($batch->status === 'pending'): ?>
                <button class="btn btn-success btn-sm" onclick="batchAction('start')">
                    <i class="fas fa-play me-1"></i><?php echo __('Start Batch'); ?>
                </button>
            <?php elseif ($batch->status === 'running'): ?>
                <button class="btn btn-warning btn-sm" onclick="batchAction('pause')">
                    <i class="fas fa-pause me-1"></i><?php echo __('Pause'); ?>
                </button>
            <?php elseif ($batch->status === 'paused'): ?>
                <button class="btn btn-success btn-sm" onclick="batchAction('resume')">
                    <i class="fas fa-play me-1"></i><?php echo __('Resume'); ?>
                </button>
            <?php endif; ?>
            <?php if (in_array($batch->status, ['pending', 'running', 'paused'])): ?>
                <button class="btn btn-danger btn-sm" onclick="if(confirm('Cancel this batch?')) batchAction('cancel')">
                    <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
                </button>
            <?php endif; ?>
            <?php if ($batch->failed_items > 0): ?>
                <button class="btn btn-outline-warning btn-sm" onclick="batchAction('retry')">
                    <i class="fas fa-redo me-1"></i><?php echo __('Retry Failed'); ?>
                </button>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php end_slot(); ?>

<?php slot('title'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>
        <i class="fas fa-tasks me-2"></i>
        <?php echo htmlspecialchars($batch->name); ?>
        <span class="badge bg-<?php
            echo match($batch->status) {
                'pending' => 'warning',
                'running' => 'primary',
                'completed' => 'success',
                'failed' => 'danger',
                'paused' => 'secondary',
                'cancelled' => 'dark',
                default => 'secondary'
            };
        ?> ms-2"><?php echo ucfirst($batch->status); ?></span>
    </h1>
</div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.progress-lg { height: 24px; }
.job-row:hover { background: #f8f9fa; }
.status-badge { font-size: 0.75rem; }
.refresh-spin { animation: spin 1s linear infinite; }
@keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<!-- Batch Overview -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-info-circle me-2"></i><?php echo __('Batch Overview'); ?></span>
        <?php if ($batch->status === 'running'): ?>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshProgress()" id="refreshBtn">
                <i class="fas fa-sync-alt me-1"></i><?php echo __('Refresh'); ?>
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <p class="mb-1"><strong><?php echo __('Description'); ?>:</strong></p>
                <p class="text-muted"><?php echo $batch->description ? htmlspecialchars($batch->description) : '<em>No description</em>'; ?></p>
            </div>
            <div class="col-md-6">
                <p class="mb-1"><strong><?php echo __('Task Types'); ?>:</strong></p>
                <?php
                $taskTypesArr = json_decode($batch->task_types, true) ?: [];
                foreach ($taskTypesArr as $tt):
                    if (isset($taskTypes[$tt])):
                ?>
                    <span class="badge bg-light text-dark me-1">
                        <i class="fas <?php echo $taskTypes[$tt]['icon']; ?> me-1"></i>
                        <?php echo $taskTypes[$tt]['label']; ?>
                    </span>
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        </div>

        <!-- Progress -->
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
                <span id="progressText"><?php echo $batch->completed_items; ?>/<?php echo $batch->total_items; ?> completed</span>
                <span id="progressPercent"><?php echo number_format($batch->progress_percent, 1); ?>%</span>
            </div>
            <div class="progress progress-lg">
                <div class="progress-bar bg-success" id="progressBar" style="width: <?php echo $batch->progress_percent; ?>%">
                </div>
                <?php if ($batch->failed_items > 0): ?>
                    <div class="progress-bar bg-danger" id="failedBar" style="width: <?php echo ($batch->failed_items / $batch->total_items * 100); ?>%">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row text-center">
            <div class="col">
                <h4 class="mb-0" id="statTotal"><?php echo $batch->total_items; ?></h4>
                <small class="text-muted"><?php echo __('Total'); ?></small>
            </div>
            <div class="col">
                <h4 class="mb-0 text-warning" id="statPending"><?php echo $stats['pending'] ?? 0; ?></h4>
                <small class="text-muted"><?php echo __('Pending'); ?></small>
            </div>
            <div class="col">
                <h4 class="mb-0 text-primary" id="statRunning"><?php echo $stats['running'] ?? 0; ?></h4>
                <small class="text-muted"><?php echo __('Running'); ?></small>
            </div>
            <div class="col">
                <h4 class="mb-0 text-success" id="statCompleted"><?php echo $batch->completed_items; ?></h4>
                <small class="text-muted"><?php echo __('Completed'); ?></small>
            </div>
            <div class="col">
                <h4 class="mb-0 text-danger" id="statFailed"><?php echo $batch->failed_items; ?></h4>
                <small class="text-muted"><?php echo __('Failed'); ?></small>
            </div>
        </div>

        <!-- Timing Info -->
        <div class="row mt-3 small text-muted">
            <div class="col-md-4">
                <i class="fas fa-calendar-plus me-1"></i>
                <?php echo __('Created'); ?>: <?php echo date('M j, Y g:i A', strtotime($batch->created_at)); ?>
            </div>
            <?php if ($batch->started_at): ?>
            <div class="col-md-4">
                <i class="fas fa-play me-1"></i>
                <?php echo __('Started'); ?>: <?php echo date('M j, Y g:i A', strtotime($batch->started_at)); ?>
            </div>
            <?php endif; ?>
            <?php if ($batch->completed_at): ?>
            <div class="col-md-4">
                <i class="fas fa-check me-1"></i>
                <?php echo __('Completed'); ?>: <?php echo date('M j, Y g:i A', strtotime($batch->completed_at)); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Job List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i><?php echo __('Jobs'); ?> (<?php echo count($jobs); ?>)</span>
        <div>
            <select id="statusFilter" class="form-select form-select-sm d-inline-block" style="width: auto" onchange="filterJobs()">
                <option value=""><?php echo __('All Status'); ?></option>
                <option value="pending"><?php echo __('Pending'); ?></option>
                <option value="queued"><?php echo __('Queued'); ?></option>
                <option value="running"><?php echo __('Running'); ?></option>
                <option value="completed"><?php echo __('Completed'); ?></option>
                <option value="failed"><?php echo __('Failed'); ?></option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="jobsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px"><?php echo __('ID'); ?></th>
                        <th><?php echo __('Object'); ?></th>
                        <th style="width: 100px"><?php echo __('Task'); ?></th>
                        <th style="width: 100px"><?php echo __('Status'); ?></th>
                        <th style="width: 80px"><?php echo __('Attempts'); ?></th>
                        <th style="width: 100px"><?php echo __('Time'); ?></th>
                        <th style="width: 200px"><?php echo __('Error'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jobs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <?php echo __('No jobs in this batch'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($jobs as $job): ?>
                            <?php
                            $statusBadge = match($job->status) {
                                'pending' => 'warning',
                                'queued' => 'info',
                                'running' => 'primary',
                                'completed' => 'success',
                                'failed' => 'danger',
                                'skipped' => 'secondary',
                                default => 'secondary'
                            };
                            ?>
                            <tr class="job-row" data-status="<?php echo $job->status; ?>">
                                <td><?php echo $job->id; ?></td>
                                <td>
                                    <?php if (isset($objects[$job->object_id])): ?>
                                        <a href="/index.php/<?php echo $objects[$job->object_id]->slug; ?>" target="_blank">
                                            <?php echo htmlspecialchars($objects[$job->object_id]->title ?? 'Untitled'); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo __('Object'); ?> #<?php echo $job->object_id; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($taskTypes[$job->task_type])): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas <?php echo $taskTypes[$job->task_type]['icon']; ?> me-1"></i>
                                            <?php echo $taskTypes[$job->task_type]['label']; ?>
                                        </span>
                                    <?php else: ?>
                                        <?php echo $job->task_type; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusBadge; ?> status-badge">
                                        <?php echo ucfirst($job->status); ?>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo $job->attempt_count; ?></td>
                                <td>
                                    <?php if ($job->processing_time_ms): ?>
                                        <?php echo number_format($job->processing_time_ms / 1000, 2); ?>s
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($job->error_message): ?>
                                        <span class="text-danger small" title="<?php echo htmlspecialchars($job->error_message); ?>">
                                            <?php echo htmlspecialchars(substr($job->error_message, 0, 50)); ?>
                                            <?php if (strlen($job->error_message) > 50): ?>...<?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Logs -->
<?php if (!empty($logs)): ?>
<div class="card mt-4">
    <div class="card-header">
        <i class="fas fa-history me-2"></i><?php echo __('Recent Activity'); ?>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
            <?php foreach ($logs as $log): ?>
                <?php
                $logIcon = match($log->event_type) {
                    'batch_created' => 'fa-plus text-primary',
                    'batch_started' => 'fa-play text-success',
                    'batch_paused' => 'fa-pause text-warning',
                    'batch_completed' => 'fa-check-circle text-success',
                    'job_completed' => 'fa-check text-success',
                    'job_failed' => 'fa-times text-danger',
                    'job_retry' => 'fa-redo text-warning',
                    default => 'fa-info-circle text-muted'
                };
                ?>
                <div class="list-group-item list-group-item-action py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas <?php echo $logIcon; ?> me-2"></i>
                            <?php echo htmlspecialchars($log->message); ?>
                        </span>
                        <small class="text-muted"><?php echo date('g:i:s A', strtotime($log->created_at)); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
const batchId = <?php echo $batch->id; ?>;
let autoRefresh = <?php echo $batch->status === 'running' ? 'true' : 'false'; ?>;
let refreshInterval;

function batchAction(action) {
    fetch('/ai/batch/' + batchId + '/action', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: action })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Action failed');
        }
    });
}

function refreshProgress() {
    const btn = document.getElementById('refreshBtn');
    if (btn) {
        btn.querySelector('i').classList.add('refresh-spin');
    }

    fetch('/ai/batch/' + batchId + '/progress')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update progress
                document.getElementById('progressBar').style.width = data.progress_percent + '%';
                document.getElementById('progressText').textContent = data.completed + '/' + data.total + ' completed';
                document.getElementById('progressPercent').textContent = data.progress_percent.toFixed(1) + '%';

                // Update stats
                document.getElementById('statCompleted').textContent = data.completed;
                document.getElementById('statFailed').textContent = data.failed;
                if (data.stats) {
                    document.getElementById('statPending').textContent = data.stats.pending || 0;
                    document.getElementById('statRunning').textContent = data.stats.running || 0;
                }

                // Check if completed
                if (data.status !== 'running') {
                    autoRefresh = false;
                    clearInterval(refreshInterval);
                    location.reload();
                }
            }
        })
        .finally(() => {
            if (btn) {
                btn.querySelector('i').classList.remove('refresh-spin');
            }
        });
}

function filterJobs() {
    const status = document.getElementById('statusFilter').value;
    const rows = document.querySelectorAll('#jobsTable tbody tr');
    rows.forEach(row => {
        if (!status || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Auto-refresh if running
if (autoRefresh) {
    refreshInterval = setInterval(refreshProgress, 5000);
}
</script>

<?php end_slot(); ?>
