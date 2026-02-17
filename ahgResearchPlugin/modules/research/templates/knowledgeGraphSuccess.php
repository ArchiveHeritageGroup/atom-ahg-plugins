<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Knowledge Graph</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Knowledge Graph</h1>
    <div class="d-flex gap-2">
        <select id="filterType" class="form-select form-select-sm" style="width:auto;"><option value="">All Types</option><option value="biographical">Biographical</option><option value="chronological">Chronological</option><option value="spatial">Spatial</option><option value="relational">Relational</option><option value="attributive">Attributive</option></select>
        <a href="<?php echo url_for(['module' => 'research', 'action' => 'assertions', 'project_id' => $project->id]); ?>" class="btn btn-sm btn-outline-primary">List View</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div id="graphContainer" style="width:100%; height:600px; background:#fafafa;"></div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?> src="https://d3js.org/d3.v7.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var projectId = <?php echo (int) $projectId; ?>;
    function loadGraph(filterType) {
        var url = '/research/knowledge-graph-data?project_id=' + projectId;
        if (filterType) url += '&assertion_type=' + filterType;
        fetch(url).then(r => r.json()).then(function(data) {
            d3.select('#graphContainer').selectAll('*').remove();
            if (!data.nodes || data.nodes.length === 0) {
                d3.select('#graphContainer').append('p').attr('class','text-muted p-4').text('No assertions to display.');
                return;
            }
            var width = document.getElementById('graphContainer').clientWidth;
            var height = 600;
            var svg = d3.select('#graphContainer').append('svg').attr('width', width).attr('height', height);
            var simulation = d3.forceSimulation(data.nodes)
                .force('link', d3.forceLink(data.edges).id(function(d){return d.id;}).distance(150))
                .force('charge', d3.forceManyBody().strength(-300))
                .force('center', d3.forceCenter(width/2, height/2));
            var link = svg.append('g').selectAll('line').data(data.edges).enter().append('line').attr('stroke','#999').attr('stroke-opacity',0.6);
            var node = svg.append('g').selectAll('circle').data(data.nodes).enter().append('circle').attr('r',8).attr('fill',function(d){
                var colors = {actor:'#4e79a7',information_object:'#f28e2c',place:'#59a14f',event:'#e15759'};
                return colors[d.type] || '#76b7b2';
            });
            var labels = svg.append('g').selectAll('text').data(data.nodes).enter().append('text').text(function(d){return d.label;}).attr('font-size','10px').attr('dx',12).attr('dy',4);
            simulation.on('tick', function(){
                link.attr('x1',function(d){return d.source.x;}).attr('y1',function(d){return d.source.y;}).attr('x2',function(d){return d.target.x;}).attr('y2',function(d){return d.target.y;});
                node.attr('cx',function(d){return d.x;}).attr('cy',function(d){return d.y;});
                labels.attr('x',function(d){return d.x;}).attr('y',function(d){return d.y;});
            });
        });
    }
    loadGraph('');
    document.getElementById('filterType').addEventListener('change', function(){ loadGraph(this.value); });
});
</script>
