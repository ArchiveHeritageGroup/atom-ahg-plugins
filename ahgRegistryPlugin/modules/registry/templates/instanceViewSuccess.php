<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php $inst = $instance; ?>
<?php $rawDesc = sfOutputEscaper::unescape($inst->description); ?>

<?php slot('title'); ?><?php echo htmlspecialchars(sfOutputEscaper::unescape($inst->name), ENT_QUOTES, 'UTF-8'); ?> - <?php echo __('Instance'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php
  $instName = sfOutputEscaper::unescape($inst->name);
  $instInst = sfOutputEscaper::unescape($institution);
  $instInstitutionName = $instInst ? sfOutputEscaper::unescape($instInst->name) : '';
  $instInstitutionSlug = $instInst ? sfOutputEscaper::unescape($instInst->slug) : '';
?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Institutions'), 'url' => url_for(['module' => 'registry', 'action' => 'institutionBrowse'])],
  ['label' => htmlspecialchars($instInstitutionName, ENT_QUOTES, 'UTF-8'), 'url' => '/registry/institutions/' . htmlspecialchars($instInstitutionSlug, ENT_QUOTES, 'UTF-8')],
  ['label' => htmlspecialchars($instName, ENT_QUOTES, 'UTF-8')],
]]); ?>

<?php
  $statusColors = [
    'online' => 'success',
    'offline' => 'danger',
    'maintenance' => 'warning',
    'decommissioned' => 'secondary',
  ];
  $sColor = $statusColors[$inst->status ?? 'offline'] ?? 'secondary';
  $statusLabel = ucfirst($inst->status ?? 'offline');
?>

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h1 class="h3 mb-1">
      <span class="d-inline-block rounded-circle bg-<?php echo $sColor; ?> me-2" style="width: 12px; height: 12px;"></span>
      <?php echo htmlspecialchars($instName, ENT_QUOTES, 'UTF-8'); ?>
    </h1>
    <div>
      <?php if ($instInst): ?>
        <a href="/registry/institutions/<?php echo htmlspecialchars($instInstitutionSlug, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
          <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($instInstitutionName, ENT_QUOTES, 'UTF-8'); ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
  <span class="badge bg-<?php echo $sColor; ?> fs-6"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
</div>

<div class="row">
  <!-- Main content -->
  <div class="col-lg-8">

    <!-- Description -->
    <?php if (!empty($rawDesc)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-info-circle me-2 text-primary"></i><?php echo __('About'); ?></div>
      <div class="card-body">
        <?php echo nl2br(htmlspecialchars($rawDesc, ENT_QUOTES, 'UTF-8')); ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Technical Details -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-cogs me-2 text-secondary"></i><?php echo __('Technical Details'); ?></div>
      <div class="card-body">
        <div class="row g-3">
          <?php if (!empty($inst->instance_type)): ?>
          <div class="col-md-4">
            <label class="form-label text-muted small mb-0"><?php echo __('Type'); ?></label>
            <div><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $inst->instance_type)), ENT_QUOTES, 'UTF-8'); ?></span></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($inst->software)): ?>
          <div class="col-md-4">
            <label class="form-label text-muted small mb-0"><?php echo __('Software'); ?></label>
            <div>
              <?php echo htmlspecialchars(sfOutputEscaper::unescape($inst->software), ENT_QUOTES, 'UTF-8'); ?>
              <?php if (!empty($inst->software_version)): ?>
                <span class="badge bg-secondary">v<?php echo htmlspecialchars(sfOutputEscaper::unescape($inst->software_version), ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($inst->os_environment)): ?>
          <div class="col-md-4">
            <label class="form-label text-muted small mb-0"><?php echo __('Environment'); ?></label>
            <div><i class="fas fa-desktop me-1 text-muted"></i><?php echo htmlspecialchars(sfOutputEscaper::unescape($inst->os_environment), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($inst->hosting)): ?>
          <div class="col-md-4">
            <label class="form-label text-muted small mb-0"><?php echo __('Hosting'); ?></label>
            <div><i class="fas fa-cloud me-1 text-muted"></i><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $inst->hosting)), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($inst->descriptive_standard)): ?>
          <div class="col-md-4">
            <label class="form-label text-muted small mb-0"><?php echo __('Descriptive Standard'); ?></label>
            <div><i class="fas fa-book me-1 text-muted"></i><?php echo htmlspecialchars(sfOutputEscaper::unescape($inst->descriptive_standard), ENT_QUOTES, 'UTF-8'); ?></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Statistics -->
    <?php if (!empty($inst->record_count) || !empty($inst->digital_object_count) || !empty($inst->storage_gb)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-chart-bar me-2 text-info"></i><?php echo __('Statistics'); ?></div>
      <div class="card-body">
        <div class="row text-center g-3">
          <?php if (!empty($inst->record_count)): ?>
          <div class="col-md-4">
            <div class="h3 mb-0 text-primary"><?php echo number_format((int) $inst->record_count); ?></div>
            <small class="text-muted"><?php echo __('Records'); ?></small>
          </div>
          <?php endif; ?>
          <?php if (!empty($inst->digital_object_count)): ?>
          <div class="col-md-4">
            <div class="h3 mb-0 text-success"><?php echo number_format((int) $inst->digital_object_count); ?></div>
            <small class="text-muted"><?php echo __('Digital Objects'); ?></small>
          </div>
          <?php endif; ?>
          <?php if (!empty($inst->storage_gb)): ?>
          <div class="col-md-4">
            <div class="h3 mb-0 text-warning"><?php echo number_format((float) $inst->storage_gb, 1); ?> GB</div>
            <small class="text-muted"><?php echo __('Storage'); ?></small>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Features in use -->
    <?php
      $rawInst = sfOutputEscaper::unescape($inst);
      $rawFeatures = $rawInst->feature_usage ?? '';
      $features = is_string($rawFeatures) ? json_decode($rawFeatures, true) : (is_array($rawFeatures) ? $rawFeatures : null);
      $enabledFeatures = [];
      if (is_array($features)) {
        foreach ($features as $key => $val) {
          if (is_array($val) && !empty($val['enabled'])) {
            $enabledFeatures[] = $key;
          } elseif (is_string($val)) {
            $enabledFeatures[] = $val;
          }
        }
      }
    ?>
    <?php if (!empty($enabledFeatures)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-puzzle-piece me-2 text-success"></i><?php echo __('Features in Use'); ?></div>
      <div class="card-body">
        <?php foreach ($enabledFeatures as $feat): ?>
          <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feat)), ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Languages -->
    <?php
      $rawLangs = $rawInst->languages ?? '';
      $languages = is_string($rawLangs) ? json_decode($rawLangs, true) : (is_array($rawLangs) ? $rawLangs : null);
    ?>
    <?php if (!empty($languages) && is_array($languages)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-language me-2 text-primary"></i><?php echo __('Languages'); ?></div>
      <div class="card-body">
        <?php foreach ($languages as $lang): ?>
          <span class="badge bg-info text-dark me-1"><?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">

    <!-- Instance URL -->
    <?php if (!empty($inst->url)): ?>
    <div class="card mb-4 border-primary">
      <div class="card-body text-center">
        <a href="<?php echo htmlspecialchars(sfOutputEscaper::unescape($inst->url), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary" target="_blank" rel="noopener">
          <i class="fas fa-external-link-alt me-1"></i> <?php echo __('Visit Instance'); ?>
        </a>
        <div class="small text-muted mt-1"><?php echo htmlspecialchars(preg_replace('#^https?://#', '', sfOutputEscaper::unescape($inst->url)), ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Vendors -->
    <?php $hv = sfOutputEscaper::unescape($hostingVendor); ?>
    <?php $mv = sfOutputEscaper::unescape($maintenanceVendor); ?>
    <?php if ($hv || $mv): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-handshake me-2 text-success"></i><?php echo __('Service Providers'); ?></div>
      <ul class="list-group list-group-flush">
        <?php if ($hv): ?>
        <li class="list-group-item">
          <small class="text-muted d-block"><?php echo __('Hosting'); ?></small>
          <a href="/registry/vendors/<?php echo htmlspecialchars($hv->slug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($hv->name, ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endif; ?>
        <?php if ($mv): ?>
        <li class="list-group-item">
          <small class="text-muted d-block"><?php echo __('Maintenance'); ?></small>
          <a href="/registry/vendors/<?php echo htmlspecialchars($mv->slug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mv->name, ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- Sync Status -->
    <?php if (!empty($inst->sync_enabled)): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-sync-alt me-2 text-info"></i><?php echo __('Sync Status'); ?></div>
      <div class="card-body">
        <?php if (!empty($inst->last_heartbeat_at)): ?>
          <?php
            $hbTime = strtotime($inst->last_heartbeat_at);
            $diff = time() - $hbTime;
            if ($diff < 3600) { $hbAgo = sprintf('%d min ago', (int) floor($diff / 60)); }
            elseif ($diff < 86400) { $hbAgo = sprintf('%d hours ago', (int) floor($diff / 3600)); }
            else { $hbAgo = sprintf('%d days ago', (int) floor($diff / 86400)); }
          ?>
          <div class="mb-2"><i class="fas fa-heartbeat me-1 text-success"></i> <?php echo __('Last heartbeat:'); ?> <?php echo $hbAgo; ?></div>
        <?php else: ?>
          <div class="text-muted"><i class="fas fa-times-circle me-1"></i> <?php echo __('Never synced'); ?></div>
        <?php endif; ?>
        <?php if (!empty($inst->last_sync_at)): ?>
          <div class="small text-muted"><?php echo __('Last sync:'); ?> <?php echo date('M j, Y H:i', strtotime($inst->last_sync_at)); ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Metadata -->
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-info me-2 text-muted"></i><?php echo __('Details'); ?></div>
      <ul class="list-group list-group-flush small">
        <?php if (!empty($inst->is_public)): ?>
        <li class="list-group-item"><i class="fas fa-globe me-2 text-success"></i><?php echo __('Public'); ?></li>
        <?php else: ?>
        <li class="list-group-item"><i class="fas fa-lock me-2 text-muted"></i><?php echo __('Private'); ?></li>
        <?php endif; ?>
        <li class="list-group-item"><i class="fas fa-calendar me-2 text-muted"></i><?php echo __('Added:'); ?> <?php echo date('M j, Y', strtotime($inst->created_at)); ?></li>
        <?php if (!empty($inst->updated_at)): ?>
        <li class="list-group-item"><i class="fas fa-clock me-2 text-muted"></i><?php echo __('Updated:'); ?> <?php echo date('M j, Y', strtotime($inst->updated_at)); ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Sync log -->
    <?php $rawSyncLogs = sfOutputEscaper::unescape($syncLogs); ?>
    <?php if (!empty($rawSyncLogs) && count($rawSyncLogs) > 0): ?>
    <div class="card mb-4">
      <div class="card-header fw-semibold"><i class="fas fa-history me-2 text-secondary"></i><?php echo __('Recent Sync Activity'); ?></div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($rawSyncLogs as $log): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span>
            <span class="badge bg-<?php echo ($log->status ?? 'success') === 'success' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($log->event_type, ENT_QUOTES, 'UTF-8'); ?></span>
          </span>
          <small class="text-muted"><?php echo date('M j H:i', strtotime($log->created_at)); ?></small>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php end_slot(); ?>
