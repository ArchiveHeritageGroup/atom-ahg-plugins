<?php
/**
 * Watermark Settings Partial for Museum Plugin
 * Uses Laravel Illuminate Database
 */
use Illuminate\Database\Capsule\Manager as DB;

// Get current watermark settings for this object
$watermarkSetting = null;
$watermarkTypes = [];
$customWatermarks = [];
$objectId = $resourceId ?? ($resource->id ?? null);

if ($objectId) {
    $watermarkSetting = DB::table('object_watermark_setting')
        ->where('object_id', $objectId)
        ->first();
}

// Get available watermark types
try {
    $watermarkTypes = DB::table('watermark_type')
        ->where('active', 1)
        ->orderBy('name')
        ->get();
} catch (\Exception $e) {
    $watermarkTypes = collect([]);
}

// Get custom watermarks (global + object-specific)
try {
    $customWatermarks = DB::table('custom_watermark')
        ->where('active', 1)
        ->where(function($q) use ($objectId) {
            $q->whereNull('object_id');
            if ($objectId) {
                $q->orWhere('object_id', $objectId);
            }
        })
        ->orderBy('name')
        ->get();
} catch (\Exception $e) {
    $customWatermarks = collect([]);
}

$watermarkEnabled = $watermarkSetting->watermark_enabled ?? 0;
$selectedTypeId = $watermarkSetting->watermark_type_id ?? null;
$selectedCustomId = $watermarkSetting->custom_watermark_id ?? null;
$position = $watermarkSetting->position ?? 'center';
$opacity = $watermarkSetting->opacity ?? 0.4;
?>

<div class="accordion-item">
  <h2 class="accordion-header" id="heading-watermark">
    <button class="accordion-button collapsed" type="button"
            data-bs-toggle="collapse" data-bs-target="#collapse-watermark"
            aria-expanded="false" aria-controls="collapse-watermark">
      <?php echo __('Watermark Settings'); ?>
      <span class="cco-chapter"><?php echo __('Digital Protection'); ?></span>
    </button>
  </h2>
  <div id="collapse-watermark" class="accordion-collapse collapse"
       aria-labelledby="heading-watermark" data-bs-parent="#ccoAccordion">
    <div class="accordion-body">
      
      <!-- Enable Watermark Toggle -->
      <div class="cco-field">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" 
                 id="watermark_enabled" name="watermark_enabled" value="1"
                 <?php echo $watermarkEnabled ? 'checked' : ''; ?>
                 style="width: 3em; height: 1.5em;">
          <label class="form-check-label" for="watermark_enabled" style="margin-left: 10px;">
            <strong><?php echo __('Enable watermark for this object'); ?></strong>
          </label>
        </div>
      </div>

      <div id="watermark-options" style="<?php echo $watermarkEnabled ? '' : 'display:none;'; ?>">
        
        <!-- System Watermark Type -->
        <div class="cco-field">
          <div class="field-header">
            <label for="watermark_type_id"><?php echo __('System Watermark'); ?></label>
          </div>
          <div class="field-input">
            <select name="watermark_type_id" id="watermark_type_id" class="form-select">
              <option value=""><?php echo __('Use default'); ?></option>
              <?php foreach ($watermarkTypes as $type): ?>
                <option value="<?php echo $type->id; ?>" 
                        <?php echo $selectedTypeId == $type->id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($type->name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Custom Watermark Selection -->
        <?php if (count($customWatermarks) > 0): ?>
        <div class="cco-field">
          <div class="field-header">
            <label for="custom_watermark_id"><?php echo __('Or use Custom Watermark'); ?></label>
          </div>
          <div class="field-input">
            <select name="custom_watermark_id" id="custom_watermark_id" class="form-select">
              <option value=""><?php echo __('None'); ?></option>
              <?php foreach ($customWatermarks as $custom): ?>
                <option value="<?php echo $custom->id; ?>"
                        <?php echo $selectedCustomId == $custom->id ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($custom->name); ?>
                  <?php echo $custom->object_id ? '' : ' (Global)'; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php endif; ?>

        <!-- Upload NEW Custom Watermark -->
        <div class="cco-field" style="background: #fff3cd; border-left-color: #ffc107;">
          <div class="field-header">
            <label><?php echo __('Upload NEW Custom Watermark'); ?></label>
          </div>
          <p class="text-muted small"><?php echo __('Leave empty to keep existing selection above'); ?></p>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label for="new_watermark_name" class="form-label"><?php echo __('Watermark Name'); ?></label>
              <input type="text" class="form-control" id="new_watermark_name" 
                     name="new_watermark_name" placeholder="e.g., Company Logo">
            </div>
            <div class="col-md-6">
              <label for="new_watermark_file" class="form-label"><?php echo __('Watermark Image'); ?></label>
              <input type="file" class="form-control" id="new_watermark_file" 
                     name="new_watermark_file" accept="image/png,image/gif">
              <div class="form-text"><?php echo __('PNG or GIF with transparency recommended'); ?></div>
            </div>
          </div>

          <div class="row g-3 mt-2">
            <div class="col-md-6">
              <label for="new_watermark_position" class="form-label"><?php echo __('Position'); ?></label>
              <select name="new_watermark_position" id="new_watermark_position" class="form-select">
                <option value="center" <?php echo $position == 'center' ? 'selected' : ''; ?>><?php echo __('Center'); ?></option>
                <option value="top left" <?php echo $position == 'top left' ? 'selected' : ''; ?>><?php echo __('Top Left'); ?></option>
                <option value="top center" <?php echo $position == 'top center' ? 'selected' : ''; ?>><?php echo __('Top Center'); ?></option>
                <option value="top right" <?php echo $position == 'top right' ? 'selected' : ''; ?>><?php echo __('Top Right'); ?></option>
                <option value="left center" <?php echo $position == 'left center' ? 'selected' : ''; ?>><?php echo __('Left Center'); ?></option>
                <option value="right center" <?php echo $position == 'right center' ? 'selected' : ''; ?>><?php echo __('Right Center'); ?></option>
                <option value="bottom left" <?php echo $position == 'bottom left' ? 'selected' : ''; ?>><?php echo __('Bottom Left'); ?></option>
                <option value="bottom center" <?php echo $position == 'bottom center' ? 'selected' : ''; ?>><?php echo __('Bottom Center'); ?></option>
                <option value="bottom right" <?php echo $position == 'bottom right' ? 'selected' : ''; ?>><?php echo __('Bottom Right'); ?></option>
                <option value="repeat" <?php echo $position == 'repeat' ? 'selected' : ''; ?>><?php echo __('Repeat/Tile'); ?></option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="new_watermark_opacity" class="form-label">
                <?php echo __('Opacity'); ?>: <span id="opacity-value"><?php echo round($opacity * 100); ?>%</span>
              </label>
              <input type="range" class="form-range" id="new_watermark_opacity" 
                     name="new_watermark_opacity" min="10" max="100" step="5"
                     value="<?php echo round($opacity * 100); ?>">
            </div>
          </div>

          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="new_watermark_global" 
                   name="new_watermark_global" value="1">
            <label class="form-check-label" for="new_watermark_global">
              <?php echo __('Make available globally (for all records)'); ?>
            </label>
          </div>
        </div>

        <!-- Regenerate Derivatives -->
        <div class="cco-field">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="regenerate_watermark" 
                   name="regenerate_watermark" value="1">
            <label class="form-check-label" for="regenerate_watermark">
              <strong><?php echo __('Regenerate derivatives with new watermark'); ?></strong>
            </label>
            <div class="form-text"><?php echo __('Check this to apply the new watermark to existing images. This may take a moment.'); ?></div>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle watermark options visibility
  var enableToggle = document.getElementById('watermark_enabled');
  var optionsDiv = document.getElementById('watermark-options');
  
  if (enableToggle && optionsDiv) {
    enableToggle.addEventListener('change', function() {
      optionsDiv.style.display = this.checked ? 'block' : 'none';
    });
  }

  // Update opacity value display
  var opacitySlider = document.getElementById('new_watermark_opacity');
  var opacityValue = document.getElementById('opacity-value');
  
  if (opacitySlider && opacityValue) {
    opacitySlider.addEventListener('input', function() {
      opacityValue.textContent = this.value + '%';
    });
  }

  // Clear system watermark when custom selected and vice versa
  var systemSelect = document.getElementById('watermark_type_id');
  var customSelect = document.getElementById('custom_watermark_id');
  
  if (systemSelect && customSelect) {
    customSelect.addEventListener('change', function() {
      if (this.value) {
        systemSelect.value = '';
      }
    });
    
    systemSelect.addEventListener('change', function() {
      if (this.value) {
        customSelect.value = '';
      }
    });
  }
});
</script>
