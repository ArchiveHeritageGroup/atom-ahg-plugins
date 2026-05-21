<?php
/**
 * heratio#137 - AI Inventory & Governance dashboard (AtoM-AHG side).
 * Mirrors the Heratio ahg-provenance-ai governance dashboard. Data is set by
 * aiActions::executeGovernance(): $stats, $models, $inferences.
 */
if (!function_exists('ahg_gov_dt')) {
    function ahg_gov_dt($v) { return $v ? htmlspecialchars(substr((string) $v, 0, 16)) : '—'; }
}
?>
<div class="container-fluid py-4">

  <div class="mb-4">
    <h1><i class="fas fa-shield-alt me-2"></i>AI Inventory &amp; Governance</h1>
    <p class="text-muted mb-0">Operator visibility into configured LLMs and recent AI inference activity.</p>
  </div>

  <?php
    $avg = $stats['avg_confidence'];
    $cards = array(
      array('LLM configs',         $stats['models_total'],     'fa-microchip'),
      array('Active',              $stats['models_active'],    'fa-check-circle'),
      array('Inferences (total)',  $stats['inferences_total'], 'fa-stream'),
      array('Inferences (7 days)', $stats['inferences_7d'],    'fa-calendar-week'),
      array('Avg confidence',      $avg !== null ? number_format(((float) $avg) * 100, 1) . '%' : '—', 'fa-percentage'),
    );
  ?>
  <div class="row g-3 mb-4">
    <?php foreach ($cards as $c): ?>
      <div class="col-6 col-md">
        <div class="card h-100">
          <div class="card-body text-center py-3">
            <i class="fas <?php echo $c[2]; ?> fa-lg text-muted mb-2"></i>
            <div class="fs-4 fw-bold"><?php echo htmlspecialchars((string) $c[1]); ?></div>
            <div class="small text-muted"><?php echo $c[0]; ?></div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card mb-4">
    <div class="card-header fw-bold">
      <i class="fas fa-microchip me-1"></i>LLM Configurations
      <span class="badge bg-secondary"><?php echo count($models); ?></span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>Provider</th><th>Name</th><th>Model</th>
            <th class="text-end">Max tokens</th><th class="text-end">Temp</th>
            <th class="text-end">Inferences</th><th>Last used</th><th>Manifest</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($models)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No LLM configurations.</td></tr>
        <?php else: foreach ($models as $m): ?>
          <tr>
            <td><?php echo htmlspecialchars((string) $m->provider); ?></td>
            <td>
              <?php echo htmlspecialchars((string) $m->name); ?>
              <?php if ($m->is_default): ?><span class="badge bg-primary ms-1">default</span><?php endif; ?>
              <?php if ($m->is_active): ?><span class="badge bg-success ms-1">active</span>
              <?php else: ?><span class="badge bg-secondary ms-1">inactive</span><?php endif; ?>
            </td>
            <td><code><?php echo htmlspecialchars((string) $m->model); ?></code></td>
            <td class="text-end"><?php echo htmlspecialchars((string) $m->max_tokens); ?></td>
            <td class="text-end"><?php echo htmlspecialchars((string) $m->temperature); ?></td>
            <td class="text-end"><?php echo (int) $m->inference_count; ?></td>
            <td class="small"><?php echo ahg_gov_dt($m->last_used); ?></td>
            <td class="small text-muted" title="Pending heratio#135"><?php echo $m->model_manifest !== null ? htmlspecialchars((string) $m->model_manifest) : '—'; ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header fw-bold">
      <i class="fas fa-stream me-1"></i>Recent Inferences
      <span class="badge bg-secondary"><?php echo count($inferences); ?></span>
    </div>
    <div class="table-responsive">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead>
          <tr>
            <th>When</th><th>Service</th><th>Model</th><th>Target</th><th>Field</th>
            <th class="text-end">Confidence</th><th class="text-end">Elapsed</th><th>Signed</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($inferences)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No inference activity recorded yet.</td></tr>
        <?php else: foreach ($inferences as $i): ?>
          <tr>
            <td class="small"><?php echo ahg_gov_dt($i->occurred_at); ?></td>
            <td><?php echo htmlspecialchars((string) ($i->service_name !== null ? $i->service_name : '—')); ?></td>
            <td><code><?php echo htmlspecialchars((string) ($i->model_name !== null ? $i->model_name : '—')); ?></code><?php if ($i->model_version): ?> <span class="text-muted small"><?php echo htmlspecialchars((string) $i->model_version); ?></span><?php endif; ?></td>
            <td class="small"><?php echo $i->target_entity_type ? htmlspecialchars($i->target_entity_type . ' #' . $i->target_entity_id) : '—'; ?></td>
            <td class="small"><?php echo htmlspecialchars((string) ($i->target_field !== null ? $i->target_field : '—')); ?></td>
            <td class="text-end">
              <?php if ($i->confidence !== null): ?>
                <?php echo number_format(((float) $i->confidence) * 100, 1); ?>%
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-end small"><?php echo $i->elapsed_ms !== null ? (int) $i->elapsed_ms . ' ms' : '—'; ?></td>
            <td>
              <?php if ($i->signed): ?>
                <span class="badge bg-success">signed</span>
              <?php else: ?>
                <span class="badge bg-light text-muted border" title="Pending heratio#136">unsigned</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="text-muted small">
    <i class="fas fa-info-circle me-1"></i>Model manifests (heratio#135) and Ed25519 inference signing (heratio#136) are not yet wired - those columns show a placeholder until then.
  </p>

</div>
