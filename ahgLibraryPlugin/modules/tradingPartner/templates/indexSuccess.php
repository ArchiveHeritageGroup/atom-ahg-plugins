<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><?php echo __('EDI Trading Partners'); ?></h1>
<?php end_slot(); ?>

<?php if ($sf_user->hasFlash('notice')): ?>
  <div class="alert alert-success"><?php echo $sf_user->getFlash('notice'); ?></div>
<?php endif; ?>
<?php if ($sf_user->hasFlash('error')): ?>
  <div class="alert alert-danger"><?php echo $sf_user->getFlash('error'); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col"><div class="card text-center"><div class="card-body"><div class="h3 mb-0"><?php echo (int) $stats['total']; ?></div><small class="text-muted"><?php echo __('Total'); ?></small></div></div></div>
  <div class="col"><div class="card text-center"><div class="card-body"><div class="h3 mb-0 text-success"><?php echo (int) $stats['active']; ?></div><small class="text-muted"><?php echo __('Active'); ?></small></div></div></div>
  <div class="col"><div class="card text-center"><div class="card-body"><div class="h3 mb-0 text-danger"><?php echo (int) $stats['errors']; ?></div><small class="text-muted"><?php echo __('With errors'); ?></small></div></div></div>
  <div class="col"><div class="card text-center"><div class="card-body"><div class="h3 mb-0"><?php echo (int) $stats['sftp']; ?></div><small class="text-muted">SFTP</small></div></div></div>
  <div class="col"><div class="card text-center"><div class="card-body"><div class="h3 mb-0"><?php echo (int) $stats['as2']; ?></div><small class="text-muted">AS2</small></div></div></div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form method="get" action="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'index']); ?>" class="d-flex gap-2">
    <input type="text" name="search" class="form-control" placeholder="<?php echo __('Partner code...'); ?>" value="<?php echo esc_entities($search); ?>">
    <select name="edi_type" class="form-select">
      <option value=""><?php echo __('All EDI types'); ?></option>
      <?php foreach (['EANCOM', 'X12', 'UN/EDIFACT', 'CUSTOM'] as $t): ?>
        <option value="<?php echo $t; ?>" <?php echo $ediType === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
  </form>
  <a href="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'edit']); ?>" class="btn btn-success">
    <i class="fas fa-plus me-2"></i><?php echo __('Add Trading Partner'); ?>
  </a>
</div>

<?php if (empty($sf_data->getRaw('partners'))): ?>
  <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i><?php echo __('No trading partners configured yet.'); ?></div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th><?php echo __('Partner Code'); ?></th>
            <th><?php echo __('EDI Type'); ?></th>
            <th><?php echo __('Endpoint'); ?></th>
            <th class="text-center"><?php echo __('Mode'); ?></th>
            <th class="text-center"><?php echo __('Status'); ?></th>
            <th class="text-end"><?php echo __('Actions'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sf_data->getRaw('partners') as $p): ?>
            <tr>
              <td><code><?php echo esc_entities($p->edi_partner_code); ?></code></td>
              <td><span class="badge bg-info text-dark"><?php echo esc_entities($p->edi_type); ?></span> <small class="text-muted"><?php echo esc_entities($p->message_profile); ?></small></td>
              <td><?php echo esc_entities($p->endpoint_type); ?></td>
              <td class="text-center">
                <?php if (!empty($p->test_mode)): ?>
                  <span class="badge bg-warning text-dark"><?php echo __('TEST'); ?></span>
                <?php else: ?>
                  <span class="badge bg-dark"><?php echo __('LIVE'); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if (!empty($p->last_error_at)): ?>
                  <span class="badge bg-danger" title="<?php echo esc_entities($p->last_error_message ?? ''); ?>"><?php echo __('Error'); ?></span>
                <?php elseif (!empty($p->is_active)): ?>
                  <span class="badge bg-success"><?php echo __('Active'); ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-info js-test-partner" data-test-url="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'test', 'id' => $p->id]); ?>" title="<?php echo __('Test connection'); ?>">
                    <i class="fas fa-plug"></i>
                  </button>
                  <a href="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'edit', 'id' => $p->id]); ?>" class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                    <i class="fas fa-edit"></i>
                  </a>
                  <form method="post" action="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'toggle', 'id' => $p->id]); ?>" class="d-inline">
                    <button type="submit" class="btn btn-outline-warning" title="<?php echo __('Toggle active'); ?>"><i class="fas fa-power-off"></i></button>
                  </form>
                  <form method="post" action="<?php echo url_for(['module' => 'tradingPartner', 'action' => 'delete', 'id' => $p->id]); ?>" class="d-inline"
                        onsubmit="return confirm('<?php echo __('Delete this trading partner?'); ?>');">
                    <button type="submit" class="btn btn-outline-danger" title="<?php echo __('Delete'); ?>"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="tp-test-result" class="alert mt-3 d-none"></div>

  <script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>>
    document.querySelectorAll('.js-test-partner').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var url = this.getAttribute('data-test-url');
        var box = document.getElementById('tp-test-result');
        box.className = 'alert alert-secondary mt-3';
        box.textContent = '<?php echo __('Testing connection…'); ?>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            box.className = 'alert mt-3 ' + (d.ok ? 'alert-success' : 'alert-danger');
            box.textContent = (d.ok ? '✓ ' : '✗ ') + (d.message || '');
          })
          .catch(function () {
            box.className = 'alert alert-danger mt-3';
            box.textContent = '<?php echo __('Test request failed.'); ?>';
          });
      });
    });
  </script>
<?php endif; ?>
