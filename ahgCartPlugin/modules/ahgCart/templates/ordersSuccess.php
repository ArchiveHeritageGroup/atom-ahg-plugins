<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-list-alt me-2"></i><?php echo __('My Orders'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <i class="fas fa-shopping-bag me-2"></i><?php echo __('Order History'); ?>
    </div>
    <div class="card-body">
      
      <?php if (empty($orders)): ?>
        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('You have no orders yet.'); ?>
          <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse']); ?>"><?php echo __('Start browsing'); ?></a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Order #'); ?></th>
                <th><?php echo __('Date'); ?></th>
                <th class="text-center"><?php echo __('Status'); ?></th>
                <th class="text-end"><?php echo __('Total'); ?></th>
                <th class="text-center"><?php echo __('Actions'); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
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
                <tr>
                  <td>
                    <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'orderConfirmation', 'order' => $order->order_number]); ?>">
                      <strong><?php echo esc_entities($order->order_number); ?></strong>
                    </a>
                  </td>
                  <td><?php echo date('Y-m-d H:i', strtotime($order->created_at)); ?></td>
                  <td class="text-center">
                    <span class="badge bg-<?php echo $statusClass[$order->status] ?? 'secondary'; ?>">
                      <?php echo ucfirst($order->status); ?>
                    </span>
                  </td>
                  <td class="text-end">
                    <strong><?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?></strong>
                  </td>
                  <td class="text-center">
                    <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'orderConfirmation', 'order' => $order->order_number]); ?>" 
                       class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i> <?php echo __('View'); ?>
                    </a>
                    <?php if ($order->status === 'pending'): ?>
                      <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'payment', 'order' => $order->order_number]); ?>" 
                         class="btn btn-sm btn-success">
                        <i class="fas fa-credit-card"></i> <?php echo __('Pay'); ?>
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="mt-3">
    <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'browse']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-shopping-cart me-2"></i><?php echo __('Back to Cart'); ?>
    </a>
  </div>
</div>

<?php end_slot(); ?>
