<?php
$title = 'Job Details: ' . htmlspecialchars($job->job_name);
slot('title', $title);
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Back to Jobs
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-pdf text-danger me-2"></i>
                        <?php echo htmlspecialchars($job->job_name); ?>
                    </h5>
                    <?php
                    $statusClass = match($job->status) {
                        'pending' => 'warning',
                        'queued' => 'info',
                        'processing' => 'primary',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'secondary'
                    };
                    ?>
                    <span class="badge bg-<?php echo $statusClass; ?> fs-6">
                        <?php echo ucfirst($job->status); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Job ID:</strong> #<?php echo $job->id; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>User:</strong> <?php echo htmlspecialchars($job->username ?? 'Unknown'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>PDF Standard:</strong> <?php echo strtoupper($job->pdf_standard ?? 'PDF/A-2b'); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>DPI:</strong> <?php echo $job->dpi ?? 300; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Quality:</strong> <?php echo $job->compression_quality ?? 85; ?>%
                        </div>
                        <div class="col-md-6">
                            <strong>Total Files:</strong> <?php echo $job->total_files; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($job->created_at)); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Completed:</strong> 
                            <?php echo $job->completed_at ? date('M j, Y g:i A', strtotime($job->completed_at)) : '-'; ?>
                        </div>
                    </div>

                    <?php if ($job->information_object_id): ?>
                    <hr>
                    <div>
                        <strong><i class="fas fa-link me-1"></i>Linked Record:</strong>
                        <?php if ($linkedRecord): ?>
                        <a href="<?php echo url_for([$linkedRecord, 'module' => 'informationobject']); ?>">
                            <?php echo htmlspecialchars($linkedRecordTitle ?? 'Record #' . $job->information_object_id); ?>
                        </a>
                        <?php else: ?>
                        Record #<?php echo $job->information_object_id; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($job->status === 'failed' && $job->error_message): ?>
                    <hr>
                    <div class="alert alert-danger mb-0">
                        <strong><i class="fas fa-exclamation-triangle me-1"></i>Error:</strong><br>
                        <?php echo htmlspecialchars($job->error_message); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($job->notes): ?>
                    <hr>
                    <div>
                        <strong>Processing Log:</strong>
                        <pre class="bg-light p-2 mt-2 small" style="max-height: 200px; overflow-y: auto;"><?php echo htmlspecialchars($job->notes); ?></pre>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($job->status === 'completed' && $job->output_path): ?>
                <div class="card-footer">
                    <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]); ?>" 
                       class="btn btn-success btn-lg">
                        <i class="fas fa-download me-1"></i>
                        Download PDF
                        <small>(<?php echo $job->output_filename; ?>)</small>
                    </a>
                </div>
                <?php elseif ($job->status === 'pending'): ?>
                <div class="card-footer">
                    <button type="button" class="btn btn-primary btn-lg" id="processBtn">
                        <i class="fas fa-play me-1"></i>Process Now
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Files -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-images me-2"></i>Source Files (<?php echo $files->count(); ?>)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Filename</th>
                                <th>Size</th>
                                <th>Dimensions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr>
                                <td><?php echo $file->page_order + 1; ?></td>
                                <td>
                                    <i class="fas fa-file-image text-secondary me-1"></i>
                                    <?php echo htmlspecialchars($file->original_filename); ?>
                                </td>
                                <td><?php echo number_format($file->file_size / 1024, 1); ?> KB</td>
                                <td><?php echo $file->width && $file->height ? $file->width . '×' . $file->height : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $file->status === 'processed' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($file->status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($job->status === 'completed' && $job->output_path): ?>
                        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]); ?>" 
                           class="btn btn-success">
                            <i class="fas fa-download me-1"></i>Download PDF
                        </a>
                        <?php endif; ?>
                        
                        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>New Merge Job
                        </a>
                        
                        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i>All Jobs
                        </a>
                        
                        <?php if (in_array($job->status, ['pending', 'completed', 'failed'])): ?>
                        <button type="button" class="btn btn-outline-danger" id="deleteBtn">
                            <i class="fas fa-trash me-1"></i>Delete Job
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Output Info -->
            <?php if ($job->status === 'completed' && $job->output_path): ?>
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-file-pdf me-2"></i>Output</h6>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Filename:</strong></p>
                    <p class="text-muted small"><?php echo htmlspecialchars($job->output_filename); ?></p>
                    
                    <p class="mb-1"><strong>Path:</strong></p>
                    <p class="text-muted small text-break"><?php echo htmlspecialchars($job->output_path); ?></p>
                    
                    <?php if (file_exists($job->output_path)): ?>
                    <p class="mb-1"><strong>File Size:</strong></p>
                    <p class="text-muted"><?php echo number_format(filesize($job->output_path) / 1024, 1); ?> KB</p>
                    <?php endif; ?>
                    
                    <?php if ($job->output_digital_object_id): ?>
                    <p class="mb-1"><strong>Digital Object ID:</strong></p>
                    <p class="text-muted">#<?php echo $job->output_digital_object_id; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
document.getElementById('processBtn')?.addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';
    
    try {
        const response = await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'process']); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'job_id=<?php echo $job->id; ?>'
        });
        const result = await response.json();
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.error);
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-play me-1"></i>Process Now';
        }
    } catch (e) {
        alert('Error processing job');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-play me-1"></i>Process Now';
    }
});

document.getElementById('deleteBtn')?.addEventListener('click', async function() {
    if (!confirm('Delete this job and all its files?')) return;
    
    try {
        const response = await fetch('<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'delete']); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'job_id=<?php echo $job->id; ?>'
        });
        const result = await response.json();
        if (result.success) {
            window.location.href = '<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>';
        } else {
            alert('Error: ' + result.error);
        }
    } catch (e) {
        alert('Error deleting job');
    }
});

// Auto-refresh if processing
<?php if (in_array($job->status, ['queued', 'processing'])): ?>
setTimeout(() => location.reload(), 3000);
<?php endif; ?>
</script>
