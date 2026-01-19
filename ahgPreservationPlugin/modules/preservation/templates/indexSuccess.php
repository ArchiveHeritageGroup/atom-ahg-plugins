<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<div class="d-flex justify-content-between align-items-center">
  <h1><i class="bi bi-shield-check text-success me-2"></i><?php echo __('Digital Preservation Dashboard'); ?></h1>
  <a href="<?php echo url_for(['module' => 'reports', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i><?php echo __('Return to Central Dashboard'); ?>
  </a>
</div>
<?php end_slot() ?>

<?php slot('content') ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Digital Objects'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_objects']); ?></h2>
                        <small><?php echo $stats['total_size_formatted']; ?></small>
                    </div>
                    <i class="bi bi-file-earmark-binary fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Checksum Coverage'); ?></h6>
                        <h2 class="mb-0"><?php echo $stats['checksum_coverage']; ?>%</h2>
                        <small><?php echo number_format($stats['objects_with_checksum']); ?> objects</small>
                    </div>
                    <i class="bi bi-check-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?php echo $stats['fixity_failures_30d'] > 0 ? 'bg-danger' : 'bg-info'; ?> text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('Fixity Checks (30d)'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['fixity_checks_30d']); ?></h2>
                        <small><?php echo $stats['fixity_failures_30d']; ?> failures</small>
                    </div>
                    <i class="bi bi-fingerprint fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?php echo $stats['formats_at_risk'] > 0 ? 'bg-warning' : 'bg-secondary'; ?> text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><?php echo __('At-Risk Formats'); ?></h6>
                        <h2 class="mb-0"><?php echo number_format($stats['formats_at_risk']); ?></h2>
                        <small><?php echo $stats['pending_verification']; ?> pending verification</small>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'identification']); ?>" class="btn btn-outline-info">
            <i class="bi bi-fingerprint me-1"></i><?php echo __('Format ID'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog']); ?>" class="btn btn-outline-primary">
            <i class="bi bi-list-check me-1"></i><?php echo __('Fixity Log'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'events']); ?>" class="btn btn-outline-primary">
            <i class="bi bi-calendar-event me-1"></i><?php echo __('Events'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'formats']); ?>" class="btn btn-outline-primary">
            <i class="bi bi-file-code me-1"></i><?php echo __('Format Registry'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'scheduler']); ?>" class="btn btn-outline-dark">
            <i class="bi bi-clock-history me-1"></i><?php echo __('Scheduler'); ?>
        </a>
    </div>
    <div>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'packages']); ?>" class="btn btn-outline-primary">
            <i class="bi bi-archive me-1"></i><?php echo __('OAIS Packages'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'virusScan']); ?>" class="btn btn-outline-danger">
            <i class="bi bi-shield-exclamation me-1"></i><?php echo __('Virus Scan'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'conversion']); ?>" class="btn btn-outline-success">
            <i class="bi bi-arrow-repeat me-1"></i><?php echo __('Format Conversion'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'backup']); ?>" class="btn btn-outline-info">
            <i class="bi bi-cloud-arrow-up me-1"></i><?php echo __('Backup'); ?>
        </a>
    </div>
</div>

<div class="row">
    <!-- Recent Fixity Checks -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-fingerprint me-2"></i><?php echo __('Recent Fixity Checks'); ?></span>
                <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'fixityLog']); ?>" class="btn btn-sm btn-outline-secondary">
                    <?php echo __('View All'); ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('File'); ?></th>
                            <th><?php echo __('Status'); ?></th>
                            <th><?php echo __('Checked'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentFixityChecks)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                <?php echo __('No fixity checks yet'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentFixityChecks as $check): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $check->digital_object_id]); ?>">
                                        <?php echo htmlspecialchars(substr($check->filename ?? 'Unknown', 0, 30)); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($check->status === 'pass'): ?>
                                        <span class="badge bg-success">Pass</span>
                                    <?php elseif ($check->status === 'fail'): ?>
                                        <span class="badge bg-danger">Fail</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning"><?php echo ucfirst($check->status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($check->checked_at)); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-event me-2"></i><?php echo __('Recent Preservation Events'); ?></span>
                <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'events']); ?>" class="btn btn-sm btn-outline-secondary">
                    <?php echo __('View All'); ?>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo __('Event'); ?></th>
                            <th><?php echo __('Outcome'); ?></th>
                            <th><?php echo __('Time'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentEvents)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">
                                <?php echo __('No preservation events yet'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentEvents as $event): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary"><?php echo str_replace('_', ' ', $event->event_type); ?></span>
                                </td>
                                <td>
                                    <?php if ($event->event_outcome === 'success'): ?>
                                        <span class="text-success"><i class="bi bi-check-circle"></i></span>
                                    <?php elseif ($event->event_outcome === 'failure'): ?>
                                        <span class="text-danger"><i class="bi bi-x-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-question-circle"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo date('Y-m-d H:i', strtotime($event->event_datetime)); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- At-Risk Objects Alert -->
<?php if (!empty($atRiskObjects)): ?>
<div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo __('Objects Requiring Attention'); ?>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('Object'); ?></th>
                    <th><?php echo __('File'); ?></th>
                    <th><?php echo __('Issue'); ?></th>
                    <th><?php echo __('Detected'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atRiskObjects as $obj): ?>
                <tr>
                    <td><?php echo htmlspecialchars($obj->title ?? 'Untitled'); ?></td>
                    <td>
                        <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'object', 'id' => $obj->id]); ?>">
                            <?php echo htmlspecialchars($obj->filename ?? 'Unknown'); ?>
                        </a>
                    </td>
                    <td><span class="text-danger"><?php echo htmlspecialchars($obj->error_message ?? 'Fixity check failed'); ?></span></td>
                    <td><small class="text-muted"><?php echo date('Y-m-d', strtotime($obj->checked_at)); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php end_slot() ?>
