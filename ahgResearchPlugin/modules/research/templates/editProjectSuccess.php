<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>">Projects</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<h1 class="h2 mb-4"><i class="fas fa-edit text-primary me-2"></i>Edit Project</h1>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Project Title *</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($project->title); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($project->description ?? ''); ?></textarea>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Project Type</label>
                            <select name="project_type" class="form-select">
                                <?php foreach (['personal' => 'Personal Research', 'thesis' => 'Thesis', 'dissertation' => 'Dissertation', 'publication' => 'Publication', 'exhibition' => 'Exhibition', 'documentary' => 'Documentary', 'genealogy' => 'Genealogy', 'institutional' => 'Institutional', 'other' => 'Other'] as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php echo $project->project_type === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'archived' => 'Archived'] as $k => $v): ?>
                                    <option value="<?php echo $k; ?>" <?php echo $project->status === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Institution</label>
                            <input type="text" name="institution" class="form-control" value="<?php echo htmlspecialchars($project->institution ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supervisor</label>
                            <input type="text" name="supervisor" class="form-control" value="<?php echo htmlspecialchars($project->supervisor ?? ''); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Funding Source</label>
                        <input type="text" name="funding_source" class="form-control" value="<?php echo htmlspecialchars($project->funding_source ?? ''); ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $project->start_date; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected End Date</label>
                            <input type="date" name="expected_end_date" class="form-control" value="<?php echo $project->expected_end_date; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visibility</label>
                        <select name="visibility" class="form-select">
                            <option value="private" <?php echo $project->visibility === 'private' ? 'selected' : ''; ?>>Private - Only you</option>
                            <option value="collaborators" <?php echo $project->visibility === 'collaborators' ? 'selected' : ''; ?>>Collaborators - Team members</option>
                            <option value="public" <?php echo $project->visibility === 'public' ? 'selected' : ''; ?>>Public - Anyone with link</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Changes</button>
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
