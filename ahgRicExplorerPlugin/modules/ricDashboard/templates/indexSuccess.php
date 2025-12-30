<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <div class="float-end">
    <a href="/ric/" target="_blank" class="btn btn-outline-info btn-sm">
      <i class="fa fa-external-link-alt"></i> <?php echo __('RIC Explorer'); ?>
    </a>
  </div>
  <h1><i class="fa fa-project-diagram"></i> <?php echo __('RIC Sync Dashboard'); ?></h1>
<?php end_slot(); ?>

<?php slot('head'); ?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<?php end_slot(); ?>

<?php slot('content'); ?>

<!-- Status Cards Row -->
<div class="row g-3 mb-4">
  <!-- Fuseki Status -->
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle text-muted mb-1"><?php echo __('Fuseki Status'); ?></h6>
            <h3 class="mb-0 <?php echo $fusekiStatus['online'] ? 'text-success' : 'text-danger'; ?>">
              <?php echo $fusekiStatus['online'] ? __('Online') : __('Offline'); ?>
            </h3>
          </div>
          <div class="fs-1 <?php echo $fusekiStatus['online'] ? 'text-success' : 'text-danger'; ?>">
            <i class="fa fa-<?php echo $fusekiStatus['online'] ? 'check-circle' : 'times-circle'; ?>"></i>
          </div>
        </div>
        <?php if ($fusekiStatus['online']): ?>
          <p class="mb-0 mt-2 text-muted small">
            <i class="fa fa-database"></i> <?php echo number_format($fusekiStatus['triple_count']); ?> <?php echo __('triples'); ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Queue Status -->
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle text-muted mb-1"><?php echo __('Queue'); ?></h6>
            <h3 class="mb-0" id="queue-count"><?php echo number_format($queueStatus['queued'] ?? 0); ?></h3>
          </div>
          <div class="fs-1 text-primary"><i class="fa fa-list-ol"></i></div>
        </div>
        <p class="mb-0 mt-2">
          <?php if (($queueStatus['processing'] ?? 0) > 0): ?>
            <span class="badge bg-warning"><?php echo ($queueStatus['processing'] ?? 0); ?> <?php echo __('processing'); ?></span>
          <?php endif; ?>
          <?php if (($queueStatus['failed'] ?? 0) > 0): ?>
            <span class="badge bg-danger"><?php echo ($queueStatus['failed'] ?? 0); ?> <?php echo __('failed'); ?></span>
          <?php endif; ?>
        </p>
      </div>
      <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'queue']); ?>" class="card-footer text-decoration-none text-center">
        <?php echo __('Manage queue'); ?> <i class="fa fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- Orphaned Triples -->
  <div class="col-md-3">
    <div class="card h-100 <?php echo $orphanCount > 0 ? 'border-warning' : ''; ?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle text-muted mb-1"><?php echo __('Orphaned Triples'); ?></h6>
            <h3 class="mb-0 <?php echo $orphanCount > 0 ? 'text-warning' : 'text-success'; ?>" id="orphan-count">
              <?php echo number_format($orphanCount); ?>
            </h3>
          </div>
          <div class="fs-1 <?php echo $orphanCount > 0 ? 'text-warning' : 'text-success'; ?>">
            <i class="fa fa-unlink"></i>
          </div>
        </div>
      </div>
      <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'orphans']); ?>" class="card-footer text-decoration-none text-center">
        <?php echo __('Manage orphans'); ?> <i class="fa fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- Sync Enabled -->
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle text-muted mb-1"><?php echo __('Sync Status'); ?></h6>
            <h3 class="mb-0 <?php echo ($configSettings['sync_enabled'] ?? '1') === '1' ? 'text-success' : 'text-secondary'; ?>">
              <?php echo ($configSettings['sync_enabled'] ?? '1') === '1' ? __('Active') : __('Disabled'); ?>
            </h3>
          </div>
          <div class="fs-1 <?php echo ($configSettings['sync_enabled'] ?? '1') === '1' ? 'text-success' : 'text-secondary'; ?>">
            <i class="fa fa-sync"></i>
          </div>
        </div>
      </div>
      <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'config']); ?>" class="card-footer text-decoration-none text-center">
        <?php echo __('Configuration'); ?> <i class="fa fa-cog"></i>
      </a>
    </div>
  </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Sync Trend (7 Days)'); ?></h5></div>
      <div class="card-body"><canvas id="syncTrendChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Operations by Type'); ?></h5></div>
      <div class="card-body"><canvas id="operationsChart" height="200"></canvas></div>
    </div>
  </div>
</div>

<!-- Entity Status & Recent Operations -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Entity Sync Status'); ?></h5></div>
      <div class="card-body">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th><?php echo __('Entity Type'); ?></th>
              <th class="text-center"><?php echo __('Synced'); ?></th>
              <th class="text-center"><?php echo __('Pending'); ?></th>
              <th class="text-center"><?php echo __('Failed'); ?></th>
              <th class="text-center"><?php echo __('Total'); ?></th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($syncSummary as $entityType => $statuses): ?>
              <?php
                $synced = 0; $pending = 0; $failed = 0;
                foreach ($statuses as $s) {
                  if ($s->sync_status === 'synced') $synced = $s->count;
                  elseif ($s->sync_status === 'pending') $pending = $s->count;
                  elseif ($s->sync_status === 'failed') $failed = $s->count;
                }
                $total = $synced + $pending + $failed;
              ?>
              <tr>
                <td><code><?php echo $entityType; ?></code></td>
                <td class="text-center"><span class="badge bg-success"><?php echo number_format($synced); ?></span></td>
                <td class="text-center"><span class="badge bg-warning text-dark"><?php echo number_format($pending); ?></span></td>
                <td class="text-center"><span class="badge bg-danger"><?php echo number_format($failed); ?></span></td>
                <td class="text-center"><?php echo number_format($total); ?></td>
                <td class="text-end">
                  <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'syncStatus', 'entity_type' => $entityType]); ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo __('Quick Actions'); ?></h5>
      </div>
      <div class="card-body">
        <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="runIntegrityCheck()">
          <i class="fa fa-check-circle"></i> <?php echo __('Run Integrity Check'); ?>
        </button>
        <button type="button" class="btn btn-outline-warning w-100 mb-2" onclick="previewCleanup()">
          <i class="fa fa-search"></i> <?php echo __('Preview Cleanup'); ?>
        </button>
        <button type="button" class="btn btn-outline-danger w-100 mb-2" onclick="executeCleanup()" id="cleanup-btn" disabled>
          <i class="fa fa-trash"></i> <?php echo __('Execute Cleanup'); ?>
        </button>
        <hr>
        <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'logs']); ?>" class="btn btn-outline-secondary w-100">
          <i class="fa fa-list"></i> <?php echo __('View Logs'); ?>
        </a>
      </div>
    </div>

    <?php include_partial("ricDashboard/externalLinks"); ?>

    <!-- Integrity Results -->
    <div class="card mt-3" id="integrity-results" style="display: none;">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Integrity Check Results'); ?></h5></div>
      <div class="card-body" id="integrity-content"></div>
    </div>
  </div>
</div>

<!-- Recent Operations -->
<div class="card">
  <div class="card-header"><h5 class="mb-0"><?php echo __('Recent Operations'); ?></h5></div>
  <div class="card-body">
    <table class="table table-sm table-hover mb-0">
      <thead>
        <tr>
          <th><?php echo __('Time'); ?></th>
          <th><?php echo __('Operation'); ?></th>
          <th><?php echo __('Entity'); ?></th>
          <th><?php echo __('Status'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($sf_data->getRaw('recentOperations'), 0, 10) as $op): ?>
          <tr>
            <td class="text-muted small"><?php echo date('H:i:s', strtotime($op->created_at)); ?></td>
            <td><span class="badge bg-secondary"><?php echo $op->operation; ?></span></td>
            <td><code><?php echo $op->entity_type; ?>/<?php echo $op->entity_id; ?></code></td>
            <td>
              <span class="badge bg-<?php echo $op->status === 'success' ? 'success' : ($op->status === 'failure' ? 'danger' : 'warning'); ?>">
                <?php echo $op->status; ?>
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
const syncTrendData = <?php echo json_encode($syncTrend); ?>;
const operationsData = <?php echo json_encode($operationsByType); ?>;

new Chart(document.getElementById('syncTrendChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: syncTrendData.map(d => d.date),
    datasets: [
      { label: 'Success', data: syncTrendData.map(d => d.success), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.3 },
      { label: 'Failure', data: syncTrendData.map(d => d.failure), borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', fill: true, tension: 0.3 }
    ]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('operationsChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: Object.keys(operationsData),
    datasets: [{ data: Object.values(operationsData), backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d', '#0dcaf0'] }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});

setInterval(refreshStats, 30000);

function refreshStats() {
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxStats']); ?>')
    .then(r => r.json())
    .then(data => {
      document.getElementById('queue-count').textContent = data.queue_status.queued || 0;
      document.getElementById('orphan-count').textContent = data.orphan_count || 0;
    });
}

function runIntegrityCheck() {
  const btn = event.target;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Running...';
  
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxIntegrityCheck']); ?>', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-check-circle"></i> Run Integrity Check';
      if (data.success) {
        showIntegrityResults(data.report);
        document.getElementById('cleanup-btn').disabled = data.report.summary.orphaned_count === 0;
      } else {
        alert('Error: ' + data.error);
      }
    });
}

function showIntegrityResults(report) {
  document.getElementById('integrity-results').style.display = 'block';
  document.getElementById('integrity-content').innerHTML = `
    <div class="mb-2"><strong>Orphaned Triples:</strong> <span class="badge ${report.summary.orphaned_count > 0 ? 'bg-warning' : 'bg-success'}">${report.summary.orphaned_count}</span></div>
    <div class="mb-2"><strong>Missing Records:</strong> <span class="badge ${report.summary.missing_count > 0 ? 'bg-warning' : 'bg-success'}">${report.summary.missing_count}</span></div>
    <div class="mb-2"><strong>Inconsistencies:</strong> <span class="badge ${report.summary.inconsistency_count > 0 ? 'bg-warning' : 'bg-success'}">${report.summary.inconsistency_count}</span></div>
    <small class="text-muted">Checked: ${report.checked_at}</small>
  `;
}

function previewCleanup() {
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxCleanupOrphans']); ?>?dry_run=1', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('Preview: ' + data.stats.orphans_found + ' orphans would be removed');
        document.getElementById('cleanup-btn').disabled = data.stats.orphans_found === 0;
      }
    });
}

function executeCleanup() {
  if (!confirm('Delete all orphaned triples? This cannot be undone.')) return;
  
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxCleanupOrphans']); ?>', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alert('Cleanup complete. Removed ' + data.stats.triples_removed + ' triples.');
        location.reload();
      }
    });
}
</script>
<?php end_slot(); ?>
