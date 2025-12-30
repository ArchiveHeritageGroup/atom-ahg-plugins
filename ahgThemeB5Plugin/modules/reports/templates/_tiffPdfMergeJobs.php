<?php
/**
 * TIFF to PDF Merge Jobs - Reports Dashboard Widget
 */
require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/TiffPdfMergeRepository.php';

use Illuminate\Database\Capsule\Manager as DB;
use AtomFramework\Repositories\TiffPdfMergeRepository;

$repository = new TiffPdfMergeRepository();
$stats = $repository->getStatistics();
$stats['queued'] = DB::table('tiff_pdf_merge_job')->where('status', 'queued')->count();

$recentJobs = DB::table('tiff_pdf_merge_job as j')
    ->leftJoin('user as u', 'j.user_id', '=', 'u.id')
    ->select([
        'j.*',
        'u.username',
        DB::raw('(SELECT COUNT(*) FROM tiff_pdf_merge_file WHERE merge_job_id = j.id) as total_files'),
    ])
    ->orderByDesc('j.created_at')
    ->limit(10)
    ->get();

$hasProcessing = DB::table('tiff_pdf_merge_job')->whereIn('status', ['queued', 'processing'])->exists();
?>

<div class="card mb-4" id="tiff-pdf-merge-section">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-layer-group me-2"></i>
            TIFF to PDF Merge Jobs
        </h5>
        <div>
            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>" class="btn btn-light btn-sm me-2">
                <i class="fas fa-list me-1"></i>View All
            </a>
            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-light btn-sm">
                <i class="fas fa-plus me-1"></i>New Merge
            </a>
        </div>
    </div>

    <div class="card-body">
        <!-- Statistics Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-sm-4 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-primary"><?php echo $stats['total_jobs']; ?></div>
                    <small class="text-muted">Total Jobs</small>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-warning"><?php echo $stats['pending']; ?></div>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-info"><?php echo $stats['queued'] + $stats['processing']; ?></div>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-success"><?php echo $stats['completed']; ?></div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-danger"><?php echo $stats['failed']; ?></div>
                    <small class="text-muted">Failed</small>
                </div>
            </div>
            <div class="col-md-2 col-sm-4 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fs-3 fw-bold text-secondary"><?php echo $stats['total_files']; ?></div>
                    <small class="text-muted">Total Files</small>
                </div>
            </div>
        </div>

        <!-- Recent Jobs Table -->
        <?php if ($recentJobs->count() > 0): ?>
        <h6 class="mb-3"><i class="fas fa-history me-2"></i>Recent Jobs</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
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
                        <td><span class="badge bg-secondary">#<?php echo $job->id; ?></span></td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'view', 'job_id' => $job->id]); ?>">
                                <i class="fas fa-file-pdf text-danger me-1"></i>
                                <?php echo htmlspecialchars($job->job_name); ?>
                            </a>
                        </td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($job->username ?? 'Unknown'); ?></small></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo $job->total_files; ?></span></td>
                        <td class="text-center">
                            <?php
                            $statusClass = match($job->status) {
                                'pending' => 'warning',
                                'queued' => 'info',
                                'processing' => 'primary',
                                'completed' => 'success',
                                'failed' => 'danger',
                                default => 'secondary'
                            };
                            $statusIcon = match($job->status) {
                                'pending' => 'clock',
                                'queued' => 'hourglass-half',
                                'processing' => 'spinner fa-spin',
                                'completed' => 'check-circle',
                                'failed' => 'times-circle',
                                default => 'question-circle'
                            };
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?>">
                                <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                <?php echo ucfirst($job->status); ?>
                            </span>
                        </td>
                        <td><small><?php echo date('M j, g:i A', strtotime($job->created_at)); ?></small></td>
                        <td class="text-end">
                            <?php if ($job->status === 'completed' && $job->output_path): ?>
                            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]); ?>" 
                               class="btn btn-sm btn-success" title="Download PDF">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php elseif ($job->status === 'pending'): ?>
                            <button type="button" class="btn btn-sm btn-primary tpm-process-btn" data-job-id="<?php echo $job->id; ?>" title="Process Now">
                                <i class="fas fa-play"></i>
                            </button>
                            <?php endif; ?>
                            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'view', 'job_id' => $job->id]); ?>" 
                               class="btn btn-sm btn-outline-secondary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3 text-center">
            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'browse']); ?>" class="btn btn-outline-primary">
                <i class="fas fa-list me-1"></i>View All Jobs
            </a>
        </div>
        <?php else: ?>
        <div class="text-center py-4 text-muted">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p class="mb-3">No PDF merge jobs yet.</p>
            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Create Your First PDF
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.querySelectorAll('.tpm-process-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const jobId = this.dataset.jobId;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        try {
            const response = await fetch('/index.php/tiff-pdf-merge/process', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'job_id=' + jobId
            });
            const result = await response.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.error);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-play"></i>';
            }
        } catch (e) {
            alert('Error processing job');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-play"></i>';
        }
    });
});

<?php if ($hasProcessing): ?>
// Auto-refresh if there are processing jobs
setTimeout(() => {
    const section = document.getElementById('tiff-pdf-merge-section');
    if (section) location.reload();
}, 5000);
<?php endif; ?>
</script>
