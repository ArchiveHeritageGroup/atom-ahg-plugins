<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Software Used'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Institution'), 'url' => url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard'])],
  ['label' => __('Software Used')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Software Used'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>" class="btn btn-outline-primary btn-sm">
      <i class="fas fa-search me-1"></i> <?php echo __('Browse Software Directory'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myInstitutionDashboard']); ?>" class="btn btn-outline-secondary btn-sm ms-1">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
    </a>
  </div>
</div>

<?php if (!empty($software) && count($software) > 0): ?>
<div class="card">
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Software Name'); ?></th>
          <th><?php echo __('Version in Use'); ?></th>
          <th><?php echo __('Deployment Date'); ?></th>
          <th><?php echo __('Notes'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($software as $sw): ?>
        <tr>
          <td>
            <?php if (!empty($sw->slug)): ?>
              <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $sw->slug]); ?>">
                <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
              </a>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
            <?php if (!empty($sw->vendor_name)): ?>
              <br><small class="text-muted"><?php echo __('by'); ?> <?php echo htmlspecialchars($sw->vendor_name, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($sw->version_in_use)): ?>
              <span class="badge bg-secondary"><?php echo htmlspecialchars($sw->version_in_use, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($sw->deployment_date)): ?>
              <?php echo date('M j, Y', strtotime($sw->deployment_date)); ?>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td><?php echo htmlspecialchars($sw->notes ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-laptop-code fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No software linked yet'); ?></h5>
  <p class="text-muted"><?php echo __('Software associations are created when you register instances or through vendor relationships.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareBrowse']); ?>" class="btn btn-primary">
    <i class="fas fa-search me-1"></i> <?php echo __('Browse Software Directory'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
