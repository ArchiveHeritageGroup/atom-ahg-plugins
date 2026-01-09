<?php use_helper('Text'); ?>

<?php
$statusClasses = [
    'detected' => 'warning',
    'investigating' => 'info',
    'contained' => 'primary',
    'resolved' => 'success',
    'closed' => 'secondary'
];
$severityClasses = [
    'low' => 'success',
    'medium' => 'warning',
    'high' => 'orange',
    'critical' => 'danger'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h2 mb-0">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                    <?php echo esc_entities($breach->reference_number); ?>
                </h1>
                <small class="text-muted"><?php echo __('Data Breach Record'); ?></small>
            </div>
        </div>
        <div>
            <span class="badge bg-<?php echo $severityClasses[$breach->severity] ?? 'secondary'; ?> fs-6 me-2">
                <?php echo ucfirst($breach->severity); ?>
            </span>
            <span class="badge bg-<?php echo $statusClasses[$breach->status] ?? 'secondary'; ?> fs-6">
                <?php echo ucfirst($breach->status); ?>
            </span>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Breach Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Breach Details'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Jurisdiction'); ?></label>
                            <p class="mb-0"><strong><?php echo strtoupper($breach->jurisdiction); ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Breach Type'); ?></label>
                            <p class="mb-0"><?php echo ucfirst($breach->breach_type); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Detected Date'); ?></label>
                            <p class="mb-0"><?php echo $breach->detected_date; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Occurred Date'); ?></label>
                            <p class="mb-0"><?php echo $breach->occurred_date ?: __('Unknown'); ?></p>
                        </div>
                        <?php if ($breach->data_subjects_affected): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Data Subjects Affected'); ?></label>
                            <p class="mb-0"><strong><?php echo number_format($breach->data_subjects_affected); ?></strong></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($breach->data_categories_affected): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted"><?php echo __('Data Categories Affected'); ?></label>
                            <p class="mb-0"><?php echo esc_entities($breach->data_categories_affected); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notification Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Notification Status'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($breach->regulator_notified): ?>
                                <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                <div>
                                    <strong><?php echo __('Regulator Notified'); ?></strong>
                                    <br><small class="text-muted"><?php echo $breach->regulator_notified_date; ?></small>
                                </div>
                                <?php else: ?>
                                <i class="fas fa-times-circle text-danger fa-2x me-3"></i>
                                <div>
                                    <strong><?php echo __('Regulator Not Notified'); ?></strong>
                                    <?php if ($breach->notification_required): ?>
                                    <br><small class="text-danger"><?php echo __('Notification required within %hours% hours', ['%hours%' => $jurisdictionInfo['breach_hours'] ?? 72]); ?></small>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <?php if ($breach->subjects_notified): ?>
                                <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                                <div>
                                    <strong><?php echo __('Data Subjects Notified'); ?></strong>
                                    <br><small class="text-muted"><?php echo $breach->subjects_notified_date; ?></small>
                                </div>
                                <?php else: ?>
                                <i class="fas fa-times-circle text-warning fa-2x me-3"></i>
                                <div>
                                    <strong><?php echo __('Data Subjects Not Notified'); ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i><?php echo __('Timeline'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php if ($breach->occurred_date): ?>
                        <li class="mb-2">
                            <i class="fas fa-circle text-danger me-2"></i>
                            <strong><?php echo __('Occurred'); ?>:</strong> <?php echo $breach->occurred_date; ?>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i class="fas fa-circle text-warning me-2"></i>
                            <strong><?php echo __('Detected'); ?>:</strong> <?php echo $breach->detected_date; ?>
                        </li>
                        <?php if ($breach->contained_date): ?>
                        <li class="mb-2">
                            <i class="fas fa-circle text-primary me-2"></i>
                            <strong><?php echo __('Contained'); ?>:</strong> <?php echo $breach->contained_date; ?>
                        </li>
                        <?php endif; ?>
                        <?php if ($breach->resolved_date): ?>
                        <li class="mb-2">
                            <i class="fas fa-circle text-success me-2"></i>
                            <strong><?php echo __('Resolved'); ?>:</strong> <?php echo $breach->resolved_date; ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Actions -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i><?php echo __('Actions'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachUpdate', 'id' => $breach->id]); ?>">
                        <input type="hidden" name="id" value="<?php echo $breach->id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Update Status'); ?></label>
                            <select name="status" class="form-select">
                                <option value="detected" <?php echo $breach->status === 'detected' ? 'selected' : ''; ?>><?php echo __('Detected'); ?></option>
                                <option value="investigating" <?php echo $breach->status === 'investigating' ? 'selected' : ''; ?>><?php echo __('Investigating'); ?></option>
                                <option value="contained" <?php echo $breach->status === 'contained' ? 'selected' : ''; ?>><?php echo __('Contained'); ?></option>
                                <option value="resolved" <?php echo $breach->status === 'resolved' ? 'selected' : ''; ?>><?php echo __('Resolved'); ?></option>
                                <option value="closed" <?php echo $breach->status === 'closed' ? 'selected' : ''; ?>><?php echo __('Closed'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Severity'); ?></label>
                            <select name="severity" class="form-select">
                                <?php foreach ($severityLevels as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $breach->severity === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="regulator_notified" value="1" id="regulator_notified" <?php echo $breach->regulator_notified ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="regulator_notified"><?php echo __('Regulator Notified'); ?></label>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="subjects_notified" value="1" id="subjects_notified" <?php echo $breach->subjects_notified ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="subjects_notified"><?php echo __('Data Subjects Notified'); ?></label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i><?php echo __('Update Breach'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="card bg-light">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo strtoupper($breach->jurisdiction); ?> <?php echo __('Requirements'); ?></h5>
                </div>
                <div class="card-body small">
                    <p class="mb-2">
                        <strong><?php echo __('Notification Deadline'); ?>:</strong><br>
                        <?php echo $jurisdictionInfo['breach_hours'] ?? 72; ?> <?php echo __('hours'); ?>
                    </p>
                    <p class="mb-0">
                        <strong><?php echo __('Regulator'); ?>:</strong><br>
                        <?php echo $jurisdictionInfo['regulator'] ?? __('Not specified'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
