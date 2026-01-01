<?php
/**
 * Security Audit Dashboard Template
 */

// Get raw data to avoid Symfony escaper issues
$stats = $sf_data->getRaw('stats');
$period = $sf_data->getRaw('period');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-line me-2"></i><?php echo __('Security Audit Dashboard') ?></h1>
    <div>
        <form method="get" class="d-inline">
            <select name="period" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                <option value="24 hours" <?php echo $period === '24 hours' ? 'selected' : '' ?>>Last 24 Hours</option>
                <option value="7 days" <?php echo $period === '7 days' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="30 days" <?php echo $period === '30 days' ? 'selected' : '' ?>>Last 30 Days</option>
                <option value="90 days" <?php echo $period === '90 days' ? 'selected' : '' ?>>Last 90 Days</option>
            </select>
        </form>
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'index']) ?>" class="btn btn-sm btn-primary ms-2">
            <i class="fas fa-list me-1"></i><?php echo __('View All Logs') ?>
        </a>
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'export']) ?>" class="btn btn-sm btn-success ms-1">
            <i class="fas fa-download me-1"></i><?php echo __('Export CSV') ?>
        </a>
    </div>
</div>

<p class="text-muted mb-4"><?php echo __('Activity since %1%', ['%1%' => date('M j, Y H:i', strtotime($stats['since']))]) ?></p>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body text-center py-4">
                <h2 class="mb-1"><?php echo number_format($stats['total_events']) ?></h2>
                <p class="mb-0 small"><?php echo __('Total Events') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body text-center py-4">
                <h2 class="mb-1"><?php echo count($stats['by_user']) ?></h2>
                <p class="mb-0 small"><?php echo __('Active Users') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body text-center py-4">
                <h2 class="mb-1"><?php echo count($stats['top_objects']) ?></h2>
                <p class="mb-0 small"><?php echo __('Objects Accessed') ?></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body text-center py-4">
                <h2 class="mb-1"><?php echo number_format($stats['security_events']) ?></h2>
                <p class="mb-0 small"><?php echo __('Security Events') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Activity by Day Chart -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i><?php echo __('Activity Over Time') ?></h5>
            </div>
            <div class="card-body">
                <canvas id="activityChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Users -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i><?php echo __('Most Active Users') ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stats['by_user'])): ?>
                <p class="text-muted text-center py-4"><?php echo __('No user activity') ?></p>
                <?php else: ?>
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('User') ?></th>
                            <th class="text-end"><?php echo __('Events') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_user'] as $user): ?>
                        <tr>
                            <td>
                                <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'index', 'user' => $user->user_name]) ?>">
                                    <i class="fas fa-user me-1 text-muted"></i>
                                    <?php echo esc_entities($user->user_name) ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-success rounded-pill"><?php echo number_format($user->count) ?></span>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top Accessed Objects -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i><?php echo __('Most Viewed Objects') ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stats['top_objects'])): ?>
                <p class="text-muted text-center py-4"><?php echo __('No object views') ?></p>
                <?php else: ?>
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Object') ?></th>
                            <th class="text-end"><?php echo __('Views') ?></th>
                            <th class="text-end"><?php echo __('Report') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_objects'] as $obj): ?>
                        <tr>
                            <td>
                                <a href="/index.php/<?php echo $obj->slug ?>" title="<?php echo esc_entities($obj->title ?: 'Untitled') ?>">
                                    <i class="fas fa-file me-1 text-muted"></i>
                                    <?php echo esc_entities(mb_substr($obj->title ?: 'Untitled', 0, 30)) ?>
                                    <?php if (mb_strlen($obj->title ?: '') > 30): ?>...<?php endif ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="badge bg-info rounded-pill"><?php echo number_format($obj->count) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'objectAccess', 'object_id' => $obj->object_id]) ?>" 
                                   class="btn btn-sm btn-outline-secondary" title="<?php echo __('View Report') ?>">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>
    </div>
    
    <!-- Actions Breakdown -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i><?php echo __('Actions Breakdown') ?></h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stats['by_action'])): ?>
                <p class="text-muted text-center py-4"><?php echo __('No actions recorded') ?></p>
                <?php else: ?>
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Action') ?></th>
                            <th class="text-end"><?php echo __('Count') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['by_action'] as $action): ?>
                        <tr>
                            <td>
                                <?php
                                $badgeClass = 'bg-secondary';
                                $icon = 'fas fa-cog';
                                if (strpos($action->action, 'create') !== false) { $badgeClass = 'bg-success'; $icon = 'fas fa-plus'; }
                                elseif (strpos($action->action, 'update') !== false) { $badgeClass = 'bg-warning text-dark'; $icon = 'fas fa-edit'; }
                                elseif (strpos($action->action, 'delete') !== false) { $badgeClass = 'bg-danger'; $icon = 'fas fa-trash'; }
                                elseif ($action->action === 'view') { $badgeClass = 'bg-info'; $icon = 'fas fa-eye'; }
                                ?>
                                <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'index', 'action' => $action->action]) ?>">
                                    <i class="<?php echo $icon ?> me-1 text-muted"></i>
                                    <?php echo esc_entities($action->action) ?>
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="badge <?php echo $badgeClass ?> rounded-pill"><?php echo number_format($action->count) ?></span>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<?php
$recentLogs = \Illuminate\Database\Capsule\Manager::table('spectrum_audit_log as sal')
    ->leftJoin('information_object_i18n as ioi', function($join) {
        $join->on('sal.object_id', '=', 'ioi.id')
            ->where('ioi.culture', '=', 'en');
    })
    ->leftJoin('slug', 'sal.object_id', '=', 'slug.object_id')
    ->select('sal.*', 'ioi.title as object_title', 'slug.slug as object_slug')
    ->orderByDesc('sal.action_date')
    ->limit(10)
    ->get();
?>
<div class="card mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i><?php echo __('Recent Activity') ?></h5>
        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'index']) ?>" class="btn btn-sm btn-light">
            <?php echo __('View All') ?> <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 150px;"><?php echo __('Date/Time') ?></th>
                    <th style="width: 120px;"><?php echo __('User') ?></th>
                    <th style="width: 100px;"><?php echo __('Action') ?></th>
                    <th><?php echo __('Object') ?></th>
                    <th style="width: 120px;"><?php echo __('IP Address') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentLogs->isEmpty()): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        <?php echo __('No recent activity') ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td class="text-nowrap">
                        <small class="text-muted">
                            <?php echo date('M j, Y', strtotime($log->action_date)) ?>
                            <br>
                            <?php echo date('H:i:s', strtotime($log->action_date)) ?>
                        </small>
                    </td>
                    <td>
                        <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'index', 'user' => $log->user_name]) ?>">
                            <?php echo esc_entities($log->user_name ?: 'System') ?>
                        </a>
                    </td>
                    <td>
                        <?php
                        $badgeClass = 'bg-secondary';
                        if (strpos($log->action, 'create') !== false) $badgeClass = 'bg-success';
                        elseif (strpos($log->action, 'update') !== false) $badgeClass = 'bg-warning text-dark';
                        elseif (strpos($log->action, 'delete') !== false) $badgeClass = 'bg-danger';
                        ?>
                        <span class="badge <?php echo $badgeClass ?>"><?php echo esc_entities($log->action) ?></span>
                    </td>
                    <td>
                        <?php if ($log->object_slug): ?>
                        <a href="/index.php/<?php echo $log->object_slug ?>">
                            <?php echo esc_entities(mb_substr($log->object_title ?: 'Untitled', 0, 35)) ?>
                            <?php if (mb_strlen($log->object_title ?: '') > 35): ?>...<?php endif ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif ?>
                    </td>
                    <td>
                        <small class="text-muted font-monospace"><?php echo $log->ip_address ?? '-' ?></small>
                    </td>
                </tr>
                <?php endforeach ?>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Links -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Quick Links') ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'index']) ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-list"></i> <?php echo __('All Audit Logs') ?>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?php echo url_for(['module' => 'arSecurityAudit', 'action' => 'export']) ?>" class="btn btn-outline-success w-100 mb-2">
                    <i class="fas fa-download"></i> <?php echo __('Export to CSV') ?>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-shield-alt"></i> <?php echo __('Security Dashboard') ?>
                </a>
            </div>
            <div class="col-md-3">
                <a href="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'report']) ?>" class="btn btn-outline-info w-100 mb-2">
                    <i class="fas fa-chart-bar"></i> <?php echo __('Security Reports') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="/plugins/ahgThemeB5Plugin/js/chart.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('activityChart');
    if (!ctx) return;
    
    <?php
    $chartLabels = [];
    $chartData = [];
    foreach ($stats['by_day'] as $day) {
        $chartLabels[] = date('M j', strtotime($day->date));
        $chartData[] = $day->count;
    }
    ?>
    
    var labels = <?php echo json_encode($chartLabels) ?>;
    var data = <?php echo json_encode($chartData) ?>;
    
    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Events',
                data: data,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
