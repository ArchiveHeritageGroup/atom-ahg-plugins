<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo $component ? __('Edit Component') : __('Add Component'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => htmlspecialchars($software->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug])],
  ['label' => __('Components'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareComponents', 'id' => (int) $software->id])],
  ['label' => $component ? __('Edit') : __('Add')],
]]); ?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <h1 class="h3 mb-4"><?php echo $component ? __('Edit Component') : __('Add Component'); ?></h1>

    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php $f = $component; ?>

    <form method="post">
      <div class="card mb-4">
        <div class="card-header fw-semibold"><i class="fas fa-puzzle-piece me-2 text-primary"></i><?php echo __('Component Details'); ?></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="comp-name" class="form-label"><?php echo __('Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="comp-name" name="name" value="<?php echo htmlspecialchars($f->name ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <div class="col-md-6">
              <label for="comp-slug" class="form-label"><?php echo __('Slug / Identifier'); ?></label>
              <input type="text" class="form-control" id="comp-slug" name="slug" value="<?php echo htmlspecialchars($f->slug ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('Auto-generated if blank'); ?>">
            </div>
            <div class="col-md-4">
              <label for="comp-type" class="form-label"><?php echo __('Component Type'); ?></label>
              <select class="form-select" id="comp-type" name="component_type">
                <?php
                  $types = ['plugin' => __('Plugin'), 'module' => __('Module'), 'extension' => __('Extension'), 'theme' => __('Theme'), 'integration' => __('Integration'), 'library' => __('Library'), 'other' => __('Other')];
                  $selType = $f->component_type ?? 'plugin';
                  foreach ($types as $val => $label): ?>
                    <option value="<?php echo $val; ?>"<?php echo $selType === $val ? ' selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label for="comp-category" class="form-label"><?php echo __('Category'); ?></label>
              <input type="text" class="form-control" id="comp-category" name="category" value="<?php echo htmlspecialchars($f->category ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo __('e.g., Core, AI & Automation'); ?>">
            </div>
            <div class="col-md-4">
              <label for="comp-version" class="form-label"><?php echo __('Version'); ?></label>
              <input type="text" class="form-control" id="comp-version" name="version" value="<?php echo htmlspecialchars($f->version ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-12">
              <label for="comp-short-desc" class="form-label"><?php echo __('Short Description'); ?></label>
              <input type="text" class="form-control" id="comp-short-desc" name="short_description" value="<?php echo htmlspecialchars($f->short_description ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
            </div>
            <div class="col-12">
              <label for="comp-desc" class="form-label"><?php echo __('Full Description'); ?></label>
              <textarea class="form-control" id="comp-desc" name="description" rows="3"><?php echo htmlspecialchars($f->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            <div class="col-md-6">
              <label for="comp-git" class="form-label"><?php echo __('Git URL'); ?></label>
              <input type="url" class="form-control" id="comp-git" name="git_url" value="<?php echo htmlspecialchars($f->git_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-6">
              <label for="comp-docs" class="form-label"><?php echo __('Documentation URL'); ?></label>
              <input type="url" class="form-control" id="comp-docs" name="documentation_url" value="<?php echo htmlspecialchars($f->documentation_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-4">
              <label for="comp-icon" class="form-label"><?php echo __('Icon CSS Class'); ?></label>
              <input type="text" class="form-control" id="comp-icon" name="icon_class" value="<?php echo htmlspecialchars($f->icon_class ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="fas fa-plug">
            </div>
            <div class="col-md-4">
              <label for="comp-order" class="form-label"><?php echo __('Sort Order'); ?></label>
              <input type="number" class="form-control" id="comp-order" name="sort_order" value="<?php echo htmlspecialchars($f->sort_order ?? '100', ENT_QUOTES, 'UTF-8'); ?>" min="0">
            </div>
            <div class="col-md-4">
              <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" id="comp-required" name="is_required" value="1"<?php echo !empty($f->is_required) ? ' checked' : ''; ?>>
                <label class="form-check-label" for="comp-required"><?php echo __('Required / Core component'); ?></label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareComponents', 'id' => (int) $software->id]); ?>" class="btn btn-outline-secondary"><?php echo __('Cancel'); ?></a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> <?php echo $component ? __('Save Changes') : __('Add Component'); ?></button>
      </div>
    </form>

  </div>
</div>

<?php end_slot(); ?>
