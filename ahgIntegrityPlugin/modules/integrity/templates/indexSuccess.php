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
