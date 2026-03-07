<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $isNew = empty($extension); ?>
<?php slot('title'); ?><?php echo $isNew ? __('Add Extension') : __('Edit Extension'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Standards'), 'url' => url_for(['module' => 'registry', 'action' => 'adminStandards'])],
  ['label' => htmlspecialchars($parentStandard->name ?? __('Standard'), ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => (int) ($parentStandard->id ?? 0)])],
  ['label' => __('Extensions')],
  ['label' => $isNew ? __('Add') : __('Edit')],
]]); ?>

<h1 class="h3 mb-4">
  <?php echo $isNew ? __('Add Extension') : __('Edit Extension'); ?>
  <small class="text-muted fs-6 ms-2"><?php echo htmlspecialchars($parentStandard->name ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
</h1>

<form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminExtensionEdit']); ?>">
  <input type="hidden" name="form_action" value="save">
  <input type="hidden" name="standard_id" value="<?php echo (int) ($parentStandard->id ?? 0); ?>">
  <?php if (!$isNew): ?>
    <input type="hidden" name="id" value="<?php echo (int) $extension->id; ?>">
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header">
      <h5 class="card-title mb-0"><?php echo __('Extension Details'); ?></h5>
    </div>
    <div class="card-body">

      <div class="mb-3">
        <label for="extension_type" class="form-label"><?php echo __('Extension Type'); ?></label>
        <?php $typeOptions = ['addition', 'deviation', 'implementation_note', 'api_binding']; ?>
        <select class="form-select" id="extension_type" name="extension_type">
          <option value=""><?php echo __('-- Select type --'); ?></option>
          <?php foreach ($typeOptions as $opt): ?>
            <option value="<?php echo htmlspecialchars($opt, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (($extension->extension_type ?? '') === $opt) ? ' selected' : ''; ?>>
              <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $opt)), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label for="title" class="form-label"><?php echo __('Title'); ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($extension->title ?? '', ENT_QUOTES, 'UTF-8'); ?>">
      </div>

      <div class="mb-3">
        <label for="description" class="form-label"><?php echo __('Description'); ?> <span class="text-danger">*</span></label>
        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($extension->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="mb-3">
        <label for="rationale" class="form-label"><?php echo __('Rationale'); ?></label>
        <textarea class="form-control" id="rationale" name="rationale" rows="3"><?php echo htmlspecialchars($extension->rationale ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <label for="plugin_name" class="form-label"><?php echo __('Plugin Name'); ?></label>
          <input type="text" class="form-control" id="plugin_name" name="plugin_name" value="<?php echo htmlspecialchars($extension->plugin_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. ahgExamplePlugin">
        </div>
        <div class="col-md-4">
          <label for="api_endpoint" class="form-label"><?php echo __('API Endpoint'); ?></label>
          <input type="text" class="form-control" id="api_endpoint" name="api_endpoint" value="<?php echo htmlspecialchars($extension->api_endpoint ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. /api/v1/example">
        </div>
        <div class="col-md-4">
          <label for="db_tables" class="form-label"><?php echo __('DB Tables'); ?></label>
          <input type="text" class="form-control" id="db_tables" name="db_tables" value="<?php echo htmlspecialchars($extension->db_tables ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. table1, table2">
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"<?php echo ($isNew || !isset($extension->is_active) || $extension->is_active) ? ' checked="checked"' : ''; ?>>
            <label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label>
          </div>
        </div>
        <div class="col-md-4">
          <label for="sort_order" class="form-label"><?php echo __('Sort Order'); ?></label>
          <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="<?php echo (int) ($extension->sort_order ?? 0); ?>">
        </div>
      </div>

    </div>
  </div>

  <div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-save me-1"></i> <?php echo __('Save'); ?>
    </button>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => (int) ($parentStandard->id ?? 0)]); ?>#extensions" class="btn btn-outline-secondary">
      <?php echo __('Cancel'); ?>
    </a>
  </div>
</form>

<?php end_slot(); ?>
