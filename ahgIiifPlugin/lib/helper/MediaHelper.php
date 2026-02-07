<?php

/**
 * MediaHelper - Combined media player, metadata & transcription helper
 *
 * Provides:
 *   - Streaming/transcoding detection (delegates to framework)
 *   - AhgMediaPlayer JS-based player with built-in controls
 *   - Media metadata panel
 *   - Transcription panel (speech-to-text) with search & timed segments
 *   - Snippets list
 *
 * Usage:
 *   use_helper('Media');
 *   echo render_media_player($digitalObjectData);
 *   echo render_media_metadata($digitalObject);
 *   echo render_transcription_panel($digitalObject);
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */

require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Helpers/MediaHelper.php';

use AtomFramework\Helpers\MediaHelper as FrameworkMediaHelper;

// ========================================================================
// Streaming / Transcoding (framework delegation)
// ========================================================================

function get_streaming_mime_types(): array
{
    return FrameworkMediaHelper::getStreamingMimeTypes();
}

function get_streaming_extensions(): array
{
    return FrameworkMediaHelper::getStreamingExtensions();
}

function needs_streaming(string $mimeType): bool
{
    return FrameworkMediaHelper::needsStreaming($mimeType);
}

function extension_needs_streaming(string $extension): bool
{
    return FrameworkMediaHelper::extensionNeedsStreaming($extension);
}

function get_output_mime_type(string $inputMimeType): string
{
    return FrameworkMediaHelper::getOutputMimeType($inputMimeType);
}

function build_streaming_url(int $digitalObjectId, string $baseUrl = ''): string
{
    return FrameworkMediaHelper::buildStreamingUrl($digitalObjectId, $baseUrl);
}

function is_ffmpeg_available(): bool
{
    return FrameworkMediaHelper::isFFmpegAvailable();
}

function get_media_duration(string $filePath): ?float
{
    return FrameworkMediaHelper::getMediaDuration($filePath);
}

// ========================================================================
// CSP Nonce helper
// ========================================================================

function _media_nonce_attr(): string
{
    $n = sfConfig::get('csp_nonce', '');

    return $n ? preg_replace('/^nonce=/', 'nonce="', $n) . '"' : '';
}

// ========================================================================
// Full-featured JS media player (AhgMediaPlayer)
// ========================================================================

/**
 * Render full-featured media player with JS controls
 *
 * Uses AhgMediaPlayer class for streaming fallback, speed controls,
 * waveform, transcription integration, and snippets.
 *
 * @param array|object $digitalObject  Digital object data (array or QubitDigitalObject)
 * @param array        $options        Keys: height, theme, allow_snippets
 */
function render_media_player($digitalObject, array $options = []): string
{
    if (!$digitalObject || !is_media_file($digitalObject)) {
        return '';
    }

    $id = is_object($digitalObject) ? $digitalObject->id : ($digitalObject['id'] ?? 0);
    $objectId = is_object($digitalObject)
        ? ($digitalObject->object_id ?? $digitalObject->objectId ?? 0)
        : ($digitalObject['object_id'] ?? 0);
    $name = is_object($digitalObject) ? $digitalObject->name : ($digitalObject['name'] ?? '');
    $path = is_object($digitalObject) ? $digitalObject->path : ($digitalObject['path'] ?? '');
    $mimeType = is_object($digitalObject)
        ? ($digitalObject->mimeType ?? '')
        : ($digitalObject['mimeType'] ?? '');
    $mediaTypeId = is_object($digitalObject)
        ? ($digitalObject->mediaTypeId ?? null)
        : ($digitalObject['mediaTypeId'] ?? null);

    // Detect audio from mimeType / mediaTypeId / extension
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $audioExts = ['wav', 'mp3', 'flac', 'ogg', 'oga', 'm4a', 'aac', 'wma', 'aiff', 'aif'];
    $isAudio = ($mediaTypeId == QubitTerm::AUDIO_ID)
        || strpos($mimeType, 'audio') !== false
        || in_array($ext, $audioExts);
    $isVideo = !$isAudio;

    // Build media URL
    $mediaUrl = $path . $name;

    // Streaming URL for formats needing transcoding
    $streamUrl = '';
    if (needs_streaming($mimeType)) {
        $streamUrl = build_streaming_url((int) $id);
    }

    // Get derivatives (waveform, poster)
    $derivatives = get_media_derivatives($id);
    $waveformUrl = $derivatives['waveform']['path'] ?? null;
    $posterUrl = !empty($derivatives['posters'])
        ? ($derivatives['posters'][0]['url'] ?? $derivatives['posters'][0]['path'] ?? null)
        : null;

    // Get transcription info
    $transcription = get_transcription($id);
    $transcriptUrl = $transcription ? '/media/transcription/' . $id : null;

    $containerId = 'media-player-' . $id;
    $height = $options['height'] ?? ($isVideo ? '400px' : '200px');
    $theme = $options['theme'] ?? 'dark';
    $allowSnippets = $options['allow_snippets'] ?? true;

    $nonceAttr = _media_nonce_attr();

    // Player container
    $html = '<div id="' . htmlspecialchars($containerId) . '" class="ahg-media-player" '
        . 'data-do-id="' . (int) $id . '" style="height:' . htmlspecialchars($height) . ';"></div>';

    // Include player JS (locally hosted)
    $html .= '<script ' . $nonceAttr . ' src="/plugins/ahgIiifPlugin/js/atom-media-player.js"></script>';

    // Initialize player
    $html .= '<script ' . $nonceAttr . '>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '  new AhgMediaPlayer("#' . $containerId . '", {';
    $html .= '    mediaUrl: "' . htmlspecialchars($mediaUrl) . '",';
    if ($streamUrl) {
        $html .= '    streamUrl: "' . htmlspecialchars($streamUrl) . '",';
    }
    $html .= '    mediaType: "' . ($isVideo ? 'video' : 'audio') . '",';
    $html .= '    mimeType: "' . htmlspecialchars($mimeType) . '",';
    $html .= '    digitalObjectId: ' . (int) $id . ',';
    $html .= '    objectId: ' . (int) $objectId . ',';

    if ($waveformUrl) {
        $html .= '    waveformUrl: "' . htmlspecialchars($waveformUrl) . '",';
    }
    if ($transcriptUrl) {
        $html .= '    transcriptUrl: "' . htmlspecialchars($transcriptUrl) . '",';
    }
    if ($posterUrl && $isVideo) {
        $html .= '    poster: "' . htmlspecialchars($posterUrl) . '",';
    }

    $html .= '    snippetsUrl: "/media/snippets",';
    $html .= '    theme: "' . htmlspecialchars($theme) . '",';
    $html .= '    allowSnippets: ' . ($allowSnippets ? 'true' : 'false') . ',';
    $html .= '    skipSeconds: 10';
    $html .= '  });';
    $html .= '});';
    $html .= '</script>';

    return $html;
}

/**
 * Render simple HTML5 player (fallback, no JS)
 */
function render_enhanced_media_player(array $digitalObjectData, array $options = []): string
{
    $mimeType = $digitalObjectData['mimeType'] ?? '';
    $doId = $digitalObjectData['id'] ?? 0;
    $mediaTypeId = $digitalObjectData['mediaTypeId'] ?? null;
    $path = ($digitalObjectData['path'] ?? '') . ($digitalObjectData['name'] ?? '');

    // Auto-detect audio
    $isAudio = ($mediaTypeId == QubitTerm::AUDIO_ID)
        || strpos($mimeType, 'audio') !== false;

    if (needs_streaming($mimeType)) {
        $mediaUrl = build_streaming_url((int) $doId);
        $outputMime = get_output_mime_type($mimeType);
    } else {
        $mediaUrl = $path;
        $outputMime = $mimeType;
    }

    $playerId = 'player-' . $doId;

    if ($isAudio) {
        $html = '<audio id="' . htmlspecialchars($playerId) . '" controls class="w-100">';
        $html .= '<source src="' . htmlspecialchars($mediaUrl) . '" type="' . htmlspecialchars($outputMime) . '">';
        $html .= 'Your browser does not support audio playback.</audio>';
    } else {
        $html = '<video id="' . htmlspecialchars($playerId) . '" controls class="w-100" style="max-height:500px;">';
        try {
            $transcription = \Illuminate\Database\Capsule\Manager::table('media_transcription')
                ->where('digital_object_id', $doId)
                ->first();
            if ($transcription) {
                $html .= '<track kind="subtitles" src="/media/transcription/' . (int) $doId . '/vtt" '
                    . 'srclang="' . htmlspecialchars($transcription->language ?? 'en') . '" label="Subtitles" default>';
            }
        } catch (\Exception $e) {
            // media_transcription table may not exist
        }
        $html .= '<source src="' . htmlspecialchars($mediaUrl) . '" type="' . htmlspecialchars($outputMime) . '">';
        $html .= 'Your browser does not support video playback.</video>';
    }

    return $html;
}

// ========================================================================
// Media metadata panel
// ========================================================================

/**
 * Render media metadata panel (codec, bitrate, resolution, etc.)
 */
function render_media_metadata($digitalObject, array $options = []): string
{
    if (!$digitalObject || !is_media_file($digitalObject)) {
        return '';
    }

    $id = is_object($digitalObject) ? $digitalObject->id : ($digitalObject['id'] ?? 0);
    $metadata = get_media_metadata($id);

    if (!$metadata) {
        return render_extraction_button($id);
    }

    $html = '<div class="media-metadata-panel card mb-3">';
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas fa-info-circle me-2"></i>Media Information</span>';
    $html .= '<span class="badge bg-' . ($metadata->media_type === 'audio' ? 'info' : 'primary') . '">';
    $html .= ucfirst($metadata->media_type) . ' - ' . strtoupper($metadata->format);
    $html .= '</span></div>';

    $html .= '<div class="card-body"><div class="row">';

    // Technical details
    $html .= '<div class="col-md-6">';
    $html .= '<h6 class="text-muted mb-2">Technical Details</h6>';
    $html .= '<table class="table table-sm table-borderless">';
    if ($metadata->duration) {
        $html .= '<tr><td class="text-muted">Duration:</td><td>' . format_duration($metadata->duration) . '</td></tr>';
    }
    $html .= '<tr><td class="text-muted">File Size:</td><td>' . format_file_size($metadata->file_size) . '</td></tr>';
    if ($metadata->bitrate) {
        $html .= '<tr><td class="text-muted">Bitrate:</td><td>' . format_bitrate($metadata->bitrate) . '</td></tr>';
    }
    if ($metadata->audio_codec) {
        $html .= '<tr><td class="text-muted">Audio Codec:</td><td>' . $metadata->audio_codec . '</td></tr>';
        $html .= '<tr><td class="text-muted">Sample Rate:</td><td>' . number_format($metadata->audio_sample_rate) . ' Hz</td></tr>';
        $html .= '<tr><td class="text-muted">Channels:</td><td>' . format_channels($metadata->audio_channels) . '</td></tr>';
        if ($metadata->audio_bits_per_sample) {
            $html .= '<tr><td class="text-muted">Bit Depth:</td><td>' . $metadata->audio_bits_per_sample . '-bit</td></tr>';
        }
    }
    if ($metadata->video_codec) {
        $html .= '<tr><td class="text-muted">Video Codec:</td><td>' . $metadata->video_codec . '</td></tr>';
        $html .= '<tr><td class="text-muted">Resolution:</td><td>' . $metadata->video_width . ' x ' . $metadata->video_height . '</td></tr>';
        $html .= '<tr><td class="text-muted">Frame Rate:</td><td>' . round($metadata->video_frame_rate, 2) . ' fps</td></tr>';
    }
    $html .= '</table></div>';

    // Embedded metadata
    $html .= '<div class="col-md-6">';
    $html .= '<h6 class="text-muted mb-2">Embedded Metadata</h6>';
    $html .= '<table class="table table-sm table-borderless">';
    foreach (['title', 'artist', 'album', 'genre', 'year', 'copyright'] as $field) {
        if (!empty($metadata->$field)) {
            $html .= '<tr><td class="text-muted">' . ucfirst($field) . ':</td><td>' . htmlspecialchars($metadata->$field) . '</td></tr>';
        }
    }
    if (!empty($metadata->make)) {
        $html .= '<tr><td class="text-muted">Device:</td><td>' . htmlspecialchars($metadata->make . ' ' . ($metadata->model ?? '')) . '</td></tr>';
    }
    $html .= '</table></div>';

    $html .= '</div>'; // row

    // Waveform
    if (!empty($metadata->waveform_path) && $metadata->media_type === 'audio') {
        $html .= '<div class="waveform-container mt-3">';
        $html .= '<h6 class="text-muted mb-2">Waveform</h6>';
        $html .= '<img src="' . htmlspecialchars($metadata->waveform_path) . '" alt="Audio waveform" class="img-fluid rounded" style="background:#1a1a1a;width:100%;">';
        $html .= '</div>';
    }

    $html .= '</div></div>'; // card-body, card

    return $html;
}

// ========================================================================
// Transcription panel (speech-to-text)
// ========================================================================

/**
 * Render transcription panel with search, timed segments, download buttons
 */
function render_transcription_panel($digitalObject, array $options = []): string
{
    if (!$digitalObject || !is_media_file($digitalObject)) {
        return '';
    }

    $id = is_object($digitalObject) ? $digitalObject->id : ($digitalObject['id'] ?? 0);
    $transcription = get_transcription($id);

    if (!$transcription) {
        return render_transcribe_button($id);
    }

    $data = json_decode($transcription->transcription_data ?? '{}', true);
    $segments = $data['segments'] ?? json_decode($transcription->segments ?? '[]', true);

    $html = '<div class="transcription-panel card mb-3" id="transcription-panel-' . $id . '">';

    // Header with download buttons
    $html .= '<div class="card-header d-flex justify-content-between align-items-center">';
    $html .= '<span><i class="fas fa-file-alt me-2"></i>Transcription</span>';
    $html .= '<div class="btn-group btn-group-sm">';
    $html .= '<a href="/media/transcription/' . $id . '/vtt" class="btn btn-outline-secondary" title="Download VTT"><i class="fas fa-closed-captioning"></i> VTT</a>';
    $html .= '<a href="/media/transcription/' . $id . '/srt" class="btn btn-outline-secondary" title="Download SRT"><i class="fas fa-file-video"></i> SRT</a>';
    if (!empty($transcription->txt_path)) {
        $html .= '<a href="' . htmlspecialchars($transcription->txt_path) . '" class="btn btn-outline-secondary" title="Download Text"><i class="fas fa-file-alt"></i> TXT</a>';
    }
    $html .= '</div></div>';

    // Info bar
    $html .= '<div class="card-body py-2 bg-light border-bottom"><small class="text-muted">';
    $html .= '<i class="fas fa-language me-1"></i>' . get_language_name($transcription->language ?? 'en');
    if (!empty($transcription->duration)) {
        $html .= ' &bull; <i class="fas fa-clock me-1"></i>' . format_duration($transcription->duration);
    }
    if (!empty($transcription->segment_count)) {
        $html .= ' &bull; <i class="fas fa-paragraph me-1"></i>' . $transcription->segment_count . ' segments';
    }
    if (!empty($transcription->confidence)) {
        $cls = $transcription->confidence > 70 ? 'success' : ($transcription->confidence > 50 ? 'warning' : 'danger');
        $html .= ' &bull; <span class="badge bg-' . $cls . '">' . round($transcription->confidence) . '% confidence</span>';
    }
    $html .= '</small></div>';

    // Search box
    $html .= '<div class="card-body py-2 border-bottom">';
    $html .= '<div class="input-group input-group-sm">';
    $html .= '<input type="text" class="form-control" id="transcript-search-' . $id . '" placeholder="Search in transcript...">';
    $html .= '<button class="btn btn-outline-secondary" type="button" id="transcript-search-btn-' . $id . '"><i class="fas fa-search"></i></button>';
    $html .= '</div></div>';

    // Content
    $html .= '<div class="card-body transcript-content" style="max-height:400px;overflow-y:auto;">';
    if (empty($options['segments_only'])) {
        $html .= '<div class="transcript-full-text" style="white-space:pre-wrap;line-height:1.8;">';
        $html .= htmlspecialchars($transcription->full_text ?? '');
        $html .= '</div>';
    }
    $html .= '<div class="transcript-segments" style="display:none;">';
    foreach ($segments as $index => $segment) {
        $html .= '<div class="transcript-segment" data-start="' . ($segment['start'] ?? 0) . '" '
            . 'data-end="' . ($segment['end'] ?? 0) . '" data-index="' . $index . '" '
            . 'style="cursor:pointer;padding:4px 8px;border-radius:4px;margin:2px 0;">';
        $html .= '<small class="text-muted me-2">[' . format_duration($segment['start'] ?? 0) . ']</small>';
        $html .= htmlspecialchars(trim($segment['text'] ?? ''));
        $html .= '</div>';
    }
    $html .= '</div></div>';

    // View toggle footer
    $html .= '<div class="card-footer py-2"><div class="btn-group btn-group-sm">';
    $html .= '<button class="btn btn-outline-secondary active" data-view="text" id="btn-text-' . $id . '"><i class="fas fa-align-left"></i> Full Text</button>';
    $html .= '<button class="btn btn-outline-secondary" data-view="segments" id="btn-segments-' . $id . '"><i class="fas fa-list"></i> Timed Segments</button>';
    $html .= '</div></div>';

    $html .= '</div>'; // card

    // Interactivity JS
    $html .= _render_transcription_js($id);

    return $html;
}

function render_extraction_button(int $digitalObjectId): string
{
    $nonceAttr = _media_nonce_attr();

    $html = '<div class="media-extraction-prompt card mb-3">';
    $html .= '<div class="card-body text-center py-4">';
    $html .= '<i class="fas fa-music fa-2x text-muted mb-3 d-block"></i>';
    $html .= '<p class="text-muted mb-3">Media metadata has not been extracted yet.</p>';
    $html .= '<button class="btn btn-primary" id="extract-btn-' . $digitalObjectId . '">';
    $html .= '<i class="fas fa-magic me-1"></i>Extract Metadata</button>';
    $html .= '</div></div>';

    $html .= '<script ' . $nonceAttr . '>';
    $html .= 'document.getElementById("extract-btn-' . $digitalObjectId . '").onclick = function() {';
    $html .= '  var btn = this; btn.disabled = true; btn.innerHTML = \'<i class="fas fa-spinner fa-spin"></i> Extracting...\';';
    $html .= '  fetch("/media/extract/' . $digitalObjectId . '", {method:"POST"}).then(function(r){return r.json()}).then(function(d){if(d.success)location.reload();else{alert("Error: "+(d.error||"Failed"));btn.disabled=false;btn.innerHTML=\'<i class="fas fa-magic me-1"></i>Extract Metadata\';}}).catch(function(){btn.disabled=false;btn.innerHTML=\'<i class="fas fa-magic me-1"></i>Extract Metadata\';});';
    $html .= '};';
    $html .= '</script>';

    return $html;
}

function render_transcribe_button(int $digitalObjectId): string
{
    $nonceAttr = _media_nonce_attr();

    $html = '<div class="transcription-prompt card mb-3">';
    $html .= '<div class="card-body text-center py-4">';
    $html .= '<i class="fas fa-microphone fa-2x text-muted mb-3 d-block"></i>';
    $html .= '<p class="text-muted mb-3">This audio/video has not been transcribed yet.</p>';
    $html .= '<div class="d-flex justify-content-center gap-2 flex-wrap">';
    $html .= '<button class="btn btn-primary" id="transcribe-en-' . $digitalObjectId . '"><i class="fas fa-language me-1"></i>Transcribe (English)</button>';
    $html .= '<button class="btn btn-outline-primary" id="transcribe-af-' . $digitalObjectId . '">Afrikaans</button>';
    $html .= '</div></div></div>';

    $html .= '<script ' . $nonceAttr . '>';
    $html .= 'function _transcribe(id,lang){var btn=document.getElementById("transcribe-"+lang+"-"+id);btn.disabled=true;btn.innerHTML=\'<i class="fas fa-spinner fa-spin"></i> Transcribing...\';fetch("/media/transcribe/"+id+"?lang="+lang,{method:"POST"}).then(function(r){return r.json()}).then(function(d){if(d.success)location.reload();else{alert("Error: "+(d.error||"Failed"));btn.disabled=false;}}).catch(function(){btn.disabled=false;});}';
    $html .= 'document.getElementById("transcribe-en-' . $digitalObjectId . '").onclick=function(){_transcribe(' . $digitalObjectId . ',"en")};';
    $html .= 'document.getElementById("transcribe-af-' . $digitalObjectId . '").onclick=function(){_transcribe(' . $digitalObjectId . ',"af")};';
    $html .= '</script>';

    return $html;
}

function _render_transcription_js(int $id): string
{
    $nonceAttr = _media_nonce_attr();

    return '<script ' . $nonceAttr . '>
(function() {
    var doId = ' . $id . ';
    var panel = document.getElementById("transcription-panel-" + doId);
    if (!panel) return;

    var btnText = panel.querySelector("#btn-text-" + doId);
    var btnSegments = panel.querySelector("#btn-segments-" + doId);
    var fullText = panel.querySelector(".transcript-full-text");
    var segments = panel.querySelector(".transcript-segments");

    if (btnText && btnSegments) {
        btnText.onclick = function() {
            fullText.style.display = "block"; segments.style.display = "none";
            btnText.classList.add("active"); btnSegments.classList.remove("active");
        };
        btnSegments.onclick = function() {
            fullText.style.display = "none"; segments.style.display = "block";
            btnText.classList.remove("active"); btnSegments.classList.add("active");
        };
    }

    panel.querySelectorAll(".transcript-segment").forEach(function(seg) {
        seg.onmouseover = function() { this.style.background = "#f0f0f0"; };
        seg.onmouseout = function() { this.style.background = "transparent"; };
        seg.onclick = function() {
            var start = parseFloat(seg.dataset.start);
            var player = document.querySelector("audio, video");
            if (player) { player.currentTime = start; player.play(); }
            panel.querySelectorAll(".transcript-segment").forEach(function(s) { s.style.background = "transparent"; });
            seg.style.background = "#fff3cd";
        };
    });

    var searchInput = panel.querySelector("#transcript-search-" + doId);
    var searchBtn = panel.querySelector("#transcript-search-btn-" + doId);
    function doSearch() {
        var query = searchInput.value.toLowerCase().trim();
        if (!query) return;
        panel.querySelectorAll(".transcript-segment").forEach(function(seg) {
            var text = seg.textContent.toLowerCase();
            if (text.indexOf(query) >= 0) { seg.style.background = "#d4edda"; seg.style.display = "block"; }
            else { seg.style.display = "none"; }
        });
        if (btnSegments) btnSegments.click();
    }
    if (searchBtn) searchBtn.onclick = doSearch;
    if (searchInput) searchInput.onkeypress = function(e) { if (e.key === "Enter") doSearch(); };
})();
</script>';
}

// ========================================================================
// Snippets
// ========================================================================

function render_snippets_list($digitalObject, array $options = []): string
{
    if (!$digitalObject) {
        return '';
    }

    $id = is_object($digitalObject) ? $digitalObject->id : ($digitalObject['id'] ?? 0);
    $snippets = get_snippets($id);

    if (empty($snippets)) {
        return '<div class="text-muted small">No saved snippets</div>';
    }

    $html = '<div class="snippets-list">';
    foreach ($snippets as $snippet) {
        $html .= '<div class="snippet-item card mb-2"><div class="card-body py-2">';
        $html .= '<div class="d-flex justify-content-between align-items-center"><div>';
        $html .= '<strong>' . htmlspecialchars($snippet->title) . '</strong>';
        $html .= '<div class="small text-muted">';
        $html .= format_duration($snippet->start_time) . ' &rarr; ' . format_duration($snippet->end_time);
        if (!empty($snippet->duration)) {
            $html .= ' (' . format_duration($snippet->duration) . ')';
        }
        $html .= '</div></div>';
        $html .= '<div class="btn-group btn-group-sm">';
        $html .= '<button class="btn btn-outline-primary" onclick="playSnippet(' . $snippet->id . ',' . $snippet->start_time . ',' . $snippet->end_time . ')"><i class="fas fa-play"></i></button>';
        if (!empty($snippet->export_path)) {
            $html .= '<a class="btn btn-outline-secondary" href="' . htmlspecialchars($snippet->export_path) . '" download><i class="fas fa-download"></i></a>';
        }
        $html .= '</div></div></div></div>';
    }
    $html .= '</div>';

    return $html;
}

// ========================================================================
// Data access helpers
// ========================================================================

function is_media_file($digitalObject): bool
{
    if (!$digitalObject) {
        return false;
    }

    $name = is_object($digitalObject) ? $digitalObject->name : ($digitalObject['name'] ?? '');
    $mimeType = is_object($digitalObject)
        ? ($digitalObject->mimeType ?? '')
        : ($digitalObject['mimeType'] ?? '');

    // Check mime type first
    if (strpos($mimeType, 'audio') !== false || strpos($mimeType, 'video') !== false) {
        return true;
    }

    // Fallback to extension
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $audioFormats = ['wav', 'mp3', 'flac', 'ogg', 'oga', 'm4a', 'aac', 'wma', 'aiff', 'aif'];
    $videoFormats = ['mov', 'mp4', 'avi', 'mkv', 'webm', 'wmv', 'flv', 'm4v', 'mpeg', 'mpg', '3gp'];

    return in_array($ext, $audioFormats) || in_array($ext, $videoFormats);
}

function get_media_metadata(int $digitalObjectId): ?object
{
    try {
        return \Illuminate\Database\Capsule\Manager::table('media_metadata')
            ->where('digital_object_id', $digitalObjectId)
            ->first() ?: null;
    } catch (\Exception $e) {
        return null;
    }
}

function get_transcription(int $digitalObjectId): ?object
{
    try {
        return \Illuminate\Database\Capsule\Manager::table('media_transcription')
            ->where('digital_object_id', $digitalObjectId)
            ->first() ?: null;
    } catch (\Exception $e) {
        return null;
    }
}

function get_media_derivatives(int $digitalObjectId): array
{
    try {
        $rows = \Illuminate\Database\Capsule\Manager::table('media_derivatives')
            ->where('digital_object_id', $digitalObjectId)
            ->orderBy('derivative_type')
            ->orderBy('derivative_index')
            ->select('derivative_type', 'derivative_index', 'path', 'metadata')
            ->get();

        $derivatives = [];
        foreach ($rows as $row) {
            $type = $row->derivative_type;
            $item = ['path' => $row->path];
            if ($row->metadata) {
                $item = array_merge($item, json_decode($row->metadata, true) ?: []);
            }
            $derivatives[$type][] = $item;
        }

        // Flatten single items (except posters which are always arrays)
        foreach ($derivatives as $type => $items) {
            if (count($items) === 1 && $type !== 'posters') {
                $derivatives[$type] = $items[0];
            }
        }

        return $derivatives;
    } catch (\Exception $e) {
        return [];
    }
}

function get_snippets(int $digitalObjectId): array
{
    try {
        return \Illuminate\Database\Capsule\Manager::table('media_snippets')
            ->where('digital_object_id', $digitalObjectId)
            ->orderBy('start_time')
            ->get()
            ->all();
    } catch (\Exception $e) {
        return [];
    }
}

// ========================================================================
// Formatting helpers
// ========================================================================

function format_duration(float $seconds): string
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = floor($seconds % 60);

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }

    return sprintf('%d:%02d', $minutes, $secs);
}

function format_file_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

function format_bitrate(int $bitrate): string
{
    if ($bitrate >= 1000000) {
        return round($bitrate / 1000000, 2) . ' Mbps';
    }

    return round($bitrate / 1000) . ' kbps';
}

function format_channels(int $channels): string
{
    $names = [1 => 'Mono', 2 => 'Stereo', 6 => '5.1 Surround', 8 => '7.1 Surround'];

    return $names[$channels] ?? $channels . ' channels';
}

function get_language_name(string $code): string
{
    $languages = [
        'en' => 'English', 'af' => 'Afrikaans', 'nl' => 'Dutch', 'de' => 'German',
        'fr' => 'French', 'es' => 'Spanish', 'pt' => 'Portuguese', 'it' => 'Italian',
        'pl' => 'Polish', 'ru' => 'Russian', 'zh' => 'Chinese', 'ja' => 'Japanese',
        'ko' => 'Korean', 'ar' => 'Arabic', 'hi' => 'Hindi', 'zu' => 'Zulu',
        'xh' => 'Xhosa', 'st' => 'Sesotho',
    ];

    return $languages[$code] ?? $code;
}
