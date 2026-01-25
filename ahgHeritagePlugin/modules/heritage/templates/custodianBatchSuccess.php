<?php
/**
 * Heritage Custodian Batch Operations.
 */

decorate_with('layout_2col');

$jobs = $jobData['jobs'] ?? [];
$total = $jobData['total'] ?? 0;
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-layer-group me-2"></i>Batch Operations
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'batch']); ?>

<div class="mt-4">
    <label class="form-label">Filter by Status</label>
    <div class="list-group">
        <a href="?" class="list-group-item list-group-item-action <?php echo !$sf_request->getParameter('status') ? 'active' : ''; ?>">All Jobs</a>
        <a href="?status=processing" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('status') === 'processing' ? 'active' : ''; ?>">Processing</a>
        <a href="?status=completed" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('status') === 'completed' ? 'active' : ''; ?>">Completed</a>
        <a href="?status=failed" class="list-group-item list-group-item-action <?php echo $sf_request->getParameter('status') === 'failed' ? 'active' : ''; ?>">Failed</a>
    </div>
</div>
<?php end_slot(); ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Batch Jobs</h5>
        <span class="badge bg-secondary"><?php echo number_format($total); ?> jobs</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($jobs)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-inbox fs-1 mb-3 d-block"></i>
            <p>No batch jobs found.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Job</th>
                        <th>Type</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_specialchars($job->job_name ?? "Job #{$job->id}"); ?></strong>
                            <br><small class="text-muted">by <?php echo esc_specialchars($job->username ?? 'Unknown'); ?></small>
                        </td>
                        <td><?php echo esc_specialchars($job->job_type); ?></td>
                        <td>
                            <?php $progress = $job->total_items > 0 ? round(($job->processed_items / $job->total_items) * 100) : 0; ?>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" style="width: <?php echo $progress; ?>%"><?php echo $progress; ?>%</div>
                            </div>
                            <small class="text-muted"><?php echo $job->processed_items; ?>/<?php echo $job->total_items; ?> items</small>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending' => 'secondary', 'queued' => 'info', 'processing' => 'primary',
                                'completed' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary', 'paused' => 'warning'
                            ];
                            $color = $statusColors[$job->status] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($job->status); ?></span>
                            <?php if ($job->failed_items > 0): ?>
                            <br><small class="text-danger"><?php echo $job->failed_items; ?> failed</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?php echo date('M d, H:i', strtotime($job->created_at)); ?></small>
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
