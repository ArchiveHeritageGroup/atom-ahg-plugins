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
                <label class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required
                       value="<?php echo esc_entities($resource->getName(['cultureFallback' => true])); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Type'); ?></label>
                <select name="type" class="form-select">
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
            <label class="form-label"><?php echo __('Location (legacy)'); ?></label>
            <input type="text" name="location" class="form-control"
                   value="<?php echo esc_entities($resource->getLocation(['cultureFallback' => true])); ?>"
                   placeholder="<?php echo __('Use extended location fields below instead'); ?>">
            <small class="text-muted"><?php echo __('For backwards compatibility. Use the detailed fields below.'); ?></small>
          </div>
        </div>
      </div>

      <!-- Extended Location -->
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo __('Location Details'); ?></h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Building'); ?></label>
                <input type="text" name="building" class="form-control"
                       value="<?php echo esc_entities($extendedData['building'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Floor'); ?></label>
                <input type="text" name="floor" class="form-control"
                       value="<?php echo esc_entities($extendedData['floor'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Room'); ?></label>
                <input type="text" name="room" class="form-control"
                       value="<?php echo esc_entities($extendedData['room'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Aisle'); ?></label>
                <input type="text" name="aisle" class="form-control"
                       value="<?php echo esc_entities($extendedData['aisle'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Bay'); ?></label>
                <input type="text" name="bay" class="form-control"
                       value="<?php echo esc_entities($extendedData['bay'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Rack'); ?></label>
                <input type="text" name="rack" class="form-control"
                       value="<?php echo esc_entities($extendedData['rack'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-3">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Shelf'); ?></label>
                <input type="text" name="shelf" class="form-control"
                       value="<?php echo esc_entities($extendedData['shelf'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Position'); ?></label>
                <input type="text" name="position" class="form-control"
                       value="<?php echo esc_entities($extendedData['position'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Barcode'); ?></label>
                <input type="text" name="barcode" class="form-control"
                       value="<?php echo esc_entities($extendedData['barcode'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Reference Code'); ?></label>
                <input type="text" name="reference_code" class="form-control"
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
                <label class="form-label"><?php echo __('Width'); ?></label>
                <input type="number" step="0.01" name="width" class="form-control"
                       value="<?php echo esc_entities($extendedData['width'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Height'); ?></label>
                <input type="number" step="0.01" name="height" class="form-control"
                       value="<?php echo esc_entities($extendedData['height'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Depth'); ?></label>
                <input type="number" step="0.01" name="depth" class="form-control"
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
                <label class="form-label"><?php echo __('Total Capacity'); ?></label>
                <input type="number" name="total_capacity" class="form-control"
                       value="<?php echo esc_entities($extendedData['total_capacity'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Used Capacity'); ?></label>
                <input type="number" name="used_capacity" class="form-control"
                       value="<?php echo esc_entities($extendedData['used_capacity'] ?? 0); ?>">
              </div>
            </div>
            <div class="col-md-4">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Capacity Unit'); ?></label>
                <select name="capacity_unit" class="form-select">
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
            <div class="progress" style="height: 25px;">
              <div class="progress-bar <?php echo $barClass; ?>" role="progressbar" 
                   style="width: <?php echo $percent; ?>%;">
                <?php echo $used; ?> / <?php echo $total; ?> (<?php echo $percent; ?>%)
              </div>
            </div>
          </div>
          <?php endif; ?>
          <hr>
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Total Linear Metres'); ?></label>
                <input type="number" step="0.01" name="total_linear_metres" class="form-control"
                       value="<?php echo esc_entities($extendedData['total_linear_metres'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Used Linear Metres'); ?></label>
                <input type="number" step="0.01" name="used_linear_metres" class="form-control"
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
            <label class="form-label"><?php echo __('Status'); ?></label>
            <select name="status" class="form-select">
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
                <label class="form-label"><?php echo __('Temp Min (°C)'); ?></label>
                <input type="number" step="0.1" name="temperature_min" class="form-control"
                       value="<?php echo esc_entities($extendedData['temperature_min'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Temp Max (°C)'); ?></label>
                <input type="number" step="0.1" name="temperature_max" class="form-control"
                       value="<?php echo esc_entities($extendedData['temperature_max'] ?? ''); ?>">
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Humidity Min (%)'); ?></label>
                <input type="number" step="0.1" name="humidity_min" class="form-control"
                       value="<?php echo esc_entities($extendedData['humidity_min'] ?? ''); ?>">
              </div>
            </div>
            <div class="col-6">
              <div class="mb-3">
                <label class="form-label"><?php echo __('Humidity Max (%)'); ?></label>
                <input type="number" step="0.1" name="humidity_max" class="form-control"
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
            <label class="form-label"><?php echo __('Security Level'); ?></label>
            <select name="security_level" class="form-select">
              <option value=""><?php echo __('Select...'); ?></option>
              <option value="public" <?php echo ($extendedData['security_level'] ?? '') === 'public' ? 'selected' : ''; ?>><?php echo __('Public'); ?></option>
              <option value="restricted" <?php echo ($extendedData['security_level'] ?? '') === 'restricted' ? 'selected' : ''; ?>><?php echo __('Restricted'); ?></option>
              <option value="confidential" <?php echo ($extendedData['security_level'] ?? '') === 'confidential' ? 'selected' : ''; ?>><?php echo __('Confidential'); ?></option>
              <option value="secure" <?php echo ($extendedData['security_level'] ?? '') === 'secure' ? 'selected' : ''; ?>><?php echo __('Secure'); ?></option>
              <option value="vault" <?php echo ($extendedData['security_level'] ?? '') === 'vault' ? 'selected' : ''; ?>><?php echo __('Vault'); ?></option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?php echo __('Access Restrictions'); ?></label>
            <textarea name="access_restrictions" class="form-control" rows="3"><?php echo esc_entities($extendedData['access_restrictions'] ?? ''); ?></textarea>
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

    </div>
  </div>

  <!-- Actions -->
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between">
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
