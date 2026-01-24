<?php
/**
 * informationobjectHelper - UI Override helpers
 *
 * NOTE: 3D model functions have been moved to their proper locations:
 * - ahg3DModelPlugin/lib/helper/Model3DHelper.php (authoritative - uses object_3d_model table)
 * - ahgIiifPlugin/lib/helper/IiifViewerHelper.php (fallback - detects from file extensions)
 *
 * This file only contains UI-related viewer helpers for ahgUiOverridesPlugin.
 */

/**
 * Render digital object viewer
 * Uses IiifViewerHelper for comprehensive viewer support
 * Includes video/audio with transcription support
 *
 * Note: Only defined if not already defined by DigitalObjectViewerHelper
 */
if (!function_exists('render_digital_object_viewer')):
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
endif;
