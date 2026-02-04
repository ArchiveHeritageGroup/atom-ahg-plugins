<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'index']); ?>">NMMZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permits']); ?>">Export Permits</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($permit->permit_number ?? 'EXP-' . $permit->id); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-file-export me-2"></i>Export Permit <?php echo htmlspecialchars($permit->permit_number ?? 'EXP-' . $permit->id); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'permits']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Permits
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Applicant Details -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Applicant Information</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Applicant Name</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($permit->applicant_name); ?></dd>

                        <dt class="col-sm-4">Applicant Type</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($permit->applicant_type ?? '-'); ?></dd>

                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($permit->applicant_address ?? '-')); ?></dd>

                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($permit->applicant_email ?? '-'); ?></dd>

                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($permit->applicant_phone ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Object Details -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Object Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php if ($permit->antiquity_id): ?>
                        <dt class="col-sm-4">Linked Antiquity</dt>
                        <dd class="col-sm-8">
                            <a href="<?php echo url_for(['module' => 'nmmz', 'action' => 'antiquityView', 'id' => $permit->antiquity_id]); ?>">
                                ANT-<?php echo $permit->antiquity_id; ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($permit->object_description ?? '-')); ?></dd>

                        <dt class="col-sm-4">Quantity</dt>
                        <dd class="col-sm-8"><?php echo $permit->quantity ?? 1; ?></dd>

                        <dt class="col-sm-4">Estimated Value</dt>
                        <dd class="col-sm-8"><?php echo $permit->estimated_value ? '$' . number_format($permit->estimated_value, 2) : '-'; ?></dd>
                    </dl>
                </div>
            </div>

            <!-- Export Details -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Export Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Export Purpose</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($permit->export_purpose ?? '-'); ?></dd>

                        <dt class="col-sm-4">Purpose Details</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($permit->purpose_details ?? '-')); ?></dd>

                        <dt class="col-sm-4">Destination Country</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($permit->destination_country ?? '-'); ?></dd>

                        <dt class="col-sm-4">Destination Institution</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($permit->destination_institution ?? '-'); ?></dd>

                        <dt class="col-sm-4">Proposed Export Date</dt>
                        <dd class="col-sm-8"><?php echo $permit->export_date_proposed ? date('j F Y', strtotime($permit->export_date_proposed)) : '-'; ?></dd>

                        <dt class="col-sm-4">Return Date</dt>
                        <dd class="col-sm-8"><?php echo $permit->return_date ? date('j F Y', strtotime($permit->return_date)) : 'Not specified (permanent)'; ?></dd>
                    </dl>
                </div>
            </div>

            <?php if ('approved' === $permit->status && $permit->conditions): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white"><h5 class="mb-0">Approval Conditions</h5></div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($permit->conditions)); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ('rejected' === $permit->status && $permit->rejection_reason): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white"><h5 class="mb-0">Rejection Reason</h5></div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($permit->rejection_reason)); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Status -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'expired' => 'secondary'];
                    $color = $statusColors[$permit->status] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($permit->status ?? 'Pending'); ?>
                    </span>

                    <?php if ('approved' === $permit->status && $permit->expiry_date): ?>
                    <p class="mt-2 mb-0">
                        <small class="text-muted">Valid until: <?php echo date('j F Y', strtotime($permit->expiry_date)); ?></small>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Admin Actions (for pending permits) -->
            <?php if ('pending' === $permit->status): ?>
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Review Actions</h5></div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Conditions (if approving)</label>
                            <textarea name="conditions" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="action_type" value="approve" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-check me-1"></i> Approve
                        </button>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Rejection Reason</label>
                            <textarea name="rejection_reason" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" name="action_type" value="reject" class="btn btn-danger w-100">
                            <i class="fas fa-times me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Record Info -->
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Record Info</h5></div>
                <div class="card-body">
                    <small class="text-muted">
                        <p class="mb-1"><strong>Applied:</strong> <?php echo $permit->created_at ? date('j M Y H:i', strtotime($permit->created_at)) : '-'; ?></p>
                        <?php if ($permit->review_date): ?>
                        <p class="mb-1"><strong>Reviewed:</strong> <?php echo date('j M Y', strtotime($permit->review_date)); ?></p>
                        <?php endif; ?>
                        <p class="mb-0"><strong>Updated:</strong> <?php echo $permit->updated_at ? date('j M Y H:i', strtotime($permit->updated_at)) : '-'; ?></p>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
