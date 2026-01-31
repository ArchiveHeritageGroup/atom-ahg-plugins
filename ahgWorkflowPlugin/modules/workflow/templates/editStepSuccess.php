<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/dashboard') ?>">Workflow</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for('workflow/admin') ?>">Admin</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for("workflow/admin/edit/{$step->workflow_id}") ?>">Workflow</a></li>
            <li class="breadcrumb-item active">Edit Step: <?php echo esc_entities($step->name) ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Step</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">Step Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo esc_entities($step->name) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="step_type" class="form-label">Step Type</label>
                                <select class="form-select" id="step_type" name="step_type">
                                    <option value="review" <?php echo $step->step_type === 'review' ? 'selected' : '' ?>>Review</option>
                                    <option value="approve" <?php echo $step->step_type === 'approve' ? 'selected' : '' ?>>Approve</option>
                                    <option value="edit" <?php echo $step->step_type === 'edit' ? 'selected' : '' ?>>Edit</option>
                                    <option value="verify" <?php echo $step->step_type === 'verify' ? 'selected' : '' ?>>Verify</option>
                                    <option value="sign_off" <?php echo $step->step_type === 'sign_off' ? 'selected' : '' ?>>Sign Off</option>
                                    <option value="custom" <?php echo $step->step_type === 'custom' ? 'selected' : '' ?>>Custom</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?php echo esc_entities($step->description) ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="action_required" class="form-label">Action Required</label>
                                <select class="form-select" id="action_required" name="action_required">
                                    <option value="approve_reject" <?php echo $step->action_required === 'approve_reject' ? 'selected' : '' ?>>Approve or Reject</option>
                                    <option value="approve" <?php echo $step->action_required === 'approve' ? 'selected' : '' ?>>Approve Only</option>
                                    <option value="complete" <?php echo $step->action_required === 'complete' ? 'selected' : '' ?>>Mark Complete</option>
                                    <option value="submit" <?php echo $step->action_required === 'submit' ? 'selected' : '' ?>>Submit to Next Step</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="escalation_days" class="form-label">Escalation Days</label>
                                <input type="number" class="form-control" id="escalation_days" name="escalation_days" min="1" value="<?php echo $step->escalation_days ?>">
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3">Access Requirements</h6>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="required_role_id" class="form-label">Required Role</label>
                                <select class="form-select" id="required_role_id" name="required_role_id">
                                    <option value="">Any authenticated user</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role->id ?>" <?php echo $step->required_role_id == $role->id ? 'selected' : '' ?>>
                                            <?php echo esc_entities($role->name) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="required_clearance_level" class="form-label">Required Clearance Level</label>
                                <select class="form-select" id="required_clearance_level" name="required_clearance_level">
                                    <option value="">No clearance required</option>
                                    <?php foreach ($clearanceLevels as $level): ?>
                                        <option value="<?php echo $level->level ?>" <?php echo $step->required_clearance_level == $level->level ? 'selected' : '' ?>>
                                            <?php echo esc_entities($level->name ?? "Level {$level->level}") ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="pool_enabled" name="pool_enabled" value="1" <?php echo $step->pool_enabled ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="pool_enabled">Enable Pool</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_optional" name="is_optional" value="1" <?php echo $step->is_optional ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_optional">Optional Step</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo $step->is_active ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="instructions" class="form-label">Instructions for Reviewers</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="3"><?php echo esc_entities($step->instructions) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                            <a href="<?php echo url_for("workflow/admin/edit/{$step->workflow_id}") ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
