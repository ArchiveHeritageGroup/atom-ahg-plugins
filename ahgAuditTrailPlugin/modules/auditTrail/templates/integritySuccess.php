<?php $r = $sf_data->getRaw('result'); $sealed = !empty($r['sealed']); $intact = !empty($r['intact']); ?>
<div class="container py-4" style="max-width: 760px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="h2"><i class="fas fa-link me-2"></i><?php echo __('Audit trail integrity'); ?></span>
        <a href="<?php echo url_for('@auditTrail_browse'); ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to audit log'); ?></a>
    </div>
    <p class="text-muted"><?php echo __('Each audit entry from the seal point on is SHA-256 hash-linked to the one before it. This check recomputes the chain — any altered, deleted or inserted entry breaks it and is reported below.'); ?></p>

    <?php if (!$sealed): ?>
    <div class="alert alert-secondary"><?php echo __('The chain is not sealed yet. Run'); ?> <code>php symfony audit:chain --seal</code>.</div>
    <?php elseif ($intact): ?>
    <div class="card border-success"><div class="card-body text-center py-5">
        <div class="display-1 text-success mb-3"><i class="fas fa-shield-halved"></i></div>
        <h3 class="text-success"><?php echo __('Chain intact'); ?></h3>
        <p class="mb-0"><?php echo __('%1% chained entries verified — no tampering detected.', ['%1%' => (int) $r['checked']]); ?></p>
    </div></div>
    <?php else: ?>
    <div class="card border-danger"><div class="card-body text-center py-5">
        <div class="display-1 text-danger mb-3"><i class="fas fa-triangle-exclamation"></i></div>
        <h3 class="text-danger"><?php echo __('Tampering detected'); ?></h3>
        <p class="mb-1"><?php echo __('The chain breaks at entry id %1%.', ['%1%' => (int) $r['broken_id']]); ?></p>
        <p class="text-muted mb-1"><?php echo htmlspecialchars((string) $r['reason']); ?></p>
        <p class="small text-muted mb-0"><?php echo __('%1% of %2% chained entries verified before the break.', ['%1%' => (int) $r['checked'], '%2%' => (int) $r['total']]); ?></p>
    </div></div>
    <?php endif; ?>

    <div class="text-muted small mt-3"><i class="fas fa-terminal me-1"></i><?php echo __('Also from the CLI:'); ?> <code>php symfony audit:chain</code></div>
</div>
