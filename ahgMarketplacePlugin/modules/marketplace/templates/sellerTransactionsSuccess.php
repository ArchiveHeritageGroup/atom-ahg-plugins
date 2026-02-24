<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Sales'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('My Sales'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('My Sales'); ?></h1>

<?php if (empty($transactions)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-receipt fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No sales yet'); ?></h5>
      <p class="text-muted"><?php echo __('Your completed sales will appear here.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Transaction #'); ?></th>
            <th><?php echo __('Item'); ?></th>
            <th><?php echo __('Buyer'); ?></th>
            <th class="text-end"><?php echo __('Sale Price'); ?></th>
            <th class="text-end"><?php echo __('Commission'); ?></th>
            <th class="text-end"><?php echo __('Your Amount'); ?></th>
            <th><?php echo __('Payment'); ?></th>
            <th><?php echo __('Shipping'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $txn): ?>
            <tr>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($txn->created_at)); ?></td>
              <td class="small"><?php echo esc_entities($txn->transaction_number); ?></td>
              <td>
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $txn->listing_slug ?? '']); ?>" class="text-decoration-none">
                  <?php echo esc_entities($txn->title ?? '-'); ?>
                </a>
              </td>
              <td class="small"><?php echo esc_entities($txn->buyer_name ?? '-'); ?></td>
              <td class="text-end"><?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->sale_price, 2); ?></td>
              <td class="text-end small text-muted">
                <?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->platform_commission_amount, 2); ?>
                <br><span class="text-muted">(<?php echo number_format((float) $txn->platform_commission_rate, 1); ?>%)</span>
              </td>
              <td class="text-end fw-semibold"><?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->seller_amount, 2); ?></td>
              <td>
                <?php
                  $payClass = match($txn->payment_status) {
                      'pending' => 'warning',
                      'paid' => 'success',
                      'failed' => 'danger',
                      'refunded' => 'secondary',
                      'disputed' => 'danger',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $payClass; ?>"><?php echo esc_entities(ucfirst($txn->payment_status)); ?></span>
              </td>
              <td>
                <?php
                  $shipClass = match($txn->shipping_status) {
                      'pending' => 'secondary',
                      'preparing' => 'info',
                      'shipped' => 'primary',
                      'in_transit' => 'primary',
                      'delivered' => 'success',
                      'returned' => 'danger',
                      default => 'secondary',
                  };
                ?>
                <span class="badge bg-<?php echo $shipClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $txn->shipping_status))); ?></span>
              </td>
              <td class="text-end">
                <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactionDetail', 'id' => $txn->id]); ?>" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-eye"></i>
                </a>
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
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
          <li class="page-item<?php echo $i === $page ? ' active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions', 'page' => $i]); ?>"><?php echo $i; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item<?php echo $page >= $totalPages ? ' disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>
