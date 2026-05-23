<?php use_helper('Date') ?>

<?php include_partial('workflow/accessibilityHelpers') ?>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i>Workflow Administration</h1>
        <div>
            <?php if (!empty($showInactive)): ?>
                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-eye-slash me-1"></i>Hide Deleted
                </a>
            <?php else: ?>
                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>?show_inactive=1" class="btn btn-outline-secondary">
                    <i class="fas fa-eye me-1"></i>Show Deleted
                </a>
            <?php endif ?>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'createWorkflow']) ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Create Workflow
            </a>
            <?php /* Spectrum#B — install seed pack */ ?>
            <form method="post" action="<?php echo url_for(['module' => 'workflow', 'action' => 'installSpectrumPack']) ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Install the Spectrum 5.1 procedure starter pack? Tick Overwrite first to RESET existing seeded steps.') ?>');">
                <label class="me-1 small text-muted" title="<?php echo __('When ticked, existing Spectrum workflows have their steps replaced with the seed defaults.') ?>">
                    <input type="checkbox" name="overwrite" value="1"> <?php echo __('Overwrite') ?>
                </label>
                <button type="submit" class="btn btn-outline-info">
                    <i class="fas fa-university me-1"></i><?php echo __('Install Spectrum pack') ?>
                </button>
            </form>
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Dashboard
            </a>
        </div>
    </div>

    <?php /* Spectrum#A — filter UI */ ?>
    <?php if (!empty($spectrumProcedures ?? [])): ?>
        <form method="get" action="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>" class="d-flex flex-wrap gap-2 align-items-end mb-3">
            <div class="flex-grow-1" style="max-width: 28rem;">
                <label for="spectrum" class="form-label small mb-1"><?php echo __('Filter by Spectrum 5.1 procedure') ?></label>
                <select name="spectrum" id="spectrum" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value=""><?php echo __('All workflows') ?></option>
                    <?php foreach ($spectrumProcedures as $code => $label): ?>
                        <option value="<?php echo esc_entities($code) ?>" <?php echo (($spectrumFilter ?? '') === $code) ? 'selected' : '' ?>><?php echo esc_entities(__($label)) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php if (!empty($spectrumFilter)): ?>
                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times me-1"></i><?php echo __('Clear filter') ?></a>
            <?php endif ?>
        </form>
    <?php endif ?>

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
            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'createWorkflow']) ?>" class="btn btn-primary btn-lg">
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
                        <th><?php echo __('Spectrum') ?></th>
                        <th>Status</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workflows as $wf): ?>
                        <tr>
                            <td>
                                <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $wf->id]) ?>">
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
                                <?php if (!empty($wf->spectrum_procedure) && isset($spectrumProcedures[$wf->spectrum_procedure])): ?>
                                    <span class="badge bg-info text-dark"><i class="fas fa-university me-1"></i><?php echo esc_entities($spectrumProcedures[$wf->spectrum_procedure]) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif ?>
                            </td>
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
                                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'diagram', 'id' => $wf->id]) ?>" class="btn btn-sm btn-outline-info" title="<?php echo __('View diagram') ?>">
                                        <i class="fas fa-project-diagram"></i>
                                    </a>
                                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $wf->id]) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'deleteWorkflow', 'id' => $wf->id]) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this workflow?');">
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
