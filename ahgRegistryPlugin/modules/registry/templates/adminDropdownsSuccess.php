<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Dropdown Manager'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Dropdown Manager')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="fas fa-list me-2"></i><?php echo __('Dropdown Manager'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDropdownEdit']); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Value'); ?>
  </a>
</div>

<!-- Group filter pills -->
<div class="mb-4">
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDropdowns']); ?>" class="btn btn-sm <?php echo empty($selectedGroup) ? 'btn-primary' : 'btn-outline-primary'; ?> me-1 mb-1"><?php echo __('All'); ?></a>
  <?php foreach ($groups as $g): ?>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDropdowns', 'group' => $g]); ?>" class="btn btn-sm <?php echo $selectedGroup === $g ? 'btn-primary' : 'btn-outline-primary'; ?> me-1 mb-1">
      <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $g)), ENT_QUOTES, 'UTF-8'); ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!empty($items)): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover table-sm mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Group'); ?></th>
          <th><?php echo __('Value'); ?></th>
          <th><?php echo __('Label'); ?></th>
          <th><?php echo __('Badge'); ?></th>
          <th class="text-center"><?php echo __('Order'); ?></th>
          <th class="text-center"><?php echo __('Active'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $row): ?>
        <tr>
          <td><code><?php echo htmlspecialchars($row->dropdown_group, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><code><?php echo htmlspecialchars($row->value, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td><?php echo htmlspecialchars($row->label, ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php if ($row->badge_color): ?>
              <span class="badge bg-<?php echo htmlspecialchars($row->badge_color, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row->badge_color, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?php echo (int) $row->sort_order; ?></td>
          <td class="text-center">
            <?php echo $row->is_active ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>'; ?>
          </td>
          <td class="text-end">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminDropdownEdit', 'id' => (int) $row->id]); ?>" class="btn btn-sm btn-outline-primary" title="<?php echo __('Edit'); ?>"><i class="fas fa-edit"></i></a>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminDropdownDelete', 'id' => (int) $row->id]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this dropdown value?'); ?>');">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-list fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No dropdown values found'); ?></h5>
</div>
<?php endif; ?>

<?php end_slot(); ?>
