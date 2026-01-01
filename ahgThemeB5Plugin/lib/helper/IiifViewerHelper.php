<?php
/**
 * IIIF Viewer Helper for AtoM Integration
 *
 * Drop-in replacement for existing digital object viewing in AtoM
 * Replaces: ZoomPan, OpenSeadragon, video/audio players
 *
 * Add to: /usr/share/nginx/archive/plugins/ahgThemeB5Plugin/lib/helper/IiifViewerHelper.php
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */

/**
 * Check if IIIF/Cantaloupe is available
 */
function is_iiif_available()
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }
    
    // Check if IIIF is enabled in config
    if (!sfConfig::get('app_iiif_enabled', false)) {
        $available = false;
        return false;
    }
    
    $cantaloupeUrl = sfConfig::get('app_iiif_cantaloupe_url', '');
    if (empty($cantaloupeUrl)) {
        $available = false;
        return false;
    }
    
    $available = true;
    return true;
}

/**
 * Render standard AtoM digital object viewer (fallback)
 */
function render_standard_viewer($resource, $options = [])
{
    $digitalObjects = $resource->digitalObjectsRelatedByobjectId;
    if (empty($digitalObjects) || count($digitalObjects) === 0) {
        return '';
    }
    
    $primaryDo = $digitalObjects[0];
    $mimeType = $primaryDo->mimeType ?? '';
    $path = $primaryDo->path ?? '';
    $name = $primaryDo->name ?? '';
    
    // Get reference representation
    $refPath = '';
    $thumbPath = '';
    foreach ($primaryDo->digitalObjectsRelatedByparentId ?? [] as $child) {
        $usageId = $child->usageId ?? 0;
        if ($usageId == QubitTerm::REFERENCE_ID) {
            $refPath = $child->path . $child->name;
        } elseif ($usageId == QubitTerm::THUMBNAIL_ID) {
            $thumbPath = $child->path . $child->name;
        }
    }
    
    // Use reference if available, otherwise master
    $displayPath = $refPath ?: $path . $name;
    $thumbDisplay = $thumbPath ?: $displayPath;
    
    $html = '<div class="digital-object-viewer">';
    
    // Image
    if (strpos($mimeType, 'image') !== false) {
        $html .= '<a href="' . $displayPath . '" target="_blank">';
        $html .= '<img src="' . $displayPath . '" alt="' . esc_entities($name) . '" class="img-fluid" style="max-height: 600px;">';
        $html .= '</a>';
    }
    // Video
    elseif (strpos($mimeType, 'video') !== false) {
        $html .= '<video controls class="w-100" style="max-height: 600px;">';
        $html .= '<source src="' . $path . $name . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the video tag.';
        $html .= '</video>';
    }
    // Audio
    elseif (strpos($mimeType, 'audio') !== false) {
        $html .= '<audio controls class="w-100">';
        $html .= '<source src="' . $path . $name . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the audio tag.';
        $html .= '</audio>';
    }
    // PDF
    elseif (strpos($mimeType, 'pdf') !== false) {
        $html .= '<iframe src="' . $path . $name . '" width="100%" height="600px"></iframe>';
    }
    // Other - show thumbnail/link
    else {
        $html .= '<a href="' . $path . $name . '" target="_blank">';
        if ($thumbPath) {
            $html .= '<img src="' . $thumbPath . '" alt="' . esc_entities($name) . '" class="img-fluid">';
        } else {
            $html .= '<i class="fas fa-file fa-5x"></i><br>' . esc_entities($name);
        }
        $html .= '</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Get digital objects for a resource
 */
function get_digital_objects($resource)
{
    if (!$resource) {
        return [];
    }
    if ($resource instanceof QubitInformationObject) {
        return $resource->digitalObjectsRelatedByobjectId ?? [];
    }
    // Fallback to database query
    $resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
    if (!$resourceId) {
        return [];
    }
    return \Illuminate\Database\Capsule\Manager::table('digital_object')
        ->where('object_id', $resourceId)
        ->get()
        ->toArray();
}

/**
 * Main function to render IIIF viewer for an information object
 * This replaces all previous viewer rendering functions
 */
function render_iiif_viewer($resource, $options = [])
{
    // Get digital objects
    $digitalObjects = $resource->digitalObjectsRelatedByobjectId;
    if (empty($digitalObjects) || count($digitalObjects) === 0) {
        // Check for 3D models
        if (has_3d_models($resource)) {
            return render_3d_model_viewer($resource, $options);
        }
        return '';
    }
    
    // Check if IIIF is available, fallback to standard viewer
    if (!is_iiif_available()) {
        return render_standard_viewer($resource, $options);
    }
    
    $primaryDo = $digitalObjects[0];
    $mimeType = $primaryDo->mimeType ?? '';
    $objectId = $resource->id;
    $slug = $resource->slug ?? $objectId;
    elseif (strpos($mimeType, 'video') !== false) {
        $html .= '<video controls class="w-100" style="max-height: 600px;">';
        $html .= '<source src="' . $path . $name . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the video tag.';
        $html .= '</video>';
    }
    // Audio
    elseif (strpos($mimeType, 'audio') !== false) {
        $html .= '<audio controls class="w-100">';
        $html .= '<source src="' . $path . $name . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the audio tag.';
        $html .= '</audio>';
    }
    // PDF
    elseif (strpos($mimeType, 'pdf') !== false) {
        $html .= '<iframe src="' . $path . $name . '" width="100%" height="600px"></iframe>';
    }
    // Other - show thumbnail/link
    else {
        $html .= '<a href="' . $path . $name . '" target="_blank">';
        if ($thumbPath) {
            $html .= '<img src="' . $thumbPath . '" alt="' . esc_entities($name) . '" class="img-fluid">';
        } else {
            $html .= '<i class="fas fa-file fa-5x"></i><br>' . esc_entities($name);
        }
        $html .= '</a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Get digital objects for a resource
 */
function get_digital_objects($resource)
{
    if (!$resource) {
        return [];
    }
    if ($resource instanceof QubitInformationObject) {
        return $resource->digitalObjectsRelatedByobjectId ?? [];
    }
    // Fallback to database query
    $resourceId = is_object($resource) ? ($resource->id ?? null) : $resource;
    if (!$resourceId) {
        return [];
    }
    return \Illuminate\Database\Capsule\Manager::table('digital_object')
        ->where('object_id', $resourceId)
        ->get()
        ->toArray();
}

/**
 * Main function to render IIIF viewer for an information object
 * This replaces all previous viewer rendering functions
 */
function render_iiif_viewer($resource, $options = [])
{
    // Get digital objects
    $digitalObjects = $resource->digitalObjectsRelatedByobjectId;
    if (empty($digitalObjects) || count($digitalObjects) === 0) {
        // Check for 3D models
        if (has_3d_models($resource)) {
            return render_3d_model_viewer($resource, $options);
        }
        return '';
    }
    
    // Check if IIIF is available, fallback to standard viewer
    if (!is_iiif_available()) {
        return render_standard_viewer($resource, $options);
    }
    
    $primaryDo = $digitalObjects[0];
    $mimeType = $primaryDo->mimeType ?? '';
    $objectId = $resource->id;
    $slug = $resource->slug ?? $objectId;
    $hasPdf = stripos($mimeType, 'pdf') !== false;
    $hasAudio = stripos($mimeType, 'audio') !== false;
    $hasVideo = stripos($mimeType, 'video') !== false;
    $has3D = has_3d_models($resource);
    $hasAV = $hasAudio || $hasVideo;
    
    // Generate unique viewer ID
    $viewerId = 'iiif-viewer-' . $objectId . '-' . substr(md5(uniqid()), 0, 8);
    
    // Build HTML
    $html = '';
    
    // Include CSS (once per page)
    $html .= get_iiif_viewer_css($frameworkPath);
    
    // Container
    $html .= '<div class="iiif-viewer-container" id="container-' . $viewerId . '">';
    
    // Viewer toggle buttons
    $html .= render_viewer_toggle($viewerId, $opts['viewer'], $has3D, $hasPdf, $hasAV);
    
    // Controls bar
    $html .= render_viewer_controls($viewerId, $manifestUrl, $objectId, $opts);
    
    // Main viewer area
    $html .= '<div class="viewer-area" id="viewer-area-' . $viewerId . '">';
    
    // OpenSeadragon viewer
    $html .= '<div id="osd-' . $viewerId . '" class="osd-viewer" style="width:100%;height:' . $viewerHeight . ';background:#1a1a1a;border-radius:8px;"></div>';
    
    // Mirador wrapper (hidden by default)
    $html .= '<div id="mirador-wrapper-' . $viewerId . '" class="mirador-wrapper" style="display:none;position:relative;">';
    $html .= '<button id="close-mirador-' . $viewerId . '" class="btn btn-sm btn-light" style="position:absolute;top:10px;right:10px;z-index:1000;">';
    $html .= '<i class="fas fa-times"></i> Close</button>';
    $html .= '<div id="mirador-' . $viewerId . '" style="width:100%;height:700px;"></div>';
    $html .= '</div>';
    
    // PDF viewer (if applicable)
    if ($hasPdf) {
        $pdfUrl = get_digital_object_url($primaryDo);
        $html .= render_pdf_viewer_html($viewerId, $pdfUrl, $viewerHeight);
