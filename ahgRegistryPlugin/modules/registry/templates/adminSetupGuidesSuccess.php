<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Manage Setup Guides'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Setup Guides')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Manage Setup Guides'); ?></h1>
</div>

<div class="alert alert-info">
  <i class="fas fa-info-circle me-1"></i>
  <?php echo __('Setup guides are managed through the vendor software management interface. This page provides an overview of all guides across all software.'); ?>
</div>

<?php if (!empty($guides)): ?>
<div class="table-responsive">
  <table class="table table-hover table-striped align-middle">
    <thead class="table-light">
      <tr>
        <th><?php echo __('Software'); ?></th>
        <th><?php echo __('Title'); ?></th>
        <th><?php echo __('Category'); ?></th>
        <th><?php echo __('Author'); ?></th>
        <th class="text-center"><?php echo __('Views'); ?></th>
        <th class="text-center"><?php echo __('Active'); ?></th>
        <th class="text-end"><?php echo __('Actions'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($guides as $guide): ?>
      <tr>
        <td>
          <?php if (!empty($guide->software_slug)): ?>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $guide->software_slug]); ?>" class="text-decoration-none">
              <?php echo htmlspecialchars($guide->software_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
          <?php else: ?>
            <?php echo htmlspecialchars($guide->software_name ?? __('Unknown'), ENT_QUOTES, 'UTF-8'); ?>
          <?php endif; ?>
        </td>
        <td class="fw-semibold"><?php echo htmlspecialchars($guide->title ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <?php if (!empty($guide->category)): ?>
            <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $guide->category)), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($guide->author ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        <td class="text-center">
          <span class="badge bg-light text-dark border"><?php echo number_format((int) ($guide->view_count ?? 0)); ?></span>
        </td>
        <td class="text-center">
          <?php if (!isset($guide->is_active) || $guide->is_active): ?>
            <span class="badge bg-success"><?php echo __('Active'); ?></span>
          <?php else: ?>
            <span class="badge bg-danger"><?php echo __('Inactive'); ?></span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideView', 'softwareSlug' => $guide->software_slug ?? '', 'slug' => $guide->slug ?? '']); ?>" class="btn btn-sm btn-outline-info" title="<?php echo __('View'); ?>">
              <i class="fas fa-eye"></i>
            </a>
            <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'adminSetupGuides']); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Are you sure you want to delete this setup guide? This cannot be undone.'); ?>');">
              <input type="hidden" name="form_action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int) ($guide->id ?? 0); ?>">
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
  <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No setup guides found'); ?></h5>
  <p class="text-muted"><?php echo __('Setup guides are added through the vendor software management interface.'); ?></p>
</div>
<?php endif; ?>

<?php end_slot(); ?>
