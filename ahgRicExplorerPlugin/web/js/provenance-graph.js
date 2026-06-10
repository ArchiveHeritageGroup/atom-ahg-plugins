/* Provenance graph (#149 strand 3). Renders the relational chain-of-custody
 * graph with the bundled cytoscape. */
(function (w) {
  'use strict';

  function init(mountId, dataId) {
    var mount = document.getElementById(mountId);
    var dataEl = document.getElementById(dataId);
    if (!mount || !dataEl || typeof w.cytoscape !== 'function') { return; }

    var graph;
    try { graph = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }
    var nodes = graph.nodes || [], edges = graph.edges || [];

    var elements = [];
    nodes.forEach(function (nd) {
      elements.push({ data: { id: nd.id, label: nd.label || nd.id, ntype: nd.type || 'agent', verified: nd.verified ? 1 : 0 } });
    });
    edges.forEach(function (e, i) {
      elements.push({ data: { id: 'e' + i, source: e.source, target: e.target, label: e.label || '', kind: e.kind || 'event' } });
    });

    var cy = w.cytoscape({
      container: mount,
      elements: elements,
      style: [
        { selector: 'node', style: {
          'label': 'data(label)', 'font-size': '11px', 'text-wrap': 'wrap', 'text-max-width': '120px',
          'text-valign': 'center', 'text-halign': 'center', 'color': '#fff',
          'width': '46px', 'height': '46px', 'background-color': '#6c757d',
          'text-outline-width': 2, 'text-outline-color': '#6c757d'
        } },
        { selector: 'node[ntype = "record"]', style: { 'background-color': '#0d6efd', 'text-outline-color': '#0d6efd', 'shape': 'round-rectangle', 'width': '64px', 'height': '52px' } },
        { selector: 'node[ntype = "donor"]', style: { 'background-color': '#fd7e14', 'text-outline-color': '#fd7e14', 'shape': 'diamond' } },
        { selector: 'node[ntype = "agent"]', style: { 'background-color': '#198754', 'text-outline-color': '#198754' } },
        { selector: 'node[verified = 1]', style: { 'border-width': 3, 'border-color': '#ffc107' } },
        { selector: 'edge', style: {
          'label': 'data(label)', 'font-size': '9px', 'color': '#333',
          'width': 2, 'line-color': '#adb5bd', 'target-arrow-color': '#adb5bd',
          'target-arrow-shape': 'triangle', 'curve-style': 'bezier',
          'text-background-color': '#fff', 'text-background-opacity': 0.85, 'text-background-padding': '2px'
        } },
        { selector: 'edge[kind = "current"]', style: { 'line-style': 'dashed', 'line-color': '#0d6efd', 'target-arrow-color': '#0d6efd' } },
        { selector: 'edge[kind = "event"]', style: { 'line-color': '#495057', 'target-arrow-color': '#495057' } }
      ],
      layout: { name: 'breadthfirst', directed: true, padding: 20, spacingFactor: 1.2 },
      wheelSensitivity: 0.2
    });

    // Center after layout.
    cy.ready(function () { cy.fit(undefined, 30); });
  }

  w.AhgProvenanceGraph = { init: init };
})(window);
