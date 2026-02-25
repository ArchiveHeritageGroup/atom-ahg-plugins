<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Software Products'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Vendor Dashboard'), 'url' => url_for(['module' => 'registry', 'action' => 'myVendorDashboard'])],
  ['label' => __('Software Products')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('Software Products'); ?></h1>
  <div>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareAdd']); ?>" class="btn btn-primary btn-sm">
      <i class="fas fa-plus me-1"></i> <?php echo __('Add Software'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorDashboard']); ?>" class="btn btn-outline-secondary btn-sm ms-1">
      <i class="fas fa-arrow-left me-1"></i> <?php echo __('Back'); ?>
    </a>
  </div>
</div>

<?php if (!empty($software) && count($software) > 0): ?>
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
  <?php foreach ($software as $sw): ?>
  <div class="col">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start mb-2">
          <?php if (!empty($sw->logo_path)): ?>
            <img src="<?php echo htmlspecialchars($sw->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-2" style="width: 40px; height: 40px; object-fit: contain;">
          <?php else: ?>
            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
              <i class="fas fa-laptop-code text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <h5 class="card-title mb-0"><?php echo htmlspecialchars($sw->name ?? '', ENT_QUOTES, 'UTF-8'); ?></h5>
            <?php if (!empty($sw->category)): ?>
              <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $sw->category)), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if (!empty($sw->short_description)): ?>
          <p class="card-text small text-muted"><?php echo htmlspecialchars($sw->short_description, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center small text-muted mt-2">
          <div>
            <?php if (!empty($sw->latest_version)): ?>
              <span class="badge bg-primary">v<?php echo htmlspecialchars($sw->latest_version, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if (!empty($sw->git_url)): ?>
              <a href="<?php echo htmlspecialchars($sw->git_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="ms-1"><i class="fab fa-git-alt"></i></a>
            <?php endif; ?>
          </div>
          <span><?php echo number_format($sw->download_count ?? 0); ?> <?php echo __('downloads'); ?></span>
        </div>
      </div>
      <div class="card-footer bg-transparent">
        <div class="d-flex gap-1">
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareEdit', 'id' => $sw->id]); ?>" class="btn btn-sm btn-outline-primary flex-fill">
            <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
          </a>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareReleases', 'id' => $sw->id]); ?>" class="btn btn-sm btn-outline-success flex-fill">
            <i class="fas fa-tags me-1"></i> <?php echo __('Releases'); ?>
          </a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="text-center py-5">
  <i class="fas fa-laptop-code fa-3x text-muted mb-3"></i>
  <h5><?php echo __('No software products yet'); ?></h5>
  <p class="text-muted"><?php echo __('Add your first software product to the directory.'); ?></p>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'myVendorSoftwareAdd']); ?>" class="btn btn-primary">
    <i class="fas fa-plus me-1"></i> <?php echo __('Add Software'); ?>
  </a>
</div>
<?php endif; ?>

<?php end_slot(); ?>
