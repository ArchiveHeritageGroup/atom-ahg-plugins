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
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-primary" title="<?php echo __('Run now'); ?>" aria-label="<?php echo __('Run now'); ?>"
                              data-integrity-action="run" data-url="<?php echo url_for('@integrity_api_run_schedule?id='.$s->id); ?>">
                        <i class="fas fa-play"></i>
                      </button>
                      <button type="button" class="btn btn-outline-secondary" title="<?php echo $s->is_enabled ? __('Disable') : __('Enable'); ?>" aria-label="<?php echo $s->is_enabled ? __('Disable') : __('Enable'); ?>"
                              data-integrity-action="toggle" data-url="<?php echo url_for('@integrity_api_schedule_toggle?id='.$s->id); ?>">
                        <i class="fas <?php echo $s->is_enabled ? 'fa-pause' : 'fa-toggle-on'; ?>"></i>
                      </button>
                      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'scheduleEdit', 'id' => $s->id]); ?>" class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>" aria-label="<?php echo __('Edit'); ?>">
                        <i class="fas fa-edit"></i>
                      </a>
                      <button type="button" class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>" aria-label="<?php echo __('Delete'); ?>"
                              data-integrity-action="delete" data-name="<?php echo htmlspecialchars($s->name); ?>" data-url="<?php echo url_for('@integrity_api_schedule_delete?id='.$s->id); ?>">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
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

<?php $n = sfConfig::get('csp_nonce', ''); ?>
<script <?php echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
// Run now, enable/disable and delete: the API existed but the list offered only Edit,
// so a check could be started only from cron or the command line.
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-integrity-action]');
  if (!b) return;
  var act = b.dataset.integrityAction;
  if (act === 'delete' && !confirm('<?php echo __('Delete this schedule?'); ?> ' + b.dataset.name)) return;
  b.disabled = true;
  if (act === 'run') b.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
  fetch(b.dataset.url, { method: 'POST' })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      if (!res.success) { alert(res.error || 'Failed'); b.disabled = false; return; }
      if (act === 'run' && res.result && res.result.run_id) {
        window.location = '<?php echo url_for(['module' => 'integrity', 'action' => 'runDetail', 'id' => 0]); ?>'.replace(/0$/, res.result.run_id);
        return;
      }
      window.location.reload();
    })
    .catch(function () { alert('Failed'); b.disabled = false; });
});
</script>
