<?php
$queue = $sf_data->getRaw('queue') ?: [];
$stats = $sf_data->getRaw('stats') ?: [];
$policies = $sf_data->getRaw('policies') ?: [];
$filterStatus = $sf_data->getRaw('filterStatus') ?: '';
$filterPolicyId = $sf_data->getRaw('filterPolicyId') ?: '';
?>

<main id="content" class="container-xxl py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clipboard-check me-2"></i><?php echo __('Disposition Queue'); ?></h1>
    <div>
      <a href="<?php echo url_for(['module' => 'integrity', 'action' => 'index']); ?>" class="btn btn-outline-secondary btn-sm me-1">
        <i class="fas fa-arrow-left me-1"></i><?php echo __('Dashboard'); ?>
      </a>
      <button class="btn btn-warning btn-sm" id="btnScanEligible">
        <i class="fas fa-search me-1"></i><?php echo __('Scan for Eligible'); ?>
      </button>
    </div>
  </div>

  <?php if (!empty($stats)): ?>
  <div class="row g-2 mb-3">
    <?php foreach ($stats as $status => $count): ?>
      <div class="col-auto"><span class="badge bg-secondary fs-6"><?php echo $status; ?>: <?php echo $count; ?></span></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form class="row g-2 mb-3" method="get" action="<?php echo url_for(['module' => 'integrity', 'action' => 'disposition']); ?>">
    <div class="col-auto">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value=""><?php echo __('All statuses'); ?></option>
        <?php foreach (['eligible', 'pending_review', 'approved', 'rejected', 'held', 'disposed'] as $s): ?>
          <option value="<?php echo $s; ?>" <?php echo $filterStatus === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="policy_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value=""><?php echo __('All policies'); ?></option>
        <?php foreach ($policies as $p): ?>
          <option value="<?php echo $p->id; ?>" <?php echo $filterPolicyId == $p->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($p->name); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th><?php echo __('ID'); ?></th><th><?php echo __('Policy'); ?></th><th><?php echo __('IO ID'); ?></th>
              <th><?php echo __('DO ID'); ?></th><th><?php echo __('Status'); ?></th><th><?php echo __('Eligible At'); ?></th>
              <th><?php echo __('Reviewed By'); ?></th><th><?php echo __('Actions'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($queue)): ?>
              <tr><td colspan="8" class="text-muted text-center py-3"><?php echo __('No disposition queue entries.'); ?></td></tr>
            <?php else: ?>
              <?php foreach ($queue as $item): ?>
                <tr>
                  <td><?php echo $item->id; ?></td>
                  <td><?php echo htmlspecialchars($item->policy_name ?? "\xE2\x80\x94"); ?></td>
                  <td><?php echo $item->information_object_id; ?></td>
                  <td><?php echo $item->digital_object_id ?? "\xE2\x80\x94"; ?></td>
                  <td><span class="badge bg-secondary"><?php echo $item->status; ?></span></td>
                  <td><?php echo $item->eligible_at; ?></td>
                  <td><?php echo htmlspecialchars($item->reviewed_by ?? "\xE2\x80\x94"); ?></td>
                  <td>
                    <?php if (in_array($item->status, ['eligible', 'pending_review'])): ?>
                      <button class="btn btn-sm btn-outline-success me-1" data-disposition-action="<?php echo $item->id; ?>" data-action-type="approved"><i class="fas fa-check"></i></button>
                      <button class="btn btn-sm btn-outline-danger" data-disposition-action="<?php echo $item->id; ?>" data-action-type="rejected"><i class="fas fa-times"></i></button>
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

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('btnScanEligible').addEventListener('click', function() {
  this.disabled = true;
  this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Scanning...';
  fetch('/api/integrity/retention/scan', { method: 'POST' })
    .then(function(r) { return r.json(); })
    .then(function(d) { alert('Scan complete: ' + (d.queued || 0) + ' items queued'); location.reload(); });
});
document.querySelectorAll('[data-disposition-action]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var id = this.getAttribute('data-disposition-action');
    var action = this.getAttribute('data-action-type');
    var notes = prompt('Notes (optional):') || '';
    fetch('/api/integrity/disposition/' + id + '/action', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'disposition_action=' + action + '&notes=' + encodeURIComponent(notes)
    }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
  });
});
</script>
