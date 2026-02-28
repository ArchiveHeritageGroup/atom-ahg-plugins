<?php
$policies = $sf_data->getRaw('policies') ?: [];
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-archive me-2"></i><?php echo __('Retention Policies'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
      </a>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'policyEdit']); ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-plus me-1"></i><?php echo __('New Policy'); ?>
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('ID'); ?></th>
              <th><?php echo __('Name'); ?></th>
              <th><?php echo __('Retention'); ?></th>
              <th><?php echo __('Trigger'); ?></th>
              <th><?php echo __('Scope'); ?></th>
              <th><?php echo __('Status'); ?></th>
              <th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($policies)): ?>
              <tr><td colspan="7" class="text-muted text-center py-3"><?php echo __('No retention policies defined.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($policies as $p): ?>
                <tr>
                  <td><?php echo $p->id; ?></td>
                  <td><?php echo htmlspecialchars($p->name); ?></td>
                  <td><?php echo $p->retention_period_days > 0 ? $p->retention_period_days . ' days' : 'Indefinite'; ?></td>
                  <td><span class="badge bg-secondary"><?php echo $p->trigger_type; ?></span></td>
                  <td><?php echo $p->scope_type; ?></td>
                  <td><span class="badge <?php echo $p->is_enabled ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $p->is_enabled ? __('Enabled') : __('Disabled'); ?></span></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary me-1" data-policy-toggle="<?php echo $p->id; ?>"><i class="fas <?php echo $p->is_enabled ? 'fa-pause' : 'fa-play'; ?>"></i></button>
                    <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'policyEdit', 'id' => $p->id]); ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="fas fa-edit"></i></a>
                    <button class="btn btn-sm btn-outline-danger" data-policy-delete="<?php echo $p->id; ?>"><i class="fas fa-trash"></i></button>
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

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.querySelectorAll('[data-policy-toggle]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.getAttribute('data-policy-toggle');
    fetch('/api/integrity/policy/' + id + '/toggle', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
  });
});
document.querySelectorAll('[data-policy-delete]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('Delete this policy?')) return;
    var id = this.getAttribute('data-policy-delete');
    fetch('/api/integrity/policy/' + id + '/delete', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
  });
});
</script>
