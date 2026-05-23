<?php /* heratio#146 PSIS port — Exhibition space create/edit */ ?>
<?php $isNew = ($space === null); ?>
<div class="container-fluid px-4 py-3 edit exhibition-space">
  <h1><i class="fas fa-palette me-2"></i><?php echo $isNew ? __('Add exhibition space') : __('Edit %1%', ['%1%' => $space->name]) ?></h1>

  <?php if ($sf_user->hasFlash('error')): ?>
    <div class="alert alert-danger"><?php echo $sf_user->getFlash('error') ?></div>
  <?php endif ?>

  <form method="post" action="<?php echo $isNew
      ? url_for(['module' => 'exhibitionSpace', 'action' => 'create'])
      : url_for(['module' => 'exhibitionSpace', 'action' => 'edit', 'slug' => $space->slug]) ?>"
        class="mt-3" style="max-width: 56rem;">

    <div class="row g-3">
      <div class="col-md-8">
        <label for="es_name" class="form-label fw-semibold"><?php echo __('Name') ?> <span class="text-danger">*</span></label>
        <input type="text" id="es_name" name="name" class="form-control" required maxlength="255"
               value="<?php echo esc_entities($space->name ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label for="es_space_type" class="form-label fw-semibold"><?php echo __('Type') ?></label>
        <select id="es_space_type" name="space_type" class="form-select">
          <?php $currentType = $space->space_type ?? 'gallery'; foreach ($spaceTypes as $key => $label): ?>
            <option value="<?php echo $key ?>" <?php echo $key === $currentType ? 'selected' : '' ?>><?php echo __($label) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <label for="es_building" class="form-label fw-semibold"><?php echo __('Building') ?></label>
        <input type="text" id="es_building" name="building" class="form-control" value="<?php echo esc_entities($space->building ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label for="es_floor" class="form-label fw-semibold"><?php echo __('Floor') ?></label>
        <input type="text" id="es_floor" name="floor" class="form-control" value="<?php echo esc_entities($space->floor ?? '') ?>">
      </div>
    </div>

    <div class="row g-3 mt-1">
      <div class="col-md-4">
        <label for="es_capacity_value" class="form-label fw-semibold"><?php echo __('Capacity') ?></label>
        <input type="number" id="es_capacity_value" name="capacity_value" class="form-control"
               min="0" step="0.01" value="<?php echo esc_entities($space->capacity_value ?? '') ?>">
        <small class="text-muted"><?php echo __('Leave blank if not tracking capacity.') ?></small>
      </div>
      <div class="col-md-4">
        <label for="es_capacity_unit" class="form-label fw-semibold"><?php echo __('Unit') ?></label>
        <select id="es_capacity_unit" name="capacity_unit" class="form-select">
          <?php $currentUnit = $space->capacity_unit ?? 'linear_wall_meters'; foreach ($capacityUnits as $key => $label): ?>
            <option value="<?php echo $key ?>" <?php echo $key === $currentUnit ? 'selected' : '' ?>><?php echo __($label) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-4">
        <label for="es_lux" class="form-label fw-semibold"><?php echo __('Lighting target (lux)') ?></label>
        <input type="number" id="es_lux" name="lighting_lux_target" class="form-control"
               min="0" step="0.01" value="<?php echo esc_entities($space->lighting_lux_target ?? '') ?>">
      </div>
    </div>

    <div class="mb-3 mt-3">
      <label for="es_notes" class="form-label fw-semibold"><?php echo __('Notes') ?></label>
      <textarea id="es_notes" name="notes" class="form-control" rows="4"><?php echo esc_entities($space->notes ?? '') ?></textarea>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i><?php echo $isNew ? __('Create exhibition space') : __('Save changes') ?>
      </button>
      <a href="<?php echo $isNew
          ? url_for(['module' => 'exhibitionSpace', 'action' => 'browse'])
          : url_for(['module' => 'exhibitionSpace', 'action' => 'show', 'slug' => $space->slug]) ?>"
         class="btn btn-outline-secondary"><?php echo __('Cancel') ?></a>
    </div>
  </form>
</div>
