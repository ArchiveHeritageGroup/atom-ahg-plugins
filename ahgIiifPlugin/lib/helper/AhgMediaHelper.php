<?php

/**
 * Helper functions for media streaming.
 */

/**
 * Get the appropriate source URL for a digital object.
 * Returns streaming URL for legacy formats, direct path for native formats.
 */
function ahg_get_media_source($digitalObject, $representation = null)
{
    // Get mime type
    $mimeType = $digitalObject->mimeType;
    
    // Check if streaming is needed
    if (AhgMimeTypeExtension::needsStreaming($mimeType)) {
        return '/media/stream/' . $digitalObject->id;
    }
    
    // Return direct path for native formats
    if ($representation) {
        return public_path($representation->getFullPath());
    }
    
    return public_path($digitalObject->getFullPath());
}

/**
 * Get the appropriate mime type for the player.
 * Returns browser-compatible type for streaming.
 */
function ahg_get_player_mime_type($digitalObject)
{
    $mimeType = $digitalObject->mimeType;
    $mediaTypeId = $digitalObject->mediaTypeId;
    
    // If streaming, return the target format type
    if (AhgMimeTypeExtension::needsStreaming($mimeType)) {
        if ($mediaTypeId == QubitTerm::VIDEO_ID) {
            return 'video/mp4';
        } elseif ($mediaTypeId == QubitTerm::AUDIO_ID) {
            return 'audio/mpeg';
        }
    }
    
    return $mimeType;
}

/**
 * Check if digital object needs streaming proxy.
 */
function ahg_needs_streaming($digitalObject)
{
    return AhgMimeTypeExtension::needsStreaming($digitalObject->mimeType);
}

/**
 * Get format display name for UI.
 */
function ahg_get_format_name($mimeType)
{
    $formats = [
        'video/quicktime' => 'QuickTime (MOV)',
        'video/x-msvideo' => 'AVI',
        'video/x-ms-wmv' => 'Windows Media',
        'video/x-ms-wtv' => 'Windows TV Recording',
        'video/x-flv' => 'Flash Video',
        'video/mp2t' => 'MPEG Transport Stream',
        'video/hevc' => 'HEVC/H.265',
        'application/mxf' => 'MXF',
        'audio/x-aiff' => 'AIFF',
        'audio/basic' => 'AU/SND',
        'audio/ac3' => 'Dolby AC3',
        'audio/8svx' => 'Amiga 8SVX',
    ];
    
    return $formats[$mimeType] ?? $mimeType;
}
