<?php
$session = $sf_data->getRaw('session');
$job = $sf_data->getRaw('job');
?>

<h1><?php echo __('Commit & Report') ?></h1>

<?php echo get_partial('default/breadcrumb', [
    'objects' => [
        ['title' => __('Admin'), 'url' => url_for(['module' => 'admin', 'action' => 'index'])],
        ['title' => __('Ingestion Manager'), 'url' => url_for(['module' => 'ingest', 'action' => 'index'])],
        ['title' => esc_entities($session->title ?: __('Session #' . $session->id))],
        ['title' => __('Commit')]
    ]
]) ?>

<!-- Wizard Progress -->
<div class="mb-4">
    <div class="d-flex justify-content-between text-center">
        <div class="flex-fill"><span class="badge bg-success rounded-pill">1</span><br><small class="text-muted"><?php echo __('Configure') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">2</span><br><small class="text-muted"><?php echo __('Upload') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">3</span><br><small class="text-muted"><?php echo __('Map') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">4</span><br><small class="text-muted"><?php echo __('Validate') ?></small></div>
        <div class="flex-fill"><span class="badge bg-success rounded-pill">5</span><br><small class="text-muted"><?php echo __('Preview') ?></small></div>
        <div class="flex-fill"><span class="badge bg-primary rounded-pill">6</span><br><small class="fw-bold"><?php echo __('Commit') ?></small></div>
    </div>
    <div class="progress mt-2" style="height: 4px;">
        <div class="progress-bar" style="width: 100%"></div>
    </div>
</div>

<?php if ($job): ?>
    <?php
    $isRunning = in_array($job->status, ['queued', 'running']);
    $isCompleted = $job->status === 'completed';
    $isFailed = $job->status === 'failed';
    $pct = $job->total_rows > 0 ? round(($job->processed_rows / $job->total_rows) * 100) : 0;
    $errors = json_decode($job->error_log ?: '[]', true);
    ?>

    <?php if ($isRunning): ?>
        <!-- Progress (polling) -->
        <div class="card mb-4" id="progress-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-spinner fa-spin me-2"></i><?php echo __('Committing...') ?></h5>
            </div>
            <div class="card-body">
                <div class="progress mb-3" style="height: 24px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="commit-progress"
                         style="width: <?php echo $pct ?>%">
                        <?php echo $pct ?>%
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col">
                        <strong id="stat-processed"><?php echo $job->processed_rows ?></strong> / <?php echo $job->total_rows ?>
                        <br><small class="text-muted"><?php echo __('Processed') ?></small>
                    </div>
                    <div class="col">
                        <strong id="stat-records" class="text-success"><?php echo $job->created_records ?></strong>
                        <br><small class="text-muted"><?php echo __('Records') ?></small>
                    </div>
                    <div class="col">
                        <strong id="stat-dos" class="text-info"><?php echo $job->created_dos ?></strong>
                        <br><small class="text-muted"><?php echo __('Digital Objects') ?></small>
                    </div>
                    <div class="col">
                        <strong id="stat-errors" class="text-danger"><?php echo $job->error_count ?></strong>
                        <br><small class="text-muted"><?php echo __('Errors') ?></small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if ($isCompleted || $isFailed): ?>
        <!-- Completion Report -->
        <div class="card mb-4">
            <div class="card-header bg-<?php echo $isCompleted ? 'success' : 'danger' ?> text-white">
                <h5 class="mb-0">
                    <i class="fas fa-<?php echo $isCompleted ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?php echo $isCompleted ? __('Ingest Completed') : __('Ingest Completed with Errors') ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-md-3">
                        <div class="card border-0">
                            <div class="card-body">
                                <h2 class="mb-0 text-success"><?php echo $job->created_records ?></h2>
                                <small class="text-muted"><?php echo __('Records Created') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0">
                            <div class="card-body">
                                <h2 class="mb-0 text-info"><?php echo $job->created_dos ?></h2>
                                <small class="text-muted"><?php echo __('Digital Objects') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0">
                            <div class="card-body">
                                <h2 class="mb-0 text-danger"><?php echo $job->error_count ?></h2>
                                <small class="text-muted"><?php echo __('Errors') ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0">
                            <div class="card-body">
                                <?php
                                $elapsed = '';
                                if ($job->started_at && $job->completed_at) {
                                    $diff = strtotime($job->completed_at) - strtotime($job->started_at);
                                    $elapsed = $diff < 60 ? $diff . 's' : round($diff / 60, 1) . 'm';
                                }
                                ?>
                                <h2 class="mb-0"><?php echo $elapsed ?: '—' ?></h2>
                                <small class="text-muted"><?php echo __('Duration') ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                    <?php if ($job->manifest_path): ?>
                        <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'downloadManifest', 'id' => $session->id]) ?>"
                           class="btn btn-outline-primary">
                            <i class="fas fa-file-csv me-1"></i><?php echo __('Download Manifest') ?>
                        </a>
                    <?php endif ?>

                    <?php if ($job->created_records > 0): ?>
                        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) ?>"
                           class="btn btn-outline-success">
                            <i class="fas fa-list me-1"></i><?php echo __('Browse Records') ?>
                        </a>
                    <?php endif ?>

                    <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'rollback', 'id' => $session->id]) ?>"
                       class="btn btn-outline-danger"
                       onclick="return confirm('<?php echo __('This will DELETE all records created by this ingest. This action cannot be undone. Are you sure?') ?>')">
                        <i class="fas fa-undo me-1"></i><?php echo __('Rollback') ?>
                    </a>
                </div>

                <?php if ($job->sip_package_id): ?>
                    <div class="alert alert-info mb-2">
                        <i class="fas fa-box me-1"></i><?php echo __('SIP (Submission Information Package) generated') ?>
                    </div>
                <?php endif ?>

                <?php if (!empty($job->aip_package_id)): ?>
                    <div class="alert alert-success mb-2">
                        <i class="fas fa-archive me-1"></i><?php echo __('AIP (Archival Information Package) generated') ?>
                    </div>
                <?php endif ?>

                <?php if ($job->dip_package_id): ?>
                    <div class="alert alert-info mb-2">
                        <i class="fas fa-box-open me-1"></i><?php echo __('DIP (Dissemination Information Package) generated') ?>
                    </div>
                <?php endif ?>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-danger"></i><?php echo __('Error Log') ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('Row / Stage') ?></th>
                                    <th><?php echo __('Error') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($errors as $err): ?>
                                    <tr>
                                        <td>
                                            <?php if (isset($err['row'])): ?>
                                                <?php echo __('Row') ?> #<?php echo $err['row'] ?>
                                            <?php elseif (isset($err['stage'])): ?>
                                                <?php echo ucfirst($err['stage']) ?>
                                            <?php endif ?>
                                        </td>
                                        <td><code><?php echo esc_entities($err['error'] ?? '') ?></code></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif ?>
    <?php endif ?>

<?php else: ?>
    <!-- No job yet — show start button -->
    <div class="card mb-4">
        <div class="card-body text-center py-5">
            <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
            <h5><?php echo __('Ready to commit') ?></h5>
            <p class="text-muted"><?php echo __('This will create records in AtoM based on your validated data.') ?></p>
            <form method="post" action="<?php echo url_for(['module' => 'ingest', 'action' => 'commit', 'id' => $session->id]) ?>">
                <button type="submit" class="btn btn-lg btn-success"
                        onclick="return confirm('<?php echo __('Start committing records to AtoM?') ?>')">
                    <i class="fas fa-play me-1"></i><?php echo __('Start Commit') ?>
                </button>
            </form>
        </div>
    </div>
<?php endif ?>

<div class="text-center">
    <a href="<?php echo url_for(['module' => 'ingest', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Dashboard') ?>
    </a>
</div>

<?php if ($job && in_array($job->status, ['queued', 'running'])): ?>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var pollInterval = setInterval(function() {
        fetch('<?php echo url_for(['module' => 'ingest', 'action' => 'jobStatus']) ?>?job_id=<?php echo $job->id ?>', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data || data.error) return;

            var pct = data.total_rows > 0 ? Math.round((data.processed_rows / data.total_rows) * 100) : 0;
            var bar = document.getElementById('commit-progress');
            if (bar) {
                bar.style.width = pct + '%';
                bar.textContent = pct + '%';
            }

            var el;
            el = document.getElementById('stat-processed');
            if (el) el.textContent = data.processed_rows;
            el = document.getElementById('stat-records');
            if (el) el.textContent = data.created_records;
            el = document.getElementById('stat-dos');
            if (el) el.textContent = data.created_dos;
            el = document.getElementById('stat-errors');
            if (el) el.textContent = data.error_count;

            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(pollInterval);
                location.reload();
            }
        })
        .catch(function() {});
    }, 2000);
});
</script>
<?php endif ?>
