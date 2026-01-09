<?php use_helper('Text'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-edit me-2"></i><?php echo __('Edit Complaint'); ?> <small class="text-muted"><?php echo esc_specialchars($complaint->reference_number); ?></small></h1>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintView', 'id' => $complaint->id]); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i><?php echo __('Back'); ?></a>
    </div>
    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintEdit', 'id' => $complaint->id]); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Complainant Information'); ?></h5></div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="complainant_name" class="form-control" value="<?php echo esc_specialchars($complaint->complainant_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Email'); ?></label>
                                <input type="email" name="complainant_email" class="form-control" value="<?php echo esc_specialchars($complaint->complainant_email ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Phone'); ?></label>
                                <input type="tel" name="complainant_phone" class="form-control" value="<?php echo esc_specialchars($complaint->complainant_phone ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select name="jurisdiction" class="form-select" disabled>
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($complaint->jurisdiction ?? '') === $code ? 'selected' : ''; ?>><?php echo $info['name']; ?></option>
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
                                    <?php foreach ($complaintTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($complaint->complaint_type ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Date of Incident'); ?></label>
                                <input type="date" name="date_of_incident" class="form-control" value="<?php echo $complaint->date_of_incident ?? ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="4"><?php echo esc_specialchars($complaint->description ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Resolution'); ?></h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Resolution Notes'); ?></label>
                            <textarea name="resolution" class="form-control" rows="3" placeholder="<?php echo __('How was this complaint resolved?'); ?>"><?php echo esc_specialchars($complaint->resolution ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Status'); ?></h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($complaint->status ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Assigned To'); ?></label>
                            <select name="assigned_to" class="form-select">
                                <option value=""><?php echo __('-- Unassigned --'); ?></option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user->id; ?>" <?php echo ($complaint->assigned_to ?? '') == $user->id ? 'selected' : ''; ?>><?php echo esc_specialchars($user->username); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-2"></i><?php echo __('Save Changes'); ?></button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintView', 'id' => $complaint->id]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
