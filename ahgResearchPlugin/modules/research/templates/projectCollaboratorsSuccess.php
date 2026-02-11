<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'projects']); ?>">Projects</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Collaborators</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><i class="fas fa-users text-primary me-2"></i>Project Collaborators</h1>
    <?php if ($project->owner_id == $researcher->id): ?>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'inviteCollaborator', 'id' => $project->id]); ?>" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Invite Collaborator
        </a>
    <?php endif; ?>
</div>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (!empty($collaborators)): ?>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <?php if ($project->owner_id == $researcher->id): ?>
                            <th></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($collaborators as $collab): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($collab->first_name . ' ' . $collab->last_name); ?>
                                <?php if ($collab->role === 'owner'): ?>
                                    <i class="fas fa-crown text-warning ms-1" title="Owner"></i>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($collab->email); ?></td>
                            <td>
                                <span class="badge bg-<?php echo match($collab->role) { 'owner' => 'warning', 'editor' => 'info', default => 'secondary' }; ?>">
                                    <?php echo ucfirst($collab->role); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $collab->status === 'accepted' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($collab->status); ?>
                                </span>
                            </td>
                            <td><?php echo $collab->accepted_at ? date('M j, Y', strtotime($collab->accepted_at)) : '-'; ?></td>
                            <?php if ($project->owner_id == $researcher->id): ?>
                                <td>
                                    <?php if ($collab->role !== 'owner'): ?>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Remove this collaborator?')">
                                            <input type="hidden" name="form_action" value="remove">
                                            <input type="hidden" name="collaborator_id" value="<?php echo $collab->id; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h5>No Collaborators Yet</h5>
                <p>Invite researchers to collaborate on this project.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
