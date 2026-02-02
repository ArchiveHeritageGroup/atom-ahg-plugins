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
    <?php if ($project->owner_id == $researcher->id): ?>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'editProject', 'id' => $project->id]); ?>" class="btn btn-outline-primary">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
    <?php endif; ?>
</div>

<?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
<?php endif; ?>

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
                                        <?php echo htmlspecialchars($resource->title); ?>
                                    </div>
                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($resource->added_at)); ?></small>
                                </div>
                                <?php if ($resource->notes): ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($resource->notes); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No resources linked yet.</p>
                <?php endif; ?>
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
