<?php

/**
 * TripoSR Generate Task
 *
 * CLI task for generating 3D models from 2D images using TripoSR
 *
 * Usage:
 *   php symfony triposr:generate --image=/path/to/image.jpg
 *   php symfony triposr:generate --image=/path/to/image.jpg --object-id=12345
 *   php symfony triposr:generate --image=/path/to/image.jpg --import
 *   php symfony triposr:generate --health
 *   php symfony triposr:generate --preload
 *
 * @package ahg3DModelPlugin
 */
class triposrGenerateTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('image', null, sfCommandOption::PARAMETER_OPTIONAL, 'Path to input image'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Information object ID to link'),
            new sfCommandOption('import', null, sfCommandOption::PARAMETER_NONE, 'Import generated model to AtoM'),
            new sfCommandOption('remove-bg', null, sfCommandOption::PARAMETER_OPTIONAL, 'Remove background (true/false)', 'true'),
            new sfCommandOption('resolution', null, sfCommandOption::PARAMETER_OPTIONAL, 'Marching cubes resolution', '256'),
            new sfCommandOption('texture', null, sfCommandOption::PARAMETER_NONE, 'Bake texture (exports as OBJ)'),
            new sfCommandOption('health', null, sfCommandOption::PARAMETER_NONE, 'Check TripoSR API health'),
            new sfCommandOption('preload', null, sfCommandOption::PARAMETER_NONE, 'Preload model into memory'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show job statistics'),
            new sfCommandOption('jobs', null, sfCommandOption::PARAMETER_NONE, 'List recent jobs'),
        ]);

        $this->namespace = 'triposr';
        $this->name = 'generate';
        $this->briefDescription = 'Generate 3D model from 2D image using TripoSR';
        $this->detailedDescription = <<<EOF
The [triposr:generate|INFO] task generates 3D models from 2D images.

Call it with:

  [php symfony triposr:generate --image=/path/to/image.jpg|INFO]

Options:
  --image         Path to input image (PNG, JPG, WEBP)
  --object-id     Link to information_object ID
  --import        Import generated model to AtoM after generation
  --remove-bg     Remove background from image (default: true)
  --resolution    Mesh resolution 128-512 (default: 256)
  --texture       Bake texture into model (exports as OBJ instead of GLB)
  --health        Check TripoSR API health status
  --preload       Preload TripoSR model into memory
  --stats         Show generation statistics
  --jobs          List recent jobs
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        // Initialize
        sfContext::createInstance($this->configuration);
        $databaseManager = new sfDatabaseManager($this->configuration);

        // Bootstrap Laravel
        $bootstrapPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }

        // Load service
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahg3DModelPlugin/lib/Services/TripoSRService.php';
        $service = new \Ahg3DModel\Services\TripoSRService();

        // Handle options
        if ($options['health']) {
            return $this->showHealth($service);
        }

        if ($options['preload']) {
            return $this->preloadModel($service);
        }

        if ($options['stats']) {
            return $this->showStats($service);
        }

        if ($options['jobs']) {
            return $this->listJobs($service);
        }

        // Generate model
        if (!$options['image']) {
            $this->logSection('error', 'No image specified. Use --image=/path/to/image.jpg');
            return 1;
        }

        return $this->generateModel($service, $options);
    }

    /**
     * Show API health status
     */
    private function showHealth($service)
    {
        $this->logSection('triposr', 'Checking TripoSR API health...');

        $health = $service->checkHealth();

        if ($health['status'] === 'ok') {
            $this->logSection('health', 'API Status: OK', null, 'INFO');
            $this->logSection('health', 'API URL: ' . $health['api_url']);
            $this->logSection('health', 'CUDA Available: ' . ($health['cuda_available'] ? 'Yes' : 'No'));
            $this->logSection('health', 'Device: ' . $health['device']);
            $this->logSection('health', 'Mode: ' . $health['mode']);
            $this->logSection('health', 'Model Loaded: ' . ($health['model_loaded'] ? 'Yes' : 'No'));

            if ($health['remote_configured']) {
                $this->logSection('health', 'Remote GPU: Configured');
                if ($health['remote_status']) {
                    $this->logSection('health', 'Remote Status: ' . json_encode($health['remote_status']));
                }
            }

            return 0;
        } else {
            $this->logSection('error', 'API Status: ERROR');
            $this->logSection('error', 'Message: ' . ($health['message'] ?? 'Unknown error'));
            $this->logSection('error', 'API URL: ' . ($health['api_url'] ?? 'Not set'));
            return 1;
        }
    }

    /**
     * Preload model into memory
     */
    private function preloadModel($service)
    {
        $this->logSection('triposr', 'Preloading TripoSR model (this may take a few minutes)...');

        $result = $service->preloadModel();

        if ($result['success'] ?? false) {
            $this->logSection('preload', 'Model preloaded successfully', null, 'INFO');
            $this->logSection('preload', 'Device: ' . ($result['device'] ?? 'unknown'));
            return 0;
        } else {
            $this->logSection('error', 'Failed to preload model: ' . ($result['error'] ?? 'Unknown error'));
            return 1;
        }
    }

    /**
     * Show job statistics
     */
    private function showStats($service)
    {
        $stats = $service->getStats();

        $this->logSection('stats', 'TripoSR Job Statistics');
        $this->logSection('stats', str_repeat('-', 40));
        $this->logSection('stats', sprintf('Total Jobs:     %d', $stats['total_jobs']));
        $this->logSection('stats', sprintf('Completed:      %d', $stats['completed']));
        $this->logSection('stats', sprintf('Failed:         %d', $stats['failed']));
        $this->logSection('stats', sprintf('Pending:        %d', $stats['pending']));
        $this->logSection('stats', sprintf('Processing:     %d', $stats['processing']));
        $this->logSection('stats', sprintf('Avg Time:       %.2fs', $stats['avg_processing_time']));

        return 0;
    }

    /**
     * List recent jobs
     */
    private function listJobs($service)
    {
        $jobs = $service->getRecentJobs(20);

        if (empty($jobs)) {
            $this->logSection('jobs', 'No jobs found');
            return 0;
        }

        $this->logSection('jobs', 'Recent TripoSR Jobs');
        $this->logSection('jobs', str_repeat('-', 80));
        $this->logSection('jobs', sprintf('%-6s %-12s %-10s %-8s %-20s', 'ID', 'Status', 'Mode', 'Time', 'Created'));
        $this->logSection('jobs', str_repeat('-', 80));

        foreach ($jobs as $job) {
            $time = $job->processing_time ? sprintf('%.1fs', $job->processing_time) : '-';
            $this->logSection('jobs', sprintf(
                '%-6d %-12s %-10s %-8s %-20s',
                $job->id,
                $job->status,
                $job->processing_mode ?? 'local',
                $time,
                $job->created_at
            ));
        }

        return 0;
    }

    /**
     * Generate 3D model from image
     */
    private function generateModel($service, $options)
    {
        $imagePath = $options['image'];

        if (!file_exists($imagePath)) {
            $this->logSection('error', 'Image file not found: ' . $imagePath);
            return 1;
        }

        $objectId = $options['object-id'] ? (int)$options['object-id'] : null;

        $this->logSection('triposr', 'Starting 3D model generation...');
        $this->logSection('triposr', 'Input: ' . $imagePath);

        if ($objectId) {
            $this->logSection('triposr', 'Object ID: ' . $objectId);
        }

        // Build options
        $genOptions = [
            'remove_bg' => $options['remove-bg'] === 'true' ? '1' : '0',
            'mc_resolution' => $options['resolution'],
            'bake_texture' => $options['texture'] ? '1' : '0',
        ];

        // Generate
        $startTime = microtime(true);
        $result = $service->generateFromImage($imagePath, $objectId, $genOptions);
        $elapsed = microtime(true) - $startTime;

        if (!$result['success']) {
            $this->logSection('error', 'Generation failed: ' . ($result['error'] ?? 'Unknown error'));
            if (isset($result['job_id'])) {
                $this->logSection('error', 'Job ID: ' . $result['job_id']);
            }
            return 1;
        }

        $this->logSection('success', 'Model generated successfully!', null, 'INFO');
        $this->logSection('result', 'Job ID: ' . $result['job_id']);
        $this->logSection('result', 'Model Path: ' . ($result['model_path'] ?? 'N/A'));
        $this->logSection('result', 'Format: ' . strtoupper($result['format']));
        $this->logSection('result', 'Processing Mode: ' . $result['processing_mode']);
        $this->logSection('result', 'Device: ' . $result['device']);
        $this->logSection('result', sprintf('Processing Time: %.2fs', $result['processing_time']));
        $this->logSection('result', sprintf('Total Time: %.2fs', $elapsed));

        // Import to AtoM if requested
        if ($options['import'] && $objectId) {
            $this->logSection('triposr', 'Importing model to AtoM...');

            $importResult = $service->importToAtoM($result['job_id'], $objectId);

            if ($importResult['success']) {
                $this->logSection('success', 'Model imported to AtoM', null, 'INFO');
                $this->logSection('result', 'Model ID: ' . $importResult['model_id']);
                $this->logSection('result', 'File Path: ' . $importResult['file_path']);
            } else {
                $this->logSection('error', 'Import failed: ' . ($importResult['error'] ?? 'Unknown error'));
            }
        } elseif ($options['import'] && !$objectId) {
            $this->logSection('warning', 'Cannot import without --object-id');
        }

        return 0;
    }
}
