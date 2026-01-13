<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-credit-card me-2"></i><?php echo __('Payment'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
      <?php if ($sf_user->hasFlash('error')): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo $sf_user->getFlash('error'); ?>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <i class="fas fa-lock me-2"></i><?php echo __('Secure Payment'); ?>
        </div>
        <div class="card-body">
          
          <!-- Order Summary -->
          <div class="mb-4">
            <h5><?php echo __('Order'); ?>: <?php echo esc_entities($order->order_number); ?></h5>
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
                      <?php echo esc_entities($item->archival_description); ?>
                      <?php if ($item->product_name): ?>
                        <br><small class="text-muted"><?php echo esc_entities($item->product_name); ?></small>
                      <?php endif; ?>
                    </td>
                    <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($item->line_total, 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot class="table-success">
                <tr class="fw-bold">
                  <td><?php echo __('Total'); ?></td>
                  <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Payment Form (auto-submit to PayFast) -->
          <?php if (isset($paymentData['success']) && $paymentData['success']): ?>
            <div class="text-center">
              <p class="mb-4"><?php echo __('You will be redirected to PayFast to complete your payment.'); ?></p>
              
              <form id="payfast-form" action="<?php echo $paymentData['payment_url']; ?>" method="post">
                <?php foreach ($paymentData['payment_data'] as $key => $value): ?>
                  <input type="hidden" name="<?php echo $key; ?>" value="<?php echo esc_entities($value); ?>">
                <?php endforeach; ?>
                
                <button type="submit" class="btn btn-success btn-lg">
                  <i class="fas fa-lock me-2"></i><?php echo __('Pay Now'); ?> (<?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?>)
                </button>
              </form>

              <p class="mt-4 text-muted small">
                <i class="fas fa-shield-alt me-1"></i>
                <?php echo __('Your payment is secured by PayFast'); ?>
              </p>

              <div class="mt-3">
                <img src="https://www.payfast.co.za/images/logo-small.svg" alt="PayFast" height="40">
              </div>
            </div>
          <?php else: ?>
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <?php echo __('Payment gateway is not configured. Please contact the administrator.'); ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <div class="mt-3 text-center">
        <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-2"></i><?php echo __('Back to Cart'); ?>
        </a>
      </div>

    </div>
  </div>
</div>

<?php end_slot(); ?>
