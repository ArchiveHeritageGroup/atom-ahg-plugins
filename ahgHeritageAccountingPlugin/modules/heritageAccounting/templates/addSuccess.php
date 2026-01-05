<?php slot('title') ?><?php echo __('Add Heritage Asset') ?><?php end_slot() ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0"><i class="fas fa-plus me-2"></i><?php echo __('Add Heritage Asset') ?></h1>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo esc_entities($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'add']) ?>">
        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Basic Information') ?></h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <?php if (isset($io) && $io): ?>
                                    <label class="form-label"><?php echo __('Linked Record') ?></label>
                                    <div class="form-control bg-light"><?php echo esc_entities($io->title ?: 'Untitled') ?></div>
                                    <input type="hidden" name="information_object_id" value="<?php echo $io->id ?>">
                                <?php else: ?>
                                    <label class="form-label"><?php echo __('Information Object ID') ?></label>
                                    <input type="number" name="information_object_id" class="form-control" value="<?php echo $formData['information_object_id'] ?? '' ?>">
                                    <small class="text-muted"><?php echo __('Optional: Link to archival description') ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Accounting Standard') ?></label>
                                <select name="accounting_standard_id" class="form-select">
                                    <option value=""><?php echo __('-- Select Standard --') ?></option>
                                    <?php foreach ($standards as $s): ?>
                                        <option value="<?php echo $s->id ?>" <?php echo ($formData['accounting_standard_id'] ?? '') == $s->id ? 'selected' : '' ?>><?php echo esc_entities($s->code . ' - ' . $s->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Asset Class') ?></label>
                                <select name="asset_class_id" class="form-select">
                                    <option value=""><?php echo __('-- Select Class --') ?></option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?php echo $c->id ?>" <?php echo ($formData['asset_class_id'] ?? '') == $c->id ? 'selected' : '' ?>><?php echo esc_entities($c->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Sub-class') ?></label>
                                <input type="text" name="asset_sub_class" class="form-control" value="<?php echo esc_entities($formData['asset_sub_class'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recognition -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Recognition') ?></h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Recognition Status') ?></label>
                                <select name="recognition_status" class="form-select">
                                    <option value="pending" <?php echo ($formData['recognition_status'] ?? 'pending') == 'pending' ? 'selected' : '' ?>><?php echo __('Pending') ?></option>
                                    <option value="recognised" <?php echo ($formData['recognition_status'] ?? '') == 'recognised' ? 'selected' : '' ?>><?php echo __('Recognised') ?></option>
                                    <option value="not_recognised" <?php echo ($formData['recognition_status'] ?? '') == 'not_recognised' ? 'selected' : '' ?>><?php echo __('Not Recognised') ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Recognition Date') ?></label>
                                <input type="date" name="recognition_date" class="form-control" value="<?php echo $formData['recognition_date'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Measurement Basis') ?></label>
                                <select name="measurement_basis" class="form-select">
                                    <option value="cost" <?php echo ($formData['measurement_basis'] ?? 'cost') == 'cost' ? 'selected' : '' ?>><?php echo __('Cost') ?></option>
                                    <option value="fair_value" <?php echo ($formData['measurement_basis'] ?? '') == 'fair_value' ? 'selected' : '' ?>><?php echo __('Fair Value') ?></option>
                                    <option value="nominal" <?php echo ($formData['measurement_basis'] ?? '') == 'nominal' ? 'selected' : '' ?>><?php echo __('Nominal') ?></option>
                                    <option value="not_practicable" <?php echo ($formData['measurement_basis'] ?? '') == 'not_practicable' ? 'selected' : '' ?>><?php echo __('Not Practicable') ?></option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?php echo __('Recognition Status Reason') ?></label>
                                <textarea name="recognition_status_reason" class="form-control" rows="2"><?php echo esc_entities($formData['recognition_status_reason'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acquisition -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Acquisition') ?></h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Acquisition Method') ?></label>
                                <select name="acquisition_method" class="form-select">
                                    <option value=""><?php echo __('-- Select --') ?></option>
                                    <option value="purchase" <?php echo ($formData['acquisition_method'] ?? '') == 'purchase' ? 'selected' : '' ?>><?php echo __('Purchase') ?></option>
                                    <option value="donation" <?php echo ($formData['acquisition_method'] ?? '') == 'donation' ? 'selected' : '' ?>><?php echo __('Donation') ?></option>
                                    <option value="bequest" <?php echo ($formData['acquisition_method'] ?? '') == 'bequest' ? 'selected' : '' ?>><?php echo __('Bequest') ?></option>
                                    <option value="transfer" <?php echo ($formData['acquisition_method'] ?? '') == 'transfer' ? 'selected' : '' ?>><?php echo __('Transfer') ?></option>
                                    <option value="found" <?php echo ($formData['acquisition_method'] ?? '') == 'found' ? 'selected' : '' ?>><?php echo __('Found') ?></option>
                                    <option value="exchange" <?php echo ($formData['acquisition_method'] ?? '') == 'exchange' ? 'selected' : '' ?>><?php echo __('Exchange') ?></option>
                                    <option value="other" <?php echo ($formData['acquisition_method'] ?? '') == 'other' ? 'selected' : '' ?>><?php echo __('Other') ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Acquisition Date') ?></label>
                                <input type="date" name="acquisition_date" class="form-control" value="<?php echo $formData['acquisition_date'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Acquisition Cost') ?></label>
                                <input type="number" step="0.01" name="acquisition_cost" class="form-control" value="<?php echo $formData['acquisition_cost'] ?? '0.00' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Fair Value at Acquisition') ?></label>
                                <input type="number" step="0.01" name="fair_value_at_acquisition" class="form-control" value="<?php echo $formData['fair_value_at_acquisition'] ?? '' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Nominal Value') ?></label>
                                <input type="number" step="0.01" name="nominal_value" class="form-control" value="<?php echo $formData['nominal_value'] ?? '1.00' ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Donor Name') ?></label>
                                <input type="text" name="donor_name" class="form-control" value="<?php echo esc_entities($formData['donor_name'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label"><?php echo __('Donor Restrictions') ?></label>
                                <textarea name="donor_restrictions" class="form-control" rows="2"><?php echo esc_entities($formData['donor_restrictions'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Values -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Carrying Amounts') ?></h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Initial Carrying Amount') ?></label>
                                <input type="number" step="0.01" name="initial_carrying_amount" class="form-control" value="<?php echo $formData['initial_carrying_amount'] ?? '0.00' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Current Carrying Amount') ?></label>
                                <input type="number" step="0.01" name="current_carrying_amount" class="form-control" value="<?php echo $formData['current_carrying_amount'] ?? '0.00' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Heritage Information -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Heritage Information') ?></h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Significance') ?></label>
                            <select name="heritage_significance" class="form-select">
                                <option value=""><?php echo __('-- Select --') ?></option>
                                <option value="exceptional" <?php echo ($formData['heritage_significance'] ?? '') == 'exceptional' ? 'selected' : '' ?>><?php echo __('Exceptional') ?></option>
                                <option value="high" <?php echo ($formData['heritage_significance'] ?? '') == 'high' ? 'selected' : '' ?>><?php echo __('High') ?></option>
                                <option value="medium" <?php echo ($formData['heritage_significance'] ?? '') == 'medium' ? 'selected' : '' ?>><?php echo __('Medium') ?></option>
                                <option value="low" <?php echo ($formData['heritage_significance'] ?? '') == 'low' ? 'selected' : '' ?>><?php echo __('Low') ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Significance Statement') ?></label>
                            <textarea name="significance_statement" class="form-control" rows="3"><?php echo esc_entities($formData['significance_statement'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Current Location') ?></label>
                            <input type="text" name="current_location" class="form-control" value="<?php echo esc_entities($formData['current_location'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Condition') ?></label>
                            <select name="condition_rating" class="form-select">
                                <option value=""><?php echo __('-- Select --') ?></option>
                                <option value="excellent" <?php echo ($formData['condition_rating'] ?? '') == 'excellent' ? 'selected' : '' ?>><?php echo __('Excellent') ?></option>
                                <option value="good" <?php echo ($formData['condition_rating'] ?? '') == 'good' ? 'selected' : '' ?>><?php echo __('Good') ?></option>
                                <option value="fair" <?php echo ($formData['condition_rating'] ?? '') == 'fair' ? 'selected' : '' ?>><?php echo __('Fair') ?></option>
                                <option value="poor" <?php echo ($formData['condition_rating'] ?? '') == 'poor' ? 'selected' : '' ?>><?php echo __('Poor') ?></option>
                                <option value="critical" <?php echo ($formData['condition_rating'] ?? '') == 'critical' ? 'selected' : '' ?>><?php echo __('Critical') ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Insurance -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Insurance') ?></h5></div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input type="checkbox" name="insurance_required" class="form-check-input" value="1" <?php echo ($formData['insurance_required'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label"><?php echo __('Insurance Required') ?></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Insurance Value') ?></label>
                            <input type="number" step="0.01" name="insurance_value" class="form-control" value="<?php echo $formData['insurance_value'] ?? '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Policy Number') ?></label>
                            <input type="text" name="insurance_policy_number" class="form-control" value="<?php echo esc_entities($formData['insurance_policy_number'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Provider') ?></label>
                            <input type="text" name="insurance_provider" class="form-control" value="<?php echo esc_entities($formData['insurance_provider'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Expiry Date') ?></label>
                            <input type="date" name="insurance_expiry_date" class="form-control" value="<?php echo $formData['insurance_expiry_date'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><?php echo __('Notes') ?></h5></div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="4"><?php echo esc_entities($formData['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-grid gap-2">
                <!-- Submit -->
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Save Asset') ?></button>
                    <?php if (isset($io) && $io): ?>
                    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'index', 'slug' => $io->slug]) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
                    <?php else: ?>
                    <a href="<?php echo url_for(['module' => 'heritageAccounting', 'action' => 'browse']) ?>" class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>
