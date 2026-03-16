<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $item ? __('Edit Dropdown Value') : __('Add Dropdown Value'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Dropdown Manager'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDropdowns'])],
  ['label' => $item ? __('Edit') : __('Add')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <h1 class="h3 mb-4"><?php echo $item ? __('Edit Dropdown Value') : __('Add Dropdown Value'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $f = $item; ?>

    <form method="post">
      <div class="card mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="dd-group" class="form-label"><?php echo __('Group'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="dd-group" name="dropdown_group" value="<?php echo htmlspecialchars($f->dropdown_group ?? '', ENT_QUOTES, 'UTF-8'); ?>" required list="group-list" placeholder="<?php echo __('e.g., institution_type'); ?>">
              <datalist id="group-list">
                <?php foreach ($existingGroups as $g): ?>
                  <option value="<?php echo htmlspecialchars($g, ENT_QUOTES, 'UTF-8'); ?>">
                <?php endforeach; ?>
              </datalist>
              <small class="form-text text-muted"><?php echo __('Use snake_case. Select existing or type new.'); ?></small>
            </div>
            <div class="col-md-6">
              <label for="dd-value" class="form-label"><?php echo __('Value'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="dd-value" name="value" value="<?php echo htmlspecialchars($f->value ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="<?php echo __('e.g., archive'); ?>">
              <small class="form-text text-muted"><?php echo __('Internal key stored in database.'); ?></small>
            </div>
            <div class="col-md-6">
              <label for="dd-label" class="form-label"><?php echo __('Label'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="dd-label" name="label" value="<?php echo htmlspecialchars($f->label ?? '', ENT_QUOTES, 'UTF-8'); ?>" required placeholder="<?php echo __('e.g., Archive'); ?>">
              <small class="form-text text-muted"><?php echo __('Display text shown to users.'); ?></small>
            </div>
            <div class="col-md-3">
              <label for="dd-badge" class="form-label"><?php echo __('Badge Color'); ?></label>
              <select class="form-select" id="dd-badge" name="badge_color">
                <option value=""><?php echo __('-- None --'); ?></option>
                <?php
                  $colors = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];
                  foreach ($colors as $c): ?>
                  <option value="<?php echo $c; ?>"<?php echo ($f->badge_color ?? '') === $c ? ' selected' : ''; ?>><?php echo ucfirst($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="dd-order" class="form-label"><?php echo __('Sort Order'); ?></label>
              <input type="number" class="form-control" id="dd-order" name="sort_order" value="<?php echo (int) ($f->sort_order ?? 100); ?>" min="0">
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="dd-active" name="is_active" value="1"<?php echo (!$f || !empty($f->is_active)) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="dd-active"><?php echo __('Active'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDropdowns']); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $item ? __('Save Changes') : __('Add Value'); ?></button>
      </div>
    </form>

  </div>
</div>

<?php end_slot(); ?>
