<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Repository Statistics</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1"><?php echo esc_entities($repository->name) ?></h1>
            <span class="text-muted">Repository Statistics</span>
        </div>
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
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Total Views</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_views']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Downloads</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['total_downloads']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center">
                    <h6 class="text-white-50">Unique Visitors</h6>
                    <h2 class="mb-0"><?php echo number_format($stats['unique_visitors']) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Items -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Items in Repository</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th class="text-end">Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['top_items'] as $idx => $item): ?>
                            <tr>
                                <td><?php echo $idx + 1 ?></td>
                                <td>
                                    <a href="<?php echo url_for("statistics/item/{$item->object_id}?start={$startDate}&end={$endDate}") ?>">
                                        <?php echo esc_entities($item->title ?? "Object #{$item->object_id}") ?>
                                    </a>
                                </td>
                                <td class="text-end"><?php echo number_format($item->total) ?></td>
                            </tr>
                        <?php endforeach ?>
                        <?php if (empty($stats['top_items'])): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No data for this period</td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
