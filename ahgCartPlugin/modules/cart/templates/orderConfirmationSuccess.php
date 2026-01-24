<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?>
  <h1><i class="fas fa-check-circle me-2"></i><?php echo __('Order Confirmation'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="container-fluid px-0">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php if ($sf_user->hasFlash('notice')): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i><?php echo $sf_user->getFlash('notice'); ?>
        </div>
      <?php endif; ?>
      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
        </div>
      <?php endif; ?>
      
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <i class="fas fa-receipt me-2"></i><?php echo __('Order'); ?>: <?php echo esc_entities($order->order_number); ?>
        </div>
        <div class="card-body">
          <div class="row mb-4">
            <div class="col-md-6">
              <h6><?php echo __('Order Details'); ?></h6>
              <p class="mb-1"><strong><?php echo __('Order Number'); ?>:</strong> <?php echo esc_entities($order->order_number); ?></p>
              <p class="mb-1"><strong><?php echo __('Date'); ?>:</strong> <?php echo date('d M Y H:i', strtotime($order->created_at)); ?></p>
              <p class="mb-1"><strong><?php echo __('Status'); ?>:</strong> 
                <span class="badge bg-<?php echo $order->status === 'completed' ? 'success' : ($order->status === 'paid' ? 'info' : 'warning'); ?>">
                  <?php echo ucfirst($order->status); ?>
                </span>
              </p>
            </div>
            <div class="col-md-6">
              <h6><?php echo __('Customer Details'); ?></h6>
              <p class="mb-1"><strong><?php echo __('Name'); ?>:</strong> <?php echo esc_entities($order->customer_name); ?></p>
              <p class="mb-1"><strong><?php echo __('Email'); ?>:</strong> <?php echo esc_entities($order->customer_email); ?></p>
              <?php if ($order->customer_phone): ?>
                <p class="mb-1"><strong><?php echo __('Phone'); ?>:</strong> <?php echo esc_entities($order->customer_phone); ?></p>
              <?php endif; ?>
            </div>
          </div>
          
          <h6><?php echo __('Order Items'); ?></h6>
          <table class="table table-sm">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Item'); ?></th>
                <th class="text-end"><?php echo __('Amount'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td>
                    <?php echo esc_entities($item->description); ?>
                    <?php if ($item->product_name): ?>
                      <br><small class="text-muted"><?php echo esc_entities($item->product_name); ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($item->total, 2); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-success">
              <tr>
                <td><strong><?php echo __('Subtotal'); ?></strong></td>
                <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->subtotal, 2); ?></td>
              </tr>
              <tr>
                <td><strong><?php echo __('VAT'); ?></strong></td>
                <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->vat_amount, 2); ?></td>
              </tr>
              <tr class="fw-bold">
                <td><?php echo __('Total'); ?></td>
                <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?></td>
              </tr>
            </tfoot>
          </table>
          
          <?php if ($order->status === 'completed'): ?>
            <div class="alert alert-success mt-4">
              <i class="fas fa-download me-2"></i>
              <?php echo __('Your order is complete. Download links have been sent to your email.'); ?>
            </div>
          <?php elseif ($order->status === 'paid' || $order->status === 'processing'): ?>
            <div class="alert alert-info mt-4">
              <i class="fas fa-spinner fa-spin me-2"></i>
              <?php echo __('Your payment has been received. We are processing your order.'); ?>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mt-4">
              <i class="fas fa-clock me-2"></i>
              <?php echo __('Your order is pending payment confirmation.'); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="mt-4 text-center">
        <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>" class="btn btn-primary">
          <i class="fas fa-search me-2"></i><?php echo __('Continue Browsing'); ?>
        </a>
      </div>
    </div>
  </div>
</div>
<?php end_slot(); ?>
