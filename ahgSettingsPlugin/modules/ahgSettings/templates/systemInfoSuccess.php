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
?>

<style>
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
