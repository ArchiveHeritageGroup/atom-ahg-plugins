<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard') ?>">Workflow</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/admin') ?>">Admin</a></li>
            <li class="breadcrumb-item active">Edit: <?php echo esc_entities($workflow->name) ?></li>
        </ol>
    </nav>

    <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $sf_user->getFlash('notice') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Workflow Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Workflow Settings</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo esc_entities($workflow->name) ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="trigger_event" class="form-label">Trigger Event</label>
                                <select class="form-select" id="trigger_event" name="trigger_event">
                                    <option value="submit" <?php echo $workflow->trigger_event === 'submit' ? 'selected' : '' ?>>On Submit</option>
                                    <option value="create" <?php echo $workflow->trigger_event === 'create' ? 'selected' : '' ?>>On Create</option>
                                    <option value="update" <?php echo $workflow->trigger_event === 'update' ? 'selected' : '' ?>>On Update</option>
                                    <option value="publish" <?php echo $workflow->trigger_event === 'publish' ? 'selected' : '' ?>>On Publish</option>
                                    <option value="manual" <?php echo $workflow->trigger_event === 'manual' ? 'selected' : '' ?>>Manual Only</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?php echo esc_entities($workflow->description) ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="scope_type" class="form-label">Scope</label>
                                <select class="form-select" id="scope_type" name="scope_type">
                                    <option value="global" <?php echo $workflow->scope_type === 'global' ? 'selected' : '' ?>>Global</option>
                                    <option value="repository" <?php echo $workflow->scope_type === 'repository' ? 'selected' : '' ?>>Repository</option>
                                    <option value="collection" <?php echo $workflow->scope_type === 'collection' ? 'selected' : '' ?>>Collection</option>
                                </select>
                            </div>
                            <div class="col-md-8" id="scope_id_container" style="<?php echo $workflow->scope_type === 'global' ? 'display:none' : '' ?>">
                                <label for="scope_id" class="form-label">Scope Target</label>
                                <select class="form-select" id="scope_id" name="scope_id">
                                    <option value="">Select...</option>
                                    <?php foreach ($repositories as $repo): ?>
                                        <option value="<?php echo $repo->id ?>" <?php echo $workflow->scope_id == $repo->id ? 'selected' : '' ?>>
                                            <?php echo esc_entities($repo->name) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo $workflow->is_active ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?php echo $workflow->is_default ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_default">Default</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled" value="1" <?php echo $workflow->notification_enabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notification_enabled">Send Notifications</label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Workflow Steps -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Workflow Steps</h5>
                    <a href="<?php echo url_for("workflow/admin/{$workflow->id}/step/add") ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Add Step
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($workflow->steps)): ?>
                        <div class="text-center text-muted py-4">
                            <p class="mb-0">No steps configured. Add your first step to define the workflow.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush" id="stepsList">
                            <?php foreach ($workflow->steps as $idx => $step): ?>
                                <div class="list-group-item" data-step-id="<?php echo $step->id ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-3"><?php echo $idx + 1 ?></span>
                                            <div>
                                                <h6 class="mb-0"><?php echo esc_entities($step->name) ?></h6>
                                                <small class="text-muted">
                                                    Type: <?php echo ucfirst($step->step_type) ?> |
                                                    Action: <?php echo str_replace('_', '/', $step->action_required) ?>
                                                    <?php if ($step->required_role_id): ?>
                                                        | Role Required
                                                    <?php endif ?>
                                                    <?php if ($step->pool_enabled): ?>
                                                        | <i class="fas fa-users text-info"></i> Pool
                                                    <?php endif ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="btn-group">
                                            <a href="<?php echo url_for("workflow/admin/step/{$step->id}/edit") ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?php echo url_for("workflow/admin/step/{$step->id}/delete") ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this step?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-1"></i>Workflow Info</h6>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">ID</span>
                        <strong>#<?php echo $workflow->id ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Steps</span>
                        <strong><?php echo count($workflow->steps ?? []) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-muted">Created</span>
                        <strong><?php echo date('M j, Y', strtotime($workflow->created_at)) ?></strong>
                    </li>
                </ul>
            </div>

            <div class="d-grid gap-2">
                <a href="<?php echo url_for('workflow/admin') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Workflows
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('scope_type').addEventListener('change', function() {
    var container = document.getElementById('scope_id_container');
    container.style.display = this.value === 'global' ? 'none' : '';
});
</script>
