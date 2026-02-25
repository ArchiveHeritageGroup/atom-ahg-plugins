<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('My Groups'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Groups')],
]]); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo __('My Groups'); ?></h1>
  <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupCreate']); ?>" class="btn btn-primary btn-sm">
    <i class="fas fa-plus me-1"></i> <?php echo __('Create New Group'); ?>
  </a>
</div>

<?php
  $organized = [];
  $memberOf = [];
  if (!empty($myGroups)) {
    foreach ($myGroups as $g) {
      if (($g->role ?? '') === 'organizer') {
        $organized[] = $g;
      } else {
        $memberOf[] = $g;
      }
    }
  }
?>

<!-- Groups I organize -->
<div class="mb-5">
  <h2 class="h5 mb-3"><i class="fas fa-crown text-warning me-2"></i><?php echo __('Groups I Organize'); ?></h2>
  <?php if (!empty($organized)): ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
    <?php foreach ($organized as $g): ?>
    <div class="col">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $g->slug ?? '']); ?>" class="text-decoration-none">
              <?php echo htmlspecialchars($g->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h5>
          <?php if (!empty($g->group_type)): ?>
            <span class="badge bg-primary me-1"><?php echo htmlspecialchars(ucfirst($g->group_type), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i><?php echo __('Organizer'); ?></span>
          <div class="mt-2 text-muted small">
            <i class="fas fa-users me-1"></i> <?php echo (int) ($g->member_count ?? 0); ?> <?php echo __('members'); ?>
          </div>
          <?php if (!empty($g->description)): ?>
            <p class="card-text small text-muted mt-2 mb-0"><?php echo htmlspecialchars(mb_strimwidth($g->description, 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent">
          <div class="d-flex gap-1">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupEdit', 'id' => $g->id]); ?>" class="btn btn-sm btn-outline-primary flex-fill">
              <i class="fas fa-edit me-1"></i> <?php echo __('Edit'); ?>
            </a>
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupMembersManage', 'id' => $g->id]); ?>" class="btn btn-sm btn-outline-secondary flex-fill">
              <i class="fas fa-users me-1"></i> <?php echo __('Members'); ?>
            </a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-body text-center text-muted py-4">
      <i class="fas fa-crown fa-2x mb-2"></i>
      <p class="mb-0"><?php echo __('You are not organizing any groups yet.'); ?></p>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- Groups I belong to -->
<div class="mb-4">
  <h2 class="h5 mb-3"><i class="fas fa-users text-info me-2"></i><?php echo __('Groups I Belong To'); ?></h2>
  <?php if (!empty($memberOf)): ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
    <?php foreach ($memberOf as $g): ?>
    <div class="col">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title">
            <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $g->slug ?? '']); ?>" class="text-decoration-none">
              <?php echo htmlspecialchars($g->name ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </a>
          </h5>
          <?php if (!empty($g->group_type)): ?>
            <span class="badge bg-primary me-1"><?php echo htmlspecialchars(ucfirst($g->group_type), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
          <span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst($g->role ?? 'member'), ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="mt-2 text-muted small">
            <i class="fas fa-users me-1"></i> <?php echo (int) ($g->member_count ?? 0); ?> <?php echo __('members'); ?>
          </div>
          <?php if (!empty($g->description)): ?>
            <p class="card-text small text-muted mt-2 mb-0"><?php echo htmlspecialchars(mb_strimwidth($g->description, 0, 120, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-body text-center text-muted py-4">
      <i class="fas fa-users fa-2x mb-2"></i>
      <p class="mb-1"><?php echo __('You have not joined any groups yet.'); ?></p>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupBrowse']); ?>" class="btn btn-outline-primary btn-sm mt-2">
        <i class="fas fa-search me-1"></i> <?php echo __('Browse Groups'); ?>
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php end_slot(); ?>
