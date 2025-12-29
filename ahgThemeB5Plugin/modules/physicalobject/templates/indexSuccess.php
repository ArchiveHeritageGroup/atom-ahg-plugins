<?php decorate_with('layout_1col.php'); ?>

<?php slot('title'); ?>
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0"><?php echo render_title($resource); ?></h1>
    <span class="small"><?php echo __('View %1%', ['%1%' => sfConfig::get('app_ui_label_physicalobject')]); ?></span>
  </div>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  // Get related resources using Framework
  $relatedResources = [];
  if (!empty($extendedData) || $resource->id) {
      require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Repositories/PhysicalObjectExtendedRepository.php';
      $repo = new \AtomFramework\Repositories\PhysicalObjectExtendedRepository();
      $relatedResources = $repo->getRelatedResources($resource->id);
  }
?>

<div class="row">
  <div class="col-md-8">

    <!-- Basic Information -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i><?php echo __('Physical Storage'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if ($resource->type): ?>
          <dt class="col-sm-4"><?php echo __('Type'); ?></dt>
          <dd class="col-sm-8"><?php echo render_value($resource->type); ?></dd>
          <?php endif; ?>

          <?php if ($resource->getLocation(['cultureFallback' => true])): ?>
          <dt class="col-sm-4"><?php echo __('Location'); ?></dt>
          <dd class="col-sm-8"><?php echo render_value($resource->getLocation(['cultureFallback' => true])); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <?php if (!empty($extendedData)): ?>
    <!-- Extended Location -->
    <?php
      $locationParts = array_filter([
        $extendedData['building'] ?? null,
        !empty($extendedData['floor']) ? 'Floor ' . $extendedData['floor'] : null,
        !empty($extendedData['room']) ? 'Room ' . $extendedData['room'] : null,
      ]);
      $shelfParts = array_filter([
        !empty($extendedData['aisle']) ? 'Aisle ' . $extendedData['aisle'] : null,
        !empty($extendedData['bay']) ? 'Bay ' . $extendedData['bay'] : null,
        !empty($extendedData['rack']) ? 'Rack ' . $extendedData['rack'] : null,
        !empty($extendedData['shelf']) ? 'Shelf ' . $extendedData['shelf'] : null,
        !empty($extendedData['position']) ? 'Pos ' . $extendedData['position'] : null,
      ]);
    ?>
    <?php if (!empty($locationParts) || !empty($shelfParts) || !empty($extendedData['barcode']) || !empty($extendedData['reference_code'])): ?>
    <div class="card mb-4">
      <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo __('Location Details'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($locationParts)): ?>
        <p class="mb-2">
          <i class="fas fa-building me-2 text-muted"></i>
          <strong><?php echo implode(' &gt; ', $locationParts); ?></strong>
        </p>
        <?php endif; ?>

        <?php if (!empty($shelfParts)): ?>
        <p class="mb-2">
          <i class="fas fa-th me-2 text-primary"></i>
          <strong><?php echo implode(' &gt; ', $shelfParts); ?></strong>
        </p>
        <?php endif; ?>

        <dl class="row mb-0 mt-3">
          <?php if (!empty($extendedData['building'])): ?>
          <dt class="col-sm-4"><?php echo __('Building'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['building']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['floor'])): ?>
          <dt class="col-sm-4"><?php echo __('Floor'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['floor']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['room'])): ?>
          <dt class="col-sm-4"><?php echo __('Room'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['room']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['aisle'])): ?>
          <dt class="col-sm-4"><?php echo __('Aisle'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['aisle']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['bay'])): ?>
          <dt class="col-sm-4"><?php echo __('Bay'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['bay']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['rack'])): ?>
          <dt class="col-sm-4"><?php echo __('Rack'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['rack']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['shelf'])): ?>
          <dt class="col-sm-4"><?php echo __('Shelf'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['shelf']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['position'])): ?>
          <dt class="col-sm-4"><?php echo __('Position'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['position']); ?></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['barcode'])): ?>
          <dt class="col-sm-4"><?php echo __('Barcode'); ?></dt>
          <dd class="col-sm-8"><code><?php echo esc_entities($extendedData['barcode']); ?></code></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['reference_code'])): ?>
          <dt class="col-sm-4"><?php echo __('Reference Code'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['reference_code']); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
    <?php endif; ?>

    <!-- Dimensions -->
    <?php if (!empty($extendedData['width']) || !empty($extendedData['height']) || !empty($extendedData['depth'])): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-ruler-combined me-2"></i><?php echo __('Dimensions'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if (!empty($extendedData['width'])): ?>
          <dt class="col-sm-4"><?php echo __('Width'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['width']); ?> cm</dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['height'])): ?>
          <dt class="col-sm-4"><?php echo __('Height'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['height']); ?> cm</dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['depth'])): ?>
          <dt class="col-sm-4"><?php echo __('Depth'); ?></dt>
          <dd class="col-sm-8"><?php echo esc_entities($extendedData['depth']); ?> cm</dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
    <?php endif; ?>

    <!-- Capacity -->
    <?php if (!empty($extendedData['total_capacity']) || !empty($extendedData['total_linear_metres'])): ?>
    <div class="card mb-4">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i><?php echo __('Capacity'); ?></h5>
      </div>
      <div class="card-body">
        <?php if (!empty($extendedData['total_capacity'])): ?>
        <?php
          $used = (int)($extendedData['used_capacity'] ?? 0);
          $total = (int)$extendedData['total_capacity'];
          $available = (int)($extendedData['available_capacity'] ?? ($total - $used));
          $percent = $total > 0 ? round(($used / $total) * 100) : 0;
          $barClass = $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success');
        ?>
        <h6><?php echo __('Unit Capacity'); ?></h6>
        <div class="row mb-3">
          <div class="col-md-4 text-center">
            <h3 class="mb-0"><?php echo $total; ?></h3>
            <small class="text-muted"><?php echo __('Total'); ?></small>
          </div>
          <div class="col-md-4 text-center">
            <h3 class="mb-0 text-primary"><?php echo $used; ?></h3>
            <small class="text-muted"><?php echo __('Used'); ?></small>
          </div>
          <div class="col-md-4 text-center">
            <h3 class="mb-0 text-success"><?php echo $available; ?></h3>
            <small class="text-muted"><?php echo __('Available'); ?></small>
          </div>
        </div>
        <div class="progress mb-2" style="height: 25px;">
          <div class="progress-bar <?php echo $barClass; ?>" role="progressbar" 
               style="width: <?php echo $percent; ?>%;">
            <?php echo $percent; ?>% <?php echo __('used'); ?>
          </div>
        </div>
        <p class="text-muted mb-0">
          <?php echo __('Unit'); ?>: <?php echo esc_entities($extendedData['capacity_unit'] ?? 'items'); ?>
        </p>
        <?php endif; ?>

        <?php if (!empty($extendedData['total_linear_metres'])): ?>
        <hr>
        <?php
          $usedLm = (float)($extendedData['used_linear_metres'] ?? 0);
          $totalLm = (float)$extendedData['total_linear_metres'];
          $availableLm = (float)($extendedData['available_linear_metres'] ?? ($totalLm - $usedLm));
          $percentLm = $totalLm > 0 ? round(($usedLm / $totalLm) * 100) : 0;
          $barClassLm = $percentLm >= 90 ? 'bg-danger' : ($percentLm >= 70 ? 'bg-warning' : 'bg-success');
        ?>
        <h6><?php echo __('Linear Metres'); ?></h6>
        <div class="row mb-3">
          <div class="col-md-4 text-center">
            <h3 class="mb-0"><?php echo number_format($totalLm, 2); ?></h3>
            <small class="text-muted"><?php echo __('Total'); ?></small>
          </div>
          <div class="col-md-4 text-center">
            <h3 class="mb-0 text-primary"><?php echo number_format($usedLm, 2); ?></h3>
            <small class="text-muted"><?php echo __('Used'); ?></small>
          </div>
          <div class="col-md-4 text-center">
            <h3 class="mb-0 text-success"><?php echo number_format($availableLm, 2); ?></h3>
            <small class="text-muted"><?php echo __('Available'); ?></small>
          </div>
        </div>
        <div class="progress" style="height: 25px;">
          <div class="progress-bar <?php echo $barClassLm; ?>" role="progressbar" 
               style="width: <?php echo $percentLm; ?>%;">
            <?php echo $percentLm; ?>% <?php echo __('used'); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Environmental & Security -->
    <?php if (!empty($extendedData['climate_controlled']) || !empty($extendedData['security_level']) || !empty($extendedData['temperature_min'])): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i><?php echo __('Environmental & Security'); ?></h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <?php if (!empty($extendedData['climate_controlled'])): ?>
          <dt class="col-sm-4"><?php echo __('Climate Controlled'); ?></dt>
          <dd class="col-sm-8"><span class="badge bg-info"><?php echo __('Yes'); ?></span></dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['temperature_min']) || !empty($extendedData['temperature_max'])): ?>
          <dt class="col-sm-4"><?php echo __('Temperature Range'); ?></dt>
          <dd class="col-sm-8">
            <?php echo esc_entities($extendedData['temperature_min'] ?? '?'); ?>°C - <?php echo esc_entities($extendedData['temperature_max'] ?? '?'); ?>°C
          </dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['humidity_min']) || !empty($extendedData['humidity_max'])): ?>
          <dt class="col-sm-4"><?php echo __('Humidity Range'); ?></dt>
          <dd class="col-sm-8">
            <?php echo esc_entities($extendedData['humidity_min'] ?? '?'); ?>% - <?php echo esc_entities($extendedData['humidity_max'] ?? '?'); ?>%
          </dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['security_level'])): ?>
          <dt class="col-sm-4"><?php echo __('Security Level'); ?></dt>
          <dd class="col-sm-8">
            <span class="badge bg-danger"><?php echo ucfirst(esc_entities($extendedData['security_level'])); ?></span>
          </dd>
          <?php endif; ?>

          <?php if (!empty($extendedData['access_restrictions'])): ?>
          <dt class="col-sm-4"><?php echo __('Access Restrictions'); ?></dt>
          <dd class="col-sm-8"><?php echo nl2br(esc_entities($extendedData['access_restrictions'])); ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
    <?php endif; ?>

    <!-- Notes -->
    <?php if (!empty($extendedData['notes'])): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i><?php echo __('Notes'); ?></h5>
      </div>
      <div class="card-body">
        <?php echo nl2br(esc_entities($extendedData['notes'])); ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Related Resources (using Framework) -->
    <?php if (!empty($relatedResources)): ?>
    <div class="card mb-4">
      <div class="card-header bg-warning">
        <h5 class="mb-0"><i class="fas fa-link me-2"></i><?php echo __('Related Resources'); ?></h5>
      </div>
      <div class="card-body">
        <ul class="list-unstyled mb-0">
          <?php foreach ($relatedResources as $related): ?>
          <li class="mb-2">
            <a href="<?php echo url_for(['module' => 'informationobject', 'slug' => $related->slug]); ?>">
              <i class="fas fa-file me-1"></i>
              <?php echo esc_entities($related->title ?? $related->slug); ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="col-md-4">

    <!-- Status -->
    <?php if (!empty($extendedData['status'])): ?>
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i><?php echo __('Status'); ?></h5>
      </div>
      <div class="card-body text-center">
        <?php
          $statusBadge = match($extendedData['status']) {
            'active' => 'bg-success',
            'full' => 'bg-danger',
            'maintenance' => 'bg-warning',
            'decommissioned' => 'bg-secondary',
            default => 'bg-primary'
          };
        ?>
        <span class="badge <?php echo $statusBadge; ?> fs-5 p-2">
          <?php echo ucfirst(esc_entities($extendedData['status'])); ?>
        </span>
      </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="card mb-4">
      <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-cog me-2"></i><?php echo __('Actions'); ?></h5>
      </div>
      <div class="card-body">
        <a href="<?php echo url_for([$resource, 'module' => 'physicalobject', 'action' => 'edit']); ?>" class="btn btn-success w-100 mb-2">
          <i class="fas fa-edit me-1"></i><?php echo __('Edit'); ?>
        </a>
        <a href="<?php echo url_for(['module' => 'physicalobject', 'action' => 'browse']); ?>" class="btn btn-outline-secondary w-100 mb-2">
          <i class="fas fa-list me-1"></i><?php echo __('Browse storage locations'); ?>
        </a>
        <a href="<?php echo url_for([$resource, 'module' => 'physicalobject', 'action' => 'delete']); ?>" class="btn btn-danger w-100">
          <i class="fas fa-trash me-1"></i><?php echo __('Delete'); ?>
        </a>
      </div>
    </div>

  </div>
</div>

<?php end_slot(); ?>
