<?php use_helper('Text'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i><?php echo __('Edit Consent Record'); ?> #<?php echo $consent->id; ?></h1>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $consent->id]); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?></a>
    </div>
    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentEdit', 'id' => $consent->id]); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Data Subject'); ?></h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Subject Identifier'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="data_subject_id" class="form-control" value="<?php echo esc_specialchars($consent->data_subject_id); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select name="jurisdiction" class="form-select">
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($consent->jurisdiction ?? '') === $code ? 'selected' : ''; ?>><?php echo $info['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Full Name'); ?></label>
                                <input type="text" name="subject_name" class="form-control" value="<?php echo esc_specialchars($consent->subject_name ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Email'); ?></label>
                                <input type="email" name="subject_email" class="form-control" value="<?php echo esc_specialchars($consent->subject_email ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Consent Details'); ?></h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Purpose'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="purpose" class="form-control" value="<?php echo esc_specialchars($consent->purpose); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Consent Method'); ?></label>
                                <select name="consent_method" class="form-select">
                                    <?php foreach ($consentMethods as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($consent->consent_method ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Source'); ?></label>
                                <input type="text" name="source" class="form-control" value="<?php echo esc_specialchars($consent->source ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Status'); ?></label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo ($consent->status ?? '') === 'active' ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
                                    <option value="withdrawn" <?php echo ($consent->status ?? '') === 'withdrawn' ? 'selected' : ''; ?>><?php echo __('Withdrawn'); ?></option>
                                    <option value="expired" <?php echo ($consent->status ?? '') === 'expired' ? 'selected' : ''; ?>><?php echo __('Expired'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="consent_given" value="1" class="form-check-input" id="consentGiven" <?php echo ($consent->consent_given ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="consentGiven"><?php echo __('Consent Given'); ?></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Save Changes'); ?></button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentView', 'id' => $consent->id]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
