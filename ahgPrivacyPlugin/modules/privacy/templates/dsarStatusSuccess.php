<?php use_helper('Text'); ?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'privacy', 'action' => 'index']); ?>"><?php echo __('Privacy'); ?></a></li>
            <li class="breadcrumb-item active"><?php echo __('Check Status'); ?></li>
        </ol>
    </nav>

    <h1 class="h2 mb-4"><i class="fas fa-search me-2"></i><?php echo __('Check Request Status'); ?></h1>

    <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
    <?php endif; ?>

    <?php if (isset($dsar)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?php echo __('Request Details'); ?>: <?php echo esc_entities($dsar->reference_number); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong><?php echo __('Status:'); ?></strong>
                        <?php
                        $statusClasses = [
                            'received' => 'secondary',
                            'verified' => 'info',
                            'in_progress' => 'primary',
                            'pending_info' => 'warning',
                            'completed' => 'success',
                            'rejected' => 'danger',
                            'withdrawn' => 'dark'
                        ];
                        $statusClass = $statusClasses[$dsar->status] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $dsar->status)); ?></span>
                    </p>
                    <p><strong><?php echo __('Request Type:'); ?></strong> <?php echo ucfirst(str_replace('_', ' ', $dsar->request_type)); ?></p>
                    <p><strong><?php echo __('Submitted:'); ?></strong> <?php echo $dsar->received_date; ?></p>
                    <p><strong><?php echo __('Due Date:'); ?></strong> <?php echo $dsar->due_date; ?></p>
                </div>
                <div class="col-md-6">
                    <?php if ($dsar->status === 'completed'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i><?php echo __('Your request has been completed.'); ?>
                    </div>
                    <?php elseif ($dsar->status === 'rejected'): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i><?php echo __('Your request was not approved.'); ?>
                        <?php if ($dsar->refusal_reason): ?>
                        <p class="mb-0 mt-2"><strong><?php echo __('Reason:'); ?></strong> <?php echo esc_entities($dsar->refusal_reason); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-clock me-2"></i><?php echo __('Your request is being processed.'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="get" action="<?php echo url_for(['module' => 'privacy', 'action' => 'dsarStatus']); ?>">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label"><?php echo __('Reference Number'); ?></label>
                        <input type="text" name="reference" class="form-control" placeholder="DSAR-202501-0001" value="<?php echo esc_entities($sf_request->getParameter('reference')); ?>">
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label"><?php echo __('Email Address'); ?></label>
                        <input type="email" name="email" class="form-control" value="<?php echo esc_entities($sf_request->getParameter('email')); ?>">
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i><?php echo __('Check'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
