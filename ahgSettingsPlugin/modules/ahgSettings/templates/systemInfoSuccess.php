<?php decorate_with('layout_2col.php'); ?>

<?php slot('sidebar'); ?>
  <?php echo get_component('ahgSettings', 'menu'); ?>
<?php end_slot(); ?>

<?php slot('title'); ?>
  <h1>
    <i class="bi bi-pc-display-horizontal me-2"></i>
    <?php echo __('System Information'); ?>
  </h1>
<?php end_slot(); ?>

<?php slot('content'); ?>

<?php
$softwareCategories = $sf_data->getRaw('softwareCategories');
$systemInfo = $sf_data->getRaw('systemInfo');
$phpExtensions = $sf_data->getRaw('phpExtensions');
$diskUsage = $sf_data->getRaw('diskUsage');
$atomRoot = $sf_data->getRaw('atomRoot');
$exportFormats = $sf_data->getRaw('exportFormats');
$doiStats = $sf_data->getRaw('doiStats');
?>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.software-card {
  border-left: 4px solid #0d6efd;
  transition: all 0.2s ease;
}
.software-card:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.software-version {
  font-family: monospace;
  font-weight: bold;
}
.status-ok { color: #198754; }
.status-warning { color: #fd7e14; }
.status-error { color: #dc3545; }
.info-label {
  font-weight: 600;
  color: #6c757d;
  min-width: 180px;
}
.progress-thin {
  height: 8px;
}
</style>

<!-- System Overview -->
<div class="card mb-4">
  <div class="card-header bg-primary text-white">
    <i class="bi bi-info-circle me-2"></i>
    <?php echo __('System Overview'); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="info-label">Hostname</td>
            <td><code><?php echo htmlspecialchars($systemInfo['hostname']); ?></code></td>
          </tr>
          <tr>
            <td class="info-label">Operating System</td>
            <td><?php echo htmlspecialchars($systemInfo['os']); ?></td>
          </tr>
          <tr>
            <td class="info-label">Architecture</td>
            <td><?php echo htmlspecialchars($systemInfo['architecture']); ?></td>
          </tr>
          <tr>
            <td class="info-label">Server Time</td>
            <td><?php echo htmlspecialchars($systemInfo['server_time']); ?></td>
          </tr>
          <tr>
            <td class="info-label">Uptime</td>
            <td><?php echo htmlspecialchars($systemInfo['uptime']); ?></td>
          </tr>
          <tr>
            <td class="info-label">Load Average</td>
            <td><?php echo htmlspecialchars($systemInfo['load_average']); ?></td>
          </tr>
        </table>
      </div>
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="info-label">PHP SAPI</td>
            <td><?php echo htmlspecialchars($systemInfo['php_sapi']); ?></td>
          </tr>
          <tr>
            <td class="info-label">Memory Limit</td>
            <td><code><?php echo htmlspecialchars($systemInfo['php_memory_limit']); ?></code></td>
          </tr>
          <tr>
            <td class="info-label">Max Execution Time</td>
            <td><?php echo htmlspecialchars($systemInfo['php_max_execution_time']); ?></td>
          </tr>
          <tr>
            <td class="info-label">Upload Max Filesize</td>
            <td><code><?php echo htmlspecialchars($systemInfo['php_upload_max_filesize']); ?></code></td>
          </tr>
          <tr>
            <td class="info-label">Post Max Size</td>
            <td><code><?php echo htmlspecialchars($systemInfo['php_post_max_size']); ?></code></td>
          </tr>
          <tr>
            <td class="info-label">Timezone</td>
            <td><?php echo htmlspecialchars($systemInfo['php_timezone']); ?></td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Disk Usage -->
<?php if (!empty($diskUsage)): ?>
<div class="card mb-4">
  <div class="card-header">
    <i class="bi bi-hdd me-2"></i>
    <?php echo __('Disk Usage'); ?>
  </div>
  <div class="card-body">
    <div class="row">
      <?php foreach ($diskUsage as $disk): ?>
        <div class="col-md-6 mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <strong><?php echo htmlspecialchars($disk['label']); ?></strong>
            <span class="badge bg-<?php echo $disk['percent'] > 90 ? 'danger' : ($disk['percent'] > 75 ? 'warning' : 'success'); ?>">
              <?php echo $disk['percent']; ?>% used
            </span>
          </div>
          <div class="progress progress-thin mb-1">
            <div class="progress-bar bg-<?php echo $disk['percent'] > 90 ? 'danger' : ($disk['percent'] > 75 ? 'warning' : 'success'); ?>"
                 style="width: <?php echo $disk['percent']; ?>%"></div>
          </div>
          <small class="text-muted">
            <?php echo $disk['used']; ?> used of <?php echo $disk['total']; ?> (<?php echo $disk['free']; ?> free)
          </small>
          <br><small class="text-muted text-truncate d-block" title="<?php echo htmlspecialchars($disk['path']); ?>">
            <?php echo htmlspecialchars($disk['path']); ?>
          </small>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Software Versions by Category -->
<?php foreach ($softwareCategories as $catKey => $category): ?>
<div class="card mb-4">
  <div class="card-header">
    <i class="<?php echo $category['icon']; ?> me-2"></i>
    <strong><?php echo __($category['title']); ?></strong>
    <span class="badge bg-secondary ms-2"><?php echo count($category['items']); ?></span>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <?php foreach ($category['items'] as $software): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card software-card h-100">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <i class="<?php echo $software['icon']; ?> me-2 status-<?php echo $software['status']; ?>"></i>
                  <strong><?php echo $software['name']; ?></strong>
                </div>
                <?php if ($software['status'] !== 'ok'): ?>
                  <span class="badge bg-<?php echo $software['status'] === 'warning' ? 'warning' : 'danger'; ?>"><?php echo $software['status']; ?></span>
                <?php endif; ?>
              </div>
              <div class="software-version status-<?php echo $software['status']; ?> mt-1">
                <?php echo $software['version']; ?>
              </div>
              <?php if (!empty($software['path'])): ?>
                <small class="text-muted d-block text-truncate" title="<?php echo htmlspecialchars($software['path']); ?>">
                  <?php echo htmlspecialchars($software['path']); ?>
                </small>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Metadata Export Formats -->
<?php if (!empty($exportFormats['formats'])): ?>
<div class="card mb-4">
  <div class="card-header bg-info text-white">
    <i class="bi bi-file-earmark-code me-2"></i>
    <strong><?php echo __('GLAM Metadata Export Formats'); ?></strong>
    <span class="badge bg-light text-dark ms-2"><?php echo count($exportFormats['formats']); ?> formats</span>
    <?php if (!$exportFormats['pluginEnabled']): ?>
      <span class="badge bg-warning ms-2">Plugin not enabled</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      Export archival descriptions to international GLAM standards. Use CLI command:
      <code><?php echo $exportFormats['command']; ?></code>
    </p>
    <div class="row g-3">
      <?php foreach ($exportFormats['formats'] as $format): ?>
        <div class="col-md-4 col-lg-3">
          <div class="card software-card h-100" style="border-left-color: <?php echo $format['status'] === 'ok' ? '#198754' : '#fd7e14'; ?>;">
            <div class="card-body py-2 px-3">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <i class="<?php echo $format['icon']; ?> me-2 status-<?php echo $format['status']; ?>"></i>
                  <strong><?php echo $format['name']; ?></strong>
                </div>
                <span class="badge bg-secondary"><?php echo $format['output']; ?></span>
              </div>
              <div class="mt-1">
                <span class="badge bg-light text-dark"><?php echo $format['sector']; ?></span>
                <code class="ms-1 small"><?php echo $format['code']; ?></code>
              </div>
              <small class="text-muted d-block mt-1"><?php echo $format['description']; ?></small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card-footer bg-light">
    <small class="text-muted">
      <i class="bi bi-terminal me-1"></i>
      <strong>CLI:</strong>
      <code>php symfony metadata:export --format=ead3 --slug=my-record</code> |
      <code>php symfony metadata:export --format=all --repository=5</code>
    </small>
  </div>
</div>
<?php endif; ?>

<!-- DOI Integration Status -->
<?php if (!empty($doiStats)): ?>
<div class="card mb-4">
  <div class="card-header bg-success text-white">
    <i class="bi bi-link-45deg me-2"></i>
    <strong><?php echo __('DOI Integration (DataCite)'); ?></strong>
    <?php if ($doiStats['enabled']): ?>
      <span class="badge bg-light text-success ms-2">Enabled</span>
    <?php else: ?>
      <span class="badge bg-warning text-dark ms-2">Not Configured</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if ($doiStats['enabled']): ?>
      <div class="row">
        <div class="col-md-6">
          <h6><i class="bi bi-bar-chart me-1"></i> DOI Statistics</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td class="text-muted" style="width: 150px;">Total DOIs</td>
              <td><strong><?php echo number_format($doiStats['total']); ?></strong></td>
            </tr>
            <tr>
              <td class="text-muted">Findable</td>
              <td>
                <span class="badge bg-success"><?php echo number_format($doiStats['by_status']['findable']); ?></span>
                <small class="text-muted ms-1">publicly discoverable</small>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Registered</td>
              <td>
                <span class="badge bg-primary"><?php echo number_format($doiStats['by_status']['registered']); ?></span>
                <small class="text-muted ms-1">reserved but not public</small>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Draft</td>
              <td>
                <span class="badge bg-secondary"><?php echo number_format($doiStats['by_status']['draft']); ?></span>
              </td>
            </tr>
            <?php if ($doiStats['by_status']['failed'] > 0): ?>
            <tr>
              <td class="text-muted">Failed</td>
              <td>
                <span class="badge bg-danger"><?php echo number_format($doiStats['by_status']['failed']); ?></span>
              </td>
            </tr>
            <?php endif; ?>
            <?php if ($doiStats['queue_pending'] > 0): ?>
            <tr>
              <td class="text-muted">Queue Pending</td>
              <td>
                <span class="badge bg-warning text-dark"><?php echo number_format($doiStats['queue_pending']); ?></span>
              </td>
            </tr>
            <?php endif; ?>
          </table>
        </div>
        <div class="col-md-6">
          <?php if ($doiStats['config']): ?>
            <h6><i class="bi bi-gear me-1"></i> Configuration</h6>
            <table class="table table-sm table-borderless mb-0">
              <tr>
                <td class="text-muted" style="width: 150px;">DOI Prefix</td>
                <td><code><?php echo htmlspecialchars($doiStats['config']['prefix']); ?></code></td>
              </tr>
              <tr>
                <td class="text-muted">Environment</td>
                <td>
                  <span class="badge bg-<?php echo $doiStats['config']['environment'] === 'production' ? 'success' : 'warning'; ?>">
                    <?php echo htmlspecialchars($doiStats['config']['environment']); ?>
                  </span>
                </td>
              </tr>
              <tr>
                <td class="text-muted">Auto-mint</td>
                <td>
                  <?php if ($doiStats['config']['auto_mint']): ?>
                    <i class="bi bi-check-circle-fill text-success"></i> Enabled
                  <?php else: ?>
                    <i class="bi bi-x-circle text-muted"></i> Disabled
                  <?php endif; ?>
                </td>
              </tr>
            </table>
          <?php else: ?>
            <div class="alert alert-warning mb-0">
              <i class="bi bi-exclamation-triangle me-1"></i>
              DataCite not configured. Go to <strong>Admin > DOI Settings</strong> to set up.
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php else: ?>
      <p class="text-muted mb-2">
        <i class="bi bi-info-circle me-1"></i>
        DOI integration allows you to mint persistent identifiers for your records via DataCite.
      </p>
      <p class="mb-0">
        <strong>To enable:</strong> Install and enable the ahgDoiPlugin, then configure your DataCite credentials.
      </p>
    <?php endif; ?>
  </div>
  <div class="card-footer bg-light">
    <small class="text-muted">
      <i class="bi bi-terminal me-1"></i>
      <strong>CLI:</strong>
      <code>php symfony doi:mint --slug=my-record</code> |
      <code>php symfony doi:process-queue --limit=10</code>
    </small>
  </div>
</div>
<?php endif; ?>

<!-- PHP Extensions -->
<div class="card mb-4">
  <div class="card-header">
    <i class="bi bi-plug me-2"></i>
    <?php echo __('PHP Extensions'); ?>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>Extension</th>
          <th>Description</th>
          <th class="text-center">Status</th>
          <th>Version</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($phpExtensions as $ext): ?>
          <tr>
            <td><code><?php echo htmlspecialchars($ext['name']); ?></code></td>
            <td class="text-muted small"><?php echo htmlspecialchars($ext['description']); ?></td>
            <td class="text-center">
              <?php if ($ext['loaded']): ?>
                <i class="bi bi-check-circle-fill text-success"></i>
              <?php else: ?>
                <i class="bi bi-x-circle-fill text-<?php echo strpos($ext['description'], 'optional') !== false ? 'warning' : 'danger'; ?>"></i>
              <?php endif; ?>
            </td>
            <td><small class="text-muted"><?php echo htmlspecialchars($ext['version']); ?></small></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- AtoM Root Path -->
<div class="card mb-4">
  <div class="card-body py-2">
    <strong>AtoM Root:</strong> <code><?php echo htmlspecialchars($atomRoot); ?></code>
  </div>
</div>

<?php end_slot(); ?>
