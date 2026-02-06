<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'index']); ?>">Duplicate Detection</a></li>
                    <li class="breadcrumb-item active">Browse</li>
                </ol>
            </nav>
            <h1><i class="fas fa-list me-2"></i>Browse Duplicates</h1>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo 'pending' === $status ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo 'confirmed' === $status ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="dismissed" <?php echo 'dismissed' === $status ? 'selected' : ''; ?>>Dismissed</option>
                        <option value="merged" <?php echo 'merged' === $status ? 'selected' : ''; ?>>Merged</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Detection Method</label>
                    <select name="method" class="form-select">
                        <option value="">All Methods</option>
                        <?php foreach ($methods as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $m === $method ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $m))); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Score</label>
                    <select name="min_score" class="form-select">
                        <option value="0">Any</option>
                        <option value="0.5" <?php echo 0.5 == $minScore ? 'selected' : ''; ?>>50%+</option>
                        <option value="0.7" <?php echo 0.7 == $minScore ? 'selected' : ''; ?>>70%+</option>
                        <option value="0.8" <?php echo 0.8 == $minScore ? 'selected' : ''; ?>>80%+</option>
                        <option value="0.9" <?php echo 0.9 == $minScore ? 'selected' : ''; ?>>90%+</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <strong><?php echo number_format($total); ?></strong> duplicate pairs found
            </span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="selectAll">
                    <i class="fas fa-check-square me-1"></i> Select All
                </button>
                <button type="button" class="btn btn-outline-danger" id="dismissSelected" disabled>
                    <i class="fas fa-times me-1"></i> Dismiss Selected
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if ($duplicates->isEmpty()): ?>
                <div class="p-4 text-center text-muted">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <p>No duplicates found matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" class="form-check-input" id="checkAll">
                            </th>
                            <th style="width: 80px;">Score</th>
                            <th>Record A</th>
                            <th>Record B</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Detected</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($duplicates as $dup): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input row-check" data-id="<?php echo $dup->id; ?>">
                                </td>
                                <td>
                                    <?php
                                    $score = $dup->similarity_score * 100;
                                    $colorClass = $score >= 90 ? 'danger' : ($score >= 75 ? 'warning' : 'info');
                                    ?>
                                    <span class="badge bg-<?php echo $colorClass; ?> score-badge" title="Similarity Score">
                                        <?php echo number_format($score, 1); ?>%
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $dup->record_a_id]); ?>" target="_blank">
                                            <?php echo htmlspecialchars($dup->title_a ?? 'Untitled'); ?>
                                            <i class="fas fa-external-link-alt fa-xs text-muted"></i>
                                        </a>
                                    </div>
                                    <?php if ($dup->identifier_a): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($dup->identifier_a); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $dup->record_b_id]); ?>" target="_blank">
                                            <?php echo htmlspecialchars($dup->title_b ?? 'Untitled'); ?>
                                            <i class="fas fa-external-link-alt fa-xs text-muted"></i>
                                        </a>
                                    </div>
                                    <?php if ($dup->identifier_b): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($dup->identifier_b); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $dup->detection_method))); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'dismissed' => 'secondary',
                                        'merged' => 'success',
                                    ];
                                    $color = $statusColors[$dup->status] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>"><?php echo $dup->status; ?></span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo $dup->detected_at ? date('M j, Y', strtotime($dup->detected_at)) : '-'; ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'compare', 'id' => $dup->id]); ?>"
                                           class="btn btn-outline-primary" title="Compare Side-by-Side">
                                            <i class="fas fa-columns"></i>
                                        </a>
                                        <?php if ('merged' !== $dup->status): ?>
                                            <a href="<?php echo url_for(['module' => 'dedupe', 'action' => 'merge', 'id' => $dup->id]); ?>"
                                               class="btn btn-outline-success" title="Merge Records">
                                                <i class="fas fa-compress-arrows-alt"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary btn-dismiss"
                                                    data-id="<?php echo $dup->id; ?>" title="Dismiss">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav>
                    <ul class="pagination mb-0 justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse', 'page' => $page - 1, 'status' => $status, 'method' => $method, 'min_score' => $minScore]); ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); ++$i): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse', 'page' => $i, 'status' => $status, 'method' => $method, 'min_score' => $minScore]); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo url_for(['module' => 'dedupe', 'action' => 'browse', 'page' => $page + 1, 'status' => $status, 'method' => $method, 'min_score' => $minScore]); ?>">
                                    Next
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var checkAll = document.getElementById('checkAll');
    var rowChecks = document.querySelectorAll('.row-check');
    var dismissSelected = document.getElementById('dismissSelected');
    var selectAllBtn = document.getElementById('selectAll');

    function updateDismissButton() {
        var checked = document.querySelectorAll('.row-check:checked');
        dismissSelected.disabled = checked.length === 0;
    }

    checkAll.addEventListener('change', function() {
        rowChecks.forEach(function(cb) {
            cb.checked = checkAll.checked;
        });
        updateDismissButton();
    });

    rowChecks.forEach(function(cb) {
        cb.addEventListener('change', updateDismissButton);
    });

    selectAllBtn.addEventListener('click', function() {
        rowChecks.forEach(function(cb) {
            cb.checked = true;
        });
        checkAll.checked = true;
        updateDismissButton();
    });

    dismissSelected.addEventListener('click', function() {
        var checked = document.querySelectorAll('.row-check:checked');
        if (checked.length === 0) return;

        if (!confirm('Dismiss ' + checked.length + ' duplicate pair(s) as false positives?')) return;

        var ids = Array.from(checked).map(function(cb) { return cb.dataset.id; });

        ids.forEach(function(id) {
            fetch('<?php echo url_for(['module' => 'dedupe', 'action' => 'dismiss']); ?>/' + id, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
        });

        // Reload page after dismissals
        setTimeout(function() { location.reload(); }, 500);
    });

    // Individual dismiss buttons
    document.querySelectorAll('.btn-dismiss').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Dismiss this duplicate pair as a false positive?')) return;

            var id = this.dataset.id;
            var row = this.closest('tr');

            fetch('<?php echo url_for(['module' => 'dedupe', 'action' => 'dismiss']); ?>/' + id, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                }
            });
        });
    });
});
</script>
