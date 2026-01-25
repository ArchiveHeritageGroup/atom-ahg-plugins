<?php
/**
 * Heritage Custodian Dashboard.
 */

decorate_with('layout_2col');

$recentActivity = $dashboardData['recent_activity'] ?? [];
$batchStats = $dashboardData['batch_stats'] ?? [];
$activitySummary = $dashboardData['activity_summary'] ?? [];
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-tools me-2"></i>Custodian Dashboard
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<?php include_partial('heritage/adminSidebar', ['active' => 'custodian']); ?>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Batch Jobs</h6>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
            <span>Running</span>
            <span class="badge bg-info"><?php echo $batchStats['running'] ?? 0; ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
            <span>Completed Today</span>
            <span class="badge bg-success"><?php echo $batchStats['completed_today'] ?? 0; ?></span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Items This Month</span>
            <span class="badge bg-secondary"><?php echo number_format($batchStats['items_this_month'] ?? 0); ?></span>
        </div>
    </div>
</div>
<?php end_slot(); ?>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianBatch']); ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-layer-group fs-1 text-primary mb-2"></i>
                <h6>Batch Operations</h6>
                <small class="text-muted">Bulk update items</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianHistory']); ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-clock-history fs-1 text-info mb-2"></i>
                <h6>Audit Trail</h6>
                <small class="text-muted">View change history</small>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminAccessRequests']); ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-key fs-1 text-warning mb-2"></i>
                <h6>Access Requests</h6>
                <small class="text-muted">Review pending requests</small>
            </div>
        </a>
    </div>
</div>

<!-- Activity by Category -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Activity Summary (30 Days)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($activitySummary['by_category'])): ?>
                <?php foreach ($activitySummary['by_category'] as $category => $count): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-capitalize"><?php echo str_replace('_', ' ', $category); ?></span>
                    <span class="badge bg-secondary"><?php echo number_format($count); ?></span>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <strong>Total Actions</strong>
                    <span class="badge bg-primary"><?php echo number_format($activitySummary['total'] ?? 0); ?></span>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No activity recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Top Contributors</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($activitySummary['top_users'])): ?>
                <?php foreach ($activitySummary['top_users'] as $idx => $user): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>
                        <span class="badge bg-light text-dark me-2"><?php echo $idx + 1; ?></span>
                        <?php echo esc_specialchars($user->username ?? 'Unknown'); ?>
                    </span>
                    <span class="badge bg-secondary"><?php echo number_format($user->count); ?> actions</span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted text-center">No activity recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Activity</h5>
        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianHistory']); ?>" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentActivity)): ?>
        <div class="text-center text-muted py-4">No recent activity.</div>
        <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($recentActivity as $log): ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($log->created_at)); ?></small>
                        <span class="mx-2">|</span>
                        <strong><?php echo esc_specialchars($log->user_name ?? 'System'); ?></strong>
                        <span class="badge bg-light text-dark ms-2"><?php echo $log->action; ?></span>
                    </div>
                    <?php if ($log->object_title): ?>
                    <small class="text-truncate" style="max-width: 200px;"><?php echo esc_specialchars($log->object_title); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
