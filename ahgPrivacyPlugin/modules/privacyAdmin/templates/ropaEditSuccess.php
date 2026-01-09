<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0"><i class="fas fa-clipboard-list me-2"></i><?php echo __('Edit Processing Activity'); ?></h1>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaEdit', 'id' => $activity->id]); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Basic Information'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label"><?php echo __('Activity Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required value="<?php echo esc_specialchars($activity->name ?? ''); ?>" placeholder="<?php echo __('e.g., Customer Database Processing'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select name="jurisdiction" class="form-select">
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo $code === ($activity->jurisdiction ?? $defaultJurisdiction) ? 'selected' : ''; ?>>
                                        <?php echo $info['name']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Purpose of Processing'); ?> <span class="text-danger">*</span></label>
                            <textarea name="purpose" class="form-control" rows="3" required placeholder="<?php echo __('Describe why this personal data is processed...'); ?>"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Lawful Basis'); ?> <span class="text-danger">*</span></label>
                                <select name="lawful_basis" class="form-select" required>
                                    <option value=""><?php echo __('Select...'); ?></option>
                                    <?php foreach ($lawfulBases as $key => $info): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $key === ($activity->lawful_basis ?? '') ? 'selected' : ''; ?>><?php echo $info['label']; ?> (<?php echo $info['code']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Department'); ?></label>
                                <input type="text" name="department" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Data Details'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Categories of Personal Data'); ?></label>
                            <input type="text" name="data_categories" class="form-control" placeholder="<?php echo __('e.g., Name, Email, ID Number, Health Data'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Categories of Data Subjects'); ?></label>
                            <input type="text" name="data_subjects" class="form-control" placeholder="<?php echo __('e.g., Customers, Employees, Researchers'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Recipients'); ?></label>
                            <textarea name="recipients" class="form-control" rows="2" placeholder="<?php echo __('Who receives this data? Internal departments, third parties...'); ?>"></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Third Country Transfers'); ?></label>
                                <input type="text" name="third_countries" class="form-control" placeholder="<?php echo __('Countries outside jurisdiction'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Transfer Safeguards'); ?></label>
                                <input type="text" name="cross_border_safeguards" class="form-control" placeholder="<?php echo __('e.g., SCCs, BCRs'); ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Retention Period'); ?></label>
                                <input type="text" name="retention_period" class="form-control" value="<?php echo esc_specialchars($activity->retention_period ?? ''); ?>" placeholder="<?php echo __('e.g., 5 years after contract ends'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Security Measures'); ?></label>
                                <input type="text" name="security_measures" class="form-control" placeholder="<?php echo __('e.g., Encryption, Access controls'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('DPIA (Data Protection Impact Assessment)'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="dpia_required" value="1" class="form-check-input" id="dpia_required" <?php echo ($activity->dpia_required ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="dpia_required"><?php echo __('DPIA Required'); ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="dpia_completed" value="1" class="form-check-input" id="dpia_completed" <?php echo ($activity->dpia_completed ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="dpia_completed"><?php echo __('DPIA Completed'); ?></label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><?php echo __('DPIA Date'); ?></label>
                                <input type="date" name="dpia_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo __('Status & Review'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <select name="status" class="form-select">
                                <option value="draft" <?php echo ($activity->status ?? 'draft') === 'draft' ? 'selected' : ''; ?>><?php echo __('Draft'); ?></option>
                                <option value="pending_review" <?php echo ($activity->status ?? '') === 'pending_review' ? 'selected' : ''; ?>><?php echo __('Pending Review'); ?></option>
                                <option value="approved" <?php echo ($activity->status ?? '') === 'approved' ? 'selected' : ''; ?>><?php echo __('Approved'); ?></option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Responsible Person'); ?></label>
                            <input type="text" name="responsible_person" class="form-control" value="<?php echo esc_specialchars($activity->owner ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Next Review Date'); ?></label>
                            <input type="date" name="next_review_date" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo __('Save Activity'); ?>
                            </button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'ropaList']); ?>" class="btn btn-outline-secondary">
                                <?php echo __('Cancel'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
