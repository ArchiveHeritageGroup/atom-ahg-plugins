<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-barcode me-2"></i><?php echo __('Batch Capture — ISBN Lookup'); ?></h1>
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

<?php
  $rawOrders        = $sf_data->getRaw('orders');
  $rawSelectedOrder = $sf_data->getRaw('selectedOrder');
  $rawOrderLines    = $sf_data->getRaw('orderLines');
  $rawLookupResults = $sf_data->getRaw('lookupResults');
  $rawLookupErrors  = $sf_data->getRaw('lookupErrors');
  $rawIsbns         = $sf_data->getRaw('rawIsbns');
  $selectedOrderId  = (int) $sf_data->getRaw('selectedOrderId');
?>

<!-- Card 1: Select Purchase Order -->
<div class="card shadow-sm mb-4">
  <div class="card-header">
    <i class="fas fa-file-invoice me-2"></i><?php echo __('Purchase Order (Optional)'); ?>
  </div>
  <div class="card-body">
    <form method="get" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'batchCapture']); ?>">
      <div class="row align-items-end">
        <div class="col-md-6">
          <label for="order_id" class="form-label"><?php echo __('Link items to a Purchase Order'); ?></label>
          <select class="form-select" id="order_id" name="order_id">
            <option value=""><?php echo __('— No PO (standalone capture) —'); ?></option>
            <?php if (!empty($rawOrders)): ?>
              <?php foreach ($rawOrders as $order): ?>
                <option value="<?php echo (int) $order->id; ?>"
                  <?php echo $selectedOrderId == $order->id ? 'selected' : ''; ?>>
                  <?php echo esc_entities($order->order_number); ?>
                  — <?php echo esc_entities($order->vendor_name ?? ''); ?>
                  (<?php echo esc_entities($order->status); ?>)
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-outline-primary">
            <i class="fas fa-sync-alt me-1"></i><?php echo __('Load Order'); ?>
          </button>
        </div>
      </div>
    </form>

    <?php if ($rawSelectedOrder): ?>
      <hr>
      <div class="row">
        <div class="col-md-4">
          <strong><?php echo __('Order'); ?>:</strong>
          <?php echo esc_entities($rawSelectedOrder->order_number); ?>
        </div>
        <div class="col-md-4">
          <strong><?php echo __('Vendor'); ?>:</strong>
          <?php echo esc_entities($rawSelectedOrder->vendor_name ?? '-'); ?>
        </div>
        <div class="col-md-4">
          <strong><?php echo __('Status'); ?>:</strong>
          <span class="badge bg-info text-dark"><?php echo esc_entities($rawSelectedOrder->status); ?></span>
        </div>
      </div>
      <?php if (!empty($rawOrderLines)): ?>
        <div class="mt-2 small text-muted">
          <?php echo __('%1% line item(s) on this order.', ['%1%' => count($rawOrderLines)]); ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Card 2: ISBN Input -->
<div class="card shadow-sm mb-4">
  <div class="card-header">
    <i class="fas fa-search me-2"></i><?php echo __('ISBN Lookup'); ?>
  </div>
  <div class="card-body">
    <form id="lookupForm" method="post" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'batchCapture']); ?>">
      <input type="hidden" name="action_type" value="lookup">
      <input type="hidden" name="order_id" value="<?php echo $selectedOrderId; ?>">
      <?php echo $sf_data->getRaw('sf_request')->getCSRFFormTag(); ?>

      <div class="mb-3">
        <label for="isbns" class="form-label"><?php echo __('Enter ISBNs (one per line)'); ?></label>
        <textarea class="form-control font-monospace" id="isbns" name="isbns" rows="6"
                  placeholder="978-0-13-468599-1&#10;978-0-596-51774-8&#10;0-321-12521-5"><?php echo esc_entities($rawIsbns); ?></textarea>
        <div class="form-text"><?php echo __('Enter ISBN-10 or ISBN-13 values, one per line. Hyphens are optional.'); ?></div>
      </div>

      <button type="submit" class="btn btn-primary" id="btnLookup">
        <i class="fas fa-search me-1"></i><?php echo __('Lookup ISBNs'); ?>
      </button>
    </form>
  </div>
</div>

<!-- Lookup errors -->
<?php if (!empty($rawLookupErrors)): ?>
  <div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong><?php echo __('Some lookups had issues:'); ?></strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($rawLookupErrors as $err): ?>
        <li><?php echo esc_entities($err); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Card 3: Review & Save Results -->
<?php if (!empty($rawLookupResults)): ?>
<div class="card shadow-sm mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-list-check me-2"></i><?php echo __('Review Results (%1% found)', ['%1%' => count($rawLookupResults)]); ?></span>
    <div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectAll"><?php echo __('Select All'); ?></button>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll"><?php echo __('Deselect All'); ?></button>
    </div>
  </div>
  <div class="card-body p-0">
    <form id="saveForm" method="post" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'batchCapture']); ?>">
      <input type="hidden" name="action_type" value="save">
      <input type="hidden" name="order_id" value="<?php echo $selectedOrderId; ?>">
      <?php echo $sf_data->getRaw('sf_request')->getCSRFFormTag(); ?>

      <div class="table-responsive">
        <table class="table table-hover table-striped mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:50px" class="text-center"><?php echo __('Include'); ?></th>
              <th style="width:80px"><?php echo __('Cover'); ?></th>
              <th><?php echo __('ISBN'); ?></th>
              <th><?php echo __('Title'); ?></th>
              <th><?php echo __('Author(s)'); ?></th>
              <th><?php echo __('Publisher'); ?></th>
              <th style="width:80px"><?php echo __('Year'); ?></th>
              <th style="width:70px"><?php echo __('Pages'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rawLookupResults as $idx => $result): ?>
              <tr>
                <td class="text-center align-middle">
                  <input type="checkbox" class="form-check-input item-check" name="items[<?php echo $idx; ?>][include]" value="1" checked>
                </td>
                <td class="align-middle">
                  <?php if (!empty($result['cover_url'])): ?>
                    <img src="<?php echo esc_entities($result['cover_url']); ?>" alt="Cover"
                         style="max-height:60px; max-width:60px" class="rounded shadow-sm">
                  <?php else: ?>
                    <span class="text-muted"><i class="fas fa-book fa-2x"></i></span>
                  <?php endif; ?>
                  <input type="hidden" name="items[<?php echo $idx; ?>][cover_url]" value="<?php echo esc_entities($result['cover_url'] ?? ''); ?>">
                </td>
                <td class="align-middle">
                  <input type="text" class="form-control form-control-sm" name="items[<?php echo $idx; ?>][isbn]"
                         value="<?php echo esc_entities($result['isbn'] ?? $result['isbn_input'] ?? ''); ?>" style="width:160px">
                </td>
                <td class="align-middle">
                  <input type="text" class="form-control form-control-sm" name="items[<?php echo $idx; ?>][title]"
                         value="<?php echo esc_entities($result['title'] ?? ''); ?>">
                  <?php if (!empty($result['subtitle'])): ?>
                    <input type="hidden" name="items[<?php echo $idx; ?>][subtitle]" value="<?php echo esc_entities($result['subtitle']); ?>">
                  <?php endif; ?>
                </td>
                <td class="align-middle">
                  <input type="text" class="form-control form-control-sm" name="items[<?php echo $idx; ?>][author]"
                         value="<?php echo esc_entities($result['creator'] ?? ''); ?>">
                </td>
                <td class="align-middle">
                  <input type="text" class="form-control form-control-sm" name="items[<?php echo $idx; ?>][publisher]"
                         value="<?php echo esc_entities($result['publisher'] ?? ''); ?>">
                </td>
                <td class="align-middle">
                  <input type="text" class="form-control form-control-sm" name="items[<?php echo $idx; ?>][year]"
                         value="<?php echo esc_entities($result['date_of_publication'] ?? ''); ?>" style="width:80px">
                </td>
                <td class="align-middle">
                  <input type="text" class="form-control form-control-sm" name="items[<?php echo $idx; ?>][pages]"
                         value="<?php echo esc_entities(str_replace(' pages', '', $result['extent'] ?? '')); ?>" style="width:70px">
                </td>
              </tr>
              <!-- Hidden fields for additional data -->
              <input type="hidden" name="items[<?php echo $idx; ?>][place_of_publication]" value="<?php echo esc_entities($result['place_of_publication'] ?? ''); ?>">
              <input type="hidden" name="items[<?php echo $idx; ?>][lccn]" value="<?php echo esc_entities($result['lccn'] ?? ''); ?>">
              <input type="hidden" name="items[<?php echo $idx; ?>][oclc_number]" value="<?php echo esc_entities($result['oclc_number'] ?? ''); ?>">
              <input type="hidden" name="items[<?php echo $idx; ?>][language]" value="<?php echo esc_entities($result['language'] ?? ''); ?>">
              <input type="hidden" name="items[<?php echo $idx; ?>][description]" value="<?php echo esc_entities($result['scope_and_content'] ?? ''); ?>">
              <input type="hidden" name="items[<?php echo $idx; ?>][subjects]" value="<?php echo esc_entities(implode('; ', $result['subjects'] ?? [])); ?>">
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-center">
        <span class="text-muted" id="selectedCount">
          <?php echo __('%1% item(s) selected', ['%1%' => count($rawLookupResults)]); ?>
        </span>
        <div class="d-flex gap-2">
          <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'batchCapture', 'order_id' => $selectedOrderId ?: null]); ?>"
             class="btn btn-outline-secondary">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
          </a>
          <button type="submit" class="btn btn-success" id="btnSave">
            <i class="fas fa-save me-1"></i><?php echo __('Save Selected Items'); ?>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Back link -->
<div class="mt-3">
  <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Acquisitions'); ?>
  </a>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  // Select All / Deselect All
  var btnSelectAll = document.getElementById('btnSelectAll');
  var btnDeselectAll = document.getElementById('btnDeselectAll');
  var checkboxes = document.querySelectorAll('.item-check');

  function updateCount() {
    var checked = document.querySelectorAll('.item-check:checked').length;
    var el = document.getElementById('selectedCount');
    if (el) {
      el.textContent = checked + ' item(s) selected';
    }
  }

  if (btnSelectAll) {
    btnSelectAll.addEventListener('click', function() {
      checkboxes.forEach(function(cb) { cb.checked = true; });
      updateCount();
    });
  }

  if (btnDeselectAll) {
    btnDeselectAll.addEventListener('click', function() {
      checkboxes.forEach(function(cb) { cb.checked = false; });
      updateCount();
    });
  }

  checkboxes.forEach(function(cb) {
    cb.addEventListener('change', updateCount);
  });

  // Disable save button on submit to prevent double-click
  var saveForm = document.getElementById('saveForm');
  if (saveForm) {
    saveForm.addEventListener('submit', function() {
      var btn = document.getElementById('btnSave');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
      }
    });
  }

  // Disable lookup button on submit
  var lookupForm = document.getElementById('lookupForm');
  if (lookupForm) {
    lookupForm.addEventListener('submit', function() {
      var btn = document.getElementById('btnLookup');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Looking up...';
      }
    });
  }
});
</script>
