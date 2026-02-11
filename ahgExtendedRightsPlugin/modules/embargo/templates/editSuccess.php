<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?>
  <h1><?php echo __('Edit Embargo'); ?></h1>
  <p class="lead"><?php echo $resource->title ?? $resource->slug; ?></p>
<?php end_slot(); ?>

<?php
// Get object ID for propagation count
$objectId = $embargo->object_id ?? $resource->id ?? 0;

// Get taxonomy options
$taxonomyService = new \ahgCorePlugin\Services\AhgTaxonomyService();
$embargoTypes = $taxonomyService->getEmbargoTypes(false);
$embargoReasons = $taxonomyService->getEmbargoReasons(true);
$embargoStatuses = $taxonomyService->getEmbargoStatuses(false);
?>

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
            <?php foreach ($embargoTypes as $code => $label): ?>
              <option value="<?php echo $code; ?>" <?php echo ($embargo->embargo_type ?? '') === $code ? 'selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="reason" class="form-label"><?php echo __('Reason'); ?></label>
          <select name="reason" id="reason" class="form-select">
            <?php foreach ($embargoReasons as $code => $label): ?>
              <option value="<?php echo $code; ?>" <?php echo ($embargo->reason ?? '') === $code ? 'selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
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
            <?php foreach ($embargoStatuses as $code => $label): ?>
              <option value="<?php echo $code; ?>" <?php echo ($embargo->status ?? '') === $code ? 'selected' : ''; ?>><?php echo __($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="notify_before_days" class="form-label"><?php echo __('Notify Days Before Expiry'); ?></label>
          <input type="number" name="notify_before_days" id="notify_before_days" class="form-control" value="<?php echo $embargo->notify_before_days ?? 30; ?>" min="0">
        </div>
      </div>
    </div>
  </div>

  <!-- Propagation Options -->
  <div class="card mb-4">
    <div class="card-header">
      <h4 class="mb-0"><i class="fas fa-sitemap me-2"></i><?php echo __('Apply to Hierarchy'); ?></h4>
    </div>
    <div class="card-body">
      <?php
      // Count descendants
      $io = \Illuminate\Database\Capsule\Manager::table('information_object')
          ->where('id', $objectId)
          ->select('lft', 'rgt')
          ->first();
      $descendantCount = 0;
      if ($io && $io->lft && $io->rgt) {
          $descendantCount = \Illuminate\Database\Capsule\Manager::table('information_object')
              ->where('lft', '>', $io->lft)
              ->where('rgt', '<', $io->rgt)
              ->count();
      }
      ?>
      <?php if ($descendantCount > 0): ?>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="apply_to_children" value="1" id="apply_to_children">
          <label class="form-check-label" for="apply_to_children">
            <strong><?php echo __('Apply changes to all descendants'); ?></strong>
            <span class="badge bg-info ms-2"><?php echo $descendantCount; ?> <?php echo __($descendantCount === 1 ? 'record' : 'records'); ?></span>
          </label>
          <div class="form-text text-muted">
            <?php echo __('This will create or update embargoes on all child records below this item.'); ?>
          </div>
        </div>
        <div class="alert alert-warning mb-0" id="propagation-warning" style="display: none;">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?php echo __('Warning: This will create new embargoes on descendants that do not have one, and update those that do.'); ?>
        </div>
      <?php else: ?>
        <p class="text-muted mb-0">
          <i class="fas fa-info-circle me-2"></i>
          <?php echo __('This record has no child records.'); ?>
        </p>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <button type="submit" class="btn btn-primary"><?php echo __('Save Changes'); ?></button>
    <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $resource->slug]); ?>" class="btn btn-secondary"><?php echo __('Cancel'); ?></a>
  </div>
</form>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('is_perpetual').addEventListener('change', function() {
  var endDateInput = document.getElementById('end_date');
  if (endDateInput) {
    endDateInput.disabled = this.checked;
    if (this.checked) {
      endDateInput.value = '';
    }
  }
});

// Show warning when propagation is selected
var propagationCheckbox = document.getElementById('apply_to_children');
if (propagationCheckbox) {
  propagationCheckbox.addEventListener('change', function() {
    var warning = document.getElementById('propagation-warning');
    if (warning) {
      warning.style.display = this.checked ? 'block' : 'none';
    }
  });
}
</script>
