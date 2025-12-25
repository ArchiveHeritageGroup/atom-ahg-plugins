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
<form method="post" action="<?php echo url_for(['module' => 'arSecurityClearance', 'action' => 'watermarkSettings']); ?>">

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

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><?php echo __('Available Watermarks'); ?></h5>
    </div>
    <div class="card-body">
      <div class="row">
        <?php foreach ($watermarkTypes as $wtype): ?>
          <?php if ($wtype->image_file): ?>
          <div class="col-md-3 mb-3 text-center">
            <div class="border rounded p-2">
              <img src="/images/watermarks/<?php echo $wtype->image_file; ?>" 
                   alt="<?php echo $wtype->name; ?>" 
                   style="max-width: 100%; max-height: 60px; opacity: 0.8;">
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
    <a href="<?php echo url_for(['module' => 'settings', 'action' => 'list']); ?>" class="btn btn-secondary">
      <?php echo __('Cancel'); ?>
    </a>
  </div>

</form>
<?php end_slot(); ?>
