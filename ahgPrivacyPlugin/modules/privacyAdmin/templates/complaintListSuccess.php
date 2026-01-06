<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="h2"><i class="fas fa-exclamation-circle me-2 text-warning"></i><?php echo __('Privacy Complaints'); ?></span>
        </div>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value=""><?php echo __('All Statuses'); ?></option>
                        <option value="received"><?php echo __('Received'); ?></option>
                        <option value="investigating"><?php echo __('Investigating'); ?></option>
                        <option value="resolved"><?php echo __('Resolved'); ?></option>
                        <option value="escalated"><?php echo __('Escalated'); ?></option>
                        <option value="closed"><?php echo __('Closed'); ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary"><?php echo __('Filter'); ?></button>
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
                        <th><?php echo __('Complainant'); ?></th>
                        <th><?php echo __('Date'); ?></th>
                        <th><?php echo __('Status'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($complaints->isEmpty()): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4"><?php echo __('No complaints'); ?></td></tr>
                    <?php else: ?>
                    <?php foreach ($complaints as $c): ?>
                    <?php
                    $statusClasses = [
                        'received' => 'secondary', 'investigating' => 'primary', 
                        'resolved' => 'success', 'escalated' => 'danger', 'closed' => 'dark'
                    ];
                    ?>
                    <tr>
                        <td><strong><?php echo esc_entities($c->reference_number); ?></strong></td>
                        <td><?php echo esc_entities($complaintTypes[$c->complaint_type] ?? $c->complaint_type); ?></td>
                        <td>
                            <?php echo esc_entities($c->complainant_name); ?>
                            <?php if ($c->complainant_email): ?>
                            <br><small class="text-muted"><?php echo $c->complainant_email; ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y', strtotime($c->created_at)); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $statusClasses[$c->status] ?? 'secondary'; ?>">
                                <?php echo ucfirst($c->status); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintView', 'id' => $c->id]); ?>" class="btn btn-sm btn-outline-primary">
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
