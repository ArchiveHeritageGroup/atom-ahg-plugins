<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>">Projects</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Invite Collaborator</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-user-plus text-primary me-2"></i>Invite Collaborator</h1>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <form method="post" id="invite-form">
                    <div class="mb-3">
                        <label class="form-label">Find Researcher *</label>
                        <select id="researcher-select" name="researcher_id" placeholder="Type name or email to search..."></select>
                        <small class="text-muted">Search registered researchers by name or email</small>
                    </div>

                    <div class="mb-3" id="external-invite-section" style="display:none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Not a registered researcher?</strong><br>
                            Enter their email below and they will be invited to register first.
                        </div>
                        <label class="form-label">External Email Address *</label>
                        <input type="email" name="external_email" id="external-email" class="form-control" placeholder="colleague@example.com">
                    </div>

                    <input type="hidden" name="email" id="selected-email" value="">

                    <div class="mb-3">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-select" required>
                            <option value="viewer">Viewer - Can view project details</option>
                            <option value="contributor" selected>Contributor - Can add resources and notes</option>
                            <option value="editor">Editor - Can edit project and manage resources</option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        An invitation email will be sent to the collaborator. They must accept the invitation to join the project.
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Send Invitation</button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Role Permissions</h6></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th class="text-center">Viewer</th>
                            <th class="text-center">Contributor</th>
                            <th class="text-center">Editor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>View project</td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        </tr>
                        <tr>
                            <td>Add notes</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        </tr>
                        <tr>
                            <td>Link resources</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        </tr>
                        <tr>
                            <td>Edit project</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        </tr>
                        <tr>
                            <td>Manage milestones</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-center text-muted">-</td>
                            <td class="text-center text-success"><i class="fas fa-check"></i></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var searchUrl = '/index.php/research/ajax/search-entities?type=researcher';

    var researcherSelect = new TomSelect('#researcher-select', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title', 'email'],
        placeholder: 'Type name or email to search...',
        loadThrottle: 300,
        maxItems: 1,
        create: false,
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            fetch(searchUrl + '&q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(j) { callback(j.items || []); })
                .catch(function() { callback(); });
        },
        render: {
            option: function(data, escape) {
                var html = '<div class="py-1">';
                html += '<span class="fw-semibold">' + escape(data.title) + '</span>';
                if (data.email) html += ' <small class="text-muted">&lt;' + escape(data.email) + '&gt;</small>';
                if (data.institution) html += '<br><small class="text-muted"><i class="fas fa-university fa-xs me-1"></i>' + escape(data.institution) + '</small>';
                html += '</div>';
                return html;
            },
            item: function(data, escape) {
                var html = '<div>' + escape(data.title);
                if (data.email) html += ' <small class="text-muted">&lt;' + escape(data.email) + '&gt;</small>';
                html += '</div>';
                return html;
            },
            no_results: function(data, escape) {
                return '<div class="no-results p-2">No researcher found. <a href="#" id="show-external-invite" class="text-primary">Invite external?</a></div>';
            }
        },
        onChange: function(value) {
            var emailField = document.getElementById('selected-email');
            if (value) {
                var item = this.options[value];
                if (item && item.email) {
                    emailField.value = item.email;
                }
                document.getElementById('external-invite-section').style.display = 'none';
                document.getElementById('external-email').removeAttribute('required');
            } else {
                emailField.value = '';
            }
        }
    });

    // Show external invite on click
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'show-external-invite') {
            e.preventDefault();
            document.getElementById('external-invite-section').style.display = 'block';
            document.getElementById('external-email').setAttribute('required', 'required');
            researcherSelect.blur();
        }
    });

    // On form submit: set email from Tom Select selection or external email
    document.getElementById('invite-form').addEventListener('submit', function(e) {
        var selectedEmail = document.getElementById('selected-email').value;
        var externalEmail = document.getElementById('external-email').value.trim();
        var researcherId = researcherSelect.getValue();

        if (!researcherId && !externalEmail) {
            e.preventDefault();
            alert('Please select a researcher or enter an external email address.');
            return;
        }

        if (!researcherId && externalEmail) {
            document.getElementById('selected-email').value = externalEmail;
        }
    });
});
</script>
