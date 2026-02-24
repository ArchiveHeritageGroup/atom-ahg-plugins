<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Seller Analytics'); ?> - <?php echo __('Marketplace'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Analytics'); ?></li>
  </ol>
</nav>

<h1 class="h3 mb-4"><?php echo __('Seller Analytics'); ?></h1>

<!-- Revenue overview cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="text-muted small mb-1"><?php echo __('Total Revenue'); ?></div>
        <div class="h4 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($analytics->total_revenue ?? 0), 2); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="text-muted small mb-1"><?php echo __('This Month'); ?></div>
        <div class="h4 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($analytics->revenue_this_month ?? 0), 2); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="text-muted small mb-1"><?php echo __('Average Sale'); ?></div>
        <div class="h4 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($analytics->average_sale ?? 0), 2); ?></div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card h-100">
      <div class="card-body text-center">
        <div class="text-muted small mb-1"><?php echo __('Total Commission Paid'); ?></div>
        <div class="h4 mb-0"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) ($analytics->total_commission ?? 0), 2); ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Monthly revenue table -->
<div class="card mb-4">
  <div class="card-header fw-semibold"><?php echo __('Monthly Revenue'); ?></div>
  <?php if (!empty($monthlyRevenue)): ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Month'); ?></th>
            <th class="text-end"><?php echo __('Revenue'); ?></th>
            <th class="text-end"><?php echo __('Sales Count'); ?></th>
            <th style="width: 40%;"><?php echo __(''); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
            $maxRevenue = 0;
            foreach ($monthlyRevenue as $row) {
              if ((float) $row->revenue > $maxRevenue) { $maxRevenue = (float) $row->revenue; }
            }
          ?>
          <?php foreach ($monthlyRevenue as $row): ?>
            <?php $pct = $maxRevenue > 0 ? round(((float) $row->revenue / $maxRevenue) * 100) : 0; ?>
            <tr>
              <td><?php echo esc_entities($row->month_label ?? $row->month); ?></td>
              <td class="text-end fw-semibold"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) $row->revenue, 2); ?></td>
              <td class="text-end"><?php echo (int) $row->sales_count; ?></td>
              <td>
                <div class="progress" style="height: 20px;">
                  <div class="progress-bar bg-primary" style="width: <?php echo $pct; ?>%;"></div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card-body text-center py-4">
      <p class="text-muted mb-0"><?php echo __('No revenue data available yet.'); ?></p>
    </div>
  <?php endif; ?>
</div>

<!-- Top selling items -->
<div class="card mb-4">
  <div class="card-header fw-semibold"><?php echo __('Top Selling Items'); ?></div>
  <?php if (!empty($topItems)): ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">#</th>
            <th><?php echo __('Item'); ?></th>
            <th class="text-end"><?php echo __('Revenue'); ?></th>
            <th class="text-end"><?php echo __('Sales'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topItems as $idx => $item): ?>
            <tr>
              <td class="text-muted"><?php echo $idx + 1; ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <?php if (!empty($item->featured_image_path)): ?>
                    <img src="<?php echo esc_entities($item->featured_image_path); ?>" alt="" class="rounded me-2" style="width: 36px; height: 36px; object-fit: cover;">
                  <?php endif; ?>
                  <span><?php echo esc_entities($item->title ?? '-'); ?></span>
                </div>
              </td>
              <td class="text-end fw-semibold"><?php echo esc_entities($seller->payout_currency ?? 'ZAR'); ?> <?php echo number_format((float) $item->total_revenue, 2); ?></td>
              <td class="text-end"><?php echo (int) $item->sales_count; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="card-body text-center py-4">
      <p class="text-muted mb-0"><?php echo __('No sales data available yet.'); ?></p>
    </div>
  <?php endif; ?>
</div>

<!-- Views overview -->
<div class="card mb-4">
  <div class="card-header fw-semibold"><?php echo __('Listing Views'); ?></div>
  <div class="card-body">
    <div class="row text-center">
      <div class="col-md-4">
        <div class="h4 mb-0"><?php echo number_format((int) ($analytics->total_views ?? 0)); ?></div>
        <small class="text-muted"><?php echo __('Total Views'); ?></small>
      </div>
      <div class="col-md-4">
        <div class="h4 mb-0"><?php echo number_format((int) ($analytics->views_this_month ?? 0)); ?></div>
        <small class="text-muted"><?php echo __('Views This Month'); ?></small>
      </div>
      <div class="col-md-4">
        <div class="h4 mb-0"><?php echo number_format((int) ($analytics->total_favourites ?? 0)); ?></div>
        <small class="text-muted"><?php echo __('Total Favourites'); ?></small>
      </div>
    </div>
  </div>
</div>

<?php end_slot(); ?>
