<?php decorate_with('layout_1col.php'); ?>
<?php
/*
 * Record a digitisation-provenance entry (staff form).
 * PSIS twin of Heratio resources/views/provenance/create.blade.php.
 */
$object = $sf_data->getRaw('object');
$capability = $sf_data->getRaw('capability');
$digitalObjects = $sf_data->getRaw('digitalObjects');
$errors = $sf_data->getRaw('errors');
$old = $sf_data->getRaw('old');
$ioId = (int) $sf_data->getRaw('informationObjectId');
$h = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$val = static fn (string $k): string => htmlspecialchars((string) ($old[$k] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<?php slot('title'); ?>
<h1><i class="fas fa-fingerprint me-2" aria-hidden="true"></i><?php echo __('Record digitisation provenance'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted mb-0"><?php echo __('Information object'); ?>:
    <strong><?php echo $h($object->title ?? $object->identifier ?? ('#' . $object->id)); ?></strong>
    <span class="badge bg-secondary ms-1">IO #<?php echo $ioId; ?></span>
  </p>
  <a href="<?php echo url_for('@ahg_c2pa_admin_index?id=' . $ioId); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1" aria-hidden="true"></i><?php echo __('Back'); ?></a>
</div>

<div class="alert <?php echo $capability['can_sign_manifest'] ? 'alert-info' : 'alert-warning'; ?>">
  <i class="fas fa-info-circle me-1" aria-hidden="true"></i><?php echo $h($capability['summary']); ?>
</div>

<?php if (!empty($errors)) { ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) { ?><li><?php echo $h($e); ?></li><?php } ?></ul></div>
<?php } ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?php echo url_for('@ahg_c2pa_admin_record?id=' . $ioId); ?>">
      <input type="hidden" name="_ahg_csrf_token" value="<?php echo htmlspecialchars(class_exists('\AtomFramework\Services\CsrfService') ? \AtomFramework\Services\CsrfService::generateToken() : '', ENT_QUOTES); ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="c2pa-do"><?php echo __('Master digital object'); ?> <span class="badge bg-secondary ms-1"><?php echo __('Optional'); ?></span></label>
          <select id="c2pa-do" name="digital_object_id" class="form-select form-select-sm">
            <option value=""><?php echo __('- none / applies to the record as a whole -'); ?></option>
            <?php foreach ($digitalObjects as $do) { ?>
              <option value="<?php echo (int) $do->id; ?>"<?php echo (string) ($old['digital_object_id'] ?? '') === (string) $do->id ? ' selected' : ''; ?>>#<?php echo (int) $do->id; ?> - <?php echo $h($do->name ?: $do->path); ?> (<?php echo $h($do->mime_type ?: '?'); ?>)</option>
            <?php } ?>
          </select>
          <div class="form-text"><?php echo __('When chosen, the manifest hashes the real file, writes a .c2pa.json sidecar next to it and, where the format allows, an embedded copy.'); ?></div>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="c2pa-by"><?php echo __('Captured by'); ?></label>
          <input type="text" id="c2pa-by" name="captured_by" class="form-control form-control-sm" value="<?php echo $val('captured_by'); ?>" maxlength="255">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="c2pa-at"><?php echo __('Captured at'); ?></label>
          <input type="datetime-local" id="c2pa-at" name="captured_at" class="form-control form-control-sm" value="<?php echo $val('captured_at'); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="c2pa-device"><?php echo __('Capture device'); ?></label>
          <input type="text" id="c2pa-device" name="capture_device" class="form-control form-control-sm" value="<?php echo $val('capture_device'); ?>" maxlength="255" placeholder="<?php echo $h(__('e.g. Phase One iXG / Artec Leo')); ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="c2pa-software"><?php echo __('Capture software'); ?></label>
          <input type="text" id="c2pa-software" name="capture_software" class="form-control form-control-sm" value="<?php echo $val('capture_software'); ?>" maxlength="255" placeholder="<?php echo $h(__('e.g. Capture One 23')); ?>">
        </div>
        <div class="col-12">
          <label class="form-label" for="c2pa-notes"><?php echo __('Notes'); ?></label>
          <textarea id="c2pa-notes" name="notes" class="form-control form-control-sm" rows="3" maxlength="65535"><?php echo $val('notes'); ?></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-certificate me-1" aria-hidden="true"></i><?php echo __('Create and sign provenance record'); ?></button>
      </div>
    </form>
  </div>
</div>
<?php end_slot(); ?>
