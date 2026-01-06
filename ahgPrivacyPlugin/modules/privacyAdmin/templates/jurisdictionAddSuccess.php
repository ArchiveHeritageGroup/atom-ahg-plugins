<?php use_helper('Text'); ?>
<?php $isEdit = isset($jurisdiction) && $jurisdiction; ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']); ?>" class="btn btn-outline-secondary me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0">
            <i class="fas fa-globe me-2"></i>
            <?php echo $isEdit ? __('Edit Jurisdiction') : __('Add Jurisdiction'); ?>
        </h1>
    </div>

    <form method="post">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Basic Information'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('Code'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" required 
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->code) : ''; ?>"
                                       placeholder="e.g. popia, gdpr" maxlength="30"
                                       <?php echo $isEdit ? 'readonly' : ''; ?>>
                                <small class="text-muted"><?php echo __('Unique identifier, lowercase'); ?></small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><?php echo __('Short Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->name) : ''; ?>"
                                       placeholder="e.g. POPIA, GDPR">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Full Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" required
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->full_name) : ''; ?>"
                                       placeholder="e.g. Protection of Personal Information Act">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Country'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="country" class="form-control" required
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->country) : ''; ?>"
                                       placeholder="e.g. South Africa">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Region'); ?></label>
                                <select name="region" class="form-select">
                                    <?php foreach ($regions as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo ($isEdit && $jurisdiction->region === $r) ? 'selected' : ''; ?>>
                                        <?php echo $r; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?php echo __('Flag Icon'); ?></label>
                                <input type="text" name="icon" class="form-control"
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->icon) : ''; ?>"
                                       placeholder="e.g. za, eu" maxlength="10">
                                <small class="text-muted"><?php echo __('ISO code'); ?></small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><?php echo __('Sort Order'); ?></label>
                                <input type="number" name="sort_order" class="form-control"
                                       value="<?php echo $isEdit ? $jurisdiction->sort_order : 99; ?>" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Regulatory Information'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Regulator Name'); ?></label>
                                <input type="text" name="regulator" class="form-control"
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->regulator) : ''; ?>"
                                       placeholder="e.g. Information Regulator">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Regulator Website'); ?></label>
                                <input type="url" name="regulator_url" class="form-control"
                                       value="<?php echo $isEdit ? esc_entities($jurisdiction->regulator_url) : ''; ?>"
                                       placeholder="https://...">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('DSAR Response Days'); ?></label>
                                <input type="number" name="dsar_days" class="form-control"
                                       value="<?php echo $isEdit ? $jurisdiction->dsar_days : 30; ?>" min="0">
                                <small class="text-muted"><?php echo __('Days to respond to data subject requests'); ?></small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Breach Notification Hours'); ?></label>
                                <input type="number" name="breach_hours" class="form-control"
                                       value="<?php echo $isEdit ? $jurisdiction->breach_hours : 72; ?>" min="0">
                                <small class="text-muted"><?php echo __('0 = "as soon as feasible"'); ?></small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Effective Date'); ?></label>
                                <input type="date" name="effective_date" class="form-control"
                                       value="<?php echo $isEdit ? $jurisdiction->effective_date : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Related Laws'); ?></label>
                            <textarea name="related_laws" class="form-control" rows="3"
                                      placeholder="<?php echo __('One law per line...'); ?>"><?php
                            if ($isEdit && $jurisdiction->related_laws) {
                                $laws = json_decode($jurisdiction->related_laws, true);
                                echo is_array($laws) ? implode("\n", $laws) : '';
                            }
                            ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Status'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                   <?php echo (!$isEdit || $jurisdiction->is_active) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <strong><?php echo __('Active'); ?></strong>
                                <br><small class="text-muted"><?php echo __('Available for selection in forms'); ?></small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-save me-1"></i><?php echo $isEdit ? __('Update Jurisdiction') : __('Add Jurisdiction'); ?>
                    </button>
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'jurisdictionList']); ?>" class="btn btn-outline-secondary">
                        <?php echo __('Cancel'); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
