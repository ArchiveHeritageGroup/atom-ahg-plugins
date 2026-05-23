<?php /* heratio#143 Phase 1 — workflow diagram (PSIS Symfony port) */ ?>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>
<style <?php echo $nonceAttr; ?>>
  .workflow-diagram-stage { overflow-x: auto; background: #fafbfc; }
  .workflow-diagram { width: 100%; height: auto; color: #6c757d; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  .wfdiag-edge { stroke: #adb5bd; stroke-width: 2; fill: none; }
  .wfdiag-node { fill: #ffffff; stroke: #0d6efd; stroke-width: 2; }
  .wfdiag-node.wfdiag-inactive { stroke: #adb5bd; fill: #f1f3f5; stroke-dasharray: 4 4; }
  .wfdiag-node.wfdiag-optional { stroke: #6f42c1; }
  .wfdiag-node.wfdiag-status-completed { fill: #d1e7dd; stroke: #198754; }
  .wfdiag-node.wfdiag-status-current   { fill: #fff3cd; stroke: #ffc107; stroke-width: 3; }
  .wfdiag-node.wfdiag-status-pending   { fill: #e9ecef; stroke: #adb5bd; }
  .wfdiag-node.wfdiag-status-rejected  { fill: #f8d7da; stroke: #dc3545; }
  .wfdiag-badge { fill: #0d6efd; stroke: #ffffff; stroke-width: 2; }
  .wfdiag-badge-text { fill: #ffffff; font-size: 11px; font-weight: 600; }
  .wfdiag-node-name { fill: #212529; font-size: 13px; font-weight: 600; }
  .wfdiag-node-type { fill: #6c757d; font-size: 11px; }
  .workflow-diagram-legend .legend-swatch { display: inline-block; width: 22px; height: 14px; border-radius: 4px; border: 2px solid #0d6efd; background: #fff; }
  .workflow-diagram-legend .swatch-optional { border-color: #6f42c1; transform: rotate(45deg); border-radius: 0; }
  .workflow-diagram-legend .swatch-inactive { border-style: dashed; border-color: #adb5bd; background: #f1f3f5; }
  @media print { .workflow-diagram-stage { background: #fff; } .btn { display: none; } }
</style>

<div class="container-fluid px-4 py-3 workflow diagram">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="mb-0 flex-grow-1"><i class="fas fa-project-diagram me-2"></i><?php echo __('Diagram:') ?> <?php echo esc_entities($workflow->name) ?></h1>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'editWorkflow', 'id' => $workflow->id]) ?>" class="btn btn-outline-secondary">
      <i class="fas fa-edit me-1"></i><?php echo __('Edit workflow') ?>
    </a>
    <a href="<?php echo url_for(['module' => 'workflow', 'action' => 'admin']) ?>" class="btn btn-outline-secondary">
      <i class="fas fa-list me-1"></i><?php echo __('All workflows') ?>
    </a>
  </div>

  <?php if (!empty($spectrumLabel)): ?>
    <p class="mb-2">
      <span class="badge bg-info text-dark"><i class="fas fa-university me-1"></i><?php echo __('Spectrum:') ?> <?php echo esc_entities($spectrumLabel) ?></span>
    </p>
  <?php endif ?>

  <?php if (!empty($workflow->description)): ?>
    <p class="text-muted"><?php echo esc_entities($workflow->description) ?></p>
  <?php endif ?>

  <div class="row">
    <div class="col-lg-9">
      <div class="card">
        <div class="card-body p-3 workflow-diagram-stage">
          <?php echo $svg ?>
        </div>
      </div>
    </div>

    <div class="col-lg-3 mt-3 mt-lg-0">
      <div class="card">
        <div class="card-header"><strong><?php echo __('Steps') ?></strong></div>
        <ol class="list-group list-group-flush list-group-numbered mb-0 small">
          <?php if (empty($fallback)): ?>
            <li class="list-group-item text-muted"><?php echo __('No steps yet.') ?></li>
          <?php else: ?>
            <?php foreach ($fallback as $line): ?>
              <li class="list-group-item"><?php echo esc_entities(substr($line, strpos($line, '. ') + 2)) ?></li>
            <?php endforeach ?>
          <?php endif ?>
        </ol>
      </div>

      <div class="card mt-3">
        <div class="card-header"><strong><?php echo __('Legend') ?></strong></div>
        <ul class="list-group list-group-flush small mb-0 workflow-diagram-legend">
          <li class="list-group-item d-flex align-items-center gap-2"><span class="legend-swatch swatch-default"></span> <?php echo __('Standard step') ?></li>
          <li class="list-group-item d-flex align-items-center gap-2"><span class="legend-swatch swatch-optional"></span> <?php echo __('Optional step (diamond)') ?></li>
          <li class="list-group-item d-flex align-items-center gap-2"><span class="legend-swatch swatch-inactive"></span> <?php echo __('Inactive step') ?></li>
        </ul>
      </div>
    </div>
  </div>
</div>
