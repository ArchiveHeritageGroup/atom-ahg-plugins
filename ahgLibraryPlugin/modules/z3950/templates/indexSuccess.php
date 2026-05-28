<?php decorate_with('layout_1col'); ?>

<?php slot('title'); ?>
  <h1><i class="fas fa-server me-2"></i><?php echo __('Z39.50 / SRU Targets'); ?></h1>
<?php end_slot(); ?>

<?php
  $targets = $sf_data->getRaw('targets');
  $yazLoaded = $sf_data->getRaw('yazLoaded');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div class="text-muted small">
    <?php if ($yazLoaded): ?>
      <span class="badge bg-success me-1"><i class="fas fa-check me-1"></i>YAZ loaded</span>
    <?php else: ?>
      <span class="badge bg-warning text-dark me-1"><i class="fas fa-exclamation-triangle me-1"></i>YAZ not loaded</span>
    <?php endif; ?>
    <?php echo __('%1% target(s)', ['%1%' => count($targets ?: [])]); ?>
  </div>
  <div>
    <a href="<?php echo url_for(['module' => 'libraryReports', 'action' => 'index']); ?>"
       class="btn btn-outline-secondary me-2">
       <i class="fas fa-chart-bar me-1"></i><?php echo __('Library Reports'); ?>
    </a>
    <a href="<?php echo url_for(['module' => 'z3950', 'action' => 'edit']); ?>"
       class="btn btn-success">
      <i class="fas fa-plus me-2"></i><?php echo __('Add Target'); ?>
    </a>
  </div>
</div>

<?php if (empty($targets)): ?>
  <div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    <?php echo __('No Z39.50 targets configured. Add a target to begin harvesting records.'); ?>
  </div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><?php echo __('Name'); ?></th>
          <th><?php echo __('Host'); ?></th>
          <th><?php echo __('Port'); ?></th>
          <th><?php echo __('Database'); ?></th>
          <th><?php echo __('Syntax'); ?></th>
          <th><?php echo __('Status'); ?></th>
          <th><?php echo __('Ping'); ?></th>
          <th class="text-end"><?php echo __('Actions'); ?></ths>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($targets as $t): ?>
          <?php
            $isActive = !empty($t['is_active']);
            $status   = $t['_status'] ?? 'unknown';
            $pingMsg = $t['_ping_msg'] ?? '';
            $pingMs  = $t['_ping_ms'] ?? 0;
          ?>
          <tr>
            <td>
              <strong><?php echo esc_entities($t['name'] ?? ''); ?></strong>
              <?php if (!$isActive): ?>
                <span class="badge bg-secondary ms-1">inactive</span>
              <?php endif; ?>
            </td>
            <td><code><?php echo esc_entities($t['host'] ?? ''); ?></code></td>
            <td><code><?php echo esc_entities($t['port'] ?? 210); ?></code></td>
            <td><?php echo esc_entities($t['database'] ?? ''); ?></td>
            <td><span class="badge bg-light text-dark"><?php echo esc_entities($t['syntax'] ?? 'marc21'); ?></span></td>
            <td>
              <?php if ($status === 'ok'): ?>
                <span class="badge bg-success"><?php echo __('OK'); ?></span>
              <?php elseif ($status === 'fail'): ?>
                <span class="badge bg-danger" title="<?php echo esc_entities($pingMsg); ?>">
                  <?php echo __('Fail'); ?>
                </span>
              <?php elseif ($status === 'inactive'): ?>
                <span class="badge bg-secondary"><?php echo __('Inactive'); ?></span>
              <?php else: ?>
                <span class="badge bg-warning text-dark"><?php echo __('N/A'); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($pingMs > 0): ?>
                <span class="text-muted small"><?php echo round((float) $pingMs, 1); ?> ms</span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <a href="<?php echo url_for(['module' => 'z3950', 'action' => 'edit', 'id' => $t['id']]); ?>"
                   class="btn btn-outline-secondary" title="<?php echo __('Edit'); ?>">
                  <i class="fas fa-edit"></i>
                </a>
                <a href="<?php echo url_for(['module' => 'z3950', 'action' => 'test', 'id' => $t['id']]); ?>"
                   class="btn btn-outline-info" title="<?php echo __('Test'); ?>"
                   target="_blank">
                  <i class="fas fa-plug"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<div class="card mt-4">
  <div class="card-header fw-bold"><?php echo __('SRU Endpoint'); ?></div>
  <div class="card-body small">
    <p class="mb-1"><?php echo __('The SRU/CQL HTTP endpoint for this library catalog:'); ?></p>
    <div class="input-group input-group-sm">
      <input type="text" readonly class="form-control font-monospace"
             value="/api/sru?version=1.1&operation=searchRetrieve&query=dc.title%3Dlibrary&recordPacking=xml"
             id="sru-url-field">
      <button class="btn btn-outline-secondary" type="button"
              onclick="document.getElementById('sru-url-field').select(); document.execCommand('copy'); this.innerHTML='<i class=\'fas fa-check\'></i>'">
        <i class="fas fa-copy"></i>
      </button>
    </div>
    <p class="mt-2 mb-0 text-muted">
      <?php echo __('Auth: include header X-API-Key: <your-sru-api-key> with scope sru. '
        . 'Operations: searchRetrieve, explain.'); ?>
    </p>
  </div>
</div>
