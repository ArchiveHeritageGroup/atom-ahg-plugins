<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Seller Dashboard'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Seller Dashboard'); ?></li>
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

<!-- Welcome banner -->
<div class="card bg-primary text-white mb-4">
  <div class="card-body d-flex justify-content-between align-items-center py-4">
    <div>
      <h1 class="h3 mb-1"><?php echo __('Welcome back, %1%', ['%1%' => esc_entities($seller->display_name)]); ?></h1>
      <p class="mb-0 opacity-75"><?php echo __('Manage your listings, orders, and payouts from your seller dashboard.'); ?></p>
    </div>
    <?php if ($seller->avatar_path): ?>
      <img src="<?php echo esc_entities($seller->avatar_path); ?>" alt="" class="rounded-circle border border-2 border-white" width="64" height="64" style="object-fit: cover;">
    <?php endif; ?>
  </div>
</div>

<div class="row">

  <!-- Main content -->
  <div class="col-lg-9">

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-tags text-primary mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo number_format((int) ($stats->active_listings ?? 0)); ?></div>
            <small class="text-muted"><?php echo __('Active Listings'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-shopping-cart text-success mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo number_format((int) ($seller->total_sales ?? 0)); ?></div>
            <small class="text-muted"><?php echo __('Total Sales'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-coins text-warning mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($stats->total_revenue ?? 0), 2); ?></div>
            <small class="text-muted"><?php echo __('Revenue'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-wallet text-info mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($stats->pending_payout ?? 0), 2); ?></div>
            <small class="text-muted"><?php echo __('Pending Payout'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-users text-secondary mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo number_format((int) ($stats->followers ?? 0)); ?></div>
            <small class="text-muted"><?php echo __('Followers'); ?></small>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="mb-4">
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListingCreate']); ?>" class="btn btn-primary me-2">
        <i class="fas fa-plus me-1"></i> <?php echo __('Create Listing'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers']); ?>" class="btn btn-outline-primary me-2">
        <i class="fas fa-hand-holding-usd me-1"></i> <?php echo __('View Offers'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions']); ?>" class="btn btn-outline-primary">
        <i class="fas fa-receipt me-1"></i> <?php echo __('Process Orders'); ?>
      </a>
    </div>

    <!-- Recent transactions -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo __('Recent Transactions'); ?></h5>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions']); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('View All'); ?></a>
      </div>
      <?php if (!empty($recentTransactions)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('Item'); ?></th>
                <th><?php echo __('Buyer'); ?></th>
                <th class="text-end"><?php echo __('Amount'); ?></th>
                <th><?php echo __('Status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentTransactions as $txn): ?>
                <tr>
                  <td class="small text-muted"><?php echo date('d M Y', strtotime($txn->created_at)); ?></td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactionDetail', 'id' => $txn->id]); ?>" class="text-decoration-none">
                      <?php echo esc_entities($txn->title ?? $txn->transaction_number); ?>
                    </a>
                  </td>
                  <td class="small"><?php echo esc_entities($txn->buyer_name ?? '-'); ?></td>
                  <td class="text-end fw-semibold"><?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->grand_total, 2); ?></td>
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
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="card-body text-center py-4">
          <p class="text-muted mb-0"><?php echo __('No transactions yet.'); ?></p>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Sidebar nav -->
  <div class="col-lg-3">
    <div class="card">
      <div class="card-header fw-semibold"><?php echo __('Seller Menu'); ?></div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>" class="list-group-item list-group-item-action active">
          <i class="fas fa-tachometer-alt me-2"></i><?php echo __('Dashboard'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerProfile']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-user-edit me-2"></i><?php echo __('My Profile'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerListings']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-tags me-2"></i><?php echo __('My Listings'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerOffers']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-hand-holding-usd me-2"></i><?php echo __('Offers'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-receipt me-2"></i><?php echo __('Sales'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerPayouts']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-wallet me-2"></i><?php echo __('Payouts'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerCollections']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-layer-group me-2"></i><?php echo __('Collections'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerEnquiries']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-envelope me-2"></i><?php echo __('Enquiries'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerReviews']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-star me-2"></i><?php echo __('Reviews'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerAnalytics']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-chart-bar me-2"></i><?php echo __('Analytics'); ?>
        </a>
      </div>
    </div>
  </div>

</div>

<?php end_slot(); ?>
