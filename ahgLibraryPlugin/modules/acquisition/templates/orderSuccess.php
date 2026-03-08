<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Purchase Order'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($notice)): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $notice; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php $rawOrder = $sf_data->getRaw('order'); ?>
<?php $rawLines = $sf_data->getRaw('lines'); ?>

<?php
  $statusBadge = 'bg-secondary';
  switch ($rawOrder->order_status ?? '') {
      case 'pending': $statusBadge = 'bg-warning text-dark'; break;
      case 'partial': $statusBadge = 'bg-info text-dark'; break;
      case 'received': $statusBadge = 'bg-success'; break;
      case 'cancelled': $statusBadge = 'bg-danger'; break;
  }
?>

<!-- Order header card -->
<div class="card shadow-sm mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>
      <i class="fas fa-file-invoice me-2"></i>
      <strong><?php echo esc_entities($rawOrder->order_number ?? '-'); ?></strong>
    </span>
    <div class="d-flex gap-2">
      <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'orderEdit', 'order_id' => $rawOrder->id]); ?>"
         class="btn btn-sm btn-outline-primary">
        <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index']); ?>"
         class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to orders'); ?>
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <th class="text-muted" style="width:40%"><?php echo __('Vendor'); ?></th>
            <td class="fw-bold"><?php echo esc_entities($rawOrder->vendor_name ?? '-'); ?></td>
          </tr>
          <tr>
            <th class="text-muted"><?php echo __('Vendor account'); ?></th>
            <td><?php echo esc_entities($rawOrder->vendor_account ?? '-'); ?></td>
          </tr>
          <tr>
            <th class="text-muted"><?php echo __('Order date'); ?></th>
            <td><?php echo esc_entities($rawOrder->order_date ?? '-'); ?></td>
          </tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <th class="text-muted" style="width:40%"><?php echo __('Type'); ?></th>
            <td><?php echo esc_entities(ucfirst($rawOrder->order_type ?? '-')); ?></td>
          </tr>
          <tr>
            <th class="text-muted"><?php echo __('Status'); ?></th>
            <td><span class="badge <?php echo $statusBadge; ?>"><?php echo esc_entities(ucfirst($rawOrder->order_status ?? '-')); ?></span></td>
          </tr>
          <tr>
            <th class="text-muted"><?php echo __('Total'); ?></th>
            <td class="fw-bold"><?php echo esc_entities($rawOrder->currency ?? 'USD'); ?> <?php echo number_format((float) ($rawOrder->total_amount ?? 0), 2); ?></td>
          </tr>
        </table>
      </div>
    </div>
    <?php if (!empty($rawOrder->notes)): ?>
      <div class="mt-2">
        <small class="text-muted"><?php echo __('Notes'); ?>:</small>
        <p class="mb-0"><?php echo nl2br(esc_entities($rawOrder->notes)); ?></p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Order lines -->
<div class="card shadow-sm mb-4">
  <div class="card-header">
    <i class="fas fa-list me-2"></i><?php echo __('Order Lines'); ?>
    <span class="badge bg-secondary ms-2"><?php echo count($rawLines); ?></span>
  </div>
  <div class="card-body p-0">
    <?php if (empty($rawLines)): ?>
      <div class="p-3 text-muted">
        <i class="fas fa-info-circle me-2"></i>
        <?php echo __('No line items yet. Use the form below to add items.'); ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Title'); ?></th>
              <th><?php echo __('ISBN'); ?></th>
              <th class="text-center"><?php echo __('Qty ordered'); ?></th>
              <th class="text-center"><?php echo __('Qty received'); ?></th>
              <th class="text-end"><?php echo __('Unit price'); ?></th>
              <th class="text-end"><?php echo __('Line total'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th class="text-center"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawLines as $line): ?>
              <?php
                $lineBadge = 'bg-secondary';
                switch ($line->line_status ?? '') {
                    case 'pending': $lineBadge = 'bg-warning text-dark'; break;
                    case 'partial': $lineBadge = 'bg-info text-dark'; break;
                    case 'received': $lineBadge = 'bg-success'; break;
                    case 'cancelled': $lineBadge = 'bg-danger'; break;
                }
              ?>
              <tr>
                <td><?php echo esc_entities($line->title ?? '-'); ?></td>
                <td><code><?php echo esc_entities($line->isbn ?? $line->item_isbn ?? '-'); ?></code></td>
                <td class="text-center"><?php echo (int) $line->quantity; ?></td>
                <td class="text-center"><?php echo (int) $line->quantity_received; ?></td>
                <td class="text-end"><?php echo number_format((float) ($line->unit_price ?? 0), 2); ?></td>
                <td class="text-end"><?php echo number_format((float) ($line->line_total ?? 0), 2); ?></td>
                <td><span class="badge <?php echo $lineBadge; ?>"><?php echo esc_entities(ucfirst($line->line_status ?? '-')); ?></span></td>
                <td class="text-center">
                  <?php if (($line->line_status ?? '') !== 'received'): ?>
                    <form method="post" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'receive']); ?>" class="d-inline">
                      <input type="hidden" name="order_line_id" value="<?php echo (int) $line->id; ?>">
                      <div class="input-group input-group-sm" style="width:140px; display:inline-flex !important;">
                        <input type="number" class="form-control form-control-sm" name="quantity_received" value="1" min="1"
                               max="<?php echo max(1, (int) $line->quantity - (int) $line->quantity_received); ?>"
                               style="width:50px;">
                        <button type="submit" class="btn btn-sm btn-outline-success" title="<?php echo __('Receive'); ?>">
                          <i class="fas fa-check"></i>
                        </button>
                      </div>
                    </form>
                  <?php else: ?>
                    <span class="text-success"><i class="fas fa-check-circle"></i></span>
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

<!-- Add line form (collapsible) -->
<div class="card shadow-sm">
  <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#addLineForm" aria-expanded="false">
    <i class="fas fa-plus me-2"></i><?php echo __('Add Line Item'); ?>
    <i class="fas fa-chevron-down float-end mt-1"></i>
  </div>
  <div class="collapse" id="addLineForm">
    <div class="card-body">
      <form method="post" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'addLine']); ?>">
        <input type="hidden" name="order_id" value="<?php echo (int) $rawOrder->id; ?>">
        <div class="row g-3">
          <div class="col-md-4">
            <label for="line_title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="line_title" name="title" required>
          </div>
          <div class="col-md-2">
            <label for="line_isbn" class="form-label"><?php echo __('ISBN'); ?></label>
            <input type="text" class="form-control" id="line_isbn" name="isbn">
          </div>
          <div class="col-md-1">
            <label for="line_qty" class="form-label"><?php echo __('Qty'); ?></label>
            <input type="number" class="form-control" id="line_qty" name="quantity" value="1" min="1">
          </div>
          <div class="col-md-2">
            <label for="line_price" class="form-label"><?php echo __('Unit price'); ?></label>
            <input type="number" class="form-control" id="line_price" name="unit_price" step="0.01" min="0" value="0.00">
          </div>
          <div class="col-md-2">
            <label for="line_fund" class="form-label"><?php echo __('Fund code'); ?></label>
            <input type="text" class="form-control" id="line_fund" name="fund_code">
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <!-- spacer for alignment -->
          </div>
          <div class="col-md-10">
            <label for="line_notes" class="form-label"><?php echo __('Notes'); ?></label>
            <input type="text" class="form-control" id="line_notes" name="notes">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-plus me-1"></i><?php echo __('Add'); ?>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
