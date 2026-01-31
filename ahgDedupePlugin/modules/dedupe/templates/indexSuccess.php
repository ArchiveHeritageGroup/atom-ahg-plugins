<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1><i class="fas fa-clone me-2"></i>Duplicate Detection</h1>
            <p class="text-muted">Detect, review, and merge duplicate archival records</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'scan']); ?>" class="btn btn-primary">
                <i class="fas fa-search me-1"></i> New Scan
            </a>
            <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-cog me-1"></i> Rules
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p class="mb-0">Total Detected</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p class="mb-0">Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['confirmed']; ?></h3>
                    <p class="mb-0">Confirmed</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['merged']; ?></h3>
                    <p class="mb-0">Merged</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h3><?php echo $stats['dismissed']; ?></h3>
                    <p class="mb-0">Dismissed</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h3><?php echo $activeRules; ?></h3>
                    <p class="mb-0 text-muted">Active Rules</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Duplicates -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Pending Review</h5>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse', 'status' => 'pending']); ?>" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recentDetections->isEmpty()): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>No pending duplicates to review.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Score</th>
                                    <th>Record A</th>
                                    <th>Record B</th>
                                    <th>Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentDetections as $dup): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $score = $dup->similarity_score * 100;
                                            $colorClass = $score >= 90 ? 'danger' : ($score >= 75 ? 'warning' : 'info');
                                            ?>
                                            <span class="badge bg-<?php echo $colorClass; ?>">
                                                <?php echo number_format($score, 0); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $dup->record_a_id]); ?>">
                                                <?php echo htmlspecialchars(mb_substr($dup->title_a ?? 'Untitled', 0, 40)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $dup->record_b_id]); ?>">
                                                <?php echo htmlspecialchars(mb_substr($dup->title_b ?? 'Untitled', 0, 40)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($dup->detection_method); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'compare', 'id' => $dup->id]); ?>"
                                                   class="btn btn-outline-primary" title="Compare">
                                                    <i class="fas fa-columns"></i>
                                                </a>
                                                <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'merge', 'id' => $dup->id]); ?>"
                                                   class="btn btn-outline-success" title="Merge">
                                                    <i class="fas fa-compress-arrows-alt"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-secondary btn-dismiss"
                                                        data-id="<?php echo $dup->id; ?>" title="Dismiss">
                                                    <i class="fas fa-times"></i>
                                                </button>
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

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- By Detection Method -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>By Detection Method</h5>
                </div>
                <div class="card-body">
                    <?php if ($byMethod->isEmpty()): ?>
                        <p class="text-muted mb-0">No data available.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($byMethod as $method => $count): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $method))); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Scans -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Scans</h5>
                </div>
                <div class="card-body">
                    <?php if ($recentScans->isEmpty()): ?>
                        <p class="text-muted mb-0">No scans run yet.</p>
                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'scan']); ?>" class="btn btn-primary btn-sm mt-2">
                            Run First Scan
                        </a>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentScans as $scan): ?>
                                <li class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <span>
                                            <?php
                                            $statusColors = [
                                                'completed' => 'success',
                                                'running' => 'primary',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                            ];
                                            $color = $statusColors[$scan->status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $scan->status; ?></span>
                                        </span>
                                        <small class="text-muted">
                                            <?php echo $scan->created_at ? date('M j, H:i', strtotime($scan->created_at)) : ''; ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo $scan->processed_records; ?>/<?php echo $scan->total_records; ?> records,
                                        <?php echo $scan->duplicates_found; ?> duplicates
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-list me-2"></i> Browse All Duplicates
                    </a>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'report']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar me-2"></i> Reports & Analytics
                    </a>
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'rules']); ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-cog me-2"></i> Detection Rules
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dismiss button handlers
    document.querySelectorAll('.btn-dismiss').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (confirm('Dismiss this duplicate pair as a false positive?')) {
                var id = this.dataset.id;
                fetch('<?php echo url_for(['module' => 'dedupe', 'action' => 'dismiss']); ?>/' + id, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('tr').remove();
                    }
                });
            }
        });
    });
});
</script>
