<?php
/**
 * Security Classification Fieldset with Watermark Selection and Custom Upload
 */

require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/SecurityClearanceService.php';
require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/WatermarkSettingsService.php';
require_once sfConfig::get('sf_root_dir').'/atom-framework/src/Services/DerivativeWatermarkService.php';

use AtomExtensions\Services\SecurityClearanceService;
use AtomExtensions\Services\WatermarkSettingsService;
use AtomExtensions\Services\DerivativeWatermarkService;

// Get all classification levels
$classifications = SecurityClearanceService::getAllClassifications();

// Get current classification if editing
$currentClassification = null;
if (isset($resource) && $resource->id) {
    $currentClassification = SecurityClearanceService::getObjectClassification($resource->id);
}

// Get watermark types and custom watermarks
$watermarkTypes = WatermarkSettingsService::getWatermarkTypes();
$customWatermarks = DerivativeWatermarkService::getCustomWatermarks($resource->id ?? null);

$currentWatermarkId = null;
$currentCustomWatermarkId = null;
$watermarkEnabled = true;

// Check watermark settings from separate table (object_watermark_setting)
$currentPosition = 'center';
$currentOpacity = 0.40;

if (isset($resource) && $resource->id) {
    $watermarkSetting = \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')
        ->where('object_id', $resource->id)
        ->first();
    
    if ($watermarkSetting) {
        $currentWatermarkId = $watermarkSetting->watermark_type_id;
        $currentCustomWatermarkId = $watermarkSetting->custom_watermark_id;
        $watermarkEnabled = (bool) $watermarkSetting->watermark_enabled;
        $currentPosition = $watermarkSetting->position ?? 'center';
        $currentOpacity = $watermarkSetting->opacity ?? 0.40;
    }
}
?>

<!-- Security Classification -->
<div class="accordion-item">
  <h2 class="accordion-header" id="security-heading">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
            data-bs-target="#security-collapse" aria-expanded="false" aria-controls="security-collapse">
      <?php echo __('Security Classification'); ?>
    </button>
  </h2>
  <div id="security-collapse" class="accordion-collapse collapse" aria-labelledby="security-heading">
    <div class="accordion-body">
      
      <div class="mb-3">
        <label for="security_classification_id" class="form-label"><?php echo __('Security Classification'); ?></label>
        <select class="form-select" id="security_classification_id" name="security_classification_id">
          <option value=""><?php echo __('Public (No Classification)'); ?></option>
          <?php foreach ($classifications as $classification): ?>
            <option value="<?php echo $classification->id; ?>"
                    data-level="<?php echo $classification->level; ?>"
                    <?php echo ($currentClassification && $currentClassification->classification_id == $classification->id) ? 'selected' : ''; ?>>
              <?php echo $classification->name; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="text-muted"><?php echo __('Security classification watermarks override all other watermarks.'); ?></small>
      </div>

      <div id="classification-details" style="<?php echo $currentClassification ? '' : 'display: none;'; ?>">
        <div class="mb-3">
          <label for="security_reason" class="form-label"><?php echo __('Classification Reason'); ?></label>
          <textarea class="form-control" id="security_reason" name="security_reason" rows="2"><?php echo $currentClassification ? htmlspecialchars($currentClassification->reason ?? '') : ''; ?></textarea>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="security_review_date" class="form-label"><?php echo __('Review Date'); ?></label>
            <input type="date" class="form-control" id="security_review_date" name="security_review_date"
                   value="<?php echo $currentClassification ? ($currentClassification->review_date ?? '') : ''; ?>">
          </div>
          <div class="col-md-6 mb-3">
            <label for="security_declassify_date" class="form-label"><?php echo __('Declassify Date'); ?></label>
            <input type="date" class="form-control" id="security_declassify_date" name="security_declassify_date"
                   value="<?php echo $currentClassification ? ($currentClassification->declassify_date ?? '') : ''; ?>">
          </div>
        </div>

        <div class="mb-3">
          <label for="security_handling_instructions" class="form-label"><?php echo __('Handling Instructions'); ?></label>
          <textarea class="form-control" id="security_handling_instructions" name="security_handling_instructions" rows="2"><?php echo $currentClassification ? htmlspecialchars($currentClassification->handling_instructions ?? '') : ''; ?></textarea>
        </div>

        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="security_inherit_to_children" name="security_inherit_to_children" value="1"
                 <?php echo (!$currentClassification || $currentClassification->inherit_to_children) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="security_inherit_to_children">
            <?php echo __('Apply to child records'); ?>
          </label>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Watermark Settings -->
<div class="accordion-item">
  <h2 class="accordion-header" id="watermark-heading">
    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
            data-bs-target="#watermark-collapse" aria-expanded="false" aria-controls="watermark-collapse">
      <?php echo __('Watermark Settings'); ?>
    </button>
  </h2>
  <div id="watermark-collapse" class="accordion-collapse collapse" aria-labelledby="watermark-heading">
    <div class="accordion-body">
      
      <div class="mb-3">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="watermark_enabled" name="watermark_enabled" 
                 value="1" <?php echo $watermarkEnabled ? 'checked' : ''; ?>>
          <label class="form-check-label" for="watermark_enabled">
            <?php echo __('Enable watermark for this object'); ?>
          </label>
        </div>
      </div>

      <div id="watermark-options" style="<?php echo $watermarkEnabled ? '' : 'display: none;'; ?>">
      
        <!-- System Watermarks -->
        <div class="mb-3">
          <label for="watermark_type_id" class="form-label"><?php echo __('System Watermark'); ?></label>
          <select class="form-select" id="watermark_type_id" name="watermark_type_id">
            <option value=""><?php echo __('Use default'); ?></option>
            <?php foreach ($watermarkTypes as $wtype): ?>
              <option value="<?php echo $wtype->id; ?>" 
                      <?php echo ($currentWatermarkId == $wtype->id && !$currentCustomWatermarkId) ? 'selected' : ''; ?>
                      data-image="<?php echo $wtype->image_file; ?>">
                <?php echo $wtype->name; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Custom Watermarks -->
        <?php if (!empty($customWatermarks)): ?>
        <div class="mb-3">
          <label for="custom_watermark_id" class="form-label"><?php echo __('Or Custom Watermark'); ?></label>
          <select class="form-select" id="custom_watermark_id" name="custom_watermark_id">
            <option value=""><?php echo __('None (use system watermark)'); ?></option>
            <?php foreach ($customWatermarks as $cw): ?>
              <option value="<?php echo $cw->id; ?>" 
                      <?php echo ($currentCustomWatermarkId == $cw->id) ? 'selected' : ''; ?>
                      data-path="<?php echo $cw->file_path; ?>">
                <?php echo htmlspecialchars($cw->name); ?>
                <?php if ($cw->object_id): ?> (<?php echo __('This record'); ?>)<?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <!-- Upload New Custom Watermark -->
        <div class="card bg-light mb-3">
          <div class="card-body">
            <h6 class="card-title"><?php echo __('Upload NEW Custom Watermark'); ?></h6><small class="text-muted d-block mb-2"><?php echo __('Leave empty to keep existing selection above'); ?></small>
            
            <div class="mb-2">
              <label for="new_watermark_name" class="form-label"><?php echo __('Watermark Name'); ?></label>
              <input type="text" class="form-control form-control-sm" id="new_watermark_name" name="new_watermark_name" 
                     placeholder="e.g., Company Logo">
            </div>
            
            <div class="mb-2">
              <label for="new_watermark_file" class="form-label"><?php echo __('Watermark Image'); ?></label>
              <input type="file" class="form-control form-control-sm" id="new_watermark_file" name="new_watermark_file" 
                     accept="image/png,image/gif">
              <small class="text-muted"><?php echo __('PNG or GIF with transparency recommended'); ?></small>
            </div>
            
            <div class="row">
              <div class="col-md-6 mb-2">
                <label for="new_watermark_position" class="form-label"><?php echo __('Position'); ?></label>
                <select class="form-select form-select-sm" id="new_watermark_position" name="new_watermark_position">
                  <option value="center" <?php echo ($currentPosition == 'center') ? 'selected' : ''; ?>><?php echo __('Center'); ?></option>
                  <option value="repeat" <?php echo ($currentPosition == 'repeat') ? 'selected' : ''; ?>><?php echo __('Repeat (tile)'); ?></option>
                  <option value="bottom right" <?php echo ($currentPosition == 'bottom right') ? 'selected' : ''; ?>><?php echo __('Bottom Right'); ?></option>
                  <option value="bottom left" <?php echo ($currentPosition == 'bottom left') ? 'selected' : ''; ?>><?php echo __('Bottom Left'); ?></option>
                  <option value="top right" <?php echo ($currentPosition == 'top right') ? 'selected' : ''; ?>><?php echo __('Top Right'); ?></option>
                  <option value="top left" <?php echo ($currentPosition == 'top left') ? 'selected' : ''; ?>><?php echo __('Top Left'); ?></option>
                </select>
              </div>
              <div class="col-md-6 mb-2">
                <label for="new_watermark_opacity" class="form-label"><?php echo __('Opacity'); ?></label>
                <input type="range" class="form-range" id="new_watermark_opacity" name="new_watermark_opacity" 
                       min="10" max="80" value="<?php echo (int)($currentOpacity * 100); ?>">
                <small class="text-muted"><span id="opacity-value"><?php echo (int)($currentOpacity * 100); ?></span>%</small>
              </div>
            </div>
            
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="new_watermark_global" name="new_watermark_global" value="1">
              <label class="form-check-label" for="new_watermark_global">
                <?php echo __('Make available globally (for all records)'); ?>
              </label>
            </div>
          </div>
        </div>

        <!-- Regenerate Button -->
        <?php if (isset($resource) && $resource->id): ?>
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="regenerate_derivatives" name="regenerate_derivatives" value="1">
            <label class="form-check-label" for="regenerate_derivatives">
              <strong><?php echo __('Regenerate derivatives with new watermark'); ?></strong>
            </label>
          </div>
          <small class="text-muted"><?php echo __('Check this to apply the new watermark to existing images. This may take a moment.'); ?></small>
        </div>
        <?php endif; ?>

        <div class="alert alert-info py-2 mb-0">
          <small><i class="fas fa-info-circle me-1"></i> 
          <?php echo __('Security classification watermarks have the highest priority and will override custom watermarks.'); ?>
          </small>
        </div>

      </div>
    </div>
  </div>
</div>

<script <?php echo __(sfConfig::get('csp_nonce', '')); ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Security classification toggle
    const classSelect = document.getElementById('security_classification_id');
    const classDetails = document.getElementById('classification-details');
    
    if (classSelect && classDetails) {
        classSelect.addEventListener('change', function() {
            classDetails.style.display = this.value ? 'block' : 'none';
        });
    }
    
    // Watermark enabled toggle
    const wmEnabled = document.getElementById('watermark_enabled');
    const wmOptions = document.getElementById('watermark-options');
    
    if (wmEnabled && wmOptions) {
        wmEnabled.addEventListener('change', function() {
            wmOptions.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Custom watermark clears system watermark selection
    const customSelect = document.getElementById('custom_watermark_id');
    const systemSelect = document.getElementById('watermark_type_id');
    
    if (customSelect && systemSelect) {
        customSelect.addEventListener('change', function() {
            if (this.value) {
                systemSelect.value = '';
            }
        });
        systemSelect.addEventListener('change', function() {
            if (this.value && customSelect) {
                customSelect.value = '';
            }
        });
    }
    
    // Opacity slider display
    const opacitySlider = document.getElementById('new_watermark_opacity');
    const opacityValue = document.getElementById('opacity-value');
    
    if (opacitySlider && opacityValue) {
        opacitySlider.addEventListener('input', function() {
            opacityValue.textContent = this.value;
        });
    }
});
</script>
