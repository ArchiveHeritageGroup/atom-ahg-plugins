<?php
/**
 * Object Access Report
 */
$object = $sf_data->getRaw('object');
$period = $sf_data->getRaw('period');
$totalAccess = $sf_data->getRaw('totalAccess');
$accessLogs = $sf_data->getRaw('accessLogs');
$securityLogs = $sf_data->getRaw('securityLogs');
$dailyAccess = $sf_data->getRaw('dailyAccess');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="fas fa-eye me-2"></i><?php echo __('Object Access Report') ?></h1>
        <h4 class="text-muted"><?php echo esc_entities($object->title ?: 'Untitled') ?></h4>
    </div>
    <div>
        <form method="get" class="d-inline">
            <input type="hidden" name="object_id" value="<?php echo $object->id ?>">
            <select name="period" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                <option value="7 days" <?php echo $period === '7 days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30 days" <?php echo $period === '30 days' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="90 days" <?php echo $period === '90 days' ? 'selected' : '' ?>>Last 90 Days</option>
            </select>
        </form>
        <a href="/index.php/<?php echo $object->slug ?>" class="btn btn-sm btn-outline-primary ms-2">
            <i class="fas fa-external-link-alt me-1"></i><?php echo __('View Object') ?>
        </a>
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'dashboard']) ?>" class="btn btn-sm btn-primary ms-1">
            <i class="fas fa-arrow-left me-1"></i><?php echo __('Back') ?>
        </a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $totalAccess ?></h3>
                <small><?php echo __('Total Views') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3><?php echo count($securityLogs) ?></h3>
                <small><?php echo __('Security Events') ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo count($dailyAccess) ?></h3>
                <small><?php echo __('Days with Activity') ?></small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i><?php echo __('Access Over Time') ?></h5>
            </div>
            <div class="card-body">
                <canvas id="accessChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Security Events') ?></h5>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($securityLogs)): ?>
                <p class="text-muted text-center py-3"><?php echo __('No security events') ?></p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($securityLogs as $log): ?>
                    <li class="list-group-item small">
                        <strong><?php echo esc_entities($log->action) ?></strong>
                        <br><span class="text-muted"><?php echo esc_entities($log->user_name) ?> - <?php echo date('M j H:i', strtotime($log->action_date)) ?></span>
                    </li>
                    <?php endforeach ?>
                </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Access Log -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Recent Access Log') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($accessLogs)): ?>
        <p class="text-muted text-center py-3"><?php echo __('No access records found') ?></p>
        <?php else: ?>
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Date/Time') ?></th>
                    <th><?php echo __('Type') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($accessLogs, 0, 20) as $log): ?>
                <tr>
                    <td><?php echo date('M j, Y H:i:s', strtotime($log->access_date)) ?></td>
                    <td><span class="badge bg-info">View</span></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php if (count($accessLogs) > 20): ?>
        <p class="text-muted text-center py-2 small"><?php echo __('Showing 20 of %1% records', ['%1%' => count($accessLogs)]) ?></p>
        <?php endif ?>
        <?php endif ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('accessChart');
    if (!ctx) return;
    
    <?php
    $chartLabels = [];
    $chartData = [];
    foreach ($dailyAccess as $d) {
        $chartLabels[] = date('M j', strtotime($d->date));
        $chartData[] = $d->count;
    }
    ?>
    
    new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Views',
                data: <?php echo json_encode($chartData) ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
});
</script>
