<?php use_helper('Date') ?>

<div class="container-fluid px-4 py-3">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'workflow', 'action' => 'dashboard']) ?>">Workflow</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>">Admin</a></li>
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $workflow->id]) ?>"><?php echo esc_entities($workflow->name) ?></a></li>
            <li class="breadcrumb-item active">Add Step</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add Workflow Step</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label">Step Name *</label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., Initial Review">
                            </div>
                            <div class="col-md-4">
                                <label for="step_type" class="form-label">Step Type</label>
                                <select class="form-select" id="step_type" name="step_type">
                                    <option value="review">Review</option>
                                    <option value="approve">Approve</option>
                                    <option value="edit">Edit</option>
                                    <option value="verify">Verify</option>
                                    <option value="sign_off">Sign Off</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Describe what happens in this step..."></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="action_required" class="form-label">Action Required</label>
                                <select class="form-select" id="action_required" name="action_required">
                                    <option value="approve_reject">Approve or Reject</option>
                                    <option value="approve">Approve Only</option>
                                    <option value="complete">Mark Complete</option>
                                    <option value="submit">Submit to Next Step</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="escalation_days" class="form-label">Escalation Days</label>
                                <input type="number" class="form-control" id="escalation_days" name="escalation_days" min="1" placeholder="Days before escalation">
                                <small class="form-text text-muted">Leave empty for no deadline</small>
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
                                        <option value="<?php echo $role->id ?>"><?php echo esc_entities($role->name) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="required_clearance_level" class="form-label">Required Clearance Level</label>
                                <select class="form-select" id="required_clearance_level" name="required_clearance_level">
                                    <option value="">No clearance required</option>
                                    <?php foreach ($clearanceLevels as $level): ?>
                                        <option value="<?php echo $level->level ?>"><?php echo esc_entities($level->name ?? "Level {$level->level}") ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="pool_enabled" name="pool_enabled" value="1" checked>
                                    <label class="form-check-label" for="pool_enabled">Enable Pool (multiple users can claim)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_optional" name="is_optional" value="1">
                                    <label class="form-check-label" for="is_optional">Optional Step (can be skipped)</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="instructions" class="form-label">Instructions for Reviewers</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="3" placeholder="Instructions displayed to users when reviewing this step..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Add Step
                            </button>
                            <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $workflow->id]) ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
