/**
 * RiC Explorer Plugin JavaScript
 * Graph visualization using Cytoscape.js
 */

(function() {
    'use strict';

    // Color scheme matching RiC entity types
    var COLORS = {
        RecordSet: '#4ecdc4',
        Record: '#45b7d1',
        RecordPart: '#96ceb4',
        Person: '#dc3545',
        CorporateBody: '#ffc107',
        Family: '#e83e8c',
        Production: '#6f42c1',
        Accumulation: '#5f27cd',
        default: '#6c757d'
    };

    // Load Cytoscape.js dynamically
    function loadCytoscape(callback) {
        if (typeof cytoscape !== 'undefined') {
            callback();
            return;
        }

        var script = document.createElement('script');
        script.src = '/plugins/arRicExplorerPlugin/js/cytoscape.min.js';
        script.onload = callback;
        document.head.appendChild(script);
    }

    // Initialize the mini graph in the panel
    function initMiniGraph() {
        var container = document.getElementById('ric-graph-container');
        if (!container || !window.ricGraphData) {
            return;
        }

        loadCytoscape(function() {
            var elements = [];

            // Add nodes
            window.ricGraphData.nodes.forEach(function(node) {
                elements.push({
                    data: {
                        id: node.id,
                        label: node.label ? node.label.substring(0, 25) : 'Unknown',
                        type: node.type,
                        color: COLORS[node.type] || COLORS.default,
                        central: node.central || false
                    }
                });
            });

            // Add edges
            window.ricGraphData.edges.forEach(function(edge) {
                elements.push({
                    data: {
                        id: edge.source + '-' + edge.label + '-' + edge.target,
                        source: edge.source,
                        target: edge.target,
                        label: edge.label
                    }
                });
            });

            var cy = cytoscape({
                container: container,
                elements: elements,
                style: [
                    {
                        selector: 'node',
                        style: {
                            'background-color': 'data(color)',
                            'label': 'data(label)',
                            'color': '#fff',
                            'font-size': '9px',
                            'text-valign': 'bottom',
                            'text-margin-y': '4px',
                            'width': '25px',
                            'height': '25px',
                            'text-wrap': 'ellipsis',
                            'text-max-width': '80px'
                        }
                    },
                    {
                        selector: 'node[?central]',
                        style: {
                            'width': '35px',
                            'height': '35px',
                            'border-width': '3px',
                            'border-color': '#fff',
                            'font-weight': 'bold'
                        }
                    },
                    {
                        selector: 'edge',
                        style: {
                            'width': 1,
                            'line-color': '#555',
                            'target-arrow-color': '#555',
                            'target-arrow-shape': 'triangle',
                            'curve-style': 'bezier',
                            'label': 'data(label)',
                            'font-size': '7px',
                            'color': '#888',
                            'text-rotation': 'autorotate'
                        }
                    }
                ],
                layout: {
                    name: 'cose',
                    animate: false,
                    nodeRepulsion: 5000,
                    idealEdgeLength: 80,
                    padding: 20
                },
                userZoomingEnabled: true,
                userPanningEnabled: true,
                boxSelectionEnabled: false
            });

            // Center on the main node
            var centralNode = cy.nodes('[?central]');
            if (centralNode.length) {
                cy.center(centralNode);
            }

            // Click handler for nodes
            cy.on('tap', 'node', function(evt) {
                var nodeData = evt.target.data();
                showNodeInfo(nodeData);
            });
        });
    }

    // Show node information tooltip/popup
    function showNodeInfo(nodeData) {
        // For now, just log - could show a tooltip
        console.log('RiC Node:', nodeData);
    }

    // Open full explorer modal
    function openFullExplorer() {
        var modal = document.createElement('div');
        modal.className = 'ric-modal active';
        modal.innerHTML = [
            '<div class="ric-modal-content">',
            '  <div class="ric-modal-header">',
            '    <h3>RiC Explorer</h3>',
            '    <button class="ric-modal-close">&times;</button>',
            '  </div>',
            '  <div class="ric-modal-body">',
            '    <iframe src="' + (window.ricExplorerUrl || '/ric/') + '?focus=' + encodeURIComponent(window.ricCurrentUri || '') + '" style="width:100%;height:100%;border:none;"></iframe>',
            '  </div>',
            '</div>'
        ].join('\n');

        document.body.appendChild(modal);

        // Close handlers
        modal.querySelector('.ric-modal-close').addEventListener('click', function() {
            modal.remove();
        });

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });

        // Escape key
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', escHandler);
            }
        });
    }

    // Initialize when DOM is ready
    function init() {
        // Initialize mini graph
        initMiniGraph();

        // Bind expand button
        var expandBtn = document.getElementById('ric-expand-graph');
        if (expandBtn) {
            expandBtn.addEventListener('click', openFullExplorer);
        }
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
