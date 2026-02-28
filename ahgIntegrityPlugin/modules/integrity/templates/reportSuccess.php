<?php
$stats = $sf_data->getRaw('stats') ?: [];
$outcomeBreakdown = $sf_data->getRaw('outcomeBreakdown') ?: [];
$monthlyTrend = $sf_data->getRaw('monthlyTrend') ?: [];
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-chart-bar me-2"></i><?php echo __('Integrity Report'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Master Objects'); ?></h6>
          <h3 class="mb-0"><?php echo number_format($stats['total_master_objects'] ?? 0); ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Verifications'); ?></h6>
          <h3 class="mb-0"><?php echo number_format($stats['total_verifications'] ?? 0); ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Pass Rate'); ?></h6>
          <h3 class="mb-0"><?php echo ($stats['pass_rate'] ?? null) !== null ? $stats['pass_rate'] . '%' : 'N/A'; ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-center h-100">
        <div class="card-body">
          <h6 class="text-muted"><?php echo __('Dead Letters'); ?></h6>
          <h3 class="mb-0"><?php echo $stats['open_dead_letters'] ?? 0; ?></h3>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($outcomeBreakdown)): ?>
  <div class="card mb-4">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Outcome Breakdown'); ?></h5></div>
    <div class="card-body">
      <?php $total = array_sum($outcomeBreakdown); ?>
      <?php foreach ($outcomeBreakdown as $outcome => $count): ?>
        <?php $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0; ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between small">
            <span class="badge <?php echo $outcome === 'pass' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $outcome; ?></span>
            <span><?php echo number_format($count); ?> (<?php echo $pct; ?>%)</span>
          </div>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar <?php echo $outcome === 'pass' ? 'bg-success' : 'bg-danger'; ?>" style="width: <?php echo $pct; ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-header"><h5 class="mb-0"><?php echo __('CLI Report Commands'); ?></h5></div>
    <div class="card-body">
      <pre class="bg-dark text-light p-3 rounded mb-0"><code>php symfony integrity:report --summary
php symfony integrity:report --dead-letter
php symfony integrity:report --summary --format=json</code></pre>
    </div>
  </div>
</main>
