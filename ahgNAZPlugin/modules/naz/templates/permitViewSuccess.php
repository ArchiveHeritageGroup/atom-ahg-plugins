<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'index']); ?>">NAZ</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>">Permits</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($permit->permit_number); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-id-card me-2"></i>Research Permit <?php echo htmlspecialchars($permit->permit_number); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'permits']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Researcher</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">
                            <a href="<?php echo url_for(['module' => 'naz', 'action' => 'researcherView', 'id' => $permit->researcher_id]); ?>">
                                <?php echo htmlspecialchars($researcher->first_name . ' ' . $researcher->last_name); ?>
                            </a>
                        </dd>
                        <dt class="col-sm-4">Type</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($researcher->researcher_type); ?></dd>
                        <dt class="col-sm-4">Institution</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($researcher->institution ?? '-'); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Research Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Topic</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($permit->research_topic); ?></dd>
                        <dt class="col-sm-4">Purpose</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($permit->research_purpose ?? '-')); ?></dd>
                        <dt class="col-sm-4">Permit Type</dt>
                        <dd class="col-sm-8"><?php echo ucfirst($permit->permit_type); ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Validity & Fees</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Valid From</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y', strtotime($permit->start_date)); ?></dd>
                        <dt class="col-sm-4">Valid Until</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y', strtotime($permit->end_date)); ?></dd>
                        <dt class="col-sm-4">Fee</dt>
                        <dd class="col-sm-8"><?php echo $permit->fee_currency; ?> <?php echo number_format($permit->fee_amount, 2); ?></dd>
                        <dt class="col-sm-4">Payment Status</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?php echo $permit->fee_paid ? 'success' : 'warning'; ?>">
                                <?php echo $permit->fee_paid ? 'Paid' : 'Pending'; ?>
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            <?php if ($permit->status === 'pending'): ?>
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Review Actions</h5></div>
                <div class="card-body">
                    <form method="post">
                        <button type="submit" name="action" value="approve" class="btn btn-success me-2">
                            <i class="fas fa-check me-1"></i> Approve
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php $statusColors = ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'active' => 'success', 'expired' => 'secondary', 'revoked' => 'dark']; ?>
                    <span class="badge bg-<?php echo $statusColors[$permit->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($permit->status); ?>
                    </span>
                    <?php
                    $daysLeft = floor((strtotime($permit->end_date) - time()) / 86400);
                    if ($permit->status === 'active' && $daysLeft > 0):
                    ?>
                    <p class="mt-2 mb-0"><?php echo $daysLeft; ?> days remaining</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Record</h5></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Created:</strong> <?php echo date('j M Y', strtotime($permit->created_at)); ?></p>
                    <?php if ($permit->approved_date): ?>
                    <p class="mb-0"><strong>Approved:</strong> <?php echo date('j M Y', strtotime($permit->approved_date)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
