<?php
/**
 * Security Reports Template
 */
$clearanceStats = $sf_data->getRaw('clearanceStats');
$clearancesByLevel = $sf_data->getRaw('clearancesByLevel');
$objectsByLevel = $sf_data->getRaw('objectsByLevel');
$recentActivity = $sf_data->getRaw('recentActivity');
$requestStats = $sf_data->getRaw('requestStats');
$period = $sf_data->getRaw('period');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-bar me-2"></i><?php echo __('Security Reports') ?></h1>
    <div>
        <form method="get" class="d-inline">
            <select name="period" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                <option value="7 days" <?php echo $period === '7 days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30 days" <?php echo $period === '30 days' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="90 days" <?php echo $period === '90 days' ? 'selected' : '' ?>>Last 90 Days</option>
            </select>
        </form>
        <a href="<?php echo url_for(['module' => 'securityClearance', 'action' => 'dashboard']) ?>" class="btn btn-sm btn-primary ms-2">
            <i class="fas fa-tachometer-alt me-1"></i><?php echo __('Dashboard') ?>
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center">
                <h2><?php echo $clearanceStats['total_users'] ?></h2>
                <p class="mb-0"><?php echo __('Active Users') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center">
                <h2><?php echo $clearanceStats['with_clearance'] ?></h2>
                <p class="mb-0"><?php echo __('With Clearance') ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body text-center">
                <h2><?php echo $clearanceStats['without_clearance'] ?></h2>
                <p class="mb-0"><?php echo __('Without Clearance') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Clearances by Level -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i><?php echo __('User Clearances by Level') ?></h5>
            </div>
            <div class="card-body">
                <canvas id="clearancesChart" height="200"></canvas>
                <table class="table table-sm mt-3">
                    <thead>
                        <tr><th><?php echo __('Level') ?></th><th class="text-end"><?php echo __('Users') ?></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clearancesByLevel as $level): ?>
                        <tr>
                            <td>
                                <span class="badge" style="background-color: <?php echo $level->color ?>"><?php echo esc_entities($level->name) ?></span>
                            </td>
                            <td class="text-end"><?php echo $level->count ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Objects by Level -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i><?php echo __('Classified Objects by Level') ?></h5>
            </div>
            <div class="card-body">
                <canvas id="objectsChart" height="200"></canvas>
                <table class="table table-sm mt-3">
                    <thead>
                        <tr><th><?php echo __('Level') ?></th><th class="text-end"><?php echo __('Objects') ?></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($objectsByLevel as $level): ?>
                        <tr>
                            <td>
                                <span class="badge" style="background-color: <?php echo $level->color ?>"><?php echo esc_entities($level->name) ?></span>
                            </td>
                            <td class="text-end"><?php echo $level->count ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Access Requests -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="fas fa-key me-2"></i><?php echo __('Access Requests') ?></h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h3 class="text-warning"><?php echo $requestStats['pending'] ?></h3>
                <p><?php echo __('Pending') ?></p>
            </div>
            <div class="col-md-4">
                <h3 class="text-success"><?php echo $requestStats['approved'] ?></h3>
                <p><?php echo __('Approved') ?></p>
            </div>
            <div class="col-md-4">
                <h3 class="text-danger"><?php echo $requestStats['denied'] ?></h3>
                <p><?php echo __('Denied') ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Security Activity -->
<div class="card">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent Security Activity') ?></h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Date') ?></th>
                    <th><?php echo __('User') ?></th>
                    <th><?php echo __('Action') ?></th>
                    <th><?php echo __('Object') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentActivity)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3"><?php echo __('No recent activity') ?></td></tr>
                <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                <tr>
                    <td><small><?php echo date('M j, H:i', strtotime($activity->action_date)) ?></small></td>
                    <td><?php echo esc_entities($activity->user_name) ?></td>
                    <td><span class="badge bg-secondary"><?php echo esc_entities($activity->action) ?></span></td>
                    <td><?php echo esc_entities($activity->object_title ?: '-') ?></td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<script src="/plugins/ahgCorePlugin/web/js/vendor/chart.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    <?php
    $clLabels = array_map(function($l) { return $l->name; }, $clearancesByLevel);
    $clData = array_map(function($l) { return $l->count; }, $clearancesByLevel);
    $clColors = array_map(function($l) { return $l->color; }, $clearancesByLevel);
    
    $objLabels = array_map(function($l) { return $l->name; }, $objectsByLevel);
    $objData = array_map(function($l) { return $l->count; }, $objectsByLevel);
    $objColors = array_map(function($l) { return $l->color; }, $objectsByLevel);
    ?>
    
    new Chart(document.getElementById('clearancesChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($clLabels) ?>,
            datasets: [{ data: <?php echo json_encode($clData) ?>, backgroundColor: <?php echo json_encode($clColors) ?> }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
    
    new Chart(document.getElementById('objectsChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($objLabels) ?>,
            datasets: [{ data: <?php echo json_encode($objData) ?>, backgroundColor: <?php echo json_encode($objColors) ?> }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
