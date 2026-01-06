<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="h2 mb-0"><i class="fas fa-exclamation-triangle me-2 text-danger"></i><?php echo __('Report Data Breach'); ?></h1>
    </div>

    <div class="alert alert-danger">
        <i class="fas fa-clock me-2"></i>
        <strong><?php echo __('Time-sensitive:'); ?></strong> <?php echo __('Most regulations require breach notification within 72 hours of detection.'); ?>
    </div>

    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachAdd']); ?>">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><?php echo __('Breach Details'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Jurisdiction'); ?> <span class="text-danger">*</span></label>
                                <select name="jurisdiction" class="form-select" required>
                                    <?php foreach ($jurisdictions as $code => $info): ?>
                                    <option value="<?php echo $code; ?>" <?php echo $code === $defaultJurisdiction ? 'selected' : ''; ?>>
                                        <?php echo $info['name']; ?> (<?php echo $info['country']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Breach Type'); ?> <span class="text-danger">*</span></label>
                                <select name="breach_type" class="form-select" required>
                                    <?php foreach ($breachTypes as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo __($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Severity'); ?> <span class="text-danger">*</span></label>
                                <select name="severity" class="form-select" required>
                                    <?php foreach ($severityLevels as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $key === 'medium' ? 'selected' : ''; ?>><?php echo __($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Records Affected'); ?></label>
                                <input type="number" name="records_affected" class="form-control" min="0">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Detected Date/Time'); ?> <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="detected_date" class="form-control" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo __('Occurred Date/Time'); ?></label>
                                <input type="datetime-local" name="occurred_date" class="form-control">
                                <small class="text-muted"><?php echo __('If known'); ?></small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Data Categories Affected'); ?></label>
                            <input type="text" name="data_categories" class="form-control" placeholder="<?php echo __('e.g., Names, ID numbers, Health data'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Cause'); ?></label>
                            <select name="cause" class="form-select">
                                <option value=""><?php echo __('Select...'); ?></option>
                                <option value="cyber_attack"><?php echo __('Cyber Attack / Hacking'); ?></option>
                                <option value="malware"><?php echo __('Malware / Ransomware'); ?></option>
                                <option value="phishing"><?php echo __('Phishing'); ?></option>
                                <option value="human_error"><?php echo __('Human Error'); ?></option>
                                <option value="system_failure"><?php echo __('System Failure'); ?></option>
                                <option value="theft"><?php echo __('Theft of Device/Media'); ?></option>
                                <option value="unauthorized_access"><?php echo __('Unauthorized Access'); ?></option>
                                <option value="other"><?php echo __('Other'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Description'); ?> <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="<?php echo __('Describe what happened, what data was affected, and how the breach was detected...'); ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i><?php echo __('Notification Deadlines'); ?></h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($jurisdictions as $code => $info): ?>
                                <tr>
                                    <td><?php echo $info['name']; ?></td>
                                    <td class="text-end"><strong><?php echo $info['breach_hours'] ?: 'ASAP'; ?><?php echo $info['breach_hours'] ? 'h' : ''; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo __('Report Breach'); ?>
                            </button>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachList']); ?>" class="btn btn-outline-secondary">
                                <?php echo __('Cancel'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
