<?php
/* Provenance graph (#149 strand 3) — chain of custody + authenticity. */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' '.preg_replace('/^nonce=/', 'nonce="', $n).'"' : '';
?>
<div class="container-fluid py-4 provenance-graph">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="h3 mb-0 flex-grow-1"><i class="fas fa-diagram-project me-2"></i><?php echo __('Provenance graph') ?></h1>
    <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('RiC dashboard') ?></a>
  </div>

  <?php if (empty($objectId)): ?>
    <p class="text-muted"><?php echo __('Records with recorded provenance. Open one to see its chain of custody.') ?></p>
    <div class="card"><div class="card-body p-0">
      <?php if (empty($records)): ?>
        <div class="p-3 text-muted small"><?php echo __('No provenance records yet.') ?></div>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-hover table-sm mb-0 align-middle">
          <thead class="table-light"><tr><th><?php echo __('Record') ?></th><th><?php echo __('Custody events') ?></th><th><?php echo __('Certainty') ?></th><th><?php echo __('Gaps') ?></th><th></th></tr></thead>
          <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?php echo esc_entities($r->title) ?> <small class="text-muted">#<?php echo (int) $r->io_id ?></small></td>
              <td><?php echo (int) $r->events ?></td>
              <td><?php echo esc_entities($r->certainty_level) ?: '—' ?></td>
              <td><?php echo ((int) $r->has_gaps) ? '<span class="badge bg-warning text-dark">gaps</span>' : '<span class="badge bg-success">none</span>' ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'ricExplorer', 'action' => 'provenanceGraph', 'id' => $r->io_id]) ?>"><i class="fas fa-diagram-project me-1"></i><?php echo __('Graph') ?></a></td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table></div>
      <?php endif ?>
    </div></div>
  <?php else: $s = $graph['summary'] ?? []; ?>
    <div class="d-flex flex-wrap align-items-baseline mb-2 gap-2">
      <strong class="flex-grow-1"><?php echo esc_entities($recordTitle) ?></strong>
      <?php if (!empty($recordSlug)): ?><a href="/<?php echo esc_entities($recordSlug) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('Open record') ?></a><?php endif ?>
      <a href="<?php echo url_for(['module' => 'ricExplorer', 'action' => 'provenanceGraph']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('All') ?></a>
    </div>

    <?php if (empty($s['has_record'])): ?>
      <div class="alert alert-info"><?php echo __('This record has no provenance recorded yet.') ?></div>
    <?php else: ?>
    <div class="row g-3">
      <div class="col-lg-8">
        <div id="prov-cy" style="height: 520px;" class="border rounded bg-light"></div>
        <div class="form-text"><?php echo __('Arrows show the direction of custody transfer. Drag nodes to rearrange.') ?></div>
      </div>
      <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header"><strong><?php echo __('Custody & acquisition') ?></strong></div>
          <div class="card-body small">
            <?php
            $rows = [
                __('Custody events') => (int) ($s['events'] ?? 0),
                __('Custody type') => $s['custody_type'] ?? '',
                __('Current status') => $s['current_status'] ?? '',
                __('Acquisition') => trim((string) ($s['acquisition_type'] ?? '').' '.($s['acquisition_date'] ?? '')),
                __('Certainty') => $s['certainty_level'] ?? '',
            ];
            foreach ($rows as $label => $v): if (trim((string) $v) === '') { continue; } ?>
              <div class="d-flex justify-content-between border-bottom py-1"><span class="text-muted"><?php echo $label ?></span><span><?php echo esc_entities($v) ?></span></div>
            <?php endforeach ?>
          </div>
        </div>
        <div class="card"><div class="card-header"><strong><?php echo __('Authenticity & due diligence') ?></strong></div>
          <div class="card-body small">
            <div class="mb-2">
              <?php if (!empty($s['has_gaps'])): ?>
                <span class="badge bg-warning text-dark"><i class="fas fa-triangle-exclamation me-1"></i><?php echo __('Custody gaps') ?></span>
              <?php else: ?>
                <span class="badge bg-success"><i class="fas fa-check me-1"></i><?php echo __('Continuous custody') ?></span>
              <?php endif ?>
              <?php if (!empty($s['is_complete'])): ?><span class="badge bg-success"><?php echo __('Complete') ?></span><?php endif ?>
            </div>
            <?php if (!empty($s['gap_description'])): ?><p class="text-muted"><?php echo esc_entities($s['gap_description']) ?></p><?php endif ?>
            <div class="mb-1">
              <span class="text-muted"><?php echo __('Nazi-era provenance') ?>:</span>
              <?php if (!empty($s['nazi_era_checked'])): ?>
                <span class="badge bg-<?php echo !empty($s['nazi_era_clear']) ? 'success' : 'danger' ?>"><?php echo !empty($s['nazi_era_clear']) ? __('checked — clear') : __('checked — flagged') ?></span>
              <?php else: ?><span class="badge bg-secondary"><?php echo __('not checked') ?></span><?php endif ?>
            </div>
            <?php if (!empty($s['cultural_property_status'])): ?>
              <div class="mb-1"><span class="text-muted"><?php echo __('Cultural property') ?>:</span> <?php echo esc_entities($s['cultural_property_status']) ?></div>
            <?php endif ?>
            <?php if (!empty($s['provenance_summary'])): ?><hr><p class="mb-0"><?php echo nl2br(esc_entities($s['provenance_summary'])) ?></p><?php endif ?>
          </div>
        </div>
      </div>
    </div>

    <script type="application/json" id="prov-graph-data"<?php echo $nonce ?>><?php echo json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="/plugins/ahgRicExplorerPlugin/web/js/cytoscape.min.js"></script>
    <script src="/plugins/ahgRicExplorerPlugin/web/js/provenance-graph.js"></script>
    <script<?php echo $nonce ?>>window.AhgProvenanceGraph && window.AhgProvenanceGraph.init('prov-cy', 'prov-graph-data');</script>
    <?php endif ?>
  <?php endif ?>
</div>
