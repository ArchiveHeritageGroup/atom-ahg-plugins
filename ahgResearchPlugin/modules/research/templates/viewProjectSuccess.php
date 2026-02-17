<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>">Projects</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($project->title); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2"><?php echo htmlspecialchars($project->title); ?></h1>
        <span class="badge bg-<?php echo match($project->status) { 'active' => 'success', 'planning' => 'info', 'on_hold' => 'warning', 'completed' => 'secondary', default => 'dark' }; ?> me-2"><?php echo ucfirst($project->status); ?></span>
        <span class="badge bg-light text-dark"><?php echo ucfirst($project->project_type); ?></span>
    </div>
    <div class="d-flex gap-2">
      <?php if ($project->owner_id == $researcher->id): ?>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'shareProject', 'id' => $project->id]); ?>" class="btn btn-outline-success">
            <i class="fas fa-share-alt me-1"></i> <?php echo __('Share'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'editProject', 'id' => $project->id]); ?>" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
        </a>
      <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Description -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Description</h5></div>
            <div class="card-body">
                <?php if ($project->description): ?>
                    <p><?php echo nl2br(htmlspecialchars($project->description)); ?></p>
                <?php else: ?>
                    <p class="text-muted">No description provided.</p>
                <?php endif; ?>

                <div class="row mt-4">
                    <?php if ($project->institution): ?>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-university me-1"></i> Institution:</strong><br>
                            <?php echo htmlspecialchars($project->institution); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($project->supervisor): ?>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-user-tie me-1"></i> Supervisor:</strong><br>
                            <?php echo htmlspecialchars($project->supervisor); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($project->funding_source): ?>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-money-bill me-1"></i> Funding:</strong><br>
                            <?php echo htmlspecialchars($project->funding_source); ?>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6 mb-2">
                        <strong><i class="fas fa-calendar me-1"></i> Timeline:</strong><br>
                        <?php if ($project->start_date): ?>
                            <?php echo date('M j, Y', strtotime($project->start_date)); ?>
                            <?php if ($project->expected_end_date): ?> - <?php echo date('M j, Y', strtotime($project->expected_end_date)); ?><?php endif; ?>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Milestones -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Milestones</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($milestones)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($milestones as $milestone): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="<?php echo $milestone->status === 'completed' ? 'text-decoration-line-through text-muted' : ''; ?>">
                                        <?php echo htmlspecialchars($milestone->title); ?>
                                    </span>
                                    <?php if ($milestone->due_date): ?>
                                        <small class="text-muted ms-2">Due: <?php echo date('M j, Y', strtotime($milestone->due_date)); ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-<?php echo $milestone->status === 'completed' ? 'success' : ($milestone->status === 'in_progress' ? 'primary' : 'secondary'); ?>">
                                    <?php echo ucfirst($milestone->status); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No milestones defined.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-stream me-2"></i>Project Timeline</h5>
            </div>
            <div class="card-body">
                <?php
                // Merge milestones and activities into timeline
                $timelineItems = [];
                if (!empty($milestones)) {
                    foreach ($milestones as $ms) {
                        $date = $ms->due_date ?: ($ms->created_at ?? date('Y-m-d'));
                        $timelineItems[] = (object)[
                            'date' => $date,
                            'type' => 'milestone',
                            'title' => $ms->title,
                            'status' => $ms->status,
                            'icon' => 'flag',
                            'color' => $ms->status === 'completed' ? 'success' : ($ms->status === 'in_progress' ? 'primary' : 'secondary'),
                        ];
                    }
                }
                if (!empty($activities)) {
                    foreach (array_slice($activities, 0, 20) as $act) {
                        $timelineItems[] = (object)[
                            'date' => $act->created_at,
                            'type' => 'activity',
                            'title' => ($act->entity_title ?: ucfirst($act->activity_type)),
                            'status' => $act->activity_type,
                            'icon' => match($act->activity_type) {
                                'create' => 'plus-circle',
                                'update', 'edit' => 'edit',
                                'view', 'access' => 'eye',
                                'delete', 'remove' => 'trash',
                                'clipboard_add' => 'clipboard',
                                'invite' => 'user-plus',
                                default => 'circle',
                            },
                            'color' => match($act->activity_type) {
                                'create' => 'success',
                                'update', 'edit' => 'info',
                                'delete', 'remove' => 'danger',
                                'invite' => 'warning',
                                default => 'secondary',
                            },
                        ];
                    }
                }
                // Sort by date descending
                usort($timelineItems, function ($a, $b) { return strcmp($b->date, $a->date); });
                ?>
                <?php if (!empty($timelineItems)): ?>
                <div class="timeline-vertical">
                    <?php
                    $lastDate = '';
                    foreach ($timelineItems as $item):
                        $itemDate = date('M j, Y', strtotime($item->date));
                        if ($itemDate !== $lastDate):
                            $lastDate = $itemDate;
                    ?>
                        <div class="timeline-date text-muted fw-bold small mb-2 mt-3"><?php echo $itemDate; ?></div>
                    <?php endif; ?>
                    <div class="d-flex align-items-start mb-2 ms-3">
                        <span class="badge bg-<?php echo $item->color; ?> rounded-circle p-1 me-2 mt-1" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-<?php echo $item->icon; ?> fa-xs"></i>
                        </span>
                        <div>
                            <span class="<?php echo $item->type === 'milestone' ? 'fw-bold' : ''; ?>"><?php echo htmlspecialchars($item->title); ?></span>
                            <?php if ($item->type === 'milestone'): ?>
                                <span class="badge bg-<?php echo $item->color; ?> ms-1"><?php echo ucfirst($item->status); ?></span>
                            <?php else: ?>
                                <small class="text-muted ms-1"><?php echo ucfirst(str_replace('_', ' ', $item->status)); ?></small>
                            <?php endif; ?>
                            <br><small class="text-muted"><?php echo date('H:i', strtotime($item->date)); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No timeline events yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resources -->
        <div class="card mb-4">
            <div class="card-header"><h5 class="mb-0">Linked Resources</h5></div>
            <div class="card-body">
                <?php if (!empty($resources)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($resources as $resource): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge bg-secondary me-2"><?php echo ucfirst($resource->resource_type); ?></span>
                                        <?php if (!empty($resource->link_type)): ?><span class="badge bg-<?php echo match($resource->link_type) { 'academic' => 'primary', 'archive' => 'info', 'database' => 'success', 'government' => 'dark', 'website' => 'warning', 'social_media' => 'danger', default => 'light text-dark' }; ?> me-1"><?php echo ucfirst(str_replace('_', ' ', $resource->link_type)); ?></span><?php endif; ?>
                                        <?php if (!empty($resource->external_url)): ?>
                                            <a href="<?php echo htmlspecialchars($resource->external_url); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php echo htmlspecialchars($resource->title ?: $resource->external_url); ?>
                                                <i class="fas fa-external-link-alt fa-xs ms-1"></i>
                                            </a>
                                        <?php elseif (!empty($resource->object_id)): ?>
                                            <?php
                                            $resSlug = DB::table('slug')->where('object_id', $resource->object_id)->value('slug');
                                            ?>
                                            <?php if ($resSlug): ?>
                                                <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resSlug]); ?>">
                                                    <?php echo htmlspecialchars($resource->title ?: 'View Item'); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($resource->title); ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($resource->title); ?>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($resource->added_at)); ?></small>
                                </div>
                                <?php if (!empty($resource->external_url) && !empty($resource->link_metadata)): ?>
                                    <?php
                                    $meta = is_string($resource->link_metadata) ? json_decode($resource->link_metadata) : $resource->link_metadata;
                                    if ($meta && !empty($meta->description)): ?>
                                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i><?php echo htmlspecialchars(substr($meta->description, 0, 200)); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($resource->notes): ?>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($resource->notes); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No resources linked yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Clipboard Items -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clipboard me-2"></i>Clipboard Items</h5>
                <span class="badge bg-primary"><?php echo count($clipboardItems ?? []); ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($clipboardItems)): ?>
                    <div class="list-group list-group-flush mb-3" id="clipboard-items-list">
                        <?php foreach ($clipboardItems as $item): ?>
                            <div class="list-group-item" id="clipboard-item-<?php echo $item->id; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <?php if ($item->is_pinned): ?>
                                            <i class="fas fa-thumbtack text-warning me-1" title="Pinned"></i>
                                        <?php endif; ?>
                                        <?php if ($item->object_slug): ?>
                                            <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'slug' => $item->object_slug]); ?>">
                                                <?php echo htmlspecialchars($item->object_title ?? $item->object_slug); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Object #<?php echo $item->object_id; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button type="button" class="btn btn-outline-warning btn-sm clipboard-pin-btn"
                                                data-item-id="<?php echo $item->id; ?>"
                                                title="<?php echo $item->is_pinned ? 'Unpin' : 'Pin'; ?>">
                                            <i class="fas fa-thumbtack"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm clipboard-remove-btn"
                                                data-item-id="<?php echo $item->id; ?>"
                                                title="Remove">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if ($item->notes): ?>
                                    <small class="text-muted d-block mt-1"><?php echo htmlspecialchars($item->notes); ?></small>
                                <?php endif; ?>
                                <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($item->created_at)); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-3">No clipboard items linked to this project.</p>
                <?php endif; ?>

                <!-- Add from clipboard form -->
                <hr>
                <h6>Add Items from Clipboard</h6>
                <div id="clipboard-add-form">
                    <div class="mb-2">
                        <label for="clipboard-slugs" class="form-label small">Slugs (comma-separated)</label>
                        <textarea id="clipboard-slugs" class="form-control form-control-sm" rows="2"
                                  placeholder="e.g. item-slug-1, item-slug-2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label for="clipboard-notes" class="form-label small">Notes (optional)</label>
                        <input type="text" id="clipboard-notes" class="form-control form-control-sm"
                               placeholder="Optional notes for these items">
                    </div>
                    <button type="button" id="clipboard-add-btn" class="btn btn-sm btn-primary"
                            data-project-id="<?php echo $project->id; ?>">
                        <i class="fas fa-plus me-1"></i> Add to Project
                    </button>
                    <div id="clipboard-add-result" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Collaborators -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-users me-2"></i>Collaborators</h6>
                <?php if ($project->owner_id == $researcher->id): ?>
                    <a href="<?php echo url_for(['module' => 'research', 'action' => 'inviteCollaborator', 'id' => $project->id]); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user-plus"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($collaborators)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($collaborators as $collab): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo htmlspecialchars($collab->first_name . ' ' . $collab->last_name); ?>
                                <?php if ($collab->role === 'owner'): ?>
                                    <i class="fas fa-crown text-warning ms-1" title="Owner"></i>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-<?php echo $collab->status === 'accepted' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($collab->role); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No collaborators</div>
            <?php endif; ?>
        </div>

        <!-- Activity Log -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h6></div>
            <?php if (!empty($activities)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach (array_slice($activities, 0, 10) as $activity): ?>
                        <li class="list-group-item">
                            <small>
                                <span class="badge bg-light text-dark"><?php echo ucfirst($activity->activity_type); ?></span>
                                <?php if ($activity->entity_title): ?>
                                    <?php echo htmlspecialchars(substr($activity->entity_title, 0, 30)); ?>
                                <?php endif; ?>
                                <br><span class="text-muted"><?php echo date('M j, H:i', strtotime($activity->created_at)); ?></span>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No activity yet</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var manageUrl = '<?php echo url_for(['module' => 'research', 'action' => 'manageClipboardItem']); ?>';
    var addUrl = '<?php echo url_for(['module' => 'research', 'action' => 'clipboardToProject']); ?>';

    // Pin/Unpin clipboard item
    document.querySelectorAll('.clipboard-pin-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var itemId = this.getAttribute('data-item-id');
            var formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('do', 'pin');
            fetch(manageUrl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) { location.reload(); }
                    else { alert(data.error || 'Error toggling pin'); }
                });
        });
    });

    // Remove clipboard item
    document.querySelectorAll('.clipboard-remove-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Remove this item from the project?')) return;
            var itemId = this.getAttribute('data-item-id');
            var formData = new FormData();
            formData.append('item_id', itemId);
            formData.append('do', 'remove');
            fetch(manageUrl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        var el = document.getElementById('clipboard-item-' + itemId);
                        if (el) el.remove();
                    } else {
                        alert(data.error || 'Error removing item');
                    }
                });
        });
    });

    // Add items from clipboard
    var addBtn = document.getElementById('clipboard-add-btn');
    if (addBtn) {
        addBtn.addEventListener('click', function() {
            var slugs = document.getElementById('clipboard-slugs').value.trim();
            var notes = document.getElementById('clipboard-notes').value.trim();
            var projectId = this.getAttribute('data-project-id');
            var resultDiv = document.getElementById('clipboard-add-result');

            if (!slugs) {
                resultDiv.innerHTML = '<div class="alert alert-warning alert-sm py-1 px-2 small">Please enter at least one slug.</div>';
                return;
            }

            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding...';

            var formData = new FormData();
            formData.append('project_id', projectId);
            formData.append('slugs', slugs);
            if (notes) formData.append('notes', notes);

            fetch(addUrl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="alert alert-success py-1 px-2 small">' + data.message + '</div>';
                        document.getElementById('clipboard-slugs').value = '';
                        document.getElementById('clipboard-notes').value = '';
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        resultDiv.innerHTML = '<div class="alert alert-danger py-1 px-2 small">' + (data.error || 'Error adding items') + '</div>';
                    }
                })
                .catch(function() {
                    resultDiv.innerHTML = '<div class="alert alert-danger py-1 px-2 small">Network error</div>';
                })
                .finally(function() {
                    addBtn.disabled = false;
                    addBtn.innerHTML = '<i class="fas fa-plus me-1"></i> Add to Project';
                });
        });
    }
});
</script>
