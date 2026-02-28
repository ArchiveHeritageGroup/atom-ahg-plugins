<?php
$runs = $sf_data->getRaw('runs') ?: [];
$schedules = $sf_data->getRaw('schedules') ?: [];
$filterScheduleId = $sf_data->getRaw('filterScheduleId');
$filterStatus = $sf_data->getRaw('filterStatus');
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-history me-2"></i><?php echo __('Run History'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th><?php echo __('Schedule'); ?></th><th><?php echo __('Status'); ?></th>
              <th><?php echo __('Scanned'); ?></th><th><?php echo __('Passed'); ?></th>
              <th><?php echo __('Failed'); ?></th><th><?php echo __('Missing'); ?></th>
              <th><?php echo __('Errors'); ?></th><th><?php echo __('Triggered'); ?></th>
              <th><?php echo __('Started'); ?></th><th><?php echo __('Completed'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($runs)): ?>
              <tr><td colspan="11" class="text-muted text-center py-3"><?php echo __('No runs found.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($runs as $run): ?>
                <tr>
                  <td><a href="<?php echo url_for(['module' => 'integrity', 'action' => 'runDetail', 'id' => $run->id]); ?>"><?php echo $run->id; ?></a></td>
                  <td><?php echo htmlspecialchars($run->schedule_name ?? '—'); ?></td>
                  <td><span class="badge <?php echo $run->status === 'completed' ? 'bg-success' : ($run->status === 'running' ? 'bg-info' : 'bg-warning'); ?>"><?php echo $run->status; ?></span></td>
                  <td><?php echo number_format($run->objects_scanned); ?></td>
                  <td><?php echo number_format($run->objects_passed); ?></td>
                  <td><?php echo number_format($run->objects_failed); ?></td>
                  <td><?php echo number_format($run->objects_missing); ?></td>
                  <td><?php echo number_format($run->objects_error); ?></td>
                  <td><?php echo $run->triggered_by; ?></td>
                  <td><?php echo $run->started_at; ?></td>
                  <td><?php echo $run->completed_at ?? '—'; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
