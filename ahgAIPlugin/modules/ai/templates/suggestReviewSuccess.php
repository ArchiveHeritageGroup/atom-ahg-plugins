<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-magic me-2"></i>AI Description Suggestions</h1>
        <div>
            <a href="/ai/llm/health" class="btn btn-outline-secondary btn-sm me-2" target="_blank">
                <i class="bi bi-heart-pulse me-1"></i>LLM Health
            </a>
            <a href="/ai/ner/review" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-tags me-1"></i>NER Review
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h2 class="display-5"><?php echo $stats['pending'] ?? 0; ?></h2>
                    <p class="mb-0">Pending Review</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h2 class="display-5"><?php echo ($stats['approved'] ?? 0) + ($stats['edited'] ?? 0); ?></h2>
                    <p class="mb-0">Approved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h2 class="display-5"><?php echo $stats['rejected'] ?? 0; ?></h2>
                    <p class="mb-0">Rejected</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h2 class="display-5"><?php echo number_format($stats['total_tokens'] ?? 0); ?></h2>
                    <p class="mb-0">Total Tokens</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="get" class="row align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 me-2">Repository:</label>
                </div>
                <div class="col-md-4">
                    <select name="repository" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Repositories</option>
                        <?php foreach ($repositories as $repo): ?>
                        <option value="<?php echo $repo->id; ?>" <?php echo $selectedRepository == $repo->id ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($repo->name); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Pending Suggestions Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Pending Suggestions</h5>
            <span class="badge bg-warning text-dark"><?php echo count($pendingSuggestions); ?> pending</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Record</th>
                        <th>Template</th>
                        <th class="text-center">Tokens</th>
                        <th class="text-center">Model</th>
                        <th>Created</th>
                        <th style="width: 150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingSuggestions)): ?>
                        <?php foreach ($pendingSuggestions as $suggestion): ?>
                        <tr id="row-<?php echo $suggestion->id; ?>">
                            <td>
                                <a href="/<?php echo $suggestion->slug; ?>" target="_blank">
                                    <?php echo htmlspecialchars($suggestion->title ?? 'Untitled'); ?>
                                </a>
                                <br><small class="text-muted"><?php echo htmlspecialchars($suggestion->identifier ?? ''); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($suggestion->template_name ?? 'Default'); ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?php echo number_format($suggestion->tokens_used); ?></span>
                            </td>
                            <td class="text-center">
                                <small class="text-muted"><?php echo htmlspecialchars($suggestion->model_used ?? '-'); ?></small>
                            </td>
                            <td>
                                <small><?php echo date('Y-m-d H:i', strtotime($suggestion->created_at)); ?></small>
                                <?php if ($suggestion->expires_at): ?>
                                <br><small class="text-muted">Expires: <?php echo date('Y-m-d', strtotime($suggestion->expires_at)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="reviewSuggestion(<?php echo $suggestion->id; ?>, <?php echo $suggestion->object_id; ?>)">
                                    <i class="bi bi-eye me-1"></i>Review
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-check-circle display-4 d-block mb-2"></i>
                            No pending suggestions to review
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-magic me-2"></i>Review AI Suggestion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reviewModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border"></div>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer" id="reviewModalFooter" style="display: none;">
                <div class="me-auto">
                    <small class="text-muted" id="reviewStats"></small>
                </div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-danger" id="rejectBtn">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
                <button type="button" class="btn btn-success" id="approveBtn">
                    <i class="bi bi-check-circle me-1"></i>Approve & Save
                </button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
var currentReview = null;

function reviewSuggestion(suggestionId, objectId) {
    currentReview = { suggestionId: suggestionId, objectId: objectId };

    document.getElementById('reviewModalBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border"></div><p class="mt-2">Loading...</p></div>';
    document.getElementById('reviewModalFooter').style.display = 'none';

    var modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();

    fetch('/ai/suggest/' + suggestionId + '/view')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                document.getElementById('reviewModalBody').innerHTML = '<div class="alert alert-danger">' + (data.error || 'Failed to load') + '</div>';
                return;
            }

            var s = data.suggestion;
            currentReview.suggestion = s;

            var html = '<div class="row">';

            // Existing text
            html += '<div class="col-md-6">';
            html += '<div class="card h-100">';
            html += '<div class="card-header"><i class="bi bi-file-text me-1"></i>Current Description</div>';
            html += '<div class="card-body" style="max-height: 400px; overflow-y: auto;">';
            if (s.existing_text && s.existing_text.trim()) {
                html += '<pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">' + escapeHtml(s.existing_text) + '</pre>';
            } else {
                html += '<em class="text-muted">No existing description</em>';
            }
            html += '</div></div></div>';

            // Suggested text (editable)
            html += '<div class="col-md-6">';
            html += '<div class="card h-100 border-primary">';
            html += '<div class="card-header bg-primary text-white"><i class="bi bi-magic me-1"></i>AI Suggestion';
            html += '<span class="badge bg-light text-primary float-end">' + (s.model_used || 'AI') + '</span></div>';
            html += '<div class="card-body">';
            html += '<textarea id="editSuggestedText" class="form-control" rows="15" style="font-size: 0.9rem;">' + escapeHtml(s.suggested_text) + '</textarea>';
            html += '<small class="text-muted mt-1 d-block"><i class="bi bi-pencil me-1"></i>Edit before approving</small>';
            html += '</div></div></div>';

            html += '</div>';

            // Notes
            html += '<div class="mt-3">';
            html += '<label class="form-label small">Review Notes (optional)</label>';
            html += '<textarea id="editReviewNotes" class="form-control" rows="2" placeholder="Add notes..."></textarea>';
            html += '</div>';

            // Object info
            html += '<div class="mt-3 alert alert-secondary py-2">';
            html += '<strong>Record:</strong> <a href="/' + data.object_slug + '" target="_blank">' + escapeHtml(data.object_title) + '</a>';
            html += '</div>';

            document.getElementById('reviewModalBody').innerHTML = html;
            document.getElementById('reviewModalFooter').style.display = 'flex';

            // Stats
            var stats = [];
            if (s.tokens_used) stats.push(s.tokens_used + ' tokens');
            if (s.generation_time_ms) stats.push((s.generation_time_ms / 1000).toFixed(1) + 's');
            document.getElementById('reviewStats').textContent = stats.join(' | ');

            // Bind buttons
            document.getElementById('approveBtn').onclick = function() {
                approveFromModal(suggestionId);
            };
            document.getElementById('rejectBtn').onclick = function() {
                rejectFromModal(suggestionId);
            };
        })
        .catch(function(err) {
            document.getElementById('reviewModalBody').innerHTML = '<div class="alert alert-danger">Error: ' + err.message + '</div>';
        });
}

function approveFromModal(suggestionId) {
    var editedText = document.getElementById('editSuggestedText').value.trim();
    var notes = document.getElementById('editReviewNotes').value.trim();
    var wasEdited = editedText !== currentReview.suggestion.suggested_text;

    var btn = document.getElementById('approveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    fetch('/ai/suggest/' + suggestionId + '/decision', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            decision: 'approve',
            edited_text: wasEdited ? editedText : null,
            notes: notes || null
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert('Error: ' + (data.error || 'Failed'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Approve & Save';
            return;
        }

        // Remove row and close modal
        var row = document.getElementById('row-' + suggestionId);
        if (row) row.remove();

        bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
        showToast('Approved and saved', 'success');

        // Update pending count
        updatePendingCount(-1);
    })
    .catch(function(err) {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Approve & Save';
    });
}

function rejectFromModal(suggestionId) {
    var notes = document.getElementById('editReviewNotes').value.trim();

    if (!confirm('Reject this suggestion?')) return;

    var btn = document.getElementById('rejectBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>...';

    fetch('/ai/suggest/' + suggestionId + '/decision', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            decision: 'reject',
            notes: notes || null
        })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert('Error: ' + (data.error || 'Failed'));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Reject';
            return;
        }

        var row = document.getElementById('row-' + suggestionId);
        if (row) row.remove();

        bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
        showToast('Rejected', 'warning');

        updatePendingCount(-1);
    })
    .catch(function(err) {
        alert('Error: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Reject';
    });
}

function updatePendingCount(delta) {
    var badges = document.querySelectorAll('.bg-warning .display-5, .badge.bg-warning');
    badges.forEach(function(badge) {
        var current = parseInt(badge.textContent) || 0;
        badge.textContent = Math.max(0, current + delta);
    });
}

function escapeHtml(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showToast(message, type) {
    type = type || 'info';
    var toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = '<div class="toast show bg-' + type + ' text-white" role="alert">' +
        '<div class="toast-body"><i class="bi bi-' + (type === 'success' ? 'check-circle' : 'info-circle') + ' me-2"></i>' + message + '</div></div>';
    document.body.appendChild(toast);
    setTimeout(function() { toast.remove(); }, 3000);
}
</script>
