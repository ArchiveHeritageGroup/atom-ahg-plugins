<?php use_helper('Text'); ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentList']); ?>" class="btn btn-outline-secondary btn-sm me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="h2 mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Consent Record'); ?> #<?php echo $consent->id; ?></h1>
                <small class="text-muted"><?php echo esc_entities($consent->data_subject_id); ?></small>
            </div>
        </div>
        <div>
            <span class="badge bg-<?php echo ($consent->status ?? 'active') === 'active' ? 'success' : 'secondary'; ?> fs-6 me-2"><?php echo ucfirst($consent->status ?? 'active'); ?></span>
            <span class="badge bg-<?php echo ($consent->consent_given ?? 0) ? 'success' : 'danger'; ?> fs-6"><?php echo ($consent->consent_given ?? 0) ? __('Consent Given') : __('No Consent'); ?></span>
            <a href="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentEdit', 'id' => $consent->id]); ?>" class="btn btn-primary ms-3"><i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?></a>
        </div>
    </div>
    <?php if ($sf_user->hasFlash('success')): ?>
    <div class="alert alert-success"><?php echo $sf_user->getFlash('success'); ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-user me-2"></i><?php echo __('Data Subject'); ?></h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4"><?php echo __('Subject Identifier'); ?></dt>
                        <dd class="col-sm-8"><?php echo esc_entities($consent->data_subject_id); ?></dd>
                        <?php if ($consent->subject_name): ?>
                        <dt class="col-sm-4"><?php echo __('Name'); ?></dt>
                        <dd class="col-sm-8"><?php echo esc_entities($consent->subject_name); ?></dd>
                        <?php endif; ?>
                        <?php if ($consent->subject_email): ?>
                        <dt class="col-sm-4"><?php echo __('Email'); ?></dt>
                        <dd class="col-sm-8"><?php echo esc_entities($consent->subject_email); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-check-circle me-2"></i><?php echo __('Consent Details'); ?></h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4"><?php echo __('Purpose'); ?></dt>
                        <dd class="col-sm-8"><?php echo esc_entities($consent->purpose); ?></dd>
                        <dt class="col-sm-4"><?php echo __('Consent Given'); ?></dt>
                        <dd class="col-sm-8"><?php echo ($consent->consent_given ?? 0) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></dd>
                        <dt class="col-sm-4"><?php echo __('Consent Method'); ?></dt>
                        <dd class="col-sm-8"><?php echo ucfirst($consent->consent_method ?? 'form'); ?></dd>
                        <dt class="col-sm-4"><?php echo __('Consent Date'); ?></dt>
                        <dd class="col-sm-8"><?php echo $consent->consent_date ?? '-'; ?></dd>
                        <?php if ($consent->source): ?>
                        <dt class="col-sm-4"><?php echo __('Source'); ?></dt>
                        <dd class="col-sm-8"><?php echo esc_entities($consent->source); ?></dd>
                        <?php endif; ?>
                        <dt class="col-sm-4"><?php echo __('Jurisdiction'); ?></dt>
                        <dd class="col-sm-8"><?php echo strtoupper($consent->jurisdiction ?? 'popia'); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i><?php echo __('Status'); ?></h5></div>
                <div class="card-body">
                    <p class="mb-2"><strong><?php echo __('Status'); ?>:</strong> <span class="badge bg-<?php echo ($consent->status ?? 'active') === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($consent->status ?? 'active'); ?></span></p>
                    <p class="mb-2"><strong><?php echo __('Created'); ?>:</strong> <?php echo $consent->created_at; ?></p>
                    <?php if ($consent->withdrawal_date): ?>
                    <p class="mb-0"><strong><?php echo __('Withdrawn'); ?>:</strong> <?php echo $consent->withdrawal_date; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (($consent->status ?? 'active') === 'active' && $consent->consent_given): ?>
            <div class="card">
                <div class="card-header bg-warning"><h5 class="mb-0"><i class="fas fa-ban me-2"></i><?php echo __('Withdraw Consent'); ?></h5></div>
                <div class="card-body">
                    <form method="post" action="<?php echo url_for(['module' => 'privacyAdmin', 'action' => 'consentWithdraw', 'id' => $consent->id]); ?>" onsubmit="return confirm('Are you sure?');">
                        <div class="mb-3">
                            <label class="form-label"><?php echo __('Reason'); ?></label>
                            <textarea name="reason" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100"><i class="fas fa-ban me-1"></i><?php echo __('Withdraw'); ?></button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
