<?php
$stats = $sf_data->getRaw('stats');
$recentRuns = $sf_data->getRaw('recentRuns') ?: [];
$recentFailures = $sf_data->getRaw('recentFailures') ?: [];
$passRate = $stats['pass_rate'] ?? null;
$outcomes = $stats['recent_outcomes'] ?? [];
$backlog = $sf_data->getRaw('backlog') ?: 0;
$throughput = $sf_data->getRaw('throughput') ?: [];
$dailyTrend = $sf_data->getRaw('dailyTrend') ?: [];
$repoBreakdown = $sf_data->getRaw('repoBreakdown') ?: [];
$failureBreakdown = $sf_data->getRaw('failureBreakdown') ?: [];
$formatBreakdown = $sf_data->getRaw('formatBreakdown') ?: [];
$storageGrowth = $sf_data->getRaw('storageGrowth') ?: [];
$repositories = $sf_data->getRaw('repositories') ?: [];
$filterRepositoryId = $sf_data->getRaw('filterRepositoryId');
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-shield-alt me-2"></i><?php echo __('Integrity Dashboard'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'export']); ?>" class="btn btn-outline-success btn-sm me-1">
        <i class="fas fa-download me-1"></i><?php echo __('Export'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'policies']); ?>" class="btn btn-outline-warning btn-sm me-1">
        <i class="fas fa-archive me-1"></i><?php echo __('Policies'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'holds']); ?>" class="btn btn-outline-danger btn-sm me-1">
        <i class="fas fa-lock me-1"></i><?php echo __('Holds'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'alerts']); ?>" class="btn btn-outline-dark btn-sm me-1">
        <i class="fas fa-bell me-1"></i><?php echo __('Alerts'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'schedules']); ?>" class="btn btn-outline-primary btn-sm me-1">
        <i class="fas fa-clock me-1"></i><?php echo __('Schedules'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'ledger']); ?>" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-book me-1"></i><?php echo __('Ledger'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'report']); ?>" class="btn btn-outline-info btn-sm">
        <i class="fas fa-chart-bar me-1"></i><?php echo __('Report'); ?>
      </a>
    </div>
  </div>

  <?php if (!empty($repositories)): ?>
  <form method="get" action="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="mb-3">
    <div class="row g-2 align-items-end">
      <div class="col-auto">
        <label class="form-label small mb-0"><?php echo __('Filter by Repository'); ?></label>
        <select name="repository_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value=""><?php echo __('All Repositories'); ?></option>
          <?php foreach ($repositories as $repo): ?>
            <option value="<?php echo $repo->id; ?>" <?php echo $filterRepositoryId == $repo->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($repo->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($filterRepositoryId): ?>
        <div class="col-auto">
          <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-sm btn-outline-secondary"><?php echo __('Clear Filter'); ?></a>
        </div>
      <?php endif; ?>
    </div>
  </form>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-2">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Master Objects'); ?></h6>
          <h2 class="mb-0"><?php echo number_format($stats['total_master_objects'] ?? 0); ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Total Verifications'); ?></h6>
          <h2 class="mb-0"><?php echo number_format($stats['total_verifications'] ?? 0); ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100 <?php echo $passRate !== null && $passRate < 95 ? 'border-warning' : ''; ?>">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Pass Rate'); ?></h6>
          <h2 class="mb-0 <?php echo $passRate !== null && $passRate < 95 ? 'text-warning' : 'text-success'; ?>">
            <?php echo $passRate !== null ? $passRate . '%' : 'N/A'; ?>
          </h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100 <?php echo ($stats['open_dead_letters'] ?? 0) > 0 ? 'border-danger' : ''; ?>">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Open Dead Letters'); ?></h6>
          <h2 class="mb-0 <?php echo ($stats['open_dead_letters'] ?? 0) > 0 ? 'text-danger' : 'text-success'; ?>">
            <?php echo $stats['open_dead_letters'] ?? 0; ?>
          </h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100 <?php echo $backlog > 0 ? 'border-info' : ''; ?>">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Never Verified'); ?></h6>
          <h2 class="mb-0 <?php echo $backlog > 0 ? 'text-info' : 'text-success'; ?>"><?php echo number_format($backlog); ?></h2>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Throughput (7d)'); ?></h6>
          <h3 class="mb-0"><?php echo number_format($throughput['objects_per_hour'] ?? 0); ?> <small class="text-muted">obj/hr</small></h3>
          <small class="text-muted"><?php echo $throughput['gb_per_hour'] ?? 0; ?> GB/hr</small>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($storageGrowth['daily'])): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Storage Scanned (30d)'); ?></h6>
          <h3 class="mb-0"><?php echo number_format(($storageGrowth['total_bytes'] ?? 0) / 1073741824, 1); ?> GB</h3>
          <small class="text-muted"><?php echo __('Avg'); ?>: <?php echo $storageGrowth['avg_gb_per_day'] ?? 0; ?> GB/day</small>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($dailyTrend)): ?>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Daily Verification Trend (30 days)'); ?></h5></div>
    <div class="card-body">
      <canvas id="dailyTrendChart" height="80"></canvas>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Repository Breakdown'); ?></h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Repository'); ?></th>
                  <th class="text-end"><?php echo __('Total'); ?></th>
                  <th class="text-end"><?php echo __('Passed'); ?></th>
                  <th class="text-end"><?php echo __('Failed'); ?></th>
                  <th class="text-end"><?php echo __('Pass Rate'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($repoBreakdown)): ?>
                  <tr><td colspan="5" class="text-muted text-center py-3"><?php echo __('No repository data yet.'); ?></td></tr>
                <?php else: ?>
                  <?php foreach ($repoBreakdown as $repo): ?>
                    <tr>
                      <td><a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index', 'repository_id' => $repo->repository_id]); ?>"><?php echo htmlspecialchars($repo->repo_name); ?></a></td>
                      <td class="text-end"><?php echo number_format($repo->total); ?></td>
                      <td class="text-end"><?php echo number_format($repo->passed); ?></td>
                      <td class="text-end <?php echo $repo->failed > 0 ? 'text-danger fw-bold' : ''; ?>"><?php echo number_format($repo->failed); ?></td>
                      <td class="text-end <?php echo $repo->pass_rate < 95 ? 'text-warning fw-bold' : 'text-success'; ?>"><?php echo $repo->pass_rate; ?>%</td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Failure Type Breakdown (30 days)'); ?></h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Outcome'); ?></th>
                  <th class="text-end"><?php echo __('Count'); ?></th>
                  <th class="text-end"><?php echo __('Percentage'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $failTotal = array_sum(array_map(fn($f) => $f->cnt ?? 0, $failureBreakdown));
                ?>
                <?php if (empty($failureBreakdown)): ?>
                  <tr><td colspan="3" class="text-muted text-center py-3"><?php echo __('No failures in the last 30 days.'); ?></td></tr>
                <?php else: ?>
                  <?php foreach ($failureBreakdown as $fb): ?>
                    <tr>
                      <td><span class="badge bg-danger"><?php echo $fb->outcome; ?></span></td>
                      <td class="text-end"><?php echo number_format($fb->cnt); ?></td>
                      <td class="text-end"><?php echo $failTotal > 0 ? round(($fb->cnt / $failTotal) * 100, 1) : 0; ?>%</td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($formatBreakdown)): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Format Breakdown'); ?></h5></div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th><?php echo __('Format'); ?></th>
                  <th class="text-end"><?php echo __('Total'); ?></th>
                  <th class="text-end"><?php echo __('Passed'); ?></th>
                  <th class="text-end"><?php echo __('Failed'); ?></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($formatBreakdown as $fmt): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($fmt->format_name); ?></td>
                    <td class="text-end"><?php echo number_format($fmt->total); ?></td>
                    <td class="text-end"><?php echo number_format($fmt->passed); ?></td>
                    <td class="text-end <?php echo $fmt->failed > 0 ? 'text-danger fw-bold' : ''; ?>"><?php echo number_format($fmt->failed); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Recent Runs'); ?></h5></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th><?php echo __('Schedule'); ?></th><th><?php echo __('Status'); ?></th>
              <th><?php echo __('Scanned'); ?></th><th><?php echo __('Passed'); ?></th>
              <th><?php echo __('Failed'); ?></th><th><?php echo __('Triggered'); ?></th>
              <th><?php echo __('Started'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recentRuns)): ?>
              <tr><td colspan="8" class="text-muted text-center py-3"><?php echo __('No runs recorded yet.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($recentRuns as $run): ?>
                <tr>
                  <td><a href="<?php echo url_for(['module' => 'integrity', 'action' => 'runDetail', 'id' => $run->id]); ?>"><?php echo $run->id; ?></a></td>
                  <td><?php echo htmlspecialchars($run->schedule_name ?? "\xE2\x80\x94"); ?></td>
                  <td><span class="badge <?php echo $run->status === 'completed' ? 'bg-success' : ($run->status === 'running' ? 'bg-info' : 'bg-warning'); ?>"><?php echo $run->status; ?></span></td>
                  <td><?php echo number_format($run->objects_scanned); ?></td>
                  <td><?php echo number_format($run->objects_passed); ?></td>
                  <td><?php echo number_format($run->objects_failed); ?></td>
                  <td><?php echo $run->triggered_by; ?></td>
                  <td><?php echo $run->started_at; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php if (!empty($dailyTrend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  var trendData = <?php echo json_encode(array_map(function($d) {
    return ['day' => substr($d->day, 5), 'passed' => (int)$d->passed, 'failed' => (int)$d->failed];
  }, $dailyTrend)); ?>;

  var ctx = document.getElementById('dailyTrendChart');
  if (ctx && typeof Chart !== 'undefined') {
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: trendData.map(function(d) { return d.day; }),
        datasets: [
          { label: 'Passed', data: trendData.map(function(d) { return d.passed; }), backgroundColor: '#198754', borderRadius: 2 },
          { label: 'Failed', data: trendData.map(function(d) { return d.failed; }), backgroundColor: '#dc3545', borderRadius: 2 }
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
      }
    });
  }
})();
</script>
<?php endif; ?>
