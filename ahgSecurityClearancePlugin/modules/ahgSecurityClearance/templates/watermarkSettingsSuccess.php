<?php decorate_with('layout_2col'); ?>

<?php slot('sidebar'); ?>
  <div class="card mb-3">
    <div class="card-header">
      <h4 class="mb-0"><?php echo __('Watermark Settings'); ?></h4>
    </div>
    <div class="card-body">
      <p><?php echo __('Configure default watermark behavior for all digital objects.'); ?></p>
    </div>
  </div>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1><?php echo __('Watermark Settings'); ?></h1>
<?php end_slot(); ?>

<?php slot('content'); ?>
<form method="post" action="<?php echo url_for(['module' => 'ahgSecurityClearance', 'action' => 'watermarkSettings']); ?>" enctype="multipart/form-data">

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Default Watermark'); ?></h5>
    </div>
    <div class="card-body">
      
      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="default_watermark_enabled" 
                 name="default_watermark_enabled" value="1" 
                 <?php echo $defaultEnabled === '1' ? 'checked' : ''; ?>>
          <label class="form-check-label" for="default_watermark_enabled">
            <strong><?php echo __('Enable default watermark'); ?></strong>
          </label>
        </div>
        <small class="text-muted"><?php echo __('Apply watermark to all images by default.'); ?></small>
      </div>

      <div class="mb-3">
        <label for="default_watermark_type" class="form-label"><?php echo __('Default Watermark Type'); ?></label>
        <select class="form-select" id="default_watermark_type" name="default_watermark_type">
          <?php foreach ($watermarkTypes as $wtype): ?>
            <option value="<?php echo $wtype->code; ?>" 
                    <?php echo ($defaultType === $wtype->code) ? 'selected' : ''; ?>>
              <?php echo $wtype->name; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted"><?php echo __('Watermark applied when no specific watermark is set.'); ?></small>
      </div>

    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Application Settings'); ?></h5>
    </div>
    <div class="card-body">

      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="apply_watermark_on_view" 
                 name="apply_watermark_on_view" value="1" 
                 <?php echo $applyOnView === '1' ? 'checked' : ''; ?>>
          <label class="form-check-label" for="apply_watermark_on_view">
            <?php echo __('Apply watermark when viewing images'); ?>
          </label>
        </div>
        <small class="text-muted"><?php echo __('Watermark will be overlaid on IIIF image viewer.'); ?></small>
      </div>

      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="apply_watermark_on_download" 
                 name="apply_watermark_on_download" value="1" 
                 <?php echo $applyOnDownload === '1' ? 'checked' : ''; ?>>
          <label class="form-check-label" for="apply_watermark_on_download">
            <?php echo __('Apply watermark on download'); ?>
          </label>
        </div>
        <small class="text-muted"><?php echo __('Downloaded images will have watermark applied. Master files are never modified.'); ?></small>
      </div>

      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="security_watermark_override" 
                 name="security_watermark_override" value="1" 
                 <?php echo $securityOverride === '1' ? 'checked' : ''; ?>>
          <label class="form-check-label" for="security_watermark_override">
            <?php echo __('Security classification overrides default'); ?>
          </label>
        </div>
        <small class="text-muted"><?php echo __('Security classification watermarks take priority over default/custom watermarks.'); ?></small>
      </div>

      <div class="mb-3">
        <label for="watermark_min_size" class="form-label"><?php echo __('Minimum Image Size'); ?></label>
        <div class="input-group" style="max-width: 200px;">
          <input type="number" class="form-control" id="watermark_min_size" 
                 name="watermark_min_size" value="<?php echo $minSize; ?>" min="0">
          <span class="input-group-text">px</span>
        </div>
        <small class="text-muted"><?php echo __('Images smaller than this dimension will not receive watermarks.'); ?></small>
      </div>

    </div>
  </div>

  <!-- Custom Watermarks Section -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Custom Watermarks'); ?></h5>
    </div>
    <div class="card-body">
      
      <!-- Upload New -->
      <h6><?php echo __('Upload New Watermark'); ?></h6>
      <div class="row mb-4">
        <div class="col-md-3">
          <label for="custom_watermark_name" class="form-label"><?php echo __('Name'); ?></label>
          <input type="text" class="form-control" id="custom_watermark_name" name="custom_watermark_name" placeholder="My Logo">
        </div>
        <div class="col-md-4">
          <label for="custom_watermark_position" class="form-label"><?php echo __('Position'); ?></label>
          <select class="form-select" id="custom_watermark_position" name="custom_watermark_position">
            <option value="center">Center</option>
            <option value="top-left">Top Left</option>
            <option value="top-right">Top Right</option>
            <option value="bottom-left">Bottom Left</option>
            <option value="bottom-right" selected>Bottom Right</option>
            <option value="repeat">Repeat/Tile</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="custom_watermark_opacity" class="form-label"><?php echo __('Opacity'); ?></label>
          <input type="number" class="form-control" id="custom_watermark_opacity" name="custom_watermark_opacity" value="0.40" min="0.1" max="1.0" step="0.1">
        </div>
      </div>
      <div class="row mb-4">
        <div class="col-md-6">
          <label for="custom_watermark_file" class="form-label"><?php echo __('Watermark Image File'); ?></label>
          <input type="file" class="form-control" id="custom_watermark_file" name="custom_watermark_file" accept="image/png,image/jpeg,image/gif">
          <small class="text-muted"><?php echo __('Supported: PNG, JPEG, GIF. Recommended: transparent PNG.'); ?></small>
        </div>
        <div class="col-md-2 d-flex align-items-center" style="padding-top: 25px;">
          <button type="submit" name="upload_watermark" value="1" class="btn btn-success">
            <i class="fas fa-upload me-1"></i> <?php echo __('Upload'); ?>
          </button>
        </div>
      </div>
      
      <!-- Existing Custom Watermarks -->
      <?php if (!empty($customWatermarks) && count($customWatermarks) > 0): ?>
      <h6 class="mt-4"><?php echo __('Existing Custom Watermarks'); ?></h6>
      <div class="row">
        <?php foreach ($customWatermarks as $cw): ?>
        <div class="col-md-3 mb-3">
          <div class="card h-100">
            <div class="card-body text-center p-2">
              <img src="/uploads/watermarks/<?php echo $cw->filename; ?>" alt="<?php echo $cw->name; ?>" style="max-width: 80px; max-height: 60px; object-fit: contain;">
              <p class="mb-1 mt-2"><small><strong><?php echo $cw->name; ?></strong></small></p>
              <p class="mb-1"><small class="text-muted"><?php echo $cw->position; ?> / <?php echo $cw->opacity; ?></small></p>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="default_custom_watermark_id" id="custom_<?php echo $cw->id; ?>" value="<?php echo $cw->id; ?>" <?php echo ($defaultCustomWatermarkId == $cw->id) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="custom_<?php echo $cw->id; ?>"><small><?php echo __('Use as Default'); ?></small></label>
              </div>
            </div>
            <div class="card-footer p-1 text-center">
              <button type="submit" name="delete_custom_watermark" value="<?php echo $cw->id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this watermark?');">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="col-md-3 mb-3">
          <div class="card h-100 border-dashed">
            <div class="card-body text-center d-flex align-items-center justify-content-center">
              <div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="default_custom_watermark_id" id="custom_none" value="" <?php echo empty($defaultCustomWatermarkId) ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="custom_none"><small><?php echo __('No Custom (Use System)'); ?></small></label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php else: ?>
      <p class="text-muted"><em><?php echo __('No custom watermarks uploaded yet.'); ?></em></p>
      <?php endif; ?>
      
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Available Watermarks'); ?></h5>
    </div>
    <div class="card-body">
      <div class="row">
        <?php foreach ($watermarkTypes as $wtype): ?>
          <?php if ($wtype->image_file): ?>
          <div class="col-md-3 mb-3 text-center">
            <div class="border rounded p-2" style="height: 150px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
              <img src="/images/watermarks/<?php echo $wtype->image_file; ?>" 
                   alt="<?php echo $wtype->name; ?>" 
                   style="max-width: 100px; max-height: 80px; object-fit: contain;">
              <p class="mb-0 mt-2"><small><strong><?php echo $wtype->name; ?></strong></small></p>
              <p class="mb-0"><small class="text-muted"><?php echo $wtype->code; ?></small></p>
            </div>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="actions">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i> <?php echo __('Save Settings'); ?>
    </button>
    <a href="<?php echo url_for(['module' => 'ahgSettings', 'action' => 'index']); ?>" class="btn btn-secondary">
      <?php echo __('Cancel'); ?>
    </a>
  </div>

</form>
<?php end_slot(); ?>
