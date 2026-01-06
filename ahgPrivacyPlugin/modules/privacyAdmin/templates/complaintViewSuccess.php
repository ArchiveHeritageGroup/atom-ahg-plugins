<?php use_helper('Text'); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h2 mb-0"><?php echo esc_entities($complaint->reference_number); ?></h1>
                <small class="text-muted"><?php echo ucwords(str_replace('_', ' ', $complaint->complaint_type)); ?></small>
            </div>
        </div>
        <?php
        $statusClasses = [
            'received' => 'secondary', 'investigating' => 'primary', 
            'resolved' => 'success', 'escalated' => 'danger', 'closed' => 'dark'
        ];
        ?>
        <span class="badge bg-<?php echo $statusClasses[$complaint->status] ?? 'secondary'; ?> fs-6">
            <?php echo ucfirst($complaint->status); ?>
        </span>
    </div>

    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Complaint Details'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong><?php echo __('Complaint Type:'); ?></strong><br>
                            <?php echo ucwords(str_replace('_', ' ', $complaint->complaint_type)); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong><?php echo __('Date of Incident:'); ?></strong><br>
                            <?php echo $complaint->date_of_incident ? date('d M Y', strtotime($complaint->date_of_incident)) : '-'; ?></p>
                        </div>
                    </div>
                    <p><strong><?php echo __('Description:'); ?></strong></p>
                    <p class="bg-light p-3 rounded"><?php echo nl2br(esc_entities($complaint->description)); ?></p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Complainant'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong><?php echo __('Name:'); ?></strong><br><?php echo esc_entities($complaint->complainant_name); ?></p>
                            <p><strong><?php echo __('Email:'); ?></strong><br>
                            <a href="mailto:<?php echo $complaint->complainant_email; ?>"><?php echo $complaint->complainant_email; ?></a></p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($complaint->complainant_phone): ?>
                            <p><strong><?php echo __('Phone:'); ?></strong><br><?php echo esc_entities($complaint->complainant_phone); ?></p>
                            <?php endif; ?>
                            <p><strong><?php echo __('Submitted:'); ?></strong><br><?php echo date('d M Y H:i', strtotime($complaint->created_at)); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($complaint->resolution): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><?php echo __('Resolution'); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(esc_entities($complaint->resolution)); ?></p>
                    <?php if ($complaint->resolved_date): ?>
                    <p class="text-muted mb-0"><small><?php echo __('Resolved:'); ?> <?php echo date('d M Y', strtotime($complaint->resolved_date)); ?></small></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo __('Update Status'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'complaintUpdate', 'id' => $complaint->id]); ?>">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Status'); ?></label>
                            <select name="status" class="form-select">
                                <option value="received" <?php echo $complaint->status === 'received' ? 'selected' : ''; ?>><?php echo __('Received'); ?></option>
                                <option value="investigating" <?php echo $complaint->status === 'investigating' ? 'selected' : ''; ?>><?php echo __('Investigating'); ?></option>
                                <option value="resolved" <?php echo $complaint->status === 'resolved' ? 'selected' : ''; ?>><?php echo __('Resolved'); ?></option>
                                <option value="escalated" <?php echo $complaint->status === 'escalated' ? 'selected' : ''; ?>><?php echo __('Escalated'); ?></option>
                                <option value="closed" <?php echo $complaint->status === 'closed' ? 'selected' : ''; ?>><?php echo __('Closed'); ?></option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Resolution Notes'); ?></label>
                            <textarea name="resolution" class="form-control" rows="4"><?php echo esc_entities($complaint->resolution ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?php echo __('Update'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
