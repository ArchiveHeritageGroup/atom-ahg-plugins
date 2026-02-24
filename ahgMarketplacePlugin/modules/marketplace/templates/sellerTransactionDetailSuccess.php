<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Transaction Detail'); ?> - <?php echo esc_entities($transaction->transaction_number); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'browse']); ?>"><?php echo __('Marketplace'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'dashboard']); ?>"><?php echo __('Dashboard'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions']); ?>"><?php echo __('My Sales'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo esc_entities($transaction->transaction_number); ?></li>
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

<h1 class="h3 mb-4"><?php echo __('Transaction: %1%', ['%1%' => esc_entities($transaction->transaction_number)]); ?></h1>

<!-- Status timeline -->
<div class="card mb-4">
  <div class="card-body">
    <?php
      $steps = [
        'pending_payment' => ['icon' => 'fa-clock', 'label' => __('Created')],
        'paid' => ['icon' => 'fa-credit-card', 'label' => __('Paid')],
        'shipping' => ['icon' => 'fa-truck', 'label' => __('Shipping')],
        'delivered' => ['icon' => 'fa-box-open', 'label' => __('Delivered')],
        'completed' => ['icon' => 'fa-check-circle', 'label' => __('Completed')],
      ];
      $statusOrder = array_keys($steps);
      $currentIdx = array_search($transaction->status, $statusOrder);
      if ($currentIdx === false) { $currentIdx = -1; }
    ?>
    <div class="d-flex justify-content-between position-relative px-4">
      <?php foreach ($steps as $key => $step): ?>
        <?php
          $idx = array_search($key, $statusOrder);
          $isActive = ($idx !== false && $idx <= $currentIdx);
          $isCurrent = ($key === $transaction->status);
        ?>
        <div class="text-center" style="flex: 1;">
          <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1 <?php echo $isActive ? 'bg-primary text-white' : 'bg-light text-muted'; ?>" style="width: 40px; height: 40px;">
            <i class="fas <?php echo $step['icon']; ?>"></i>
          </div>
          <div class="small <?php echo $isCurrent ? 'fw-bold' : ''; ?>"><?php echo $step['label']; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-8">

    <!-- Transaction summary -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Transaction Summary'); ?></div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="text-muted" style="width: 200px;"><?php echo __('Transaction Number'); ?></td>
            <td class="fw-semibold"><?php echo esc_entities($transaction->transaction_number); ?></td>
          </tr>
          <tr>
            <td class="text-muted"><?php echo __('Date'); ?></td>
            <td><?php echo date('d M Y H:i', strtotime($transaction->created_at)); ?></td>
          </tr>
          <tr>
            <td class="text-muted"><?php echo __('Source'); ?></td>
            <td><span class="badge bg-secondary"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $transaction->source))); ?></span></td>
          </tr>
          <tr>
            <td class="text-muted"><?php echo __('Status'); ?></td>
            <td>
              <?php
                $statusClass = match($transaction->status) {
                    'pending_payment' => 'warning',
                    'paid' => 'info',
                    'shipping' => 'primary',
                    'delivered' => 'success',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    'disputed' => 'danger',
                    'refunded' => 'secondary',
                    default => 'secondary',
                };
              ?>
              <span class="badge bg-<?php echo $statusClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $transaction->status))); ?></span>
            </td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Item section -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Item'); ?></div>
      <div class="card-body">
        <div class="d-flex">
          <?php if (!empty($listing->featured_image_path)): ?>
            <img src="<?php echo esc_entities($listing->featured_image_path); ?>" alt="" class="rounded me-3" style="width: 80px; height: 80px; object-fit: cover;">
          <?php else: ?>
            <div class="bg-light rounded d-flex align-items-center justify-content-center me-3" style="width: 80px; height: 80px;">
              <i class="fas fa-image fa-2x text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <h6 class="mb-1"><?php echo esc_entities($listing->title ?? '-'); ?></h6>
            <p class="text-muted small mb-0"><?php echo esc_entities($listing->listing_number ?? ''); ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Financial breakdown -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Financial Breakdown'); ?></div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          <tr>
            <td><?php echo __('Sale Price'); ?></td>
            <td class="text-end"><?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) $transaction->sale_price, 2); ?></td>
          </tr>
          <tr>
            <td><?php echo __('Platform Commission (%1%%)', ['%1%' => number_format((float) $transaction->platform_commission_rate, 1)]); ?></td>
            <td class="text-end text-danger">- <?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) $transaction->platform_commission_amount, 2); ?></td>
          </tr>
          <tr class="fw-semibold">
            <td><?php echo __('Seller Amount'); ?></td>
            <td class="text-end text-success"><?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) $transaction->seller_amount, 2); ?></td>
          </tr>
          <tr class="table-light">
            <td colspan="2"></td>
          </tr>
          <tr>
            <td><?php echo __('VAT'); ?></td>
            <td class="text-end"><?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) ($transaction->vat_amount ?? 0), 2); ?></td>
          </tr>
          <tr>
            <td><?php echo __('Shipping'); ?></td>
            <td class="text-end"><?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) ($transaction->shipping_cost ?? 0), 2); ?></td>
          </tr>
          <tr class="fw-bold">
            <td><?php echo __('Grand Total (Buyer Paid)'); ?></td>
            <td class="text-end"><?php echo esc_entities($transaction->currency); ?> <?php echo number_format((float) $transaction->grand_total, 2); ?></td>
          </tr>
        </table>
      </div>
    </div>

    <!-- Shipping section -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Shipping & Delivery'); ?></div>
      <div class="card-body">
        <?php
          $shipClass = match($transaction->shipping_status) {
              'pending' => 'secondary',
              'preparing' => 'info',
              'shipped' => 'primary',
              'in_transit' => 'primary',
              'delivered' => 'success',
              'returned' => 'danger',
              default => 'secondary',
          };
        ?>
        <p class="mb-3">
          <?php echo __('Current Status:'); ?>
          <span class="badge bg-<?php echo $shipClass; ?>"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $transaction->shipping_status))); ?></span>
        </p>

        <?php if ($transaction->tracking_number): ?>
          <p class="small mb-1"><strong><?php echo __('Courier:'); ?></strong> <?php echo esc_entities($transaction->courier ?? '-'); ?></p>
          <p class="small mb-3"><strong><?php echo __('Tracking Number:'); ?></strong> <?php echo esc_entities($transaction->tracking_number); ?></p>
        <?php endif; ?>

        <?php if ($transaction->payment_status === 'paid' && in_array($transaction->shipping_status, ['pending', 'preparing'])): ?>
          <hr>
          <h6 class="mb-3"><?php echo __('Update Shipping'); ?></h6>
          <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactionDetail', 'id' => $transaction->id]); ?>">
            <input type="hidden" name="form_action" value="update_shipping">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="tracking_number" class="form-label"><?php echo __('Tracking Number'); ?></label>
                <input type="text" class="form-control" id="tracking_number" name="tracking_number" value="<?php echo esc_entities($transaction->tracking_number ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label for="courier" class="form-label"><?php echo __('Courier'); ?></label>
                <input type="text" class="form-control" id="courier" name="courier" value="<?php echo esc_entities($transaction->courier ?? ''); ?>" placeholder="<?php echo __('e.g. DHL, PostNet, Courier Guy'); ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-truck me-1"></i> <?php echo __('Mark as Shipped'); ?>
            </button>
          </form>
        <?php endif; ?>

        <!-- Delivery timeline -->
        <div class="mt-4">
          <h6 class="mb-3"><?php echo __('Delivery Timeline'); ?></h6>
          <ul class="list-unstyled">
            <li class="mb-2">
              <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
              <strong class="small"><?php echo __('Created'); ?></strong>
              <span class="small text-muted ms-2"><?php echo date('d M Y H:i', strtotime($transaction->created_at)); ?></span>
            </li>
            <?php if ($transaction->paid_at): ?>
              <li class="mb-2">
                <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                <strong class="small"><?php echo __('Paid'); ?></strong>
                <span class="small text-muted ms-2"><?php echo date('d M Y H:i', strtotime($transaction->paid_at)); ?></span>
              </li>
            <?php endif; ?>
            <?php if ($transaction->shipped_at): ?>
              <li class="mb-2">
                <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                <strong class="small"><?php echo __('Shipped'); ?></strong>
                <span class="small text-muted ms-2"><?php echo date('d M Y H:i', strtotime($transaction->shipped_at)); ?></span>
              </li>
            <?php endif; ?>
            <?php if ($transaction->delivered_at): ?>
              <li class="mb-2">
                <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                <strong class="small"><?php echo __('Delivered'); ?></strong>
                <span class="small text-muted ms-2"><?php echo date('d M Y H:i', strtotime($transaction->delivered_at)); ?></span>
              </li>
            <?php endif; ?>
            <?php if ($transaction->completed_at): ?>
              <li class="mb-2">
                <i class="fas fa-circle text-success me-2" style="font-size: 0.5rem; vertical-align: middle;"></i>
                <strong class="small"><?php echo __('Completed'); ?></strong>
                <span class="small text-muted ms-2"><?php echo date('d M Y H:i', strtotime($transaction->completed_at)); ?></span>
              </li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

  </div>

  <div class="col-lg-4">

    <!-- Buyer info -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Buyer Information'); ?></div>
      <div class="card-body">
        <p class="mb-1"><strong><?php echo esc_entities($buyerName ?? '-'); ?></strong></p>
        <?php if (!empty($buyerEmail)): ?>
          <p class="small text-muted mb-0"><i class="fas fa-envelope me-1"></i><?php echo esc_entities($buyerEmail); ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Payment info -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Payment'); ?></div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="text-muted"><?php echo __('Status'); ?></td>
            <td>
              <?php
                $payClass = match($transaction->payment_status) {
                    'pending' => 'warning',
                    'paid' => 'success',
                    'failed' => 'danger',
                    'refunded' => 'secondary',
                    'disputed' => 'danger',
                    default => 'secondary',
                };
              ?>
              <span class="badge bg-<?php echo $payClass; ?>"><?php echo esc_entities(ucfirst($transaction->payment_status)); ?></span>
            </td>
          </tr>
          <?php if ($transaction->payment_gateway): ?>
            <tr>
              <td class="text-muted"><?php echo __('Gateway'); ?></td>
              <td><?php echo esc_entities(ucfirst($transaction->payment_gateway)); ?></td>
            </tr>
          <?php endif; ?>
          <?php if ($transaction->paid_at): ?>
            <tr>
              <td class="text-muted"><?php echo __('Paid At'); ?></td>
              <td class="small"><?php echo date('d M Y H:i', strtotime($transaction->paid_at)); ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>
    </div>

  </div>
</div>

<div class="mt-2">
  <a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'sellerTransactions']); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Sales'); ?>
  </a>
</div>

<?php end_slot(); ?>
