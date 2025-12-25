<?php

/**
 * MediaHelper - Wrapper for backward compatibility
 * Delegates to AtomFramework\Helpers\MediaHelper
 */

require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Helpers/MediaHelper.php';

use AtomFramework\Helpers\MediaHelper as FrameworkMediaHelper;

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

function format_duration(float $seconds): string
{
    return FrameworkMediaHelper::formatDuration($seconds);
}
