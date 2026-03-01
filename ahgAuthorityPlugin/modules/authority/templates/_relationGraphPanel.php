<?php
/**
 * Embeddable panel: Cytoscape.js agent-to-agent graph for actor view pages.
 * Usage: include_partial('authority/relationGraphPanel', ['actorId' => $actorId])
 */
$actorId = $actorId ?? 0;
if (!$actorId) return;
?>

<div class="card mb-3 authority-graph-panel">
  <div class="card-header py-2 d-flex justify-content-between">
    <span><i class="fas fa-project-diagram me-1"></i><?php echo __('Relationship Graph'); ?></span>
    <div>
      <select id="graph-depth" class="form-select form-select-sm d-inline-block" style="width:auto">
        <option value="1"><?php echo __('Depth 1'); ?></option>
        <option value="2"><?php echo __('Depth 2'); ?></option>
        <option value="3"><?php echo __('Depth 3'); ?></option>
      </select>
      <button class="btn btn-sm btn-outline-primary" id="btn-load-graph">
        <i class="fas fa-sync"></i>
      </button>
    </div>
  </div>
  <div class="card-body p-0">
    <div id="authority-graph" style="height:400px; background:#f8f9fa;"></div>
  </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
  var actorId = <?php echo (int) $actorId; ?>;

  function loadGraph() {
    var depth = document.getElementById('graph-depth').value;
    fetch('/api/authority/graph/' + actorId + '?depth=' + depth)
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (typeof cytoscape === 'undefined') {
          document.getElementById('authority-graph').innerHTML =
            '<div class="p-3 text-muted text-center">Cytoscape.js not loaded. Include it via CDN or ahgRicExplorerPlugin.</div>';
          return;
        }

        var elements = [];
        (data.nodes || []).forEach(function(n) { elements.push(n); });
        (data.edges || []).forEach(function(e) { elements.push(e); });

        cytoscape({
          container: document.getElementById('authority-graph'),
          elements: elements,
          style: [
            { selector: 'node', style: { 'label': 'data(label)', 'background-color': '#0d6efd', 'color': '#333', 'font-size': '11px', 'text-wrap': 'wrap', 'text-max-width': '100px' } },
            { selector: 'edge', style: { 'label': 'data(label)', 'curve-style': 'bezier', 'target-arrow-shape': 'triangle', 'font-size': '9px', 'line-color': '#adb5bd', 'target-arrow-color': '#adb5bd' } }
          ],
          layout: { name: 'cose', animate: true }
        });
      });
  }

  document.getElementById('btn-load-graph').addEventListener('click', loadGraph);
  loadGraph();
});
</script>
