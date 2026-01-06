<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-file-contract me-2"></i><?php echo __('PAIA Requests'); ?></span>
            <small class="text-muted d-block mt-1"><?php echo __('Promotion of Access to Information Act (South Africa)'); ?></small>
        </div>
        <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'paiaAdd']); ?>" class="btn btn-warning">
            <i class="fas fa-plus me-1"></i><?php echo __('New PAIA Request'); ?>
        </a>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <!-- PAIA Section Reference -->
    <div class="row mb-4">
        <?php foreach ($paiaTypes as $code => $info): ?>
        <div class="col-md-4 col-lg-2 mb-2">
            <div class="card h-100">
                <div class="card-body text-center py-2">
                    <small class="text-muted d-block"><?php echo $info['code']; ?></small>
                    <small><?php echo $info['label']; ?></small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo __('Reference'); ?></th>
                        <th><?php echo __('Section'); ?></th>
                        <th><?php echo __('Requestor'); ?></th>
                        <th><?php echo __('Received'); ?></th>
                        <th><?php echo __('Due Date'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th><?php echo __('Fee'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests->isEmpty()): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?php echo __('No PAIA requests'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                    <?php
                    $isOverdue = strtotime($req->due_date) < time() && !in_array($req->status, ['granted', 'partially_granted', 'refused', 'transferred']);
                    $statusClasses = [
                        'received' => 'secondary', 'processing' => 'primary', 'granted' => 'success',
                        'partially_granted' => 'info', 'refused' => 'danger', 'transferred' => 'warning', 'appealed' => 'dark'
                    ];
                    ?>
                    <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
                        <td><strong><?php echo esc_entities($req->reference_number); ?></strong></td>
                        <td><?php echo $paiaTypes[$req->paia_section]['code'] ?? $req->paia_section; ?></td>
                        <td>
                            <?php echo esc_entities($req->requestor_name); ?>
                            <?php if ($req->requestor_email): ?>
                            <br><small class="text-muted"><?php echo $req->requestor_email; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $req->received_date; ?></td>
                        <td>
                            <?php echo $req->due_date; ?>
                            <?php if ($isOverdue): ?>
                            <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo __('Overdue'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $statusClasses[$req->status] ?? 'secondary'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $req->status)); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($req->fee_deposit || $req->fee_access): ?>
                            R<?php echo number_format(($req->fee_deposit ?? 0) + ($req->fee_access ?? 0), 2); ?>
                            <?php if ($req->fee_paid): ?>
                            <span class="text-success"><i class="fas fa-check"></i></span>
                            <?php endif; ?>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'paiaView', 'id' => $req->id]); ?>" class="btn btn-sm btn-outline-primary">
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

    <!-- PAIA Info Card -->
    <div class="card mt-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('PAIA Requirements'); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6><?php echo __('Response Time'); ?></h6>
                    <p class="mb-0"><strong>30 days</strong> from receipt (extendable by 30 days with notice)</p>
                </div>
                <div class="col-md-4">
                    <h6><?php echo __('Fees'); ?></h6>
                    <p class="mb-0">Request fee + access fee (based on search time and reproduction)</p>
                </div>
                <div class="col-md-4">
                    <h6><?php echo __('Appeals'); ?></h6>
                    <p class="mb-0">Internal appeal within 60 days, then to court within 180 days</p>
                </div>
            </div>
        </div>
    </div>
</div>
