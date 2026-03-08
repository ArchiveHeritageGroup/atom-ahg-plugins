<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage ERD Documentation'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('ERD Documentation')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage ERD Documentation'); ?></h1>
  <div class="d-flex gap-2">
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdBrowse']); ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-eye me-1"></i><?php echo __('Public View'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErdEdit']); ?>" class="btn btn-success btn-sm">
      <i class="fas fa-plus me-1"></i><?php echo __('Add ERD Entry'); ?>
    </a>
  </div>
</div>

<?php
  $rawItems = sfOutputEscaper::unescape($items);
  if (!is_array($rawItems)) { $rawItems = []; }
?>

<div class="table-responsive">
  <table class="table table-sm table-hover">
    <thead class="table-light">
      <tr>
        <th style="width:40px;">#</th>
        <th><?php echo __('Plugin'); ?></th>
        <th><?php echo __('Display Name'); ?></th>
        <th><?php echo __('Category'); ?></th>
        <th><?php echo __('Tables'); ?></th>
        <th><?php echo __('Diagram'); ?></th>
        <th><?php echo __('Order'); ?></th>
        <th><?php echo __('Active'); ?></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rawItems as $erd): ?>
      <?php $tables = json_decode($erd->tables_json ?? '[]', true); ?>
      <tr>
        <td class="text-muted"><?php echo (int) $erd->id; ?></td>
        <td><code class="small"><?php echo htmlspecialchars($erd->plugin_name, ENT_QUOTES, 'UTF-8'); ?></code></td>
        <td>
          <i class="<?php echo htmlspecialchars($erd->icon ?? 'fas fa-database', ENT_QUOTES, 'UTF-8'); ?> text-<?php echo htmlspecialchars($erd->color ?? 'primary', ENT_QUOTES, 'UTF-8'); ?> me-1"></i>
          <?php echo htmlspecialchars($erd->display_name, ENT_QUOTES, 'UTF-8'); ?>
        </td>
        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($erd->category, ENT_QUOTES, 'UTF-8'); ?></span></td>
        <td><span class="badge bg-light text-dark border"><?php echo is_array($tables) ? count($tables) : 0; ?></span></td>
        <td>
          <?php if (!empty($erd->diagram_image) && !empty($erd->diagram)): ?>
            <span class="badge bg-success" title="Image + ASCII"><i class="fas fa-image me-1"></i><i class="fas fa-code"></i></span>
          <?php elseif (!empty($erd->diagram_image)): ?>
            <span class="badge bg-success" title="Image"><i class="fas fa-image"></i></span>
          <?php elseif (!empty($erd->diagram)): ?>
            <span class="badge bg-success" title="ASCII"><i class="fas fa-code"></i></span>
          <?php else: ?>
            <span class="badge bg-light text-muted border">-</span>
          <?php endif; ?>
        </td>
        <td><?php echo (int) $erd->sort_order; ?></td>
        <td>
          <?php if ($erd->is_active): ?>
            <span class="badge bg-success"><?php echo __('Yes'); ?></span>
          <?php else: ?>
            <span class="badge bg-danger"><?php echo __('No'); ?></span>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminErdEdit', 'id' => $erd->id]); ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-edit"></i>
          </a>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'erdView', 'slug' => $erd->slug]); ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="fas fa-eye"></i>
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php end_slot(); ?>
