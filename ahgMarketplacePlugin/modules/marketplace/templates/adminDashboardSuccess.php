<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Marketplace Administration'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Administration'); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Marketplace Administration'); ?></h1>

<div class="row">

  <!-- Main content -->
  <div class="col-lg-9">

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-store text-primary mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo number_format((int) ($totalSellers ?? 0)); ?></div>
            <small class="text-muted"><?php echo __('Total Sellers'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-tags text-info mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo number_format((int) ($totalListings ?? 0)); ?></div>
            <small class="text-muted"><?php echo __('Total Listings'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-receipt text-success mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0"><?php echo number_format((int) ($totalTransactions ?? 0)); ?></div>
            <small class="text-muted"><?php echo __('Total Transactions'); ?></small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md">
        <div class="card h-100 text-center">
          <div class="card-body py-3">
            <i class="fas fa-coins text-warning mb-1 d-block" style="font-size: 1.5rem;"></i>
            <div class="h4 mb-0">ZAR <?php echo number_format((float) ($totalRevenue ?? 0), 2); ?></div>
            <small class="text-muted"><?php echo __('Revenue'); ?></small>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert badges -->
    <div class="row g-3 mb-4">
      <?php if (($pendingListings ?? 0) > 0): ?>
        <div class="col-auto">
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings', 'status' => 'pending_review']); ?>" class="btn btn-warning">
            <i class="fas fa-clipboard-list me-1"></i> <?php echo __('Pending Listings'); ?>
            <span class="badge bg-dark ms-1"><?php echo (int) $pendingListings; ?></span>
          </a>
        </div>
      <?php endif; ?>
      <?php if (($unverifiedSellers ?? 0) > 0): ?>
        <div class="col-auto">
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers', 'verification_status' => 'unverified']); ?>" class="btn btn-warning">
            <i class="fas fa-user-clock me-1"></i> <?php echo __('Unverified Sellers'); ?>
            <span class="badge bg-dark ms-1"><?php echo (int) $unverifiedSellers; ?></span>
          </a>
        </div>
      <?php endif; ?>
      <?php if (($pendingPayoutsCount ?? 0) > 0): ?>
        <div class="col-auto">
          <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminPayouts', 'status' => 'pending']); ?>" class="btn btn-warning">
            <i class="fas fa-wallet me-1"></i> <?php echo __('Pending Payouts'); ?>
            <span class="badge bg-dark ms-1"><?php echo (int) $pendingPayoutsCount; ?></span>
          </a>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent transactions -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><?php echo __('Recent Transactions'); ?></h5>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminTransactions']); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('View All'); ?></a>
      </div>
      <?php if (!empty($recentTransactions)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Date'); ?></th>
                <th><?php echo __('TXN #'); ?></th>
                <th><?php echo __('Item'); ?></th>
                <th><?php echo __('Seller'); ?></th>
                <th><?php echo __('Buyer'); ?></th>
                <th class="text-end"><?php echo __('Amount'); ?></th>
                <th><?php echo __('Status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentTransactions as $txn): ?>
                <tr>
                  <td class="small text-muted"><?php echo date('d M Y', strtotime($txn->created_at)); ?></td>
                  <td class="small"><?php echo esc_entities($txn->transaction_number); ?></td>
                  <td><?php echo esc_entities($txn->title ?? '-'); ?></td>
                  <td class="small"><?php echo esc_entities($txn->seller_name ?? '-'); ?></td>
                  <td class="small"><?php echo esc_entities($txn->buyer_name ?? '-'); ?></td>
                  <td class="text-end fw-semibold"><?php echo esc_entities($txn->currency); ?> <?php echo number_format((float) $txn->grand_total, 2); ?></td>
                  <td>
                    <?php
                      $statusClass = match($txn->status ?? '') {
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

    <!-- Monthly revenue -->
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Monthly Revenue (Last 6 Months)'); ?></h5>
      </div>
      <?php if (!empty($monthlyRevenue)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Month'); ?></th>
                <th class="text-end"><?php echo __('Revenue'); ?></th>
                <th class="text-end"><?php echo __('Commission'); ?></th>
                <th class="text-end"><?php echo __('Sales Count'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php $rawRevenue = (is_array($monthlyRevenue) ? $monthlyRevenue : (method_exists($monthlyRevenue, 'getRawValue') ? $monthlyRevenue->getRawValue() : iterator_to_array($monthlyRevenue))); ?>
              <?php foreach (array_slice($rawRevenue, 0, 6) as $month): ?>
                <tr>
                  <td><?php echo esc_entities($month->month ?? '-'); ?></td>
                  <td class="text-end">ZAR <?php echo number_format((float) ($month->revenue ?? 0), 2); ?></td>
                  <td class="text-end">ZAR <?php echo number_format((float) ($month->commission ?? 0), 2); ?></td>
                  <td class="text-end"><?php echo number_format((int) ($month->sales_count ?? 0)); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="card-body text-center py-4">
          <p class="text-muted mb-0"><?php echo __('No revenue data yet.'); ?></p>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Sidebar: Quick links -->
  <div class="col-lg-3">
    <div class="card">
      <div class="card-header fw-semibold"><?php echo __('Admin Menu'); ?></div>
      <div class="list-group list-group-flush">
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>" class="list-group-item list-group-item-action active">
          <i class="fas fa-tachometer-alt me-2"></i><?php echo __('Dashboard'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminListings']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-tags me-2"></i><?php echo __('Listings'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSellers']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-store me-2"></i><?php echo __('Sellers'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminTransactions']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-receipt me-2"></i><?php echo __('Transactions'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminPayouts']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-wallet me-2"></i><?php echo __('Payouts'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReviews']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-star me-2"></i><?php echo __('Reviews'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCategories']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-folder me-2"></i><?php echo __('Categories'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminCurrencies']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-money-bill me-2"></i><?php echo __('Currencies'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSettings']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-cog me-2"></i><?php echo __('Settings'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminReports']); ?>" class="list-group-item list-group-item-action">
          <i class="fas fa-chart-bar me-2"></i><?php echo __('Reports'); ?>
        </a>
      </div>
    </div>
  </div>

</div>

<?php end_slot(); ?>
