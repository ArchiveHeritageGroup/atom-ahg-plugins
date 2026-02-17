<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'workspace']); ?>">Workspace</a></li>
        <li class="breadcrumb-item active">Saved Searches</li>
    </ol>
</nav>
<h1 class="h2 mb-4"><i class="fas fa-search text-primary me-2"></i>Saved Searches</h1>
<div class="card">
    <?php if (!empty($savedSearches)): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Citation ID</th>
                        <th>Query</th>
                        <th>Results</th>
                        <th>Alerts</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($savedSearches as $s): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($s->name); ?></strong>
                                <?php if (!empty($s->description)): ?><br><small class="text-muted"><?php echo htmlspecialchars($s->description); ?></small><?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($s->citation_id)): ?>
                                    <code class="small"><?php echo htmlspecialchars($s->citation_id); ?></code>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td><code class="small"><?php echo htmlspecialchars(mb_substr($s->search_query, 0, 40)); ?><?php echo mb_strlen($s->search_query) > 40 ? '...' : ''; ?></code></td>
                            <td>
                                <?php if (isset($s->last_result_count) && $s->last_result_count !== null): ?>
                                    <span class="badge bg-secondary"><?php echo (int) $s->last_result_count; ?> results</span>
                                <?php else: ?>
                                    <small class="text-muted">No snapshot</small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo ($s->alert_enabled ?? 0) ? '<span class="badge bg-success">On</span>' : '<span class="badge bg-secondary">Off</span>'; ?></td>
                            <td><small><?php echo date('Y-m-d', strtotime($s->created_at)); ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']) . '?sq0=' . urlencode($s->search_query); ?>" class="btn btn-outline-primary" title="Run search"><i class="fas fa-search"></i></a>
                                    <button class="btn btn-outline-info diff-btn" data-id="<?php echo (int) $s->id; ?>" data-query="<?php echo htmlspecialchars($s->search_query); ?>" title="Diff results"><i class="fas fa-exchange-alt"></i></button>
                                    <button class="btn btn-outline-success snapshot-btn" data-id="<?php echo (int) $s->id; ?>" data-query="<?php echo htmlspecialchars($s->search_query); ?>" title="Snapshot current results"><i class="fas fa-camera"></i></button>
                                    <form method="post" class="d-inline"><input type="hidden" name="booking_action" value="delete"><input type="hidden" name="id" value="<?php echo (int) $s->id; ?>"><button type="submit" class="btn btn-outline-danger" onclick="return confirm('Delete this saved search?')"><i class="fas fa-trash"></i></button></form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-body text-center text-muted"><i class="fas fa-search fa-3x mb-3"></i><p>No saved searches. Use the search feature and save searches for quick access.</p></div>
    <?php endif; ?>
</div>

<!-- Diff Results Modal -->
<div class="modal fade" id="diffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Search Result Diff</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="diffBody"><div class="text-center"><i class="fas fa-spinner fa-spin"></i> Computing diff...</div></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Diff button
    document.querySelectorAll('.diff-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var searchId = this.dataset.id;
            document.getElementById('diffBody').innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Computing diff against last snapshot...</p></div>';
            new bootstrap.Modal(document.getElementById('diffModal')).show();
            fetch('/research/search-diff/' + searchId, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({})
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.error) {
                    document.getElementById('diffBody').innerHTML = '<div class="alert alert-warning">' + d.error + '</div>';
                    return;
                }
                var html = '<div class="row mb-3">';
                html += '<div class="col-md-4"><div class="card text-center"><div class="card-body py-2"><div class="fs-5 fw-bold">' + d.previous_count + '</div><small class="text-muted">Previous</small></div></div></div>';
                html += '<div class="col-md-4"><div class="card text-center"><div class="card-body py-2"><div class="fs-5 fw-bold">' + d.current_count + '</div><small class="text-muted">Current</small></div></div></div>';
                html += '<div class="col-md-4"><div class="card text-center"><div class="card-body py-2"><div class="fs-5 fw-bold">' + d.unchanged_count + '</div><small class="text-muted">Unchanged</small></div></div></div>';
                html += '</div>';
                if (d.added.length > 0) {
                    html += '<h6 class="text-success"><i class="fas fa-plus-circle me-1"></i>Added (' + d.added.length + ')</h6><ul class="list-group mb-3">';
                    d.added.forEach(function(id) {
                        var title = d.added_titles && d.added_titles[id] ? d.added_titles[id] : 'Object #' + id;
                        html += '<li class="list-group-item list-group-item-success py-1"><small>' + title + ' (ID: ' + id + ')</small></li>';
                    });
                    html += '</ul>';
                }
                if (d.removed.length > 0) {
                    html += '<h6 class="text-danger"><i class="fas fa-minus-circle me-1"></i>Removed (' + d.removed.length + ')</h6><ul class="list-group mb-3">';
                    d.removed.forEach(function(id) {
                        var title = d.removed_titles && d.removed_titles[id] ? d.removed_titles[id] : 'Object #' + id;
                        html += '<li class="list-group-item list-group-item-danger py-1"><small>' + title + ' (ID: ' + id + ')</small></li>';
                    });
                    html += '</ul>';
                }
                if (d.added.length === 0 && d.removed.length === 0) {
                    html += '<div class="alert alert-success"><i class="fas fa-check-circle me-1"></i>No changes since last snapshot.</div>';
                }
                document.getElementById('diffBody').innerHTML = html;
            });
        });
    });

    // Snapshot button
    document.querySelectorAll('.snapshot-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Snapshot the current results for this search? This will be used as the baseline for future diffs.')) return;
            var searchId = this.dataset.id;
            fetch('/research/search-snapshot/' + searchId, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({})
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) {
                    alert('Snapshot saved (' + (d.count || 0) + ' results).');
                    location.reload();
                } else {
                    alert(d.error || 'Error saving snapshot');
                }
            });
        });
    });
});
</script>
