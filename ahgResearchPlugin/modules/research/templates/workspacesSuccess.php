<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<?php
$taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
$privacyOptions = $taxonomyService->getWorkspacePrivacyOptions(false);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">Workspaces</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-users-cog text-primary me-2"></i>Research Workspaces</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWorkspaceModal">
        <i class="fas fa-plus me-1"></i> New Workspace
    </button>
</div>
<?php if (!empty($workspaces)): ?>
    <div class="row">
        <?php foreach ($workspaces as $ws): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?php echo $ws->visibility === 'private' ? 'dark' : ($ws->visibility === 'members' ? 'info' : 'success'); ?>">
                            <i class="fas fa-<?php echo $ws->visibility === 'private' ? 'lock' : ($ws->visibility === 'members' ? 'users' : 'globe'); ?> me-1"></i>
                            <?php echo ucfirst($ws->visibility); ?>
                        </span>
                        <?php if ($ws->role === 'owner'): ?>
                            <i class="fas fa-crown text-warning" title="Owner"></i>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewWorkspace', 'id' => $ws->id]); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($ws->name); ?>
                            </a>
                        </h5>
                        <?php if ($ws->description): ?>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($ws->description, 0, 100)); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between">
                        <small class="text-muted">
                            <i class="fas fa-users me-1"></i><?php echo $ws->member_count ?? 1; ?> members
                        </small>
                        <small class="text-muted">
                            <?php echo date('M j, Y', strtotime($ws->created_at)); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-users-cog fa-3x text-muted mb-3"></i>
            <h5>No Workspaces Yet</h5>
            <p class="text-muted">Create a private workspace to collaborate with other researchers.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWorkspaceModal">
                <i class="fas fa-plus me-1"></i> Create Workspace
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Create Workspace Modal -->
<div class="modal fade" id="createWorkspaceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Create Workspace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Thesis Research Group">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visibility</label>
                        <select name="visibility" class="form-select">
                            <?php foreach ($privacyOptions as $code => $label): ?>
                            <option value="<?php echo $code ?>"><?php echo __($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Private is recommended for research collaboration</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
