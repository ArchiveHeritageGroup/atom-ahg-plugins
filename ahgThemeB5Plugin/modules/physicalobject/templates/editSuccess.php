<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0"><?php echo render_title($resource); ?></h1>
    <span class="small"><?php echo __('Edit %1%', ['%1%' => sfConfig::get('app_ui_label_physicalobject')]); ?></span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php if ($resource->id): ?>
  <form method="post" action="<?php echo url_for([$resource, 'module' => 'physicalobject', 'action' => 'edit']); ?>">
<?php else: ?>
  <form method="post" action="<?php echo url_for(['module' => 'physicalobject', 'action' => 'add']); ?>">
<?php endif; ?>

  <div class="row">
    <div class="col-md-8">

      <!-- Basic Information -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i><?php echo __('Basic Information'); ?></h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label" for="po-name"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                <input id="po-name" type="text" name="name" class="form-control" required
                       value="<?php echo esc_entities($resource->id ? $resource->getName(['cultureFallback' => true]) : ''); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label" for="po-type"><?php echo __('Type'); ?></label>
                <select id="po-type" name="type" class="form-select">
                  <option value=""><?php echo __('Select...'); ?></option>
                  <?php foreach ($typeChoices as $url => $label): ?>
                    <option value="<?php echo $url; ?>" <?php echo ($resource->type && $url === $sf_context->routing->generate(null, [$resource->type, 'module' => 'term'])) ? 'selected' : ''; ?>>
                      <?php echo $label; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label" for="po-location"><?php echo __('Location (legacy)'); ?></label>
            <input id="po-location" type="text" name="location" class="form-control"
                   value="<?php echo esc_entities($resource->id ? $resource->getLocation(['cultureFallback' => true]) : ''); ?>"
                   placeholder="<?php echo __('Use extended location fields below instead'); ?>">
            <small class="text-muted"><?php echo __('For backwards compatibility. Use the detailed fields below.'); ?></small>
          </div>
        </div>
      </div>

      <!-- Extended Location -->
      <?php // Building ... Shelf come from the storage location tree when the box is placed there. ?>
      <?php $loc = null !== ($storageLevels ?? null) ? $storageLevels : $extendedData; ?>
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo __('Location Details'); ?></h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-building"><?php echo __('Building'); ?></label>
                <input id="po-building" type="text" name="building" class="form-control"
                       value="<?php echo esc_entities($loc['building'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-floor"><?php echo __('Floor'); ?></label>
                <input id="po-floor" type="text" name="floor" class="form-control"
                       value="<?php echo esc_entities($loc['floor'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-room"><?php echo __('Room'); ?></label>
                <input id="po-room" type="text" name="room" class="form-control"
                       value="<?php echo esc_entities($loc['room'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label" for="po-aisle"><?php echo __('Aisle'); ?></label>
                <input id="po-aisle" type="text" name="aisle" class="form-control"
                       value="<?php echo esc_entities($loc['aisle'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label" for="po-bay"><?php echo __('Bay'); ?></label>
                <input id="po-bay" type="text" name="bay" class="form-control"
                       value="<?php echo esc_entities($loc['bay'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label" for="po-rack"><?php echo __('Rack'); ?></label>
                <input id="po-rack" type="text" name="rack" class="form-control"
                       value="<?php echo esc_entities($loc['rack'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label" for="po-shelf"><?php echo __('Shelf'); ?></label>
                <input id="po-shelf" type="text" name="shelf" class="form-control"
                       value="<?php echo esc_entities($loc['shelf'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <?php if (class_exists('\\AhgStorageManage\\Services\\StoragePlacementService') && \AhgStorageManage\Services\StoragePlacementService::available()): ?>
            <div class="mb-3"><?php include_partial('storageLocation/locationPickers'); ?></div>
          <?php endif; ?>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-position"><?php echo __('Position'); ?></label>
                <input id="po-position" type="text" name="position" class="form-control"
                       value="<?php echo esc_entities($extendedData['position'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-barcode"><?php echo __('Barcode'); ?></label>
                <input id="po-barcode" type="text" name="barcode" class="form-control"
                       value="<?php echo esc_entities($extendedData['barcode'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-reference_code"><?php echo __('Reference Code'); ?></label>
                <input id="po-reference_code" type="text" name="reference_code" class="form-control"
                       value="<?php echo esc_entities($extendedData['reference_code'] ?? ''); ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dimensions -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-ruler-combined me-2"></i><?php echo __('Dimensions (cm)'); ?></h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-width"><?php echo __('Width'); ?></label>
                <input id="po-width" type="number" step="0.01" name="width" class="form-control"
                       value="<?php echo esc_entities($extendedData['width'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-height"><?php echo __('Height'); ?></label>
                <input id="po-height" type="number" step="0.01" name="height" class="form-control"
                       value="<?php echo esc_entities($extendedData['height'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-depth"><?php echo __('Depth'); ?></label>
                <input id="po-depth" type="number" step="0.01" name="depth" class="form-control"
                       value="<?php echo esc_entities($extendedData['depth'] ?? ''); ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Capacity Tracking -->
      <div class="card mb-4">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0"><i class="fas fa-boxes me-2"></i><?php echo __('Capacity Tracking'); ?></h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-total_capacity"><?php echo __('Total Capacity'); ?></label>
                <input id="po-total_capacity" type="number" name="total_capacity" class="form-control"
                       value="<?php echo esc_entities($extendedData['total_capacity'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-used_capacity"><?php echo __('Used Capacity'); ?></label>
                <input id="po-used_capacity" type="number" name="used_capacity" class="form-control"
                       value="<?php echo esc_entities($extendedData['used_capacity'] ?? 0); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label" for="po-capacity_unit"><?php echo __('Capacity Unit'); ?></label>
                <select id="po-capacity_unit" name="capacity_unit" class="form-select">
                  <option value=""><?php echo __('Select...'); ?></option>
                  <option value="boxes" <?php echo ($extendedData['capacity_unit'] ?? '') === 'boxes' ? 'selected' : ''; ?>><?php echo __('Boxes'); ?></option>
                  <option value="files" <?php echo ($extendedData['capacity_unit'] ?? '') === 'files' ? 'selected' : ''; ?>><?php echo __('Files'); ?></option>
                  <option value="folders" <?php echo ($extendedData['capacity_unit'] ?? '') === 'folders' ? 'selected' : ''; ?>><?php echo __('Folders'); ?></option>
                  <option value="items" <?php echo ($extendedData['capacity_unit'] ?? '') === 'items' ? 'selected' : ''; ?>><?php echo __('Items'); ?></option>
                  <option value="volumes" <?php echo ($extendedData['capacity_unit'] ?? '') === 'volumes' ? 'selected' : ''; ?>><?php echo __('Volumes'); ?></option>
                  <option value="metres" <?php echo ($extendedData['capacity_unit'] ?? '') === 'metres' ? 'selected' : ''; ?>><?php echo __('Linear metres'); ?></option>
                </select>
              </div>
            </div>
          </div>
          <?php if (!empty($extendedData['total_capacity'])): ?>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Capacity Usage'); ?></label>
            <?php 
              $used = (int)($extendedData['used_capacity'] ?? 0);
              $total = (int)$extendedData['total_capacity'];
              $percent = $total > 0 ? round(($used / $total) * 100) : 0;
              $barClass = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success');
            ?>
            <div class="progress" data-ahg-style="height: 25px;">
              <div class="progress-bar <?php echo $barClass; ?>" role="progressbar" 
                   data-ahg-style="width: <?php echo $percent; ?>%;">
                <?php echo $used; ?> / <?php echo $total; ?> (<?php echo $percent; ?>%)
              </div>
            </div>
          </div>
          <?php endif; ?>
          <hr>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label" for="po-total_linear_metres"><?php echo __('Total Linear Metres'); ?></label>
                <input id="po-total_linear_metres" type="number" step="0.01" name="total_linear_metres" class="form-control"
                       value="<?php echo esc_entities($extendedData['total_linear_metres'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label" for="po-used_linear_metres"><?php echo __('Used Linear Metres'); ?></label>
                <input id="po-used_linear_metres" type="number" step="0.01" name="used_linear_metres" class="form-control"
                       value="<?php echo esc_entities($extendedData['used_linear_metres'] ?? 0); ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="col-md-4">

      <!-- Status -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i><?php echo __('Status'); ?></h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="po-status"><?php echo __('Status'); ?></label>
            <select id="po-status" name="status" class="form-select">
              <option value="active" <?php echo ($extendedData['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>><?php echo __('Active'); ?></option>
              <option value="full" <?php echo ($extendedData['status'] ?? '') === 'full' ? 'selected' : ''; ?>><?php echo __('Full'); ?></option>
              <option value="maintenance" <?php echo ($extendedData['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>><?php echo __('Under Maintenance'); ?></option>
              <option value="decommissioned" <?php echo ($extendedData['status'] ?? '') === 'decommissioned' ? 'selected' : ''; ?>><?php echo __('Decommissioned'); ?></option>
            </select>
          </div>
        </div>
      </div>

      <!-- Environmental -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-thermometer-half me-2"></i><?php echo __('Environmental'); ?></h5>
        </div>
        <div class="card-body">
          <div class="mb-3 form-check">
            <input type="checkbox" name="climate_controlled" value="1" class="form-check-input" id="climate_controlled"
                   <?php echo !empty($extendedData['climate_controlled']) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="climate_controlled"><?php echo __('Climate Controlled'); ?></label>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label" for="po-temperature_min"><?php echo __('Temp Min (°C)'); ?></label>
                <input id="po-temperature_min" type="number" step="0.1" name="temperature_min" class="form-control"
                       value="<?php echo esc_entities($extendedData['temperature_min'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label" for="po-temperature_max"><?php echo __('Temp Max (°C)'); ?></label>
                <input id="po-temperature_max" type="number" step="0.1" name="temperature_max" class="form-control"
                       value="<?php echo esc_entities($extendedData['temperature_max'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label" for="po-humidity_min"><?php echo __('Humidity Min (%)'); ?></label>
                <input id="po-humidity_min" type="number" step="0.1" name="humidity_min" class="form-control"
                       value="<?php echo esc_entities($extendedData['humidity_min'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label" for="po-humidity_max"><?php echo __('Humidity Max (%)'); ?></label>
                <input id="po-humidity_max" type="number" step="0.1" name="humidity_max" class="form-control"
                       value="<?php echo esc_entities($extendedData['humidity_max'] ?? ''); ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Security -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-lock me-2"></i><?php echo __('Security'); ?></h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="po-security_level"><?php echo __('Security Level'); ?></label>
            <select id="po-security_level" name="security_level" class="form-select">
              <option value=""><?php echo __('Select...'); ?></option>
              <option value="public" <?php echo ($extendedData['security_level'] ?? '') === 'public' ? 'selected' : ''; ?>><?php echo __('Public'); ?></option>
              <option value="restricted" <?php echo ($extendedData['security_level'] ?? '') === 'restricted' ? 'selected' : ''; ?>><?php echo __('Restricted'); ?></option>
              <option value="confidential" <?php echo ($extendedData['security_level'] ?? '') === 'confidential' ? 'selected' : ''; ?>><?php echo __('Confidential'); ?></option>
              <option value="secure" <?php echo ($extendedData['security_level'] ?? '') === 'secure' ? 'selected' : ''; ?>><?php echo __('Secure'); ?></option>
              <option value="vault" <?php echo ($extendedData['security_level'] ?? '') === 'vault' ? 'selected' : ''; ?>><?php echo __('Vault'); ?></option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label" for="po-access_restrictions"><?php echo __('Access Restrictions'); ?></label>
            <textarea id="po-access_restrictions" name="access_restrictions" class="form-control" rows="3"><?php echo esc_entities($extendedData['access_restrictions'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Notes'); ?></h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <textarea name="notes" class="form-control" rows="4"><?php echo esc_entities($extendedData['notes'] ?? ''); ?></textarea>
          </div>
        </div>
      </div>

      <?php /* Strongrooms are rooms in the storage location tree (Building ... Shelf above); the separate strongroom block is gone. */ ?>

    </div>
  </div>

  <!-- Actions -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex gap-2">
        <?php if ($resource->id): ?>
          <a href="<?php echo url_for([$resource, 'module' => 'physicalobject']); ?>" class="btn btn-secondary">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
          </a>
        <?php else: ?>
          <a href="<?php echo url_for(['module' => 'physicalobject', 'action' => 'browse']); ?>" class="btn btn-secondary">
            <i class="fas fa-times me-1"></i><?php echo __('Cancel'); ?>
          </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i><?php echo __('Save'); ?>
        </button>
      </div>
    </div>
  </div>

</form>

<?php end_slot(); ?>
