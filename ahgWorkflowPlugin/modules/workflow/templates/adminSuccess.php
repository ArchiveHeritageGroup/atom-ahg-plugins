<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i>Workflow Administration</h1>
        <div>
            <a href="<?php echo url_for('workflow/admin/create') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Create Workflow
            </a>
            <a href="<?php echo url_for('workflow/dashboard') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>
    <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $sf_user->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <?php if (empty($workflows)): ?>
        <div class="text-center text-muted py-5">
            <i class="fas fa-project-diagram fa-4x mb-3 opacity-50"></i>
            <h4>No workflows configured</h4>
            <p>Create your first workflow to get started.</p>
            <a href="<?php echo url_for('workflow/admin/create') ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-1"></i>Create Workflow
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Scope</th>
                        <th>Trigger</th>
                        <th>Status</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workflows as $wf): ?>
                        <tr>
                            <td>
                                <a href="<?php echo url_for("workflow/admin/edit/{$wf->id}") ?>">
                                    <strong><?php echo esc_entities($wf->name) ?></strong>
                                </a>
                                <?php if ($wf->description): ?>
                                    <br><small class="text-muted"><?php echo esc_entities($wf->description) ?></small>
                                <?php endif ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo ucfirst($wf->scope_type) ?></span>
                                <?php if ($wf->scope_id): ?>
                                    <small class="text-muted">ID: <?php echo $wf->scope_id ?></small>
                                <?php endif ?>
                            </td>
                            <td><?php echo ucfirst($wf->trigger_event) ?></td>
                            <td>
                                <?php if ($wf->is_active): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif ?>
                            </td>
                            <td>
                                <?php if ($wf->is_default): ?>
                                    <i class="fas fa-check text-success"></i>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo url_for("workflow/admin/edit/{$wf->id}") ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo url_for("workflow/admin/delete/{$wf->id}") ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this workflow?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
