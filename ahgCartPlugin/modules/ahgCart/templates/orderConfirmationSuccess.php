<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-check-circle me-2"></i><?php echo __('Order Confirmation'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <?php if ($order->status === 'paid' || $order->status === 'completed'): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i>
          <strong><?php echo __('Thank you for your order!'); ?></strong>
          <?php echo __('Your payment has been received.'); ?>
        </div>
      <?php elseif ($order->status === 'pending'): ?>
        <div class="alert alert-warning">
          <i class="fas fa-clock me-2"></i>
          <strong><?php echo __('Payment Pending'); ?></strong>
          <?php echo __('Your order is awaiting payment.'); ?>
        </div>
      <?php elseif ($order->status === 'cancelled'): ?>
        <div class="alert alert-danger">
          <i class="fas fa-times-circle me-2"></i>
          <strong><?php echo __('Order Cancelled'); ?></strong>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between">
          <span><i class="fas fa-receipt me-2"></i><?php echo __('Order Details'); ?></span>
          <span class="badge bg-light text-dark"><?php echo esc_entities($order->order_number); ?></span>
        </div>
        <div class="card-body">
          
          <div class="row mb-4">
            <div class="col-md-6">
              <h6><?php echo __('Order Information'); ?></h6>
              <table class="table table-sm">
                <tr>
                  <td class="text-muted"><?php echo __('Order Number'); ?></td>
                  <td><strong><?php echo esc_entities($order->order_number); ?></strong></td>
                </tr>
                <tr>
                  <td class="text-muted"><?php echo __('Date'); ?></td>
                  <td><?php echo date('Y-m-d H:i', strtotime($order->created_at)); ?></td>
                </tr>
                <tr>
                  <td class="text-muted"><?php echo __('Status'); ?></td>
                  <td>
                    <?php
                    $statusClass = [
                        'pending' => 'warning',
                        'paid' => 'success',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'refunded' => 'secondary',
                    ];
                    ?>
                    <span class="badge bg-<?php echo $statusClass[$order->status] ?? 'secondary'; ?>">
                      <?php echo ucfirst($order->status); ?>
                    </span>
                  </td>
                </tr>
                <tr>
                  <td class="text-muted"><?php echo __('Total'); ?></td>
                  <td><strong><?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?></strong></td>
                </tr>
              </table>
            </div>
            <div class="col-md-6">
              <h6><?php echo __('Customer Details'); ?></h6>
              <table class="table table-sm">
                <tr>
                  <td class="text-muted"><?php echo __('Name'); ?></td>
                  <td><?php echo esc_entities($order->customer_name); ?></td>
                </tr>
                <tr>
                  <td class="text-muted"><?php echo __('Email'); ?></td>
                  <td><?php echo esc_entities($order->customer_email); ?></td>
                </tr>
                <?php if ($order->customer_phone): ?>
                <tr>
                  <td class="text-muted"><?php echo __('Phone'); ?></td>
                  <td><?php echo esc_entities($order->customer_phone); ?></td>
                </tr>
                <?php endif; ?>
              </table>
            </div>
          </div>

          <h6><?php echo __('Order Items'); ?></h6>
          <table class="table table-sm">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Item'); ?></th>
                <th><?php echo __('Product'); ?></th>
                <th class="text-center"><?php echo __('Qty'); ?></th>
                <th class="text-end"><?php echo __('Price'); ?></th>
                <?php if ($order->status === 'paid' || $order->status === 'completed'): ?>
                  <th class="text-center"><?php echo __('Download'); ?></th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <?php if ($item->slug): ?>
                      <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $item->slug]); ?>">
                        <?php echo esc_entities($item->archival_description); ?>
                      </a>
                    <?php else: ?>
                      <?php echo esc_entities($item->archival_description); ?>
                    <?php endif; ?>
                  </td>
                  <td><?php echo esc_entities($item->product_name ?? '-'); ?></td>
                  <td class="text-center"><?php echo $item->quantity; ?></td>
                  <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($item->line_total, 2); ?></td>
                  <?php if ($order->status === 'paid' || $order->status === 'completed'): ?>
                    <td class="text-center">
                      <?php if ($item->download_url && (!$item->download_expires_at || strtotime($item->download_expires_at) > time())): ?>
                        <a href="<?php echo $item->download_url; ?>" class="btn btn-sm btn-success">
                          <i class="fas fa-download"></i>
                        </a>
                        <?php if ($item->download_expires_at): ?>
                          <br><small class="text-muted"><?php echo __('Expires'); ?>: <?php echo date('Y-m-d', strtotime($item->download_expires_at)); ?></small>
                        <?php endif; ?>
                      <?php elseif ($item->download_url): ?>
                        <span class="text-muted"><i class="fas fa-times"></i> <?php echo __('Expired'); ?></span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-success">
              <tr class="fw-bold">
                <td colspan="<?php echo ($order->status === 'paid' || $order->status === 'completed') ? 3 : 2; ?>"><?php echo __('Total'); ?></td>
                <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?></td>
                <?php if ($order->status === 'paid' || $order->status === 'completed'): ?>
                  <td></td>
                <?php endif; ?>
              </tr>
            </tfoot>
          </table>

        </div>
      </div>

      <div class="mt-4 d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'orders']); ?>" class="btn btn-outline-secondary">
          <i class="fas fa-list me-2"></i><?php echo __('My Orders'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-primary">
          <i class="fas fa-search me-2"></i><?php echo __('Continue Browsing'); ?>
        </a>
      </div>

    </div>
  </div>
</div>

<?php end_slot(); ?>
