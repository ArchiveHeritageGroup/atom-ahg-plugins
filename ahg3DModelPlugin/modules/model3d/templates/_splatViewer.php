<?php
/**
 * Gaussian Splat Viewer Partial
 *
 * Usage:
 *   include_partial('model3d/splatViewer', [
 *       'url' => '/uploads/3d/model.splat',
 *       'height' => '500px',
 *       'title' => 'My Splat Model'
 *   ]);
 */

$url = $sf_data->getRaw('url') ?? '';
$height = $height ?? '500px';
$title = $title ?? 'Gaussian Splat';
$viewerId = 'splat-viewer-' . uniqid();
$pluginPath = '/plugins/ahg3DModelPlugin';
?>

<div class="splat-viewer-container" id="<?php echo $viewerId; ?>-container">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-info">
            <i class="fas fa-cloud me-1"></i><?php echo esc_entities($title); ?> (Gaussian Splat)
        </span>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" id="<?php echo $viewerId; ?>-fullscreen" title="Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    </div>

    <div id="<?php echo $viewerId; ?>"
         style="width:100%; height:<?php echo $height; ?>; background:linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius:8px; position:relative;">
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white" id="<?php echo $viewerId; ?>-loading">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <span><?php echo __('Loading Gaussian Splat...'); ?></span>
        </div>
    </div>

    <small class="text-muted mt-2 d-block">
        <i class="fas fa-mouse me-1"></i><?php echo __('Drag to rotate'); ?> |
        <i class="fas fa-search-plus me-1"></i><?php echo __('Scroll to zoom'); ?>
    </small>
</div>

<!-- Load GaussianSplats3D library -->
<script src="<?php echo $pluginPath; ?>/web/vendor/gaussian-splats3d/gaussian-splats-3d.umd.js"></script>
<script src="<?php echo $pluginPath; ?>/web/js/model3d.js"></script>

<script <?php $n = sfConfig::get('csp_nonce', ''); echo $n ? preg_replace('/^nonce=/', 'nonce="', $n).'"' : ''; ?>>
(function() {
    var container = document.getElementById('<?php echo $viewerId; ?>');
    var loading = document.getElementById('<?php echo $viewerId; ?>-loading');
    var fullscreenBtn = document.getElementById('<?php echo $viewerId; ?>-fullscreen');

    if (!container) return;

    var viewer = Model3D.initSplatViewer(container, '<?php echo esc_entities($url); ?>', {
        onLoad: function() {
            if (loading) loading.style.display = 'none';
        },
        onError: function(err) {
            if (loading) {
                loading.innerHTML = '<i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Error loading splat model';
            }
        }
    });

    // Fullscreen toggle
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
            var containerEl = document.getElementById('<?php echo $viewerId; ?>-container');
            if (document.fullscreenElement) {
                document.exitFullscreen();
            } else {
                containerEl.requestFullscreen();
            }
        });
    }
})();
</script>
