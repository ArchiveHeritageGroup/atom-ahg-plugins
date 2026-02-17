<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item active">My Projects</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-project-diagram text-primary me-2"></i>Research Projects</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
        <i class="fas fa-plus me-1"></i> New Project
    </button>
</div>
<div class="row mb-3">
    <div class="col-md-6">
        <form method="get" class="d-flex gap-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach (['planning', 'active', 'on_hold', 'completed', 'archived'] as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo $sf_request->getParameter('status') === $s ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (!empty($projects)): ?>
    <div class="row">
        <?php foreach ($projects as $project): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?php echo match($project->status) { 'active' => 'success', 'planning' => 'info', 'on_hold' => 'warning', 'completed' => 'secondary', default => 'dark' }; ?>"><?php echo ucfirst($project->status); ?></span>
                        <small class="text-muted"><?php echo ucfirst($project->project_type); ?></small>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($project->title); ?>
                            </a>
                        </h5>
                        <?php if ($project->description): ?>
                            <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($project->description, 0, 100)); ?><?php echo strlen($project->description) > 100 ? '...' : ''; ?></p>
                        <?php endif; ?>
                        <?php if ($project->institution): ?>
                            <p class="card-text"><small><i class="fas fa-university me-1"></i><?php echo htmlspecialchars($project->institution); ?></small></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent">
                        <small class="text-muted">
                            <?php if ($project->start_date): ?>
                                <?php echo date('M j, Y', strtotime($project->start_date)); ?>
                                <?php if ($project->expected_end_date): ?> - <?php echo date('M j, Y', strtotime($project->expected_end_date)); ?><?php endif; ?>
                            <?php else: ?>
                                Created <?php echo date('M j, Y', strtotime($project->created_at)); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
            <h5>No Projects Yet</h5>
            <p class="text-muted">Create your first research project to organize your work.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                <i class="fas fa-plus me-1"></i> Create Project
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Create Project Modal -->
<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="form_action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Create Research Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Project Title *</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Type</label>
                            <select name="project_type" class="form-select">
                                <option value="personal">Personal Research</option>
                                <option value="thesis">Thesis</option>
                                <option value="dissertation">Dissertation</option>
                                <option value="publication">Publication</option>
                                <option value="exhibition">Exhibition</option>
                                <option value="documentary">Documentary</option>
                                <option value="genealogy">Genealogy</option>
                                <option value="institutional">Institutional</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Institution</label>
                            <input type="text" name="institution" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected End Date</label>
                            <input type="date" name="expected_end_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>
