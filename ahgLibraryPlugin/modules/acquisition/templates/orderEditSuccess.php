<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $sf_data->getRaw('order') ? __('Edit Order') : __('New Order'); ?></h1>
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
<?php $rawOrderTypes = $sf_data->getRaw('orderTypes'); ?>
<?php $rawBudgets = $sf_data->getRaw('budgets'); ?>

<div class="card shadow-sm">
  <div class="card-header">
    <i class="fas fa-file-invoice me-2"></i>
    <?php echo $rawOrder ? __('Edit Order: %1%', ['%1%' => $rawOrder->order_number]) : __('Create New Purchase Order'); ?>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'acquisition', 'action' => 'orderEdit', 'order_id' => $rawOrder ? $rawOrder->id : '']); ?>">
      <?php if ($rawOrder): ?>
        <input type="hidden" name="order_id" value="<?php echo (int) $rawOrder->id; ?>">
      <?php endif; ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label for="vendor_name" class="form-label"><?php echo __('Vendor name'); ?> <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="vendor_name" name="vendor_name"
                 value="<?php echo esc_entities($rawOrder->vendor_name ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
          <label for="vendor_account" class="form-label"><?php echo __('Vendor account'); ?></label>
          <input type="text" class="form-control" id="vendor_account" name="vendor_account"
                 value="<?php echo esc_entities($rawOrder->vendor_account ?? ''); ?>">
        </div>
        <div class="col-md-4">
          <label for="order_date" class="form-label"><?php echo __('Order date'); ?></label>
          <input type="date" class="form-control" id="order_date" name="order_date"
                 value="<?php echo esc_entities($rawOrder->order_date ?? date('Y-m-d')); ?>">
        </div>
        <div class="col-md-4">
          <label for="order_type" class="form-label"><?php echo __('Order type'); ?></label>
          <select class="form-select" id="order_type" name="order_type">
            <?php
              $defaultTypes = ['purchase', 'standing', 'gift', 'exchange', 'approval'];
              $currentType = $rawOrder->order_type ?? 'purchase';
              if (!empty($rawOrderTypes)) {
                  foreach ($rawOrderTypes as $ot) {
                      $val = is_object($ot) ? ($ot->value ?? $ot->name ?? '') : $ot;
                      $label = is_object($ot) ? ($ot->label ?? $ot->name ?? $val) : ucfirst($ot);
            ?>
              <option value="<?php echo esc_entities($val); ?>" <?php echo $currentType === $val ? 'selected' : ''; ?>>
                <?php echo esc_entities($label); ?>
              </option>
            <?php
                  }
              } else {
                  foreach ($defaultTypes as $dt) {
            ?>
              <option value="<?php echo $dt; ?>" <?php echo $currentType === $dt ? 'selected' : ''; ?>>
                <?php echo esc_entities(ucfirst($dt)); ?>
              </option>
            <?php
                  }
              }
            ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="budget_id" class="form-label"><?php echo __('Budget'); ?></label>
          <select class="form-select" id="budget_id" name="budget_id">
            <option value=""><?php echo __('— None —'); ?></option>
            <?php if (!empty($rawBudgets)): ?>
              <?php foreach ($rawBudgets as $budget): ?>
                <option value="<?php echo (int) $budget->id; ?>"
                  <?php echo (($rawOrder->budget_id ?? '') == $budget->id) ? 'selected' : ''; ?>>
                  <?php echo esc_entities($budget->budget_name . ' (' . $budget->budget_code . ')'); ?>
                </option>
              <?php endforeach; ?>
            <?php endif; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="currency" class="form-label"><?php echo __('Currency'); ?></label>
          <select class="form-select" id="currency" name="currency">
            <?php
              $currencies = ['USD', 'EUR', 'GBP', 'ZAR', 'CAD', 'AUD'];
              $currentCurrency = $rawOrder->currency ?? 'USD';
              foreach ($currencies as $c):
            ?>
              <option value="<?php echo $c; ?>" <?php echo $currentCurrency === $c ? 'selected' : ''; ?>>
                <?php echo $c; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-8">
          <label for="notes" class="form-label"><?php echo __('Notes'); ?></label>
          <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo esc_entities($rawOrder->notes ?? ''); ?></textarea>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i><?php echo $rawOrder ? __('Update Order') : __('Create Order'); ?>
        </button>
        <?php if ($rawOrder): ?>
          <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'order', 'order_id' => $rawOrder->id]); ?>" class="btn btn-outline-secondary">
            <?php echo __('Cancel'); ?>
          </a>
        <?php else: ?>
          <a href="<?php echo url_for(['module' => 'acquisition', 'action' => 'index']); ?>" class="btn btn-outline-secondary">
            <?php echo __('Cancel'); ?>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>
