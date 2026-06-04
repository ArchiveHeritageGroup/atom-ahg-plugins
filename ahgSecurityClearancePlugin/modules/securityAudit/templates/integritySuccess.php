<?php $r = $sf_data->getRaw('result'); $intact = !empty($r['intact']); ?>
<div class="container py-4" style="max-width: 720px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <span class="h2"><i class="fas fa-link me-2"></i><?php echo __('Audit trail integrity'); ?></span>
        <a href="<?php echo url_for(['module' => 'securityAudit', 'action' => 'dashboard']); ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to dashboard'); ?></a>
    </div>
    <p class="text-muted"><?php echo __('Every entry in the security access log is hash-linked to the one before it. This check recomputes the whole chain — any altered, deleted or inserted entry breaks it and is reported below.'); ?></p>

    <?php if ($intact): ?>
    <div class="card border-success">
        <div class="card-body text-center py-5">
            <div class="display-1 text-success mb-3"><i class="fas fa-shield-check"></i></div>
            <h3 class="text-success"><?php echo __('Chain intact'); ?></h3>
            <p class="mb-0"><?php echo __('%1% of %2% entries verified — no tampering detected.', ['%1%' => (int) $r['checked'], '%2%' => (int) $r['total']]); ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="card border-danger">
        <div class="card-body text-center py-5">
            <div class="display-1 text-danger mb-3"><i class="fas fa-triangle-exclamation"></i></div>
            <h3 class="text-danger"><?php echo __('Tampering detected'); ?></h3>
            <p class="mb-1"><?php echo __('The chain breaks at entry id %1%.', ['%1%' => (int) $r['broken_id']]); ?></p>
            <p class="text-muted mb-1"><?php echo htmlspecialchars((string) $r['reason']); ?></p>
            <p class="small text-muted mb-0"><?php echo __('%1% of %2% entries verified before the break.', ['%1%' => (int) $r['checked'], '%2%' => (int) $r['total']]); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-muted small mt-3">
        <i class="fas fa-terminal me-1"></i><?php echo __('Also available from the CLI:'); ?> <code>php symfony security:audit-verify</code>
    </div>
</div>
