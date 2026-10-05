<?php

/**
 * AHG Voice - Describe 3D Object via multi-angle renders + LLM.
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
        $force = (bool) $request->getParameter('force', false);

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

        // If not forcing, check if extent_and_medium already has AI-described content
        if (!$force && $objectId) {
            $culture = sfConfig::get('sf_default_culture', 'en');
            $i18n = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->first();
            $extentVal = $i18n->extent_and_medium ?? '';
            if (stripos($extentVal, '[AI-described]') === 0) {
                // Strip the tag for reading back
                $description = trim(substr($extentVal, strlen('[AI-described]')));
                return $this->renderJson([
                    'success' => true,
                    'description' => $description,
                    'source' => 'cached',
                    'model' => null,
                    'information_object_id' => $objectId,
                    'render_count' => 0,
                    'cached' => true,
                    'from_field' => 'extent_and_medium',
                ]);
            }
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

        // Create a single labeled collage from the 6 renders (3x2 grid).
        // Sending ONE image to the LLM is far faster than 6 separate base64 images
        // and avoids PHP-FPM's request_terminate_timeout.
        $collagePath = $multiAngleDir . '/collage.jpg';
        $collageData = $this->createCollage($renders, $collagePath);

        if (!$collageData) {
            return $this->renderJson(['success' => false, 'error' => 'Failed to create collage from renders']);
        }

        $base64Collage = base64_encode($collageData);

        // Detect context
        $context = $this->detectContext($informationObject);

        // Load config
        $config = $this->loadConfig();

        // Gather contextual hints for the LLM: filename + existing scope_and_content
        $fileName = pathinfo($digitalObject->name ?? '', PATHINFO_FILENAME);
        $scopeContent = '';
        if ($objectId) {
            $culture = sfConfig::get('sf_default_culture', 'en');
            $i18n = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', $culture)
                ->first();
            $scopeContent = $i18n->scope_and_content ?? '';
        }

        // Call LLM with single collage image + context hints
        $result = $this->callCollageLlm($base64Collage, array_keys($renders), $context, $config, $fileName, $scopeContent);

        // Audit logging
        if ($config['audit_ai_calls'] ?? true) {
            $this->logAudit($doId, $objectId, $result);
        }

        $result['information_object_id'] = $objectId;
        $result['render_count'] = count($renders);
        $result['cached'] = $cached;

        return $this->renderJson($result);
    }

    /**
     * Create a labeled 3x2 collage from individual render PNGs using ImageMagick montage.
     * Returns the collage image data or null on failure.
     */
    protected function createCollage(array $renders, string $outputPath): ?string
    {
        // If collage already exists and is newer than all renders, return cached
        if (file_exists($outputPath) && filesize($outputPath) > 500) {
            $collageMtime = filemtime($outputPath);
            $allOlder = true;
            foreach ($renders as $path) {
                if (filemtime($path) > $collageMtime) {
                    $allOlder = false;
                    break;
                }
            }
            if ($allOlder) {
                return file_get_contents($outputPath);
            }
        }

        // Ensure the output directory is writable
        $outputDir = dirname($outputPath);
        if (is_dir($outputDir) && !is_writable($outputDir)) {
            @chmod($outputDir, 0775);
        }
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0775, true);
        }
        // Fallback to tmp if still not writable
        if (!is_writable($outputDir)) {
            $outputPath = sys_get_temp_dir() . '/ahg_collage_' . md5(implode(',', $renders)) . '.png';
        }

        // Build montage command: 3x2 grid with labels
        $labelOrder = ['front', 'back', 'left', 'right', 'top', 'detail'];
        $inputFiles = [];
        foreach ($labelOrder as $view) {
            if (isset($renders[$view])) {
                $inputFiles[] = '-label ' . escapeshellarg(ucfirst($view)) . ' ' . escapeshellarg($renders[$view]);
            }
        }
        // Add any remaining renders not in the standard order
        foreach ($renders as $view => $path) {
            if (!in_array($view, $labelOrder)) {
                $inputFiles[] = '-label ' . escapeshellarg(ucfirst($view)) . ' ' . escapeshellarg($path);
            }
        }

        if (empty($inputFiles)) {
            return null;
        }

        $outEsc = escapeshellarg($outputPath);
        $cmd = 'montage ' . implode(' ', $inputFiles)
            . ' -tile 3x2 -geometry 192x192+2+2'
            . ' -background white -fill black -pointsize 12'
            . ' -quality 80'
            . ' ' . $outEsc . ' 2>&1';

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || !file_exists($outputPath) || filesize($outputPath) < 500) {
            error_log('[VoiceDescribe3D] Montage failed (exit=' . $exitCode . '): ' . implode("\n", $output));
            return null;
        }

        return file_get_contents($outputPath);
    }

    /**
     * Call LLM with single collage image (local first, cloud fallback if hybrid).
     */
    protected function callCollageLlm(string $base64Collage, array $viewLabels, string $context, array $config, string $fileName = '', string $scopeContent = ''): array
    {
        $provider = $config['llm_provider'] ?? 'local';

        if ($provider === 'local' || $provider === 'hybrid') {
            $result = $this->callCollageLocal($base64Collage, $viewLabels, $context, $config, $fileName, $scopeContent);
            if ($result['success']) {
                return $result;
            }
            // Hybrid is local until the gateway has a cloud route (see
            // callCollageCloud); return the local error rather than a refusal.
            return $result;
        }

        if ($provider === 'cloud') {
            return $this->callCollageCloud($base64Collage, $viewLabels, $context, $config, $fileName, $scopeContent);
        }

        return ['success' => false, 'error' => 'No LLM provider configured'];
    }

    /**
     * Call Ollama with single collage image.
     */
    protected function callCollageLocal(string $base64Collage, array $viewLabels, string $context, array $config, string $fileName = '', string $scopeContent = ''): array
    {
        require_once dirname(__FILE__) . '/../lib/VoicePromptTemplates.php';

        $model = $config['local_llm_model'] ?? 'llava:7b';
        $timeout = (int) ($config['local_llm_timeout'] ?? 30);
        $timeout = max($timeout, 50); // Single collage is much faster than 6 images
        $prompt = \VoicePromptTemplates::get3DPrompt($context, $fileName, $scopeContent);

        // Vision via the AHG AI gateway (keyed + SSRF-guarded) - no direct node
        // port (migration 2026-06-15). "local" provider = local models via gateway.
        $result = \AtomFramework\Services\AI\AiGatewayClient::fromSettings()->visionGenerate(
            $prompt,
            [$base64Collage],
            $model,
            ['timeout' => $timeout]
        );

        if (empty($result['success'])) {
            return [
                'success' => false,
                'error' => 'Local LLM unavailable: ' . ($result['error'] ?? 'unknown'),
                'fallback_available' => true,
            ];
        }

        $description = trim((string) $result['text']);
        if ('' === $description) {
            return ['success' => false, 'error' => 'Local LLM returned empty response'];
        }

        return [
            'success' => true,
            'description' => $description,
            'source' => 'local',
            'model' => $result['model'] ?? $model,
        ];
    }

    /**
     * Cloud vision is not available.
     *
     * This used to call api.anthropic.com directly with an API key from the
     * voice settings. Every AI call must go through the AHG AI gateway
     * (ai.theahg.co.za), which is keyed, metered and audited, and the gateway
     * has no cloud-vendor route: /ai/v1/anthropic/* returns 404. So the direct
     * call is removed rather than routed. When the gateway gains a cloud route,
     * add it to AiGatewayClient and call that here. "hybrid" therefore behaves
     * as "local", and "cloud" reports why it cannot run.
     */
    protected function callCollageCloud(string $base64Collage, array $viewLabels, string $context, array $config, string $fileName = '', string $scopeContent = ''): array
    {
        return [
            'success' => false,
            'error' => 'Cloud AI is not available: AI requests must go through the AHG AI gateway, which has no cloud model route yet. Use the local provider.',
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
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // Table may not exist
        }

        return 'isad';
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
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($e, basename(__FILE__).':'.__LINE__);
            // Silent - audit failure shouldn't break the feature
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
