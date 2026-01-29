<?php

/**
 * IIIF Viewer Helper for AtoM Integration
 *
 * Drop-in replacement for existing digital object viewing in AtoM
 * Replaces: ZoomPan, OpenSeadragon, video/audio players
 *
 * Part of: ahgIiifPlugin
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.0.0
 */

/**
 * Get base URL from current request or configuration
 * Auto-detects from request if not configured
 */
function get_iiif_base_url()
{
    static $baseUrl = null;
    if ($baseUrl !== null) {
        return $baseUrl;
    }

    // Try config first
    $configured = sfConfig::get('app_iiif_base_url', '');
    if (!empty($configured)) {
        $baseUrl = rtrim($configured, '/');
        return $baseUrl;
    }

    // Auto-detect from request
    if (isset($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
    } else {
        // Fallback for CLI
        $baseUrl = 'http://localhost';
    }

    return $baseUrl;
}

/**
 * Get full Cantaloupe URL (handles relative paths)
 */
function get_iiif_cantaloupe_url()
{
    $cantaloupeUrl = sfConfig::get('app_iiif_cantaloupe_url', '/iiif/2');

    // If it's a relative URL, prepend base URL
    if (!empty($cantaloupeUrl) && strpos($cantaloupeUrl, 'http') !== 0) {
        return get_iiif_base_url() . '/' . ltrim($cantaloupeUrl, '/');
    }

    return $cantaloupeUrl;
}

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
 * Render standard AtoM digital object viewer (fallback when IIIF not available)
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
    foreach ($primaryDo->digitalObjectsRelatedByparentId ?? [] as $child) {
        $usageId = $child->usageId ?? 0;
        if ($usageId == QubitTerm::REFERENCE_ID) {
            $refPath = $child->path . $child->name;
            break;
        }
    }
    $displayPath = $refPath ?: $path . $name;
    $html = '<div class="digital-object-viewer">';
    if (strpos($mimeType, 'image') !== false) {
        $html .= '<a href="' . $displayPath . '" target="_blank">';
        $html .= '<img src="' . $displayPath . '" alt="' . esc_entities($name) . '" class="img-fluid" style="max-height: 600px;">';
        $html .= '</a>';
    } elseif (strpos($mimeType, 'video') !== false) {
        $html .= '<video controls class="w-100" style="max-height: 600px;">';
        $html .= '<source src="' . $path . $name . '" type="' . $mimeType . '">';
        $html .= '</video>';
    } elseif (strpos($mimeType, 'audio') !== false) {
        $html .= '<audio controls class="w-100">';
        $html .= '<source src="' . $path . $name . '" type="' . $mimeType . '">';
        $html .= '</audio>';
    } elseif (strpos($mimeType, 'pdf') !== false) {
        $html .= '<iframe src="' . $path . $name . '" width="100%" height="600px"></iframe>';
    } else {
        $html .= '<a href="' . $path . $name . '" target="_blank" class="btn btn-primary">';
        $html .= '<i class="fas fa-download me-2"></i>Download ' . esc_entities($name);
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
    
    // Configuration - use helper functions for dynamic URL resolution
    $baseUrl = get_iiif_base_url();
    $cantaloupeUrl = get_iiif_cantaloupe_url();
    $pluginPath = sfConfig::get('app_iiif_plugin_path', '/plugins/ahgIiifPlugin/web');
    $defaultViewer = sfConfig::get('app_iiif_default_viewer', 'openseadragon');
    $enableAnnotations = sfConfig::get('app_iiif_enable_annotations', true);
    $viewerHeight = $options['height'] ?? sfConfig::get('app_iiif_viewer_height', '600px');
    
    // Merge options
    $opts = array_merge([
        'viewer' => $defaultViewer,
        'height' => $viewerHeight,
        'enable_annotations' => $enableAnnotations,
        'enable_download' => false,
        'enable_fullscreen' => true,
    ], $options);
    
    // Build manifest URL
    $manifestUrl = $baseUrl . '/iiif/manifest/' . $slug;
    
    // Determine content type flags
    $hasPdf = stripos($mimeType, 'pdf') !== false;
    $hasAudio = stripos($mimeType, 'audio') !== false;
    $hasVideo = stripos($mimeType, 'video') !== false;
    $has3D = has_3d_models($resource);
    $hasAV = $hasAudio || $hasVideo;

    // Override default viewer based on content type
    // Note: viewer names must match JavaScript IiifViewerManager expectations
    if ($hasPdf) {
        $opts['viewer'] = 'pdfjs';
    } elseif ($hasAV) {
        $opts['viewer'] = 'av';
    } elseif ($has3D) {
        $opts['viewer'] = 'model-viewer';
    }

    // Generate unique viewer ID
    $viewerId = 'iiif-viewer-' . $objectId . '-' . substr(md5(uniqid()), 0, 8);
    
    // Build HTML
    $html = '';

    // For PDF content - use simple embedded viewer without IIIF complexity
    if ($hasPdf) {
        $pdfUrl = get_digital_object_url($primaryDo);
        $html .= '<div class="pdf-viewer-container">';
        $html .= ahg_iiif_render_pdf_viewer_html($viewerId, $pdfUrl, $viewerHeight, true);
        $html .= '</div>';
        return $html;
    }

    // For images - use full IIIF viewer
    $html .= get_iiif_viewer_css($pluginPath);
    $html .= '<div class="iiif-viewer-container" id="container-' . $viewerId . '">';

    // Determine if we have actual images (not just PDF/AV/3D)
    $hasImages = !$hasPdf && !$hasAV && !$has3D;

    // Viewer toggle buttons (only for images)
    $html .= ahg_iiif_render_viewer_toggle($viewerId, $opts['viewer'], $has3D, $hasPdf, $hasAV, $hasImages);

    // Controls bar (only for image content)
    if ($hasImages) {
        $html .= ahg_iiif_render_viewer_controls($viewerId, $manifestUrl, $objectId, $opts);
    }

    // Main viewer area
    $html .= '<div class="viewer-area" id="viewer-area-' . $viewerId . '">';

    // OpenSeadragon viewer (only for images)
    if ($hasImages) {
        $html .= '<div id="osd-' . $viewerId . '" class="osd-viewer" style="width:100%;height:' . $viewerHeight . ';background:#1a1a1a;border-radius:8px;"></div>';

        // Mirador wrapper (hidden by default)
        $html .= '<div id="mirador-wrapper-' . $viewerId . '" class="mirador-wrapper" style="display:none;position:relative;">';
        $html .= '<button id="close-mirador-' . $viewerId . '" class="btn btn-sm btn-light" style="position:absolute;top:10px;right:10px;z-index:1000;">';
        $html .= '<i class="fas fa-times"></i> Close</button>';
        $html .= '<div id="mirador-' . $viewerId . '" style="width:100%;height:700px;"></div>';
        $html .= '</div>';
    }

    // 3D viewer (if applicable) - show by default for 3D content
    if ($has3D) {
        $model = get_primary_3d_model($resource);
        if ($model) {
            $html .= ahg_iiif_render_3d_viewer_html($viewerId, $model, $viewerHeight, $baseUrl, true);
        }
    }

    // Audio/Video viewer (if applicable) - show by default for AV content
    if ($hasAV) {
        $html .= ahg_iiif_render_av_viewer_html($viewerId, $primaryDo, $viewerHeight, $baseUrl, true);
    }
    
    $html .= '</div>'; // viewer-area
    
    // Thumbnail strip for multi-image
    if (count($digitalObjects) > 1) {
        $html .= ahg_iiif_render_thumbnail_strip($viewerId, $digitalObjects, $cantaloupeUrl);
    }
    
    $html .= '</div>'; // container
    
    // JavaScript initialization
    $pdfUrl = $hasPdf ? get_digital_object_url($primaryDo) : null;
    $html .= ahg_iiif_render_viewer_javascript($viewerId, $objectId, $manifestUrl, $opts, [
        'has3D' => $has3D,
        'hasPdf' => $hasPdf,
        'hasAV' => $hasAV,
        'pdfUrl' => $pdfUrl,
        'baseUrl' => $baseUrl,
        'cantaloupeUrl' => $cantaloupeUrl,
        'pluginPath' => $pluginPath,
    ]);
    
    return $html;
}

/**
 * Check if resource has 3D models
 *
 * Uses ahg3DModelPlugin's has_3d_model() if available (authoritative),
 * otherwise falls back to checking digital object file extensions.
 */
if (!function_exists('has_3d_models')):
function has_3d_models($resource)
{
    // Use ahg3DModelPlugin's function if available (checks object_3d_model table)
    if (function_exists('has_3d_model')) {
        return has_3d_model($resource);
    }
    // Fallback: check digital object extensions
    return get_primary_3d_model($resource) !== null;
}
endif;

/**
 * Get primary 3D model for resource
 *
 * Uses ahg3DModelPlugin's get_primary_3d_model() if available (from object_3d_model table),
 * otherwise falls back to detecting 3D files from standard digital objects.
 *
 * Note: ahg3DModelPlugin defines get_primary_3d_model() in Model3DHelper.php.
 * This fallback only activates when that plugin is not installed.
 */
if (!function_exists('get_primary_3d_model')):
function get_primary_3d_model($resource)
{
    $extensions = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'usdz'];

    try {
        $digitalObjects = get_digital_objects($resource);

        foreach ($digitalObjects as $do) {
            $name = is_object($do) ? $do->name : ($do['name'] ?? '');
            $path = is_object($do) ? $do->path : ($do['path'] ?? '');
            $id = is_object($do) ? $do->id : ($do['id'] ?? 0);
            $objectId = is_object($do) ? $do->object_id : ($do['object_id'] ?? $resource->id);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (in_array($ext, $extensions)) {
                // Return as object with expected properties
                return (object)[
                    'id' => $id,
                    'object_id' => $objectId,
                    'filename' => $name,
                    'path' => $path,
                    'format' => $ext,
                    'title' => pathinfo($name, PATHINFO_FILENAME),
                    'auto_rotate' => true,
                    'ar_enabled' => true,
                    'camera_orbit' => '0deg 75deg 105%',
                    'background_color' => '#f5f5f5',
                    'poster_image' => null,
                ];
            }
        }
        return null;
    } catch (Exception $e) {
        return null;
    }
}
endif;

/**
 * Get digital object URL - with redaction support
 */
function get_digital_object_url($digitalObject)
{
    $objectId = $digitalObject->objectId ?? null;
    $mimeType = $digitalObject->mimeType ?? '';
    
    // Check if it's a PDF and has redactions
    if ($objectId && stripos($mimeType, 'pdf') !== false) {
        // Check if redactions exist for this object
        $hasRedactions = \Illuminate\Database\Capsule\Manager::table('privacy_visual_redaction')
            ->where('object_id', $objectId)
            ->exists();
        
        if ($hasRedactions) {
            // Return redacted PDF URL
            return sfContext::getInstance()->getRouting()->generate(null, [
                'module' => 'privacyAdmin',
                'action' => 'downloadPdf',
                'id' => $objectId
            ]);
        }
    }
    
    // Original logic - return direct path
    $path = $digitalObject->path ?? '';
    $name = $digitalObject->name ?? '';
    $fullPath = rtrim($path, '/') . '/' . $name;
    return '/' . ltrim($fullPath, '/');
}
/**
 * Build IIIF identifier from path and name
 */
function build_iiif_identifier($path, $name)
{
    $path = trim($path ?? '', '/');
    return str_replace('/', '_SL_', $path . '/' . $name);
}

/**
 * Render viewer toggle buttons
 * Only shows relevant buttons based on content type
 */
function ahg_iiif_render_viewer_toggle($viewerId, $defaultViewer, $has3D, $hasPdf, $hasAV, $hasImages = true)
{
    // For PDF/AV/3D only content, don't show toggle - just show the appropriate viewer
    if (($hasPdf || $hasAV || $has3D) && !$hasImages) {
        return '';
    }

    $html = '<div class="viewer-toggle btn-group btn-group-sm mb-2" role="group">';

    // Only show image viewer buttons if there are actual images
    if ($hasImages && !$hasPdf && !$hasAV) {
        // OpenSeadragon button
        $activeOsd = ($defaultViewer === 'openseadragon') ? ' active' : '';
        $html .= '<button type="button" class="btn btn-outline-primary' . $activeOsd . '" id="btn-osd-' . $viewerId . '" title="Image Viewer">';
        $html .= '<i class="fas fa-image"></i></button>';

        // Mirador button
        $activeMirador = ($defaultViewer === 'mirador') ? ' active' : '';
        $html .= '<button type="button" class="btn btn-outline-primary' . $activeMirador . '" id="btn-mirador-' . $viewerId . '" title="Mirador Viewer">';
        $html .= '<i class="fas fa-columns"></i></button>';
    }

    // 3D button (if has 3D models)
    if ($has3D) {
        $html .= '<button type="button" class="btn btn-outline-primary" id="btn-3d-' . $viewerId . '" title="3D Model Viewer">';
        $html .= '<i class="fas fa-cube"></i></button>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Include viewer CSS (only once per page)
 */
function get_iiif_viewer_css($pluginPath)
{
    static $cssIncluded = false;

    if ($cssIncluded) {
        return '';
    }

    $cssIncluded = true;

    $html = '<style>
.iiif-viewer-container { margin-bottom: 1rem; }
.viewer-area { position: relative; }
.osd-viewer { border-radius: 8px; }
.thumb-item { transition: border-color 0.2s; }
.thumb-item:hover, .thumb-item.active { border-color: #0d6efd !important; }
.mirador-wrapper { border-radius: 8px; overflow: hidden; }
.pdf-wrapper canvas { display: block; margin: 0 auto; }
</style>';

    return $html;
}

/**
 * Render viewer controls bar
 */
function ahg_iiif_render_viewer_controls($viewerId, $manifestUrl, $objectId, $opts)
{
    $html = '<div class="viewer-controls mb-2 d-flex justify-content-between align-items-center">';
    
    // IIIF badge
    $html .= '<div>';
    $html .= '<span class="badge bg-info"><i class="fas fa-certificate me-1"></i>IIIF</span>';
    $html .= '<small class="text-muted ms-2 d-none d-sm-inline">Presentation API 2.1</small>';
    $html .= '</div>';
    
    // Control buttons
    $html .= '<div class="btn-group btn-group-sm">';
    
    // New window
    $html .= '<button type="button" class="btn btn-outline-secondary" id="btn-newwin-' . $viewerId . '" title="Open in new window">';
    $html .= '<i class="fas fa-external-link-alt"></i></button>';
    
    // Fullscreen
    if ($opts['enable_fullscreen']) {
        $html .= '<button type="button" class="btn btn-outline-secondary" id="btn-fullscreen-' . $viewerId . '" title="Fullscreen">';
        $html .= '<i class="fas fa-expand"></i></button>';
    }
    
    // Download
    if ($opts['enable_download']) {
        $html .= '<button type="button" class="btn btn-outline-secondary" id="btn-download-' . $viewerId . '" title="Download">';
        $html .= '<i class="fas fa-download"></i></button>';
    }
    
    // Annotations
    if ($opts['enable_annotations']) {
        $html .= '<button type="button" class="btn btn-outline-secondary" id="btn-annotations-' . $viewerId . '" title="Toggle Annotations">';
        $html .= '<i class="fas fa-comment-dots"></i></button>';
    }
    
    // Copy manifest URL
    $html .= '<button type="button" class="btn btn-outline-secondary" id="btn-manifest-' . $viewerId . '" title="Copy IIIF Manifest URL" data-url="' . htmlspecialchars($manifestUrl) . '">';
    $html .= '<i class="fas fa-link"></i></button>';
    
    $html .= '</div></div>';
    
    return $html;
}

/**
 * Render PDF viewer HTML
 * Uses browser's native PDF viewer via iframe for best compatibility
 */
function ahg_iiif_render_pdf_viewer_html($viewerId, $pdfUrl, $height, $showByDefault = false)
{
    $displayStyle = $showByDefault ? '' : 'display:none;';

    $html = '<div id="pdf-wrapper-' . $viewerId . '" class="pdf-wrapper" style="' . $displayStyle . '">';

    // Toolbar with download and fullscreen
    $html .= '<div class="pdf-toolbar mb-2 d-flex justify-content-between align-items-center">';
    $html .= '<span class="badge bg-danger"><i class="fas fa-file-pdf me-1"></i>PDF Document</span>';
    $html .= '<div class="btn-group btn-group-sm">';
    $html .= '<a href="' . htmlspecialchars($pdfUrl) . '" target="_blank" class="btn btn-outline-secondary" title="Open in new tab">';
    $html .= '<i class="fas fa-external-link-alt"></i></a>';
    $html .= '<a href="' . htmlspecialchars($pdfUrl) . '" download class="btn btn-outline-secondary" title="Download PDF">';
    $html .= '<i class="fas fa-download"></i></a>';
    $html .= '</div></div>';

    // Embedded PDF viewer using iframe (uses browser's native PDF viewer)
    $html .= '<iframe id="pdf-frame-' . $viewerId . '" ';
    $html .= 'src="' . htmlspecialchars($pdfUrl) . '" ';
    $html .= 'style="width:100%;height:' . $height . ';border:none;border-radius:8px;background:#525659;" ';
    $html .= 'title="PDF Viewer"></iframe>';

    $html .= '</div>';

    return $html;
}

/**
 * Render 3D viewer HTML (uses standard digital object uploads)
 */
function ahg_iiif_render_3d_viewer_html($viewerId, $model, $height, $baseUrl, $showByDefault = false)
{
    // Use standard digital object path (path already includes /uploads/)
    $path = trim($model->path ?? '', '/');
    // Don't add /uploads/ if path already starts with it
    $modelUrl = $baseUrl . '/' . $path . '/' . $model->filename;
    $arAttr = !empty($model->ar_enabled) ? 'ar ar-modes="webxr scene-viewer quick-look"' : '';
    $autoRotate = !empty($model->auto_rotate) ? 'auto-rotate' : '';
    $cameraOrbit = $model->camera_orbit ?? '0deg 75deg 105%';
    $bgColor = $model->background_color ?? '#f5f5f5';
    $poster = !empty($model->poster_image) ? 'poster="' . $baseUrl . $model->poster_image . '"' : '';

    $displayStyle = $showByDefault ? '' : 'display:none;';
    $html = '<div id="model-wrapper-' . $viewerId . '" class="model-wrapper" style="' . $displayStyle . '">';
    $html .= '<model-viewer id="model-' . $viewerId . '" ';
    $html .= 'src="' . $modelUrl . '" ';
    $html .= $poster . ' ';
    $html .= $arAttr . ' ';
    $html .= $autoRotate . ' ';
    $html .= 'camera-controls touch-action="pan-y" ';
    $html .= 'camera-orbit="' . $cameraOrbit . '" ';
    $html .= 'style="width:100%;height:' . $height . ';background-color:' . $bgColor . ';border-radius:8px;">';
    $html .= '<button slot="ar-button" class="btn btn-primary" style="position:absolute;bottom:16px;right:16px;">';
    $html .= '<i class="fas fa-cube me-1"></i>View in AR</button>';
    $html .= '</model-viewer></div>';
    
    return $html;
}

/**
 * Render audio/video viewer HTML
 */
function ahg_iiif_render_av_viewer_html($viewerId, $digitalObject, $height, $baseUrl, $showByDefault = false)
{
    $mediaUrl = get_digital_object_url($digitalObject);
    $mimeType = $digitalObject->mimeType ?? 'video/mp4';
    $isAudio = stripos($mimeType, 'audio') !== false;

    $displayStyle = $showByDefault ? '' : 'display:none;';
    $html = '<div id="av-wrapper-' . $viewerId . '" class="av-wrapper" style="' . $displayStyle . '">';
    
    if ($isAudio) {
        $html .= '<audio id="audio-' . $viewerId . '" controls style="width:100%;">';
        $html .= '<source src="' . $mediaUrl . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the audio element.</audio>';
    } else {
        $html .= '<video id="video-' . $viewerId . '" controls style="width:100%;height:' . $height . ';background:#000;border-radius:8px;">';
        $html .= '<source src="' . $mediaUrl . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the video element.</video>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render thumbnail strip
 */
function ahg_iiif_render_thumbnail_strip($viewerId, $digitalObjects, $cantaloupeUrl)
{
    $html = '<div class="thumbnail-strip mt-2" id="thumbs-' . $viewerId . '" style="display:flex;gap:8px;overflow-x:auto;padding:8px 0;">';
    
    foreach ($digitalObjects as $index => $do) {
        $iiifId = build_iiif_identifier($do->path, $do->name);
        $thumbUrl = $cantaloupeUrl . '/' . urlencode($iiifId) . '/full/100,/0/default.jpg';
        $activeClass = $index === 0 ? 'active' : '';
        
        $html .= '<div class="thumb-item ' . $activeClass . '" data-index="' . $index . '" style="flex-shrink:0;cursor:pointer;border:2px solid transparent;border-radius:4px;">';
        $html .= '<img src="' . $thumbUrl . '" alt="Page ' . ($index + 1) . '" style="height:80px;display:block;">';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Render viewer JavaScript initialization
 */
function ahg_iiif_render_viewer_javascript($viewerId, $objectId, $manifestUrl, $opts, $config)
{
    $flagsJson = json_encode([
        'has3D' => $config['has3D'],
        'hasPdf' => $config['hasPdf'],
        'hasAV' => $config['hasAV'],
        'pdfUrl' => $config['pdfUrl'] ?? null,
        'enableAnnotations' => $opts['enable_annotations'],
    ]);
    
    $osdConfig = json_encode([
        'showNavigator' => true,
        'navigatorPosition' => 'BOTTOM_RIGHT',
        'showRotationControl' => true,
        'showFlipControl' => true,
        'gestureSettingsMouse' => ['scrollToZoom' => true],
    ]);
    
    $miradorConfig = json_encode([
        'sideBarOpenByDefault' => false,
        'defaultSideBarPanel' => 'info',
    ]);
    
    // OpenSeadragon loaded from CDN by the viewer manager module
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
    $js = '<script type="module"' . $nonceAttr . '>' . "\n";
    $js .= 'import { IiifViewerManager } from "' . $config['pluginPath'] . '/js/iiif-viewer-manager.js";' . "\n";
    $js .= 'document.addEventListener("DOMContentLoaded", function() {' . "\n";
    $js .= '    const viewer = new IiifViewerManager("' . $viewerId . '", {' . "\n";
    $js .= '        objectId: ' . $objectId . ',' . "\n";
    $js .= '        manifestUrl: "' . $manifestUrl . '",' . "\n";
    $js .= '        baseUrl: "' . $config['baseUrl'] . '",' . "\n";
    $js .= '        cantaloupeUrl: "' . $config['cantaloupeUrl'] . '",' . "\n";
    $js .= '        pluginPath: "' . $config['pluginPath'] . '",' . "\n";
    $js .= '        defaultViewer: "' . $opts['viewer'] . '",' . "\n";
    $js .= '        flags: ' . $flagsJson . ',' . "\n";
    $js .= '        osdConfig: ' . $osdConfig . ',' . "\n";
    $js .= '        miradorConfig: ' . $miradorConfig . "\n";
    $js .= '    });' . "\n";
    $js .= '    viewer.init();' . "\n";
    $js .= '});' . "\n";
    $js .= '</script>' . "\n";
    
    return $js;
}

/**
 * Render standalone 3D model viewer
 */
function render_3d_model_viewer($resource, $options = [])
{
    $model = get_primary_3d_model($resource);

    if (!$model) {
        return '';
    }

    $baseUrl = get_iiif_base_url();
    $height = $options['height'] ?? '600px';
    $viewerId = 'model-viewer-' . $resource->id . '-' . substr(md5(uniqid()), 0, 8);
    
    $html = '<div class="iiif-viewer-container">';
    $html .= ahg_iiif_render_3d_viewer_html($viewerId, $model, $height, $baseUrl);
    $html .= '</div>';
    
    // Auto-show 3D viewer
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', ' nonce="', $n) . '"' : '';
    $html .= '<script' . $nonceAttr . '>';
    $html .= 'document.getElementById("model-wrapper-' . $viewerId . '").style.display = "block";';
    $html .= '</script>';
    
    // Model-viewer script
    $html .= '<script type="module" src="/plugins/ahgCorePlugin/web/js/vendor/model-viewer.min.js"></script>';
    
    return $html;
}

/**
 * Simple function to just render an image via IIIF
 * Useful for thumbnails or simple displays
 */
function render_iiif_image($identifier, $options = [])
{
    $cantaloupeUrl = get_iiif_cantaloupe_url();
    
    $region = $options['region'] ?? 'full';
    $size = $options['size'] ?? 'max';
    $rotation = $options['rotation'] ?? '0';
    $quality = $options['quality'] ?? 'default';
    $format = $options['format'] ?? 'jpg';
    
    $url = $cantaloupeUrl . '/' . urlencode($identifier) . '/' . $region . '/' . $size . '/' . $rotation . '/' . $quality . '.' . $format;
    
    $alt = $options['alt'] ?? 'Image';
    $class = $options['class'] ?? '';
    $style = $options['style'] ?? '';
    
    return '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '" style="' . $style . '">';
}
