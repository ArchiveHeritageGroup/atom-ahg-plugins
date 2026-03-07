<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php
  $isNew = empty($erd);
  $e = $isNew ? (object) [
    'id' => 0, 'plugin_name' => '', 'display_name' => '', 'category' => 'general',
    'description' => '', 'tables_json' => '[]', 'diagram' => '', 'notes' => '',
    'icon' => 'fas fa-database', 'color' => 'primary', 'sort_order' => 100, 'is_active' => 1,
  ] : $erd;
?>

<?php slot('title'); ?><?php echo $isNew ? __('Add ERD Entry') : __('Edit ERD Entry'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('ERD Documentation'), 'url' => url_for(['module' => 'registry', 'action' => 'adminErd'])],
  ['label' => $isNew ? __('Add') : __('Edit')],
]]); ?>

<h1 class="h3 mb-4"><?php echo $isNew ? __('Add ERD Entry') : __('Edit ERD Entry'); ?></h1>

<form method="post" action="<?php echo $isNew
  ? url_for(['module' => 'registry', 'action' => 'adminErdEdit'])
  : url_for(['module' => 'registry', 'action' => 'adminErdEdit', 'id' => $e->id]); ?>">

  <div class="row g-4">

    <!-- Left column -->
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Basic Information'); ?></h5></div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Plugin Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="plugin_name" value="<?php echo htmlspecialchars($e->plugin_name, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="ahgExamplePlugin">
            </div>
            <div class="col-md-6">
              <label class="form-label"><?php echo __('Display Name'); ?> <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="display_name" value="<?php echo htmlspecialchars($e->display_name, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="Example Feature">
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Category'); ?></label>
              <select class="form-select" name="form_category">
                <?php foreach (['core','sector','compliance','collection','rights','research','ai','ingest','integration','exhibition','reporting'] as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($e->category === $c) ? 'selected' : ''; ?>><?php echo ucfirst($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Icon'); ?></label>
              <input type="text" class="form-control" name="icon" value="<?php echo htmlspecialchars($e->icon ?? 'fas fa-database', ENT_QUOTES, 'UTF-8'); ?>" placeholder="fas fa-database">
              <div class="form-text">Font Awesome class</div>
            </div>
            <div class="col-md-4">
              <label class="form-label"><?php echo __('Color'); ?></label>
              <select class="form-select" name="color">
                <?php foreach (['primary','secondary','success','danger','warning','info','dark','purple'] as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo (($e->color ?? 'primary') === $c) ? 'selected' : ''; ?>><?php echo ucfirst($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label"><?php echo __('Description'); ?></label>
              <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($e->description ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Tables (JSON Array)'); ?></h5></div>
        <div class="card-body">
          <textarea class="form-control font-monospace" name="tables_json" rows="4" placeholder='["table_one","table_two"]'><?php echo htmlspecialchars($e->tables_json ?? '[]', ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="form-text"><?php echo __('JSON array of database table names. Schemas are rendered live from information_schema.'); ?></div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('ASCII ERD Diagram'); ?></h5></div>
        <div class="card-body">
          <textarea class="form-control font-monospace" name="diagram" rows="15" style="font-size: 0.8em;" placeholder="Paste ASCII diagram here..."><?php echo htmlspecialchars($e->diagram ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div class="form-text"><?php echo __('Optional ASCII art ERD diagram. Displayed in a &lt;pre&gt; block.'); ?></div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Additional Notes'); ?></h5></div>
        <div class="card-body">
          <textarea class="form-control" name="notes" rows="4"><?php echo htmlspecialchars($e->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div class="col-lg-4">
      <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Settings'); ?></h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label"><?php echo __('Sort Order'); ?></label>
            <input type="number" class="form-control" name="sort_order" value="<?php echo (int) ($e->sort_order ?? 100); ?>">
          </div>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php echo ($e->is_active ?? 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active"><?php echo __('Active'); ?></label>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-1"></i><?php echo $isNew ? __('Create') : __('Save Changes'); ?>
        </button>
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErd']); ?>" class="btn btn-outline-secondary">
          <?php echo __('Cancel'); ?>
        </a>
      </div>
    </div>

  </div>
</form>

<?php end_slot(); ?>
