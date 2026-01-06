<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-exclamation-circle me-2"></i><?php echo __('Breach Register'); ?></span>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachAdd']); ?>" class="btn btn-danger">
            <i class="fas fa-plus me-1"></i><?php echo __('Report Breach'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Reference'); ?></th>
                        <th><?php echo __('Type'); ?></th>
                        <th><?php echo __('Severity'); ?></th>
                        <th><?php echo __('Detected'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th><?php echo __('Regulator Notified'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($breaches->isEmpty()): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><?php echo __('No breaches recorded'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($breaches as $breach): ?>
                    <?php
                    $severityClasses = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                    $statusClasses = ['detected' => 'danger', 'investigating' => 'warning', 'contained' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $breach->id]); ?>">
                                <strong><?php echo esc_entities($breach->reference_number); ?></strong>
                            </a>
                        </td>
                        <td><?php echo ucfirst($breach->breach_type); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $severityClasses[$breach->severity] ?? 'secondary'; ?>">
                                <?php echo ucfirst($breach->severity); ?>
                            </span>
                        </td>
                        <td><?php echo $breach->detected_date; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $statusClasses[$breach->status] ?? 'secondary'; ?>">
                                <?php echo ucfirst($breach->status); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($breach->regulator_notified): ?>
                            <span class="text-success"><i class="fas fa-check"></i> <?php echo $breach->regulator_notified_date; ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'breachView', 'id' => $breach->id]); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
