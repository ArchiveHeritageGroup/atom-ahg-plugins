<?php
/**
 * informationobjectHelper - Wrapper for backward compatibility
 * Delegates to IiifViewerHelper when available
 */

// Load IiifViewerHelper
require_once sfConfig::get('sf_plugins_dir') . '/arAHGThemeB5Plugin/lib/helper/IiifViewerHelper.php';

function get_3d_models_from_plugin($objectId)
{
    if (function_exists('get_primary_3d_model')) {
        $model = get_primary_3d_model($objectId);
        return $model ? [$model] : null;
    }
    return null;
}

function has_3d_models($objectId)
{
    if (function_exists('has_3d_models_iiif')) {
        return has_3d_models_iiif($objectId);
    }
    $models = get_3d_models_from_plugin($objectId);
    return !empty($models);
}

/**
 * Render digital object viewer
 * Uses IiifViewerHelper for comprehensive viewer support
 */
function render_digital_object_viewer($resource, $digitalObject = null, array $options = [])
{
    // Use render_iiif_viewer from IiifViewerHelper
    if (function_exists('render_iiif_viewer') && is_object($resource)) {
        return render_iiif_viewer($resource, $options);
    }
    
    // Fallback - simple rendering
    $objectId = is_object($resource) ? $resource->id : (int)$resource;
    $mimeType = '';
    if (is_object($digitalObject)) {
        $mimeType = $digitalObject->mimeType ?? '';
    }
    
    return '<div class="alert alert-warning">Viewer not available</div>';
}
