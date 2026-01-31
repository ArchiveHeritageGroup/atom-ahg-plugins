<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Downloads Report</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-download me-2"></i>Downloads Report</h1>
        <a href="<?php echo url_for("statistics/export?type=downloads&start={$startDate}&end={$endDate}") ?>" class="btn btn-outline-secondary">
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
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Downloads Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="downloadsChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Top Downloaded Items</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th class="text-end">Downloads</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($topDownloads, 0, 15) as $idx => $item): ?>
                                    <tr>
                                        <td><?php echo $idx + 1 ?></td>
                                        <td>
                                            <?php if ($item->slug): ?>
                                                <a href="<?php echo url_for(['slug' => $item->slug, 'module' => 'informationobject']) ?>" target="_blank">
                                                    <?php echo esc_entities(mb_substr($item->title ?? "#{$item->object_id}", 0, 35)) ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo esc_entities(mb_substr($item->title ?? "#{$item->object_id}", 0, 35)) ?>
                                            <?php endif ?>
                                        </td>
                                        <td class="text-end"><?php echo number_format($item->total) ?></td>
                                    </tr>
                                <?php endforeach ?>
                                <?php if (empty($topDownloads)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No downloads recorded</td></tr>
                                <?php endif ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const downloadData = <?php echo json_encode($data) ?>;

    if (downloadData.length > 0) {
        const ctx = document.getElementById('downloadsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: downloadData.map(d => d.period),
                datasets: [{
                    label: 'Downloads',
                    data: downloadData.map(d => d.total),
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: 'rgb(25, 135, 84)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
});
</script>
