<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?><?php echo __('Marketplace Settings'); ?> - <?php echo __('Marketplace Admin'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage'); ?>"><?php echo __('Home'); ?></a></li>
    <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminDashboard']); ?>"><?php echo __('Marketplace Admin'); ?></a></li>
    <li class="breadcrumb-item active"><?php echo __('Settings'); ?></li>
  </ol>
</nav>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<h1 class="h3 mb-4"><?php echo __('Marketplace Settings'); ?></h1>

<?php if (empty($settings)): ?>
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-cog fa-3x text-muted mb-3 d-block"></i>
      <h5><?php echo __('No settings configured'); ?></h5>
      <p class="text-muted"><?php echo __('Run the marketplace install to populate default settings.'); ?></p>
    </div>
  </div>
<?php else: ?>
  <form method="post" action="<?php echo url_for(['module' => 'marketplace', 'action' => 'adminSettings']); ?>">
    <input type="hidden" name="form_action" value="save">

    <?php
      // Group settings by setting_group
      $grouped = [];
      foreach ($settings as $setting) {
          $group = $setting->setting_group ?? 'general';
          if (!isset($grouped[$group])) {
              $grouped[$group] = [];
          }
          $grouped[$group][] = $setting;
      }
      ksort($grouped);
    ?>

    <?php foreach ($grouped as $groupName => $groupSettings): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><?php echo esc_entities(ucfirst(str_replace('_', ' ', $groupName))); ?></h5>
        </div>
        <div class="card-body">
          <?php foreach ($groupSettings as $setting): ?>
            <?php
              $key = $setting->setting_key;
              $type = $setting->setting_type ?? 'string';
              $value = $setting->setting_value ?? '';
              $desc = $setting->description ?? '';
              $inputName = 'setting_' . $key;
            ?>
            <div class="mb-3 row">
              <label class="col-sm-4 col-form-label" for="<?php echo esc_entities($inputName); ?>">
                <?php echo esc_entities($key); ?>
              </label>
              <div class="col-sm-8">
                <?php if ($type === 'boolean'): ?>
                  <div class="form-check mt-2">
                    <input type="hidden" name="<?php echo esc_entities($inputName); ?>" value="0">
                    <input type="checkbox" class="form-check-input" name="<?php echo esc_entities($inputName); ?>" id="<?php echo esc_entities($inputName); ?>" value="1"<?php echo ($value && $value !== '0' && $value !== 'false') ? ' checked' : ''; ?>>
                    <label class="form-check-label" for="<?php echo esc_entities($inputName); ?>"><?php echo __('Enabled'); ?></label>
                  </div>
                <?php elseif ($type === 'number' || $type === 'integer' || $type === 'float'): ?>
                  <input type="number" class="form-control" name="<?php echo esc_entities($inputName); ?>" id="<?php echo esc_entities($inputName); ?>" value="<?php echo esc_entities($value); ?>"<?php echo $type === 'float' ? ' step="0.01"' : ''; ?>>
                <?php elseif ($type === 'json'): ?>
                  <textarea class="form-control font-monospace" name="<?php echo esc_entities($inputName); ?>" id="<?php echo esc_entities($inputName); ?>" rows="3"><?php echo esc_entities($value); ?></textarea>
                <?php else: ?>
                  <input type="text" class="form-control" name="<?php echo esc_entities($inputName); ?>" id="<?php echo esc_entities($inputName); ?>" value="<?php echo esc_entities($value); ?>">
                <?php endif; ?>
                <?php if ($desc): ?>
                  <div class="form-text"><?php echo esc_entities($desc); ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="text-end mb-4">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-1"></i> <?php echo __('Save All Settings'); ?>
      </button>
    </div>
  </form>
<?php endif; ?>

<?php end_slot(); ?>
