<?php
$loans = $sf_data->getRaw('loans') ?: [];
$counts = $sf_data->getRaw('counts') ?: [];
$report = $sf_data->getRaw('report') ?: [];
$statuses = $sf_data->getRaw('statuses') ?: [];
$label = fn ($s) => __(ucfirst(str_replace('_', ' ', $s)));
$badge = ['requested' => 'bg-info text-dark', 'approved' => 'bg-primary', 'declined' => 'bg-danger', 'in_transit' => 'bg-warning text-dark', 'returned' => 'bg-success', 'cancelled' => 'bg-secondary'];
$loansUrl = url_for(['module' => 'federation', 'action' => 'loans']);
$filterUrl = fn ($status, $direction) => $loansUrl . '?' . http_build_query(array_filter(['status' => $status, 'direction' => $direction]));
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-arrow-left-right me-2"></i><?php echo __('Loan requests') ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'union']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Union catalogue') ?></a>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Federation') ?></a>
      <a href="<?php echo url_for(['module' => 'federation', 'action' => 'loanNew']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i><?php echo __('New loan request') ?></a>
    </div>
  </div>

  <div class="row mb-4">
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Incoming') ?></div><div class="display-6"><?php echo (int) ($report['incoming'] ?? 0) ?></div><div class="small text-muted"><?php echo __('Others asking to borrow from us') ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Outgoing') ?></div><div class="display-6"><?php echo (int) ($report['outgoing'] ?? 0) ?></div><div class="small text-muted"><?php echo __('We ask to borrow') ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Approval rate') ?></div><div class="display-6"><?php echo null === ($report['rate_pct'] ?? null) ? '-' : (int) $report['rate_pct'] . '%' ?></div><div class="small text-muted"><?php echo (int) ($report['approved'] ?? 0) ?> <?php echo __('approved') ?>, <?php echo (int) ($report['declined'] ?? 0) ?> <?php echo __('declined') ?></div></div></div></div>
    <div class="col-md-3 mb-2"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase"><?php echo __('Days to decide') ?></div><div class="display-6"><?php echo null === ($report['avg_days'] ?? null) ? '-' : esc_entities((string) $report['avg_days']) ?></div><div class="small text-muted"><?php echo __('Average, request to last decision') ?></div></div></div></div>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <a class="btn btn-sm <?php echo '' === $status ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?php echo $filterUrl('', $direction) ?>"><?php echo __('All') ?> <span class="badge bg-light text-dark"><?php echo (int) ($counts['all'] ?? 0) ?></span></a>
    <?php foreach ($statuses as $s): ?>
      <a class="btn btn-sm <?php echo $status === $s ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?php echo $filterUrl($s, $direction) ?>"><?php echo $label($s) ?> <span class="badge bg-light text-dark"><?php echo (int) ($counts[$s] ?? 0) ?></span></a>
    <?php endforeach ?>
    <span class="ms-3 small text-muted"><?php echo __('Direction') ?>:</span>
    <?php foreach (['' => __('Both'), 'incoming' => __('Incoming'), 'outgoing' => __('Outgoing')] as $d => $dl): ?>
      <a class="btn btn-sm <?php echo $direction === $d ? 'btn-secondary' : 'btn-outline-secondary' ?>" href="<?php echo $filterUrl($status, $d) ?>"><?php echo $dl ?></a>
    <?php endforeach ?>
  </div>

  <div class="card mb-4">
    <div class="card-body p-0 table-responsive">
      <table class="table table-hover table-sm mb-0 align-middle">
        <thead class="table-light"><tr><th>#</th><th><?php echo __('Item') ?></th><th><?php echo __('Direction') ?></th><th><?php echo __('Partner') ?></th><th><?php echo __('Loan window') ?></th><th><?php echo __('Status') ?></th><th></th></tr></thead>
        <tbody>
        <?php if (empty($loans)): ?>
          <tr><td colspan="7" class="text-muted p-3"><?php echo __('No loan requests yet. Use "Request loan" on a harvested record in the union catalogue, or start a new request.') ?></td></tr>
        <?php else: foreach ($loans as $l): ?>
          <tr>
            <td><?php echo (int) $l->id ?></td>
            <td><?php echo esc_entities((string) ($l->item_title ?: $l->item_ref ?: __('(untitled item)'))) ?></td>
            <td><?php echo 'incoming' === $l->direction ? __('Incoming') : __('Outgoing') ?></td>
            <td><?php echo esc_entities('incoming' === $l->direction ? $l->requesting_name : $l->holding_name) ?></td>
            <td class="small"><?php echo ($l->needed_from || $l->needed_to) ? esc_entities(($l->needed_from ?: '?') . ' to ' . ($l->needed_to ?: '?')) : '-' ?></td>
            <td><span class="badge <?php echo $badge[$l->status] ?? 'bg-secondary' ?>"><?php echo $label($l->status) ?></span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'federation', 'action' => 'loan', 'id' => $l->id]) ?>"><?php echo __('Open') ?></a></td>
          </tr>
        <?php endforeach ?>
        <?php endif ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if (!empty($report['partners'])): ?>
  <div class="card">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Busiest partners') ?></h5></div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0"><tbody>
        <?php foreach ($report['partners'] as $name => $n): ?>
          <tr><td><?php echo esc_entities((string) $name) ?></td><td class="text-end"><?php echo (int) $n ?></td></tr>
        <?php endforeach ?>
      </tbody></table>
    </div>
  </div>
  <?php endif ?>
</div>
