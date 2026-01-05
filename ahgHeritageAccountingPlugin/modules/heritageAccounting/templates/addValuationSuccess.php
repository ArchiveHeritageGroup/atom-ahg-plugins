<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Add Valuation') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-calculator me-2"></i><?php echo __('Add Valuation') ?></h1>
            <p class="text-muted"><?php echo esc_entities($asset->object_identifier ?? '') ?> - <?php echo esc_entities($asset->object_title ?? 'Untitled') ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo esc_entities($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?php echo __('Valuation Details') ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Valuation Date') ?> <span class="text-danger">*</span></label>
                        <input type="date" name="valuation_date" class="form-control" value="<?php echo date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('New Value') ?> <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="new_value" class="form-control" value="<?php echo $asset->current_carrying_amount ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Valuation Method') ?></label>
                        <select name="valuation_method" class="form-select">
                            <option value=""><?php echo __('-- Select --') ?></option>
                            <option value="market"><?php echo __('Market Value') ?></option>
                            <option value="cost"><?php echo __('Cost Approach') ?></option>
                            <option value="income"><?php echo __('Income Approach') ?></option>
                            <option value="expert"><?php echo __('Expert Opinion') ?></option>
                            <option value="insurance"><?php echo __('Insurance Valuation') ?></option>
                            <option value="other"><?php echo __('Other') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('Valuer Name') ?></label>
                        <input type="text" name="valuer_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('Valuer Credentials') ?></label>
                        <input type="text" name="valuer_credentials" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php echo __('Notes') ?></label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Save Valuation') ?></button>
            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
        </div>
    </form>
</div>
