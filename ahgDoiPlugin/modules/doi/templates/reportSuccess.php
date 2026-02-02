<?php use_helper('Date') ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-chart-bar me-2"></i>DOI Reports</h1>
            <p class="text-muted">Statistics and analytics for DOI minting</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'doi', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
            <div class="btn-group ms-2">
                <a href="<?php echo url_for(['module' => 'doi', 'action' => 'export', 'format' => 'csv']) ?>" class="btn btn-outline-primary">
                    <i class="fas fa-file-csv me-1"></i> Export CSV
                </a>
                <a href="<?php echo url_for(['module' => 'doi', 'action' => 'export', 'format' => 'json']) ?>" class="btn btn-outline-primary">
                    <i class="fas fa-file-code me-1"></i> Export JSON
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['total'] ?? 0 ?></h3>
                    <p class="mb-0">Total DOIs</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['findable'] ?? 0 ?></h3>
                    <p class="mb-0">Findable</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['registered'] ?? 0 ?></h3>
                    <p class="mb-0">Registered</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['by_status']['draft'] ?? 0 ?></h3>
                    <p class="mb-0">Draft</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo $stats['queue_pending'] ?? 0 ?></h3>
                    <p class="mb-0">Queue Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?php echo ($stats['by_status']['failed'] ?? 0) + ($stats['queue_failed'] ?? 0) ?></h3>
                    <p class="mb-0">Failed</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <!-- Monthly Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Monthly Minting</h5>
                </div>
                <div class="card-body">
                    <?php if ($monthlyStats->isEmpty()): ?>
                        <p class="text-muted text-center">No data available</p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">DOIs Minted</th>
                                    <th style="width: 50%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $maxCount = $monthlyStats->max('count') ?: 1;
                                foreach ($monthlyStats as $month):
                                    $percentage = ($month->count / $maxCount) * 100;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($month->month) ?></td>
                                        <td class="text-end"><?php echo $month->count ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-primary" style="width: <?php echo $percentage ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <!-- By Repository -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>DOIs by Repository</h5>
                </div>
                <div class="card-body">
                    <?php if ($byRepository->isEmpty()): ?>
                        <p class="text-muted text-center">No data available</p>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Repository</th>
                                    <th class="text-end">Count</th>
                                    <th style="width: 40%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $maxCount = $byRepository->max('count') ?: 1;
                                foreach ($byRepository as $repo):
                                    $percentage = ($repo->count / $maxCount) * 100;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($repo->repository ?? 'No repository') ?></td>
                                        <td class="text-end"><?php echo $repo->count ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" style="width: <?php echo $percentage ?>%"></div>
                                            </div>
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

    <!-- Export Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-download me-2"></i>Export Options</h5>
        </div>
        <div class="card-body">
            <form method="get" action="<?php echo url_for(['module' => 'doi', 'action' => 'export']) ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select">
                        <option value="csv">CSV</option>
                        <option value="json">JSON</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="findable">Findable</option>
                        <option value="registered">Registered</option>
                        <option value="draft">Draft</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
