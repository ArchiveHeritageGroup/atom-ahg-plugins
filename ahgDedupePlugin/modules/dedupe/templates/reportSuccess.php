<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item active">Reports</li>
                </ol>
            </nav>
            <h1><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h1>
        </div>
    </div>

    <!-- Efficiency Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-primary"><?php echo number_format($efficiency['total_detected']); ?></h2>
                    <p class="text-muted mb-0">Total Detected</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-success"><?php echo number_format($efficiency['total_merged']); ?></h2>
                    <p class="text-muted mb-0">Merged</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-secondary"><?php echo number_format($efficiency['total_dismissed']); ?></h2>
                    <p class="text-muted mb-0">Dismissed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-warning"><?php echo $efficiency['false_positive_rate']; ?>%</h2>
                    <p class="text-muted mb-0">False Positive Rate</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Trend -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Monthly Detection Trend</h5>
                </div>
                <div class="card-body">
                    <?php if ($monthlyStats->isEmpty()): ?>
                        <p class="text-muted text-center">No data available yet.</p>
                    <?php else: ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Detected</th>
                                    <th>Merged</th>
                                    <th>Dismissed</th>
                                    <th>Resolution Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthlyStats as $stat): ?>
                                    <?php
                                    $resolved = $stat->merged + $stat->dismissed;
                                    $rate = $stat->total > 0 ? round(($resolved / $stat->total) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo $stat->month; ?></td>
                                        <td><?php echo $stat->total; ?></td>
                                        <td><span class="text-success"><?php echo $stat->merged; ?></span></td>
                                        <td><span class="text-secondary"><?php echo $stat->dismissed; ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-success" role="progressbar"
                                                     style="width: <?php echo $rate; ?>%">
                                                    <?php echo $rate; ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Duplicate Clusters -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clone me-2"></i>Top Duplicate Clusters</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($topClusters->isEmpty()): ?>
                        <div class="p-3 text-muted text-center">No pending duplicate clusters.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($topClusters as $cluster): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse', 'record' => $cluster->record_a_id]); ?>">
                                            <?php echo htmlspecialchars(mb_substr($cluster->title ?? 'Untitled', 0, 35)); ?>
                                        </a>
                                    </div>
                                    <span class="badge bg-warning rounded-pill" title="Duplicate pairs">
                                        <?php echo $cluster->duplicate_count; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-download me-2"></i>Export Reports</h5>
        </div>
        <div class="card-body">
            <p>Use the CLI to export detailed reports:</p>
            <div class="row">
                <div class="col-md-6">
                    <h6>CSV Export</h6>
                    <pre class="bg-light p-3 rounded">php symfony dedupe:report --format=csv --output=duplicates.csv</pre>
                </div>
                <div class="col-md-6">
                    <h6>JSON Export</h6>
                    <pre class="bg-light p-3 rounded">php symfony dedupe:report --format=json --output=duplicates.json</pre>
                </div>
            </div>

            <h6 class="mt-3">Filter Options</h6>
            <pre class="bg-light p-3 rounded">php symfony dedupe:report --status=pending --min-score=0.9 --limit=500</pre>
        </div>
    </div>
</div>
