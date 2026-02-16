<?php

/**
 * AHG Voice — Describe 3D Object via multi-angle renders + LLM.
 *
 * POST /ahgVoice/describeObject
 * Params: digital_object_id | information_object_id | slug
 * Returns: JSON {success, description, source, model, render_count, cached, information_object_id}
 *
 * Generates 6 Blender renders (front, back, left, right, top, detail) of a 3D
 * model and sends them to an LLM (Ollama local / Anthropic cloud) for description.
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */

use Illuminate\Database\Capsule\Manager as DB;

class ahgVoiceDescribeObjectAction extends sfAction
{
    public function execute($request)
    {
        $this->getResponse()->setContentType('application/json');

        // Initialize Laravel DB
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        \AhgCore\Core\AhgDb::init();

        if (!$this->getUser()->isAuthenticated()) {
            return $this->renderJson(['success' => false, 'error' => 'Authentication required'], 401);
        }

        if (!$request->isMethod('POST')) {
            return $this->renderJson(['success' => false, 'error' => 'POST required'], 405);
        }

        $doId = (int) $request->getParameter('digital_object_id');
        $infoObjectId = (int) $request->getParameter('information_object_id');
        $slug = trim($request->getParameter('slug', ''));

        $digitalObject = null;
        $informationObject = null;
        $objectId = null;

        // Strategy 1: Direct digital object ID
        if ($doId > 0) {
            $digitalObject = DB::table('digital_object')->where('id', $doId)->first();
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
        }

        // Strategy 3: Slug → resolve information object → find digital object
        if (!$digitalObject && $slug !== '') {
            $slugParts = explode('/', $slug);
            $slugCandidates = [$slug];
            if (count($slugParts) > 1) {
                $slugCandidates[] = end($slugParts);
            }

            foreach ($slugCandidates as $candidate) {
                $slugRow = DB::table('slug')->where('slug', $candidate)->first();
                if ($slugRow) {
                    $informationObject = DB::table('information_object')
                        ->where('id', $slugRow->object_id)
                        ->first();
                    if ($informationObject) {
                        $digitalObject = DB::table('digital_object')
                            ->where('object_id', $informationObject->id)
                            ->orderBy('id', 'asc')
                            ->first();
                        break;
                    }
                }
            }
        }

        if (!$digitalObject) {
            return $this->renderJson(['success' => false, 'error' => 'Digital object not found']);
        }

        $doId = $digitalObject->id;
        $objectId = $digitalObject->object_id ?? null;
        if (!$informationObject && $objectId) {
            $informationObject = DB::table('information_object')->where('id', $objectId)->first();
        }

        // Validate: is this a 3D model?
        $ext = strtolower(pathinfo($digitalObject->name ?? '', PATHINFO_EXTENSION));
        $supported3DExts = ['glb', 'gltf', 'obj', 'stl', 'fbx', 'ply', 'dae'];
        $supported3DMimes = [
            'model/obj', 'model/gltf-binary', 'model/gltf+json', 'model/stl',
            'application/x-tgif', 'model/vnd.usdz+zip', 'application/x-ply',
        ];

        $is3D = in_array($ext, $supported3DExts) || in_array($digitalObject->mime_type ?? '', $supported3DMimes);
        if (!$is3D) {
            return $this->renderJson(['success' => false, 'error' => 'Not a 3D model file: ' . $digitalObject->name]);
        }

        // Resolve master file path
        $rootDir = sfConfig::get('sf_root_dir', sfConfig::get('sf_web_dir', '/usr/share/nginx/archive'));
        $masterPath = $rootDir . $digitalObject->path . $digitalObject->name;

        if (!file_exists($masterPath)) {
            return $this->renderJson(['success' => false, 'error' => 'Master 3D file not accessible']);
        }

        // Generate multi-angle renders
        $multiAngleDir = dirname($masterPath) . '/multiangle';

        // Lazy-load ThreeDThumbnailService
        $frameworkDir = sfConfig::get('sf_root_dir', '/usr/share/nginx/archive') . '/atom-framework';
        $servicePath = $frameworkDir . '/src/Services/ThreeDThumbnailService.php';
        $pathResolverPath = $frameworkDir . '/src/Helpers/PathResolver.php';

        if (file_exists($pathResolverPath)) {
            require_once $pathResolverPath;
        }
        if (file_exists($servicePath)) {
            require_once $servicePath;
        }

        $cached = false;
        $renders = [];

        if (class_exists('AtomExtensions\\Services\\ThreeDThumbnailService')) {
            $thumbService = new \AtomExtensions\Services\ThreeDThumbnailService();
            $renders = $thumbService->generateMultiAngle($masterPath, $multiAngleDir);
            // Check if these were cached
            if (!empty($renders)) {
                $firstRender = reset($renders);
                $cached = filemtime($masterPath) < filemtime($firstRender);
            }
        } else {
            // Fallback: check if renders already exist
            $views = ['front', 'back', 'left', 'right', 'top', 'detail'];
            foreach ($views as $view) {
                $png = $multiAngleDir . '/' . $view . '.png';
                if (file_exists($png) && filesize($png) > 500) {
                    $renders[$view] = $png;
                }
            }
        }

        if (empty($renders)) {
            return $this->renderJson(['success' => false, 'error' => 'Failed to generate 3D renders. Check Blender installation.']);
        }

        // Read all renders as base64
        $base64Array = [];
        $viewLabels = [];
        foreach ($renders as $viewName => $filePath) {
            $imageData = file_get_contents($filePath);
            if ($imageData) {
                $base64Array[] = base64_encode($imageData);
                $viewLabels[] = $viewName;
            }
        }

        if (empty($base64Array)) {
            return $this->renderJson(['success' => false, 'error' => 'Could not read render files']);
        }

        // Detect context
        $context = $this->detectContext($informationObject);

        // Load config
        $config = $this->loadConfig();

        // Call LLM with multi-image
        $result = $this->callMultiImageLlm($base64Array, $viewLabels, $context, $config);

        // Audit logging
        if ($config['audit_ai_calls'] ?? true) {
            $this->logAudit($doId, $objectId, $result);
        }

        $result['information_object_id'] = $objectId;
        $result['render_count'] = count($base64Array);
        $result['cached'] = $cached;

        return $this->renderJson($result);
    }

    /**
     * Call LLM with multiple images (local first, cloud fallback if hybrid).
     */
    protected function callMultiImageLlm(array $base64Array, array $viewLabels, string $context, array $config): array
    {
        $provider = $config['llm_provider'] ?? 'local';

        if ($provider === 'local' || $provider === 'hybrid') {
            $result = $this->callMultiImageLocal($base64Array, $context, $config);
            if ($result['success']) {
                return $result;
            }
            if ($provider === 'local') {
                return $result;
            }
        }

        if ($provider === 'cloud' || $provider === 'hybrid') {
            return $this->callMultiImageCloud($base64Array, $viewLabels, $context, $config);
        }

        return ['success' => false, 'error' => 'No LLM provider configured'];
    }

    /**
     * Call Ollama with multiple images.
     */
    protected function callMultiImageLocal(array $base64Array, string $context, array $config): array
    {
        require_once dirname(__FILE__) . '/../lib/VoicePromptTemplates.php';

        $url = rtrim($config['local_llm_url'] ?? 'http://localhost:11434', '/') . '/api/generate';
        $model = $config['local_llm_model'] ?? 'llava:7b';
        $timeout = (int) ($config['local_llm_timeout'] ?? 30);
        $timeout = max($timeout, 180); // 3D renders need more time
        $prompt = \VoicePromptTemplates::get3DPrompt($context);

        $payload = json_encode([
            'model' => $model,
            'prompt' => $prompt,
            'images' => $base64Array,
            'stream' => false,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $timeout,
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
            'success' => true,
            'description' => trim($description),
            'source' => 'local',
            'model' => $model,
        ];
    }

    /**
     * Call Anthropic Claude API with multiple images.
     */
    protected function callMultiImageCloud(array $base64Array, array $viewLabels, string $context, array $config): array
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
        $prompt = \VoicePromptTemplates::get3DCloudPrompt($context);

        // Build content array: 6 image blocks + 1 text block
        $content = [];
        foreach ($base64Array as $i => $b64) {
            $label = $viewLabels[$i] ?? "view_{$i}";
            $content[] = [
                'type' => 'text',
                'text' => ucfirst($label) . ' view:',
            ];
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => 'image/png',
                    'data' => $b64,
                ],
            ];
        }
        $content[] = [
            'type' => 'text',
            'text' => $prompt,
        ];

        $payload = json_encode([
            'model' => $model,
            'max_tokens' => 2000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $content,
                ],
            ],
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 120,
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
            'success' => true,
            'description' => trim($description),
            'source' => 'cloud',
            'model' => $model,
        ];
    }

    protected function detectContext($informationObject): string
    {
        if (!$informationObject) {
            return 'default';
        }

        try {
            $config = DB::table('display_object_config')
                ->where('object_id', $informationObject->id)
                ->first();
            if ($config && !empty($config->glam_type)) {
                $type = strtolower($config->glam_type);
                if (strpos($type, 'museum') !== false) {
                    return 'cco';
                }
                if (strpos($type, 'library') !== false) {
                    return 'marc';
                }
                if (strpos($type, 'gallery') !== false) {
                    return 'vra';
                }
                if (strpos($type, 'dam') !== false) {
                    return 'iptc';
                }
            }
        } catch (\Exception $e) {
            // Table may not exist
        }

        return 'isad';
    }

    protected function getDailyCloudUsage(): int
    {
        try {
            return DB::table('audit_log')
                ->where('action', 'voice_ai_describe_3d')
                ->where('created_at', '>=', date('Y-m-d 00:00:00'))
                ->where('source', 'cloud')
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    protected function logAudit($doId, $objectId, array $result): void
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('audit_log')) {
                return;
            }
            DB::table('audit_log')->insert([
                'user_id' => $this->getUser()->getAttribute('user_id'),
                'action' => 'voice_ai_describe_3d',
                'object_type' => 'digital_object',
                'object_id' => $doId,
                'source' => $result['source'] ?? 'unknown',
                'details' => json_encode([
                    'information_object_id' => $objectId,
                    'model' => $result['model'] ?? null,
                    'success' => $result['success'] ?? false,
                    'render_count' => $result['render_count'] ?? 0,
                ]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Silent — audit failure shouldn't break the feature
        }
    }

    protected function loadConfig(): array
    {
        $configFile = sfConfig::get('sf_plugins_dir') . '/ahgThemeB5Plugin/config/voiceConfig.php';
        if (file_exists($configFile)) {
            return include $configFile;
        }
        return [
            'llm_provider' => 'local',
            'local_llm_url' => 'http://localhost:11434',
            'local_llm_model' => 'llava:7b',
            'local_llm_timeout' => 30,
            'anthropic_api_key' => '',
            'cloud_model' => 'claude-sonnet-4-20250514',
            'daily_cloud_limit' => 50,
            'audit_ai_calls' => true,
        ];
    }

    protected function renderJson(array $data, int $statusCode = 200)
    {
        $response = $this->getResponse();
        $response->setStatusCode($statusCode);
        $response->setContent(json_encode($data));

        return sfView::NONE;
    }
}
