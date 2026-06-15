<?php
/* Unified cross-domain knowledge graph (#150) — record ↔ creators / repository /
   subjects / related records / donor. */
$n = sfConfig::get('csp_nonce', '');
$nonce = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
?>
<div class="container-fluid py-4 knowledge-graph">
  <div class="d-flex flex-wrap align-items-baseline mb-3 gap-2">
    <h1 class="h3 mb-0 flex-grow-1"><i class="fas fa-share-nodes me-2"></i><?php echo __('Knowledge graph') ?></h1>
    <a href="<?php echo url_for(['module' => 'ricDashboard', 'action' => 'index']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('RiC dashboard') ?></a>
  </div>

  <?php if (empty($objectId)): ?>
    <p class="text-muted"><?php echo __('Pick a record to see its cross-domain graph — creators, repository, subjects and related records.') ?></p>
    <div class="card"><div class="card-body p-0">
      <?php if (empty($records)): ?>
        <div class="p-3 text-muted small"><?php echo __('No records found.') ?></div>
      <?php else: ?>
        <div class="table-responsive"><table class="table table-hover table-sm mb-0 align-middle">
          <thead class="table-light"><tr><th><?php echo __('Record') ?></th><th></th></tr></thead>
          <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?php echo esc_entities($r->title) ?> <small class="text-muted">#<?php echo (int) $r->io_id ?></small></td>
              <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo url_for(['module' => 'ricExplorer', 'action' => 'knowledgeGraph', 'id' => $r->io_id]) ?>"><i class="fas fa-share-nodes me-1"></i><?php echo __('Graph') ?></a></td>
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
      <a href="<?php echo url_for(['module' => 'ricExplorer', 'action' => 'knowledgeGraph']) ?>" class="btn btn-outline-secondary btn-sm"><?php echo __('All') ?></a>
    </div>
    <div class="mb-2 small text-muted">
      <span class="badge bg-secondary me-1"><?php echo (int) ($s['creators'] ?? 0) ?> <?php echo __('creators') ?></span>
      <span class="badge bg-info text-dark me-1"><?php echo (int) ($s['subjects'] ?? 0) ?> <?php echo __('subjects') ?></span>
      <span class="badge bg-success me-1"><?php echo (int) ($s['related'] ?? 0) ?> <?php echo __('related') ?></span>
    </div>
    <div id="kg-cy" style="height: 560px;" class="border rounded bg-light"></div>
    <div class="form-text"><?php echo __('Drag nodes to rearrange. Colours: record (dark), repository (blue), creator (orange), subject (teal), related record (green).') ?></div>

    <script type="application/json" id="kg-graph-data"<?php echo $nonce ?>><?php echo json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script src="/plugins/ahgRicExplorerPlugin/web/js/cytoscape.min.js"></script>
    <script<?php echo $nonce ?>>
    (function () {
      var raw = document.getElementById('kg-graph-data');
      if (!raw || typeof cytoscape === 'undefined') { return; }
      var g = JSON.parse(raw.textContent || '{}');
      var els = [];
      (g.nodes || []).forEach(function (nd) { els.push({ data: nd }); });
      (g.edges || []).forEach(function (ed) { els.push({ data: ed }); });
      var tone = { record: '#212529', repository: '#0d6efd', actor: '#fd7e14', term: '#20c997', related: '#198754', donor: '#6f42c1' };
      cytoscape({
        container: document.getElementById('kg-cy'),
        elements: els,
        style: [
          { selector: 'node', style: { 'label': 'data(label)', 'font-size': '9px', 'color': '#fff', 'text-outline-width': 2, 'text-outline-color': '#555', 'background-color': '#6c757d', 'width': 16, 'height': 16, 'text-wrap': 'ellipsis', 'text-max-width': '120px' } },
          { selector: 'node[type="record"]', style: { 'background-color': tone.record, 'width': 26, 'height': 26, 'font-size': '11px' } },
          { selector: 'node[type="repository"]', style: { 'background-color': tone.repository } },
          { selector: 'node[type="actor"]', style: { 'background-color': tone.actor, 'text-outline-color': tone.actor } },
          { selector: 'node[type="term"]', style: { 'background-color': tone.term, 'text-outline-color': '#157347', 'shape': 'round-rectangle' } },
          { selector: 'node[type="related"]', style: { 'background-color': tone.related, 'text-outline-color': '#146c43' } },
          { selector: 'node[type="donor"]', style: { 'background-color': tone.donor } },
          { selector: 'edge', style: { 'label': 'data(label)', 'font-size': '8px', 'color': '#666', 'width': 1, 'line-color': '#ced4da', 'curve-style': 'bezier', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#ced4da', 'text-rotation': 'autorotate' } }
        ],
        layout: { name: 'cose', animate: false, padding: 20, nodeRepulsion: 6000, idealEdgeLength: 90 }
      });
    })();
    </script>
  <?php endif ?>
</div>
