<?php use_helper('Date'); ?>
<?php slot('title') ?><?php echo __('Add Impairment') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Add Impairment') ?></h1>
            <p class="text-muted"><?php echo esc_entities(sfOutputEscaper::unescape($asset)->object_identifier ?? '') ?> - <?php echo esc_entities(sfOutputEscaper::unescape($asset)->object_title ?? 'Untitled') ?></p>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo esc_entities($error) ?></div>
    <?php endif; ?>

    <form method="post">
<input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><?php echo __('Impairment Details') ?></h5>
            </div>
            <div class="card-body">
                <?php // Fields match the action (#impairment): the form used to send impairment_amount,
                      // impairment_type, reason, reviewed_by and is_reversed, none of which it read,
                      // so every assessment was saved empty and the carrying amount never changed. ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="assessmentDate"><?php echo __('Assessment Date') ?> <span class="text-danger">*</span></label>
                        <input type="date" id="assessmentDate" name="assessment_date" class="form-control" value="<?php echo date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="assessorName"><?php echo __('Assessed By') ?></label>
                        <input type="text" id="assessorName" name="assessor_name" class="form-control">
                    </div>
                </div>

                <h6 class="mt-4"><?php echo __('Indicators of impairment') ?></h6>
                <div class="row g-3">
                    <?php foreach (['physical_damage' => __('Physical damage'), 'obsolescence' => __('Obsolescence'), 'change_in_use' => __('Change in use'), 'external_factors' => __('External factors')] as $key => $label): ?>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" name="<?php echo $key ?>" class="form-check-input" id="ind_<?php echo $key ?>" value="1">
                            <label class="form-check-label" for="ind_<?php echo $key ?>"><?php echo $label ?></label>
                        </div>
                        <input type="text" name="<?php echo $key ?>_details" class="form-control form-control-sm mt-1" placeholder="<?php echo __('Details') ?>" aria-label="<?php echo $label ?>: <?php echo __('Details') ?>">
                    </div>
                    <?php endforeach; ?>
                </div>

                <h6 class="mt-4"><?php echo __('Outcome') ?></h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check mt-2">
                            <input type="checkbox" name="impairment_identified" class="form-check-input" id="impairmentIdentified" value="1">
                            <label class="form-check-label" for="impairmentIdentified"><?php echo __('Impairment identified') ?></label>
                        </div>
                        <div class="form-text"><?php echo __('Carrying amount now: %1%', ['%1%' => number_format((float) $asset->current_carrying_amount, 2)]) ?></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="recoverableAmount"><?php echo __('Recoverable service amount') ?></label>
                        <input type="number" step="0.01" id="recoverableAmount" name="recoverable_amount" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="impairmentLoss"><?php echo __('Impairment loss') ?></label>
                        <input type="number" step="0.01" id="impairmentLoss" name="impairment_loss" class="form-control">
                        <div class="form-text"><?php echo __('Left blank, it is the carrying amount less the recoverable amount.') ?></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="impairmentNotes"><?php echo __('Notes') ?></label>
                        <textarea id="impairmentNotes" name="notes" class="form-control" rows="3"></textarea>
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
