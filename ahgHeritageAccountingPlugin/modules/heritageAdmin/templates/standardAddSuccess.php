<?php use_helper('Text'); ?>
<?php $isEdit = isset($standard) && $standard; ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardList']); ?>" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0">
            <i class="fas fa-balance-scale me-2"></i>
            <?php echo $isEdit ? __('Edit Accounting Standard') : __('Add Accounting Standard'); ?>
        </h1>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Standard Details'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Code'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required 
                                       value="<?php echo $isEdit ? esc_entities($standard->code) : ''; ?>"
                                       placeholder="e.g. GRAP103, IPSAS45" maxlength="20"
                                       style="text-transform: uppercase;">
                                <small class="text-muted"><?php echo __('Unique identifier, uppercase'); ?></small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required 
                                       value="<?php echo $isEdit ? esc_entities($standard->name) : ''; ?>"
                                       placeholder="e.g. GRAP 103 Heritage Assets">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Country / Region'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="country" class="form-control" required
                                       value="<?php echo $isEdit ? esc_entities($standard->country) : ''; ?>"
                                       placeholder="e.g. South Africa, International">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Sort Order'); ?></label>
                                <input type="number" name="sort_order" class="form-control" 
                                       value="<?php echo $isEdit ? $standard->sort_order : 99; ?>" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="3"
                                      placeholder="<?php echo __('Brief description of the standard and its applicability...'); ?>"><?php echo $isEdit ? esc_entities($standard->description) : ''; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Requirements'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Valuation Methods Allowed'); ?></label>
                            <?php 
                            $selectedMethods = $isEdit && $standard->valuation_methods 
                                ? json_decode($standard->valuation_methods, true) 
                                : [];
                            ?>
                            <div class="row">
                                <?php foreach ($valuationMethods as $key => $label): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input type="checkbox" name="valuation_methods[]" value="<?php echo $key; ?>" 
                                               class="form-check-input" id="vm_<?php echo $key; ?>"
                                               <?php echo in_array($key, $selectedMethods) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="vm_<?php echo $key; ?>">
                                            <?php echo __($label); ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Disclosure Requirements'); ?></label>
                            <textarea name="disclosure_requirements" class="form-control" rows="5"
                                      placeholder="<?php echo __('One requirement per line...'); ?>"><?php 
                            if ($isEdit && $standard->disclosure_requirements) {
                                $reqs = json_decode($standard->disclosure_requirements, true);
                                echo is_array($reqs) ? implode("\n", $reqs) : '';
                            }
                            ?></textarea>
                            <small class="text-muted"><?php echo __('Enter one disclosure requirement per line'); ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Settings'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   <?php echo (!$isEdit || $standard->is_active) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <strong><?php echo __('Active'); ?></strong>
                                <br><small class="text-muted"><?php echo __('Available for selection'); ?></small>
                            </label>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="capitalisation_required" value="1" class="form-check-input" id="capitalisation_required"
                                   <?php echo ($isEdit && $standard->capitalisation_required) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="capitalisation_required">
                                <strong><?php echo __('Capitalisation Required'); ?></strong>
                                <br><small class="text-muted"><?php echo __('Monetary value must be recorded'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-1"></i><?php echo $isEdit ? __('Update Standard') : __('Add Standard'); ?>
                    </button>
                    <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'standardList']); ?>" class="btn btn-outline-secondary">
                        <?php echo __('Cancel'); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
