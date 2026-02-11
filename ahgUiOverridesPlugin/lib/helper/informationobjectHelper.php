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

// Load MediaHelper for enhanced media player
sfContext::getInstance()->getConfiguration()->loadHelpers(['Media']);

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

    if (is_object($digitalObject)) {
        $mimeType = $digitalObject->mimeType ?? '';
        $mediaTypeId = $digitalObject->mediaTypeId ?? null;
        $ext = strtolower(pathinfo($digitalObject->name ?? '', PATHINFO_EXTENSION));
        $doId = $digitalObject->id ?? 0;

        // Extension-based routing for special formats (BEFORE video/audio check)
        // PSD / CR2 / RAW camera formats -> converted image viewer
        if (in_array($ext, ['psd', 'cr2', 'nef', 'arw', 'dng'])) {
            return _render_converted_image_viewer($doId, $digitalObject, $ext);
        }

        // JPS (stereo JPEG) -> image viewer
        if ($ext === 'jps') {
            return _render_converted_image_viewer($doId, $digitalObject, 'jps');
        }

        // SVG -> native browser rendering
        if ($ext === 'svg') {
            return _render_svg_viewer($doId, $digitalObject);
        }

        // Office documents -> PDF viewer
        if (in_array($ext, ['docx', 'doc', 'xlsx', 'xls', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'])) {
            return _render_document_viewer($doId, $digitalObject, $ext);
        }

        // Plain text files -> text viewer
        if (in_array($ext, ['txt', 'csv', 'log', 'md', 'xml', 'json', 'yml', 'yaml', 'ini', 'cfg', 'conf'])) {
            return _render_text_viewer($doId, $digitalObject, $ext);
        }

        // Archives -> file listing viewer
        if (in_array($ext, ['zip', 'rar', 'tgz', 'gz', 'tar'])) {
            return _render_archive_viewer($doId, $digitalObject, $ext);
        }

        // SWF (Flash) -> legacy download only
        if ($ext === 'swf') {
            return _render_legacy_download($doId, $digitalObject, 'Adobe Flash (SWF)');
        }

        // 3D models -> model-viewer (GLB/GLTF) or Three.js (OBJ/STL)
        if (in_array($ext, ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae', 'usdz'])) {
            return _render_3d_viewer($doId, $digitalObject, $ext);
        }

        // Video/audio - use transcription-enabled player
        $isVideo = ($mediaTypeId == QubitTerm::VIDEO_ID) || strpos($mimeType, 'video') !== false;
        $isAudio = ($mediaTypeId == QubitTerm::AUDIO_ID) || strpos($mimeType, 'audio') !== false;

        // Also detect by extension for files with wrong/missing MIME types
        if (!$isVideo && !$isAudio) {
            $videoExts = ['mov', 'mp4', 'avi', 'mkv', 'webm', 'wmv', 'flv', 'm4v', 'mpeg', 'mpg', '3gp',
                'f4v', 'm2ts', 'mts', 'ts', 'wtv', 'vob', 'ogv', 'hevc', 'mxf', 'asf', 'rm'];
            $audioExts = ['wav', 'mp3', 'flac', 'ogg', 'oga', 'm4a', 'aac', 'wma', 'aiff', 'aif',
                'au', 'ac3', '8svx', 'amb'];
            if (in_array($ext, $videoExts)) {
                $isVideo = true;
            } elseif (in_array($ext, $audioExts)) {
                $isAudio = true;
            }
        }

        if ($isVideo || $isAudio) {
            // Use AhgMediaPlayer JS player when ahgIiifPlugin is enabled
            if (function_exists('render_media_player')) {
                return render_media_player([
                    'id' => $digitalObject->id,
                    'name' => $digitalObject->name,
                    'path' => $digitalObject->path,
                    'mimeType' => $digitalObject->mimeType,
                    'mediaTypeId' => $digitalObject->mediaTypeId ?? null,
                    'object_id' => $digitalObject->objectId ?? 0,
                ]);
            }
            // Fallback to native HTML5 player (no ahgIiifPlugin)
            $url = get_digital_object_url($digitalObject);
            if ($isAudio) {
                return '<audio controls class="w-100"><source src="' . htmlspecialchars($url) . '" type="' . htmlspecialchars($mimeType) . '">Your browser does not support audio.</audio>';
            }

            return '<video controls class="w-100" style="max-height:500px;"><source src="' . htmlspecialchars($url) . '" type="' . htmlspecialchars($mimeType) . '">Your browser does not support video.</video>';
        }
    }

    // For image formats not supported by Cantaloupe, render as simple <img>
    if (is_object($digitalObject)) {
        $imgExt = strtolower(pathinfo($digitalObject->name ?? '', PATHINFO_EXTENSION));
        if (in_array($imgExt, ['webp', 'bmp', 'gif'])) {
            $imgUrl = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');
            $html = '<div class="text-center">';
            $html .= '<img src="' . htmlspecialchars($imgUrl) . '" alt="" class="img-fluid" style="max-height:600px;">';
            $html .= '</div>';

            return $html;
        }
    }

    // Use render_iiif_viewer from IiifViewerHelper for images (JPG, PNG, TIFF, etc.)
    if (function_exists('render_iiif_viewer') && is_object($resource)) {
        return render_iiif_viewer($resource, $options);
    }

    // Fallback - simple rendering
    return '<div class="alert alert-warning">Viewer not available</div>';
}
endif;

// ========================================================================
// Format-specific viewer renderers
// ========================================================================

/**
 * Render converted image viewer (PSD, CR2, JPS, RAW formats)
 * Fetches converted JPEG from /media/convert/:id
 */
function _render_converted_image_viewer(int $doId, $digitalObject, string $ext): string
{
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
    $labels = ['psd' => 'Photoshop (PSD)', 'cr2' => 'Canon RAW (CR2)', 'nef' => 'Nikon RAW (NEF)',
        'arw' => 'Sony RAW (ARW)', 'dng' => 'Adobe DNG', 'jps' => 'Stereo JPEG (JPS)'];
    $label = $labels[$ext] ?? strtoupper($ext);
    $downloadUrl = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');

    $html = '<div class="converted-image-viewer card mb-3">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas fa-image me-2"></i>' . htmlspecialchars($label) . '</span>';
    $html .= '<a href="' . htmlspecialchars($downloadUrl) . '" download class="btn btn-sm btn-outline-secondary">';
    $html .= '<i class="fas fa-download me-1"></i>Download Original</a></div>';
    $html .= '<div class="card-body text-center p-0" id="convert-img-' . $doId . '">';
    $html .= '<div class="py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i>';
    $html .= '<p class="text-muted mt-2">Converting ' . htmlspecialchars($label) . '...</p></div>';
    $html .= '</div></div>';

    $html .= '<script ' . $nonceAttr . '>';
    $html .= '(function(){';
    $html .= 'var c=document.getElementById("convert-img-' . $doId . '");';
    $html .= 'var img=new Image();';
    $html .= 'img.onload=function(){c.innerHTML="";img.className="img-fluid";img.style.maxHeight="600px";c.appendChild(img);};';
    $html .= 'img.onerror=function(){c.innerHTML=\'<div class="py-4 text-muted"><i class="fas fa-exclamation-triangle fa-2x"></i><p class="mt-2">Conversion failed. <a href="' . htmlspecialchars($downloadUrl) . '" download>Download original</a></p></div>\';};';
    $html .= 'img.src="/media/convert/' . $doId . '";';
    $html .= '})();';
    $html .= '</script>';

    return $html;
}

/**
 * Render SVG viewer (native browser SVG rendering)
 */
function _render_svg_viewer(int $doId, $digitalObject): string
{
    $url = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');

    $html = '<div class="svg-viewer card mb-3">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas fa-bezier-curve me-2"></i>SVG Vector Image</span>';
    $html .= '<a href="' . htmlspecialchars($url) . '" download class="btn btn-sm btn-outline-secondary">';
    $html .= '<i class="fas fa-download me-1"></i>Download</a></div>';
    $html .= '<div class="card-body text-center p-2" style="background:#f8f9fa;">';
    $html .= '<img src="' . htmlspecialchars($url) . '" alt="SVG" class="img-fluid" style="max-height:600px;">';
    $html .= '</div></div>';

    return $html;
}

/**
 * Render document viewer (office docs converted to PDF via /media/convert/:id)
 */
function _render_document_viewer(int $doId, $digitalObject, string $ext): string
{
    $labels = ['docx' => 'Word Document', 'doc' => 'Word Document', 'xlsx' => 'Excel Spreadsheet',
        'xls' => 'Excel Spreadsheet', 'ppt' => 'PowerPoint', 'pptx' => 'PowerPoint',
        'odt' => 'OpenDocument Text', 'ods' => 'OpenDocument Spreadsheet',
        'odp' => 'OpenDocument Presentation', 'rtf' => 'Rich Text Format'];
    $icons = ['docx' => 'fa-file-word', 'doc' => 'fa-file-word', 'xlsx' => 'fa-file-excel',
        'xls' => 'fa-file-excel', 'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint',
        'odt' => 'fa-file-alt', 'ods' => 'fa-file-alt', 'odp' => 'fa-file-alt', 'rtf' => 'fa-file-alt'];
    $label = $labels[$ext] ?? strtoupper($ext);
    $icon = $icons[$ext] ?? 'fa-file';
    $downloadUrl = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');

    $html = '<div class="document-viewer card mb-3">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas ' . $icon . ' me-2"></i>' . htmlspecialchars($label) . '</span>';
    $html .= '<a href="' . htmlspecialchars($downloadUrl) . '" download class="btn btn-sm btn-outline-secondary">';
    $html .= '<i class="fas fa-download me-1"></i>Download Original</a></div>';
    $html .= '<div class="card-body p-0">';
    $html .= '<iframe src="/media/convert/' . $doId . '" style="width:100%;height:600px;border:none;"';
    $html .= ' title="' . htmlspecialchars($label) . '"></iframe>';
    $html .= '</div></div>';

    return $html;
}

/**
 * Render text file viewer (plain text, CSV, logs, etc.)
 */
function _render_text_viewer(int $doId, $digitalObject, string $ext): string
{
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
    $labels = ['txt' => 'Plain Text', 'csv' => 'CSV Data', 'log' => 'Log File',
        'md' => 'Markdown', 'xml' => 'XML', 'json' => 'JSON',
        'yml' => 'YAML', 'yaml' => 'YAML', 'ini' => 'Configuration', 'cfg' => 'Configuration', 'conf' => 'Configuration'];
    $label = $labels[$ext] ?? strtoupper($ext);
    $downloadUrl = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');

    $html = '<div class="text-viewer card mb-3">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas fa-file-alt me-2"></i>' . htmlspecialchars($label) . '</span>';
    $html .= '<a href="' . htmlspecialchars($downloadUrl) . '" download class="btn btn-sm btn-outline-secondary">';
    $html .= '<i class="fas fa-download me-1"></i>Download</a></div>';
    $html .= '<div class="card-body p-0">';
    $html .= '<pre id="text-content-' . $doId . '" class="p-3 m-0" style="max-height:500px;overflow:auto;background:#f8f9fa;font-size:0.85rem;white-space:pre-wrap;word-wrap:break-word;">';
    $html .= '<i class="fas fa-spinner fa-spin"></i> Loading...</pre>';
    $html .= '</div></div>';

    $html .= '<script ' . $nonceAttr . '>';
    $html .= 'fetch("/media/convert/' . $doId . '").then(function(r){return r.text()}).then(function(t){';
    $html .= 'var el=document.getElementById("text-content-' . $doId . '");';
    $html .= 'el.textContent=t.substring(0,500000);';
    $html .= '}).catch(function(){document.getElementById("text-content-' . $doId . '").textContent="Failed to load file content.";});';
    $html .= '</script>';

    return $html;
}

/**
 * Render archive viewer (ZIP, RAR, TGZ - shows file listing)
 */
function _render_archive_viewer(int $doId, $digitalObject, string $ext): string
{
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
    $labels = ['zip' => 'ZIP Archive', 'rar' => 'RAR Archive', 'tgz' => 'TGZ Archive',
        'gz' => 'GZip Archive', 'tar' => 'TAR Archive'];
    $label = $labels[$ext] ?? strtoupper($ext) . ' Archive';
    $downloadUrl = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');

    $html = '<div class="archive-viewer card mb-3">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas fa-file-archive me-2"></i>' . htmlspecialchars($label) . '</span>';
    $html .= '<a href="' . htmlspecialchars($downloadUrl) . '" download class="btn btn-sm btn-outline-primary">';
    $html .= '<i class="fas fa-download me-1"></i>Download Archive</a></div>';
    $html .= '<div class="card-body" id="archive-content-' . $doId . '">';
    $html .= '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i>';
    $html .= '<p class="text-muted mt-2">Reading archive contents...</p></div>';
    $html .= '</div></div>';

    $html .= '<script ' . $nonceAttr . '>';
    $html .= 'fetch("/media/convert/' . $doId . '").then(function(r){return r.json()}).then(function(d){';
    $html .= 'var c=document.getElementById("archive-content-' . $doId . '");';
    $html .= 'if(d.error){c.innerHTML=\'<div class="text-muted"><i class="fas fa-exclamation-triangle"></i> \'+d.error+\'</div>\';return;}';
    // ZIP format with structured data
    $html .= 'if(d.files&&Array.isArray(d.files)){';
    $html .= 'var h=\'<p class="text-muted mb-2"><i class="fas fa-folder-open me-1"></i>\'+d.count+\' files</p>\';';
    $html .= 'h+=\'<div class="table-responsive"><table class="table table-sm table-striped mb-0"><thead><tr><th>File</th><th class="text-end">Size</th></tr></thead><tbody>\';';
    $html .= 'var list=d.files.slice(0,200);';
    $html .= 'for(var i=0;i<list.length;i++){';
    $html .= 'var f=list[i];var sz=typeof f==="string"?"":(f.size>=1048576?(f.size/1048576).toFixed(1)+" MB":f.size>=1024?(f.size/1024).toFixed(0)+" KB":f.size+" B");';
    $html .= 'var nm=typeof f==="string"?f:f.name;';
    $html .= 'h+=\'<tr><td><i class="fas fa-file fa-sm text-muted me-1"></i>\'+nm+\'</td><td class="text-end text-muted">\'+sz+\'</td></tr>\';';
    $html .= '}';
    $html .= 'if(d.count>200)h+=\'<tr><td colspan="2" class="text-muted">... and \'+(d.count-200)+\' more files</td></tr>\';';
    $html .= 'h+=\'</tbody></table></div>\';c.innerHTML=h;';
    // RAR/other text listing
    $html .= '}else if(d.listing){c.innerHTML=\'<pre class="p-2 m-0" style="max-height:400px;overflow:auto;font-size:0.8rem;">\'+d.listing+\'</pre>\';';
    $html .= '}else{c.innerHTML=\'<div class="text-muted">No file listing available</div>\';}';
    $html .= '}).catch(function(){document.getElementById("archive-content-' . $doId . '").innerHTML=\'<div class="text-muted">Failed to read archive</div>\';});';
    $html .= '</script>';

    return $html;
}

/**
 * Render legacy format download (SWF, etc. - cannot be displayed)
 */
function _render_legacy_download(int $doId, $digitalObject, string $formatLabel): string
{
    $downloadUrl = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');

    $html = '<div class="legacy-download card mb-3">';
    $html .= '<div class="card-body text-center py-4">';
    $html .= '<i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>';
    $html .= '<h5>' . htmlspecialchars($formatLabel) . '</h5>';
    $html .= '<p class="text-muted">This format is no longer supported by modern browsers and cannot be displayed inline.</p>';
    $html .= '<a href="' . htmlspecialchars($downloadUrl) . '" download class="btn btn-primary">';
    $html .= '<i class="fas fa-download me-1"></i>Download Original File</a>';
    $html .= '</div></div>';

    return $html;
}

/**
 * Render 3D model viewer (GLB, GLTF, OBJ, STL, etc.)
 * Uses Google model-viewer for GLB/GLTF, Three.js for OBJ/STL
 */
function _render_3d_viewer(int $doId, $digitalObject, string $ext): string
{
    $n = sfConfig::get('csp_nonce', '');
    $nonceAttr = $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
    $fullPath = ($digitalObject->path ?? '') . ($digitalObject->name ?? '');
    $viewerId = 'viewer-3d-' . $doId;

    $html = '<div class="model3d-viewer">';
    $html .= '<div class="d-flex flex-column align-items-center">';
    $html .= '<div class="mb-2">';
    $html .= '<span class="badge bg-primary"><i class="fas fa-cube me-1"></i>' . htmlspecialchars($digitalObject->name ?? '') . ' (3D)</span>';
    $html .= '</div>';

    $html .= '<div id="' . $viewerId . '-container" style="width:100%;height:400px;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);border-radius:8px;position:relative;">';

    if (in_array($ext, ['glb', 'gltf'])) {
        // Google model-viewer for GLB/GLTF
        $html .= '<script type="module" src="/plugins/ahgCorePlugin/web/js/vendor/model-viewer.min.js"></script>';
        $html .= '<model-viewer id="' . $viewerId . '"';
        $html .= ' src="' . htmlspecialchars($fullPath) . '"';
        $html .= ' camera-controls touch-action="pan-y" auto-rotate shadow-intensity="1" exposure="1"';
        $html .= ' style="width:100%;height:100%;background:transparent;border-radius:8px;">';
        $html .= '<div slot="poster" class="d-flex flex-column align-items-center justify-content-center h-100 text-white">';
        $html .= '<div class="spinner-border text-primary mb-3" role="status"></div>';
        $html .= '<span>Loading 3D model...</span>';
        $html .= '</div>';
        $html .= '</model-viewer>';
    } elseif (in_array($ext, ['obj', 'stl'])) {
        // Three.js for OBJ/STL
        $html .= '<div id="' . $viewerId . '-threejs" style="width:100%;height:100%;border-radius:8px;"></div>';
        $html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/OBJLoader.js"></script>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/STLLoader.js"></script>';
        $html .= '<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>';
        $html .= '<script ' . $nonceAttr . '>';
        $html .= '(function(){';
        $html .= 'var c=document.getElementById("' . $viewerId . '-threejs");if(!c)return;';
        $html .= 'var s=new THREE.Scene();s.background=new THREE.Color(0x1a1a2e);';
        $html .= 'var cam=new THREE.PerspectiveCamera(45,c.clientWidth/c.clientHeight,0.1,1000);cam.position.set(0,1,3);';
        $html .= 'var r=new THREE.WebGLRenderer({antialias:true});r.setSize(c.clientWidth,c.clientHeight);r.setPixelRatio(window.devicePixelRatio);c.appendChild(r.domElement);';
        $html .= 'var ctrl=new THREE.OrbitControls(cam,r.domElement);ctrl.enableDamping=true;ctrl.autoRotate=true;';
        $html .= 's.add(new THREE.AmbientLight(0xffffff,0.6));var dl=new THREE.DirectionalLight(0xffffff,0.8);dl.position.set(5,10,7.5);s.add(dl);';
        $html .= 'function fit(o){var b=new THREE.Box3().setFromObject(o);var ct=b.getCenter(new THREE.Vector3());var sz=b.getSize(new THREE.Vector3());var sc=2/Math.max(sz.x,sz.y,sz.z);o.scale.setScalar(sc);o.position.sub(ct.multiplyScalar(sc));o.traverse(function(ch){if(ch.isMesh)ch.material=new THREE.MeshStandardMaterial({color:0xcccccc,roughness:0.5,metalness:0.3});});s.add(o);}';
        $html .= 'var ext="' . $ext . '";';
        $html .= 'if(ext==="obj")new THREE.OBJLoader().load("' . htmlspecialchars($fullPath) . '",fit);';
        $html .= 'else if(ext==="stl")new THREE.STLLoader().load("' . htmlspecialchars($fullPath) . '",function(g){fit(new THREE.Mesh(g));});';
        $html .= '(function anim(){requestAnimationFrame(anim);ctrl.update();r.render(s,cam);})();';
        $html .= 'window.addEventListener("resize",function(){cam.aspect=c.clientWidth/c.clientHeight;cam.updateProjectionMatrix();r.setSize(c.clientWidth,c.clientHeight);});';
        $html .= '})();';
        $html .= '</script>';
    } else {
        // FBX, PLY, DAE, USDZ - download with 3D badge
        $html .= '<div class="d-flex flex-column align-items-center justify-content-center h-100 text-white">';
        $html .= '<i class="fas fa-cube fa-4x mb-3 text-info"></i>';
        $html .= '<h6>' . strtoupper($ext) . ' 3D Model</h6>';
        $html .= '<a href="' . htmlspecialchars($fullPath) . '" download class="btn btn-primary mt-2">';
        $html .= '<i class="fas fa-download me-1"></i>Download 3D Model</a>';
        $html .= '</div>';
    }

    $html .= '</div>';

    $html .= '<small class="text-muted mt-2">';
    $html .= '<i class="fas fa-mouse me-1"></i>Drag to rotate | <i class="fas fa-search-plus me-1"></i>Scroll to zoom';
    $html .= '</small>';

    // Download link
    $html .= '<div class="mt-2">';
    $html .= '<a href="' . htmlspecialchars($fullPath) . '" download class="btn btn-sm btn-outline-secondary">';
    $html .= '<i class="fas fa-download me-1"></i>Download Original</a>';
    $html .= '</div>';

    $html .= '</div></div>';

    return $html;
}
