<?php
$alertConfigs = $sf_data->getRaw('alertConfigs') ?: [];
$alertTypes = [
    'pass_rate_below' => 'Pass rate falls below threshold',
    'failure_count_above' => 'Failure count exceeds threshold',
    'dead_letter_count_above' => 'Dead letter count exceeds threshold',
    'backlog_above' => 'Backlog (never verified) exceeds threshold',
    'run_failure' => 'Verification run fails/times out',
];
$comparisons = ['lt' => '<', 'lte' => '<=', 'gt' => '>', 'gte' => '>=', 'eq' => '='];
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-bell me-2"></i><?php echo __('Alert Configuration'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
      </a>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#alertModal" onclick="resetAlertForm()">
        <i class="fas fa-plus me-1"></i><?php echo __('New Alert'); ?>
      </button>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('ID'); ?></th><th><?php echo __('Type'); ?></th><th><?php echo __('Threshold'); ?></th>
              <th><?php echo __('Status'); ?></th><th><?php echo __('Email'); ?></th><th><?php echo __('Webhook'); ?></th>
              <th><?php echo __('Last Triggered'); ?></th><th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($alertConfigs)): ?>
              <tr><td colspan="8" class="text-muted text-center py-3"><?php echo __('No alert configurations.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($alertConfigs as $ac): ?>
                <tr>
                  <td><?php echo $ac->id; ?></td>
                  <td><?php echo $alertTypes[$ac->alert_type] ?? $ac->alert_type; ?></td>
                  <td><?php echo ($comparisons[$ac->comparison] ?? $ac->comparison) . ' ' . $ac->threshold_value; ?></td>
                  <td><span class="badge <?php echo $ac->is_enabled ? 'bg-success' : 'bg-secondary'; ?>"><?php echo $ac->is_enabled ? __('Enabled') : __('Disabled'); ?></span></td>
                  <td><?php echo htmlspecialchars($ac->email ?? "\xE2\x80\x94"); ?></td>
                  <td><?php echo $ac->webhook_url ? 'Yes' : "\xE2\x80\x94"; ?></td>
                  <td><?php echo $ac->last_triggered_at ?? "\xE2\x80\x94"; ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-secondary me-1" onclick='editAlert(<?php echo json_encode($ac); ?>)'><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline-danger" data-alert-delete="<?php echo $ac->id; ?>"><i class="fas fa-trash"></i></button>
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

<div class="modal fade" id="alertModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="alertModalTitle"><?php echo __('New Alert'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="alertId" value="">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Alert Type'); ?></label>
            <select id="alertType" class="form-select">
              <?php foreach ($alertTypes as $key => $label): ?>
                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Comparison'); ?></label>
            <select id="alertComparison" class="form-select">
              <?php foreach ($comparisons as $key => $label): ?>
                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label"><?php echo __('Threshold'); ?></label>
            <input type="number" id="alertThreshold" class="form-control" step="0.01" value="95">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Email'); ?></label>
            <input type="email" id="alertEmail" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Webhook URL'); ?></label>
            <input type="url" id="alertWebhookUrl" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label"><?php echo __('Webhook Secret'); ?></label>
            <input type="text" id="alertWebhookSecret" class="form-control">
          </div>
          <div class="col-md-6">
            <div class="form-check mt-4">
              <input type="checkbox" id="alertEnabled" class="form-check-input" checked>
              <label class="form-check-label" for="alertEnabled"><?php echo __('Enabled'); ?></label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-primary" id="btnSaveAlert"><?php echo __('Save'); ?></button>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function resetAlertForm() {
  document.getElementById('alertId').value = '';
  document.getElementById('alertType').value = 'pass_rate_below';
  document.getElementById('alertComparison').value = 'lt';
  document.getElementById('alertThreshold').value = '95';
  document.getElementById('alertEmail').value = '';
  document.getElementById('alertWebhookUrl').value = '';
  document.getElementById('alertWebhookSecret').value = '';
  document.getElementById('alertEnabled').checked = true;
  document.getElementById('alertModalTitle').textContent = 'New Alert';
}
function editAlert(config) {
  document.getElementById('alertId').value = config.id;
  document.getElementById('alertType').value = config.alert_type;
  document.getElementById('alertComparison').value = config.comparison;
  document.getElementById('alertThreshold').value = config.threshold_value;
  document.getElementById('alertEmail').value = config.email || '';
  document.getElementById('alertWebhookUrl').value = config.webhook_url || '';
  document.getElementById('alertWebhookSecret').value = config.webhook_secret || '';
  document.getElementById('alertEnabled').checked = !!config.is_enabled;
  document.getElementById('alertModalTitle').textContent = 'Edit Alert #' + config.id;
  var modal = new bootstrap.Modal(document.getElementById('alertModal'));
  modal.show();
}
document.getElementById('btnSaveAlert').addEventListener('click', function() {
  var id = document.getElementById('alertId').value;
  var body = 'alert_type=' + document.getElementById('alertType').value
    + '&comparison=' + document.getElementById('alertComparison').value
    + '&threshold_value=' + document.getElementById('alertThreshold').value
    + '&email=' + encodeURIComponent(document.getElementById('alertEmail').value)
    + '&webhook_url=' + encodeURIComponent(document.getElementById('alertWebhookUrl').value)
    + '&webhook_secret=' + encodeURIComponent(document.getElementById('alertWebhookSecret').value)
    + '&is_enabled=' + (document.getElementById('alertEnabled').checked ? '1' : '0');
  if (id) { body += '&id=' + id; }
  fetch('/api/integrity/alert/save', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body })
    .then(function(r) { return r.json(); }).then(function(d) { if (d.success) { location.reload(); } else { alert(d.error || 'Failed'); } });
});
document.querySelectorAll('[data-alert-delete]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('Delete this alert?')) return;
    var id = this.getAttribute('data-alert-delete');
    fetch('/api/integrity/alert/' + id + '/delete', { method: 'POST' })
      .then(function(r) { return r.json(); }).then(function() { location.reload(); });
  });
});
</script>
