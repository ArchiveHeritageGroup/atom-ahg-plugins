<?php
$entries = $sf_data->getRaw('entries') ?: [];
$statusCounts = $sf_data->getRaw('statusCounts') ?: [];
$filterStatus = $sf_data->getRaw('filterStatus');
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-exclamation-triangle me-2"></i><?php echo __('Dead Letter Queue'); ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
    </a>
  </div>

  <div class="row g-3 mb-4">
    <?php foreach (['open' => 'danger', 'acknowledged' => 'warning', 'investigating' => 'info', 'resolved' => 'success', 'ignored' => 'secondary'] as $st => $color): ?>
      <div class="col">
        <div class="card text-center h-100">
          <div class="card-body py-2">
            <small class="text-muted d-block"><?php echo ucfirst($st); ?></small>
            <strong class="text-<?php echo $color; ?>"><?php echo $statusCounts[$st] ?? 0; ?></strong>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th><?php echo __('Object'); ?></th><th><?php echo __('Failure Type'); ?></th>
              <th><?php echo __('Status'); ?></th><th><?php echo __('Failures'); ?></th>
              <th><?php echo __('First'); ?></th><th><?php echo __('Last'); ?></th>
              <th><?php echo __('Error'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($entries)): ?>
              <tr><td colspan="8" class="text-muted text-center py-3"><?php echo __('No dead letter entries.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($entries as $e): ?>
                <tr>
                  <td><?php echo $e->id; ?></td>
                  <td><?php echo $e->digital_object_id; ?></td>
                  <td><span class="badge bg-danger"><?php echo $e->failure_type; ?></span></td>
                  <td><span class="badge bg-secondary"><?php echo $e->status; ?></span></td>
                  <td><?php echo $e->consecutive_failures; ?></td>
                  <td><?php echo $e->first_failure_at; ?></td>
                  <td><?php echo $e->last_failure_at; ?></td>
                  <td><?php echo htmlspecialchars($e->last_error_detail ?? '—'); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
