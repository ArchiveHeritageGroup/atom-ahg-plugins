<?php
/*
 * heratio#145 — Strongroom create/edit (one template for both, switches on $room).
 * Action: strongroom/create or strongroom/edit.
 */
$capacityUnits = $sf_data->getRaw('capacityUnits');
$isNew = (null === $room);
$errors = isset($errors) ? $sf_data->getRaw('errors') : [];
$formData = isset($formData) ? $sf_data->getRaw('formData') : [];

// Resolve previous-value / current-value for sticky form fields.
$val = function ($key, $default = '') use ($formData, $room) {
    if (array_key_exists($key, $formData)) {
        return (string) $formData[$key];
    }
    if (null !== $room && isset($room->$key)) {
        return (string) $room->$key;
    }

    return (string) $default;
};

$formAction = $isNew
    ? url_for(['module' => 'strongroom', 'action' => 'create'])
    : url_for(['module' => 'strongroom', 'action' => 'edit', 'slug' => $room->slug]);
?>
<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo $isNew ? __('Add strongroom') : __('Edit %1%', ['%1%' => esc_specialchars($room->name)]); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
  <?php if (!empty($errors)) { ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach ($errors as $msg) { ?><li><?php echo esc_specialchars($msg); ?></li><?php } ?>
      </ul>
    </div>
  <?php } ?>

  <form method="post" action="<?php echo $formAction; ?>" class="mt-3" style="max-width: 48rem;">
    <div class="mb-3">
      <label for="sr_name" class="form-label fw-semibold"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
      <input type="text" id="sr_name" name="name" class="form-control" required maxlength="255"
             value="<?php echo esc_specialchars($val('name')); ?>">
      <div class="form-text"><?php echo __('Required. e.g. "Room A1" or "North annex shelving".'); ?></div>
    </div>

    <div class="mb-3">
      <label for="sr_location" class="form-label fw-semibold"><?php echo __('Location'); ?></label>
      <textarea id="sr_location" name="location_description" class="form-control" rows="2"><?php echo esc_specialchars($val('location_description')); ?></textarea>
      <div class="form-text"><?php echo __('Where in the building / on the site this room is. Free text.'); ?></div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <label for="sr_capacity_value" class="form-label fw-semibold"><?php echo __('Capacity'); ?></label>
        <input type="number" id="sr_capacity_value" name="capacity_value" class="form-control" min="0" step="0.01"
               value="<?php echo esc_specialchars($val('capacity_value')); ?>">
        <div class="form-text"><?php echo __('Leave blank if not tracking capacity.'); ?></div>
      </div>
      <div class="col-md-6">
        <?php $currentUnit = $val('capacity_unit', 'linear_meters'); ?>
        <label for="sr_capacity_unit" class="form-label fw-semibold"><?php echo __('Unit'); ?></label>
        <select id="sr_capacity_unit" name="capacity_unit" class="form-select">
          <?php foreach ($capacityUnits as $key => $label) { ?>
            <option value="<?php echo esc_specialchars($key); ?>"<?php echo ($key === $currentUnit) ? ' selected' : ''; ?>><?php echo __($label); ?></option>
          <?php } ?>
        </select>
      </div>
    </div>

    <div class="mb-4">
      <label for="sr_notes" class="form-label fw-semibold"><?php echo __('Notes'); ?></label>
      <textarea id="sr_notes" name="notes" class="form-control" rows="4"><?php echo esc_specialchars($val('notes')); ?></textarea>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i><?php echo $isNew ? __('Create strongroom') : __('Save changes'); ?>
      </button>
      <a href="<?php echo $isNew ? url_for(['module' => 'strongroom', 'action' => 'browse']) : url_for(['module' => 'strongroom', 'action' => 'show', 'slug' => $room->slug]); ?>"
         class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
    </div>
  </form>
<?php end_slot(); ?>
