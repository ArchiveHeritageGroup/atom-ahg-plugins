<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?>
  <h1><?php echo __('Edit Embargo'); ?></h1>
  <p class="lead"><?php echo render_title($resource); ?></p>
<?php end_slot(); ?>
<form method="post" action="<?php echo url_for(['module' => 'embargo', 'action' => 'edit', 'id' => $embargo->id]); ?>">
  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0"><?php echo __('Embargo Details'); ?></h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="embargo_type" class="form-label"><?php echo __('Embargo Type'); ?> <span class="text-danger">*</span></label>
          <select name="embargo_type" id="embargo_type" class="form-select" required>
            <option value="full" <?php echo ($embargo->embargo_type ?? '') === 'full' ? 'selected' : ''; ?>><?php echo __('Full - Hide completely'); ?></option>
            <option value="metadata_only" <?php echo ($embargo->embargo_type ?? '') === 'metadata_only' ? 'selected' : ''; ?>><?php echo __('Metadata Only - Hide digital objects'); ?></option>
            <option value="digital_only" <?php echo ($embargo->embargo_type ?? '') === 'digital_only' ? 'selected' : ''; ?>><?php echo __('Digital Object - Restrict downloads'); ?></option>
            <option value="partial" <?php echo ($embargo->embargo_type ?? '') === 'partial' ? 'selected' : ''; ?>><?php echo __('Partial'); ?></option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="reason" class="form-label"><?php echo __('Reason'); ?></label>
          <select name="reason" id="reason" class="form-select">
            <option value="">-- Select --</option>
            <option value="donor_restriction" <?php echo ($embargo->reason ?? '') === 'donor_restriction' ? 'selected' : ''; ?>><?php echo __('Donor Restriction'); ?></option>
            <option value="copyright" <?php echo ($embargo->reason ?? '') === 'copyright' ? 'selected' : ''; ?>><?php echo __('Copyright'); ?></option>
            <option value="privacy" <?php echo ($embargo->reason ?? '') === 'privacy' ? 'selected' : ''; ?>><?php echo __('Privacy'); ?></option>
            <option value="legal" <?php echo ($embargo->reason ?? '') === 'legal' ? 'selected' : ''; ?>><?php echo __('Legal'); ?></option>
            <option value="commercial" <?php echo ($embargo->reason ?? '') === 'commercial' ? 'selected' : ''; ?>><?php echo __('Commercial'); ?></option>
            <option value="research" <?php echo ($embargo->reason ?? '') === 'research' ? 'selected' : ''; ?>><?php echo __('Research'); ?></option>
            <option value="cultural" <?php echo ($embargo->reason ?? '') === 'cultural' ? 'selected' : ''; ?>><?php echo __('Cultural'); ?></option>
            <option value="security" <?php echo ($embargo->reason ?? '') === 'security' ? 'selected' : ''; ?>><?php echo __('Security'); ?></option>
            <option value="other" <?php echo ($embargo->reason ?? '') === 'other' ? 'selected' : ''; ?>><?php echo __('Other'); ?></option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4 mb-3">
          <label for="start_date" class="form-label"><?php echo __('Start Date'); ?> <span class="text-danger">*</span></label>
          <input type="date" name="start_date" id="start_date" class="form-control" required value="<?php echo $embargo->start_date ?? date('Y-m-d'); ?>">
        </div>
        <div class="col-md-4 mb-3">
          <label for="end_date" class="form-label"><?php echo __('End Date'); ?></label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $embargo->end_date ?? ''; ?>">
          <small class="text-muted"><?php echo __('Leave blank for perpetual embargo'); ?></small>
        </div>
        <div class="col-md-4 mb-3 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_perpetual" value="1" id="is_perpetual" <?php echo empty($embargo->auto_release) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_perpetual">
              <?php echo __('Perpetual (no end date)'); ?>
            </label>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="status" class="form-label"><?php echo __('Status'); ?></label>
          <select name="status" id="status" class="form-select">
            <option value="active" <?php echo ($embargo->status ?? '') === 'active' ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
            <option value="pending" <?php echo ($embargo->status ?? '') === 'pending' ? 'selected' : ''; ?>><?php echo __('Pending'); ?></option>
            <option value="extended" <?php echo ($embargo->status ?? '') === 'extended' ? 'selected' : ''; ?>><?php echo __('Extended'); ?></option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="notify_before_days" class="form-label"><?php echo __('Notify Days Before Expiry'); ?></label>
          <input type="number" name="notify_before_days" id="notify_before_days" class="form-control" value="<?php echo $embargo->notify_before_days ?? 30; ?>" min="0">
        </div>
      </div>
    </div>
  </div>
  <div class="d-flex gap-2 justify-content-end">
    <button type="submit" class="btn btn-primary"><?php echo __('Save Changes'); ?></button>
    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
  </div>
</form>
