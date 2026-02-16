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

<!-- Invite Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="invite">
                <div class="modal-header">
                    <h5 class="modal-title">Invite Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                        <small class="text-muted">Must be a registered researcher</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="member">Member - Can view and comment</option>
                            <option value="editor">Editor - Can add resources</option>
                            <option value="admin">Admin - Can manage members</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="add_resource">
                <div class="modal-header">
                    <h5 class="modal-title">Add Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Resource Type *</label>
                        <select name="resource_type" class="form-select" required>
                            <option value="collection">Collection</option>
                            <option value="saved_search">Saved Search</option>
                            <option value="bibliography">Bibliography</option>
                            <option value="object">Archive Object</option>
                            <option value="external_link">External Link</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Resource ID</label>
                        <input type="number" name="resource_id" class="form-control">
                        <small class="text-muted">ID of collection, search, bibliography, or object</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Resource</button>
                </div>
            </form>
        </div>
    </div>
</div>
