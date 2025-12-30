<?php
/**
 * Watermark selection component.
 * Uses object_watermark_setting table for all watermark data.
 */

$watermarkTypes = \Illuminate\Database\Capsule\Manager::table('watermark_type')
    ->where('active', 1)
    ->orderBy('sort_order')
    ->get();

$currentWatermarkId = null;
$watermarkEnabled = true;
$customWatermarkId = null;
$position = 'center';
$opacity = 0.40;

// Get from object_watermark_setting
if (isset($resource) && $resource->id) {
    $setting = \Illuminate\Database\Capsule\Manager::table('object_watermark_setting')
        ->where('object_id', $resource->id)
        ->first();

    if ($setting) {
        $currentWatermarkId = $setting->watermark_type_id;
        $watermarkEnabled = (bool) $setting->watermark_enabled;
        $customWatermarkId = $setting->custom_watermark_id;
        $position = $setting->position ?? 'center';
        $opacity = $setting->opacity ?? 0.40;
    }
}
?>

<div class="watermark-settings mb-3">
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="watermark_enabled" name="watermark_enabled"
               value="1" <?php echo $watermarkEnabled ? 'checked' : ''; ?>>
        <label class="form-check-label" for="watermark_enabled">
            <?php echo __('Enable Watermark'); ?>
        </label>
    </div>

    <div class="mb-3">
        <label for="watermark_type_id" class="form-label"><?php echo __('Watermark Type'); ?></label>
        <select class="form-select" id="watermark_type_id" name="watermark_type_id">
            <option value=""><?php echo __('Use default'); ?></option>
            <?php foreach ($watermarkTypes as $type): ?>
                <option value="<?php echo $type->id; ?>"
                    <?php echo $currentWatermarkId == $type->id ? 'selected' : ''; ?>>
                    <?php echo esc_entities($type->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="watermark_position" class="form-label"><?php echo __('Position'); ?></label>
        <select class="form-select" id="watermark_position" name="watermark_position">
            <?php
            $positions = [
                'top-left' => __('Top Left'),
                'top-center' => __('Top Center'),
                'top-right' => __('Top Right'),
                'center-left' => __('Center Left'),
                'center' => __('Center'),
                'center-right' => __('Center Right'),
                'bottom-left' => __('Bottom Left'),
                'bottom-center' => __('Bottom Center'),
                'bottom-right' => __('Bottom Right'),
                'repeat' => __('Repeat/Tile'),
            ];
            foreach ($positions as $value => $label): ?>
                <option value="<?php echo $value; ?>" <?php echo $position === $value ? 'selected' : ''; ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="watermark_opacity" class="form-label"><?php echo __('Opacity'); ?></label>
        <input type="range" class="form-range" id="watermark_opacity" name="watermark_opacity"
               min="10" max="100" step="5" value="<?php echo (int) ($opacity * 100); ?>">
        <small class="text-muted"><span id="opacity_value"><?php echo (int) ($opacity * 100); ?></span>%</small>
    </div>
</div>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    const enabledCheckbox = document.getElementById('watermark_enabled');
    const typeSelect = document.getElementById('watermark_type_id');
    const positionSelect = document.getElementById('watermark_position');
    const opacityRange = document.getElementById('watermark_opacity');
    const opacityValue = document.getElementById('opacity_value');

    function toggleFields() {
        const enabled = enabledCheckbox.checked;
        typeSelect.disabled = !enabled;
        positionSelect.disabled = !enabled;
        opacityRange.disabled = !enabled;
    }

    enabledCheckbox.addEventListener('change', toggleFields);
    toggleFields();

    opacityRange.addEventListener('input', function() {
        opacityValue.textContent = this.value;
    });
});
</script>
