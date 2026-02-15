<?php
/**
 * AHG Voice — Describe Image via LLM (Ollama local / Anthropic cloud).
 *
 * POST /ahgVoice/describeImage
 * Params: digital_object_id (int)
 * Returns: JSON {success, description, source, model, information_object_id}
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgVoiceDescribeImageAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Initialize Laravel DB
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        \AhgCore\Core\AhgDb::init();

        // Auth check
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required'], 401);
        }

        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required'], 405);
        }

        $doId = (int) $request->getParameter('digital_object_id');
        $infoObjectId = (int) $request->getParameter('information_object_id');
        $slug = trim($request->getParameter('slug', ''));

        // Temporary debug log
        $debugLog = '/tmp/voice_debug.log';
        file_put_contents($debugLog, date('Y-m-d H:i:s') . " === describeImage request ===\n", FILE_APPEND);
        file_put_contents($debugLog, "  doId=$doId, infoObjectId=$infoObjectId, slug=$slug\n", FILE_APPEND);

        $digitalObject = null;
        $informationObject = null;
        $objectId = null;

        // Strategy 1: Direct digital object ID
        if ($doId > 0) {
            $digitalObject = DB::table('digital_object')->where('id', $doId)->first();
            file_put_contents($debugLog, "  Strategy1: doId=$doId => " . ($digitalObject ? 'FOUND' : 'null') . "\n", FILE_APPEND);
        }

        // Strategy 2: Information object ID → find its digital object
        if (!$digitalObject && $infoObjectId > 0) {
            $informationObject = DB::table('information_object')->where('id', $infoObjectId)->first();
            if ($informationObject) {
                $digitalObject = DB::table('digital_object')
                    ->where('object_id', $infoObjectId)
                    ->orderBy('id', 'asc')
                    ->first();
            }
            file_put_contents($debugLog, "  Strategy2: infoObjectId=$infoObjectId => io=" . ($informationObject ? 'FOUND' : 'null') . ", do=" . ($digitalObject ? 'FOUND' : 'null') . "\n", FILE_APPEND);
        }

        // Strategy 3: Slug → resolve information object → find digital object
        if (!$digitalObject && $slug !== '') {
            // Slug may include module prefix (e.g., "museum/test-opensearch" or "library/my-item")
            // Try the last segment first, then the full slug
            $slugParts = explode('/', $slug);
            $slugCandidates = [$slug];
            if (count($slugParts) > 1) {
                $slugCandidates[] = end($slugParts); // Last segment
            }

            file_put_contents($debugLog, "  Strategy3: candidates=" . json_encode($slugCandidates) . "\n", FILE_APPEND);

            foreach ($slugCandidates as $candidate) {
                $slugRow = DB::table('slug')->where('slug', $candidate)->first();
                file_put_contents($debugLog, "    candidate='$candidate' => slugRow=" . ($slugRow ? json_encode(['object_id' => $slugRow->object_id]) : 'null') . "\n", FILE_APPEND);
                if ($slugRow) {
                    $informationObject = DB::table('information_object')
                        ->where('id', $slugRow->object_id)
                        ->first();
                    file_put_contents($debugLog, "    io=" . ($informationObject ? 'FOUND(id=' . $informationObject->id . ')' : 'null') . "\n", FILE_APPEND);
                    if ($informationObject) {
                        $digitalObject = DB::table('digital_object')
                            ->where('object_id', $informationObject->id)
                            ->orderBy('id', 'asc')
                            ->first();
                        file_put_contents($debugLog, "    do=" . ($digitalObject ? 'FOUND(id=' . $digitalObject->id . ', name=' . $digitalObject->name . ')' : 'null') . "\n", FILE_APPEND);
                        break;
                    }
                }
            }
        }

        if (!$digitalObject) {
            file_put_contents($debugLog, "  RESULT: Digital object not found!\n\n", FILE_APPEND);
            return $this->renderJson(['success' => false, 'error' => 'Digital object not found']);
        }

        file_put_contents($debugLog, "  RESULT: Found DO id=" . $digitalObject->id . ", name=" . $digitalObject->name . "\n\n", FILE_APPEND);

        $doId = $digitalObject->id;
        $objectId = $digitalObject->object_id ?? null;
        if (!$informationObject && $objectId) {
            $informationObject = DB::table('information_object')->where('id', $objectId)->first();
        }

        // Resolve image file — prefer reference/thumbnail derivative (already JPEG)
        // over converting a potentially large master TIFF
        $useObject = $this->findBestDerivative($digitalObject) ?? $digitalObject;
        $path = $this->resolveFilePath($useObject);
        if (!$path || !file_exists($path)) {
            // Fall back to master if derivative path doesn't resolve
            if ($useObject->id !== $digitalObject->id) {
                $path = $this->resolveFilePath($digitalObject);
            }
            if (!$path || !file_exists($path)) {
                return $this->renderJson(['success' => false, 'error' => 'Image file not accessible']);
            }
            $useObject = $digitalObject; // Reset to master
        }

        // Determine context/standard for prompt
        $context = $this->detectContext($informationObject);

        // Load config
        $config = $this->loadConfig();

        // Attempt LLM description
        $result = $this->callLlm($path, $useObject->mime_type ?? 'image/jpeg', $context, $config);

        // Audit logging
        if ($config['audit_ai_calls']) {
            $this->logAudit($doId, $objectId, $result);
        }

        $result['information_object_id'] = $objectId;

        return $this->renderJson($result);
    }

    /**
     * Resolve the physical file path for a digital object.
     */
    protected function resolveFilePath($digitalObject)
    {
        $name = $digitalObject->name ?? '';
        $path = $digitalObject->path ?? '';

        if (!$name) {
            return null;
        }

        $webDir = sfConfig::get('sf_web_dir', sfConfig::get('sf_root_dir'));

        // DB path is typically /uploads/r/... (relative to web root)
        // Build full path: webDir + dbPath + name
        $fullDir = rtrim($webDir, '/') . '/' . trim($path, '/');
        $candidate = $fullDir . '/' . $name;
        if (file_exists($candidate)) {
            return $candidate;
        }

        // Try without trailing slash on dir (path already includes name separator)
        $candidate = rtrim($fullDir, '/') . $name;
        if (file_exists($candidate)) {
            return $candidate;
        }

        // Try reference copy instead of master
        $refDir = str_replace('/master/', '/reference/', $fullDir);
        $candidate = $refDir . '/' . $name;
        if (file_exists($candidate)) {
            return $candidate;
        }

        return null;
    }

    /**
     * Detect the descriptive context (ISAD, CCO, MARC, etc.).
     */
    protected function detectContext($informationObject)
    {
        if (!$informationObject) {
            return 'default';
        }

        // Check display_object_config for sector
        try {
            $config = DB::table('display_object_config')
                ->where('object_id', $informationObject->id)
                ->first();
            if ($config && !empty($config->glam_type)) {
                $type = strtolower($config->glam_type);
                if (strpos($type, 'museum') !== false) return 'cco';
                if (strpos($type, 'library') !== false) return 'marc';
                if (strpos($type, 'gallery') !== false) return 'vra';
                if (strpos($type, 'dam') !== false) return 'iptc';
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        return 'isad'; // Default: archival
    }

    /**
     * Find the best derivative for LLM processing.
     * Prefers: reference copy (usage_id=141) > thumbnail (usage_id=142).
     * Returns null if no derivative exists.
     */
    protected function findBestDerivative($digitalObject)
    {
        $doId = $digitalObject->id;

        // Look for child derivatives (parent_id = this DO's id)
        // Prefer reference (141) over thumbnail (142) — better quality for LLM
        $derivative = DB::table('digital_object')
            ->where('parent_id', $doId)
            ->whereIn('usage_id', [141, 142])
            ->orderByRaw('FIELD(usage_id, 141, 142)')
            ->first();

        return $derivative;
    }

    /**
     * Convert unsupported image formats (TIFF, BMP, etc.) to JPEG for LLM processing.
     * Resizes large images to max 1024px. Uses ImageMagick, then ffmpeg, then GD as fallbacks.
     * Returns [filePath, mimeType] — original if already supported, converted otherwise.
     */
    protected function ensureSupportedFormat($filePath, $mimeType)
    {
        $supported = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($mimeType, $supported)) {
            // Even supported formats may need resizing for very large images
            $size = @getimagesize($filePath);
            if ($size && ($size[0] > 2048 || $size[1] > 2048)) {
                return $this->resizeImage($filePath, $mimeType);
            }
            return [$filePath, $mimeType];
        }

        // Convert to JPEG
        $tmpPath = sys_get_temp_dir() . '/voice_convert_' . md5($filePath) . '.jpg';

        // Re-use cached conversion if recent (< 1 hour)
        if (file_exists($tmpPath) && (time() - filemtime($tmpPath)) < 3600) {
            return [$tmpPath, 'image/jpeg'];
        }

        // Strategy 1: ImageMagick with resize (handles most formats + large images)
        $cmd = sprintf(
            'convert %s[0] -resize 1024x1024\\> -quality 85 %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($tmpPath)
        );
        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($tmpPath) && filesize($tmpPath) > 0) {
            return [$tmpPath, 'image/jpeg'];
        }

        // Strategy 2: ffmpeg (handles very large TIFFs that exceed ImageMagick pixel limits)
        $cmd = sprintf(
            'ffmpeg -y -i %s -vf "scale=\'min(1024,iw)\':\'min(1024,ih)\':force_original_aspect_ratio=decrease" -q:v 3 -frames:v 1 -update 1 %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($tmpPath)
        );
        exec($cmd, $output2, $exitCode2);

        if ($exitCode2 === 0 && file_exists($tmpPath) && filesize($tmpPath) > 0) {
            return [$tmpPath, 'image/jpeg'];
        }

        // Strategy 3: PHP GD (last resort — may fail on large files)
        $img = @imagecreatefromstring(file_get_contents($filePath));
        if ($img) {
            imagejpeg($img, $tmpPath, 85);
            imagedestroy($img);
            if (file_exists($tmpPath) && filesize($tmpPath) > 0) {
                return [$tmpPath, 'image/jpeg'];
            }
        }

        // Return original if all conversion fails — LLM will handle or reject
        return [$filePath, $mimeType];
    }

    /**
     * Resize a large supported-format image to max 1024px for LLM processing.
     */
    protected function resizeImage($filePath, $mimeType)
    {
        $tmpPath = sys_get_temp_dir() . '/voice_resize_' . md5($filePath) . '.jpg';

        if (file_exists($tmpPath) && (time() - filemtime($tmpPath)) < 3600) {
            return [$tmpPath, 'image/jpeg'];
        }

        $cmd = sprintf(
            'convert %s[0] -resize 1024x1024\\> -quality 85 %s 2>&1',
            escapeshellarg($filePath),
            escapeshellarg($tmpPath)
        );
        exec($cmd, $output, $exitCode);

        if ($exitCode === 0 && file_exists($tmpPath) && filesize($tmpPath) > 0) {
            return [$tmpPath, 'image/jpeg'];
        }

        return [$filePath, $mimeType];
    }

    /**
     * Call LLM (local first, then cloud fallback if hybrid).
     */
    protected function callLlm($filePath, $mimeType, $context, $config)
    {
        $provider = $config['llm_provider'] ?? 'local';

        // Convert unsupported formats (TIFF, BMP, etc.) to JPEG
        [$filePath, $mimeType] = $this->ensureSupportedFormat($filePath, $mimeType);

        // Read and encode image
        $imageData = file_get_contents($filePath);
        if (!$imageData) {
            return ['success' => false, 'error' => 'Could not read image file'];
        }
        $base64 = base64_encode($imageData);

        // Try local first (if local or hybrid)
        if ($provider === 'local' || $provider === 'hybrid') {
            $result = $this->callLocal($base64, $context, $config);
            if ($result['success']) {
                return $result;
            }
            // If local-only, return the error
            if ($provider === 'local') {
                return $result;
            }
            // Hybrid: fall through to cloud
        }

        // Try cloud (if cloud or hybrid fallback)
        if ($provider === 'cloud' || $provider === 'hybrid') {
            return $this->callCloud($base64, $mimeType, $context, $config);
        }

        return ['success' => false, 'error' => 'No LLM provider configured'];
    }

    /**
     * Call Ollama (local LLM).
     */
    protected function callLocal($base64, $context, $config)
    {
        require_once dirname(__FILE__) . '/../lib/VoicePromptTemplates.php';

        $url = rtrim($config['local_llm_url'] ?? 'http://localhost:11434', '/') . '/api/generate';
        $model = $config['local_llm_model'] ?? 'llava:7b';
        $timeout = (int) ($config['local_llm_timeout'] ?? 30);
        $prompt = VoicePromptTemplates::getPrompt($context);

        $payload = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'images' => [$base64],
            'stream' => false,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'Local LLM unavailable' . ($error ? ': ' . $error : ''),
                'fallback_available' => true,
            ];
        }

        $data = json_decode($response, true);
        $description = $data['response'] ?? '';

        if (empty($description)) {
            return ['success' => false, 'error' => 'Local LLM returned empty response'];
        }

        return [
            'success'     => true,
            'description' => trim($description),
            'source'      => 'local',
            'model'       => $model,
        ];
    }

    /**
     * Call Anthropic Claude API (cloud).
     */
    protected function callCloud($base64, $mimeType, $context, $config)
    {
        require_once dirname(__FILE__) . '/../lib/VoicePromptTemplates.php';

        $apiKey = $config['anthropic_api_key'] ?? '';
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'Anthropic API key not configured'];
        }

        // Check daily limit
        $dailyLimit = (int) ($config['daily_cloud_limit'] ?? 50);
        $todayCount = $this->getDailyCloudUsage();
        if ($todayCount >= $dailyLimit) {
            return ['success' => false, 'error' => 'Daily cloud limit reached (' . $dailyLimit . ')'];
        }

        $model = $config['cloud_model'] ?? 'claude-sonnet-4-20250514';
        $prompt = VoicePromptTemplates::getCloudPrompt($context);

        // Normalize mime type
        $validMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $validMimes)) {
            $mimeType = 'image/jpeg'; // Fallback
        }

        $payload = json_encode([
            'model'      => $model,
            'max_tokens' => 1000,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mimeType,
                                'data'       => $base64,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            $errMsg = 'Cloud AI unavailable';
            if ($response) {
                $errData = json_decode($response, true);
                if (!empty($errData['error']['message'])) {
                    $errMsg = $errData['error']['message'];
                }
            }
            return ['success' => false, 'error' => $errMsg];
        }

        $data = json_decode($response, true);
        $description = '';
        if (!empty($data['content'])) {
            foreach ($data['content'] as $block) {
                if (($block['type'] ?? '') === 'text') {
                    $description .= $block['text'];
                }
            }
        }

        if (empty($description)) {
            return ['success' => false, 'error' => 'Cloud AI returned empty response'];
        }

        return [
            'success'     => true,
            'description' => trim($description),
            'source'      => 'cloud',
            'model'       => $model,
        ];
    }

    /**
     * Count today's cloud API calls from audit log.
     */
    protected function getDailyCloudUsage()
    {
        try {
            return DB::table('audit_log')
                ->where('action', 'voice_ai_describe')
                ->where('created_at', '>=', date('Y-m-d 00:00:00'))
                ->where('source', 'cloud')
                ->count();
        } catch (\Exception $e) {
            return 0; // Table may not exist
        }
    }

    /**
     * Log AI call to audit trail.
     */
    protected function logAudit($doId, $objectId, $result)
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('audit_log')) {
                return;
            }
            DB::table('audit_log')->insert([
                'user_id'    => $this->getUser()->getAttribute('user_id'),
                'action'     => 'voice_ai_describe',
                'object_type' => 'digital_object',
                'object_id'  => $doId,
                'source'     => $result['source'] ?? 'unknown',
                'details'    => json_encode([
                    'information_object_id' => $objectId,
                    'model'   => $result['model'] ?? null,
                    'success' => $result['success'] ?? false,
                ]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silent — audit failure shouldn't break the feature
        }
    }

    /**
     * Load voice config.
     */
    protected function loadConfig()
    {
        $configFile = sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/config/voiceConfig.php';
        if (file_exists($configFile)) {
            return include $configFile;
        }
        return [
            'llm_provider'      => 'local',
            'local_llm_url'     => 'http://localhost:11434',
            'local_llm_model'   => 'llava:7b',
            'local_llm_timeout' => 30,
            'anthropic_api_key'  => '',
            'cloud_model'        => 'claude-sonnet-4-20250514',
            'daily_cloud_limit'  => 50,
            'audit_ai_calls'     => true,
        ];
    }

    /**
     * Render JSON response.
     */
    protected function renderJson($data, $statusCode = 200)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setContent(json_encode($data));

        return sfView::NONE;
    }
}
