<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Registry Settings'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Settings')],
]]); ?>

<h1 class="h3 mb-4"><?php echo __('Registry Settings'); ?></h1>

<?php if (!empty($saved)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
  <i class="fas fa-check-circle me-2"></i>
  <?php echo __('Settings saved successfully.'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('Close'); ?>"></button>
</div>
<?php endif; ?>

<form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminSettings']); ?>">

  <?php if (!empty($settings) && count($settings) > 0): ?>
  <div class="card">
    <div class="card-body">
      <?php foreach ($settings as $idx => $setting): ?>
        <?php if ($idx > 0): ?>
          <hr class="my-4">
        <?php endif; ?>

        <div class="mb-3">
          <label for="setting_<?php echo htmlspecialchars($setting->setting_key, ENT_QUOTES, 'UTF-8'); ?>" class="form-label fw-semibold">
            <?php echo htmlspecialchars($setting->setting_key, ENT_QUOTES, 'UTF-8'); ?>
          </label>

          <?php if (!empty($setting->description)): ?>
            <div class="form-text mb-2"><?php echo htmlspecialchars($setting->description, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>

          <?php $fieldName = 'setting_' . $setting->setting_key; ?>
          <?php $fieldId = 'setting_' . htmlspecialchars($setting->setting_key, ENT_QUOTES, 'UTF-8'); ?>
          <?php $settingType = $setting->setting_type ?? 'text'; ?>

          <?php if ('boolean' === $settingType): ?>
            <div class="form-check form-switch">
              <input type="hidden" name="<?php echo $fieldName; ?>" value="0">
              <input class="form-check-input" type="checkbox" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="1"<?php echo !empty($setting->setting_value) && '0' !== $setting->setting_value ? ' checked' : ''; ?>>
              <label class="form-check-label" for="<?php echo $fieldId; ?>"><?php echo __('Enabled'); ?></label>
            </div>

          <?php elseif ('number' === $settingType): ?>
            <input type="number" class="form-control" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <?php elseif ('json' === $settingType): ?>
            <textarea class="form-control font-monospace" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" rows="4"><?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="form-text"><?php echo __('Enter valid JSON.'); ?></div>

          <?php else: ?>
            <input type="text" class="form-control" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($setting->setting_value ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="card-footer text-end">
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDashboard']); ?>" class="btn btn-secondary me-2">
        <?php echo __('Cancel'); ?>
      </a>
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> <?php echo __('Save Settings'); ?>
      </button>
    </div>
  </div>

  <?php else: ?>
  <div class="text-center py-5">
    <i class="fas fa-cog fa-3x text-muted mb-3"></i>
    <h5><?php echo __('No settings configured'); ?></h5>
    <p class="text-muted"><?php echo __('Registry settings will appear here once configured.'); ?></p>
  </div>
  <?php endif; ?>

</form>

<?php end_slot(); ?>
