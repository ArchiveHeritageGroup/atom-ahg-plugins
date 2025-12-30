<?php
/**
 * TIFF to PDF Merge Settings Section
 * For inclusion in the main settings page
 */
require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/TiffPdfMergeRepository.php';

use AtomFramework\Repositories\TiffPdfMergeRepository;

$repository = new TiffPdfMergeRepository();
$settings = $repository->getSettings();
$stats = $repository->getStatistics();
$recentJobs = $repository->getJobs([], 10);
?>

<div class="settings-section" id="tiff-pdf-merge-settings">
    <h4 class="mb-4">
        <i class="fas fa-layer-group text-primary me-2"></i>
        TIFF to PDF Merge
    </h4>

    <!-- Quick Access -->
    <div class="alert alert-primary d-flex align-items-center mb-4">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div class="flex-grow-1">
            <strong>Create PDF from Images</strong><br>
            <small>Upload multiple TIFF, JPEG, or PNG files and merge them into a single PDF/A archival document.</small>
        </div>
        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-primary ms-3">
            <i class="fas fa-plus me-1"></i> New Merge
        </a>
    </div>

    <!-- Statistics -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Statistics</h6>
        </div>
        <div class="card-body">
            <div class="row g-3 text-center">
                <div class="col-md-2 col-4">
                    <div class="border rounded p-3">
                        <div class="fs-3 fw-bold text-primary"><?php echo $stats['total_jobs']; ?></div>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="border rounded p-3">
                        <div class="fs-3 fw-bold text-success"><?php echo $stats['completed']; ?></div>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="border rounded p-3">
                        <div class="fs-3 fw-bold text-warning"><?php echo $stats['pending']; ?></div>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="border rounded p-3">
                        <div class="fs-3 fw-bold text-info"><?php echo $stats['processing']; ?></div>
                        <small class="text-muted">Processing</small>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="border rounded p-3">
                        <div class="fs-3 fw-bold text-danger"><?php echo $stats['failed']; ?></div>
                        <small class="text-muted">Failed</small>
                    </div>
                </div>
                <div class="col-md-2 col-4">
                    <div class="border rounded p-3">
                        <div class="fs-3 fw-bold text-secondary"><?php echo $stats['total_files']; ?></div>
                        <small class="text-muted">Files</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Default Settings</h6>
        </div>
        <div class="card-body">
            <form id="tiffPdfSettingsForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Default PDF Standard</label>
                    <select name="default_pdf_standard" class="form-select">
                        <option value="pdfa-1b" <?php echo ($settings['default_pdf_standard'] ?? '') === 'pdfa-1b' ? 'selected' : ''; ?>>PDF/A-1b</option>
                        <option value="pdfa-2b" <?php echo ($settings['default_pdf_standard'] ?? 'pdfa-2b') === 'pdfa-2b' ? 'selected' : ''; ?>>PDF/A-2b (Recommended)</option>
                        <option value="pdfa-3b" <?php echo ($settings['default_pdf_standard'] ?? '') === 'pdfa-3b' ? 'selected' : ''; ?>>PDF/A-3b</option>
                        <option value="pdf" <?php echo ($settings['default_pdf_standard'] ?? '') === 'pdf' ? 'selected' : ''; ?>>Standard PDF</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Default DPI</label>
                    <select name="default_dpi" class="form-select">
                        <option value="150" <?php echo ($settings['default_dpi'] ?? 300) == 150 ? 'selected' : ''; ?>>150</option>
                        <option value="300" <?php echo ($settings['default_dpi'] ?? 300) == 300 ? 'selected' : ''; ?>>300</option>
                        <option value="400" <?php echo ($settings['default_dpi'] ?? 300) == 400 ? 'selected' : ''; ?>>400</option>
                        <option value="600" <?php echo ($settings['default_dpi'] ?? 300) == 600 ? 'selected' : ''; ?>>600</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Default Quality</label>
                    <select name="default_quality" class="form-select">
                        <option value="70" <?php echo ($settings['default_quality'] ?? 85) == 70 ? 'selected' : ''; ?>>70%</option>
                        <option value="85" <?php echo ($settings['default_quality'] ?? 85) == 85 ? 'selected' : ''; ?>>85%</option>
                        <option value="95" <?php echo ($settings['default_quality'] ?? 85) == 95 ? 'selected' : ''; ?>>95%</option>
                        <option value="100" <?php echo ($settings['default_quality'] ?? 85) == 100 ? 'selected' : ''; ?>>100%</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Max Files/Job</label>
                    <input type="number" name="max_files_per_job" class="form-control" 
                           value="<?php echo $settings['max_files_per_job'] ?? 100; ?>" min="10" max="500">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Max File Size (MB)</label>
                    <input type="number" name="max_file_size_mb" class="form-control" 
                           value="<?php echo $settings['max_file_size_mb'] ?? 500; ?>" min="10" max="2000">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Temp Directory</label>
                    <input type="text" name="temp_directory" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['temp_directory'] ?? '/tmp/tiff-pdf-merge'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">ImageMagick Path</label>
                    <input type="text" name="imagemagick_path" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['imagemagick_path'] ?? '/usr/bin/convert'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ghostscript Path</label>
                    <input type="text" name="ghostscript_path" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['ghostscript_path'] ?? '/usr/bin/gs'); ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                    <span id="tiffPdfSettingsStatus" class="ms-2"></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Recent Jobs -->
    <?php if ($recentJobs->count() > 0): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Jobs</h6>
            <a href="<?php echo url_for(['module' => 'jobs', 'action' => 'browse']); ?>" class="btn btn-sm btn-outline-secondary">
                View All Jobs
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Job Name</th>
                        <th>User</th>
                        <th class="text-center">Files</th>
                        <th class="text-center">Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentJobs as $job): ?>
                    <tr>
                        <td>
                            <i class="fas fa-file-pdf text-danger me-1"></i>
                            <?php echo htmlspecialchars($job->job_name); ?>
                        </td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($job->username ?? 'Unknown'); ?></small></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo $job->total_files; ?></span></td>
                        <td class="text-center">
                            <?php
                            $cls = match($job->status) { 'pending'=>'warning', 'processing'=>'info', 'completed'=>'success', 'failed'=>'danger', default=>'secondary' };
                            $ico = match($job->status) { 'pending'=>'clock', 'processing'=>'spinner fa-spin', 'completed'=>'check-circle', 'failed'=>'times-circle', default=>'question' };
                            ?>
                            <span class="badge bg-<?php echo $cls; ?>">
                                <i class="fas fa-<?php echo $ico; ?> me-1"></i><?php echo ucfirst($job->status); ?>
                            </span>
                        </td>
                        <td><small><?php echo date('M j, Y g:i A', strtotime($job->created_at)); ?></small></td>
                        <td class="text-end">
                            <?php if ($job->status === 'completed' && $job->output_path): ?>
                            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]); ?>" 
                               class="btn btn-sm btn-success" title="Download PDF">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($job->output_digital_object_id): ?>
                            <span class="badge bg-primary" title="Attached to record"><i class="fas fa-paperclip"></i></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.getElementById('tiffPdfSettingsForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const settings = Object.fromEntries(formData.entries());
    const statusEl = document.getElementById('tiffPdfSettingsStatus');
    
    statusEl.innerHTML = '<span class="text-info"><i class="fas fa-spinner fa-spin me-1"></i>Saving...</span>';
    
    try {
        const response = await fetch('/index.php/ahgSettings/saveTiffPdfSettings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ settings })
        });
        const result = await response.json();
        
        if (result.success) {
            statusEl.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>Saved!</span>';
        } else {
            statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>' + (result.error || 'Error') + '</span>';
        }
    } catch (error) {
        statusEl.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Error saving</span>';
    }
    
    setTimeout(() => statusEl.innerHTML = '', 3000);
});
</script>
