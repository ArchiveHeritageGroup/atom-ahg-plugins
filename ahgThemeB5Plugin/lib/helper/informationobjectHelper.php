<?php
/**
 * informationobjectHelper - Wrapper for backward compatibility
 * Delegates to IiifViewerHelper when available
 */

// Load IiifViewerHelper
require_once sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/lib/helper/IiifViewerHelper.php';

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
 * Includes video/audio with transcription support
 */
function render_digital_object_viewer($resource, $digitalObject = null, array $options = [])
{
    // Get the digital object if not provided
    if (!$digitalObject && is_object($resource) && isset($resource->digitalObjectsRelatedByobjectId)) {
        $digitalObjects = $resource->digitalObjectsRelatedByobjectId;
        if (count($digitalObjects) > 0) {
            $digitalObject = $digitalObjects[0];
        }
    }

    // Check for video/audio - use transcription-enabled player
    if (is_object($digitalObject)) {
        $mimeType = $digitalObject->mimeType ?? '';
        $mediaTypeId = $digitalObject->mediaTypeId ?? null;
        $isVideo = ($mediaTypeId == QubitTerm::VIDEO_ID) || strpos($mimeType, 'video') !== false;
        $isAudio = ($mediaTypeId == QubitTerm::AUDIO_ID) || strpos($mimeType, 'audio') !== false;

        if ($isVideo || $isAudio) {
            // Use the video player partial with transcription support
            ob_start();
            include_partial('digitalobject/showVideo', ['resource' => $digitalObject]);
            return ob_get_clean();
        }
    }

    // Use render_iiif_viewer from IiifViewerHelper for images
    if (function_exists('render_iiif_viewer') && is_object($resource)) {
        return render_iiif_viewer($resource, $options);
    }

    // Fallback - simple rendering
    return '<div class="alert alert-warning">Viewer not available</div>';
}
