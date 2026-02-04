<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">Data Protection Officer</li>
                </ol>
            </nav>
            <h1><i class="fas fa-user-shield me-2"></i>Data Protection Officer</h1>
            <p class="text-muted">DPO appointment under CDPA [Chapter 12:07]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpoEdit']); ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> <?php echo $dpo ? 'Edit' : 'Appoint'; ?> DPO
            </a>
        </div>
    </div>

    <?php if ($dpo): ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">DPO Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8"><strong><?php echo htmlspecialchars($dpo->name); ?></strong></dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><a href="mailto:<?php echo htmlspecialchars($dpo->email); ?>"><?php echo htmlspecialchars($dpo->email); ?></a></dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dpo->phone ?? '-'); ?></dd>

                        <dt class="col-sm-4">Qualifications</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($dpo->qualifications ?? '-')); ?></dd>

                        <dt class="col-sm-4">HIT Certificate Number</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dpo->hit_cert_number ?? '-'); ?></dd>

                        <dt class="col-sm-4">Appointment Date</dt>
                        <dd class="col-sm-8"><?php echo $dpo->appointment_date ? date('j F Y', strtotime($dpo->appointment_date)) : '-'; ?></dd>

                        <dt class="col-sm-4">Term End Date</dt>
                        <dd class="col-sm-8"><?php echo $dpo->term_end_date ? date('j F Y', strtotime($dpo->term_end_date)) : '-'; ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Form DP2 (Notification of DPO)</h5></div>
                <div class="card-body">
                    <?php if ($dpo->form_dp2_submitted): ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Form DP2 Submitted</strong><br>
                            <?php if ($dpo->form_dp2_date): ?>
                                <small>Date: <?php echo date('j F Y', strtotime($dpo->form_dp2_date)); ?></small><br>
                            <?php endif; ?>
                            <?php if ($dpo->form_dp2_ref): ?>
                                <small>Reference: <?php echo htmlspecialchars($dpo->form_dp2_ref); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Form DP2 Not Submitted</strong><br>
                            <small>Data controllers must notify POTRAZ of DPO appointment using Form DP2</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <span class="badge bg-success fs-5 px-4 py-2">Active</span>
                    <?php if ($dpo->appointment_date): ?>
                        <p class="mt-2 mb-0 small text-muted">
                            Since <?php echo date('j M Y', strtotime($dpo->appointment_date)); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">DPO Requirements</h5></div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Expert knowledge of data protection</li>
                        <li>Knowledge of organization operations</li>
                        <li>Independent position</li>
                        <li>Direct reporting to management</li>
                        <li>HIT certification recommended</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-user-shield fa-4x text-muted mb-3"></i>
            <h4>No Data Protection Officer Appointed</h4>
            <p class="text-muted mb-4">Data controllers processing personal data must appoint a DPO under the CDPA.</p>
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpoEdit']); ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Appoint DPO
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>
