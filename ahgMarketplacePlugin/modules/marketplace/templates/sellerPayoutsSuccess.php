<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Payouts'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Payouts'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Payouts'); ?></h1>

<!-- Pending amount banner -->
<div class="card bg-primary text-white mb-4">
  <div class="card-body d-flex justify-content-between align-items-center py-4">
    <div>
      <p class="mb-1 opacity-75"><?php echo __('Pending Payout Balance'); ?></p>
      <div class="h2 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($pendingAmount ?? 0), 2); ?></div>
    </div>
    <i class="fas fa-wallet fa-3x opacity-50"></i>
  </div>
</div>

<!-- Payout history -->
<?php if (empty($payouts)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-money-check-alt fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No payouts yet'); ?></h5>
      <p class="text-muted"><?php echo __('Completed payouts will appear here.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Payout #'); ?></th>
            <th class="text-end"><?php echo __('Amount'); ?></th>
            <th><?php echo __('Method'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Reference'); ?></th>
            <th><?php echo __('Processed'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payouts as $payout): ?>
            <tr>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($payout->created_at)); ?></td>
              <td class="small"><?php echo esc_entities($payout->payout_number); ?></td>
              <td class="text-end fw-semibold"><?php echo esc_entities($payout->currency); ?> <?php echo number_format((float) $payout->amount, 2); ?></td>
              <td class="small"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $payout->method))); ?></td>
              <td>
                <?php
                  $statusClass = match($payout->status) {
                      'pending' => 'warning',
                      'processing' => 'info',
                      'completed' => 'success',
                      'failed' => 'danger',
                      'cancelled' => 'secondary',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst($payout->status)); ?></span>
              </td>
              <td class="small"><?php echo esc_entities($payout->reference ?? '-'); ?></td>
              <td class="small text-muted">
                <?php echo $payout->processed_at ? date('d M Y', strtotime($payout->processed_at)) : '-'; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerPayouts', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerPayouts', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerPayouts', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>
