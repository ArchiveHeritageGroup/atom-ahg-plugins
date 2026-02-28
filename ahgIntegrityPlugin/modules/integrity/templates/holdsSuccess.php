<?php
$holds = $sf_data->getRaw('holds') ?: [];
$filterStatus = $sf_data->getRaw('filterStatus') ?: '';
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-lock me-2"></i><?php echo __('Legal Holds'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
      </a>
      <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#placeHoldModal">
        <i class="fas fa-plus me-1"></i><?php echo __('Place Hold'); ?>
      </button>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get" action="<?php echo url_for(['module' => 'integrity', 'action' => 'holds']); ?>">
    <div class="col-auto">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value=""><?php echo __('All statuses'); ?></option>
        <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>active</option>
        <option value="released" <?php echo $filterStatus === 'released' ? 'selected' : ''; ?>>released</option>
      </select>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('ID'); ?></th><th><?php echo __('IO ID'); ?></th><th><?php echo __('Reason'); ?></th>
              <th><?php echo __('Placed By'); ?></th><th><?php echo __('Placed At'); ?></th>
              <th><?php echo __('Status'); ?></th><th><?php echo __('Released By'); ?></th><th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($holds)): ?>
              <tr><td colspan="8" class="text-muted text-center py-3"><?php echo __('No legal holds.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($holds as $h): ?>
                <tr>
                  <td><?php echo $h->id; ?></td>
                  <td><?php echo $h->information_object_id; ?></td>
                  <td class="text-truncate" style="max-width:300px"><?php echo htmlspecialchars($h->reason); ?></td>
                  <td><?php echo htmlspecialchars($h->placed_by); ?></td>
                  <td><?php echo $h->placed_at; ?></td>
                  <td><span class="badge <?php echo $h->status === 'active' ? 'bg-danger' : 'bg-secondary'; ?>"><?php echo $h->status; ?></span></td>
                  <td><?php echo htmlspecialchars($h->released_by ?? "\xE2\x80\x94"); ?></td>
                  <td>
                    <?php if ($h->status === 'active'): ?>
                      <button class="btn btn-sm btn-outline-warning" data-hold-release="<?php echo $h->id; ?>"><i class="fas fa-unlock"></i></button>
                    <?php endif; ?>
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

<div class="modal fade" id="placeHoldModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo __('Place Legal Hold'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label"><?php echo __('Information Object ID'); ?> *</label>
          <input type="number" id="holdIoId" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label"><?php echo __('Reason'); ?> *</label>
          <textarea id="holdReason" class="form-control" rows="3" required></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('Cancel'); ?></button>
        <button type="button" class="btn btn-danger" id="btnPlaceHold"><?php echo __('Place Hold'); ?></button>
      </div>
    </div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('btnPlaceHold').addEventListener('click', function() {
  var ioId = document.getElementById('holdIoId').value;
  var reason = document.getElementById('holdReason').value;
  if (!ioId || !reason) { alert('Both fields required'); return; }
  fetch('/api/integrity/hold/place', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'information_object_id=' + ioId + '&reason=' + encodeURIComponent(reason)
  }).then(function(r) { return r.json(); }).then(function(d) {
    if (d.success) { location.reload(); } else { alert(d.error || 'Failed'); }
  });
});
document.querySelectorAll('[data-hold-release]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    if (!confirm('Release this hold?')) return;
    var id = this.getAttribute('data-hold-release');
    fetch('/api/integrity/hold/' + id + '/release', { method: 'POST' })
      .then(function(r) { return r.json(); })
      .then(function() { location.reload(); });
  });
});
</script>
