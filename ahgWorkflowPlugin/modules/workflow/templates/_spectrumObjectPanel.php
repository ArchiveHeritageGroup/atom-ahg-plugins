<?php /* Spectrum Phase C3 — per-object panel partial (PSIS port).

  Embed from any IO-view template via:

    <?php include_component('workflow', 'spectrumObjectPanel', [
        'informationObjectId' => $resource->id,
    ]) ?>
*/ ?>
<?php if (!empty($summary)): ?>
  <?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
  <style <?php echo $nonceAttr; ?>>
    .spectrum-obj-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 6px; }
    .spectrum-obj-cell { padding: 6px 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; }
    .spectrum-obj-cell.s-not_started { background: #f8f9fa; color: #6c757d; }
    .spectrum-obj-cell.s-in_progress { background: #cfe2ff; color: #084298; }
    .spectrum-obj-cell.s-completed   { background: #d1e7dd; color: #0f5132; }
    .spectrum-obj-cell.s-overdue     { background: #f8d7da; color: #842029; font-weight: 600; }
    .spectrum-obj-cell.s-rejected    { background: #fff3cd; color: #664d03; }
    .spectrum-obj-cell .icon { opacity: 0.7; }
  </style>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="fas fa-university me-2"></i><strong><?php echo __('Spectrum compliance') ?></strong></span>
      <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'spectrumDashboard']) ?>" class="text-decoration-none small">
        <?php echo __('Collection dashboard') ?> <i class="fas fa-external-link-alt"></i>
      </a>
    </div>
    <div class="card-body">
      <div class="spectrum-obj-grid">
        <?php foreach ($summary as $code => $entry): ?>
          <div class="spectrum-obj-cell s-<?php echo esc_entities($entry['status']) ?>"
               title="<?php echo esc_entities($entry['label']) ?> — <?php echo esc_entities($statuses[$entry['status']] ?? $entry['status']) ?>">
            <span><?php echo esc_entities($entry['label']) ?></span>
            <span class="icon">
              <?php if ($entry['status'] === 'completed'): ?>
                <i class="fas fa-check-circle"></i>
              <?php elseif ($entry['status'] === 'in_progress'): ?>
                <i class="fas fa-spinner"></i>
              <?php elseif ($entry['status'] === 'overdue'): ?>
                <i class="fas fa-exclamation-triangle"></i>
              <?php elseif ($entry['status'] === 'rejected'): ?>
                <i class="fas fa-times-circle"></i>
              <?php else: ?>
                <i class="fas fa-circle text-muted" style="opacity:0.3"></i>
              <?php endif ?>
            </span>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  </div>
<?php endif ?>
