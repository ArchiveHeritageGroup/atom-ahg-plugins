<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <div class="float-end">
    <a href="/ric/" target="_blank" class="btn btn-outline-info btn-sm">
      <i class="fa fa-external-link-alt"></i> <?php echo __('RIC Explorer'); ?>
    </a>
  </div>
  <h1><i class="fa fa-project-diagram"></i> <?php echo __('RIC Sync Dashboard'); ?></h1>
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
            <?php if (isset($fusekiStatus['triple_count'])): ?>
              <i class="fa fa-database"></i> <?php echo number_format($fusekiStatus['triple_count']); ?> <?php echo __('triples'); ?>
            <?php elseif (!empty($fusekiStatus['has_data'])): ?>
              <i class="fa fa-check"></i> <?php echo __('Connected with data'); ?>
            <?php else: ?>
              <i class="fa fa-check"></i> <?php echo __('Connected'); ?>
            <?php endif; ?>
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
            <h3 class="mb-0" id="queue-count">
              <span class="spinner-border spinner-border-sm text-muted"></span>
            </h3>
          </div>
          <div class="fs-1 text-primary"><i class="fa fa-list-ol"></i></div>
        </div>
        <p class="mb-0 mt-2" id="queue-badges"></p>
      </div>
      <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'queue']); ?>" class="card-footer text-decoration-none text-center">
        <?php echo __('Manage queue'); ?> <i class="fa fa-arrow-right"></i>
      </a>
    </div>
  </div>

  <!-- Orphaned Triples -->
  <div class="col-md-3">
    <div class="card h-100" id="orphan-card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="card-subtitle text-muted mb-1"><?php echo __('Orphaned Triples'); ?></h6>
            <h3 class="mb-0" id="orphan-count">
              <span class="spinner-border spinner-border-sm text-muted"></span>
            </h3>
          </div>
          <div class="fs-1 text-muted" id="orphan-icon">
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
      <div class="card-header"><h5 class="mb-0"><?php echo __('Record Activity (7 Days)'); ?></h5></div>
      <div class="card-body position-relative" style="min-height: 200px;">
        <div id="chart-loading-1" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
          <p class="mt-2 text-muted"><?php echo __('Loading chart...'); ?></p>
        </div>
        <canvas id="syncTrendChart" height="200" style="display:none;"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Operations by Type'); ?></h5></div>
      <div class="card-body position-relative" style="min-height: 200px;">
        <div id="chart-loading-2" class="text-center py-5">
          <div class="spinner-border text-primary"></div>
          <p class="mt-2 text-muted"><?php echo __('Loading chart...'); ?></p>
        </div>
        <canvas id="operationsChart" height="200" style="display:none;"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Entity Status & Recent Operations -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><h5 class="mb-0"><?php echo __('Entity Sync Status'); ?></h5></div>
      <div class="card-body" id="entity-status-body">
        <div class="text-center py-4">
          <div class="spinner-border text-primary"></div>
          <p class="mt-2 text-muted"><?php echo __('Loading entity status...'); ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo __('Quick Actions'); ?></h5>
      </div>
      <div class="card-body">
        <button type="button" class="btn btn-success w-100 mb-2" onclick="runManualSync()" id="sync-btn">
          <i class="fa fa-sync-alt"></i> <?php echo __('Sync to Fuseki'); ?>
        </button>
        <div id="sync-status" class="mb-2" style="display:none;"></div>
        <hr>
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
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><?php echo __('Recent Operations'); ?></h5>
    <small class="text-muted" id="last-updated"></small>
  </div>
  <div class="card-body" id="recent-ops-body">
    <div class="text-center py-4">
      <div class="spinner-border text-primary"></div>
      <p class="mt-2 text-muted"><?php echo __('Loading recent operations...'); ?></p>
    </div>
  </div>
</div>

<?php end_slot(); ?>

<?php slot('after-content'); ?>
<script src="/plugins/ahgCorePlugin/web/js/vendor/chart.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
let syncTrendChart = null;
let operationsChart = null;

// Load dashboard data asynchronously on page load
document.addEventListener('DOMContentLoaded', function() {
  loadDashboardData();
});

function loadDashboardData() {
  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxDashboard']); ?>')
    .then(r => r.json())
    .then(data => {
      updateQueueStatus(data.queue_status || {});
      updateOrphanCount(data.orphan_count || 0);
      updateEntityStatus(data.sync_summary || {});
      updateRecentOperations(data.recent_operations || []);
      updateCharts(data.sync_trend || [], data.operations_by_type || {});

      const cacheNote = data.from_cache ? ' (cached)' : '';
      document.getElementById('last-updated').textContent = 'Updated: ' + data.timestamp + cacheNote;
    })
    .catch(err => {
      console.error('Failed to load dashboard:', err);
      document.getElementById('entity-status-body').innerHTML =
        '<div class="alert alert-danger">Failed to load data. <a href="javascript:loadDashboardData()">Retry</a></div>';
    });
}

function updateQueueStatus(status) {
  const queued = status.queued || 0;
  const processing = status.processing || 0;
  const failed = status.failed || 0;

  document.getElementById('queue-count').textContent = queued.toLocaleString();

  let badges = '';
  if (processing > 0) badges += '<span class="badge bg-warning">' + processing + ' processing</span> ';
  if (failed > 0) badges += '<span class="badge bg-danger">' + failed + ' failed</span>';
  document.getElementById('queue-badges').innerHTML = badges;
}

function updateOrphanCount(count) {
  const el = document.getElementById('orphan-count');
  const icon = document.getElementById('orphan-icon');
  const card = document.getElementById('orphan-card');

  el.textContent = count.toLocaleString();
  el.className = 'mb-0 ' + (count > 0 ? 'text-warning' : 'text-success');
  icon.className = 'fs-1 ' + (count > 0 ? 'text-warning' : 'text-success');
  card.className = 'card h-100' + (count > 0 ? ' border-warning' : '');
}

function updateEntityStatus(summary) {
  if (!summary || Object.keys(summary).length === 0) {
    document.getElementById('entity-status-body').innerHTML =
      '<div class="alert alert-info mb-0">No sync status data available.</div>';
    return;
  }

  let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
    '<th>Entity Type</th><th class="text-center">Synced</th>' +
    '<th class="text-center">Pending</th><th class="text-center">Failed</th>' +
    '<th class="text-center">Total</th><th></th></tr></thead><tbody>';

  for (const [entityType, statuses] of Object.entries(summary)) {
    let synced = 0, pending = 0, failed = 0;
    statuses.forEach(s => {
      if (s.sync_status === 'synced') synced = s.count;
      else if (s.sync_status === 'pending') pending = s.count;
      else if (s.sync_status === 'failed') failed = s.count;
    });
    const total = synced + pending + failed;

    html += '<tr><td><code>' + entityType + '</code></td>' +
      '<td class="text-center"><span class="badge bg-success">' + synced.toLocaleString() + '</span></td>' +
      '<td class="text-center"><span class="badge bg-warning text-dark">' + pending.toLocaleString() + '</span></td>' +
      '<td class="text-center"><span class="badge bg-danger">' + failed.toLocaleString() + '</span></td>' +
      '<td class="text-center">' + total.toLocaleString() + '</td>' +
      '<td class="text-end"><a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'syncStatus']); ?>?entity_type=' + entityType + '" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a></td></tr>';
  }

  html += '</tbody></table>';
  document.getElementById('entity-status-body').innerHTML = html;
}

function updateRecentOperations(ops) {
  if (!ops || ops.length === 0) {
    document.getElementById('recent-ops-body').innerHTML =
      '<div class="alert alert-info mb-0">No recent operations.</div>';
    return;
  }

  let html = '<table class="table table-sm table-hover mb-0"><thead><tr>' +
    '<th>Time</th><th>Operation</th><th>Entity</th><th>Status</th></tr></thead><tbody>';

  ops.slice(0, 10).forEach(op => {
    const time = new Date(op.created_at).toLocaleTimeString();
    const statusClass = op.status === 'success' ? 'success' : (op.status === 'failure' ? 'danger' : 'warning');
    const entityName = op.entity_name || (op.entity_type + '/' + op.entity_id);
    html += '<tr><td class="text-muted small">' + time + '</td>' +
      '<td><span class="badge bg-secondary">' + op.operation + '</span></td>' +
      '<td><strong>' + entityName + '</strong><br><small class="text-muted">' + op.entity_type + ' #' + op.entity_id + '</small></td>' +
      '<td><span class="badge bg-' + statusClass + '">' + op.status + '</span></td></tr>';
  });

  html += '</tbody></table>';
  document.getElementById('recent-ops-body').innerHTML = html;
}

function updateCharts(trendData, opsData) {
  // Hide loading, show canvas
  document.getElementById('chart-loading-1').style.display = 'none';
  document.getElementById('chart-loading-2').style.display = 'none';
  document.getElementById('syncTrendChart').style.display = 'block';
  document.getElementById('operationsChart').style.display = 'block';

  // Sync Trend Chart
  if (trendData && trendData.length > 0) {
    if (syncTrendChart) syncTrendChart.destroy();
    syncTrendChart = new Chart(document.getElementById('syncTrendChart').getContext('2d'), {
      type: 'line',
      data: {
        labels: trendData.map(d => d.date),
        datasets: [
          { label: 'Created', data: trendData.map(d => d.success), borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', fill: true, tension: 0.3 },
          { label: 'Updated', data: trendData.map(d => d.failure), borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true, tension: 0.3 }
        ]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });
  }

  // Operations Chart
  if (opsData && Object.keys(opsData).length > 0) {
    if (operationsChart) operationsChart.destroy();
    operationsChart = new Chart(document.getElementById('operationsChart').getContext('2d'), {
      type: 'doughnut',
      data: {
        labels: Object.keys(opsData),
        datasets: [{ data: Object.values(opsData), backgroundColor: ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#6c757d', '#0dcaf0'] }]
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
  }
}

// Auto-refresh every 30 seconds
setInterval(loadDashboardData, 30000);

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
        loadDashboardData();
      }
    });
}

// Manual Sync
let syncLogFile = null;
let syncPollTimer = null;

function runManualSync() {
  const btn = document.getElementById('sync-btn');
  const statusDiv = document.getElementById('sync-status');

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Starting sync...';
  statusDiv.style.display = 'block';
  statusDiv.innerHTML = '<div class="alert alert-info py-1 small mb-0"><i class="fa fa-spinner fa-spin"></i> Launching sync process...</div>';

  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxSync']); ?>', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        syncLogFile = data.log_file;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Syncing (PID: ' + data.pid + ')';
        statusDiv.innerHTML = '<div class="alert alert-info py-1 small mb-0"><i class="fa fa-spinner fa-spin"></i> ' + data.message + '</div>';

        // Poll for progress
        syncPollTimer = setInterval(pollSyncProgress, 3000);
      } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-sync-alt"></i> Sync to Fuseki';
        statusDiv.innerHTML = '<div class="alert alert-danger py-1 small mb-0">Error: ' + (data.error || 'Unknown') + '</div>';
      }
    })
    .catch(err => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa fa-sync-alt"></i> Sync to Fuseki';
      statusDiv.innerHTML = '<div class="alert alert-danger py-1 small mb-0">Failed: ' + err.message + '</div>';
    });
}

function pollSyncProgress() {
  if (!syncLogFile) return;

  fetch('<?php echo url_for(['module' => 'ricDashboard', 'action' => 'ajaxSyncProgress']); ?>?log_file=' + encodeURIComponent(syncLogFile))
    .then(r => r.json())
    .then(data => {
      const btn = document.getElementById('sync-btn');
      const statusDiv = document.getElementById('sync-status');

      if (!data.running) {
        // Done
        clearInterval(syncPollTimer);
        syncPollTimer = null;
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-sync-alt"></i> Sync to Fuseki';

        const hasError = data.output && data.output.indexOf('ERROR') !== -1;
        const alertClass = hasError ? 'alert-warning' : 'alert-success';
        const icon = hasError ? 'exclamation-triangle' : 'check-circle';

        statusDiv.innerHTML = '<div class="alert ' + alertClass + ' py-1 small mb-0"><i class="fa fa-' + icon + '"></i> Sync complete</div>' +
          '<pre class="small mt-1 mb-0 p-2 bg-dark text-light" style="max-height:150px; overflow-y:auto; font-size:0.7rem;">' +
          (data.output || 'No output') + '</pre>';

        // Refresh dashboard data
        loadDashboardData();
      } else {
        // Still running
        const lastLine = data.output ? data.output.split('\n').pop() : 'Processing...';
        statusDiv.innerHTML = '<div class="alert alert-info py-1 small mb-0"><i class="fa fa-spinner fa-spin"></i> ' + lastLine + '</div>';
      }
    });
}
</script>
<?php end_slot(); ?>
