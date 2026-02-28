<?php
$run = $sf_data->getRaw('run');
$ledgerEntries = $sf_data->getRaw('ledgerEntries') ?: [];
$outcomeBreakdown = $sf_data->getRaw('outcomeBreakdown') ?: [];
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-play-circle me-2"></i><?php echo __('Run'); ?> #<?php echo $run->id ?? ''; ?></h1>
    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'runs']); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="fas fa-arrow-left me-1"></i><?php echo __('Back to Runs'); ?>
    </a>
  </div>

  <?php if ($run): ?>
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Run Details'); ?></h5></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4"><?php echo __('Status'); ?></dt>
            <dd class="col-sm-8"><span class="badge <?php echo $run->status === 'completed' ? 'bg-success' : 'bg-warning'; ?>"><?php echo $run->status; ?></span></dd>
            <dt class="col-sm-4"><?php echo __('Schedule'); ?></dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($run->schedule_name ?? '—'); ?></dd>
            <dt class="col-sm-4"><?php echo __('Started'); ?></dt>
            <dd class="col-sm-8"><?php echo $run->started_at; ?></dd>
            <dt class="col-sm-4"><?php echo __('Completed'); ?></dt>
            <dd class="col-sm-8"><?php echo $run->completed_at ?? '—'; ?></dd>
          </dl>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-header"><h5 class="mb-0"><?php echo __('Counters'); ?></h5></div>
        <div class="card-body">
          <p><?php echo __('Scanned'); ?>: <?php echo number_format($run->objects_scanned); ?> |
             <?php echo __('Passed'); ?>: <?php echo number_format($run->objects_passed); ?> |
             <?php echo __('Failed'); ?>: <?php echo number_format($run->objects_failed); ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h5 class="mb-0"><?php echo __('Verification Entries'); ?></h5></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('Object'); ?></th><th><?php echo __('Outcome'); ?></th>
              <th><?php echo __('File'); ?></th><th><?php echo __('Verified'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ledgerEntries as $e): ?>
              <tr class="<?php echo $e->outcome !== 'pass' ? 'table-danger' : ''; ?>">
                <td><?php echo $e->digital_object_id; ?></td>
                <td><span class="badge <?php echo $e->outcome === 'pass' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $e->outcome; ?></span></td>
                <td><?php echo htmlspecialchars(basename($e->file_path ?? '—')); ?></td>
                <td><?php echo $e->verified_at; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>
