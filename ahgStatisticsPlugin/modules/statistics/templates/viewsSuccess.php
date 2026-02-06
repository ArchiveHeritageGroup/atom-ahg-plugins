<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Views Report</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-eye me-2"></i>Views Over Time</h1>
        <a href="<?php echo url_for("statistics/export?type=views&start={$startDate}&end={$endDate}") ?>" class="btn btn-outline-secondary">
            <i class="fas fa-download me-1"></i>Export CSV
        </a>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0">Period:</label>
                </div>
                <div class="col-auto">
                    <input type="date" name="start" class="form-control form-control-sm" value="<?php echo $startDate ?>">
                </div>
                <div class="col-auto">to</div>
                <div class="col-auto">
                    <input type="date" name="end" class="form-control form-control-sm" value="<?php echo $endDate ?>">
                </div>
                <div class="col-auto">
                    <select name="group" class="form-select form-select-sm">
                        <option value="day" <?php echo $groupBy === 'day' ? 'selected' : '' ?>>Daily</option>
                        <option value="month" <?php echo $groupBy === 'month' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Views Chart</h5>
                </div>
                <div class="card-body">
                    <canvas id="viewsChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Data Table</h5>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Period</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Unique</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <td><?php echo $row->period ?></td>
                                    <td class="text-end"><?php echo number_format($row->total) ?></td>
                                    <td class="text-end"><?php echo number_format($row->unique_visitors) ?></td>
                                </tr>
                            <?php endforeach ?>
                            <?php if (empty($data)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No data</td></tr>
                            <?php endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    const viewsData = <?php echo json_encode($data) ?>;

    if (viewsData.length > 0) {
        const ctx = document.getElementById('viewsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: viewsData.map(d => d.period),
                datasets: [{
                    label: 'Total Views',
                    data: viewsData.map(d => d.total),
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3
                }, {
                    label: 'Unique Visitors',
                    data: viewsData.map(d => d.unique_visitors),
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
