<?php use_helper('Text'); ?>

<?php
$regions = $sf_data->getRaw('regions');
$standardsByRegion = $sf_data->getRaw('standardsByRegion');
$rulesByRegion = $sf_data->getRaw('rulesByRegion');
$activeConfig = $sf_data->getRaw('activeConfig');
$activeRegion = $activeConfig ? $activeConfig->region_code : null;
?>

<h1><i class="fas fa-globe me-2"></i><?php echo __('Heritage Accounting Regions'); ?></h1>

<p class="text-muted mb-4">
  <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'index']); ?>">
    <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Heritage Admin'); ?>
  </a>
</p>

<?php if ($sf_user->hasFlash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo $sf_user->getFlash('success'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo $sf_user->getFlash('error'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-info alert-dismissible fade show">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo $sf_user->getFlash('notice'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Active Region Banner -->
<?php if ($activeRegion): ?>
  <?php
    $activeRegionData = null;
    foreach ($regions as $r) {
        if ($r->region_code === $activeRegion) {
            $activeRegionData = $r;
            break;
        }
    }
  ?>
  <?php if ($activeRegionData): ?>
    <div class="alert alert-primary mb-4">
      <div class="d-flex align-items-center">
        <i class="fas fa-check-circle me-3 fs-4"></i>
        <div>
          <strong><?php echo __('Active Region:'); ?></strong>
          <?php echo htmlspecialchars($activeRegionData->region_name); ?>
          <span class="badge bg-white text-primary ms-2"><?php echo htmlspecialchars($activeConfig->currency ?? $activeRegionData->default_currency); ?></span>
        </div>
      </div>
    </div>
  <?php endif; ?>
<?php else: ?>
  <div class="alert alert-warning mb-4">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <?php echo __('No active region set. Install and activate a region to enable compliance checking.'); ?>
  </div>
<?php endif; ?>

<!-- Explanation Card -->
<div class="card mb-4">
  <div class="card-body">
    <h5 class="card-title"><i class="fas fa-info-circle me-2"></i><?php echo __('About Regional Standards'); ?></h5>
    <p class="card-text text-muted mb-0">
      <?php echo __('Each region implements specific accounting standards for heritage assets. Install only the regions you need - this keeps your database lean and compliance rules relevant to your jurisdiction.'); ?>
    </p>
  </div>
</div>

<!-- Regions Grid -->
<div class="row g-4">
  <?php foreach ($regions as $region): ?>
    <?php
      $isInstalled = $region->is_installed;
      $isActive = $region->region_code === $activeRegion;
      $rulesCount = $rulesByRegion[$region->region_code] ?? 0;
      $countries = is_array($region->countries) ? $region->countries : [];
    ?>
    <div class="col-md-6 col-lg-4">
      <div class="card h-100 <?php echo $isActive ? 'border-primary border-2' : ($isInstalled ? 'border-success' : ''); ?>">
        <div class="card-header <?php echo $isActive ? 'bg-primary text-white' : ($isInstalled ? 'bg-success bg-opacity-10' : 'bg-light'); ?>">
          <div class="d-flex justify-content-between align-items-center">
            <strong><?php echo htmlspecialchars($region->region_name); ?></strong>
            <?php if ($isActive): ?>
              <span class="badge bg-white text-primary">ACTIVE</span>
            <?php elseif ($isInstalled): ?>
              <span class="badge bg-success">Installed</span>
            <?php else: ?>
              <span class="badge bg-secondary">Not Installed</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body">
          <p class="card-text small text-muted mb-2">
            <i class="fas fa-map-marker-alt me-1"></i>
            <?php echo htmlspecialchars(implode(', ', array_slice($countries, 0, 3))); ?>
            <?php if (count($countries) > 3): ?>
              <span class="text-muted">+<?php echo count($countries) - 3; ?> more</span>
            <?php endif; ?>
          </p>

          <div class="mb-3">
            <span class="badge bg-light text-dark me-1">
              <i class="fas fa-money-bill me-1"></i><?php echo htmlspecialchars($region->default_currency); ?>
            </span>
            <?php if ($isInstalled): ?>
              <span class="badge bg-info text-white">
                <i class="fas fa-check-square me-1"></i><?php echo $rulesCount; ?> rules
              </span>
            <?php endif; ?>
          </div>

          <p class="card-text small">
            <strong><?php echo __('Regulatory Body:'); ?></strong><br>
            <?php echo htmlspecialchars($region->regulatory_body); ?>
          </p>

          <?php if ($isInstalled && $region->installed_at): ?>
            <p class="card-text small text-muted">
              <i class="fas fa-calendar-check me-1"></i>
              <?php echo __('Installed:'); ?> <?php echo date('Y-m-d', strtotime($region->installed_at)); ?>
            </p>
          <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent">
          <div class="btn-group w-100" role="group">
            <?php if (!$isInstalled): ?>
              <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionInstall', 'region' => $region->region_code]); ?>"
                 class="btn btn-success btn-sm"
                 onclick="return confirm('Install <?php echo htmlspecialchars($region->region_name); ?>? This will add the accounting standard and compliance rules.');">
                <i class="fas fa-download me-1"></i><?php echo __('Install'); ?>
              </a>
            <?php else: ?>
              <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionInfo', 'region' => $region->region_code]); ?>"
                 class="btn btn-outline-primary btn-sm">
                <i class="fas fa-info-circle me-1"></i><?php echo __('Details'); ?>
              </a>
              <?php if (!$isActive): ?>
                <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionSetActive', 'region' => $region->region_code]); ?>"
                   class="btn btn-primary btn-sm"
                   onclick="return confirm('Set <?php echo htmlspecialchars($region->region_name); ?> as the active region?');">
                  <i class="fas fa-check-circle me-1"></i><?php echo __('Activate'); ?>
                </a>
                <a href="<?php echo url_for(['module' => 'heritageAdmin', 'action' => 'regionUninstall', 'region' => $region->region_code]); ?>"
                   class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('Uninstall <?php echo htmlspecialchars($region->region_name); ?>? This will remove the standard and compliance rules.');">
                  <i class="fas fa-trash me-1"></i>
                </a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Help Section -->
<div class="card mt-4">
  <div class="card-header">
    <i class="fas fa-terminal me-2"></i><?php echo __('CLI Commands'); ?>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-2"><?php echo __('You can also manage regions via command line:'); ?></p>
    <pre class="bg-dark text-light p-3 rounded small mb-0"><code>php symfony heritage:region                        # List all regions
php symfony heritage:region --install=africa_ipsas # Install a region
php symfony heritage:region --set-active=uk_frs    # Set active region
php symfony heritage:region --info=south_africa_grap # View details
php symfony heritage:region --uninstall=uk_frs     # Uninstall a region</code></pre>
  </div>
</div>
