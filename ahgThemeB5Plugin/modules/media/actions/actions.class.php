<?php
/**
 * Media streaming actions - Handles on-the-fly transcoding of legacy formats
 *
 * Supports transcoding of:
 * - Video: ASF, AVI, MOV, WMV, FLV, MKV, TS, WTV, HEVC, 3GP, VOB, MXF
 * - Audio: AIFF, AU, AC3, 8SVX, WMA, RA, FLAC
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class mediaActions extends sfActions
{
    /**
     * Stream media file, transcoding if necessary for browser compatibility
     *
     * @param sfWebRequest $request
     */
    public function executeStream(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404('Digital object not found');
        }

        // Get digital object
        $digitalObject = QubitDigitalObject::getById($id);
        if (!$digitalObject) {
            $this->forward404('Digital object not found');
        }

        // Get the file path
        $filePath = $this->getFilePath($digitalObject);
        if (!$filePath || !file_exists($filePath)) {
            $this->forward404('Media file not found: ' . $filePath);
        }

        $mimeType = $digitalObject->mimeType;
        $mediaTypeId = $digitalObject->mediaTypeId;
        $isVideo = ($mediaTypeId == QubitTerm::VIDEO_ID);

        // Check if transcoding is needed
        if ($this->needsTranscoding($mimeType, $digitalObject->name)) {
            $this->streamTranscoded($filePath, $isVideo);
        } else {
            // Stream directly with proper range support
            $this->streamDirect($filePath, $mimeType);
        }

        return sfView::NONE;
    }

    /**
     * Download original media file
     *
     * @param sfWebRequest $request
     */
    public function executeDownload(sfWebRequest $request)
    {
        $id = (int) $request->getParameter('id');

        if (!$id) {
            $this->forward404('Digital object not found');
        }

        $digitalObject = QubitDigitalObject::getById($id);
        if (!$digitalObject) {
            $this->forward404('Digital object not found');
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!$filePath || !file_exists($filePath)) {
            $this->forward404('Media file not found');
        }

        // Force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($digitalObject->name) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');

        readfile($filePath);

        return sfView::NONE;
    }

    /**
     * Get the full file path for a digital object
     */
    protected function getFilePath($digitalObject): ?string
    {
        $path = $digitalObject->path;
        $name = $digitalObject->name;

        if (!$path || !$name) {
            return null;
        }

        // The path in database may already contain /uploads/ prefix
        $webDir = sfConfig::get('sf_web_dir');

        // Check if path already starts with /uploads/
        if (str_starts_with($path, '/uploads/')) {
            $fullPath = $webDir . $path . '/' . $name;
        } else {
            $fullPath = $webDir . '/uploads/' . trim($path, '/') . '/' . $name;
        }

        // Normalize path
        $fullPath = realpath($fullPath);

        // Security: ensure path is within web directory
        if (!$fullPath || !str_starts_with($fullPath, realpath($webDir))) {
            return null;
        }

        return $fullPath;
    }

    /**
     * Check if the format needs transcoding
     */
    protected function needsTranscoding(string $mimeType, string $fileName = null): bool
    {
        // MIME types that need transcoding
        $transcodingMimes = [
            // Video
            'video/x-ms-asf',       // ASF
            'video/x-msvideo',      // AVI
            'video/quicktime',      // MOV
            'video/x-ms-wmv',       // WMV
            'video/x-flv',          // FLV
            'video/x-matroska',     // MKV
            'video/mp2t',           // TS
            'video/x-ms-wtv',       // WTV
            'video/hevc',           // HEVC
            'video/3gpp',           // 3GP
            'video/3gpp2',          // 3G2
            'application/vnd.rn-realmedia', // RM
            'video/x-ms-vob',       // VOB
            'application/mxf',      // MXF
            // Audio
            'audio/aiff',           // AIFF
            'audio/x-aiff',         // AIFF (alt)
            'audio/basic',          // AU/SND
            'audio/x-au',           // AU (alt)
            'audio/ac3',            // AC3
            'audio/8svx',           // 8SVX
            'audio/AMB',            // AMB
            'audio/x-ms-wma',       // WMA
            'audio/x-pn-realaudio', // RA
        ];

        if (in_array($mimeType, $transcodingMimes)) {
            return true;
        }

        // Also check by extension
        if ($fileName) {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $transcodingExtensions = [
                // Video
                'asf', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'ts', 'm2ts', 'mts',
                'wtv', 'hevc', '3gp', '3g2', 'rm', 'rmvb', 'vob', 'mxf', 'divx',
                // Audio
                'aiff', 'aif', 'aifc', 'au', 'snd', 'ac3', '8svx', 'amb',
                'wma', 'ra', 'ram',
            ];

            if (in_array($ext, $transcodingExtensions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stream transcoded content using FFmpeg
     */
    protected function streamTranscoded(string $filePath, bool $isVideo): void
    {
        // Determine output format
        $contentType = $isVideo ? 'video/mp4' : 'audio/mpeg';

        // Set headers for streaming
        header('Content-Type: ' . $contentType);
        header('Accept-Ranges: none');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Accel-Buffering: no');

        // Disable output buffering
        while (ob_get_level()) {
            ob_end_flush();
        }

        // Build FFmpeg command
        $escapedPath = escapeshellarg($filePath);

        if ($isVideo) {
            // Video transcoding to MP4 with H.264
            // Using fragmented MP4 for streaming without seeking
            $cmd = "ffmpeg -y -i {$escapedPath} "
                 . '-c:v libx264 -preset ultrafast -tune zerolatency -crf 23 '
                 . '-c:a aac -b:a 128k '
                 . '-movflags frag_keyframe+empty_moov+faststart '
                 . '-f mp4 '
                 . 'pipe:1 2>/dev/null';
        } else {
            // Audio transcoding to MP3
            $cmd = "ffmpeg -y -i {$escapedPath} "
                 . '-c:a libmp3lame -b:a 192k '
                 . '-f mp3 '
                 . 'pipe:1 2>/dev/null';
        }

        // Execute FFmpeg and stream output
        $process = popen($cmd, 'r');
        if ($process) {
            // Use a reasonable buffer size
            $bufferSize = 8192;

            while (!feof($process)) {
                $chunk = fread($process, $bufferSize);
                if ($chunk !== false && strlen($chunk) > 0) {
                    echo $chunk;
                    flush();
                }
            }

            pclose($process);
        } else {
            // FFmpeg failed - send error response
            http_response_code(500);
            header('Content-Type: text/plain');
            echo 'Transcoding failed - FFmpeg process could not start';
        }
    }

    /**
     * Stream file directly with range request support
     */
    protected function streamDirect(string $filePath, string $mimeType): void
    {
        $fileSize = filesize($filePath);

        // Handle range requests for seeking
        $start = 0;
        $end = $fileSize - 1;

        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
                $start = $matches[1] !== '' ? intval($matches[1]) : 0;
                $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;
            }

            // Partial content response
            http_response_code(206);
            header("Content-Range: bytes {$start}-{$end}/{$fileSize}");
        }

        $length = $end - $start + 1;

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $length);
        header('Accept-Ranges: bytes');
        header('Cache-Control: public, max-age=86400');

        // Stream the file
        $handle = fopen($filePath, 'rb');
        if ($handle) {
            fseek($handle, $start);

            $remaining = $length;
            $bufferSize = 8192;

            while ($remaining > 0 && !feof($handle)) {
                $readSize = min($bufferSize, $remaining);
                $chunk = fread($handle, $readSize);
                if ($chunk === false) {
                    break;
                }
                echo $chunk;
                flush();
                $remaining -= strlen($chunk);
            }

            fclose($handle);
        }
    }
}