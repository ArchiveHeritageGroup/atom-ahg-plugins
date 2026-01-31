<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Item Statistics</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?php echo esc_entities($object->title ?? "Object #{$object->id}") ?></h1>
            <?php if ($object->identifier): ?>
                <span class="text-muted"><?php echo esc_entities($object->identifier) ?></span>
            <?php endif ?>
        </div>
        <a href="<?php echo url_for(['slug' => $object->slug, 'module' => 'informationobject']) ?>" class="btn btn-outline-primary" target="_blank">
            <i class="fas fa-external-link-alt me-1"></i>View Record
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

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Views</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_views']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Unique Viewers</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['unique_views']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Downloads</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_downloads']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Countries</h6>
                    <h2 class="mb-0"><?php echo count($stats['top_countries']) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Views Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Views Over Time</h5>
                </div>
                <div class="card-body">
                    <canvas id="viewsChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Countries -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Top Countries</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($stats['top_countries'] as $country): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo esc_entities($country->country_name ?? $country->country_code ?? 'Unknown') ?></span>
                                <span class="badge bg-primary rounded-pill"><?php echo number_format($country->count) ?></span>
                            </div>
                        <?php endforeach ?>
                        <?php if (empty($stats['top_countries'])): ?>
                            <div class="list-group-item text-muted text-center">No geographic data</div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const viewsData = <?php echo json_encode($stats['views_by_day']) ?>;

    if (viewsData.length > 0) {
        const ctx = document.getElementById('viewsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: viewsData.map(d => d.date),
                datasets: [{
                    label: 'Views',
                    data: viewsData.map(d => d.count),
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgb(13, 110, 253)',
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
