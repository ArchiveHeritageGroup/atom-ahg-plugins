<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Payouts'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Payouts'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Manage Payouts'); ?></h1>

<!-- Pending payouts summary -->
<?php
  $pendingTotal = 0;
  $pendingCount = 0;
  if (!empty($payouts)) {
      foreach ($payouts as $p) {
          if (($p->status ?? '') === 'pending') {
              $pendingTotal += (float) ($p->amount ?? 0);
              $pendingCount++;
          }
      }
  }
?>
<?php if ($pendingCount > 0): ?>
  <div class="card bg-warning bg-opacity-10 border-warning mb-4">
    <div class="card-body d-flex justify-content-between align-items-center py-3">
      <div>
        <h5 class="mb-1"><?php echo __('%1% Pending Payouts', ['%1%' => $pendingCount]); ?></h5>
        <p class="mb-0 text-muted"><?php echo __('Total pending amount: ZAR %1%', ['%1%' => number_format($pendingTotal, 2)]); ?></p>
      </div>
      <i class="fas fa-clock fa-2x text-warning"></i>
    </div>
  </div>
<?php endif; ?>

<!-- Batch process form -->
<?php if (!empty($payouts)): ?>
  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminPayoutsBatch']); ?>">
    <input type="hidden" name="form_action" value="batch_process">

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo __('Payout Queue'); ?></h5>
        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('<?php echo __('Process all selected payouts?'); ?>');">
          <i class="fas fa-check-double me-1"></i> <?php echo __('Process Selected'); ?>
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 40px;">
                <input type="checkbox" class="form-check-input" id="selectAll">
              </th>
              <th><?php echo __('Payout #'); ?></th>
              <th><?php echo __('Seller'); ?></th>
              <th class="text-end"><?php echo __('Amount'); ?></th>
              <th><?php echo __('Currency'); ?></th>
              <th><?php echo __('Method'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Created'); ?></th>
              <th><?php echo __('Processed'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payouts as $payout): ?>
              <tr>
                <td>
                  <?php if (($payout->status ?? '') === 'pending'): ?>
                    <input type="checkbox" class="form-check-input payout-check" name="payout_ids[]" value="<?php echo (int) $payout->id; ?>">
                  <?php endif; ?>
                </td>
                <td class="small fw-semibold"><?php echo esc_entities($payout->payout_number); ?></td>
                <td class="small"><?php echo esc_entities($payout->seller_name ?? '-'); ?></td>
                <td class="text-end fw-semibold"><?php echo esc_entities($payout->currency); ?> <?php echo number_format((float) $payout->amount, 2); ?></td>
                <td class="small"><?php echo esc_entities($payout->currency ?? 'ZAR'); ?></td>
                <td class="small"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $payout->method ?? '-'))); ?></td>
                <td>
                  <?php
                    $statusClass = match($payout->status ?? '') {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'secondary',
                        default => 'secondary',
                    };
                  ?>
                  <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst($payout->status ?? '-')); ?></span>
                </td>
                <td class="small text-muted"><?php echo date('d M Y', strtotime($payout->created_at)); ?></td>
                <td class="small text-muted">
                  <?php echo ($payout->processed_at ?? null) ? date('d M Y', strtotime($payout->processed_at)) : '-'; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>

  <!-- Pagination -->
  <?php
    $limit = 30;
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminPayouts', 'status' => $statusFilter ?? '', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminPayouts', 'status' => $statusFilter ?? '', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminPayouts', 'status' => $statusFilter ?? '', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>

<?php else: ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-money-check-alt fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No payouts found'); ?></h5>
      <p class="text-muted"><?php echo __('Payouts will appear here when sellers have completed sales.'); ?></p>
    </div>
  </div>
<?php endif; ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
  document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
      selectAll.addEventListener('change', function() {
        var checks = document.querySelectorAll('.payout-check');
        for (var i = 0; i < checks.length; i++) {
          checks[i].checked = selectAll.checked;
        }
      });
    }
  });
</script>

<?php end_slot(); ?>
