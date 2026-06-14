<?php decorate_with('layout_1col'); ?>
<?php slot('title'); ?>
  <h1 class="h3"><i class="fas fa-diagram-project me-2"></i><?php echo __('System map'); ?></h1>
<?php end_slot(); ?>
<?php $n = sfConfig::get('csp_nonce', ''); $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : ''; ?>

<p class="text-muted"><?php echo __('Interactive map of the enabled plugins, grouped by category. Click a plugin for details.'); ?></p>

<div class="d-flex gap-3">
  <div id="cy" class="border rounded flex-grow-1"></div>
  <div id="sm-detail" class="border rounded p-3" style="width:280px;min-width:280px;">
    <p class="text-muted mb-0"><?php echo __('Select a node to see details.'); ?></p>
  </div>
</div>

<style <?php echo $nonceAttr; ?>>
  #cy { height: 640px; background: #fff; }
  #sm-detail h6 { word-break: break-word; }
</style>

<script src="/plugins/ahgThemeB5Plugin/web/js/cytoscape.min.js"></script>
<script <?php echo $nonceAttr; ?>>
(function () {
  var detail = document.getElementById('sm-detail');
  fetch('/index.php/help/api/system-map')
    .then(function (r) { return r.json(); })
    .then(function (d) {
      var els = (d.nodes || []).concat(d.edges || []);
      if (typeof cytoscape === 'undefined' || !els.length) {
        document.getElementById('cy').innerHTML = '<p class="text-muted p-3">No data.</p>';
        return;
      }
      var cy = cytoscape({
        container: document.getElementById('cy'),
        elements: els,
        style: [
          { selector: 'node', style: { 'label': 'data(label)', 'font-size': '10px', 'text-valign': 'center', 'color': '#fff', 'text-outline-width': 1, 'text-outline-color': '#333' } },
          { selector: 'node[type="root"]', style: { 'background-color': '#212529', 'shape': 'round-rectangle', 'width': 110, 'height': 36, 'font-size': '13px' } },
          { selector: 'node[type="category"]', style: { 'background-color': '#0d6efd', 'shape': 'round-rectangle', 'width': 'label', 'padding': '6px' } },
          { selector: 'node[type="plugin"]', style: { 'background-color': '#6c757d', 'width': 14, 'height': 14, 'text-outline-color': '#6c757d' } },
          { selector: 'node[type="plugin"][core = 1]', style: { 'background-color': '#fd7e14', 'border-width': 2, 'border-color': '#b35900' } },
          { selector: 'edge', style: { 'width': 1, 'line-color': '#ced4da', 'curve-style': 'bezier', 'target-arrow-shape': 'none' } }
        ],
        layout: { name: 'breadthfirst', directed: true, roots: ['__root'], spacingFactor: 1.1, padding: 10 }
      });
      cy.on('tap', 'node', function (evt) {
        var dt = evt.target.data();
        var html = '<h6>' + (dt.label || '') + '</h6>';
        html += '<div class="small text-muted mb-2">' + (dt.type || '') + (dt.core ? ' · core' : '') + '</div>';
        if (dt.desc) { html += '<p class="small mb-0">' + dt.desc.replace(/[<>]/g, '') + '</p>'; }
        detail.innerHTML = html;
      });
    })
    .catch(function () { document.getElementById('cy').innerHTML = '<p class="text-danger p-3">Failed to load map.</p>'; });
})();
</script>
