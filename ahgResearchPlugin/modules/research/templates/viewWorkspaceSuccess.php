<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'workspaces']); ?>">Workspaces</a></li>
        <li class="breadcrumb-item active"><?php echo htmlspecialchars($workspaceData->name); ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h2"><?php echo htmlspecialchars($workspaceData->name); ?></h1>
        <?php if ($workspaceData->description): ?>
            <p class="text-muted"><?php echo htmlspecialchars($workspaceData->description); ?></p>
        <?php endif; ?>
        <span class="badge bg-<?php echo $workspaceData->visibility === 'private' ? 'dark' : ($workspaceData->visibility === 'members' ? 'info' : 'success'); ?>">
            <i class="fas fa-<?php echo $workspaceData->visibility === 'private' ? 'lock' : ($workspaceData->visibility === 'members' ? 'users' : 'globe'); ?> me-1"></i>
            <?php echo ucfirst($workspaceData->visibility); ?>
        </span>
    </div>
    <div>
        <?php include_partial('research/favoriteResearchButton', [
            'objectId' => $workspaceData->id,
            'objectType' => 'research_workspace',
            'title' => $workspaceData->name,
            'url' => '/research/workspaces/' . $workspaceData->id,
        ]); ?>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <!-- Discussions -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Discussions</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newDiscussionModal">
                    <i class="fas fa-plus me-1"></i> New Discussion
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($discussions)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($discussions as $disc): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($disc->title); ?></h6>
                                        <p class="mb-1 small"><?php echo htmlspecialchars(substr($disc->content, 0, 150)); ?><?php echo strlen($disc->content) > 150 ? '...' : ''; ?></p>
                                        <small class="text-muted">
                                            by <?php echo htmlspecialchars($disc->author_name ?? 'Unknown'); ?> -
                                            <?php echo date('M j, Y H:i', strtotime($disc->created_at)); ?>
                                            <?php if ($disc->reply_count ?? 0): ?>
                                                <span class="badge bg-secondary ms-2"><?php echo $disc->reply_count; ?> replies</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($disc->is_resolved ?? false): ?>
                                        <span class="badge bg-success">Resolved</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>No discussions yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resources -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Shared Resources</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                    <i class="fas fa-plus me-1"></i> Add Resource
                </button>
            </div>
            <div class="card-body">
                <?php
                $resources = Illuminate\Database\Capsule\Manager::table('research_workspace_resource')
                    ->where('workspace_id', $workspaceData->id)
                    ->orderBy('added_at', 'desc')
                    ->get()->toArray();
                ?>
                <?php if (!empty($resources)): ?>
                    <div class="row">
                        <?php foreach ($resources as $res): ?>
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-3">
                                    <span class="badge bg-secondary mb-2"><?php echo ucfirst($res->resource_type); ?></span>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($res->title ?: 'Untitled'); ?></h6>
                                    <?php if ($res->notes): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($res->notes); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No resources shared yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Members -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-users me-2"></i>Members</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">
                    <i class="fas fa-user-plus"></i>
                </button>
            </div>
            <?php if (!empty($members)): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($members as $member): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <?php echo htmlspecialchars($member->first_name . ' ' . $member->last_name); ?>
                                <?php if ($member->role === 'owner'): ?>
                                    <i class="fas fa-crown text-warning ms-1" title="Owner"></i>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-<?php echo $member->status === 'accepted' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($member->role); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="card-body text-muted">No members yet</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>About</h6></div>
            <div class="card-body">
                <p class="mb-2"><strong>Created:</strong> <?php echo date('M j, Y', strtotime($workspaceData->created_at)); ?></p>
                <p class="mb-0"><strong>Your role:</strong> <?php echo ucfirst($workspaceData->role ?? 'member'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- New Discussion Modal -->
<div class="modal fade" id="newDiscussionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="create_discussion">
                <div class="modal-header">
                    <h5 class="modal-title">New Discussion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content *</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Discussion</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tom Select CSS/JS -->
<link href="/plugins/ahgCorePlugin/web/css/vendor/tom-select.bootstrap5.min.css" rel="stylesheet">
<script src="/plugins/ahgCorePlugin/web/js/vendor/tom-select.complete.min.js" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<style <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.ts-dropdown { background: #fff !important; color: #212529 !important; }
.ts-dropdown .option { color: #212529 !important; }
</style>

<!-- Invite Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="invite">
                <input type="hidden" name="email" id="inviteEmailHidden" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('Invite Member'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Search Researcher'); ?> *</label>
                        <select id="inviteResearcherSearch" required></select>
                        <small class="text-muted"><?php echo __('Type name or email of a registered researcher'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Role'); ?></label>
                        <select name="role" class="form-select">
                            <option value="member"><?php echo __('Member - Can view and comment'); ?></option>
                            <option value="editor"><?php echo __('Editor - Can add resources'); ?></option>
                            <option value="admin"><?php echo __('Admin - Can manage members'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Send Invitation'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var inviteSelect = new TomSelect('#inviteResearcherSearch', {
        valueField: 'email',
        labelField: 'title',
        searchField: ['title', 'email'],
        placeholder: '<?php echo __("Search by name or email..."); ?>',
        loadThrottle: 300,
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            var url = '<?php echo url_for(["module" => "research", "action" => "searchEntities"]); ?>';
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            fetch(url + sep + 'type=researcher&q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(json) { callback(json.items || []); })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item, escape) {
                return '<div class="py-1"><strong>' + escape(item.title) + '</strong>' +
                    '<br><small class="text-muted">' + escape(item.email || '') +
                    (item.institution ? ' &middot; ' + escape(item.institution) : '') + '</small></div>';
            },
            item: function(item, escape) {
                return '<div>' + escape(item.title) + ' <small class="text-muted">(' + escape(item.email || '') + ')</small></div>';
            }
        },
        onChange: function(value) {
            document.getElementById('inviteEmailHidden').value = value;
        }
    });
});
</script>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="add_resource">
                <input type="hidden" name="resource_id" id="resourceIdHidden" value="">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo __('Add Resource'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Resource Type'); ?> *</label>
                        <select name="resource_type" id="resourceTypeSelect" class="form-select" required>
                            <option value="collection"><?php echo __('Collection'); ?></option>
                            <option value="saved_search"><?php echo __('Saved Search'); ?></option>
                            <option value="bibliography"><?php echo __('Bibliography'); ?></option>
                            <option value="object"><?php echo __('Archive Object'); ?></option>
                            <option value="external_link"><?php echo __('External Link'); ?></option>
                        </select>
                    </div>
                    <div class="mb-3" id="resourceSearchGroup">
                        <label class="form-label"><?php echo __('Search Resource'); ?></label>
                        <select id="resourceSearch"></select>
                        <small class="text-muted"><?php echo __('Type to search by name or title'); ?></small>
                    </div>
                    <div class="mb-3" id="externalLinkGroup" style="display:none;">
                        <label class="form-label"><?php echo __('URL'); ?> *</label>
                        <input type="url" name="external_url" id="externalUrlInput" class="form-control" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Title'); ?> *</label>
                        <input type="text" name="title" id="resourceTitleInput" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo __('Notes'); ?></label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo __('Add Resource'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var typeMap = {collection: 'collection', saved_search: 'saved_search', bibliography: 'bibliography', object: 'information_object'};
    var resourceSelect = new TomSelect('#resourceSearch', {
        valueField: 'id',
        labelField: 'title',
        searchField: ['title', 'identifier'],
        placeholder: '<?php echo __("Type to search..."); ?>',
        loadThrottle: 300,
        load: function(query, callback) {
            if (!query.length || query.length < 2) return callback();
            var resType = document.getElementById('resourceTypeSelect').value;
            var searchType = typeMap[resType] || 'information_object';
            var url = '<?php echo url_for(["module" => "research", "action" => "searchEntities"]); ?>';
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            fetch(url + sep + 'type=' + searchType + '&q=' + encodeURIComponent(query))
                .then(function(r) { return r.json(); })
                .then(function(json) { callback(json.items || []); })
                .catch(function() { callback(); });
        },
        render: {
            option: function(item, escape) {
                return '<div class="py-1"><strong>' + escape(item.title) + '</strong>' +
                    (item.identifier ? '<br><small class="text-muted">' + escape(item.identifier) + '</small>' : '') +
                    '</div>';
            },
            item: function(item, escape) {
                return '<div>' + escape(item.title) + '</div>';
            }
        },
        onChange: function(value) {
            document.getElementById('resourceIdHidden').value = value;
            var selected = this.options[value];
            if (selected && selected.title) {
                document.getElementById('resourceTitleInput').value = selected.title;
            }
        }
    });
    // Toggle search vs URL input based on resource type
    document.getElementById('resourceTypeSelect').addEventListener('change', function() {
        var isLink = this.value === 'external_link';
        document.getElementById('resourceSearchGroup').style.display = isLink ? 'none' : '';
        document.getElementById('externalLinkGroup').style.display = isLink ? '' : 'none';
        resourceSelect.clear();
        resourceSelect.clearOptions();
        document.getElementById('resourceIdHidden').value = '';
        document.getElementById('resourceTitleInput').value = '';
    });
});
</script>
