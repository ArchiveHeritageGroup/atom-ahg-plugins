<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Top <?php echo ucfirst($eventType) ?>s</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-trophy me-2"></i>Top <?php echo $eventType === 'view' ? 'Viewed' : 'Downloaded' ?> Items
        </h1>
        <a href="<?php echo url_for("statistics/export?type=top_items&start={$startDate}&end={$endDate}") ?>" class="btn btn-outline-secondary">
            <i class="fas fa-download me-1"></i>Export CSV
        </a>
    </div>

    <!-- Date Filter -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row g-3 align-items-center">
                <input type="hidden" name="type" value="<?php echo $eventType ?>">
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
                    <select name="limit" class="form-select form-select-sm">
                        <option value="25" <?php echo $limit == 25 ? 'selected' : '' ?>>Top 25</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : '' ?>>Top 50</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : '' ?>>Top 100</option>
                        <option value="500" <?php echo $limit == 500 ? 'selected' : '' ?>>Top 500</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toggle View/Download -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $eventType === 'view' ? 'active' : '' ?>" href="<?php echo url_for("statistics/topItems?type=view&start={$startDate}&end={$endDate}&limit={$limit}") ?>">
                <i class="fas fa-eye me-1"></i>Views
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $eventType === 'download' ? 'active' : '' ?>" href="<?php echo url_for("statistics/topItems?type=download&start={$startDate}&end={$endDate}&limit={$limit}") ?>">
                <i class="fas fa-download me-1"></i>Downloads
            </a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px">Rank</th>
                            <th>Title</th>
                            <th class="text-end" style="width: 120px">Total</th>
                            <th class="text-end" style="width: 120px">Unique</th>
                            <th style="width: 100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $idx + 1 ?></span></td>
                                <td>
                                    <?php if ($item->slug): ?>
                                        <a href="<?php echo url_for(['slug' => $item->slug, 'module' => 'informationobject']) ?>" target="_blank">
                                            <?php echo esc_entities($item->title ?? "Object #{$item->object_id}") ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_entities($item->title ?? "Object #{$item->object_id}") ?>
                                    <?php endif ?>
                                </td>
                                <td class="text-end"><strong><?php echo number_format($item->total) ?></strong></td>
                                <td class="text-end"><?php echo number_format($item->unique_visitors) ?></td>
                                <td>
                                    <a href="<?php echo url_for("statistics/item/{$item->object_id}?start={$startDate}&end={$endDate}") ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php if (empty($items)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No data for this period</td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
