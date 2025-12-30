<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Add Embargo'); ?></h1>
  <p class="lead"><?php echo render_title($resource); ?></p>
<?php end_slot(); ?>

<form method="post" action="<?php echo url_for(['module' => 'embargo', 'action' => 'add', 'slug' => $resource->slug]); ?>">

  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0"><?php echo __('Embargo Details'); ?></h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="embargo_type" class="form-label"><?php echo __('Embargo Type'); ?> <span class="text-danger">*</span></label>
          <select name="embargo_type" id="embargo_type" class="form-select" required>
            <option value="full"><?php echo __('Full - Hide completely'); ?></option>
            <option value="metadata_only"><?php echo __('Metadata Only - Hide digital objects'); ?></option>
            <option value="digital_object"><?php echo __('Digital Object - Restrict downloads'); ?></option>
            <option value="custom"><?php echo __('Custom'); ?></option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col-md-4 mb-3">
          <label for="start_date" class="form-label"><?php echo __('Start Date'); ?> <span class="text-danger">*</span></label>
          <input type="date" name="start_date" id="start_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label for="end_date" class="form-label"><?php echo __('End Date'); ?></label>
          <input type="date" name="end_date" id="end_date" class="form-control" id="end_date_input">
          <small class="text-muted"><?php echo __('Leave blank for perpetual embargo'); ?></small>
        </div>
        <div class="col-md-4 mb-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_perpetual" value="1" id="is_perpetual">
            <label class="form-check-label" for="is_perpetual">
              <?php echo __('Perpetual (no end date)'); ?>
            </label>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="reason" class="form-label"><?php echo __('Reason'); ?></label>
        <input type="text" name="reason" id="reason" class="form-control" placeholder="<?php echo __('e.g., Donor restriction, Privacy concerns, Legal hold'); ?>">
      </div>

      <div class="mb-3">
        <label for="public_message" class="form-label"><?php echo __('Public Message'); ?></label>
        <textarea name="public_message" id="public_message" class="form-control" rows="2" placeholder="<?php echo __('Message displayed to users when they encounter this embargo'); ?>"></textarea>
      </div>

      <div class="mb-3">
        <label for="notes" class="form-label"><?php echo __('Internal Notes'); ?></label>
        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0"><?php echo __('Notifications'); ?></h4>
    </div>
    <div class="card-body">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="notify_on_expiry" value="1" id="notify_on_expiry" checked>
        <label class="form-check-label" for="notify_on_expiry">
          <?php echo __('Send notification before expiry'); ?>
        </label>
      </div>

      <div class="row">
        <div class="col-md-4">
          <label for="notify_days_before" class="form-label"><?php echo __('Notify days before expiry'); ?></label>
          <input type="number" name="notify_days_before" id="notify_days_before" class="form-control" value="30" min="1" max="365">
        </div>
      </div>
    </div>
  </div>

  <div class="actions">
    <a href="<?php echo url_for(['module' => 'informationobject', 'action' => 'browse', 'slug' => $resource->slug]); ?>" class="btn btn-secondary">
      <?php echo __('Cancel'); ?>
    </a>
    <button type="submit" class="btn btn-danger">
      <i class="fas fa-lock"></i> <?php echo __('Create Embargo'); ?>
    </button>
  </div>

</form>

<script>
document.getElementById('is_perpetual').addEventListener('change', function() {
  document.getElementById('end_date_input').disabled = this.checked;
  if (this.checked) {
    document.getElementById('end_date_input').value = '';
  }
});
</script>
