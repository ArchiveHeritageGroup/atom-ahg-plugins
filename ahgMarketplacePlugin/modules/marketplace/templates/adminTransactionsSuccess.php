<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Manage Transactions'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Transactions'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Manage Transactions'); ?></h1>

<!-- Filter row -->
<div class="card mb-4">
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminTransactions']); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Order Status'); ?></label>
        <select name="status" class="form-select form-select-sm">
          <option value=""><?php echo __('All Statuses'); ?></option>
          <?php foreach (['pending_payment', 'paid', 'shipping', 'delivered', 'completed', 'cancelled', 'refunded', 'disputed'] as $s): ?>
            <option value="<?php echo $s; ?>"<?php echo ($filters['status'] ?? '') === $s ? ' selected' : ''; ?>><?php echo esc_entities(ucfirst(str_replace('_', ' ', $s))); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small"><?php echo __('Payment Status'); ?></label>
        <select name="payment_status" class="form-select form-select-sm">
          <option value=""><?php echo __('All'); ?></option>
          <?php foreach (['pending', 'paid', 'failed', 'refunded', 'disputed'] as $ps): ?>
            <option value="<?php echo $ps; ?>"<?php echo ($filters['payment_status'] ?? '') === $ps ? ' selected' : ''; ?>><?php echo esc_entities(ucfirst($ps)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small"><?php echo __('Search'); ?></label>
        <input type="text" name="search" class="form-control form-control-sm" value="<?php echo esc_entities($filters['search'] ?? ''); ?>" placeholder="<?php echo __('TXN #, seller, buyer...'); ?>">
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="fas fa-filter me-1"></i> <?php echo __('Filter'); ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Transactions table -->
<?php if (empty($transactions)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-receipt fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No transactions found'); ?></h5>
      <p class="text-muted"><?php echo __('Try adjusting your filters.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('TXN #'); ?></th>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Item'); ?></th>
            <th><?php echo __('Seller'); ?></th>
            <th><?php echo __('Buyer'); ?></th>
            <th class="text-end"><?php echo __('Amount'); ?></th>
            <th class="text-end"><?php echo __('Commission'); ?></th>
            <th class="text-end"><?php echo __('Seller Amt'); ?></th>
            <th><?php echo __('Payment'); ?></th>
            <th><?php echo __('Order'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $txn): ?>
            <tr>
              <td class="small fw-semibold"><?php echo esc_entities($txn->transaction_number); ?></td>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($txn->created_at)); ?></td>
              <td>
                <?php if ($txn->listing_slug ?? null): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $txn->listing_slug]); ?>" class="text-decoration-none">
                    <?php echo esc_entities($txn->title ?? '-'); ?>
                  </a>
                <?php else: ?>
                  <?php echo esc_entities($txn->title ?? '-'); ?>
                <?php endif; ?>
              </td>
              <td class="small"><?php echo esc_entities($txn->seller_name ?? '-'); ?></td>
              <td class="small"><?php echo esc_entities($txn->buyer_name ?? '-'); ?></td>
              <td class="text-end fw-semibold"><?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->sale_price, 2); ?></td>
              <td class="text-end small text-muted">
                <?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) ($txn->platform_commission_amount ?? 0), 2); ?>
              </td>
              <td class="text-end small"><?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) ($txn->seller_amount ?? 0), 2); ?></td>
              <td>
                <?php
                  $payClass = match($txn->payment_status ?? '') {
                      'pending' => 'warning',
                      'paid' => 'success',
                      'failed' => 'danger',
                      'refunded' => 'secondary',
                      'disputed' => 'danger',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $payClass; ?>"><?php echo esc_entities(ucfirst($txn->payment_status ?? '-')); ?></span>
              </td>
              <td>
                <?php
                  $orderClass = match($txn->status ?? '') {
                      'pending_payment' => 'warning',
                      'paid' => 'info',
                      'shipping' => 'primary',
                      'delivered' => 'success',
                      'completed' => 'success',
                      'cancelled' => 'danger',
                      'refunded' => 'secondary',
                      'disputed' => 'danger',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $orderClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $txn->status ?? '-'))); ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $limit = 30;
    $totalPages = (int) ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item<?php echo $page <= 1 ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminTransactions', 'status' => $filters['status'] ?? '', 'payment_status' => $filters['payment_status'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminTransactions', 'status' => $filters['status'] ?? '', 'payment_status' => $filters['payment_status'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminTransactions', 'status' => $filters['status'] ?? '', 'payment_status' => $filters['payment_status'] ?? '', 'search' => $filters['search'] ?? '', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>
