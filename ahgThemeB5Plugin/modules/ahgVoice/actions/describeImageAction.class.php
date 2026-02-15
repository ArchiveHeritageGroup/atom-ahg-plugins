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

        // Auth check
        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required'], 401);
        }

        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required'], 405);
        }

        $doId = (int) $request->getParameter('digital_object_id');
        if ($doId <= 0) {
            return $this->renderJson(['success' => false, 'error' => 'Invalid digital object ID']);
        }

        // Load digital object from DB
        $digitalObject = DB::table('digital_object')->where('id', $doId)->first();
        if (!$digitalObject) {
            return $this->renderJson(['success' => false, 'error' => 'Digital object not found']);
        }

        // Get the information object for context
        $objectId = $digitalObject->object_id ?? null;
        $informationObject = null;
        if ($objectId) {
            $informationObject = DB::table('information_object')->where('id', $objectId)->first();
        }

        // Resolve image file path
        $path = $this->resolveFilePath($digitalObject);
        if (!$path || !file_exists($path)) {
            return $this->renderJson(['success' => false, 'error' => 'Image file not accessible']);
        }

        // Determine context/standard for prompt
        $context = $this->detectContext($informationObject);

        // Load config
        $config = $this->loadConfig();

        // Attempt LLM description
        $result = $this->callLlm($path, $digitalObject->mime_type ?? 'image/jpeg', $context, $config);

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

        // Try standard AtoM upload path
        $uploadsDir = sfConfig::get('sf_upload_dir', sfConfig::get('sf_web_dir') . '/uploads');
        $fullPath = $uploadsDir . '/' . ltrim($path, '/');

        if (is_dir($fullPath)) {
            // Path is directory — look for the file inside
            $candidate = $fullPath . '/' . $name;
            if (file_exists($candidate)) {
                return $candidate;
            }
            // Try reference copy
            $refPath = str_replace('/master/', '/reference/', $fullPath);
            $candidate = $refPath . '/' . $name;
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Direct path
        if (file_exists($fullPath)) {
            return $fullPath;
        }

        // Try web dir relative
        $webPath = sfConfig::get('sf_web_dir') . '/' . ltrim($path, '/') . '/' . $name;
        if (file_exists($webPath)) {
            return $webPath;
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
     * Call LLM (local first, then cloud fallback if hybrid).
     */
    protected function callLlm($filePath, $mimeType, $context, $config)
    {
        $provider = $config['llm_provider'] ?? 'local';

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
