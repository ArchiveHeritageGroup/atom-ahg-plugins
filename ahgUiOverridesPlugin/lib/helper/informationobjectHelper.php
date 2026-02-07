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

    // Use render_iiif_viewer from IiifViewerHelper for images
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
