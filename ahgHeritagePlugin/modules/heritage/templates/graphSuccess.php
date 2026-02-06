<?php
/**
 * Knowledge Graph Visualization.
 *
 * Displays entity relationships using D3.js force-directed graph.
 *
 * @var array $graphData    Graph data with nodes and links
 * @var array $entityTypes  Available entity types for filtering
 * @var array $stats        Graph statistics
 */

use_helper('Heritage');
?>

<div class="heritage-graph-page py-4">
    <div class="container-xxl">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'heritage', 'action' => 'landing']); ?>">Heritage</a></li>
                        <li class="breadcrumb-item active">Knowledge Graph</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="h2 mb-0">Entity Relationship Graph</h1>
                    <div class="btn-group">
                        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-search me-1"></i> Search
                        </a>
                        <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'explore']); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-compass me-1"></i> Explore
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Row -->
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body py-2">
                        <div class="row g-2 align-items-center">
                            <!-- Entity Type Filter -->
                            <div class="col-auto">
                                <label class="form-label mb-0 small text-muted">Filter by type:</label>
                            </div>
                            <div class="col-auto">
                                <select id="entity-type-filter" class="form-select form-select-sm">
                                    <option value="">All Types</option>
                                    <?php foreach ($entityTypes as $type): ?>
                                    <option value="<?php echo esc_specialchars($type); ?>"><?php echo ucfirst(esc_specialchars($type)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Search -->
                            <div class="col-auto">
                                <input type="text" id="graph-search" class="form-control form-control-sm" placeholder="Search entities..." style="width: 180px;">
                            </div>

                            <!-- Min Occurrences -->
                            <div class="col-auto">
                                <label class="form-label mb-0 small text-muted">Min occurrences:</label>
                            </div>
                            <div class="col-auto">
                                <input type="number" id="min-occurrences" class="form-control form-control-sm" value="1" min="1" max="100" style="width: 70px;">
                            </div>

                            <!-- Refresh Button -->
                            <div class="col-auto">
                                <button id="refresh-graph" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Legend -->
                <div class="card shadow-sm">
                    <div class="card-body py-2">
                        <div class="d-flex flex-wrap gap-3 small">
                            <span><span class="badge rounded-pill" style="background-color: #4e79a7;">Person</span></span>
                            <span><span class="badge rounded-pill" style="background-color: #59a14f;">Organization</span></span>
                            <span><span class="badge rounded-pill" style="background-color: #e15759;">Place</span></span>
                            <span><span class="badge rounded-pill" style="background-color: #b07aa1;">Date</span></span>
                            <span><span class="badge rounded-pill" style="background-color: #76b7b2;">Event</span></span>
                            <span><span class="badge rounded-pill" style="background-color: #ff9da7;">Work</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graph Container -->
        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-0 position-relative">
                        <div id="graph-container" style="height: 600px; width: 100%;"></div>
                        <div id="graph-loading" class="position-absolute top-50 start-50 translate-middle" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="card shadow-sm mt-3">
                    <div class="card-body py-2">
                        <div class="row text-center small">
                            <div class="col">
                                <span class="text-muted">Nodes:</span>
                                <strong id="stat-nodes"><?php echo number_format($stats['total_nodes'] ?? 0); ?></strong>
                            </div>
                            <div class="col">
                                <span class="text-muted">Edges:</span>
                                <strong id="stat-edges"><?php echo number_format($stats['total_edges'] ?? 0); ?></strong>
                            </div>
                            <div class="col">
                                <span class="text-muted">Avg connections:</span>
                                <strong id="stat-avg-connections"><?php echo number_format($stats['avg_connections_per_node'] ?? 0, 1); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entity Detail Panel -->
            <div class="col-md-4">
                <div id="entity-panel" class="card shadow-sm" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" id="entity-panel-title">Entity Details</h5>
                        <button type="button" class="btn-close" id="close-entity-panel" aria-label="Close"></button>
                    </div>
                    <div class="card-body">
                        <div id="entity-panel-content">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Instructions when no entity selected -->
                <div id="entity-instructions" class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-diagram-3 fs-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted mb-0">Click on a node to see entity details and related records.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- D3.js -->
<script src="https://d3js.org/d3.v7.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    'use strict';

    // Color scale for entity types
    const colorScale = {
        person: '#4e79a7',
        organization: '#59a14f',
        place: '#e15759',
        date: '#b07aa1',
        event: '#76b7b2',
        work: '#ff9da7',
        concept: '#edc949'
    };

    // Graph data URL
    const dataUrl = '<?php echo url_for(['module' => 'heritage', 'action' => 'graphData']); ?>';

    // SVG dimensions
    const container = document.getElementById('graph-container');
    let width = container.clientWidth;
    let height = container.clientHeight;

    // Create SVG
    const svg = d3.select('#graph-container')
        .append('svg')
        .attr('width', '100%')
        .attr('height', '100%')
        .attr('viewBox', [0, 0, width, height]);

    // Add zoom behavior
    const g = svg.append('g');
    svg.call(d3.zoom()
        .extent([[0, 0], [width, height]])
        .scaleExtent([0.1, 4])
        .on('zoom', (event) => g.attr('transform', event.transform)));

    // Simulation
    let simulation = null;

    // Load and render graph
    function loadGraph() {
        const entityType = document.getElementById('entity-type-filter').value;
        const search = document.getElementById('graph-search').value;
        const minOccurrences = document.getElementById('min-occurrences').value;

        document.getElementById('graph-loading').style.display = 'block';

        let url = dataUrl + '?limit=100';
        if (entityType) url += '&entity_type=' + encodeURIComponent(entityType);
        if (search) url += '&search=' + encodeURIComponent(search);
        if (minOccurrences > 1) url += '&min_occurrences=' + encodeURIComponent(minOccurrences);

        fetch(url, {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('graph-loading').style.display = 'none';
            if (data.success !== false) {
                renderGraph(data);
                updateStats(data.stats);
            }
        })
        .catch(error => {
            document.getElementById('graph-loading').style.display = 'none';
            console.error('Error loading graph:', error);
        });
    }

    function renderGraph(data) {
        // Clear existing
        g.selectAll('*').remove();

        if (!data.nodes || data.nodes.length === 0) {
            g.append('text')
                .attr('x', width / 2)
                .attr('y', height / 2)
                .attr('text-anchor', 'middle')
                .attr('fill', '#666')
                .text('No entities found. Try adjusting your filters.');
            return;
        }

        // Create simulation
        simulation = d3.forceSimulation(data.nodes)
            .force('link', d3.forceLink(data.links).id(d => d.id).distance(100))
            .force('charge', d3.forceManyBody().strength(-300))
            .force('center', d3.forceCenter(width / 2, height / 2))
            .force('collision', d3.forceCollide().radius(d => d.size + 5));

        // Draw links
        const link = g.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(data.links)
            .enter()
            .append('line')
            .attr('stroke', '#999')
            .attr('stroke-opacity', 0.6)
            .attr('stroke-width', d => Math.min(5, d.weight || 1));

        // Draw nodes
        const node = g.append('g')
            .attr('class', 'nodes')
            .selectAll('g')
            .data(data.nodes)
            .enter()
            .append('g')
            .attr('class', 'node')
            .style('cursor', 'pointer')
            .call(d3.drag()
                .on('start', dragstarted)
                .on('drag', dragged)
                .on('end', dragended));

        // Node circles
        node.append('circle')
            .attr('r', d => d.size || 10)
            .attr('fill', d => colorScale[d.type] || '#999')
            .attr('stroke', '#fff')
            .attr('stroke-width', 2);

        // Node labels
        node.append('text')
            .text(d => d.label.length > 20 ? d.label.substring(0, 20) + '...' : d.label)
            .attr('x', d => (d.size || 10) + 5)
            .attr('y', 4)
            .attr('font-size', '11px')
            .attr('fill', '#333');

        // Node click handler
        node.on('click', (event, d) => {
            event.stopPropagation();
            showEntityPanel(d);
        });

        // Tooltip
        node.append('title')
            .text(d => `${d.label}\nType: ${d.type}\nOccurrences: ${d.occurrences}`);

        // Simulation tick
        simulation.on('tick', () => {
            link
                .attr('x1', d => d.source.x)
                .attr('y1', d => d.source.y)
                .attr('x2', d => d.target.x)
                .attr('y2', d => d.target.y);

            node.attr('transform', d => `translate(${d.x},${d.y})`);
        });
    }

    function dragstarted(event, d) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
    }

    function dragged(event, d) {
        d.fx = event.x;
        d.fy = event.y;
    }

    function dragended(event, d) {
        if (!event.active) simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
    }

    function showEntityPanel(entity) {
        document.getElementById('entity-instructions').style.display = 'none';
        document.getElementById('entity-panel').style.display = 'block';
        document.getElementById('entity-panel-title').textContent = entity.label;

        const content = document.getElementById('entity-panel-content');
        content.innerHTML = `
            <div class="mb-3">
                <span class="badge" style="background-color: ${colorScale[entity.type] || '#999'};">${entity.type}</span>
                <span class="badge bg-light text-dark ms-1">${entity.occurrences} occurrences</span>
            </div>
            <dl class="row small mb-3">
                <dt class="col-5 text-muted">Confidence:</dt>
                <dd class="col-7">${(entity.confidence * 100).toFixed(0)}%</dd>
                ${entity.actor_id ? `<dt class="col-5 text-muted">Linked Actor:</dt><dd class="col-7"><a href="/actor/${entity.actor_id}">#${entity.actor_id}</a></dd>` : ''}
                ${entity.term_id ? `<dt class="col-5 text-muted">Linked Term:</dt><dd class="col-7">#${entity.term_id}</dd>` : ''}
            </dl>
            <div class="d-grid gap-2">
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'search']); ?>?ner_${entity.type}[]=${encodeURIComponent(entity.label)}" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i> View Records
                </a>
                <a href="<?php echo url_for(['module' => 'heritage', 'action' => 'entity']); ?>/${entity.type}/${encodeURIComponent(entity.label)}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-info-circle me-1"></i> Entity Details
                </a>
            </div>
        `;
    }

    function updateStats(stats) {
        if (stats) {
            document.getElementById('stat-nodes').textContent = stats.total_nodes || 0;
            document.getElementById('stat-edges').textContent = stats.total_links || 0;
        }
    }

    // Event listeners
    document.getElementById('refresh-graph').addEventListener('click', loadGraph);
    document.getElementById('entity-type-filter').addEventListener('change', loadGraph);
    document.getElementById('close-entity-panel').addEventListener('click', () => {
        document.getElementById('entity-panel').style.display = 'none';
        document.getElementById('entity-instructions').style.display = 'block';
    });

    let searchTimeout;
    document.getElementById('graph-search').addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(loadGraph, 500);
    });

    document.getElementById('min-occurrences').addEventListener('change', loadGraph);

    // Handle window resize
    window.addEventListener('resize', () => {
        width = container.clientWidth;
        height = container.clientHeight;
        svg.attr('viewBox', [0, 0, width, height]);
        if (simulation) {
            simulation.force('center', d3.forceCenter(width / 2, height / 2));
            simulation.alpha(0.3).restart();
        }
    });

    // Initial load
    loadGraph();
})();
</script>

<style <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
.heritage-graph-page .node:hover circle {
    stroke: #333;
    stroke-width: 3px;
}
.heritage-graph-page .links line {
    pointer-events: none;
}
</style>
