<?php

use AtomFramework\Http\Controllers\AhgController;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Media streaming actions - Handles on-the-fly transcoding of legacy formats
 *
 * Supports transcoding of:
 * - Video: ASF, AVI, MOV, WMV, FLV, MKV, TS, WTV, HEVC, 3GP, VOB, MXF
 * - Audio: AIFF, AU, AC3, 8SVX, WMA, RA, FLAC
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class mediaActions extends AhgController
{
    /**
     * Stream media file, transcoding if necessary for browser compatibility
     *
     * @param sfWebRequest $request
     */
    public function executeStream($request)
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
    public function executeDownload($request)
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
        $webDir = $this->config('sf_web_dir');

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
            'video/x-f4v',          // F4V
            'video/mpeg',           // MPEG
            'video/x-m2ts',         // M2TS
            'video/ogg',            // OGV
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
                'f4v', 'ogv', 'mpeg', 'mpg', 'm2ts', 'mts',
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

    /**
     * Get snippets for a digital object (AJAX)
     *
     * @param sfWebRequest $request
     */
    public function executeSnippets($request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');

        if (!$id) {
            echo json_encode(['error' => 'Digital object ID required']);
            return sfView::NONE;
        }

        try {
            $snippets = DB::table('media_snippets')
                ->where('digital_object_id', $id)
                ->orderBy('start_time')
                ->get()
                ->map(function ($row) {
                    return (array) $row;
                })
                ->toArray();

            echo json_encode(['snippets' => $snippets]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }

    /**
     * Save a media snippet (AJAX POST)
     *
     * @param sfWebRequest $request
     */
    public function executeSaveSnippet($request)
    {
        $this->getResponse()->setContentType('application/json');

        // GET /media/snippets?digital_object_id=X → list snippets
        if ($request->isMethod('get')) {
            $doId = (int) $request->getParameter('digital_object_id');
            if (!$doId) {
                echo json_encode(['error' => 'digital_object_id required']);

                return sfView::NONE;
            }

            try {
                $snippets = DB::table('media_snippets')
                    ->where('digital_object_id', $doId)
                    ->orderBy('start_time')
                    ->get()
                    ->map(function ($row) {
                        return (array) $row;
                    })
                    ->toArray();
                echo json_encode(['snippets' => $snippets]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }

            return sfView::NONE;
        }

        if (!$request->isMethod('post')) {
            echo json_encode(['error' => 'POST method required']);
            return sfView::NONE;
        }

        // Get JSON body
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            echo json_encode(['error' => 'Invalid JSON data']);
            return sfView::NONE;
        }

        $digitalObjectId = (int) ($data['digital_object_id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $startTime = (float) ($data['start_time'] ?? 0);
        $endTime = (float) ($data['end_time'] ?? 0);
        $notes = trim($data['notes'] ?? '');

        if (!$digitalObjectId || !$title || $startTime < 0 || $endTime <= $startTime) {
            echo json_encode(['error' => 'Invalid snippet data']);
            return sfView::NONE;
        }

        try {
            $id = DB::table('media_snippets')->insertGetId([
                'digital_object_id' => $digitalObjectId,
                'title' => $title,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => $notes,
            ]);

            echo json_encode(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }

    /**
     * Delete a media snippet (AJAX DELETE)
     *
     * @param sfWebRequest $request
     */
    public function executeDeleteSnippet($request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');

        if (!$id) {
            echo json_encode(['error' => 'Snippet ID required']);
            return sfView::NONE;
        }

        try {
            DB::table('media_snippets')->where('id', $id)->delete();

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }

    /**
     * Extract media metadata (AJAX POST)
     */
    public function executeExtract($request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');
        if (!$id) {
            echo json_encode(['error' => 'Digital object ID required']);

            return sfView::NONE;
        }

        $digitalObject = QubitDigitalObject::getById($id);
        if (!$digitalObject) {
            echo json_encode(['error' => 'Digital object not found']);

            return sfView::NONE;
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!$filePath || !file_exists($filePath)) {
            echo json_encode(['error' => 'Media file not found']);

            return sfView::NONE;
        }

        // Use ffprobe for metadata extraction
        $escaped = escapeshellarg($filePath);
        $cmd = "ffprobe -v quiet -print_format json -show_format -show_streams {$escaped} 2>/dev/null";
        $output = shell_exec($cmd);
        $probe = json_decode($output ?: '{}', true);

        if (empty($probe)) {
            echo json_encode(['error' => 'Could not extract metadata (ffprobe failed)']);

            return sfView::NONE;
        }

        $format = $probe['format'] ?? [];
        $streams = $probe['streams'] ?? [];

        $audioStream = null;
        $videoStream = null;
        foreach ($streams as $s) {
            if ($s['codec_type'] === 'audio' && !$audioStream) {
                $audioStream = $s;
            }
            if ($s['codec_type'] === 'video' && !$videoStream) {
                $videoStream = $s;
            }
        }

        $isVideo = ($digitalObject->mediaTypeId == QubitTerm::VIDEO_ID) || strpos($digitalObject->mimeType, 'video') !== false;

        $meta = [
            'digital_object_id' => $id,
            'media_type' => $isVideo ? 'video' : 'audio',
            'format' => pathinfo($digitalObject->name, PATHINFO_EXTENSION),
            'duration' => (float) ($format['duration'] ?? 0),
            'file_size' => (int) ($format['size'] ?? filesize($filePath)),
            'bitrate' => (int) ($format['bit_rate'] ?? 0),
            'title' => $format['tags']['title'] ?? null,
            'artist' => $format['tags']['artist'] ?? null,
            'album' => $format['tags']['album'] ?? null,
            'genre' => $format['tags']['genre'] ?? null,
            'year' => $format['tags']['date'] ?? null,
            'copyright' => $format['tags']['copyright'] ?? null,
            'audio_codec' => $audioStream['codec_name'] ?? null,
            'audio_sample_rate' => (int) ($audioStream['sample_rate'] ?? 0),
            'audio_channels' => (int) ($audioStream['channels'] ?? 0),
            'audio_bits_per_sample' => (int) ($audioStream['bits_per_raw_sample'] ?? 0),
            'video_codec' => $videoStream['codec_name'] ?? null,
            'video_width' => (int) ($videoStream['width'] ?? 0),
            'video_height' => (int) ($videoStream['height'] ?? 0),
            'video_frame_rate' => $this->parseFrameRate($videoStream['r_frame_rate'] ?? '0/1'),
        ];

        try {
            DB::table('media_metadata')->updateOrInsert(
                ['digital_object_id' => $id],
                $meta
            );

            echo json_encode(['success' => true, 'metadata' => $meta]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }

    /**
     * Start transcription as background job (AJAX POST)
     * Returns immediately with status=processing, client polls /media/transcription/:id
     */
    public function executeTranscribe($request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');
        $lang = $request->getParameter('lang', 'en');
        $model = $request->getParameter('model', 'tiny');

        if (!$id) {
            echo json_encode(['error' => 'Digital object ID required']);

            return sfView::NONE;
        }

        $digitalObject = QubitDigitalObject::getById($id);
        if (!$digitalObject) {
            echo json_encode(['error' => 'Digital object not found']);

            return sfView::NONE;
        }

        $filePath = $this->getFilePath($digitalObject);
        if (!$filePath || !file_exists($filePath)) {
            echo json_encode(['error' => 'Media file not found']);

            return sfView::NONE;
        }

        // Check if already processing
        $lockFile = sys_get_temp_dir() . '/transcribe_' . $id . '.lock';
        if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 600) {
            echo json_encode(['success' => true, 'status' => 'processing', 'message' => 'Transcription already in progress']);

            return sfView::NONE;
        }

        // Create lock file
        file_put_contents($lockFile, json_encode(['started' => date('Y-m-d H:i:s'), 'pid' => getmypid()]));

        // Build background transcription script
        $rootDir = $this->config('sf_root_dir');
        $script = <<<PHP
<?php
// Background transcription worker
define('SF_ROOT_DIR', '{$rootDir}');
require_once SF_ROOT_DIR . '/config/ProjectConfiguration.class.php';
\$configuration = ProjectConfiguration::getApplicationConfiguration('qubit', 'prod', false);
sfContext::createInstance(\$configuration);

require_once SF_ROOT_DIR . '/atom-ahg-plugins/ahgIiifPlugin/lib/Extensions/IiifViewer/Services/TranscriptionService.php';

\$service = new \\AtomFramework\\Extensions\\IiifViewer\\Services\\TranscriptionService(['whisper_model' => '{$model}']);
\$result = \$service->transcribe({$id}, ['language' => '{$lang}', 'force' => true]);

// Remove lock file
@unlink('{$lockFile}');

// Write result status
\$statusFile = sys_get_temp_dir() . '/transcribe_{$id}.status';
file_put_contents(\$statusFile, json_encode([
    'completed' => date('Y-m-d H:i:s'),
    'success' => \$result !== null,
    'segments' => count(\$result['segments'] ?? []),
]));
PHP;

        $scriptFile = sys_get_temp_dir() . '/transcribe_' . $id . '.php';
        file_put_contents($scriptFile, $script);

        // Launch background process
        $logFile = $this->config('sf_log_dir', '/var/log/atom') . '/transcription-bg.log';
        $cmd = sprintf('php %s >> %s 2>&1 &', escapeshellarg($scriptFile), escapeshellarg($logFile));
        exec($cmd);

        echo json_encode([
            'success' => true,
            'status' => 'processing',
            'message' => 'Transcription started in background. The page will update when complete.',
        ]);

        return sfView::NONE;
    }

    /**
     * Get transcription data (AJAX GET) or download in format (vtt/srt/txt)
     */
    public function executeTranscription($request)
    {
        $id = (int) $request->getParameter('id');
        $format = $request->getParameter('format', 'json');

        if (!$id) {
            $this->getResponse()->setContentType('application/json');
            echo json_encode(['error' => 'Digital object ID required']);

            return sfView::NONE;
        }

        // Handle DELETE — remove transcription
        if ($request->isMethod('delete') || $request->isMethod('post') && $request->getParameter('_method') === 'delete') {
            $this->getResponse()->setContentType('application/json');

            try {
                DB::table('media_transcription')->where('digital_object_id', $id)->delete();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }

            return sfView::NONE;
        }

        try {
            $transcription = DB::table('media_transcription')
                ->where('digital_object_id', $id)
                ->first();

            if (!$transcription) {
                $this->getResponse()->setStatusCode(404);
                $this->getResponse()->setContentType('application/json');
                echo json_encode(['error' => 'No transcription found']);

                return sfView::NONE;
            }

            $segments = json_decode($transcription->segments ?? '[]', true);
            $data = json_decode($transcription->transcription_data ?? '{}', true);

            if ($format === 'json') {
                $this->getResponse()->setContentType('application/json');
                echo json_encode([
                    'full_text' => $transcription->full_text ?? '',
                    'language' => $transcription->language ?? 'en',
                    'confidence' => $transcription->confidence ?? null,
                    'segments' => !empty($data['segments']) ? $data['segments'] : $segments,
                    'segment_count' => $transcription->segment_count ?? count($segments),
                    'duration' => $transcription->duration ?? null,
                ]);
            } elseif ($format === 'vtt') {
                $this->getResponse()->setContentType('text/vtt');
                header('Content-Disposition: attachment; filename="transcription-' . $id . '.vtt"');
                echo "WEBVTT\n\n";
                foreach ($segments as $i => $seg) {
                    echo ($i + 1) . "\n";
                    echo $this->formatVttTime($seg['start'] ?? 0) . ' --> ' . $this->formatVttTime($seg['end'] ?? 0) . "\n";
                    echo trim($seg['text'] ?? '') . "\n\n";
                }
            } elseif ($format === 'srt') {
                $this->getResponse()->setContentType('text/srt');
                header('Content-Disposition: attachment; filename="transcription-' . $id . '.srt"');
                foreach ($segments as $i => $seg) {
                    echo ($i + 1) . "\n";
                    echo $this->formatSrtTime($seg['start'] ?? 0) . ' --> ' . $this->formatSrtTime($seg['end'] ?? 0) . "\n";
                    echo trim($seg['text'] ?? '') . "\n\n";
                }
            } elseif ($format === 'txt') {
                $this->getResponse()->setContentType('text/plain');
                header('Content-Disposition: attachment; filename="transcription-' . $id . '.txt"');
                echo $transcription->full_text ?? '';
            }
        } catch (Exception $e) {
            $this->getResponse()->setContentType('application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }

    /**
     * Get media metadata (AJAX GET)
     */
    public function executeMetadata($request)
    {
        $this->getResponse()->setContentType('application/json');

        $id = (int) $request->getParameter('id');
        if (!$id) {
            echo json_encode(['error' => 'Digital object ID required']);

            return sfView::NONE;
        }

        try {
            $meta = DB::table('media_metadata')
                ->where('digital_object_id', $id)
                ->first();

            if ($meta) {
                echo json_encode((array) $meta);
            } else {
                echo json_encode(['error' => 'No metadata found']);
            }
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }

        return sfView::NONE;
    }

    /**
     * On-demand file conversion endpoint
     *
     * Converts non-viewable formats to browser-friendly formats:
     * - PSD/CR2 -> JPEG (via ImageMagick)
     * - DOCX/XLSX/XLS/PPT/PPTX/ODT/ODS/ODP/RTF -> PDF (via LibreOffice)
     * - ZIP/RAR/TGZ/TAR.GZ -> JSON file listing
     * - TXT/CSV/LOG/MD/XML/JSON -> Plain text content
     * - JPS -> Serve as JPEG (stereo JPEG is valid JPEG)
     *
     * Results are cached in uploads/conversions/
     */
    public function executeConvert($request)
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
            $this->forward404('File not found');
        }

        $ext = strtolower(pathinfo($digitalObject->name, PATHINFO_EXTENSION));
        $cacheDir = $this->config('sf_web_dir') . '/uploads/conversions';

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        // PSD / CR2 -> JPEG via ImageMagick
        if (in_array($ext, ['psd', 'cr2', 'nef', 'arw', 'dng'])) {
            $cacheFile = $cacheDir . '/' . $id . '_' . $ext . '.jpg';

            if (!file_exists($cacheFile)) {
                $escaped = escapeshellarg($filePath . ($ext === 'psd' ? '[0]' : ''));
                $escapedOut = escapeshellarg($cacheFile);
                $cmd = "convert {$escaped} -resize 2048x2048 -quality 92 {$escapedOut} 2>&1";
                $output = shell_exec($cmd);

                if (!file_exists($cacheFile)) {
                    $this->getResponse()->setContentType('application/json');
                    echo json_encode(['error' => 'Conversion failed', 'detail' => $output]);

                    return sfView::NONE;
                }
            }

            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($cacheFile));
            header('Cache-Control: public, max-age=86400');
            readfile($cacheFile);

            return sfView::NONE;
        }

        // JPS -> serve as JPEG (stereo JPEG is valid JPEG)
        if ($ext === 'jps') {
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=86400');
            readfile($filePath);

            return sfView::NONE;
        }

        // SVG -> serve directly with proper content type
        if ($ext === 'svg') {
            header('Content-Type: image/svg+xml');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: public, max-age=86400');
            readfile($filePath);

            return sfView::NONE;
        }

        // Office documents -> PDF via LibreOffice
        if (in_array($ext, ['docx', 'doc', 'xlsx', 'xls', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'])) {
            $cacheFile = $cacheDir . '/' . $id . '_doc.pdf';

            if (!file_exists($cacheFile)) {
                $escaped = escapeshellarg($filePath);
                $escapedDir = escapeshellarg($cacheDir);
                // Convert to PDF in temp dir, then rename to cache path
                $tmpDir = sys_get_temp_dir() . '/lo_convert_' . $id;
                @mkdir($tmpDir, 0755, true);
                $cmd = sprintf(
                    'timeout 60 libreoffice --headless --norestore --convert-to pdf --outdir %s %s 2>&1',
                    escapeshellarg($tmpDir),
                    $escaped
                );
                $output = shell_exec($cmd);

                // Find the generated PDF
                $baseName = pathinfo($digitalObject->name, PATHINFO_FILENAME);
                $tmpPdf = $tmpDir . '/' . $baseName . '.pdf';
                if (file_exists($tmpPdf)) {
                    rename($tmpPdf, $cacheFile);
                }
                @rmdir($tmpDir);

                if (!file_exists($cacheFile)) {
                    $this->getResponse()->setContentType('application/json');
                    echo json_encode(['error' => 'PDF conversion failed', 'detail' => $output]);

                    return sfView::NONE;
                }
            }

            header('Content-Type: application/pdf');
            header('Content-Length: ' . filesize($cacheFile));
            header('Cache-Control: public, max-age=86400');
            readfile($cacheFile);

            return sfView::NONE;
        }

        // Plain text files -> serve as text
        if (in_array($ext, ['txt', 'csv', 'log', 'md', 'xml', 'json', 'yml', 'yaml', 'ini', 'cfg', 'conf'])) {
            $content = file_get_contents($filePath, false, null, 0, 512000); // Max 500KB
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: public, max-age=86400');
            echo $content;

            return sfView::NONE;
        }

        // ZIP archive -> JSON file listing
        if ($ext === 'zip') {
            $this->getResponse()->setContentType('application/json');
            $files = [];
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                for ($i = 0; $i < min($zip->numFiles, 500); $i++) {
                    $stat = $zip->statIndex($i);
                    $files[] = [
                        'name' => $stat['name'],
                        'size' => $stat['size'],
                        'compressed' => $stat['comp_size'],
                    ];
                }
                $zip->close();
                echo json_encode(['type' => 'zip', 'count' => count($files), 'files' => $files]);
            } else {
                echo json_encode(['error' => 'Could not open ZIP file']);
            }

            return sfView::NONE;
        }

        // RAR archive -> JSON file listing
        if ($ext === 'rar') {
            $this->getResponse()->setContentType('application/json');
            $escaped = escapeshellarg($filePath);
            $output = shell_exec("unrar l {$escaped} 2>&1");
            echo json_encode(['type' => 'rar', 'listing' => $output]);

            return sfView::NONE;
        }

        // TGZ/TAR.GZ archive -> JSON file listing
        if (in_array($ext, ['tgz', 'gz', 'tar'])) {
            $this->getResponse()->setContentType('application/json');
            $escaped = escapeshellarg($filePath);
            $cmd = ($ext === 'tar') ? "tar -tf {$escaped}" : "tar -tzf {$escaped}";
            $output = shell_exec($cmd . ' 2>&1');
            $files = array_filter(explode("\n", trim($output ?: '')));
            echo json_encode(['type' => $ext, 'count' => count($files), 'files' => array_slice($files, 0, 500)]);

            return sfView::NONE;
        }

        // Unsupported format
        $this->getResponse()->setContentType('application/json');
        echo json_encode(['error' => 'Unsupported format: ' . $ext]);

        return sfView::NONE;
    }

    /**
     * Parse frame rate string (e.g., "24000/1001") to float
     */
    protected function parseFrameRate(string $rate): float
    {
        if (strpos($rate, '/') !== false) {
            [$num, $den] = explode('/', $rate);

            return $den > 0 ? round((float) $num / (float) $den, 2) : 0;
        }

        return (float) $rate;
    }

    protected function formatVttTime(float $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = floor($seconds % 60);
        $ms = round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d.%03d', $h, $m, $s, $ms);
    }

    protected function formatSrtTime(float $seconds): string
    {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = floor($seconds % 60);
        $ms = round(($seconds - floor($seconds)) * 1000);

        return sprintf('%02d:%02d:%02d,%03d', $h, $m, $s, $ms);
    }
}