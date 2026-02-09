<?php

declare(strict_types=1);

namespace Ahg3DModel\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TripoSRService
 *
 * Service for generating 3D models from 2D images using TripoSR
 * Supports both local CPU processing and remote GPU server
 *
 * @package ahg3DModelPlugin
 */
class TripoSRService
{
    private string $apiUrl;
    private int $timeout;
    private array $config;

    // Default TripoSR API endpoint
    private const DEFAULT_API_URL = 'http://127.0.0.1:5050';
    private const DEFAULT_TIMEOUT = 300;

    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Load TripoSR configuration from database
     */
    private function loadConfig(): void
    {
        $this->config = $this->getSettings();
        $this->apiUrl = $this->config['triposr_api_url'] ?? self::DEFAULT_API_URL;
        $this->timeout = (int)($this->config['triposr_timeout'] ?? self::DEFAULT_TIMEOUT);
    }

    /**
     * Get all TripoSR settings from database
     */
    public function getSettings(): array
    {
        $settings = DB::table('viewer_3d_settings')
            ->whereIn('setting_key', [
                'triposr_enabled',
                'triposr_api_url',
                'triposr_mode',
                'triposr_remote_url',
                'triposr_remote_api_key',
                'triposr_timeout',
                'triposr_remove_bg',
                'triposr_foreground_ratio',
                'triposr_mc_resolution',
                'triposr_bake_texture',
            ])
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        // Apply defaults
        return array_merge([
            'triposr_enabled' => '0',
            'triposr_api_url' => self::DEFAULT_API_URL,
            'triposr_mode' => 'local',
            'triposr_remote_url' => '',
            'triposr_remote_api_key' => '',
            'triposr_timeout' => (string)self::DEFAULT_TIMEOUT,
            'triposr_remove_bg' => '1',
            'triposr_foreground_ratio' => '0.85',
            'triposr_mc_resolution' => '256',
            'triposr_bake_texture' => '0',
        ], $settings);
    }

    /**
     * Save TripoSR settings to database
     */
    public function saveSettings(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                if (strpos($key, 'triposr_') !== 0) {
                    continue;
                }

                DB::table('viewer_3d_settings')
                    ->updateOrInsert(
                        ['setting_key' => $key],
                        [
                            'setting_value' => (string)$value,
                            'setting_type' => 'string',
                            'description' => $this->getSettingDescription($key),
                        ]
                    );
            }

            // Reload config after save
            $this->loadConfig();

            return true;
        } catch (\Exception $e) {
            error_log("TripoSRService::saveSettings error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get setting description
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            'triposr_enabled' => 'Enable TripoSR image-to-3D conversion',
            'triposr_api_url' => 'Local TripoSR API server URL',
            'triposr_mode' => 'Processing mode: local or remote',
            'triposr_remote_url' => 'Remote GPU server URL',
            'triposr_remote_api_key' => 'API key for remote GPU server',
            'triposr_timeout' => 'Request timeout in seconds',
            'triposr_remove_bg' => 'Remove background from input image',
            'triposr_foreground_ratio' => 'Foreground ratio after background removal',
            'triposr_mc_resolution' => 'Marching cubes resolution',
            'triposr_bake_texture' => 'Bake texture into model',
        ];

        return $descriptions[$key] ?? '';
    }

    /**
     * Check if TripoSR is enabled
     */
    public function isEnabled(): bool
    {
        return ($this->config['triposr_enabled'] ?? '0') === '1';
    }

    /**
     * Check health of TripoSR API
     */
    public function checkHealth(): array
    {
        try {
            $ch = curl_init($this->apiUrl . '/health');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'status' => 'error',
                    'message' => 'Connection failed: ' . $error,
                    'api_url' => $this->apiUrl,
                ];
            }

            if ($httpCode !== 200) {
                return [
                    'status' => 'error',
                    'message' => 'API returned HTTP ' . $httpCode,
                    'api_url' => $this->apiUrl,
                ];
            }

            $data = json_decode($response, true);

            return [
                'status' => 'ok',
                'api_url' => $this->apiUrl,
                'cuda_available' => $data['cuda_available'] ?? false,
                'device' => $data['device'] ?? 'unknown',
                'mode' => $data['mode'] ?? 'unknown',
                'model_loaded' => $data['model_loaded'] ?? false,
                'remote_configured' => $data['remote_configured'] ?? false,
                'remote_status' => $data['remote_status'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'api_url' => $this->apiUrl,
            ];
        }
    }

    /**
     * Configure the TripoSR API server
     */
    public function configureApi(): bool
    {
        try {
            $config = [
                'mode' => $this->config['triposr_mode'] ?? 'local',
                'remote_url' => $this->config['triposr_remote_url'] ?? '',
                'remote_api_key' => $this->config['triposr_remote_api_key'] ?? '',
                'default_remove_bg' => ($this->config['triposr_remove_bg'] ?? '1') === '1',
                'default_foreground_ratio' => (float)($this->config['triposr_foreground_ratio'] ?? 0.85),
                'default_mc_resolution' => (int)($this->config['triposr_mc_resolution'] ?? 256),
                'timeout_seconds' => (int)($this->config['triposr_timeout'] ?? 300),
            ];

            $ch = curl_init($this->apiUrl . '/config');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($config),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                error_log("TripoSRService::configureApi error: HTTP $httpCode");
                return false;
            }

            $data = json_decode($response, true);
            return $data['success'] ?? false;
        } catch (\Exception $e) {
            error_log("TripoSRService::configureApi error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate 3D model from image
     *
     * @param string $imagePath Path to input image
     * @param int|null $objectId Optional information_object ID to link
     * @param array $options Generation options
     * @return array Result with job_id, model_path, etc.
     */
    public function generateFromImage(string $imagePath, ?int $objectId = null, array $options = []): array
    {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'TripoSR is not enabled',
            ];
        }

        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Image file not found: ' . $imagePath,
            ];
        }

        // Create job record
        $jobId = $this->createJob($objectId, $imagePath);

        try {
            // Prepare form data
            $postFields = [
                'image' => new \CURLFile($imagePath),
                'remove_bg' => $options['remove_bg'] ?? $this->config['triposr_remove_bg'] ?? '1',
                'foreground_ratio' => $options['foreground_ratio'] ?? $this->config['triposr_foreground_ratio'] ?? '0.85',
                'mc_resolution' => $options['mc_resolution'] ?? $this->config['triposr_mc_resolution'] ?? '256',
                'bake_texture' => $options['bake_texture'] ?? $this->config['triposr_bake_texture'] ?? '0',
            ];

            // Convert boolean strings
            $postFields['remove_bg'] = $postFields['remove_bg'] === '1' || $postFields['remove_bg'] === true ? 'true' : 'false';
            $postFields['bake_texture'] = $postFields['bake_texture'] === '1' || $postFields['bake_texture'] === true ? 'true' : 'false';

            $this->updateJobStatus($jobId, 'processing');

            $ch = curl_init($this->apiUrl . '/generate');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->updateJobStatus($jobId, 'failed', $error);
                return [
                    'success' => false,
                    'error' => 'API request failed: ' . $error,
                    'job_id' => $jobId,
                ];
            }

            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['error'] ?? 'HTTP ' . $httpCode;
                $this->updateJobStatus($jobId, 'failed', $errorMsg);
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'job_id' => $jobId,
                ];
            }

            $data = json_decode($response, true);

            if (!($data['success'] ?? false)) {
                $errorMsg = $data['error'] ?? 'Unknown error';
                $this->updateJobStatus($jobId, 'failed', $errorMsg);
                return [
                    'success' => false,
                    'error' => $errorMsg,
                    'job_id' => $jobId,
                ];
            }

            // Update job with result
            $this->updateJobResult($jobId, $data);

            return [
                'success' => true,
                'job_id' => $jobId,
                'triposr_job_id' => $data['job_id'] ?? null,
                'model_path' => $data['model_path'] ?? null,
                'model_url' => $data['model_url'] ?? null,
                'format' => $data['format'] ?? 'glb',
                'processing_time' => $data['processing_time'] ?? 0,
                'processing_mode' => $data['processing_mode'] ?? 'local',
                'device' => $data['device'] ?? 'cpu',
            ];
        } catch (\Exception $e) {
            $this->updateJobStatus($jobId, 'failed', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'job_id' => $jobId,
            ];
        }
    }

    /**
     * Preload the TripoSR model into memory
     */
    public function preloadModel(): array
    {
        try {
            $ch = curl_init($this->apiUrl . '/preload');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                $errorData = json_decode($response, true);
                return [
                    'success' => false,
                    'error' => $errorData['error'] ?? 'HTTP ' . $httpCode,
                ];
            }

            return json_decode($response, true);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a job record in database
     */
    private function createJob(?int $objectId, string $inputPath): int
    {
        return DB::table('triposr_jobs')->insertGetId([
            'object_id' => $objectId,
            'input_image' => $inputPath,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update job status
     */
    private function updateJobStatus(int $jobId, string $status, ?string $errorMessage = null): void
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($errorMessage) {
            $data['error_message'] = $errorMessage;
        }

        if ($status === 'processing') {
            $data['started_at'] = date('Y-m-d H:i:s');
        }

        DB::table('triposr_jobs')
            ->where('id', $jobId)
            ->update($data);
    }

    /**
     * Update job with result data
     */
    private function updateJobResult(int $jobId, array $result): void
    {
        DB::table('triposr_jobs')
            ->where('id', $jobId)
            ->update([
                'status' => 'completed',
                'output_model' => $result['model_path'] ?? null,
                'output_format' => $result['format'] ?? 'glb',
                'processing_time' => $result['processing_time'] ?? 0,
                'processing_mode' => $result['processing_mode'] ?? 'local',
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Get job by ID
     */
    public function getJob(int $jobId): ?object
    {
        return DB::table('triposr_jobs')
            ->where('id', $jobId)
            ->first();
    }

    /**
     * Get jobs for an information object
     */
    public function getJobsForObject(int $objectId): array
    {
        return DB::table('triposr_jobs')
            ->where('object_id', $objectId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get recent jobs
     */
    public function getRecentJobs(int $limit = 20): array
    {
        return DB::table('triposr_jobs')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get job statistics
     */
    public function getStats(): array
    {
        $total = DB::table('triposr_jobs')->count();
        $completed = DB::table('triposr_jobs')->where('status', 'completed')->count();
        $failed = DB::table('triposr_jobs')->where('status', 'failed')->count();
        $pending = DB::table('triposr_jobs')->where('status', 'pending')->count();
        $processing = DB::table('triposr_jobs')->where('status', 'processing')->count();

        $avgTime = DB::table('triposr_jobs')
            ->where('status', 'completed')
            ->avg('processing_time');

        return [
            'total_jobs' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
            'processing' => $processing,
            'avg_processing_time' => round((float)($avgTime ?? 0), 2),
        ];
    }

    /**
     * Import generated model to AtoM as a digital object
     *
     * @param int $jobId TripoSR job ID
     * @param int $objectId Target information_object ID
     * @return array Result with model_id
     */
    public function importToAtoM(int $jobId, int $objectId): array
    {
        $job = $this->getJob($jobId);

        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        if ($job->status !== 'completed') {
            return ['success' => false, 'error' => 'Job not completed'];
        }

        if (!$job->output_model || !file_exists($job->output_model)) {
            return ['success' => false, 'error' => 'Output model not found'];
        }

        try {
            // Copy model to uploads directory
            $filename = basename($job->output_model);
            $rootDir = class_exists('\AtomFramework\Helpers\PathResolver')
                ? \AtomFramework\Helpers\PathResolver::getRootDir()
                : \sfConfig::get('sf_root_dir', dirname(__DIR__, 4));
            $uploadsDir = $rootDir . '/uploads/3dmodels/' . $objectId;

            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            $destPath = $uploadsDir . '/' . $filename;
            copy($job->output_model, $destPath);

            // Create object_3d_model record
            // Store relative path from /uploads/ directory
            $relativePath = '3dmodels/' . $objectId . '/' . $filename;

            $modelId = DB::table('object_3d_model')->insertGetId([
                'object_id' => $objectId,
                'filename' => $filename,
                'original_filename' => $filename,
                'file_path' => $relativePath,
                'file_size' => filesize($destPath),
                'mime_type' => 'model/gltf-binary',
                'format' => $job->output_format ?? 'glb',
                'auto_rotate' => 1,
                'ar_enabled' => 1,
                'is_primary' => 1,
                'is_public' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Link job to model
            DB::table('triposr_jobs')
                ->where('id', $jobId)
                ->update([
                    'model_id' => $modelId,
                    'object_id' => $objectId,
                ]);

            return [
                'success' => true,
                'model_id' => $modelId,
                'file_path' => $destPath,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
