<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">Order <?php echo esc_specialchars($order->order_number); ?></h1>
    <span class="small">
      <a href="<?php echo url_for(['module' => 'cart', 'action' => 'adminOrders']); ?>">&laquo; Back to orders</a>
    </span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>

<div class="row">
  <!-- Order Info -->
  <div class="col-md-8">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-receipt me-2"></i>Order Details</div>
      <div class="card-body">
        <?php echo render_show(__('Order Number'), esc_specialchars($order->order_number)); ?>
        <?php echo render_show(__('Status'), '<span class="badge bg-' . (['pending'=>'warning','paid'=>'info','processing'=>'primary','completed'=>'success','cancelled'=>'danger','refunded'=>'secondary'][$order->status] ?? 'secondary') . '">' . ucfirst($order->status) . '</span>'); ?>
        <?php echo render_show(__('Created'), date('Y-m-d H:i', strtotime($order->created_at))); ?>
        <?php if ($order->paid_at): ?>
          <?php echo render_show(__('Paid'), date('Y-m-d H:i', strtotime($order->paid_at))); ?>
        <?php endif; ?>
        <?php if ($order->completed_at): ?>
          <?php echo render_show(__('Completed'), date('Y-m-d H:i', strtotime($order->completed_at))); ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Items -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-box me-2"></i>Items (<?php echo count($items); ?>)</div>
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light">
            <tr><th>Item</th><th>Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <?php echo esc_specialchars($item->product_name ?: $item->archival_description); ?>
                  <?php if ($item->slug): ?>
                    <br><small class="text-muted"><a href="/<?php echo $item->slug; ?>"><?php echo $item->slug; ?></a></small>
                  <?php endif; ?>
                </td>
                <td><?php echo $item->quantity; ?></td>
                <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($item->unit_price, 2); ?></td>
                <td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($item->line_total, 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot class="table-success">
            <tr><td colspan="3"><strong>Subtotal</strong></td><td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->subtotal, 2); ?></td></tr>
            <tr><td colspan="3"><strong>VAT</strong></td><td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->vat_amount, 2); ?></td></tr>
            <tr class="fw-bold"><td colspan="3">Total</td><td class="text-end"><?php echo $order->currency; ?> <?php echo number_format($order->total, 2); ?></td></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Payments -->
    <?php if (!empty($payments)): ?>
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-credit-card me-2"></i>Payments</div>
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead class="table-light">
            <tr><th>Gateway</th><th>Transaction ID</th><th>Status</th><th class="text-end">Amount</th><th>Date</th></tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td><?php echo ucfirst($p->payment_gateway); ?></td>
                <td><code><?php echo esc_specialchars($p->transaction_id ?: '—'); ?></code></td>
                <td><span class="badge bg-<?php echo $p->status === 'completed' ? 'success' : ($p->status === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($p->status); ?></span></td>
                <td class="text-end"><?php echo $p->currency; ?> <?php echo number_format($p->amount, 2); ?></td>
                <td><?php echo $p->paid_at ? date('Y-m-d H:i', strtotime($p->paid_at)) : '—'; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Notes -->
    <?php if (!empty($order->notes)): ?>
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-sticky-note me-2"></i>Notes</div>
      <div class="card-body"><pre class="mb-0"><?php echo esc_specialchars($order->notes); ?></pre></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Sidebar -->
  <div class="col-md-4">
    <!-- Customer -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-user me-2"></i>Customer</div>
      <div class="card-body">
        <?php echo render_show(__('Name'), esc_specialchars($order->customer_name ?: '—')); ?>
        <?php echo render_show(__('Email'), esc_specialchars($order->customer_email ?: '—')); ?>
        <?php echo render_show(__('Phone'), esc_specialchars($order->customer_phone ?: '—')); ?>
        <?php if ($order->billing_address): ?>
          <?php echo render_show(__('Billing Address'), nl2br(esc_specialchars($order->billing_address))); ?>
        <?php endif; ?>
        <?php if ($order->shipping_address): ?>
          <?php echo render_show(__('Shipping Address'), nl2br(esc_specialchars($order->shipping_address))); ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Update Status -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-cog me-2"></i>Update Status</div>
      <div class="card-body">
        <form method="post" action="<?php echo url_for(['module' => 'cart', 'action' => 'adminOrderDetail', 'id' => $order->id]); ?>">
          <div class="mb-3">
            <label class="form-label fw-bold">New Status</label>
            <select name="new_status" class="form-select">
              <?php foreach ($validStatuses as $s): ?>
                <option value="<?php echo $s; ?>" <?php echo $order->status === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Admin Notes</label>
            <textarea name="admin_notes" class="form-control" rows="2" placeholder="Optional note..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Update</button>
        </form>
      </div>
    </div>

    <!-- Downloads -->
    <?php if (!empty($downloads)): ?>
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><i class="fas fa-download me-2"></i>Downloads</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($downloads as $dl): ?>
          <li class="list-group-item d-flex justify-content-between">
            <small><code><?php echo substr($dl->token, 0, 12); ?>…</code></small>
            <span class="badge bg-<?php echo $dl->is_active ? 'success' : 'secondary'; ?>"><?php echo $dl->download_count; ?>/<?php echo $dl->max_downloads; ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php end_slot(); ?>
