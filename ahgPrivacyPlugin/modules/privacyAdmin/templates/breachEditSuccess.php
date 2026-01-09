<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-edit me-2"></i><?php echo __('Edit Breach'); ?>
            <small class="text-muted"><?php echo esc_specialchars($breach->reference_number); ?></small>
        </h1>
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $breach->id]); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to View'); ?>
            </a>
        </div>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachEdit', 'id' => $breach->id]); ?>">
        <input type="hidden" name="id" value="<?php echo $breach->id; ?>">
        
        <div class="row">
            <!-- Left Column - Main Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Breach Information'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?></label>
                                <select class="form-select" disabled>
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo ($breach->jurisdiction ?? '') === $code ? 'selected' : ''; ?>><?php echo $info['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted"><?php echo __('Cannot change jurisdiction after creation'); ?></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Breach Type'); ?></label>
                                <select name="breach_type" class="form-select">
                                    <?php foreach ($breachTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($breach->breach_type ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Severity'); ?></label>
                                <select name="severity" class="form-select">
                                    <?php foreach ($severityLevels as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($breach->severity ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Risk to Rights'); ?></label>
                                <select name="risk_to_rights" class="form-select">
                                    <?php foreach ($riskLevels as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($breach->risk_to_rights ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Data Subjects Affected'); ?></label>
                                <input type="number" name="data_subjects_affected" class="form-control" min="0" value="<?php echo $breach->data_subjects_affected ?? ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Assigned To'); ?></label>
                                <select name="assigned_to" class="form-select">
                                    <option value=""><?php echo __('-- Unassigned --'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->id; ?>" <?php echo ($breach->assigned_to ?? '') == $user->id ? 'selected' : ''; ?>><?php echo esc_specialchars($user->username); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Data Categories Affected'); ?></label>
                            <textarea name="data_categories_affected" class="form-control" rows="2" placeholder="<?php echo __('e.g., Names, ID numbers, financial data...'); ?>"><?php echo esc_specialchars($breach->data_categories_affected ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i><?php echo __('Description & Analysis'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Title'); ?></label>
                            <input type="text" name="title" class="form-control" value="<?php echo esc_specialchars($breachI18n->title ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?></label>
                            <textarea name="description" class="form-control" rows="3"><?php echo esc_specialchars($breachI18n->description ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Cause'); ?></label>
                            <textarea name="cause" class="form-control" rows="2"><?php echo esc_specialchars($breachI18n->cause ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Impact Assessment'); ?></label>
                            <textarea name="impact_assessment" class="form-control" rows="3" placeholder="<?php echo __('Assess the potential consequences for data subjects...'); ?>"><?php echo esc_specialchars($breachI18n->impact_assessment ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Remedial Actions'); ?></label>
                            <textarea name="remedial_actions" class="form-control" rows="3" placeholder="<?php echo __('Actions taken or planned to address the breach...'); ?>"><?php echo esc_specialchars($breachI18n->remedial_actions ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Lessons Learned'); ?></label>
                            <textarea name="lessons_learned" class="form-control" rows="2" placeholder="<?php echo __('What can be improved to prevent future breaches...'); ?>"><?php echo esc_specialchars($breachI18n->lessons_learned ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Status & Notifications -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Status'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <select name="status" class="form-select">
                                <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($breach->status ?? '') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i><?php echo __('Timeline'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Occurred Date'); ?></label>
                            <input type="datetime-local" name="occurred_date" class="form-control" value="<?php echo $breach->occurred_date ? date('Y-m-d\TH:i', strtotime($breach->occurred_date)) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Detected Date'); ?></label>
                            <input type="datetime-local" class="form-control" value="<?php echo $breach->detected_date ? date('Y-m-d\TH:i', strtotime($breach->detected_date)) : ''; ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Contained Date'); ?></label>
                            <input type="datetime-local" name="contained_date" class="form-control" value="<?php echo $breach->contained_date ? date('Y-m-d\TH:i', strtotime($breach->contained_date)) : ''; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Resolved Date'); ?></label>
                            <input type="datetime-local" name="resolved_date" class="form-control" value="<?php echo $breach->resolved_date ? date('Y-m-d\TH:i', strtotime($breach->resolved_date)) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Notifications'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="notification_required" value="1" class="form-check-input" id="notifRequired" <?php echo ($breach->notification_required ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notifRequired"><?php echo __('Notification Required'); ?></label>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="regulator_notified" value="1" class="form-check-input" id="regulatorNotified" <?php echo ($breach->regulator_notified ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="regulatorNotified"><?php echo __('Regulator Notified'); ?></label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Regulator Notified Date'); ?></label>
                            <input type="datetime-local" name="regulator_notified_date" class="form-control" value="<?php echo $breach->regulator_notified_date ? date('Y-m-d\TH:i', strtotime($breach->regulator_notified_date)) : ''; ?>">
                        </div>
                        <hr>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="subjects_notified" value="1" class="form-check-input" id="subjectsNotified" <?php echo ($breach->subjects_notified ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="subjectsNotified"><?php echo __('Data Subjects Notified'); ?></label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Subjects Notified Date'); ?></label>
                            <input type="datetime-local" name="subjects_notified_date" class="form-control" value="<?php echo $breach->subjects_notified_date ? date('Y-m-d\TH:i', strtotime($breach->subjects_notified_date)) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i><?php echo __('Save Changes'); ?>
                            </button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $breach->id]); ?>" class="btn btn-outline-secondary">
                                <?php echo __('Cancel'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
