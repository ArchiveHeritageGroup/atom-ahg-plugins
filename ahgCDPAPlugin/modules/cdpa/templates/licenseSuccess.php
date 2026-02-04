<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item active">POTRAZ License</li>
                </ol>
            </nav>
            <h1><i class="fas fa-id-card me-2"></i>POTRAZ License</h1>
            <p class="text-muted">Data Controller Registration under CDPA [Chapter 12:07]</p>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'licenseEdit']); ?>" class="btn btn-primary">
                <i class="fas fa-edit me-1"></i> <?php echo $license ? 'Edit' : 'Register'; ?> License
            </a>
        </div>
    </div>

    <?php if ($license): ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">License Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">License Number</dt>
                        <dd class="col-sm-8"><strong><?php echo htmlspecialchars($license->license_number); ?></strong></dd>

                        <dt class="col-sm-4">Organization Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($license->organization_name); ?></dd>

                        <dt class="col-sm-4">Tier</dt>
                        <dd class="col-sm-8"><span class="badge bg-info fs-6"><?php echo strtoupper($license->tier); ?></span></dd>

                        <dt class="col-sm-4">Registration Date</dt>
                        <dd class="col-sm-8"><?php echo $license->registration_date ? date('j F Y', strtotime($license->registration_date)) : '-'; ?></dd>

                        <dt class="col-sm-4">Issue Date</dt>
                        <dd class="col-sm-8"><?php echo $license->issue_date ? date('j F Y', strtotime($license->issue_date)) : '-'; ?></dd>

                        <dt class="col-sm-4">Expiry Date</dt>
                        <dd class="col-sm-8"><?php echo $license->expiry_date ? date('j F Y', strtotime($license->expiry_date)) : '-'; ?></dd>

                        <dt class="col-sm-4">POTRAZ Reference</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($license->potraz_ref ?? '-'); ?></dd>

                        <dt class="col-sm-4">Data Subjects Count</dt>
                        <dd class="col-sm-8"><?php echo $license->data_subjects_count ? number_format($license->data_subjects_count) : '-'; ?></dd>

                        <?php if ($license->notes): ?>
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($license->notes)); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['active' => 'success', 'expiring_soon' => 'warning', 'expired' => 'danger'];
                    $color = $statusColors[$licenseStatus] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?> fs-4 px-4 py-3">
                        <?php echo ucfirst(str_replace('_', ' ', $licenseStatus ?? 'Unknown')); ?>
                    </span>

                    <?php if ($license->expiry_date): ?>
                        <?php
                        $daysRemaining = (int) ((strtotime($license->expiry_date) - time()) / 86400);
                        ?>
                        <p class="mt-3 mb-0">
                            <?php if ($daysRemaining > 0): ?>
                                <strong><?php echo $daysRemaining; ?></strong> days remaining
                            <?php elseif ($daysRemaining === 0): ?>
                                <span class="text-danger">Expires today</span>
                            <?php else: ?>
                                <span class="text-danger">Expired <?php echo abs($daysRemaining); ?> days ago</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">License Tiers</h5></div>
                <div class="card-body small">
                    <p class="mb-2"><strong>Tier 1:</strong> Small Scale (&lt;1,000 subjects)</p>
                    <p class="mb-2"><strong>Tier 2:</strong> Medium Scale (1,000-10,000)</p>
                    <p class="mb-0"><strong>Tier 3:</strong> Large Scale (&gt;10,000 subjects)</p>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-id-card fa-4x text-muted mb-3"></i>
            <h4>No POTRAZ License Registered</h4>
            <p class="text-muted mb-4">Data controllers must register with POTRAZ under the Cyber and Data Protection Act.</p>
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'licenseEdit']); ?>" class="btn btn-primary btn-lg">
                <i class="fas fa-plus me-2"></i>Register License
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>
