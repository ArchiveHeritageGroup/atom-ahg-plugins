<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Add Impairment') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Add Impairment') ?></h1>
            <p class="text-muted"><?php echo esc_entities($asset->object_identifier ?? '') ?> - <?php echo esc_entities($asset->object_title ?? 'Untitled') ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo esc_entities($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?php echo __('Impairment Details') ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Assessment Date') ?> <span class="text-danger">*</span></label>
                        <input type="date" name="assessment_date" class="form-control" value="<?php echo date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Impairment Amount') ?> <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="impairment_amount" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Impairment Type') ?></label>
                        <select name="impairment_type" class="form-select">
                            <option value=""><?php echo __('-- Select --') ?></option>
                            <option value="physical"><?php echo __('Physical Damage') ?></option>
                            <option value="obsolescence"><?php echo __('Obsolescence') ?></option>
                            <option value="market"><?php echo __('Market Decline') ?></option>
                            <option value="legal"><?php echo __('Legal/Regulatory') ?></option>
                            <option value="environmental"><?php echo __('Environmental') ?></option>
                            <option value="other"><?php echo __('Other') ?></option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php echo __('Reason') ?></label>
                        <textarea name="reason" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('Reviewed By') ?></label>
                        <input type="text" name="reviewed_by" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_reversed" class="form-check-input" id="isReversed" value="1">
                            <label class="form-check-label" for="isReversed"><?php echo __('Reversal of Previous Impairment') ?></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Save Impairment') ?></button>
            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
        </div>
    </form>
</div>
