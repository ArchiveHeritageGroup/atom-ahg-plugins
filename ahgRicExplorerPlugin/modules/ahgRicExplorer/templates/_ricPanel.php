<?php
$resource = $sf_data->getRaw('resource');
$ricData = $sf_data->getRaw('ricData');

$graphData = isset($ricData['graphData']) ?
    (is_object($ricData['graphData']) && method_exists($ricData['graphData'], 'getRawValue') ?
        $ricData['graphData']->getRawValue() : (array)$ricData['graphData']) : ['nodes' => [], 'edges' => []];

if (!is_array($graphData)) $graphData = ['nodes' => [], 'edges' => []];

$panelId = 'ric-' . $resource->id;

// Extract entities from graph nodes by type
$creators = [];
$relatedRecords = [];
$events = [];
$places = [];
$corporateBodies = [];

if (!empty($graphData['nodes'])) {
    foreach ($graphData['nodes'] as $node) {
        $type = $node['type'] ?? '';
        $nodeData = [
            'id' => $node['id'] ?? '',
            'name' => $node['label'] ?? $node['name'] ?? '',
            'fullName' => $node['name'] ?? $node['label'] ?? '',
            'type' => $type,
            'identifier' => $node['identifier'] ?? '',
            'date' => $node['date'] ?? '',
            'dateStart' => $node['dateStart'] ?? '',
            'dateEnd' => $node['dateEnd'] ?? '',
            'description' => $node['description'] ?? '',
            'participant' => $node['participant'] ?? '',
            'role' => $node['role'] ?? ''
        ];
        
        switch ($type) {
            case 'Person':
            case 'Family':
                $creators[] = $nodeData;
                break;
            case 'CorporateBody':
                $corporateBodies[] = $nodeData;
                break;
            case 'Record':
            case 'RecordSet':
                if (($node['identifier'] ?? '') != $resource->identifier) {
                    $relatedRecords[] = $nodeData;
                }
                break;
            case 'Production':
            case 'Accumulation':
            case 'Activity':
                $events[] = $nodeData;
                break;
            case 'Place':
                $places[] = $nodeData;
                break;
        }
    }
}
?>

<section id="ric-explorer-panel" class="card mb-3">
  <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-2">
    <h6 class="mb-0">
      <i class="fas fa-project-diagram me-2"></i>RiC Explorer
    </h6>
    <div class="btn-group btn-group-sm">
      <a href="/ric/" target="_blank" class="btn btn-outline-light btn-sm" title="Open Full Explorer"><i class="fas fa-external-link-alt"></i></a>
      <button type="button" class="btn btn-light btn-sm ric-view-btn active" data-view="2d">2D</button>
      <button type="button" class="btn btn-outline-light btn-sm ric-view-btn" data-view="3d">3D</button>
      <button type="button" class="btn btn-outline-light btn-sm" id="ric-fullscreen-btn" title="Fullscreen">
        <i class="fas fa-expand"></i>
      </button>
    </div>
  </div>

  <div class="card-body p-2">
    <div id="ric-mini-graph-container" style="height: 300px; border: 1px solid #dee2e6; border-radius: 4px; position: relative; overflow: hidden;">
      <div id="ric-graph-2d" style="width: 100%; height: 100%;"></div>
      <div id="ric-graph-3d" style="width: 100%; height: 100%; display: none;"></div>
    </div>
    
    <!-- Accordion for RiC Data -->
    <div class="accordion mt-2" id="ricAccordion<?php echo $panelId; ?>">
      
      <!-- Persons/Families (Creators) -->
      <?php if (!empty($creators)): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-success text-white py-2" type="button" data-bs-toggle="collapse" data-bs-target="#creatorsCollapse<?php echo $panelId; ?>" aria-expanded="false">
            <i class="fas fa-users me-2"></i>Persons / Families
            <span class="badge bg-light text-dark ms-2"><?php echo count($creators); ?></span>
          </button>
        </h2>
        <div id="creatorsCollapse<?php echo $panelId; ?>" class="accordion-collapse collapse" data-bs-parent="#ricAccordion<?php echo $panelId; ?>">
          <div class="accordion-body py-2">
            <ul class="list-unstyled mb-0">
              <?php foreach ($creators as $creator): ?>
              <li class="mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-start">
                  <i class="fas fa-user me-2 mt-1" style="color: #dc3545;"></i>
                  <div class="flex-grow-1">
                    <strong><?php echo esc_specialchars($creator['fullName']); ?></strong>
                    <span class="badge bg-secondary ms-1"><?php echo esc_specialchars($creator['type']); ?></span>
                    <?php if (!empty($creator['role'])): ?>
                    <span class="badge bg-info ms-1"><?php echo esc_specialchars($creator['role']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($creator['date']) || !empty($creator['dateStart'])): ?>
                    <div class="text-muted small">
                      <i class="fas fa-calendar-alt me-1"></i>
                      <?php echo esc_specialchars($creator['date'] ?: ($creator['dateStart'] . ($creator['dateEnd'] ? ' - ' . $creator['dateEnd'] : ''))); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($creator['description'])): ?>
                    <div class="text-muted small mt-1"><?php echo esc_specialchars(substr($creator['description'], 0, 150)); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Corporate Bodies -->
      <?php if (!empty($corporateBodies)): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-success text-white py-2" type="button" data-bs-toggle="collapse" data-bs-target="#corporateCollapse<?php echo $panelId; ?>" aria-expanded="false">
            <i class="fas fa-building me-2"></i>Corporate Bodies
            <span class="badge bg-light text-dark ms-2"><?php echo count($corporateBodies); ?></span>
          </button>
        </h2>
        <div id="corporateCollapse<?php echo $panelId; ?>" class="accordion-collapse collapse" data-bs-parent="#ricAccordion<?php echo $panelId; ?>">
          <div class="accordion-body py-2">
            <ul class="list-unstyled mb-0">
              <?php foreach ($corporateBodies as $corp): ?>
              <li class="mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-start">
                  <i class="fas fa-building me-2 mt-1" style="color: #ffc107;"></i>
                  <div class="flex-grow-1">
                    <strong><?php echo esc_specialchars($corp['fullName']); ?></strong>
                    <?php if (!empty($corp['role'])): ?>
                    <span class="badge bg-info ms-1"><?php echo esc_specialchars($corp['role']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($corp['date']) || !empty($corp['dateStart'])): ?>
                    <div class="text-muted small">
                      <i class="fas fa-calendar-alt me-1"></i>
                      <?php echo esc_specialchars($corp['date'] ?: ($corp['dateStart'] . ($corp['dateEnd'] ? ' - ' . $corp['dateEnd'] : ''))); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($corp['description'])): ?>
                    <div class="text-muted small mt-1"><?php echo esc_specialchars(substr($corp['description'], 0, 150)); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Events/Activities -->
      <?php if (!empty($events)): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-success text-white py-2" type="button" data-bs-toggle="collapse" data-bs-target="#eventsCollapse<?php echo $panelId; ?>" aria-expanded="false">
            <i class="fas fa-calendar-alt me-2"></i>Events / Activities
            <span class="badge bg-light text-dark ms-2"><?php echo count($events); ?></span>
          </button>
        </h2>
        <div id="eventsCollapse<?php echo $panelId; ?>" class="accordion-collapse collapse" data-bs-parent="#ricAccordion<?php echo $panelId; ?>">
          <div class="accordion-body py-2">
            <ul class="list-unstyled mb-0">
              <?php foreach ($events as $event): ?>
              <li class="mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-start">
                  <?php 
                  $iconClass = 'fa-clock';
                  $iconColor = '#6f42c1';
                  if (stripos($event['name'], 'Creation') !== false || $event['type'] === 'Production') {
                      $iconClass = 'fa-plus-circle'; $iconColor = '#28a745';
                  } elseif (stripos($event['name'], 'Accumulation') !== false) {
                      $iconClass = 'fa-layer-group'; $iconColor = '#17a2b8';
                  } elseif (stripos($event['name'], 'Transfer') !== false) {
                      $iconClass = 'fa-exchange-alt'; $iconColor = '#6f42c1';
                  } elseif (stripos($event['name'], 'Management') !== false) {
                      $iconClass = 'fa-cog'; $iconColor = '#ffc107';
                  } elseif (stripos($event['name'], 'Digitization') !== false) {
                      $iconClass = 'fa-camera'; $iconColor = '#e83e8c';
                  } elseif (stripos($event['name'], 'Preservation') !== false) {
                      $iconClass = 'fa-shield-alt'; $iconColor = '#6c757d';
                  } elseif (stripos($event['name'], 'Description') !== false) {
                      $iconClass = 'fa-file-alt'; $iconColor = '#20c997';
                  }
                  ?>
                  <i class="fas <?php echo $iconClass; ?> me-2 mt-1" style="color: <?php echo $iconColor; ?>;"></i>
                  <div class="flex-grow-1">
                    <strong><?php echo esc_specialchars($event['fullName']); ?></strong>
                    <span class="badge ms-1" style="background-color: <?php echo $iconColor; ?>;"><?php echo esc_specialchars($event['type']); ?></span>
                    
                    <?php if (!empty($event['date']) || !empty($event['dateStart'])): ?>
                    <div class="text-muted small">
                      <i class="fas fa-calendar me-1"></i>
                      <?php 
                      if (!empty($event['date'])) {
                          echo esc_specialchars($event['date']);
                      } elseif (!empty($event['dateStart'])) {
                          echo esc_specialchars($event['dateStart']);
                          if (!empty($event['dateEnd'])) {
                              echo ' — ' . esc_specialchars($event['dateEnd']);
                          }
                      }
                      ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['participant'])): ?>
                    <div class="text-muted small">
                      <i class="fas fa-user me-1"></i>
                      <?php echo esc_specialchars($event['participant']); ?>
                      <?php if (!empty($event['role'])): ?>
                      <span class="badge bg-secondary"><?php echo esc_specialchars($event['role']); ?></span>
                      <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($event['description'])): ?>
                    <div class="text-muted small mt-1 fst-italic">
                      <?php echo esc_specialchars(substr($event['description'], 0, 200)); ?>
                      <?php if (strlen($event['description']) > 200): ?>...<?php endif; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Places -->
      <?php if (!empty($places)): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-success text-white py-2" type="button" data-bs-toggle="collapse" data-bs-target="#placesCollapse<?php echo $panelId; ?>" aria-expanded="false">
            <i class="fas fa-map-marker-alt me-2"></i>Places
            <span class="badge bg-light text-dark ms-2"><?php echo count($places); ?></span>
          </button>
        </h2>
        <div id="placesCollapse<?php echo $panelId; ?>" class="accordion-collapse collapse" data-bs-parent="#ricAccordion<?php echo $panelId; ?>">
          <div class="accordion-body py-2">
            <ul class="list-unstyled mb-0">
              <?php foreach ($places as $place): ?>
              <li class="mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-start">
                  <i class="fas fa-map-marker-alt me-2 mt-1" style="color: #fd7e14;"></i>
                  <div class="flex-grow-1">
                    <strong><?php echo esc_specialchars($place['fullName']); ?></strong>
                    <?php if (!empty($place['description'])): ?>
                    <div class="text-muted small mt-1"><?php echo esc_specialchars(substr($place['description'], 0, 150)); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Related Records -->
      <?php if (!empty($relatedRecords)): ?>
      <div class="accordion-item">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed bg-success text-white py-2" type="button" data-bs-toggle="collapse" data-bs-target="#relatedCollapse<?php echo $panelId; ?>" aria-expanded="false">
            <i class="fas fa-archive me-2"></i>Related Records
            <span class="badge bg-light text-dark ms-2"><?php echo count($relatedRecords); ?></span>
          </button>
        </h2>
        <div id="relatedCollapse<?php echo $panelId; ?>" class="accordion-collapse collapse" data-bs-parent="#ricAccordion<?php echo $panelId; ?>">
          <div class="accordion-body py-2">
            <ul class="list-unstyled mb-0">
              <?php foreach ($relatedRecords as $related): ?>
              <li class="mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-start">
                  <i class="fas fa-file-alt me-2 mt-1" style="color: #17a2b8;"></i>
                  <div class="flex-grow-1">
                    <strong><?php echo esc_specialchars($related['fullName']); ?></strong>
                    <?php if (!empty($related['identifier'])): ?>
                    <span class="badge bg-secondary ms-1"><?php echo esc_specialchars($related['identifier']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($related['date']) || !empty($related['dateStart'])): ?>
                    <div class="text-muted small">
                      <i class="fas fa-calendar-alt me-1"></i>
                      <?php echo esc_specialchars($related['date'] ?: $related['dateStart']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($related['description'])): ?>
                    <div class="text-muted small mt-1"><?php echo esc_specialchars(substr($related['description'], 0, 150)); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
    </div><!-- /accordion -->
  </div>
</section>

<div id="ric-fullscreen-modal" class="ric-fullscreen-overlay" style="display: none;">
  <div class="ric-fullscreen-controls">
    <div class="btn-group btn-group-sm">
      <button type="button" class="btn btn-light ric-fs-view-btn active" data-view="2d">2D</button>
      <button type="button" class="btn btn-light ric-fs-view-btn" data-view="3d">3D</button>
    </div>
    <button type="button" class="btn btn-light btn-sm ms-2" id="ric-close-fullscreen">
      <i class="fas fa-times"></i> Close
    </button>
  </div>
  <div id="ric-fullscreen-graph" style="width: 100%; height: 100%;"></div>
</div>

<style>
.ric-fullscreen-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #1a1a2e; z-index: 9999; }
.ric-fullscreen-controls { position: absolute; top: 15px; right: 15px; z-index: 10001; }
.ric-view-btn.active, .ric-fs-view-btn.active { background-color: #198754 !important; border-color: #198754 !important; color: white !important; }
#ric-explorer-panel .accordion-button:not(.collapsed) { color: #fff; }
#ric-explorer-panel .accordion-button::after { filter: brightness(0) invert(1); }
#ric-explorer-panel .accordion-body { max-height: 400px; overflow-y: auto; }
#ric-explorer-panel .accordion-body li:last-child { border-bottom: none !important; margin-bottom: 0 !important; padding-bottom: 0 !important; }
</style>

<script src="https://unpkg.com/cytoscape@3.25.0/dist/cytoscape.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://unpkg.com/three-spritetext@1.6.5/dist/three-spritetext.min.js"></script>
<script src="https://unpkg.com/3d-force-graph@1.73.0/dist/3d-force-graph.min.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
  var graphData = <?php echo json_encode($graphData); ?>;
  var currentRecordId = '<?php echo $resource->id; ?>';
  var cy2d = null;
  var graph3d = null;
  var fsGraph = null;

  var typeColors = {
    'RecordSet': '#17a2b8', 'Record': '#17a2b8',
    'CorporateBody': '#ffc107', 'Person': '#dc3545', 'Family': '#dc3545',
    'Production': '#6f42c1', 'Accumulation': '#6f42c1', 'Activity': '#6f42c1',
    'Place': '#fd7e14', 'Thing': '#20c997', 'Instantiation': '#6c757d'
  };

  function getColor(type) { return typeColors[type] || '#6c757d'; }

  function init2DGraph(containerId) {
    var container = document.getElementById(containerId);
    if (!container || !graphData.nodes || graphData.nodes.length === 0) return null;
    var elements = [];
    graphData.nodes.forEach(function(node) {
      elements.push({ data: { id: node.id, label: node.label || node.id, type: node.type, color: getColor(node.type) } });
    });
    if (graphData.edges) {
      graphData.edges.forEach(function(edge, idx) {
        elements.push({ data: { id: 'e' + idx, source: edge.source, target: edge.target } });
      });
    }
    return cytoscape({
      container: container,
      elements: elements,
      style: [
        { selector: 'node', style: { 'background-color': 'data(color)', 'label': 'data(label)', 'font-size': '12px', 'text-valign': 'bottom', 'text-margin-y': '5px', 'width': '25px', 'height': '25px' } },
        { selector: 'edge', style: { 'width': 1, 'line-color': '#ccc', 'target-arrow-color': '#ccc', 'target-arrow-shape': 'triangle', 'curve-style': 'bezier' } },
        { selector: 'node[id = "' + currentRecordId + '"]', style: { 'border-width': '3px', 'border-color': '#000', 'width': '35px', 'height': '35px' } }
      ],
      layout: { name: 'cose', animate: false, padding: 20 }
    });
  }

  function init3DGraph(containerId, width, height) {
    var container = document.getElementById(containerId);
    if (!container || !graphData.nodes || graphData.nodes.length === 0) return null;
    
    if (!width) {
      var parent = container.parentElement;
      width = parent ? parent.clientWidth : 400;
    }
    if (!height) {
      var parent = container.parentElement;
      height = parent ? parent.clientHeight : 200;
    }
    if (width < 100) width = 400;
    if (height < 100) height = 200;

    var nodes = graphData.nodes.map(function(n) {
      return { id: n.id, name: n.label || n.id, color: getColor(n.type), val: 1 };
    });
    var links = (graphData.edges || []).map(function(e) {
      return { source: e.source, target: e.target };
    });

    try {
      var graph = ForceGraph3D()(container)
        .graphData({ nodes: nodes, links: links })
        .nodeColor('color')
        .nodeVal('val')
        .nodeLabel('name')
        .linkDirectionalParticles(1)
        .backgroundColor('#1a1a2e')
        .width(width)
        .height(height);
      
      if (typeof SpriteText !== 'undefined') {
        graph.nodeThreeObject(function(node) {
          var sprite = new SpriteText(node.name);
          sprite.color = '#ffffff';
          sprite.textHeight = 4;
          return sprite;
        });
        graph.nodeThreeObjectExtend(true);
      }
      
      return graph;
    } catch(e) {
      console.error('3D Graph error:', e);
      return null;
    }
  }

  function switchView(view) {
    var g2d = document.getElementById('ric-graph-2d');
    var g3d = document.getElementById('ric-graph-3d');
    if (view === '2d') {
      g2d.style.display = 'block';
      g3d.style.display = 'none';
    } else {
      g2d.style.display = 'none';
      g3d.style.display = 'block';
      requestAnimationFrame(function() {
        setTimeout(function() {
          if (!graph3d) graph3d = init3DGraph('ric-graph-3d');
        }, 50);
      });
    }
    document.querySelectorAll('.ric-view-btn').forEach(function(btn) {
      btn.classList.toggle('active', btn.dataset.view === view);
      btn.classList.toggle('btn-light', btn.dataset.view === view);
      btn.classList.toggle('btn-outline-light', btn.dataset.view !== view);
    });
  }

  function switchFullscreenView(view) {
    var fsContainer = document.getElementById('ric-fullscreen-graph');
    if (fsGraph) {
      if (typeof fsGraph.destroy === 'function') fsGraph.destroy();
      if (typeof fsGraph._destructor === 'function') fsGraph._destructor();
      fsGraph = null;
    }
    fsContainer.innerHTML = '';
    var graphDiv = document.createElement('div');
    graphDiv.id = 'ric-fs-graph';
    graphDiv.style.cssText = 'width:100%;height:100%;';
    fsContainer.appendChild(graphDiv);
    
    setTimeout(function() {
      if (view === '2d') {
        fsGraph = init2DGraph('ric-fs-graph');
        if (fsGraph) fsGraph.resize();
      } else {
        var w = fsContainer.clientWidth || window.innerWidth;
        var h = fsContainer.clientHeight || window.innerHeight;
        fsGraph = init3DGraph('ric-fs-graph', w, h);
      }
    }, 100);
    
    document.querySelectorAll('.ric-fs-view-btn').forEach(function(btn) {
      btn.classList.toggle('active', btn.dataset.view === view);
    });
  }

  function openFullscreen() {
    document.getElementById('ric-fullscreen-modal').style.display = 'block';
    document.querySelectorAll('.ric-fs-view-btn').forEach(function(btn) {
      btn.classList.toggle('active', btn.dataset.view === '2d');
    });
    setTimeout(function() { switchFullscreenView('2d'); }, 100);
  }

  function closeFullscreen() {
    document.getElementById('ric-fullscreen-modal').style.display = 'none';
    if (fsGraph) {
      if (typeof fsGraph.destroy === 'function') fsGraph.destroy();
      if (typeof fsGraph._destructor === 'function') fsGraph._destructor();
      fsGraph = null;
    }
    document.getElementById('ric-fullscreen-graph').innerHTML = '';
  }

  document.addEventListener('DOMContentLoaded', function() {
    cy2d = init2DGraph('ric-graph-2d');
    document.querySelectorAll('.ric-view-btn').forEach(function(btn) {
      btn.addEventListener('click', function() { switchView(this.dataset.view); });
    });
    document.querySelectorAll('.ric-fs-view-btn').forEach(function(btn) {
      btn.addEventListener('click', function() { switchFullscreenView(this.dataset.view); });
    });
    var fsBtn = document.getElementById('ric-fullscreen-btn');
    if (fsBtn) fsBtn.addEventListener('click', openFullscreen);
    var closeBtn = document.getElementById('ric-close-fullscreen');
    if (closeBtn) closeBtn.addEventListener('click', closeFullscreen);
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeFullscreen(); });
  });
})();
</script>
