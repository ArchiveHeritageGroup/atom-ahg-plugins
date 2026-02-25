<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Components'); ?> - <?php echo htmlspecialchars($software->name, ENT_QUOTES, 'UTF-8'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareBrowse'])],
  ['label' => htmlspecialchars($software->name, ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug])],
  ['label' => __('Components')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Components & Plugins'); ?> <span class="badge bg-secondary"><?php echo count($components); ?></span></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareComponentAdd', 'id' => (int) $software->id]); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Component'); ?>
  </a>
</div>

<?php if (empty($components)): ?>
  <div class="alert alert-info"><?php echo __('No components have been added yet.'); ?></div>
<?php else: ?>
  <?php
    $grouped = [];
    foreach ($components as $comp) {
      $cat = $comp->category ?: __('Uncategorized');
      $grouped[$cat][] = $comp;
    }
  ?>
  <?php foreach ($grouped as $catName => $catComponents): ?>
    <div class="card mb-3">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><?php echo htmlspecialchars($catName, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="badge bg-secondary"><?php echo count($catComponents); ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="min-width: 250px;"><?php echo __('Name'); ?></th>
              <th><?php echo __('Type'); ?></th>
              <th><?php echo __('Version'); ?></th>
              <th class="text-center"><?php echo __('Required'); ?></th>
              <th><?php echo __('Links'); ?></th>
              <th class="text-end"><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($catComponents as $comp): ?>
            <tr>
              <td>
                <?php if (!empty($comp->icon_class)): ?>
                  <i class="<?php echo htmlspecialchars($comp->icon_class, ENT_QUOTES, 'UTF-8'); ?> me-1 text-muted"></i>
                <?php endif; ?>
                <strong><?php echo htmlspecialchars($comp->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                <br><code class="small text-muted"><?php echo htmlspecialchars($comp->slug, ENT_QUOTES, 'UTF-8'); ?></code>
                <?php if (!empty($comp->short_description)): ?>
                  <br><small class="text-muted"><?php echo htmlspecialchars($comp->short_description, ENT_QUOTES, 'UTF-8'); ?></small>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst($comp->component_type ?? ''), ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td>
                <?php if (!empty($comp->version)): ?>
                  <span class="badge bg-primary"><?php echo htmlspecialchars($comp->version, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if (!empty($comp->is_required)): ?>
                  <i class="fas fa-check text-success"></i>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap">
                <?php if (!empty($comp->git_url)): ?>
                  <a href="<?php echo htmlspecialchars($comp->git_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark me-1" title="<?php echo __('Source Code'); ?>">
                    <i class="fab fa-github"></i>
                  </a>
                <?php endif; ?>
                <?php if (!empty($comp->documentation_url)): ?>
                  <a href="<?php echo htmlspecialchars($comp->documentation_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-info me-1" title="<?php echo __('Documentation'); ?>">
                    <i class="fas fa-book"></i>
                  </a>
                <?php endif; ?>
              </td>
              <td class="text-end text-nowrap">
                <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareComponentEdit', 'comp_id' => (int) $comp->id]); ?>" class="btn btn-sm btn-outline-primary me-1" title="<?php echo __('Edit'); ?>">
                  <i class="fas fa-edit"></i>
                </a>
                <form method="post" action="<?php echo url_for(['module' => 'registry', 'action' => 'softwareComponentDelete', 'comp_id' => (int) $comp->id]); ?>" class="d-inline" onsubmit="return confirm('<?php echo __('Delete this component?'); ?>');">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="<?php echo __('Delete'); ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<div class="mt-3">
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug]); ?>" class="btn btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back to Software'); ?>
  </a>
</div>

<?php end_slot(); ?>
