<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo htmlspecialchars($guide->title ?? '', ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Setup Guide'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Software'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareBrowse'])],
  ['label' => htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'), 'url' => url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug ?? ''])],
  ['label' => __('Setup Guides'), 'url' => url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $software->slug ?? ''])],
  ['label' => htmlspecialchars($guide->title ?? '', ENT_QUOTES, 'UTF-8')],
]]); ?>

<?php
  $guideCatBg = [
    'security' => 'bg-danger',
    'deployment' => 'bg-primary',
    'configuration' => 'bg-info text-dark',
    'optimization' => 'bg-success',
    'troubleshooting' => 'bg-warning text-dark',
    'integration' => 'bg-dark',
  ];
  $gCat = $guide->category ?? '';
  $gCatClass = $guideCatBg[strtolower($gCat)] ?? 'bg-secondary';
?>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <!-- Header -->
    <div class="mb-4">
      <h1 class="h3 mb-2"><?php echo htmlspecialchars($guide->title ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="badge <?php echo $gCatClass; ?>"><?php echo htmlspecialchars(ucfirst($gCat), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php if (!empty($guide->is_featured)): ?>
          <span class="badge bg-warning text-dark"><i class="fas fa-award me-1"></i><?php echo __('Featured'); ?></span>
        <?php endif; ?>
        <?php if (!empty($guide->author_name)): ?>
          <span class="text-muted small"><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($guide->author_name, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <?php if (!empty($guide->updated_at)): ?>
          <span class="text-muted small"><i class="fas fa-clock me-1"></i><?php echo date('M j, Y', strtotime($guide->updated_at)); ?></span>
        <?php endif; ?>
        <?php if (!empty($guide->view_count)): ?>
          <span class="text-muted small"><i class="fas fa-eye me-1"></i><?php echo number_format((int) $guide->view_count); ?> <?php echo __('views'); ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Content -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="guide-content">
          <?php echo nl2br(htmlspecialchars($guide->content ?? '', ENT_QUOTES, 'UTF-8')); ?>
        </div>
      </div>
    </div>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Software link -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Software'); ?></div>
      <div class="card-body">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'softwareView', 'slug' => $software->slug ?? '']); ?>" class="d-flex align-items-center text-decoration-none">
          <?php if (!empty($software->logo_path)): ?>
            <img src="<?php echo htmlspecialchars($software->logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="rounded me-2 flex-shrink-0" style="width: 40px; height: 40px; object-fit: contain;">
          <?php else: ?>
            <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
              <i class="fas fa-box-open text-muted"></i>
            </div>
          <?php endif; ?>
          <div>
            <strong><?php echo htmlspecialchars($software->name ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php if (!empty($software->latest_version)): ?>
              <br><small class="text-muted">v<?php echo htmlspecialchars($software->latest_version, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php endif; ?>
          </div>
        </a>
      </div>
    </div>

    <!-- Other guides for this software -->
    <?php if (!empty($otherGuides)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><?php echo __('Other Guides'); ?></div>
      <ul class="list-group list-group-flush">
        <?php foreach ($otherGuides as $other): ?>
        <li class="list-group-item">
          <?php
            $oCat = $other->category ?? '';
            $oCatClass = $guideCatBg[strtolower($oCat)] ?? 'bg-secondary';
          ?>
          <a href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideView', 'slug' => $software->slug ?? '', 'guide_slug' => $other->slug ?? '']); ?>" class="text-decoration-none">
            <?php echo htmlspecialchars($other->title ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </a>
          <span class="badge <?php echo $oCatClass; ?> ms-1" style="font-size: 0.65em;"><?php echo htmlspecialchars(ucfirst($oCat), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($other->view_count)): ?>
            <small class="text-muted d-block"><i class="fas fa-eye me-1"></i><?php echo number_format((int) $other->view_count); ?></small>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="card-footer text-center">
        <a href="<?php echo url_for(['module' => 'registry', 'action' => 'setupGuideBrowse', 'slug' => $software->slug ?? '']); ?>" class="btn btn-sm btn-outline-primary">
          <?php echo __('View All Guides'); ?>
        </a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php end_slot(); ?>
