<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Add Journal Entry') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-book me-2"></i><?php echo __('Add Journal Entry') ?></h1>
            <p class="text-muted"><?php echo esc_entities($asset->object_identifier ?? '') ?> - <?php echo esc_entities($asset->object_title ?? 'Untitled') ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo esc_entities($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?php echo __('Journal Entry Details') ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Journal Date') ?> <span class="text-danger">*</span></label>
                        <input type="date" name="journal_date" class="form-control" value="<?php echo date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Journal Type') ?> <span class="text-danger">*</span></label>
                        <select name="journal_type" class="form-select" required>
                            <option value=""><?php echo __('-- Select --') ?></option>
                            <option value="recognition"><?php echo __('Recognition') ?></option>
                            <option value="revaluation"><?php echo __('Revaluation') ?></option>
                            <option value="depreciation"><?php echo __('Depreciation') ?></option>
                            <option value="impairment"><?php echo __('Impairment') ?></option>
                            <option value="impairment_reversal"><?php echo __('Impairment Reversal') ?></option>
                            <option value="derecognition"><?php echo __('Derecognition') ?></option>
                            <option value="adjustment"><?php echo __('Adjustment') ?></option>
                            <option value="transfer"><?php echo __('Transfer') ?></option>
						</select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Journal Number') ?></label>
                        <input type="text" name="journal_number" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('Debit Account') ?></label>
                        <input type="text" name="debit_account" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo __('Credit Account') ?></label>
                        <input type="text" name="credit_account" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Debit Amount') ?></label>
                        <input type="number" step="0.01" name="debit_amount" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Credit Amount') ?></label>
                        <input type="number" step="0.01" name="credit_amount" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><?php echo __('Prepared By') ?></label>
                        <input type="text" name="prepared_by" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><?php echo __('Description') ?></label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Save Journal Entry') ?></button>
            <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'view', 'id' => $asset->id]) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
        </div>
    </form>
</div>
