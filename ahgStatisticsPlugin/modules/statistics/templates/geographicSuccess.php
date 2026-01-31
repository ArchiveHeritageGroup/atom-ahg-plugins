<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('statistics/dashboard') ?>">Statistics</a></li>
            <li class="breadcrumb-item active">Geographic Distribution</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-globe me-2"></i>Geographic Distribution</h1>
        <a href="<?php echo url_for("statistics/export?type=geographic&start={$startDate}&end={$endDate}") ?>" class="btn btn-outline-secondary">
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

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px">#</th>
                            <th style="width: 80px">Code</th>
                            <th>Country</th>
                            <th class="text-end" style="width: 150px">Total Requests</th>
                            <th class="text-end" style="width: 150px">Unique Visitors</th>
                            <th style="width: 200px">Distribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $maxTotal = !empty($data) ? max(array_column($data, 'total')) : 1;
                        foreach ($data as $idx => $row):
                            $percent = round(($row->total / $maxTotal) * 100);
                        ?>
                            <tr>
                                <td><?php echo $idx + 1 ?></td>
                                <td><span class="badge bg-secondary"><?php echo esc_entities($row->country_code ?? '-') ?></span></td>
                                <td><?php echo esc_entities($row->country_name ?? 'Unknown') ?></td>
                                <td class="text-end"><strong><?php echo number_format($row->total) ?></strong></td>
                                <td class="text-end"><?php echo number_format($row->unique_visitors) ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" style="width: <?php echo $percent ?>%"><?php echo $percent ?>%</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <?php if (empty($data)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No geographic data for this period</td></tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
