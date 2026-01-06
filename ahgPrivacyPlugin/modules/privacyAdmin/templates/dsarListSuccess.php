<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-file-alt me-2"></i><?php echo __('Data Subject Access Requests'); ?></span>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarAdd']); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i><?php echo __('New DSAR'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value=""><?php echo __('All Status'); ?></option>
                        <option value="received" <?php echo $sf_request->getParameter('status') === 'received' ? 'selected' : ''; ?>><?php echo __('Received'); ?></option>
                        <option value="in_progress" <?php echo $sf_request->getParameter('status') === 'in_progress' ? 'selected' : ''; ?>><?php echo __('In Progress'); ?></option>
                        <option value="completed" <?php echo $sf_request->getParameter('status') === 'completed' ? 'selected' : ''; ?>><?php echo __('Completed'); ?></option>
                        <option value="rejected" <?php echo $sf_request->getParameter('status') === 'rejected' ? 'selected' : ''; ?>><?php echo __('Rejected'); ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary"><?php echo __('Filter'); ?></button>
                    <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarList', 'overdue' => 1]); ?>" class="btn btn-outline-danger">
                        <?php echo __('Overdue'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Reference'); ?></th>
                        <th><?php echo __('Type'); ?></th>
                        <th><?php echo __('Requestor'); ?></th>
                        <th><?php echo __('Received'); ?></th>
                        <th><?php echo __('Due Date'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th><?php echo __('Assigned To'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dsars->isEmpty()): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?php echo __('No DSARs found'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($dsars as $dsar): ?>
                    <?php
                    $isOverdue = strtotime($dsar->due_date) < time() && !in_array($dsar->status, ['completed', 'rejected', 'withdrawn']);
                    $statusClasses = [
                        'received' => 'secondary',
                        'verified' => 'info',
                        'in_progress' => 'primary',
                        'pending_info' => 'warning',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        'withdrawn' => 'dark'
                    ];
                    ?>
                    <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $dsar->id]); ?>">
                                <strong><?php echo esc_entities($dsar->reference_number); ?></strong>
                            </a>
                        </td>
                        <td><?php echo esc_entities($requestTypes[$dsar->request_type] ?? $dsar->request_type); ?></td>
                        <td>
                            <?php echo esc_entities($dsar->requestor_name); ?>
                            <?php if ($dsar->requestor_email): ?>
                            <br><small class="text-muted"><?php echo $dsar->requestor_email; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $dsar->received_date; ?></td>
                        <td>
                            <?php echo $dsar->due_date; ?>
                            <?php if ($isOverdue): ?>
                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Overdue'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $statusClasses[$dsar->status] ?? 'secondary'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $dsar->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_entities($dsar->assigned_username ?? '-'); ?></td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'dsarView', 'id' => $dsar->id]); ?>" class="btn btn-sm btn-outline-primary">
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
