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
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required placeholder="researcher@example.com">
                        <small class="text-muted">The collaborator must be a registered researcher</small>
                    </div>
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
                        <a href="<?php echo url_for(['module' => 'research', 'action' => 'projectCollaborators', 'id' => $project->id]); ?>" class="btn btn-secondary">Cancel</a>
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
