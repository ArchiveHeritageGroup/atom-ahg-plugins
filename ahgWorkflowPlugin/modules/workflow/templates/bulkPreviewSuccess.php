<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-layer-group me-2"></i>Bulk Operations</h1>
        <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>

    <div class="row g-4">
        <!-- Left: Task Selection -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Select Tasks</strong>
                    <span class="badge bg-primary" id="selected-count">0 selected</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th><input type="checkbox" id="select-all-bulk"></th>
                                    <th>ID</th>
                                    <th>Object</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assigned</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-task-list">
                                <tr><td colspan="6" class="text-center text-muted py-3">Use the search below to load tasks</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <form id="bulk-search-form" class="row g-2 align-items-end">
                        <div class="col-auto">
                            <select id="bulk-queue-filter" class="form-select form-select-sm">
                                <option value="">All Queues</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <select id="bulk-status-filter" class="form-select form-select-sm">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="claimed">Claimed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="returned">Returned</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" id="load-tasks-btn" class="btn btn-sm btn-primary">
                                <i class="fas fa-search me-1"></i>Load Tasks
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Action Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><strong>Bulk Action</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Action</label>
                        <select id="bulk-action-select" class="form-select">
                            <option value="">Select action...</option>
                            <option value="approve">Approve</option>
                            <option value="reject">Reject</option>
                            <option value="return">Return</option>
                            <option value="cancel">Cancel</option>
                            <option value="assign">Reassign</option>
                            <option value="priority">Change Priority</option>
                            <option value="note">Add Note</option>
                            <option value="move_queue">Move to Queue</option>
                        </select>
                    </div>

                    <!-- Conditional fields -->
                    <div id="assign-field" class="mb-3 d-none">
                        <label class="form-label">Assign To</label>
                        <select id="assign-user-select" class="form-select form-select-sm">
                            <option value="">Select user...</option>
                        </select>
                    </div>

                    <div id="priority-field" class="mb-3 d-none">
                        <label class="form-label">New Priority</label>
                        <select id="priority-select" class="form-select form-select-sm">
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="normal" selected>Normal</option>
                            <option value="low">Low</option>
                        </select>
                    </div>

                    <div id="queue-field" class="mb-3 d-none">
                        <label class="form-label">Target Queue</label>
                        <select id="queue-select" class="form-select form-select-sm">
                            <option value="">Select queue...</option>
                        </select>
                    </div>

                    <div id="comment-field" class="mb-3">
                        <label class="form-label">Comment <small class="text-muted">(optional)</small></label>
                        <textarea id="bulk-comment" class="form-control form-control-sm" rows="2"></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="button" id="preview-btn" class="btn btn-warning" disabled>
                            <i class="fas fa-eye me-1"></i>Preview (Dry Run)
                        </button>
                        <button type="button" id="execute-btn" class="btn btn-danger" disabled>
                            <i class="fas fa-play me-1"></i>Execute
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Results -->
            <div id="preview-results" class="card mt-3 d-none">
                <div class="card-header"><strong>Preview Results</strong></div>
                <div class="card-body p-0">
                    <div id="preview-body"></div>
                </div>
            </div>

            <!-- Execution Results -->
            <div id="execute-results" class="card mt-3 d-none">
                <div class="card-header"><strong>Execution Results</strong></div>
                <div class="card-body">
                    <div id="execute-body"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>'.replace('/dashboard', '');
    let selectedTaskIds = new Set();

    // Toggle conditional fields based on action
    document.getElementById('bulk-action-select').addEventListener('change', function() {
        const action = this.value;
        document.getElementById('assign-field').classList.toggle('d-none', action !== 'assign');
        document.getElementById('priority-field').classList.toggle('d-none', action !== 'priority');
        document.getElementById('queue-field').classList.toggle('d-none', action !== 'move_queue');
        document.getElementById('preview-btn').disabled = !action || selectedTaskIds.size === 0;
    });

    // Select all checkbox
    document.getElementById('select-all-bulk').addEventListener('change', function() {
        const checked = this.checked;
        document.querySelectorAll('.bulk-task-cb').forEach(function(cb) {
            cb.checked = checked;
            const id = parseInt(cb.value);
            if (checked) { selectedTaskIds.add(id); } else { selectedTaskIds.delete(id); }
        });
        updateCount();
    });

    // Task checkbox delegation
    document.getElementById('bulk-task-list').addEventListener('change', function(e) {
        if (e.target.classList.contains('bulk-task-cb')) {
            const id = parseInt(e.target.value);
            if (e.target.checked) { selectedTaskIds.add(id); } else { selectedTaskIds.delete(id); }
            updateCount();
        }
    });

    function updateCount() {
        document.getElementById('selected-count').textContent = selectedTaskIds.size + ' selected';
        const action = document.getElementById('bulk-action-select').value;
        document.getElementById('preview-btn').disabled = !action || selectedTaskIds.size === 0;
        document.getElementById('execute-btn').disabled = true;
    }

    // Load tasks
    document.getElementById('load-tasks-btn').addEventListener('click', function() {
        const queue = document.getElementById('bulk-queue-filter').value;
        const status = document.getElementById('bulk-status-filter').value;
        let url = baseUrl + '/api/tasks?limit=100';
        if (queue) url += '&queue_id=' + queue;
        if (status) url += '&status=' + status;

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                const tasks = data.tasks || [];
                const tbody = document.getElementById('bulk-task-list');
                if (tasks.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No tasks found</td></tr>';
                    return;
                }
                let html = '';
                tasks.forEach(function(t) {
                    const checked = selectedTaskIds.has(t.id) ? ' checked' : '';
                    html += '<tr>' +
                        '<td><input type="checkbox" class="bulk-task-cb" value="' + t.id + '"' + checked + '></td>' +
                        '<td>' + t.id + '</td>' +
                        '<td>' + (t.object_title || 'Task #' + t.id) + '</td>' +
                        '<td><span class="badge bg-secondary">' + t.status + '</span></td>' +
                        '<td>' + (t.priority || 'normal') + '</td>' +
                        '<td>' + (t.assigned_to ? 'Assigned' : 'Unassigned') + '</td>' +
                        '</tr>';
                });
                tbody.innerHTML = html;
            })
            .catch(function(err) {
                console.error('Failed to load tasks:', err);
            });
    });

    // Preview
    document.getElementById('preview-btn').addEventListener('click', function() {
        const action = document.getElementById('bulk-action-select').value;
        const payload = {
            task_ids: Array.from(selectedTaskIds),
            bulk_action: action,
            comment: document.getElementById('bulk-comment').value
        };
        if (action === 'assign') {
            payload.target_user_id = document.getElementById('assign-user-select').value;
        }

        fetch(baseUrl + '/bulk/preview', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(flattenForForm(payload))
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            const container = document.getElementById('preview-results');
            const body = document.getElementById('preview-body');
            container.classList.remove('d-none');

            if (data.error) {
                body.innerHTML = '<div class="alert alert-danger m-2">' + data.error + '</div>';
                return;
            }

            const preview = data.preview || [];
            let html = '<table class="table table-sm mb-0"><thead class="table-light"><tr><th>Task</th><th>Result</th></tr></thead><tbody>';
            let canExecute = false;
            preview.forEach(function(p) {
                const ok = p.can_transition || p.can_assign;
                if (ok) canExecute = true;
                const icon = ok ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';
                html += '<tr><td>#' + p.task_id + '</td><td>' + icon + ' ' + (p.reason || '') + '</td></tr>';
            });
            html += '</tbody></table>';
            body.innerHTML = html;

            document.getElementById('execute-btn').disabled = !canExecute;
        })
        .catch(function(err) {
            console.error('Preview failed:', err);
        });
    });

    // Execute
    document.getElementById('execute-btn').addEventListener('click', function() {
        if (!confirm('Are you sure you want to execute this bulk operation?')) return;

        const action = document.getElementById('bulk-action-select').value;
        const payload = {
            task_ids: Array.from(selectedTaskIds),
            bulk_action: action,
            comment: document.getElementById('bulk-comment').value
        };
        if (action === 'assign') {
            payload.target_user_id = document.getElementById('assign-user-select').value;
        }
        if (action === 'priority') {
            payload.new_priority = document.getElementById('priority-select').value;
        }
        if (action === 'move_queue') {
            payload.queue_id = document.getElementById('queue-select').value;
        }

        fetch(baseUrl + '/bulk/execute', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(flattenForForm(payload))
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            const container = document.getElementById('execute-results');
            const body = document.getElementById('execute-body');
            container.classList.remove('d-none');

            if (data.error) {
                body.innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
                return;
            }

            const success = data.result?.success || [];
            const failed = data.result?.failed || [];
            let html = '<div class="alert alert-' + (failed.length === 0 ? 'success' : 'warning') + '">';
            html += '<strong>' + success.length + '</strong> succeeded, <strong>' + failed.length + '</strong> failed';
            if (data.result?.correlation_id) {
                html += '<br><small class="text-muted">Correlation: ' + data.result.correlation_id + '</small>';
            }
            html += '</div>';

            if (failed.length > 0) {
                html += '<ul class="list-unstyled small">';
                failed.forEach(function(f) {
                    html += '<li class="text-danger"><i class="fas fa-times me-1"></i>#' + f.task_id + ': ' + f.reason + '</li>';
                });
                html += '</ul>';
            }

            body.innerHTML = html;
            document.getElementById('execute-btn').disabled = true;
        })
        .catch(function(err) {
            console.error('Execution failed:', err);
        });
    });

    function flattenForForm(obj) {
        const flat = {};
        for (const key in obj) {
            if (Array.isArray(obj[key])) {
                obj[key].forEach(function(v, i) { flat[key + '[' + i + ']'] = v; });
            } else {
                flat[key] = obj[key];
            }
        }
        return flat;
    }
});
</script>
