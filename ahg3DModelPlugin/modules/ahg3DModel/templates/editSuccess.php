<?php
/**
 * 3D Model Edit Template
 */
$model = $sf_data->getRaw('model');
$hotspots = $sf_data->getRaw('hotspots');
?>

<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>">Home</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'index']) ?>">3D Models</a></li>
        <li class="breadcrumb-item"><a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'view', 'id' => $model->id]) ?>"><?php echo esc_entities($model->title ?: $model->original_filename) ?></a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
</nav>

<h1><i class="fas fa-edit me-2"></i>Edit 3D Model Settings</h1>

<form method="post">
    <div class="row">
        <div class="col-md-8">
            <!-- Preview -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Preview</h5>
                </div>
                <div class="card-body p-0">
                    <model-viewer
                        id="preview-viewer"
                        src="/uploads/<?php echo $model->file_path ?>"
                        alt="Preview"
                        camera-controls
                        touch-action="pan-y"
                        auto-rotate
                        rotation-per-second="<?php echo $model->rotation_speed ?>deg"
                        camera-orbit="<?php echo $model->camera_orbit ?>"
                        field-of-view="<?php echo $model->field_of_view ?>"
                        exposure="<?php echo $model->exposure ?>"
                        shadow-intensity="<?php echo $model->shadow_intensity ?>"
                        style="width:100%; height:400px; background-color: <?php echo $model->background_color ?>;"
                    ></model-viewer>
                </div>
            </div>

            <!-- Basic Info -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo esc_entities($model->title) ?>">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo esc_entities($model->description) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="alt_text" class="form-label">Alt Text (Accessibility)</label>
                        <input type="text" class="form-control" id="alt_text" name="alt_text" 
                               value="<?php echo esc_entities($model->alt_text) ?>">
                    </div>
                </div>
            </div>

            <!-- Viewer Settings -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Viewer Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="camera_orbit" class="form-label">Camera Orbit</label>
                                <input type="text" class="form-control" id="camera_orbit" name="camera_orbit" 
                                       value="<?php echo esc_entities($model->camera_orbit) ?>">
                                <div class="form-text">Format: "0deg 75deg 105%" (theta phi radius)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="field_of_view" class="form-label">Field of View</label>
                                <input type="text" class="form-control" id="field_of_view" name="field_of_view" 
                                       value="<?php echo esc_entities($model->field_of_view) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="exposure" class="form-label">Exposure</label>
                                <input type="range" class="form-range" id="exposure" name="exposure" 
                                       min="0" max="2" step="0.1" value="<?php echo $model->exposure ?>"
                                       oninput="document.getElementById('exposure-val').textContent=this.value; updatePreview();">
                                <span id="exposure-val"><?php echo $model->exposure ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="shadow_intensity" class="form-label">Shadow Intensity</label>
                                <input type="range" class="form-range" id="shadow_intensity" name="shadow_intensity" 
                                       min="0" max="2" step="0.1" value="<?php echo $model->shadow_intensity ?>"
                                       oninput="document.getElementById('shadow-val').textContent=this.value; updatePreview();">
                                <span id="shadow-val"><?php echo $model->shadow_intensity ?></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="rotation_speed" class="form-label">Rotation Speed (deg/sec)</label>
                                <input type="number" class="form-control" id="rotation_speed" name="rotation_speed" 
                                       value="<?php echo $model->rotation_speed ?>" min="0" max="360" step="1">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="background_color" class="form-label">Background Color</label>
                        <div class="input-group" style="max-width:200px;">
                            <input type="color" class="form-control form-control-color" id="bg_color_picker" 
                                   value="<?php echo $model->background_color ?>"
                                   onchange="document.getElementById('background_color').value=this.value; updatePreview();">
                            <input type="text" class="form-control" id="background_color" name="background_color" 
                                   value="<?php echo $model->background_color ?>">
                        </div>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="auto_rotate" name="auto_rotate" value="1"
                               <?php echo $model->auto_rotate ? 'checked' : '' ?>>
                        <label class="form-check-label" for="auto_rotate">
                            Enable Auto-Rotate
                        </label>
                    </div>
                </div>
            </div>

            <!-- AR Settings -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Augmented Reality (AR)</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="ar_enabled" name="ar_enabled" value="1"
                               <?php echo $model->ar_enabled ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ar_enabled">
                            <strong>Enable AR Viewing</strong>
                            <br><small class="text-muted">Allow users to view this model in augmented reality on supported devices</small>
                        </label>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ar_scale" class="form-label">AR Scale</label>
                                <select class="form-select" id="ar_scale" name="ar_scale">
                                    <option value="auto" <?php echo $model->ar_scale == 'auto' ? 'selected' : '' ?>>Auto</option>
                                    <option value="fixed" <?php echo $model->ar_scale == 'fixed' ? 'selected' : '' ?>>Fixed</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ar_placement" class="form-label">AR Placement</label>
                                <select class="form-select" id="ar_placement" name="ar_placement">
                                    <option value="floor" <?php echo $model->ar_placement == 'floor' ? 'selected' : '' ?>>Floor</option>
                                    <option value="wall" <?php echo $model->ar_placement == 'wall' ? 'selected' : '' ?>>Wall</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Status -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i>Status</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_primary" name="is_primary" value="1"
                               <?php echo $model->is_primary ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_primary">
                            <strong>Primary Model</strong>
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" value="1"
                               <?php echo $model->is_public ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_public">
                            <strong>Public</strong>
                        </label>
                    </div>
                </div>
            </div>

            <!-- File Info -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-file me-2"></i>File Info</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th>Filename</th>
                            <td><?php echo esc_entities($model->original_filename) ?></td>
                        </tr>
                        <tr>
                            <th>Format</th>
                            <td><?php echo strtoupper($model->format) ?></td>
                        </tr>
                        <tr>
                            <th>Size</th>
                            <td><?php echo number_format($model->file_size / 1024 / 1024, 2) ?> MB</td>
                        </tr>
                        <tr>
                            <th>Uploaded</th>
                            <td><?php echo date('M j, Y', strtotime($model->created_at)) ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Hotspots -->
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Hotspots</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addHotspotModal">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($hotspots)): ?>
                    <p class="text-muted mb-0 small">No hotspots defined.</p>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($hotspots as $hotspot): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge" style="background-color:<?php echo $hotspot->color ?>;"><?php echo ucfirst($hotspot->hotspot_type) ?></span>
                                <small class="ms-1"><?php echo esc_entities($hotspot->title ?: 'Untitled') ?></small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-hotspot" data-id="<?php echo $hotspot->id ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </li>
                        <?php endforeach ?>
                    </ul>
                    <?php endif ?>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Permanently delete this 3D model and all associated data.</p>
                    <a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'delete', 'id' => $model->id]) ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Are you sure you want to delete this 3D model? This cannot be undone.');">
                        <i class="fas fa-trash me-1"></i>Delete Model
                    </a>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="d-flex justify-content-between">
        <a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'view', 'id' => $model->id]) ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Save Changes
        </button>
    </div>
</form>

<!-- Add Hotspot Modal -->
<div class="modal fade" id="addHotspotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Hotspot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Click on the 3D model to set the hotspot position, then fill in the details below.</p>
                
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="hotspot_type">
                        <option value="annotation">Annotation</option>
                        <option value="info">Information</option>
                        <option value="damage">Damage</option>
                        <option value="detail">Detail</option>
                        <option value="link">Link</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" class="form-control" id="hotspot_title">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" id="hotspot_description" rows="2"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Position (X, Y, Z)</label>
                    <div class="row g-2">
                        <div class="col"><input type="number" class="form-control form-control-sm" id="hotspot_x" step="0.001" placeholder="X"></div>
                        <div class="col"><input type="number" class="form-control form-control-sm" id="hotspot_y" step="0.001" placeholder="Y"></div>
                        <div class="col"><input type="number" class="form-control form-control-sm" id="hotspot_z" step="0.001" placeholder="Z"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveHotspot">Add Hotspot</button>
            </div>
        </div>
    </div>
</div>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
function updatePreview() {
    const viewer = document.getElementById('preview-viewer');
    viewer.exposure = document.getElementById('exposure').value;
    viewer.shadowIntensity = document.getElementById('shadow_intensity').value;
    viewer.style.backgroundColor = document.getElementById('background_color').value;
}

document.addEventListener('DOMContentLoaded', function() {
    const viewer = document.getElementById('preview-viewer');
    const modelId = <?php echo $model->id ?>;
    
    // Get position on click for hotspots
    viewer.addEventListener('click', function(event) {
        const rect = viewer.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // Get 3D position from model-viewer
        const hit = viewer.surfaceFromPoint(x, y);
        if (hit) {
            document.getElementById('hotspot_x').value = hit.x.toFixed(4);
            document.getElementById('hotspot_y').value = hit.y.toFixed(4);
            document.getElementById('hotspot_z').value = hit.z.toFixed(4);
        }
    });
    
    // Save hotspot
    document.getElementById('saveHotspot').addEventListener('click', function() {
        const data = {
            hotspot_type: document.getElementById('hotspot_type').value,
            title: document.getElementById('hotspot_title').value,
            description: document.getElementById('hotspot_description').value,
            position_x: parseFloat(document.getElementById('hotspot_x').value) || 0,
            position_y: parseFloat(document.getElementById('hotspot_y').value) || 0,
            position_z: parseFloat(document.getElementById('hotspot_z').value) || 0,
        };
        
        fetch('/index.php/ahg3DModel/addHotspot/' + modelId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + (result.error || 'Unknown error'));
            }
        });
    });
    
    // Delete hotspot
    document.querySelectorAll('.delete-hotspot').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Delete this hotspot?')) {
                const hotspotId = this.dataset.id;
                fetch('/index.php/ahg3DModel/deleteHotspot/' + hotspotId, { method: 'POST' })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        location.reload();
                    }
                });
            }
        });
    });
});
</script>
