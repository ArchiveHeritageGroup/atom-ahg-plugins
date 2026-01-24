<?php

/**
 * 3D Model Helper Functions
 * 
 * Include this file in your templates to easily display 3D models
 * 
 * Usage:
 *   include_once sfConfig::get('sf_plugins_dir').'/ahg3DModelPlugin/lib/helper/Model3DHelper.php';
 *   echo render_3d_model($resource);
 *   echo render_3d_model_viewer($modelId, ['height' => '500px']);
 * 
 * @package ahg3DModelPlugin
 * @author Johan Pieterse - The Archive and Heritage Group
 */

/**
 * Check if an information object has any 3D models attached
 * 
 * @param object|int $resource Information object or ID
 * @return bool
 */
function has_3d_model($resource): bool
{
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    
    static $cache = [];
    if (isset($cache[$objectId])) {
        return $cache[$objectId];
    }
    
    try {
        \AhgCore\Core\AhgDb::init();
        $db = \Illuminate\Database\Capsule\Manager::class;
        
        $cache[$objectId] = $db::table('object_3d_model')
            ->where('object_id', $objectId)
            ->where('is_public', 1)
            ->exists();
            
        return $cache[$objectId];
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Get 3D models for an information object
 * 
 * @param object|int $resource Information object or ID
 * @return array
 */
function get_3d_models($resource): array
{
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    
    try {
        \AhgCore\Core\AhgDb::init();
        $service = new \AtomFramework\Services\Model3DService();
        return $service->getModelsForObject($objectId);
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * Get the primary 3D model for an object
 * 
 * @param object|int $resource Information object or ID
 * @return object|null
 */
function get_primary_3d_model($resource): ?object
{
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    
    try {
        \AhgCore\Core\AhgDb::init();
        $service = new \AtomFramework\Services\Model3DService();
        return $service->getPrimaryModel($objectId);
    } catch (\Exception $e) {
        return null;
    }
}

/**
 * Render 3D model viewer for an information object
 * 
 * @param object|int $resource Information object or ID
 * @param array $options Viewer options (height, viewer_type, show_hotspots)
 * @return string HTML output
 */
function render_3d_model($resource, array $options = []): string
{
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    
    try {
        \AhgCore\Core\AhgDb::init();
        $service = new \AtomFramework\Services\Model3DService();
        
        $model = $service->getPrimaryModel($objectId);
        if (!$model) {
            return '';
        }
        
        $viewerType = $options['viewer_type'] ?? $service->getSetting('default_viewer', 'model-viewer');
        
        if ($viewerType === 'threejs') {
            return $service->getThreeJsViewerHtml($model->id, $options);
        }
        
        return $service->getModelViewerHtml($model->id, $options);
        
    } catch (\Exception $e) {
        error_log('3D Model render error: ' . $e->getMessage());
        return '';
    }
}

/**
 * Render 3D model viewer by model ID
 * 
 * @param int $modelId Model ID
 * @param array $options Viewer options
 * @return string HTML output
 */
function render_3d_model_viewer(int $modelId, array $options = []): string
{
    try {
        \AhgCore\Core\AhgDb::init();
        $service = new \AtomFramework\Services\Model3DService();
        
        $viewerType = $options['viewer_type'] ?? $service->getSetting('default_viewer', 'model-viewer');
        
        if ($viewerType === 'threejs') {
            return $service->getThreeJsViewerHtml($modelId, $options);
        }
        
        return $service->getModelViewerHtml($modelId, $options);
        
    } catch (\Exception $e) {
        error_log('3D Model viewer render error: ' . $e->getMessage());
        return '<div class="alert alert-danger">Error loading 3D viewer</div>';
    }
}

/**
 * Render 3D model gallery for an object with multiple models
 * 
 * @param object|int $resource Information object or ID  
 * @param array $options Gallery options
 * @return string HTML output
 */
function render_3d_model_gallery($resource, array $options = []): string
{
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    
    try {
        \AhgCore\Core\AhgDb::init();
        $service = new \AtomFramework\Services\Model3DService();
        
        $models = $service->getModelsForObject($objectId);
        if (empty($models)) {
            return '';
        }
        
        // Single model - just render it
        if (count($models) === 1) {
            return render_3d_model_viewer($models[0]->id, $options);
        }
        
        // Multiple models - create tabbed interface
        $height = $options['height'] ?? '500px';
        $galleryId = 'model-gallery-' . $objectId;
        
        $html = '<div class="model-3d-gallery" id="' . $galleryId . '">';
        
        // Tab navigation
        $html .= '<ul class="nav nav-tabs mb-3" role="tablist">';
        foreach ($models as $index => $model) {
            $active = $index === 0 ? 'active' : '';
            $selected = $index === 0 ? 'true' : 'false';
            $title = esc_entities($model->title ?: $model->original_filename ?: 'Model ' . ($index + 1));
            $html .= '<li class="nav-item" role="presentation">';
            $html .= '<button class="nav-link ' . $active . '" data-bs-toggle="tab" ';
            $html .= 'data-bs-target="#model-tab-' . $model->id . '" type="button" role="tab" ';
            $html .= 'aria-controls="model-tab-' . $model->id . '" aria-selected="' . $selected . '">';
            $html .= '<i class="fas fa-cube me-1"></i>' . mb_substr($title, 0, 20);
            if ($model->ar_enabled) {
                $html .= ' <span class="badge bg-success">AR</span>';
            }
            $html .= '</button></li>';
        }
        $html .= '</ul>';
        
        // Tab content
        $html .= '<div class="tab-content">';
        foreach ($models as $index => $model) {
            $active = $index === 0 ? 'show active' : '';
            $html .= '<div class="tab-pane fade ' . $active . '" id="model-tab-' . $model->id . '" ';
            $html .= 'role="tabpanel">';
            $html .= render_3d_model_viewer($model->id, $options);
            $html .= '</div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
        
    } catch (\Exception $e) {
        error_log('3D Model gallery render error: ' . $e->getMessage());
        return '';
    }
}

/**
 * Get upload URL for adding a 3D model to an object
 * 
 * @param object|int $resource Information object or ID
 * @return string URL
 */
function get_3d_model_upload_url($resource): string
{
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    return url_for(['module' => 'model3d', 'action' => 'upload', 'object_id' => $objectId]);
}

/**
 * Get IIIF 3D manifest URL for a model
 * 
 * @param int $modelId Model ID
 * @return string URL
 */
function get_iiif_3d_manifest_url(int $modelId): string
{
    $baseUrl = sfContext::getInstance()->getRequest()->getUriPrefix();
    return $baseUrl . '/iiif/3d/' . $modelId . '/manifest.json';
}

/**
 * Check if a file extension is a supported 3D format
 * 
 * @param string $extension File extension (without dot)
 * @return bool
 */
function is_3d_format(string $extension): bool
{
    $supported = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'usdz'];
    return in_array(strtolower($extension), $supported);
}

/**
 * Get 3D model type label
 * 
 * @param string $format Format code
 * @return string Human readable label
 */
function get_3d_format_label(string $format): string
{
    $labels = [
        'glb' => 'glTF Binary',
        'gltf' => 'glTF JSON',
        'obj' => 'Wavefront OBJ',
        'stl' => 'Stereolithography',
        'fbx' => 'Autodesk FBX',
        'ply' => 'Polygon File Format',
        'usdz' => 'Apple AR Format',
    ];
    
    return $labels[strtolower($format)] ?? strtoupper($format);
}

/**
 * Render link to upload 3D model (for editors)
 * 
 * @param object $resource Information object
 * @param string $label Button label
 * @param string $class CSS classes
 * @return string HTML
 */
function render_3d_upload_button($resource, string $label = 'Add 3D Model', string $class = 'btn btn-sm btn-outline-primary'): string
{
    $user = sfContext::getInstance()->getUser();
    
    if (!$user->isAuthenticated()) {
        return '';
    }
    
    if (!$user->hasCredential('administrator') && !$user->hasCredential('editor')) {
        return '';
    }
    
    $url = get_3d_model_upload_url($resource);
    
    return '<a href="' . $url . '" class="' . $class . '"><i class="fas fa-cube me-1"></i>' . $label . '</a>';
}
