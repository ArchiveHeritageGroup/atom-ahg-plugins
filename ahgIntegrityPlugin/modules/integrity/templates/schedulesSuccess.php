<?php $schedules = $sf_data->getRaw('schedules') ?: []; ?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clock me-2"></i><?php echo __('Verification Schedules'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'scheduleEdit']); ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i><?php echo __('New Schedule'); ?>
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th><th><?php echo __('Name'); ?></th><th><?php echo __('Scope'); ?></th>
              <th><?php echo __('Frequency'); ?></th><th><?php echo __('Algorithm'); ?></th>
              <th><?php echo __('Batch'); ?></th><th><?php echo __('Status'); ?></th>
              <th><?php echo __('Last Run'); ?></th><th><?php echo __('Next Run'); ?></th>
              <th><?php echo __('Runs'); ?></th><th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($schedules)): ?>
              <tr><td colspan="11" class="text-muted text-center py-3"><?php echo __('No schedules configured.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($schedules as $s): ?>
                <tr>
                  <td><?php echo $s->id; ?></td>
                  <td><a href="<?php echo url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id]); ?>"><?php echo htmlspecialchars($s->name); ?></a></td>
                  <td><span class="badge bg-secondary"><?php echo $s->scope_type; ?></span></td>
                  <td><?php echo $s->frequency; ?></td>
                  <td><code><?php echo $s->algorithm; ?></code></td>
                  <td><?php echo $s->batch_size ?: 'All'; ?></td>
                  <td><span class="badge <?php echo $s->is_enabled ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $s->is_enabled ? __('Enabled') : __('Disabled'); ?></span></td>
                  <td><?php echo $s->last_run_at ?? '—'; ?></td>
                  <td><?php echo $s->next_run_at ?? '—'; ?></td>
                  <td><?php echo $s->total_runs; ?></td>
                  <td>
                    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id]); ?>" class="btn btn-outline-secondary btn-sm">
                      <i class="fas fa-edit"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>
