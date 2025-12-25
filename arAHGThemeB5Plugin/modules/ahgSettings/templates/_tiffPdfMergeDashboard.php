<?php
/**
 * TIFF to PDF Merge - User Dashboard Widget
 * Include this on the main dashboard for quick access
 */
require_once '/usr/share/nginx/archive/atom-framework/bootstrap.php';
require_once '/usr/share/nginx/archive/atom-framework/src/Repositories/TiffPdfMergeRepository.php';

use AtomFramework\Repositories\TiffPdfMergeRepository;

$userId = sfContext::getInstance()->user->getAttribute('user_id');
$repository = new TiffPdfMergeRepository();
$userStats = $repository->getStatistics($userId);
$userJobs = $repository->getJobs(['user_id' => $userId], 3);
?>

<div class="card shadow-sm h-100">
    <div class="card-header bg-gradient bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-layer-group me-2"></i>
                PDF Merge Tool
            </h6>
            <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" 
               class="btn btn-sm btn-light">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Combine multiple TIFF, JPEG, or PNG images into a single PDF/A document.
        </p>
        
        <!-- Quick Stats -->
        <div class="row g-2 mb-3">
            <div class="col-4 text-center">
                <div class="fs-4 fw-bold text-primary"><?php echo $userStats['completed']; ?></div>
                <small class="text-muted">Completed</small>
            </div>
            <div class="col-4 text-center">
                <div class="fs-4 fw-bold text-warning"><?php echo $userStats['pending'] + $userStats['processing']; ?></div>
                <small class="text-muted">In Progress</small>
            </div>
            <div class="col-4 text-center">
                <div class="fs-4 fw-bold text-secondary"><?php echo $userStats['total_jobs']; ?></div>
                <small class="text-muted">Total</small>
            </div>
        </div>

        <?php if ($userJobs->count() > 0): ?>
        <div class="list-group list-group-flush small">
            <?php foreach ($userJobs as $job): ?>
            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                <div class="text-truncate" style="max-width: 150px;" title="<?php echo htmlspecialchars($job->job_name); ?>">
                    <?php echo htmlspecialchars($job->job_name); ?>
                </div>
                <div>
                    <?php if ($job->status === 'completed' && $job->output_path): ?>
                    <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'download', 'job_id' => $job->id]); ?>" 
                       class="btn btn-sm btn-success py-0 px-1" title="Download">
                        <i class="fas fa-download"></i>
                    </a>
                    <?php else: ?>
                    <span class="badge bg-<?php echo $job->status === 'pending' ? 'warning' : ($job->status === 'processing' ? 'info' : 'danger'); ?>">
                        <?php echo ucfirst($job->status); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-light">
        <a href="<?php echo url_for(['module' => 'tiffpdfmerge', 'action' => 'index']); ?>" class="btn btn-primary btn-sm w-100">
            <i class="fas fa-file-pdf me-1"></i>
            Create New PDF
        </a>
    </div>
</div>
