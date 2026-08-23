<?php

if (!function_exists('iiif_table_exists')) {
    /**
     * Is a table owned by another plugin present?
     *
     * This helper reads tables belonging to optional plugins - redactions from
     * ahgPrivacyPlugin among them - and a missing optional plugin is an ordinary
     * state rather than an exceptional one. Checked rather than caught, and
     * cached per request, because a viewer page can ask repeatedly.
     */
    function iiif_table_exists(string $table): bool
    {
        static $seen = [];

        if (isset($seen[$table])) {
            return $seen[$table];
        }

        try {
            return $seen[$table] = \Illuminate\Database\Capsule\Manager::schema()->hasTable($table);
        } catch (\Throwable $e) {
            return $seen[$table] = false;
        }
    }
}



/**
 * IIIF Viewer Helper for AtoM Integration
 *
 * Drop-in replacement for existing digital object viewing in AtoM
 * Replaces: ZoomPan, OpenSeadragon, video/audio players
 *
 * Part of: ahgIiifPlugin
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 * @version 1.1.0
 */

/**
 * Get the global RendererRegistry instance (lazy-loaded singleton).
 *
 * @return \AhgIiif\Services\RendererRegistry
 */
function get_renderer_registry()
{
    static $registry = null;

    if ($registry === null) {
        $pluginDir = sfConfig::get('sf_plugins_dir', '') . '/ahgIiifPlugin';
        require_once $pluginDir . '/lib/Services/RendererRegistry.php';
        require_once $pluginDir . '/lib/Services/Renderers/RendererInterface.php';

        $registry = new \AhgIiif\Services\RendererRegistry();
        // Auto-discovery will load all renderers from Renderers/ directory
    }

    return $registry;
}

/**
 * Detect the best viewer name for a given MIME type using the RendererRegistry.
 *
 * @param string $mimeType
 * @param array $context
 * @return string|null Renderer name or null if no match
 */
function detect_viewer_type(string $mimeType, array $context = []): ?string
{
    $registry = get_renderer_registry();
    $renderer = $registry->getRenderer($mimeType, $context);

    return $renderer ? $renderer->getName() : null;
}

/**
 * Get an IIIF viewer setting from the iiif_viewer_settings DB table.
 * Falls back to sfConfig then to $default.
 *
 * @param string $key     Setting key (e.g. 'viewer_height', 'enable_fullscreen')
 * @param mixed  $default Fallback value
 * @return mixed
 */
function get_iiif_setting(string $key, $default = null)
{
    static $cache = null;

    if ($cache === null) {
        $cache = [];
        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('iiif_viewer_settings')->get();
            foreach ($rows as $row) {
                $cache[$row->setting_key] = $row->setting_value;
            }
        } catch (\Exception $e) {
            // Table may not exist
        }
    }

    if (isset($cache[$key]) && $cache[$key] !== '') {
        return $cache[$key];
    }

    // Fallback to sfConfig app_iiif_<key>
    $sfKey = 'app_iiif_' . $key;
    $sfVal = sfConfig::get($sfKey);
    if ($sfVal !== null) {
        return $sfVal;
    }

    return $default;
}

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
    // Check if IIIF is enabled in config or DB settings
    $dbEnabled = get_iiif_setting('iiif_enabled', null);
    $configEnabled = sfConfig::get('app_iiif_enabled', false);
    if ($dbEnabled !== null ? $dbEnabled === '0' : !$configEnabled) {
        $available = false;
        return false;
    }
    $cantaloupeUrl = get_iiif_cantaloupe_url();
    if (empty($cantaloupeUrl)) {
        $available = false;

        return false;
    }

    // A configured URL is not a running image server.
    //
    // This used to return true as soon as a URL was set. With Cantaloupe absent -
    // which is the normal state of an install that has not deployed it - the
    // viewer initialised anyway, the manifest yielded no usable tiles, and every
    // record with an image showed "No images found in manifest". The manifest was
    // never the problem; the image server was not there, and the message sent
    // people looking in the wrong place.
    //
    // Probing costs one short request per process, cached below, and only matters
    // on pages that are about to render a viewer. Where the server does not
    // answer the caller falls back to render_standard_viewer(), which needs no
    // tile server at all.
    $available = iiif_image_server_responds($cantaloupeUrl);

    return $available;
}

/**
 * Does the IIIF image server answer?
 *
 * Deliberately generous about what counts as answering: any HTTP status means
 * something is listening and able to reply. A 403 or 404 from Cantaloupe's root
 * is still a working server - only a connection failure or timeout is treated as
 * absent.
 */
function iiif_image_server_responds(string $baseUrl, float $timeout = 1.5): bool
{
    static $seen = [];

    $key = rtrim($baseUrl, '/');
    if (array_key_exists($key, $seen)) {
        return $seen[$key];
    }

    $seen[$key] = false;

    if (!function_exists('curl_init')) {
        // Without curl, assume configured means available rather than disabling
        // a viewer that may well work.
        return $seen[$key] = true;
    }

    try {
        $ch = curl_init($key.'/');
        curl_setopt_array($ch, [
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => (int) ($timeout * 1000),
            CURLOPT_TIMEOUT_MS => (int) ($timeout * 1000),
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);

        // Only DNS, connect and timeout failures mean "absent" - which is what the
        // comment above always intended, but a bare status check did not deliver.
        //
        // A server can answer in ways curl reports as an error while still plainly
        // being there: an HTTP/2 stream reset after the headers (errno 92) or an
        // empty reply (errno 52). Measured on PSIS, where the site is HTTPS and the
        // host cannot reach its own public :443 endpoint, so EVERY variant - HEAD,
        // GET, HTTP/1.1, ranged GET - came back status 0. Cantaloupe was running
        // and answering on :8182 the whole time, and every record silently showed
        // the plain-image fallback instead of a viewer.
        //
        // 6 = COULDNT_RESOLVE_HOST, 7 = COULDNT_CONNECT, 28 = OPERATION_TIMEDOUT.
        // Named constants are not used: CURLE_HTTP2 and CURLE_HTTP2_STREAM are not
        // defined in every PHP build, and referencing an undefined constant is a
        // fatal in PHP 8.
        if (0 === $status && !in_array($errno, [6, 7, 28], true)) {
            return $seen[$key] = true;
        }
        $seen[$key] = $status > 0;
    } catch (\Throwable $e) {
        $seen[$key] = false;
    }

    return $seen[$key];
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
        $html .= '<img src="' . $displayPath . '" alt="' . esc_entities($name) . '" class="img-fluid ahg-media-capped">';
        $html .= '</a>';
    } elseif (strpos($mimeType, 'video') !== false) {
        $html .= '<video controls class="w-100 ahg-media-capped">';
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
    // Check show_on_view / show_on_browse settings
    try {
        $action = sfContext::getInstance()->getActionName();
        $isView = in_array($action, ['index', 'view', 'show'], true);
        $isBrowse = in_array($action, ['browse', 'list', 'search'], true);
        if ($isView && get_iiif_setting('show_on_view', '1') !== '1') {
            return render_standard_viewer($resource, $options);
        }
        if ($isBrowse && get_iiif_setting('show_on_browse', '1') !== '1') {
            return render_standard_viewer($resource, $options);
        }
    } catch (\Exception $e) {
        // Context may not be available in CLI
    }

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
    
    // Configuration - read from iiif_viewer_settings DB, fallback to sfConfig
    $baseUrl = get_iiif_base_url();
    $cantaloupeUrl = get_iiif_cantaloupe_url();
    $pluginPath = sfConfig::get('app_iiif_plugin_path', '/plugins/ahgIiifPlugin/web');
    $defaultViewer = get_iiif_setting('viewer_type', 'openseadragon');
    $enableAnnotations = get_iiif_setting('enable_annotations', null);
    $enableAnnotations = ($enableAnnotations !== null) ? ($enableAnnotations === '1') : sfConfig::get('app_iiif_enable_annotations', true);
    $viewerHeight = $options['height'] ?? get_iiif_setting('viewer_height', '600px');
    $bgColor = get_iiif_setting('background_color', '#1a1a1a');

    // Merge options — DB settings as defaults, $options overrides
    $opts = array_merge([
        'viewer' => $defaultViewer,
        'height' => $viewerHeight,
        'enable_annotations' => $enableAnnotations,
        'enable_download' => false,
        'enable_fullscreen' => get_iiif_setting('enable_fullscreen', '1') === '1',
        'show_zoom_controls' => get_iiif_setting('show_zoom_controls', '1') === '1',
        'background_color' => $bgColor,
        'default_zoom' => (int) get_iiif_setting('default_zoom', 1),
    ], $options);
    
    // Build manifest URL. Cache-bust it with the current master digital object's
    // id + checksum so that replacing/updating the image shows immediately - the
    // manifest URL is otherwise stable, so the browser serves a cached manifest
    // that still points at the OLD image until a manual hard refresh.
    $manifestUrl = $baseUrl . '/iiif/manifest/' . $slug;
    try {
        $__doVer = \Illuminate\Database\Capsule\Manager::table('digital_object')
            ->where('object_id', $objectId)
            ->orderBy('id')
            ->first(['id', 'checksum']);
        if ($__doVer) {
            $manifestUrl .= (false === strpos($manifestUrl, '?') ? '?' : '&')
                . 'v=' . $__doVer->id . '-' . substr((string) $__doVer->checksum, 0, 12);
        }
    } catch (\Throwable $__e) {
        // best effort - fall back to the unversioned URL
    }

    // Determine content type flags
    $hasPdf = stripos($mimeType, 'pdf') !== false;
    $hasAudio = stripos($mimeType, 'audio') !== false;
    $hasVideo = stripos($mimeType, 'video') !== false;
    $has3D = has_3d_models($resource);
    $hasAV = $hasAudio || $hasVideo;

    // Override default viewer based on content type using RendererRegistry
    $context = ['has3D' => $has3D];
    $detectedViewer = detect_viewer_type($mimeType, $context);
    if ($detectedViewer && $detectedViewer !== 'openseadragon' && $detectedViewer !== 'mirador') {
        $opts['viewer'] = $detectedViewer;
    } elseif ($hasPdf) {
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
        // Geometry comes from viewer-switch.css; only the configured height and
        // background vary, so only those go in a nonce-carrying style element.
        $html .= ahg_iiif_viewer_style_block(
            '#osd-' . $viewerId . '{height:' . esc_specialchars($viewerHeight)
            . ';background:' . esc_specialchars($opts['background_color'] ?? '#1a1a1a') . ';}'
        );
        $html .= '<div id="osd-' . $viewerId . '" class="osd-viewer"></div>';

        // Mirador wrapper (hidden by default)
        $html .= '<div id="mirador-wrapper-' . $viewerId . '" class="mirador-wrapper ahg-hidden">';
        $html .= '<button id="close-mirador-' . $viewerId . '" class="btn btn-sm btn-light ahg-mirador-close">';
        $html .= '<i class="fas fa-times"></i> Close</button>';
        $html .= '<div id="mirador-' . $viewerId . '" class="ahg-mirador-frame"></div>';
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
    $extensions = ['glb', 'gltf', 'obj', 'stl', 'ply', 'usdz'];

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
        // ahgPrivacyPlugin owns this table and is optional, so its absence must
        // mean "no redactions" rather than a fatal on the PDF viewer path.
        $hasRedactions = iiif_table_exists('privacy_visual_redaction')
            && \Illuminate\Database\Capsule\Manager::table('privacy_visual_redaction')
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

/**
 * A <style> element carrying the CSP nonce, for rules whose values are only known
 * at runtime (configured viewer height, background colour).
 *
 * Static geometry belongs in web/css/viewer-switch.css. This exists for the values
 * that cannot: a class cannot express "whatever height the administrator set".
 *
 * Why not a style="" attribute, which is what this replaces: a nonce covers style
 * and script ELEMENTS, never style ATTRIBUTES. Under a policy without
 * 'unsafe-inline' the attribute is dropped, the container gets no height, and the
 * viewer collapses to nothing with no error explaining why.
 */
function ahg_iiif_viewer_style_block(string $css): string
{
    if ('' === trim($css)) {
        return '';
    }

    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';

    return '<style' . $nonceAttr . '>' . $css . '</style>';
}

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

    // A <style> element with no nonce is blocked outright under this estate's CSP,
    // taking the viewer layout with it. The nonce is not optional here.
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? ' ' . preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';

    // Mirador 3 styles itself at runtime through Material-UI's JSS, which creates
    // <style> elements from JavaScript. Those are subject to style-src exactly
    // like any other style element, so under a nonce policy they are all dropped
    // and Mirador renders as unstyled dark blocks with working tiles - which reads
    // as a broken viewer rather than as a CSP problem.
    //
    // JSS looks for this specific tag and stamps its content onto every sheet it
    // creates. The selector is meta[property="csp-nonce"]; the name is not ours to
    // choose. Without it there is no way to nonce styles that do not exist until
    // runtime.
    // The stylesheet must be linked HERE, not only from ViewerInjector.
    //
    // ViewerInjector links it only when it renders the viewer switcher. Where the
    // viewer is emitted by this helper instead, the classes it defines have no
    // stylesheet behind them: .ahg-mirador-frame computes height:0 and Mirador
    // lays out at zero. OpenSeadragon escaped that only because its height comes
    // from the per-id <style> block below rather than from a class - which is
    // precisely why OSD worked and Mirador did not.
    //
    // A <link> is governed by style-src 'self', so it needs no nonce.
    $html = '<link rel="stylesheet" href="/plugins/ahgIiifPlugin/web/css/viewer-switch.css">';

    if ($n) {
        $nonceValue = trim(preg_replace('/^nonce=/', '', $n), '"\'');
        $html .= '<meta property="csp-nonce" content="' . esc_specialchars($nonceValue) . '">';
    }

    $html .= '<style' . $nonceAttr . '>
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
    $hiddenClass = $showByDefault ? '' : ' ahg-hidden';

    $html = '<div id="pdf-wrapper-' . $viewerId . '" class="pdf-wrapper' . $hiddenClass . '">';

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
    $html .= ahg_iiif_viewer_style_block(
        '#pdf-frame-' . $viewerId . '{height:' . esc_specialchars((string) $height) . ';background:#525659;}'
    );
    $html .= '<iframe id="pdf-frame-' . $viewerId . '" class="ahg-pdf-frame" ';
    $html .= 'src="' . htmlspecialchars($pdfUrl) . '" ';
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

    $hiddenClass = $showByDefault ? '' : ' ahg-hidden';
    $html = '<div id="model-wrapper-' . $viewerId . '" class="model-wrapper' . $hiddenClass . '">';
    $html .= '<model-viewer id="model-' . $viewerId . '" ';
    $html .= 'src="' . $modelUrl . '" ';
    $html .= $poster . ' ';
    $html .= ahg_iiif_viewer_style_block(
        'model-viewer#' . $viewerId . '-model{height:' . esc_specialchars((string) $height)
        . ';background-color:' . esc_specialchars((string) $bgColor) . ';}'
    );
    $html .= $arAttr . ' ';
    $html .= $autoRotate . ' ';
    $html .= 'camera-controls touch-action="pan-y" ';
    $html .= 'camera-orbit="' . $cameraOrbit . '" ';
    $html .= 'class="ahg-model-frame">';
    $html .= '<button slot="ar-button" class="btn btn-primary ahg-ar-button">';
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

    $hiddenClass = $showByDefault ? '' : ' ahg-hidden';
    $html = '<div id="av-wrapper-' . $viewerId . '" class="av-wrapper' . $hiddenClass . '">';
    
    if ($isAudio) {
        $html .= '<audio id="audio-' . $viewerId . '" controls class="ahg-av-audio">';
        $html .= '<source src="' . $mediaUrl . '" type="' . $mimeType . '">';
        $html .= 'Your browser does not support the audio element.</audio>';
    } else {
        $html .= ahg_iiif_viewer_style_block(
            '#video-' . $viewerId . '{height:' . esc_specialchars((string) $height) . ';background:#000;}'
        );
        $html .= '<video id="video-' . $viewerId . '" controls class="ahg-av-video">';
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
    $html = '<div class="thumbnail-strip ahg-thumb-strip mt-2" id="thumbs-' . $viewerId . '">';
    
    foreach ($digitalObjects as $index => $do) {
        $iiifId = build_iiif_identifier($do->path, $do->name);
        $thumbUrl = $cantaloupeUrl . '/' . urlencode($iiifId) . '/full/100,/0/default.jpg';
        $activeClass = $index === 0 ? 'active' : '';
        
        $html .= '<div class="thumb-item ahg-thumb-item ' . $activeClass . '" data-index="' . $index . '">';
        $html .= '<img src="' . $thumbUrl . '" alt="Page ' . ($index + 1) . '">';
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
    
    $showZoom = $opts['show_zoom_controls'] ?? true;
    $fullscreen = $opts['enable_fullscreen'] ?? true;
    $defaultZoom = $opts['default_zoom'] ?? 1;

    // The stored setting documents "1 = fit to viewer", but OpenSeadragon's
    // defaultZoomLevel uses 0 for fit and 1 for an absolute 1:1 zoom - which opens
    // the image larger than the viewport, spilling past the edge (and looking blank
    // when it lands on a corner). Map the "fit" intent (<= 1) to OSD's 0; only
    // values above 1 request a real zoom-in.
    $osdZoomLevel = ((int) $defaultZoom <= 1) ? 0 : (int) $defaultZoom;

    $osdConfig = json_encode([
        'showNavigator' => true,
        'navigatorPosition' => 'BOTTOM_RIGHT',
        'showRotationControl' => true,
        'showFlipControl' => true,
        'showZoomControl' => (bool) $showZoom,
        'showFullPageControl' => (bool) $fullscreen,
        'defaultZoomLevel' => $osdZoomLevel,
        'gestureSettingsMouse' => ['scrollToZoom' => true],
    ]);
    
    $miradorConfig = json_encode([
        'sideBarOpenByDefault' => false,
        'defaultSideBarPanel' => 'info',
    ]);
    
    // OpenSeadragon loaded locally by the viewer manager module
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
    $carouselConfig = json_encode([
        'autoplay' => get_iiif_setting('carousel_autoplay', '1') === '1',
        'interval' => (int) get_iiif_setting('carousel_interval', 5000),
        'showThumbnails' => get_iiif_setting('carousel_show_thumbnails', '1') === '1',
        'showControls' => get_iiif_setting('carousel_show_controls', '1') === '1',
    ]);
    $js .= '        osdConfig: ' . $osdConfig . ',' . "\n";
    $js .= '        miradorConfig: ' . $miradorConfig . ',' . "\n";
    $js .= '        carouselConfig: ' . $carouselConfig . "\n";
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
    
    // A style attribute is dropped under any enforcing CSP, so emit one only when a
    // caller explicitly asks. Prefer passing 'class'.
    $styleAttr = '' !== trim((string) $style) ? ' style="' . htmlspecialchars($style) . '"' : '';

    return '<img src="' . htmlspecialchars($url) . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '"' . $styleAttr . '>';
}
