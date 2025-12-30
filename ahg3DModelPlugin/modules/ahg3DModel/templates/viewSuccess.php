<?php
/**
 * 3D Model View Template
 * 
 * Displays a 3D model with Google's model-viewer component and hotspots
 */
$model = $sf_data->getRaw('model');
$hotspots = $sf_data->getRaw('hotspots');
$object = $sf_data->getRaw('object');

$baseUrl = sfContext::getInstance()->getRequest()->getUriPrefix();
$modelUrl = "{$baseUrl}/uploads/{$model->file_path}";
$posterUrl = $model->poster_image ? "{$baseUrl}/uploads/{$model->poster_image}" : '';
?>

<!-- Include model-viewer component -->
<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js"></script>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo url_for('@homepage') ?>">Home</a></li>
        <?php if ($object): ?>
        <li class="breadcrumb-item"><a href="/index.php/<?php echo $object->slug ?>"><?php echo esc_entities($object->title) ?></a></li>
        <?php endif ?>
        <li class="breadcrumb-item active">3D Model</li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="fas fa-cube me-2"></i><?php echo esc_entities($model->title ?: $model->original_filename) ?></h1>
        <p class="text-muted mb-0">
            <?php echo strtoupper($model->format) ?> • <?php echo number_format($model->file_size / 1024 / 1024, 2) ?> MB
            <?php if ($model->ar_enabled): ?>
            <span class="badge bg-success ms-2"><i class="fas fa-mobile-alt me-1"></i>AR Ready</span>
            <?php endif ?>
        </p>
    </div>
    <div>
        <?php if ($sf_user->hasCredential('administrator') || $sf_user->hasCredential('editor')): ?>
        <a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'edit', 'id' => $model->id]) ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i>Edit Settings
        </a>
        <?php endif ?>
        <a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'index']) ?>" class="btn btn-outline-secondary ms-1">
            <i class="fas fa-list me-1"></i>All Models
        </a>
    </div>
</div>

<?php if ($model->description): ?>
<div class="card mb-4">
    <div class="card-body">
        <p class="mb-0"><?php echo nl2br(esc_entities($model->description)) ?></p>
    </div>
</div>
<?php endif ?>

<!-- 3D Viewer -->
<div class="card mb-4">
    <div class="card-body p-0">
        <div class="model-viewer-wrapper" style="height: 600px; position: relative;">
            <model-viewer
                id="main-viewer"
                src="<?php echo $modelUrl ?>"
                <?php if ($posterUrl): ?>poster="<?php echo $posterUrl ?>"<?php endif ?>
                alt="<?php echo esc_entities($model->alt_text ?: $model->title) ?>"
                camera-controls
                touch-action="pan-y"
                <?php if ($model->ar_enabled): ?>
                ar
                ar-modes="webxr scene-viewer quick-look"
                <?php endif ?>
                <?php if ($model->auto_rotate): ?>auto-rotate<?php endif ?>
                rotation-per-second="<?php echo $model->rotation_speed ?>deg"
                camera-orbit="<?php echo $model->camera_orbit ?>"
                field-of-view="<?php echo $model->field_of_view ?>"
                exposure="<?php echo $model->exposure ?>"
                shadow-intensity="<?php echo $model->shadow_intensity ?>"
                shadow-softness="<?php echo $model->shadow_softness ?>"
                <?php if ($model->environment_image): ?>environment-image="/uploads/<?php echo $model->environment_image ?>"<?php endif ?>
                <?php if ($model->skybox_image): ?>skybox-image="/uploads/<?php echo $model->skybox_image ?>"<?php endif ?>
                style="width: 100%; height: 100%; background-color: <?php echo $model->background_color ?>;"
            >
                <!-- Hotspots -->
                <?php foreach ($hotspots as $hotspot): ?>
                <button class="hotspot" 
                        slot="hotspot-<?php echo $hotspot->id ?>"
                        data-position="<?php echo $hotspot->position_x ?>m <?php echo $hotspot->position_y ?>m <?php echo $hotspot->position_z ?>m"
                        data-normal="<?php echo $hotspot->normal_x ?>m <?php echo $hotspot->normal_y ?>m <?php echo $hotspot->normal_z ?>m"
                        data-type="<?php echo $hotspot->hotspot_type ?>"
                        style="--hotspot-color: <?php echo $hotspot->color ?>;">
                    <div class="hotspot-annotation">
                        <?php if ($hotspot->title): ?>
                        <strong><?php echo esc_entities($hotspot->title) ?></strong>
                        <?php endif ?>
                        <?php if ($hotspot->description): ?>
                        <p><?php echo esc_entities($hotspot->description) ?></p>
                        <?php endif ?>
                        <?php if ($hotspot->link_url): ?>
                        <a href="<?php echo $hotspot->link_url ?>" target="<?php echo $hotspot->link_target ?>" class="btn btn-sm btn-primary mt-1">
                            <i class="fas fa-external-link-alt"></i> Learn More
                        </a>
                        <?php endif ?>
                    </div>
                </button>
                <?php endforeach ?>

                <!-- AR Button -->
                <?php if ($model->ar_enabled): ?>
                <button slot="ar-button" class="ar-button">
                    <i class="fas fa-cube"></i> View in AR
                </button>
                <?php endif ?>

                <!-- Progress bar -->
                <div class="progress-bar" slot="progress-bar">
                    <div class="update-bar"></div>
                </div>
            </model-viewer>

            <!-- Control buttons -->
            <div class="viewer-controls">
                <button id="btn-fullscreen" class="viewer-btn" title="Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
                <button id="btn-rotate" class="viewer-btn" title="Toggle Auto-Rotate">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button id="btn-reset" class="viewer-btn" title="Reset Camera">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Model Info -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Model Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="40%">Format</th>
                        <td><?php echo strtoupper($model->format) ?></td>
                    </tr>
                    <tr>
                        <th>File Size</th>
                        <td><?php echo number_format($model->file_size / 1024 / 1024, 2) ?> MB</td>
                    </tr>
                    <?php if ($model->vertex_count): ?>
                    <tr>
                        <th>Vertices</th>
                        <td><?php echo number_format($model->vertex_count) ?></td>
                    </tr>
                    <?php endif ?>
                    <?php if ($model->face_count): ?>
                    <tr>
                        <th>Faces</th>
                        <td><?php echo number_format($model->face_count) ?></td>
                    </tr>
                    <?php endif ?>
                    <?php if ($model->texture_count): ?>
                    <tr>
                        <th>Textures</th>
                        <td><?php echo $model->texture_count ?></td>
                    </tr>
                    <?php endif ?>
                    <?php if ($model->animation_count): ?>
                    <tr>
                        <th>Animations</th>
                        <td><?php echo $model->animation_count ?></td>
                    </tr>
                    <?php endif ?>
                    <tr>
                        <th>AR Enabled</th>
                        <td><?php echo $model->ar_enabled ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                    </tr>
                    <tr>
                        <th>Uploaded</th>
                        <td><?php echo date('M j, Y', strtotime($model->created_at)) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Hotspots (<?php echo count($hotspots) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($hotspots)): ?>
                <p class="text-muted mb-0">No hotspots defined for this model.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($hotspots as $hotspot): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>
                            <span class="badge me-2" style="background-color: <?php echo $hotspot->color ?>">
                                <?php echo ucfirst($hotspot->hotspot_type) ?>
                            </span>
                            <?php echo esc_entities($hotspot->title ?: 'Untitled') ?>
                        </div>
                        <button class="btn btn-sm btn-outline-primary focus-hotspot" data-id="<?php echo $hotspot->id ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                    </li>
                    <?php endforeach ?>
                </ul>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- IIIF Info -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-link me-2"></i>IIIF 3D Manifest</h5>
    </div>
    <div class="card-body">
        <p>Access the IIIF 3D manifest for this model:</p>
        <div class="input-group">
            <input type="text" class="form-control" id="manifest-url" readonly 
                   value="<?php echo $baseUrl ?>/iiif/3d/<?php echo $model->id ?>/manifest.json">
            <button class="btn btn-outline-secondary" type="button" onclick="copyManifestUrl()">
                <i class="fas fa-copy"></i> Copy
            </button>
            <a href="<?php echo $baseUrl ?>/iiif/3d/<?php echo $model->id ?>/manifest.json" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-external-link-alt"></i> View
            </a>
        </div>
    </div>
</div>

<style>
.model-viewer-wrapper model-viewer {
    --poster-color: transparent;
}

.hotspot {
    display: block;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid white;
    background-color: var(--hotspot-color, #1a73e8);
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    cursor: pointer;
    transition: transform 0.2s;
    padding: 0;
}

.hotspot:hover {
    transform: scale(1.2);
}

.hotspot-annotation {
    display: none;
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    padding: 12px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    min-width: 180px;
    max-width: 280px;
    text-align: left;
    z-index: 100;
    margin-bottom: 8px;
}

.hotspot:hover .hotspot-annotation,
.hotspot:focus .hotspot-annotation {
    display: block;
}

.hotspot-annotation strong {
    display: block;
    margin-bottom: 4px;
    color: #333;
    font-size: 0.95em;
}

.hotspot-annotation p {
    margin: 0;
    font-size: 0.85em;
    color: #666;
}

.viewer-controls {
    position: absolute;
    bottom: 16px;
    right: 16px;
    display: flex;
    gap: 8px;
    z-index: 10;
}

.viewer-btn {
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 8px;
    background: rgba(0,0,0,0.6);
    color: white;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 1.1em;
}

.viewer-btn:hover {
    background: rgba(0,0,0,0.8);
}

.ar-button {
    position: absolute;
    bottom: 16px;
    left: 16px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    background: #1a73e8;
    color: white;
    font-weight: 500;
    cursor: pointer;
    z-index: 10;
}

.ar-button:hover {
    background: #1557b0;
}

.progress-bar {
    display: block;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: rgba(0,0,0,0.1);
}

.progress-bar .update-bar {
    height: 100%;
    background: #1a73e8;
    transition: width 0.1s;
}
</style>

<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
document.addEventListener('DOMContentLoaded', function() {
    const viewer = document.getElementById('main-viewer');
    
    // Fullscreen
    document.getElementById('btn-fullscreen').addEventListener('click', function() {
        const wrapper = document.querySelector('.model-viewer-wrapper');
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            wrapper.requestFullscreen();
        }
    });
    
    // Toggle auto-rotate
    document.getElementById('btn-rotate').addEventListener('click', function() {
        const isRotating = viewer.hasAttribute('auto-rotate');
        if (isRotating) {
            viewer.removeAttribute('auto-rotate');
            this.classList.remove('active');
        } else {
            viewer.setAttribute('auto-rotate', '');
            this.classList.add('active');
        }
    });
    
    // Reset camera
    document.getElementById('btn-reset').addEventListener('click', function() {
        viewer.cameraOrbit = '<?php echo $model->camera_orbit ?>';
        viewer.fieldOfView = '<?php echo $model->field_of_view ?>';
    });
    
    // Focus hotspot
    document.querySelectorAll('.focus-hotspot').forEach(btn => {
        btn.addEventListener('click', function() {
            const hotspotId = this.dataset.id;
            const hotspot = viewer.querySelector('[slot="hotspot-' + hotspotId + '"]');
            if (hotspot) {
                const position = hotspot.dataset.position;
                // Animate camera to hotspot position
                // This is a simplified version - full implementation would calculate orbit from position
            }
        });
    });
});

function copyManifestUrl() {
    const input = document.getElementById('manifest-url');
    input.select();
    document.execCommand('copy');
    
    // Show feedback
    const btn = input.nextElementSibling;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    setTimeout(() => {
        btn.innerHTML = originalHtml;
    }, 2000);
}
</script>
