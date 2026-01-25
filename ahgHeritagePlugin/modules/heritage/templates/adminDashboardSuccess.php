<?php
/**
 * Heritage Admin Dashboard.
 */

decorate_with('layout_2col');
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-tachometer-alt me-2"></i>Heritage Admin Dashboard
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<div class="list-group mb-4">
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminDashboard']); ?>" class="list-group-item list-group-item-action active">
        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminConfig']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-sliders-h me-2"></i>Landing Config
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminFeatures']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-toggle-on me-2"></i>Feature Toggles
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminBranding']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-palette me-2"></i>Branding
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminUsers']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-users me-2"></i>Users
    </a>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold">Access Control</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminAccessRequests']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-key me-2"></i>Access Requests
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminEmbargoes']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-lock me-2"></i>Embargoes
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminPopia']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-shield-alt-exclamation me-2"></i>POPIA Flags
    </a>
</div>

<div class="list-group mb-4">
    <span class="list-group-item bg-light fw-bold">Custodian Tools</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianDashboard']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-tools me-2"></i>Custodian Dashboard
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianBatch']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-layer-group me-2"></i>Batch Operations
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianHistory']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-clock-history me-2"></i>Audit Trail
    </a>
</div>

<div class="list-group">
    <span class="list-group-item bg-light fw-bold">Analytics</span>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsDashboard']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsSearch']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-search me-2"></i>Search Insights
    </a>
    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsAlerts']); ?>" class="list-group-item list-group-item-action">
        <i class="fas fa-bell me-2"></i>Alerts
    </a>
</div>
<?php end_slot(); ?>

<!-- Quick Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-users fs-4 text-primary"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 text-muted">Total Users</h6>
                        <h3 class="mb-0"><?php echo number_format($userStats['total_users'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-user-check fs-4 text-success"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 text-muted">Active Users</h6>
                        <h3 class="mb-0"><?php echo number_format($userStats['active_users'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-user-plus fs-4 text-info"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 text-muted">New This Month</h6>
                        <h3 class="mb-0"><?php echo number_format($userStats['recent_signups'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <?php $alertCountsArray = is_array($alertCounts) ? $alertCounts : iterator_to_array($alertCounts); ?>
                        <?php $criticalCount = ($alertCountsArray['critical'] ?? 0) + ($alertCountsArray['warning'] ?? 0); ?>
                        <div class="bg-<?php echo $criticalCount > 0 ? 'danger' : 'secondary'; ?> bg-opacity-10 rounded-3 p-3">
                            <i class="fas fa-bell fs-4 text-<?php echo $criticalCount > 0 ? 'danger' : 'secondary'; ?>"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0 text-muted">Active Alerts</h6>
                        <h3 class="mb-0"><?php echo array_sum($alertCountsArray); ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Links -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-lightning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'adminAccessRequests']); ?>" class="btn btn-outline-primary text-start">
                        <i class="fas fa-key me-2"></i>Review Access Requests
                    </a>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'custodianBatch']); ?>" class="btn btn-outline-primary text-start">
                        <i class="fas fa-layer-group me-2"></i>Create Batch Operation
                    </a>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'analyticsAlerts']); ?>" class="btn btn-outline-primary text-start">
                        <i class="fas fa-bell me-2"></i>View System Alerts
                    </a>
                    <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>" class="btn btn-outline-secondary text-start" target="_blank">
                        <i class="fas fa-eye me-2"></i>Preview Landing Page
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Trust Level Distribution</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($userStats['trust_distribution'])): ?>
                <?php foreach ($userStats['trust_distribution'] as $trust): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?php echo esc_specialchars($trust->name ?? 'Unknown'); ?></span>
                    <span class="badge bg-secondary"><?php echo $trust->count ?? 0; ?></span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted mb-0">No trust level assignments yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
