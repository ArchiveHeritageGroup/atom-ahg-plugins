<?php use_helper('Text'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-plus-circle me-2"></i><?php echo __('Log Complaint'); ?></h1>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintList']); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?></a>
    </div>
    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintAdd']); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Complainant Information'); ?></h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="complainant_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Email'); ?></label>
                                <input type="email" name="complainant_email" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Phone'); ?></label>
                                <input type="tel" name="complainant_phone" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select name="jurisdiction" class="form-select">
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($defaultJurisdiction ?? '') === $code ? 'selected' : ''; ?>><?php echo $info['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i><?php echo __('Complaint Details'); ?></h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Complaint Type'); ?> <span class="text-danger">*</span></label>
                                <select name="complaint_type" class="form-select" required>
                                    <option value=""><?php echo __('-- Select --'); ?></option>
                                    <?php foreach ($complaintTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Date of Incident'); ?></label>
                                <input type="date" name="date_of_incident" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="4" placeholder="<?php echo __('Detailed description of the complaint...'); ?>"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Assigned To'); ?></label>
                            <select name="assigned_to" class="form-select">
                                <option value=""><?php echo __('-- Unassigned --'); ?></option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->id; ?>"><?php echo esc_specialchars($user->username); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Log Complaint'); ?></button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintList']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
