<?php decorate_with('layout_2col') ?>
<?php slot('sidebar') ?>
<?php include_partial('research/researchSidebar', ['active' => $sidebarActive, 'unreadNotifications' => $unreadNotifications ?? 0]) ?>
<?php end_slot() ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'dashboard']); ?>">Research</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'research', 'action' => 'viewProject', 'id' => $project->id]); ?>"><?php echo htmlspecialchars($project->title); ?></a></li>
        <li class="breadcrumb-item active">Map</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Map Builder</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPointModal"><i class="fas fa-map-marker-alt me-1"></i> Add Point</button>
</div>

<div class="card mb-4">
    <div class="card-body p-0">
        <div id="map" style="width:100%; height:500px;"></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="mb-0">Points (<?php echo count($points); ?>)</h5></div>
    <div class="card-body">
        <?php if (empty($points)): ?>
            <p class="text-muted">No map points yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Label</th><th>Place</th><th>Lat</th><th>Lng</th></tr></thead>
                <tbody>
                <?php foreach ($points as $pt): ?>
                    <tr><td><?php echo htmlspecialchars($pt->label); ?></td><td><?php echo htmlspecialchars($pt->place_name ?? ''); ?></td><td><?php echo $pt->latitude; ?></td><td><?php echo $pt->longitude; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?> src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('map').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(map);
    var points = <?php echo json_encode(array_map(function($pt) {
        return ['lat' => (float) $pt->latitude, 'lng' => (float) $pt->longitude, 'label' => $pt->label, 'place' => $pt->place_name ?? ''];
    }, $points)); ?>;
    var bounds = [];
    points.forEach(function(pt) {
        L.marker([pt.lat, pt.lng]).addTo(map).bindPopup('<strong>' + pt.label + '</strong>' + (pt.place ? '<br>' + pt.place : ''));
        bounds.push([pt.lat, pt.lng]);
    });
    if (bounds.length) map.fitBounds(bounds, {padding: [50, 50]});
});
</script>

<div class="modal fade" id="addPointModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Map Point</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Label</label><input type="text" id="pointLabel" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Place Name</label><input type="text" id="pointPlace" class="form-control"></div>
                <div class="row mb-3"><div class="col"><label class="form-label">Latitude</label><input type="number" step="any" id="pointLat" class="form-control" required></div><div class="col"><label class="form-label">Longitude</label><input type="number" step="any" id="pointLng" class="form-control" required></div></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea id="pointDesc" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="savePoint">Save</button></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.getElementById('savePoint')?.addEventListener('click', function() {
    fetch('/research/map-point-api', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({form_action:'create', project_id:<?php echo (int) $projectId; ?>, label:document.getElementById('pointLabel').value, place_name:document.getElementById('pointPlace').value, latitude:parseFloat(document.getElementById('pointLat').value), longitude:parseFloat(document.getElementById('pointLng').value), description:document.getElementById('pointDesc').value})
    }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
});
</script>
