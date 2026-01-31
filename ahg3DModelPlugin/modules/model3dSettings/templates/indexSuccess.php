<?php
/**
 * 3D Settings Template
 */
$settings = $sf_data->getRaw('settings');
$stats = $sf_data->getRaw('stats');
$formatStats = $sf_data->getRaw('formatStats');

function getSetting($settings, $key, $default = '') {
    return isset($settings[$key]) ? $settings[$key]->setting_value : $default;
}

function isSettingEnabled($settings, $key) {
    return isset($settings[$key]) && $settings[$key]->setting_value === '1';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="fas fa-cog me-2"></i>3D Viewer Settings</h1>
        <p class="text-muted mb-0">Configure global settings for 3D model viewing</p>
    </div>
    <a href="<?php echo url_for(['module' => 'model3d', 'action' => 'index']) ?>" class="btn btn-outline-secondary">
        <i class="fas fa-cubes me-1"></i>View All Models
    </a>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6 text-primary"><?php echo number_format($stats['total_models']) ?></div>
                <small class="text-muted">Total Models</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6 text-success"><?php echo number_format($stats['ar_enabled_models']) ?></div>
                <small class="text-muted">AR Enabled</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6 text-info"><?php echo number_format($stats['total_hotspots']) ?></div>
                <small class="text-muted">Hotspots</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6 text-secondary"><?php echo number_format($stats['total_views']) ?></div>
                <small class="text-muted">Views</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6 text-warning"><?php echo number_format($stats['total_ar_views']) ?></div>
                <small class="text-muted">AR Views</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6 text-dark"><?php echo number_format($stats['storage_used'] / 1024 / 1024, 1) ?></div>
                <small class="text-muted">MB Used</small>
            </div>
        </div>
    </div>
</div>

<form method="post">
    <div class="row">
        <div class="col-md-8">
            <!-- Viewer Settings -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Viewer Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Default Viewer</label>
                                <select class="form-select" name="default_viewer">
                                    <option value="model-viewer" <?php echo getSetting($settings, 'default_viewer') == 'model-viewer' ? 'selected' : '' ?>>
                                        Model Viewer (Google WebXR)
                                    </option>
                                    <option value="threejs" <?php echo getSetting($settings, 'default_viewer') == 'threejs' ? 'selected' : '' ?>>
                                        Three.js
                                    </option>
                                </select>
                                <div class="form-text">Model Viewer provides AR support on mobile devices</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Default Background Color</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="bg_picker"
                                           value="<?php echo getSetting($settings, 'default_background', '#f5f5f5') ?>"
                                           onchange="document.getElementById('default_background').value=this.value;">
                                    <input type="text" class="form-control" id="default_background" name="default_background"
                                           value="<?php echo getSetting($settings, 'default_background', '#f5f5f5') ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Default Exposure</label>
                                <input type="number" class="form-control" name="default_exposure" 
                                       value="<?php echo getSetting($settings, 'default_exposure', '1.0') ?>"
                                       min="0" max="2" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Default Shadow Intensity</label>
                                <input type="number" class="form-control" name="default_shadow_intensity" 
                                       value="<?php echo getSetting($settings, 'default_shadow_intensity', '1.0') ?>"
                                       min="0" max="2" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Rotation Speed (deg/sec)</label>
                                <input type="number" class="form-control" name="rotation_speed" 
                                       value="<?php echo getSetting($settings, 'rotation_speed', '30') ?>"
                                       min="0" max="360">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="enable_auto_rotate" name="enable_auto_rotate" value="1"
                                       <?php echo isSettingEnabled($settings, 'enable_auto_rotate') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_auto_rotate">
                                    Enable Auto-Rotate by Default
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="enable_fullscreen" name="enable_fullscreen" value="1"
                                       <?php echo isSettingEnabled($settings, 'enable_fullscreen') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_fullscreen">
                                    Enable Fullscreen Button
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AR Settings -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Augmented Reality</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="enable_ar" name="enable_ar" value="1"
                               <?php echo isSettingEnabled($settings, 'enable_ar') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_ar">
                            <strong>Enable AR Viewing</strong>
                            <br><small class="text-muted">Allow users to view 3D models in augmented reality on supported devices (iOS Safari, Chrome for Android)</small>
                        </label>
                    </div>

                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        AR requires HTTPS and is supported on:
                        <ul class="mb-0 mt-1">
                            <li>iOS 12+ (Safari with Quick Look)</li>
                            <li>Android 7+ (Chrome with Scene Viewer)</li>
                            <li>WebXR-capable browsers</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Upload Settings -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Upload Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Maximum File Size (MB)</label>
                                <input type="number" class="form-control" name="max_file_size_mb" 
                                       value="<?php echo getSetting($settings, 'max_file_size_mb', '100') ?>"
                                       min="1" max="500">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Allowed Formats</label>
                        <?php 
                        $allowedFormats = json_decode(getSetting($settings, 'allowed_formats', '["glb","gltf","usdz"]'), true) ?: [];
                        $allFormats = ['glb', 'gltf', 'usdz', 'obj', 'stl', 'fbx', 'ply'];
                        ?>
                        <div class="row">
                            <?php foreach ($allFormats as $fmt): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="allowed_formats[]" 
                                           value="<?php echo $fmt ?>" id="fmt_<?php echo $fmt ?>"
                                           <?php echo in_array($fmt, $allowedFormats) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="fmt_<?php echo $fmt ?>">
                                        <?php echo strtoupper($fmt) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach ?>
                        </div>
                        <div class="form-text">GLB and GLTF are recommended for web viewing</div>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="enable_download" name="enable_download" value="1"
                               <?php echo isSettingEnabled($settings, 'enable_download') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_download">
                            Allow Model Downloads
                            <br><small class="text-muted">Let users download 3D model files</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Annotations -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Annotations & Hotspots</h5>
                </div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="enable_annotations" name="enable_annotations" value="1"
                               <?php echo isSettingEnabled($settings, 'enable_annotations') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="enable_annotations">
                            <strong>Enable 3D Hotspots</strong>
                            <br><small class="text-muted">Allow adding clickable annotation points on 3D models</small>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Watermark -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-stamp me-2"></i>Watermark</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="watermark_enabled" name="watermark_enabled" value="1"
                               <?php echo isSettingEnabled($settings, 'watermark_enabled') ? 'checked' : '' ?>>
                        <label class="form-check-label" for="watermark_enabled">
                            <strong>Enable Watermark</strong>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Watermark Text</label>
                        <input type="text" class="form-control" name="watermark_text"
                               value="<?php echo esc_entities(getSetting($settings, 'watermark_text', 'The Archive and Heritage Group')) ?>">
                    </div>
                </div>
            </div>

            <!-- TripoSR - Image to 3D -->
            <?php
            $triposrHealth = $sf_data->getRaw('triposrHealth') ?? ['status' => 'unknown'];
            $triposrOnline = ($triposrHealth['status'] ?? '') === 'ok';
            ?>
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-magic me-2"></i>TripoSR - Image to 3D</h5>
                    <span class="badge <?php echo $triposrOnline ? 'bg-success' : 'bg-danger' ?>">
                        <?php echo $triposrOnline ? 'Online' : 'Offline' ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Generate 3D models from 2D images using AI. Supports local CPU processing or remote GPU server.
                    </p>

                    <?php if ($triposrOnline): ?>
                    <div class="alert alert-success small mb-3">
                        <i class="fas fa-check-circle me-1"></i>
                        <strong>API Status:</strong> Online |
                        <strong>Device:</strong> <?php echo $triposrHealth['device'] ?? 'unknown' ?> |
                        <strong>Mode:</strong> <?php echo $triposrHealth['mode'] ?? 'unknown' ?>
                        <?php if ($triposrHealth['cuda_available'] ?? false): ?>
                        | <strong>CUDA:</strong> Available
                        <?php endif ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        TripoSR service not responding. Check if the service is running.
                    </div>
                    <?php endif ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="triposr_enabled" name="triposr_enabled" value="1"
                                       <?php echo isSettingEnabled($settings, 'triposr_enabled') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="triposr_enabled">
                                    <strong>Enable TripoSR</strong>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Processing Mode</label>
                                <select class="form-select" name="triposr_mode" id="triposr_mode">
                                    <option value="local" <?php echo getSetting($settings, 'triposr_mode', 'local') == 'local' ? 'selected' : '' ?>>
                                        Local (CPU/GPU)
                                    </option>
                                    <option value="remote" <?php echo getSetting($settings, 'triposr_mode') == 'remote' ? 'selected' : '' ?>>
                                        Remote GPU Server
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="triposr_remote_config" style="display: <?php echo getSetting($settings, 'triposr_mode') == 'remote' ? 'block' : 'none' ?>;">
                        <div class="alert alert-info small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Configure remote GPU server for faster processing. The local server will auto-fallback if remote fails.
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Remote GPU Server URL</label>
                                    <input type="url" class="form-control" name="triposr_remote_url"
                                           value="<?php echo esc_entities(getSetting($settings, 'triposr_remote_url')) ?>"
                                           placeholder="https://gpu-server.example.com:5050">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">API Key (optional)</label>
                                    <?php
                                    $apiKey = getSetting($settings, 'triposr_remote_api_key');
                                    $maskedKey = $apiKey ? '***' : '';
                                    ?>
                                    <input type="password" class="form-control" name="triposr_remote_api_key"
                                           value="<?php echo $maskedKey ?>"
                                           placeholder="API key">
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3">Default Generation Options</h6>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="triposr_remove_bg" name="triposr_remove_bg" value="1"
                                       <?php echo getSetting($settings, 'triposr_remove_bg', '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="triposr_remove_bg">
                                    Remove Background
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Foreground Ratio</label>
                                <input type="number" class="form-control" name="triposr_foreground_ratio"
                                       value="<?php echo getSetting($settings, 'triposr_foreground_ratio', '0.85') ?>"
                                       min="0.5" max="1" step="0.05">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Resolution</label>
                                <select class="form-select" name="triposr_mc_resolution">
                                    <option value="128" <?php echo getSetting($settings, 'triposr_mc_resolution', '256') == '128' ? 'selected' : '' ?>>128 (Fast)</option>
                                    <option value="256" <?php echo getSetting($settings, 'triposr_mc_resolution', '256') == '256' ? 'selected' : '' ?>>256 (Balanced)</option>
                                    <option value="512" <?php echo getSetting($settings, 'triposr_mc_resolution', '256') == '512' ? 'selected' : '' ?>>512 (High Quality)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="triposr_bake_texture" name="triposr_bake_texture" value="1"
                                       <?php echo isSettingEnabled($settings, 'triposr_bake_texture') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="triposr_bake_texture">
                                    Bake Texture (OBJ output)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Timeout (seconds)</label>
                                <input type="number" class="form-control" name="triposr_timeout"
                                       value="<?php echo getSetting($settings, 'triposr_timeout', '300') ?>"
                                       min="60" max="600">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Local API URL</label>
                                <input type="text" class="form-control" name="triposr_api_url"
                                       value="<?php echo esc_entities(getSetting($settings, 'triposr_api_url', 'http://127.0.0.1:5050')) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="text-muted small">
                        <strong>CLI:</strong> <code>php symfony triposr:generate --image=/path/to/image.jpg</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Format Distribution -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Format Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($formatStats)): ?>
                    <p class="text-muted mb-0">No models uploaded yet.</p>
                    <?php else: ?>
                    <canvas id="formatChart" height="200"></canvas>
                    <?php endif ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo url_for(['module' => 'model3d', 'action' => 'index']) ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-cubes me-2"></i>View All 3D Models
                    </a>
                    <a href="<?php echo url_for(['module' => 'model3dSettings', 'action' => 'triposr']) ?>" class="list-group-item list-group-item-action">
                        <i class="fas fa-magic me-2"></i>TripoSR Settings
                    </a>
                    <a href="https://modelviewer.dev/" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-external-link-alt me-2"></i>Model Viewer Documentation
                    </a>
                    <a href="https://iiif.io/api/3d/" target="_blank" class="list-group-item list-group-item-action">
                        <i class="fas fa-external-link-alt me-2"></i>IIIF 3D Specification
                    </a>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>Save Settings
        </button>
    </div>
</form>

<?php if (!empty($formatStats)): ?>
<script src="/plugins/ahgCorePlugin/web/js/vendor/chart.min.js"></script>
<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('formatChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map('strtoupper', array_keys($formatStats))) ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($formatStats)) ?>,
                backgroundColor: ['#1a73e8', '#34a853', '#fbbc04', '#ea4335', '#673ab7', '#00bcd4', '#ff5722']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>
<?php endif ?>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle TripoSR remote config visibility
    const modeSelect = document.getElementById('triposr_mode');
    const remoteConfig = document.getElementById('triposr_remote_config');

    if (modeSelect && remoteConfig) {
        modeSelect.addEventListener('change', function() {
            remoteConfig.style.display = this.value === 'remote' ? 'block' : 'none';
        });
    }
});
</script>
