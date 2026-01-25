<?php
/**
 * Heritage Request Access Form.
 */

decorate_with('layout_2col');
?>

<?php slot('title'); ?>
<h1 class="h3">
    <i class="fas fa-key me-2"></i>Request Access
</h1>
<?php end_slot(); ?>

<?php slot('sidebar'); ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        <h6 class="mb-0">Requesting Access To</h6>
    </div>
    <div class="card-body">
        <h5><?php echo esc_specialchars($resource->title ?? $resource->slug ?? 'Item'); ?></h5>
        <?php if ($resource->slug): ?>
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
            <i class="fas fa-eye me-1"></i>View Item
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Why request access?</strong><br>
    Some items may have restricted access due to privacy, copyright, or cultural sensitivity. Your request will be reviewed by our team.
</div>
<?php end_slot(); ?>

<?php if (!$sf_user->isAuthenticated()): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Login Required</strong><br>
    You must be logged in to request access.
    <a href="<?php echo url_for(['module' => 'user', 'action' => 'login']); ?>" class="alert-link">Login here</a>
</div>
<?php else: ?>

<form method="post" action="">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Access Request Details</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="purpose_id" class="form-label">Purpose of Access <span class="text-danger">*</span></label>
                <select class="form-select" name="purpose_id" id="purpose_id" required>
                    <option value="">Select a purpose...</option>
                    <?php foreach ($purposes as $purpose): ?>
                    <option value="<?php echo $purpose->id; ?>">
                        <?php echo esc_specialchars($purpose->name); ?>
                        <?php if ($purpose->requires_approval): ?>(Requires Approval)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="institution_affiliation" class="form-label">Institution/Organization</label>
                <input type="text" class="form-control" name="institution_affiliation" id="institution_affiliation"
                       placeholder="e.g., University of Cape Town">
            </div>

            <div class="mb-3">
                <label for="research_description" class="form-label">Research Project/Description</label>
                <textarea class="form-control" name="research_description" id="research_description" rows="3"
                          placeholder="Briefly describe your research project or use case..."></textarea>
            </div>

            <div class="mb-3">
                <label for="justification" class="form-label">Justification <span class="text-danger">*</span></label>
                <textarea class="form-control" name="justification" id="justification" rows="4" required
                          placeholder="Explain why you need access to this item and how you plan to use it..."></textarea>
                <div class="form-text">Please provide sufficient detail to help us evaluate your request.</div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent">
            <h5 class="mb-0">Terms & Conditions</h5>
        </div>
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="agree_terms" required>
                <label class="form-check-label" for="agree_terms">
                    I agree to use this material only for the stated purpose and will comply with any usage restrictions.
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="agree_attribution" required>
                <label class="form-check-label" for="agree_attribution">
                    I agree to provide proper attribution when using or citing this material.
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-send me-2"></i>Submit Request
        </button>
    </div>
</form>

<?php endif; ?>
