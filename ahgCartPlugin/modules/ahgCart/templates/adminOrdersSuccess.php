<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-shopping-bag me-2"></i><?php echo __('Order Management'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<div class="container-fluid px-0">

  <!-- Stats Cards -->
  <div class="row mb-4">
    <div class="col-md-2">
      <div class="card bg-primary text-white">
        <div class="card-body text-center">
          <h3 class="mb-0"><?php echo $stats->total_orders; ?></h3>
          <small><?php echo __('Total Orders'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-warning text-dark">
        <div class="card-body text-center">
          <h3 class="mb-0"><?php echo $stats->pending_orders; ?></h3>
          <small><?php echo __('Pending'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-success text-white">
        <div class="card-body text-center">
          <h3 class="mb-0"><?php echo $stats->paid_orders; ?></h3>
          <small><?php echo __('Paid'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card bg-info text-white">
        <div class="card-body text-center">
          <h3 class="mb-0"><?php echo $stats->completed_orders; ?></h3>
          <small><?php echo __('Completed'); ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card bg-dark text-white">
        <div class="card-body text-center">
          <h3 class="mb-0">R <?php echo number_format($stats->total_revenue, 2); ?></h3>
          <small><?php echo __('Total Revenue'); ?></small>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <form method="get" class="row g-3 align-items-end">
        <div class="col-auto">
          <label class="form-label"><?php echo __('Status'); ?></label>
          <select name="status" class="form-select">
            <option value=""><?php echo __('All'); ?></option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>><?php echo __('Pending'); ?></option>
            <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>><?php echo __('Paid'); ?></option>
            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>><?php echo __('Processing'); ?></option>
            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>><?php echo __('Completed'); ?></option>
            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>><?php echo __('Cancelled'); ?></option>
          </select>
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter me-1"></i><?php echo __('Filter'); ?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Orders Table -->
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <i class="fas fa-list me-2"></i><?php echo __('Orders'); ?>
      <span class="badge bg-light text-dark ms-2"><?php echo $totalOrders; ?></span>
    </div>
    <div class="card-body p-0">
      <?php if (empty($orders)): ?>
        <div class="alert alert-info m-3">
          <i class="fas fa-info-circle me-2"></i><?php echo __('No orders found.'); ?>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th><?php echo __('Order #'); ?></th>
                <th><?php echo __('Customer'); ?></th>
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
                  <td><strong><?php echo esc_entities($order->order_number); ?></strong></td>
                  <td>
                    <?php echo esc_entities($order->customer_name); ?>
                    <br><small class="text-muted"><?php echo esc_entities($order->customer_email); ?></small>
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
                    <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'adminOrderDetail', 'id' => $order->id]); ?>" 
                       class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <div class="card-footer">
            <nav>
              <ul class="pagination mb-0 justify-content-center">
                <?php if ($currentPage > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&status=<?php echo $status; ?>">&laquo;</a>
                  </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                  <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                  </li>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&status=<?php echo $status; ?>">&raquo;</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="mt-4">
    <a href="<?php echo url_for(['module' => 'ahgCart', 'action' => 'adminSettings']); ?>" class="btn btn-outline-secondary">
      <i class="fas fa-cog me-2"></i><?php echo __('E-Commerce Settings'); ?>
    </a>
  </div>

</div>

<?php end_slot(); ?>
