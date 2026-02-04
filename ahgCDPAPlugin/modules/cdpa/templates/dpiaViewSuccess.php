<?php use_helper('Date'); ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'index']); ?>">CDPA</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpia']); ?>">DPIA</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($dpia->name); ?></li>
                </ol>
            </nav>
            <h1><i class="fas fa-clipboard-check me-2"></i><?php echo htmlspecialchars($dpia->name); ?></h1>
        </div>
        <div class="col-auto">
            <a href="<?php echo url_for(['module' => 'cdpa', 'action' => 'dpia']); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Description</h5></div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($dpia->description ?? '-')); ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Necessity & Proportionality Assessment</h5></div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($dpia->necessity_assessment ?? '-')); ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Details</h5></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Assessor</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars($dpia->assessor_name ?? '-'); ?></dd>

                        <dt class="col-sm-4">Created</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y H:i', strtotime($dpia->created_at)); ?></dd>

                        <?php if ($dpia->approved_at): ?>
                        <dt class="col-sm-4">Approved</dt>
                        <dd class="col-sm-8"><?php echo date('j F Y', strtotime($dpia->approved_at)); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Risk Level</h5></div>
                <div class="card-body text-center">
                    <?php
                    $riskColors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                    ?>
                    <span class="badge bg-<?php echo $riskColors[$dpia->risk_level] ?? 'secondary'; ?> fs-4 px-4 py-3">
                        <?php echo ucfirst($dpia->risk_level ?? 'Unknown'); ?>
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">Status</h5></div>
                <div class="card-body text-center">
                    <?php
                    $statusColors = ['draft' => 'secondary', 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
                    ?>
                    <span class="badge bg-<?php echo $statusColors[$dpia->status] ?? 'secondary'; ?> fs-5 px-4 py-2">
                        <?php echo ucfirst($dpia->status ?? 'Draft'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
