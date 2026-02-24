<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('My Purchases'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('My Purchases'); ?></li>
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

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('My Purchases'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myBids']); ?>" class="btn btn-outline-secondary btn-sm me-1">
      <i class="fas fa-gavel me-1"></i><?php echo __('My Bids'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myOffers']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-hand-holding-usd me-1"></i><?php echo __('My Offers'); ?>
    </a>
  </div>
</div>

<?php if (empty($transactions)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-shopping-bag fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No purchases yet'); ?></h5>
      <p class="text-muted"><?php echo __('Browse the marketplace to find items you love.'); ?></p>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>" class="btn btn-primary">
        <i class="fas fa-search me-1"></i> <?php echo __('Browse Marketplace'); ?>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Item'); ?></th>
            <th><?php echo __('Seller'); ?></th>
            <th class="text-end"><?php echo __('Amount'); ?></th>
            <th><?php echo __('Status'); ?></th>
            <th><?php echo __('Tracking'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $txn): ?>
            <tr>
              <td class="small text-muted"><?php echo date('d M Y', strtotime($txn->created_at)); ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <?php if ($txn->featured_image_path): ?>
                    <img src="<?php echo esc_entities($txn->featured_image_path); ?>" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                  <?php else: ?>
                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                      <i class="fas fa-image text-muted small"></i>
                    </div>
                  <?php endif; ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $txn->slug]); ?>" class="text-decoration-none fw-semibold">
                    <?php echo esc_entities($txn->title); ?>
                  </a>
                </div>
              </td>
              <td class="small"><?php echo esc_entities($txn->seller_name ?? '-'); ?></td>
              <td class="text-end fw-semibold">
                <?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->grand_total, 2); ?>
              </td>
              <td>
                <?php
                  $statusClass = match($txn->status) {
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
                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $txn->status))); ?></span>
              </td>
              <td class="small">
                <?php if ($txn->tracking_number): ?>
                  <span class="text-muted"><?php echo esc_entities($txn->courier ?? ''); ?></span>
                  <span class="fw-semibold"><?php echo esc_entities($txn->tracking_number); ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($txn->status === 'delivered' || ($txn->status === 'shipping' && $txn->tracking_number)): ?>
                  <?php if (empty($txn->buyer_confirmed_receipt)): ?>
                    <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases']); ?>" class="d-inline">
                      <input type="hidden" name="form_action" value="confirm_receipt">
                      <input type="hidden" name="transaction_id" value="<?php echo (int) $txn->id; ?>">
                      <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('<?php echo __('Confirm you have received this item?'); ?>');">
                        <i class="fas fa-check me-1"></i><?php echo __('Confirm Receipt'); ?>
                      </button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>

                <?php if ($txn->status === 'completed' && empty($reviewedMap[$txn->id])): ?>
                  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'reviewForm', 'id' => $txn->id]); ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-star me-1"></i><?php echo __('Leave Review'); ?>
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php
    $totalPages = ceil($total / $limit);
    if ($totalPages > 1):
  ?>
    <nav class="mt-4" aria-label="<?php echo __('Pagination'); ?>">
      <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases', 'page' => $page - 1]); ?>">&laquo;</a>
        </li>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases', 'page' => $p]); ?>"><?php echo $p; ?></a>
          </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?php echo url_for(['module' => 'marketplace', 'action' => 'myPurchases', 'page' => $page + 1]); ?>">&raquo;</a>
        </li>
      </ul>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php end_slot(); ?>
