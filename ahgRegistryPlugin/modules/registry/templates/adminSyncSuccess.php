<?php decorate_with(sfConfig::get('sf_plugins_dir').'/ahgRegistryPlugin/modules/registry/templates/layout_registry'); ?>

<?php slot('title'); ?><?php echo __('Sync Dashboard'); ?><?php end_slot(); ?>

<?php slot('content'); ?>

<?php include_partial('registry/breadcrumb', ['items' => [
  ['label' => __('Home'), 'url' => url_for('@homepage')],
  ['label' => __('Registry'), 'url' => url_for(['module' => 'registry', 'action' => 'index'])],
  ['label' => __('Admin'), 'url' => url_for(['module' => 'registry', 'action' => 'adminDashboard'])],
  ['label' => __('Sync Dashboard')],
]]); ?>

<h1 class="h3 mb-4"><?php echo __('Sync Dashboard'); ?></h1>

<!-- Active Instances -->
<div class="card mb-4">
  <div class="card-header fw-semibold">
    <i class="fas fa-server me-2"></i><?php echo __('Active Instances'); ?>
    <?php if (!empty($instances)): ?>
      <span class="badge bg-secondary ms-2"><?php echo count($instances); ?></span>
    <?php endif; ?>
  </div>
  <?php if (!empty($instances) && count($instances) > 0): ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Instance'); ?></th>
          <th><?php echo __('Institution'); ?></th>
          <th><?php echo __('URL'); ?></th>
          <th><?php echo __('Version'); ?></th>
          <th><?php echo __('Last Heartbeat'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($instances as $inst): ?>
        <tr>
          <td class="fw-semibold"><?php echo htmlspecialchars($inst->name ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <small><?php echo htmlspecialchars($inst->institution_name ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td>
            <?php if (!empty($inst->url)): ?>
              <a href="<?php echo htmlspecialchars($inst->url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="small">
                <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $inst->url), ENT_QUOTES, 'UTF-8'); ?>
                <i class="fas fa-external-link-alt ms-1"></i>
              </a>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($inst->software_version)): ?>
              <span class="badge bg-secondary"><?php echo htmlspecialchars($inst->software_version, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($inst->last_heartbeat_at)): ?>
              <?php
                $hbTime = strtotime($inst->last_heartbeat_at);
                $diff = time() - $hbTime;
                if ($diff < 60) {
                    $ago = __('just now');
                } elseif ($diff < 3600) {
                    $ago = sprintf(__('%d min ago'), (int) floor($diff / 60));
                } elseif ($diff < 86400) {
                    $ago = sprintf(__('%d hours ago'), (int) floor($diff / 3600));
                } else {
                    $ago = sprintf(__('%d days ago'), (int) floor($diff / 86400));
                }
              ?>
              <small class="text-muted" title="<?php echo date('Y-m-d H:i:s', $hbTime); ?>"><?php echo $ago; ?></small>
            <?php else: ?>
              <small class="text-muted"><?php echo __('Never'); ?></small>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php
              $status = $inst->status ?? 'offline';
              $statusMap = [
                'online' => ['bg-success', __('Online')],
                'offline' => ['bg-danger', __('Offline')],
                'maintenance' => ['bg-warning text-dark', __('Maintenance')],
                'decommissioned' => ['bg-secondary', __('Decommissioned')],
              ];
              $s = $statusMap[$status] ?? ['bg-secondary', ucfirst($status)];
            ?>
            <span class="badge <?php echo $s[0]; ?>"><?php echo $s[1]; ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-center text-muted py-4">
    <i class="fas fa-server fa-2x mb-2"></i>
    <p class="mb-0"><?php echo __('No sync-enabled instances found.'); ?></p>
  </div>
  <?php endif; ?>
</div>

<!-- Recent Sync Logs -->
<div class="card">
  <div class="card-header fw-semibold">
    <i class="fas fa-history me-2"></i><?php echo __('Recent Sync Logs'); ?>
    <?php if (!empty($recentLogs)): ?>
      <span class="badge bg-secondary ms-2"><?php echo count($recentLogs); ?></span>
    <?php endif; ?>
  </div>
  <?php if (!empty($recentLogs) && count($recentLogs) > 0): ?>
  <div class="table-responsive">
    <table class="table table-hover table-striped align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Instance'); ?></th>
          <th><?php echo __('Event Type'); ?></th>
          <th class="text-center"><?php echo __('Status'); ?></th>
          <th><?php echo __('IP'); ?></th>
          <th><?php echo __('Timestamp'); ?></th>
          <th><?php echo __('Error'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recentLogs as $log): ?>
        <tr>
          <td>
            <small class="fw-semibold"><?php echo htmlspecialchars($log->instance_name ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td>
            <?php
              $eventTypeBg = [
                'heartbeat' => 'bg-info text-dark',
                'register' => 'bg-primary',
                'update' => 'bg-success',
                'error' => 'bg-danger',
                'deregister' => 'bg-warning text-dark',
              ];
              $et = $log->event_type ?? '';
              $etClass = $eventTypeBg[$et] ?? 'bg-secondary';
            ?>
            <span class="badge <?php echo $etClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $et)), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td class="text-center">
            <?php
              $logStatus = $log->status ?? '';
              $logStatusMap = [
                'success' => 'bg-success',
                'error' => 'bg-danger',
                'warning' => 'bg-warning text-dark',
              ];
              $lsClass = $logStatusMap[$logStatus] ?? 'bg-secondary';
            ?>
            <span class="badge <?php echo $lsClass; ?>"><?php echo htmlspecialchars(ucfirst($logStatus), ENT_QUOTES, 'UTF-8'); ?></span>
          </td>
          <td>
            <small class="text-muted font-monospace"><?php echo htmlspecialchars($log->ip_address ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
          </td>
          <td>
            <small class="text-muted"><?php echo !empty($log->created_at) ? date('Y-m-d H:i:s', strtotime($log->created_at)) : '-'; ?></small>
          </td>
          <td>
            <?php if (!empty($log->error_message)): ?>
              <small class="text-danger"><?php echo htmlspecialchars($log->error_message, ENT_QUOTES, 'UTF-8'); ?></small>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body text-center text-muted py-4">
    <i class="fas fa-history fa-2x mb-2"></i>
    <p class="mb-0"><?php echo __('No sync logs recorded yet.'); ?></p>
  </div>
  <?php endif; ?>
</div>

<?php end_slot(); ?>
