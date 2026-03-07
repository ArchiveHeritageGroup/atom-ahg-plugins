<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Standards'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Standards')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Standards'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Standard'); ?>
  </a>
</div>

<!-- Category filter tabs -->
<?php if (!empty($categories)): ?>
<ul class="nav nav-pills mb-4">
  <li class="nav-item">
    <a class="nav-link<?php echo empty($currentCategory) ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandards']); ?>">
      <?php echo __('All'); ?>
    </a>
  </li>
  <?php foreach ($categories as $cat): ?>
  <li class="nav-item">
    <a class="nav-link<?php echo ($currentCategory === ($cat->category ?? '')) ? ' active' : ''; ?>" href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandards', 'category' => $cat->category ?? '']); ?>">
      <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $cat->category ?? '')), ENT_QUOTES, 'UTF-8'); ?>
      <span class="badge bg-secondary ms-1"><?php echo (int) ($cat->cnt ?? 0); ?></span>
    </a>
  </li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if (!empty($standards)): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th style="width: 60px;"><?php echo __('Sort'); ?></th>
        <th><?php echo __('Acronym'); ?></th>
        <th><?php echo __('Name'); ?></th>
        <th><?php echo __('Category'); ?></th>
        <th><?php echo __('Issuing Body'); ?></th>
        <th class="text-center"><?php echo __('Extensions'); ?></th>
        <th class="text-center"><?php echo __('Featured'); ?></th>
        <th class="text-center"><?php echo __('Active'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($standards as $standard): ?>
      <tr>
        <td><?php echo (int) ($standard->sort_order ?? 0); ?></td>
        <td>
          <?php if (!empty($standard->acronym)): ?>
            <span class="badge bg-dark"><?php echo htmlspecialchars($standard->acronym, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => (int) $standard->id]); ?>" class="fw-semibold text-decoration-none">
            <?php echo htmlspecialchars($standard->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </td>
        <td>
          <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $standard->category ?? '')), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td><?php echo htmlspecialchars($standard->issuing_body ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-center">
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => (int) $standard->id]); ?>#extensions" class="badge bg-light text-dark border text-decoration-none">
            <?php echo (int) ($standard->extension_count ?? 0); ?>
          </a>
        </td>
        <td class="text-center">
          <?php if (!empty($standard->is_featured)): ?>
            <span class="badge bg-primary"><i class="fas fa-star"></i></span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td class="text-center">
          <?php if (!isset($standard->is_active) || $standard->is_active): ?>
            <span class="badge bg-success"><?php echo __('Active'); ?></span>
          <?php else: ?>
            <span class="badge bg-danger"><?php echo __('Inactive'); ?></span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit', 'id' => (int) $standard->id]); ?>" class="btn btn-sm btn-outline-secondary" title="<?php echo __('Edit'); ?>">
              <i class="fas fa-edit"></i>
            </a>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandards']); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Are you sure you want to delete this standard? This cannot be undone.'); ?>');">
              <input type="hidden" name="form_action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int) $standard->id; ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No standards found'); ?></h5>
  <p class="text-muted"><?php echo __('Add your first standard to get started.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'adminStandardEdit']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Standard'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
