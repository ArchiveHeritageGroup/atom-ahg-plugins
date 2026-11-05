<?php
$loan = $sf_data->getRaw('loan');
$allowed = $sf_data->getRaw('allowed') ?: [];
$label = fn ($s) => __(ucfirst(str_replace('_', ' ', $s)));
$badge = ['requested' => 'bg-info text-dark', 'approved' => 'bg-primary', 'declined' => 'bg-danger', 'in_transit' => 'bg-warning text-dark', 'returned' => 'bg-success', 'cancelled' => 'bg-secondary'][$loan->status] ?? 'bg-secondary';
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-arrow-left-right me-2"></i><?php echo __('Loan request') ?> #<?php echo (int) $loan->id ?></h1>
    <a href="<?php echo url_for(['module' => 'federation', 'action' => 'loans']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Back to loans') ?></a>
  </div>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><?php echo __('Request detail') ?></span>
          <span class="badge <?php echo $badge ?>"><?php echo $label($loan->status) ?></span>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-3"><?php echo __('Item') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities((string) ($loan->item_title ?: __('(untitled item)'))) ?>
              <?php if ($loan->item_slug): ?> <a class="small ms-1" href="/<?php echo esc_entities($loan->item_slug) ?>" target="_blank"><?php echo __('Open record') ?></a><?php endif ?></dd>
            <dt class="col-sm-3"><?php echo __('Direction') ?></dt>
            <dd class="col-sm-9"><?php echo 'incoming' === $loan->direction ? __('Incoming: they borrow from us') : __('Outgoing: we borrow from them') ?></dd>
            <dt class="col-sm-3"><?php echo __('Requesting') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($loan->requesting_name) ?></dd>
            <dt class="col-sm-3"><?php echo __('Holding') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities($loan->holding_name) ?></dd>
            <dt class="col-sm-3"><?php echo __('Purpose') ?></dt>
            <dd class="col-sm-9"><?php echo esc_entities((string) ($loan->purpose ?: '-')) ?></dd>
            <dt class="col-sm-3"><?php echo __('Loan window') ?></dt>
            <dd class="col-sm-9"><?php echo ($loan->needed_from || $loan->needed_to) ? esc_entities(($loan->needed_from ?: '?') . ' to ' . ($loan->needed_to ?: '?')) : '-' ?></dd>
            <dt class="col-sm-3"><?php echo __('Notes') ?></dt>
            <dd class="col-sm-9"><?php if ($loan->notes): ?><pre class="bg-light border rounded p-2 small mb-0" style="white-space: pre-wrap"><?php echo esc_entities($loan->notes) ?></pre><?php else: ?>-<?php endif ?></dd>
          </dl>
        </div>
        <div class="card-footer small text-muted">
          <?php echo __('Created') ?>: <?php echo esc_entities((string) $loan->created_at) ?>
          <?php if ($loan->decided_at): ?> &middot; <?php echo __('Last decision') ?>: <?php echo esc_entities((string) ($loan->decided_by ?: __('unknown'))) ?> (<?php echo esc_entities($loan->decided_at) ?>)<?php endif ?>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><?php echo __('Workflow') ?></div>
        <div class="card-body">
          <?php if (empty($allowed)): ?>
            <p class="text-muted small mb-0"><?php echo __('This request is in a final state. No further changes are available.') ?></p>
          <?php else: ?>
            <form method="post" action="<?php echo url_for(['module' => 'federation', 'action' => 'loan', 'id' => $loan->id]) ?>">
              <?php echo \AtomFramework\Services\CsrfService::renderHiddenField() ?>
              <div class="mb-3">
                <label class="form-label small" for="to"><?php echo __('Move to') ?></label>
                <select class="form-select form-select-sm" id="to" name="to">
                  <?php foreach ($allowed as $next): ?><option value="<?php echo $next ?>"><?php echo $label($next) ?></option><?php endforeach ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small" for="note"><?php echo __('Note (optional)') ?></label>
                <textarea class="form-control form-control-sm" id="note" name="note" rows="3" maxlength="2048"></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-100"><?php echo __('Update status') ?></button>
            </form>
          <?php endif ?>
        </div>
      </div>
    </div>
  </div>
</div>
