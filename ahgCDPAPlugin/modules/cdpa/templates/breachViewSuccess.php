<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches']); ?>">Breaches</a></li>
                    <li class="breadcrumb-item active">Breach #<?php echo $breach->id; ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-exclamation-triangle me-2"></i>Data Breach Report</h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'breaches']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <?php if ($notificationOverdue): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-clock me-2"></i>POTRAZ Notification Overdue!</h5>
        <p class="mb-0">This breach must be reported to POTRAZ immediately. The 72-hour deadline has passed.</p>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Incident Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($breach->reference_number ?? 'BRE-' . $breach->id); ?></dd>

                        <dt class="col-sm-4">Breach Type</dt>
                        <dd class="col-sm-8"><?php echo ucfirst(str_replace('_', ' ', $breach->breach_type)); ?></dd>

                        <dt class="col-sm-4">Incident Date</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y H:i', strtotime($breach->incident_date)); ?></dd>

                        <dt class="col-sm-4">Discovery Date</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y H:i', strtotime($breach->discovery_date)); ?></dd>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($breach->description)); ?></dd>

                        <dt class="col-sm-4">Root Cause</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($breach->root_cause ?? '-')); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Impact</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Data Types Affected</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($breach->data_affected ?? '-'); ?></dd>

                        <dt class="col-sm-4">Records Affected</dt>
                        <dd class="col-sm-8"><?php echo $breach->records_affected ? number_format($breach->records_affected) : '-'; ?></dd>

                        <dt class="col-sm-4">Data Subjects Affected</dt>
                        <dd class="col-sm-8"><?php echo $breach->data_subjects_affected ? number_format($breach->data_subjects_affected) : '-'; ?></dd>
                    </dl>
                </div>
            </div>

            <?php if ($breach->potraz_notified): ?>
            <div class="card">
                <div class="card-header bg-success text-white"><h5 class="mb-0">POTRAZ Notification</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Notified On</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y H:i', strtotime($breach->potraz_notification_date)); ?></dd>

                        <dt class="col-sm-4">Reference</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($breach->potraz_reference ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Severity</h5></div>
                <div class="card-body text-center">
                    <?php
                    $severityColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                    ?>
                    <span class="badge bg-<?php echo $severityColors[$breach->severity] ?? 'secondary'; ?> fs-4 px-4 py-3">
                        <?php echo ucfirst($breach->severity); ?>
                    </span>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['open' => 'danger', 'investigating' => 'warning', 'contained' => 'info', 'resolved' => 'success'];
                    ?>
                    <span class="badge bg-<?php echo $statusColors[$breach->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($breach->status ?? 'Open'); ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Timeline</h5></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Reported:</strong> <?php echo date('j M Y H:i', strtotime($breach->created_at)); ?></p>
                    <?php
                    $hoursFromDiscovery = (time() - strtotime($breach->discovery_date)) / 3600;
                    $deadlineHours = 72 - $hoursFromDiscovery;
                    ?>
                    <?php if (!$breach->potraz_notified): ?>
                    <p class="mb-0 <?php echo $deadlineHours < 0 ? 'text-danger fw-bold' : ($deadlineHours < 24 ? 'text-warning' : ''); ?>">
                        <strong>POTRAZ Deadline:</strong>
                        <?php if ($deadlineHours < 0): ?>
                            OVERDUE by <?php echo abs(round($deadlineHours)); ?> hours
                        <?php else: ?>
                            <?php echo round($deadlineHours); ?> hours remaining
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
