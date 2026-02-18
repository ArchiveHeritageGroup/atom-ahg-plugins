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

<div class="alert alert-info alert-dismissible fade show" id="mapClickHint">
    <i class="fas fa-info-circle me-1"></i> Click on the map to set coordinates for a new point, then fill in the form.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
                <thead><tr><th>Label</th><th>Place</th><th>Lat</th><th>Lng</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($points as $pt): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pt->label); ?></td>
                        <td><?php echo htmlspecialchars($pt->place_name ?? ''); ?></td>
                        <td><?php echo $pt->latitude; ?></td>
                        <td><?php echo $pt->longitude; ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary edit-point-btn" data-id="<?php echo (int) $pt->id; ?>" data-label="<?php echo htmlspecialchars($pt->label); ?>" data-place="<?php echo htmlspecialchars($pt->place_name ?? ''); ?>" data-lat="<?php echo $pt->latitude; ?>" data-lng="<?php echo $pt->longitude; ?>" data-desc="<?php echo htmlspecialchars($pt->description ?? ''); ?>" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-outline-danger delete-point-btn" data-id="<?php echo (int) $pt->id; ?>" title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
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
    var projectId = <?php echo (int) $projectId; ?>;
    var map = L.map('map').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(map);

    var points = <?php echo json_encode(array_map(function($pt) {
        return ['id' => $pt->id, 'lat' => (float) $pt->latitude, 'lng' => (float) $pt->longitude, 'label' => $pt->label, 'place' => $pt->place_name ?? ''];
    }, $points)); ?>;
    var bounds = [];
    points.forEach(function(pt) {
        var popup = '<strong>' + escHtml(pt.label) + '</strong>' + (pt.place ? '<br>' + escHtml(pt.place) : '')
            + '<br><button class="btn btn-sm btn-outline-primary mt-1 popup-edit-btn" data-id="' + pt.id + '">Edit</button>'
            + ' <button class="btn btn-sm btn-outline-danger mt-1 popup-delete-btn" data-id="' + pt.id + '">Delete</button>';
        L.marker([pt.lat, pt.lng]).addTo(map).bindPopup(popup);
        bounds.push([pt.lat, pt.lng]);
    });
    if (bounds.length) map.fitBounds(bounds, {padding: [50, 50]});

    // Click on map to set coordinates
    map.on('click', function(e) {
        document.getElementById('pointLat').value = e.latlng.lat.toFixed(6);
        document.getElementById('pointLng').value = e.latlng.lng.toFixed(6);
        new bootstrap.Modal(document.getElementById('addPointModal')).show();
    });

    // Delegate popup button clicks
    map.on('popupopen', function() {
        document.querySelectorAll('.popup-edit-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var id = parseInt(this.dataset.id);
                var pt = points.find(function(p){return p.id==id;});
                if (pt) {
                    document.getElementById('editPointId').value = pt.id;
                    document.getElementById('editPointLabel').value = pt.label;
                    document.getElementById('editPointPlace').value = pt.place;
                    document.getElementById('editPointLat').value = pt.lat;
                    document.getElementById('editPointLng').value = pt.lng;
                    document.getElementById('editPointDesc').value = '';
                    new bootstrap.Modal(document.getElementById('editPointModal')).show();
                }
            });
        });
        document.querySelectorAll('.popup-delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (!confirm('Delete this point?')) return;
                fetch('/research/map-point-api', {
                    method:'POST', headers:{'Content-Type':'application/json'},
                    body: JSON.stringify({form_action:'delete', id:parseInt(this.dataset.id)})
                }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
            });
        });
    });

    function escHtml(s) { var d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
});
</script>

<!-- Add Point Modal -->
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

<!-- Edit Point Modal -->
<div class="modal fade" id="editPointModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Map Point</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="editPointId">
                <div class="mb-3"><label class="form-label">Label</label><input type="text" id="editPointLabel" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">Place Name</label><input type="text" id="editPointPlace" class="form-control"></div>
                <div class="row mb-3"><div class="col"><label class="form-label">Latitude</label><input type="number" step="any" id="editPointLat" class="form-control" required></div><div class="col"><label class="form-label">Longitude</label><input type="number" step="any" id="editPointLng" class="form-control" required></div></div>
                <div class="mb-3"><label class="form-label">Description</label><textarea id="editPointDesc" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary" id="updatePoint">Update</button></div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    var projectId = <?php echo (int) $projectId; ?>;

    // Create point
    document.getElementById('savePoint')?.addEventListener('click', function() {
        fetch('/research/map-point-api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({form_action:'create', project_id:projectId, label:document.getElementById('pointLabel').value, place_name:document.getElementById('pointPlace').value, latitude:parseFloat(document.getElementById('pointLat').value), longitude:parseFloat(document.getElementById('pointLng').value), description:document.getElementById('pointDesc').value})
        }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
    });

    // Edit point buttons
    document.querySelectorAll('.edit-point-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editPointId').value = this.dataset.id;
            document.getElementById('editPointLabel').value = this.dataset.label;
            document.getElementById('editPointPlace').value = this.dataset.place;
            document.getElementById('editPointLat').value = this.dataset.lat;
            document.getElementById('editPointLng').value = this.dataset.lng;
            document.getElementById('editPointDesc').value = this.dataset.desc;
            new bootstrap.Modal(document.getElementById('editPointModal')).show();
        });
    });

    // Update point
    document.getElementById('updatePoint')?.addEventListener('click', function() {
        fetch('/research/map-point-api', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({form_action:'update', id:parseInt(document.getElementById('editPointId').value), label:document.getElementById('editPointLabel').value, place_name:document.getElementById('editPointPlace').value, latitude:parseFloat(document.getElementById('editPointLat').value), longitude:parseFloat(document.getElementById('editPointLng').value), description:document.getElementById('editPointDesc').value})
        }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
    });

    // Delete point buttons
    document.querySelectorAll('.delete-point-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this point?')) return;
            fetch('/research/map-point-api', {
                method:'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({form_action:'delete', id:parseInt(this.dataset.id)})
            }).then(function(r){return r.json();}).then(function(d){ if(d.success) location.reload(); else alert(d.error||'Error'); });
        });
    });
});
</script>
