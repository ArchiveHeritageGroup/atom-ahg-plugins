<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('My Favorites'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('My Favorites')],
]]); ?>

<h1 class="h3 mb-4"><i class="fas fa-star text-warning me-2"></i><?php echo __('My Favorites'); ?></h1>

<?php $hasAny = !empty($institutions) || !empty($vendors) || !empty($software) || !empty($groups); ?>

<?php if (!$hasAny): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-1"></i>
    <?php echo __('You have not favorited anything yet. Browse the registry and click the star icon to add items to your favorites.'); ?>
  </div>
<?php endif; ?>

<?php if (!empty($institutions)): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fas fa-university me-2 text-primary"></i><?php echo __('Institutions'); ?> <span class="badge bg-primary"><?php echo count($institutions); ?></span></div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($institutions as $inst): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'institutionView', 'slug' => $inst->slug]); ?>" class="list-group-item list-group-item-action">
        <div class="d-flex align-items-center">
          <?php if (!empty($inst->logo_path)): ?>
            <img src="<?php echo htmlspecialchars($inst->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 40px; height: 40px; object-fit: contain;">
          <?php else: ?>
            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-university text-muted"></i></div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <strong><?php echo htmlspecialchars($inst->name, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if (!empty($inst->is_verified)): ?><i class="fas fa-check-circle text-primary ms-1"></i><?php endif; ?>
            <div class="small text-muted">
              <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $inst->institution_type ?? '')), ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($inst->country)): ?>&middot; <?php echo htmlspecialchars($inst->country, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
            </div>
          </div>
          <i class="fas fa-star text-warning"></i>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($vendors)): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fas fa-building me-2 text-success"></i><?php echo __('Vendors'); ?> <span class="badge bg-success"><?php echo count($vendors); ?></span></div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($vendors as $v): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'vendorView', 'slug' => $v->slug]); ?>" class="list-group-item list-group-item-action">
        <div class="d-flex align-items-center">
          <?php if (!empty($v->logo_path)): ?>
            <img src="<?php echo htmlspecialchars($v->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 40px; height: 40px; object-fit: contain;">
          <?php else: ?>
            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-building text-muted"></i></div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <strong><?php echo htmlspecialchars($v->name, ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if (!empty($v->is_verified)): ?><i class="fas fa-check-circle text-primary ms-1"></i><?php endif; ?>
            <div class="small text-muted"><?php
              $rawVt = sfOutputEscaper::unescape($v->vendor_type ?? '[]');
              $vtArr = is_string($rawVt) ? (json_decode($rawVt, true) ?: []) : (is_array($rawVt) ? $rawVt : []);
              echo htmlspecialchars(implode(', ', array_map(function($t) { return ucfirst(str_replace('_', ' ', $t)); }, $vtArr)), ENT_QUOTES, 'UTF-8');
            ?></div>
          </div>
          <i class="fas fa-star text-warning"></i>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($software)): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fas fa-cube me-2 text-info"></i><?php echo __('Software'); ?> <span class="badge bg-info"><?php echo count($software); ?></span></div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($software as $sw): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $sw->slug]); ?>" class="list-group-item list-group-item-action">
        <div class="d-flex align-items-center">
          <?php if (!empty($sw->logo_path)): ?>
            <img src="<?php echo htmlspecialchars($sw->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-3" style="width: 40px; height: 40px; object-fit: contain;">
          <?php else: ?>
            <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-cube text-muted"></i></div>
          <?php endif; ?>
          <div class="flex-grow-1">
            <strong><?php echo htmlspecialchars($sw->name, ENT_QUOTES, 'UTF-8'); ?></strong>
            <div class="small text-muted"><?php echo htmlspecialchars($sw->category ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <i class="fas fa-star text-warning"></i>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($groups)): ?>
<div class="card mb-4">
  <div class="card-header fw-semibold"><i class="fas fa-users me-2 text-warning"></i><?php echo __('User Groups'); ?> <span class="badge bg-warning text-dark"><?php echo count($groups); ?></span></div>
  <div class="card-body p-0">
    <div class="list-group list-group-flush">
      <?php foreach ($groups as $g): ?>
      <a href="<?php echo url_for(['module' => 'registry', 'action' => 'groupView', 'slug' => $g->slug]); ?>" class="list-group-item list-group-item-action">
        <div class="d-flex align-items-center">
          <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;"><i class="fas fa-users text-muted"></i></div>
          <div class="flex-grow-1">
            <strong><?php echo htmlspecialchars($g->name, ENT_QUOTES, 'UTF-8'); ?></strong>
            <div class="small text-muted"><?php echo (int) ($g->member_count ?? 0); ?> <?php echo __('members'); ?></div>
          </div>
          <i class="fas fa-star text-warning"></i>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php end_slot(); ?>
