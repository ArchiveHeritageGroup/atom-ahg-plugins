<?php
/**
 * 3D Model Partial
 * 
 * Include in digital object templates to display 3D models
 * 
 * Usage:
 *   include_partial('ahg3DModel/model3dViewer', ['resource' => $resource]);
 * 
 * @var QubitInformationObject $resource
 */

// Load helper
include_once sfConfig::get('sf_plugins_dir') . '/ahg3DModelPlugin/lib/helper/Model3DHelper.php';

// Check if object has 3D models
if (!has_3d_model($resource)) {
    return;
}

$models = get_3d_models($resource);
$modelCount = count($models);
?>

<div class="model-3d-section mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h4 class="mb-0">
            <i class="fas fa-cube me-2"></i>3D Model<?php echo $modelCount > 1 ? 's' : '' ?>
            <span class="badge bg-secondary"><?php echo $modelCount ?></span>
        </h4>
        <?php if ($sf_user->hasCredential('administrator') || $sf_user->hasCredential('editor')): ?>
        <a href="<?php echo get_3d_model_upload_url($resource) ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-plus me-1"></i>Add 3D Model
        </a>
        <?php endif ?>
    </div>

    <?php if ($modelCount === 1): ?>
        <?php 
        $model = $models[0];
        echo render_3d_model_viewer($model->id, [
            'height' => '500px',
            'base_url' => sfContext::getInstance()->getRequest()->getUriPrefix()
        ]);
        ?>
        
        <div class="mt-2">
            <small class="text-muted">
                <?php echo get_3d_format_label($model->format) ?> • 
                <?php echo number_format($model->file_size / 1024 / 1024, 2) ?> MB
                <?php if ($model->ar_enabled): ?>
                • <span class="badge bg-success"><i class="fas fa-mobile-alt me-1"></i>AR Ready</span>
                <?php endif ?>
            </small>
            
            <?php if ($sf_user->hasCredential('administrator') || $sf_user->hasCredential('editor')): ?>
            <div class="mt-1">
                <a href="<?php echo url_for(['module' => 'ahg3DModel', 'action' => 'edit', 'id' => $model->id]) ?>" 
                   class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-cog me-1"></i>Settings
                </a>
            </div>
            <?php endif ?>
        </div>
        
    <?php else: ?>
        <?php echo render_3d_model_gallery($resource, [
            'height' => '500px',
            'base_url' => sfContext::getInstance()->getRequest()->getUriPrefix()
        ]); ?>
    <?php endif ?>
</div>

<!-- Load model-viewer script if not already loaded -->
<script <?php echo sfConfig::get('csp_nonce', ''); ?>>
if (!customElements.get('model-viewer')) {
    var script = document.createElement('script');
    script.type = 'module';
    script.src = 'https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js';
    document.head.appendChild(script);
}
</script>
