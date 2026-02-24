<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Marketplace Reports'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Reports'); ?></li>
  </ol>
</nav>

<h1 class="h3 mb-4"><?php echo __('Marketplace Reports'); ?></h1>

<!-- Revenue overview cards -->
<?php
  $totalRevenue = (float) ($revenueStats->total_revenue ?? 0);
  $totalCommission = (float) ($revenueStats->total_commission ?? 0);
  $netSellerPayouts = (float) ($revenueStats->net_seller_payouts ?? 0);
  $txnCount = (int) ($revenueStats->transaction_count ?? 0);
?>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card h-100 text-center">
      <div class="card-body py-3">
        <i class="fas fa-coins text-success mb-1 d-block" style="font-size: 1.5rem;"></i>
        <div class="h4 mb-0">ZAR <?php echo number_format($totalRevenue, 2); ?></div>
        <small class="text-muted"><?php echo __('Total Revenue'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 text-center">
      <div class="card-body py-3">
        <i class="fas fa-percentage text-primary mb-1 d-block" style="font-size: 1.5rem;"></i>
        <div class="h4 mb-0">ZAR <?php echo number_format($totalCommission, 2); ?></div>
        <small class="text-muted"><?php echo __('Total Commission'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 text-center">
      <div class="card-body py-3">
        <i class="fas fa-wallet text-warning mb-1 d-block" style="font-size: 1.5rem;"></i>
        <div class="h4 mb-0">ZAR <?php echo number_format($netSellerPayouts, 2); ?></div>
        <small class="text-muted"><?php echo __('Net Seller Payouts'); ?></small>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 text-center">
      <div class="card-body py-3">
        <i class="fas fa-receipt text-info mb-1 d-block" style="font-size: 1.5rem;"></i>
        <div class="h4 mb-0"><?php echo number_format($txnCount); ?></div>
        <small class="text-muted"><?php echo __('Transaction Count'); ?></small>
      </div>
    </div>
  </div>
</div>

<!-- Monthly revenue table with progress bars -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0"><?php echo __('Monthly Revenue'); ?></h5>
  </div>
  <?php if (!empty($monthlyRevenue)): ?>
    <?php
      // Find max revenue for progress bar scaling
      $maxRevenue = 0;
      foreach ($monthlyRevenue as $m) {
          $rev = (float) ($m->revenue ?? 0);
          if ($rev > $maxRevenue) $maxRevenue = $rev;
      }
    ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 120px;"><?php echo __('Month'); ?></th>
            <th><?php echo __('Revenue'); ?></th>
            <th class="text-end" style="width: 140px;"><?php echo __('Commission'); ?></th>
            <th class="text-end" style="width: 100px;"><?php echo __('Sales'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($monthlyRevenue as $month): ?>
            <?php
              $rev = (float) ($month->revenue ?? 0);
              $pct = $maxRevenue > 0 ? round(($rev / $maxRevenue) * 100) : 0;
            ?>
            <tr>
              <td class="fw-semibold"><?php echo esc_entities($month->month ?? '-'); ?></td>
              <td>
                <div class="d-flex align-items-center">
                  <div class="progress flex-grow-1 me-2" style="height: 20px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pct; ?>%;" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                  <span class="text-nowrap small fw-semibold" style="width: 120px;">ZAR <?php echo number_format($rev, 2); ?></span>
                </div>
              </td>
              <td class="text-end small">ZAR <?php echo number_format((float) ($month->commission ?? 0), 2); ?></td>
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

<div class="row">

  <!-- Top 10 sellers -->
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Top 10 Sellers by Revenue'); ?></h5>
      </div>
      <?php if (!empty($topSellers)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th><?php echo __('Seller'); ?></th>
                <th class="text-end"><?php echo __('Sales'); ?></th>
                <th class="text-end"><?php echo __('Revenue'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topSellers as $idx => $ts): ?>
                <tr>
                  <td class="small fw-semibold"><?php echo $idx + 1; ?></td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'seller', 'slug' => $ts->slug]); ?>" class="text-decoration-none">
                      <?php echo esc_entities($ts->display_name); ?>
                    </a>
                  </td>
                  <td class="text-end"><?php echo number_format((int) $ts->sales_count); ?></td>
                  <td class="text-end fw-semibold">ZAR <?php echo number_format((float) $ts->total_revenue, 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="card-body text-center py-4">
          <p class="text-muted mb-0"><?php echo __('No seller data yet.'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top 10 items -->
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><?php echo __('Top 10 Items by Sales'); ?></h5>
      </div>
      <?php if (!empty($topItems)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 40px;">#</th>
                <th><?php echo __('Item'); ?></th>
                <th><?php echo __('Sector'); ?></th>
                <th class="text-end"><?php echo __('Revenue'); ?></th>
                <th class="text-end"><?php echo __('Sales'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topItems as $idx => $ti): ?>
                <tr>
                  <td class="small fw-semibold"><?php echo $idx + 1; ?></td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'listing', 'slug' => $ti->slug]); ?>" class="text-decoration-none">
                      <?php echo esc_entities($ti->title); ?>
                    </a>
                  </td>
                  <td><span class="badge bg-info"><?php echo esc_entities(ucfirst($ti->sector ?? '-')); ?></span></td>
                  <td class="text-end fw-semibold">ZAR <?php echo number_format((float) $ti->total_revenue, 2); ?></td>
                  <td class="text-end"><?php echo number_format((int) $ti->sales_count); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="card-body text-center py-4">
          <p class="text-muted mb-0"><?php echo __('No item data yet.'); ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php end_slot(); ?>
