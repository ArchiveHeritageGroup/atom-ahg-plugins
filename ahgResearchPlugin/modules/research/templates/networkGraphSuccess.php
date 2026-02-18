<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Network Graph</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Network Graph</h1>
    <div class="d-flex gap-2">
        <select id="filterType" class="form-select form-select-sm" style="width:auto;">
            <option value="">All Types</option>
            <option value="biographical">Biographical</option>
            <option value="chronological">Chronological</option>
            <option value="spatial">Spatial</option>
            <option value="relational">Relational</option>
            <option value="attributive">Attributive</option>
        </select>
        <select id="filterStatus" class="form-select form-select-sm" style="width:auto;">
            <option value="">All Statuses</option>
            <option value="proposed">Proposed</option>
            <option value="verified">Verified</option>
            <option value="disputed">Disputed</option>
        </select>
        <input type="text" id="nodeSearch" class="form-control form-control-sm" style="width:180px;" placeholder="Search nodes...">
        <div class="btn-group btn-group-sm">
            <a href="<?php echo url_for(['module' => 'research', 'action' => 'exportGraphGEXF', 'project_id' => $projectId]); ?>" class="btn btn-outline-secondary" title="Export GEXF (Gephi)"><i class="fas fa-download me-1"></i>GEXF</a>
            <button id="exportGraphML" class="btn btn-outline-secondary" title="Export GraphML"><i class="fas fa-download me-1"></i>GraphML</button>
        </div>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'assertions', 'project_id' => $projectId]); ?>" class="btn btn-sm btn-outline-primary">List View</a>
    </div>
</div>

<!-- Legend -->
<div class="card mb-3">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <small class="text-muted me-1">Node types:</small>
        <span><svg width="12" height="12"><circle cx="6" cy="6" r="5" fill="#4e79a7"/></svg> Person</span>
        <span><svg width="12" height="12"><circle cx="6" cy="6" r="5" fill="#e15759"/></svg> Organization</span>
        <span><svg width="12" height="12"><circle cx="6" cy="6" r="5" fill="#f28e2c"/></svg> Event</span>
        <span><svg width="12" height="12"><circle cx="6" cy="6" r="5" fill="#59a14f"/></svg> Place</span>
        <span><svg width="12" height="12"><circle cx="6" cy="6" r="5" fill="#76b7b2"/></svg> Object</span>
    </div>
</div>

<div class="row">
    <!-- Graph area -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0 position-relative">
                <div id="graphContainer" style="width:100%; height:600px; background:#fafafa;"></div>
                <div class="position-absolute bottom-0 end-0 p-2 d-flex gap-1">
                    <button id="zoomIn" class="btn btn-sm btn-light border" title="Zoom in"><i class="fas fa-plus"></i></button>
                    <button id="zoomOut" class="btn btn-sm btn-light border" title="Zoom out"><i class="fas fa-minus"></i></button>
                    <button id="zoomReset" class="btn btn-sm btn-light border" title="Reset view"><i class="fas fa-expand"></i></button>
                </div>
            </div>
        </div>
    </div>
    <!-- Detail panel -->
    <div class="col-lg-4">
        <div class="card" id="detailPanel">
            <div class="card-header"><h5 class="mb-0">Node Details</h5></div>
            <div class="card-body" id="detailContent">
                <p class="text-muted mb-0">Click a node to see details.</p>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?> src="https://d3js.org/d3.v7.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var projectId = <?php echo (int) $projectId; ?>;
    var currentZoom = d3.zoomIdentity;
    var svg, gAll, simulation, nodeElements, linkElements, labelElements;
    var allData = {nodes:[], edges:[]};

    var typeColors = {
        actor: '#4e79a7', person: '#4e79a7',
        organization: '#e15759', corporate_body: '#e15759',
        event: '#f28e2c',
        place: '#59a14f',
        information_object: '#76b7b2', object: '#76b7b2'
    };

    function loadGraph() {
        var url = '/research/network-graph-data?project_id=' + projectId;
        var filterType = document.getElementById('filterType').value;
        var filterStatus = document.getElementById('filterStatus').value;
        if (filterType) url += '&assertion_type=' + filterType;
        if (filterStatus) url += '&status=' + filterStatus;

        fetch(url).then(function(r){return r.json();}).then(function(data) {
            allData = data;
            renderGraph(data);
        });
    }

    function renderGraph(data) {
        d3.select('#graphContainer').selectAll('*').remove();
        if (!data.nodes || data.nodes.length === 0) {
            d3.select('#graphContainer').append('p').attr('class','text-muted p-4').text('No data to display.');
            return;
        }

        var width = document.getElementById('graphContainer').clientWidth;
        var height = 600;
        svg = d3.select('#graphContainer').append('svg').attr('width', width).attr('height', height);
        gAll = svg.append('g');

        var zoom = d3.zoom().scaleExtent([0.1, 5]).on('zoom', function(event) {
            currentZoom = event.transform;
            gAll.attr('transform', event.transform);
        });
        svg.call(zoom);

        document.getElementById('zoomIn').onclick = function() { svg.transition().call(zoom.scaleBy, 1.3); };
        document.getElementById('zoomOut').onclick = function() { svg.transition().call(zoom.scaleBy, 0.7); };
        document.getElementById('zoomReset').onclick = function() { svg.transition().call(zoom.transform, d3.zoomIdentity); };

        simulation = d3.forceSimulation(data.nodes)
            .force('link', d3.forceLink(data.edges).id(function(d){return d.id;}).distance(120))
            .force('charge', d3.forceManyBody().strength(-250))
            .force('center', d3.forceCenter(width/2, height/2))
            .force('collision', d3.forceCollide().radius(20));

        // Edge labels
        linkElements = gAll.append('g').selectAll('line').data(data.edges).enter().append('line')
            .attr('stroke','#999').attr('stroke-opacity',0.5).attr('stroke-width', 1.5);

        var edgeLabels = gAll.append('g').selectAll('text').data(data.edges).enter().append('text')
            .text(function(d){return d.label || '';}).attr('font-size','8px').attr('fill','#888').attr('text-anchor','middle');

        nodeElements = gAll.append('g').selectAll('circle').data(data.nodes).enter().append('circle')
            .attr('r', function(d){return Math.max(6, Math.min(16, 6 + (d.connections || 0)));})
            .attr('fill', function(d){return typeColors[d.type] || '#76b7b2';})
            .attr('stroke', '#fff').attr('stroke-width', 1.5)
            .style('cursor','pointer')
            .call(d3.drag().on('start', dragStart).on('drag', dragged).on('end', dragEnd));

        labelElements = gAll.append('g').selectAll('text').data(data.nodes).enter().append('text')
            .text(function(d){return d.label;}).attr('font-size','10px').attr('dx',14).attr('dy',4);

        nodeElements.on('click', function(event, d) {
            nodeElements.attr('stroke','#fff').attr('stroke-width',1.5);
            d3.select(this).attr('stroke','#333').attr('stroke-width',3);
            showNodeDetail(d);
        });

        simulation.on('tick', function() {
            linkElements.attr('x1',function(d){return d.source.x;}).attr('y1',function(d){return d.source.y;})
                .attr('x2',function(d){return d.target.x;}).attr('y2',function(d){return d.target.y;});
            edgeLabels.attr('x',function(d){return (d.source.x+d.target.x)/2;}).attr('y',function(d){return (d.source.y+d.target.y)/2;});
            nodeElements.attr('cx',function(d){return d.x;}).attr('cy',function(d){return d.y;});
            labelElements.attr('x',function(d){return d.x;}).attr('y',function(d){return d.y;});
        });
    }

    function dragStart(event, d) { if (!event.active) simulation.alphaTarget(0.3).restart(); d.fx = d.x; d.fy = d.y; }
    function dragged(event, d) { d.fx = event.x; d.fy = event.y; }
    function dragEnd(event, d) { if (!event.active) simulation.alphaTarget(0); d.fx = null; d.fy = null; }

    function showNodeDetail(node) {
        var connections = allData.edges.filter(function(e) {
            var sid = typeof e.source === 'object' ? e.source.id : e.source;
            var tid = typeof e.target === 'object' ? e.target.id : e.target;
            return sid === node.id || tid === node.id;
        });
        var html = '<h6>' + escHtml(node.label) + '</h6>';
        html += '<p class="mb-1"><span class="badge" style="background:' + (typeColors[node.type] || '#76b7b2') + '">' + escHtml(node.type || 'unknown') + '</span></p>';
        html += '<p class="mb-2 text-muted small">ID: ' + node.id + ' | Connections: ' + connections.length + '</p>';
        if (connections.length > 0) {
            html += '<hr><h6 class="small">Connections</h6><ul class="list-unstyled small">';
            connections.forEach(function(e) {
                var other = (typeof e.source === 'object' ? e.source.id : e.source) === node.id
                    ? (typeof e.target === 'object' ? e.target : allData.nodes.find(function(n){return n.id===e.target;}))
                    : (typeof e.source === 'object' ? e.source : allData.nodes.find(function(n){return n.id===e.source;}));
                if (other) html += '<li><span class="badge bg-light text-dark">' + escHtml(e.label || 'related') + '</span> ' + escHtml(other.label || 'Node') + '</li>';
            });
            html += '</ul>';
        }
        if (node.entity_url) html += '<a href="' + node.entity_url + '" class="btn btn-sm btn-outline-primary mt-2">View Entity</a>';
        document.getElementById('detailContent').innerHTML = html;
    }

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    // Search nodes
    document.getElementById('nodeSearch').addEventListener('input', function() {
        var term = this.value.toLowerCase();
        if (!nodeElements) return;
        nodeElements.style('opacity', function(d) {
            return !term || d.label.toLowerCase().indexOf(term) >= 0 ? 1 : 0.15;
        });
        labelElements.style('opacity', function(d) {
            return !term || d.label.toLowerCase().indexOf(term) >= 0 ? 1 : 0.15;
        });
    });

    // Export GraphML
    document.getElementById('exportGraphML').addEventListener('click', function() {
        window.location.href = '/research/network-graph/' + projectId + '/export/graphml';
    });

    loadGraph();
    document.getElementById('filterType').addEventListener('change', loadGraph);
    document.getElementById('filterStatus').addEventListener('change', loadGraph);
});
</script>
