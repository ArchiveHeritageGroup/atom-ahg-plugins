<?php decorate_with('layout_1col.php') ?>
<?php slot('title') ?>
<h1><i class="bi bi-graph-up text-primary me-2"></i><?php echo __('Preservation Reports'); ?></h1>
<?php end_slot() ?>

<?php slot('content') ?>

<div class="d-flex justify-content-end mb-4">
    <a href="<?php echo url_for(['module' => 'preservation', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
</div>

<!-- Summary Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="text-primary"><?php echo $stats['checksum_coverage']; ?>%</h3>
                <p class="mb-0"><?php echo __('Checksum Coverage'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="<?php echo $stats['fixity_failures_30d'] > 0 ? 'text-danger' : 'text-success'; ?>">
                    <?php echo $stats['fixity_failures_30d']; ?>
                </h3>
                <p class="mb-0"><?php echo __('Fixity Failures (30d)'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h3 class="<?php echo $stats['formats_at_risk'] > 0 ? 'text-warning' : 'text-success'; ?>">
                    <?php echo $stats['formats_at_risk']; ?>
                </h3>
                <p class="mb-0"><?php echo __('At-Risk Formats'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Objects Without Checksums -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo __('Objects Without Checksums'); ?>
        <span class="badge bg-dark float-end"><?php echo count($objectsWithoutChecksums); ?></span>
    </div>
    <?php if (empty($objectsWithoutChecksums)): ?>
    <div class="card-body text-center text-muted">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="mt-2"><?php echo __('All objects have checksums'); ?></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('ID'); ?></th>
                    <th><?php echo __('Filename'); ?></th>
                    <th><?php echo __('Size'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($objectsWithoutChecksums as $obj): ?>
                <tr>
                    <td><?php echo $obj->id; ?></td>
                    <td><?php echo htmlspecialchars($obj->name ?? 'Unknown'); ?></td>
                    <td><?php echo number_format($obj->byte_size ?? 0); ?> bytes</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Stale Verification -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <i class="bi bi-clock me-2"></i><?php echo __('Stale Verification (>30 days)'); ?>
        <span class="badge bg-light text-dark float-end"><?php echo count($staleVerification); ?></span>
    </div>
    <?php if (empty($staleVerification)): ?>
    <div class="card-body text-center text-muted">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="mt-2"><?php echo __('All verifications are current'); ?></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('ID'); ?></th>
                    <th><?php echo __('Filename'); ?></th>
                    <th><?php echo __('Algorithm'); ?></th>
                    <th><?php echo __('Last Verified'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staleVerification as $obj): ?>
                <tr>
                    <td><?php echo $obj->id; ?></td>
                    <td><?php echo htmlspecialchars($obj->name ?? 'Unknown'); ?></td>
                    <td><?php echo strtoupper($obj->algorithm); ?></td>
                    <td><?php echo date('Y-m-d', strtotime($obj->verified_at)); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- High Risk Formats -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <i class="bi bi-shield-exclamation me-2"></i><?php echo __('High-Risk Format Objects'); ?>
        <span class="badge bg-light text-dark float-end"><?php echo count($highRiskObjects); ?></span>
    </div>
    <?php if (empty($highRiskObjects)): ?>
    <div class="card-body text-center text-muted">
        <i class="bi bi-check-circle fs-1 text-success"></i>
        <p class="mt-2"><?php echo __('No high-risk format objects'); ?></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th><?php echo __('ID'); ?></th>
                    <th><?php echo __('Filename'); ?></th>
                    <th><?php echo __('Format'); ?></th>
                    <th><?php echo __('Risk'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($highRiskObjects as $obj): ?>
                <tr>
                    <td><?php echo $obj->id; ?></td>
                    <td><?php echo htmlspecialchars($obj->name ?? 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($obj->format_name); ?></td>
                    <td><span class="badge bg-danger"><?php echo ucfirst($obj->risk_level); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php end_slot() ?>
