<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $sf_data->getRaw('subscriptionId') ? __('Edit Subscription') : __('New Subscription'); ?></h1>
<?php end_slot(); ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<?php $rawSub = $sf_data->getRaw('subscription'); ?>

<div class="card shadow-sm">
  <div class="card-header bg-primary text-white">
    <i class="fas fa-newspaper me-2"></i>
    <?php echo $sf_data->getRaw('subscriptionId') ? __('Edit Subscription') : __('New Subscription'); ?>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo url_for(['module' => 'serial', 'action' => 'edit',
      'id' => $sf_data->getRaw('subscriptionId') ?: null]); ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label for="sub_library_item_id" class="form-label"><?php echo __('Library item ID'); ?> <span class="text-danger">*</span></label>
          <input type="number" class="form-control" id="sub_library_item_id" name="library_item_id"
                 value="<?php echo (int) ($rawSub->library_item_id ?? ''); ?>" required>
          <div class="form-text"><?php echo __('The ID of the library item this subscription is for.'); ?></div>
        </div>

        <div class="col-md-6">
          <label for="sub_vendor_name" class="form-label"><?php echo __('Vendor name'); ?></label>
          <input type="text" class="form-control" id="sub_vendor_name" name="vendor_name"
                 value="<?php echo esc_entities($rawSub->vendor_name ?? ''); ?>">
        </div>

        <div class="col-md-6">
          <label for="sub_subscription_number" class="form-label"><?php echo __('Subscription number'); ?></label>
          <input type="text" class="form-control" id="sub_subscription_number" name="subscription_number"
                 value="<?php echo esc_entities($rawSub->subscription_number ?? ''); ?>">
        </div>

        <div class="col-md-6">
          <label for="sub_frequency" class="form-label"><?php echo __('Frequency'); ?></label>
          <select class="form-select" id="sub_frequency" name="frequency">
            <?php
              $frequencies = [
                  'daily' => __('Daily'),
                  'weekly' => __('Weekly'),
                  'biweekly' => __('Biweekly'),
                  'monthly' => __('Monthly'),
                  'bimonthly' => __('Bimonthly'),
                  'quarterly' => __('Quarterly'),
                  'triannual' => __('Triannual'),
                  'semiannual' => __('Semiannual'),
                  'annual' => __('Annual'),
                  'biennial' => __('Biennial'),
              ];
              $currentFreq = $rawSub->frequency ?? 'monthly';
              foreach ($frequencies as $val => $label):
            ?>
              <option value="<?php echo $val; ?>" <?php echo $currentFreq === $val ? 'selected' : ''; ?>>
                <?php echo $label; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label for="sub_start_date" class="form-label"><?php echo __('Start date'); ?></label>
          <input type="date" class="form-control" id="sub_start_date" name="start_date"
                 value="<?php echo esc_entities($rawSub->start_date ?? ''); ?>">
        </div>

        <div class="col-md-4">
          <label for="sub_end_date" class="form-label"><?php echo __('End date'); ?></label>
          <input type="date" class="form-control" id="sub_end_date" name="end_date"
                 value="<?php echo esc_entities($rawSub->end_date ?? ''); ?>">
        </div>

        <div class="col-md-4">
          <label for="sub_renewal_date" class="form-label"><?php echo __('Renewal date'); ?></label>
          <input type="date" class="form-control" id="sub_renewal_date" name="renewal_date"
                 value="<?php echo esc_entities($rawSub->renewal_date ?? ''); ?>">
        </div>

        <div class="col-md-4">
          <label for="sub_expected_issues" class="form-label"><?php echo __('Expected issues/year'); ?></label>
          <input type="number" class="form-control" id="sub_expected_issues" name="expected_issues_year"
                 value="<?php echo (int) ($rawSub->expected_issues_year ?? 12); ?>" min="1">
        </div>

        <div class="col-md-4">
          <label for="sub_cost" class="form-label"><?php echo __('Cost per year'); ?></label>
          <input type="number" class="form-control" id="sub_cost" name="cost_per_year"
                 value="<?php echo esc_entities($rawSub->cost_per_year ?? ''); ?>" step="0.01" min="0">
        </div>

        <div class="col-md-4">
          <label for="sub_currency" class="form-label"><?php echo __('Currency'); ?></label>
          <select class="form-select" id="sub_currency" name="currency">
            <?php
              $currencies = ['USD', 'EUR', 'GBP', 'ZAR', 'CAD', 'AUD'];
              $currentCurr = $rawSub->currency ?? 'USD';
              foreach ($currencies as $c):
            ?>
              <option value="<?php echo $c; ?>" <?php echo $currentCurr === $c ? 'selected' : ''; ?>>
                <?php echo $c; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label for="sub_notes" class="form-label"><?php echo __('Notes'); ?></label>
          <textarea class="form-control" id="sub_notes" name="notes" rows="3"><?php echo esc_entities($rawSub->notes ?? ''); ?></textarea>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
        </button>
        <a href="<?php echo url_for(['module' => 'serial', 'action' => 'index']); ?>" class="btn btn-outline-secondary ms-2">
          <?php echo __('Cancel'); ?>
        </a>
      </div>
    </form>
  </div>
</div>
