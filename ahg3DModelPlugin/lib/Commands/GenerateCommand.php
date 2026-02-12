<?php

namespace AtomFramework\Console\Commands\Triposr;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate 3D models from 2D images using TripoSR.
 */
class GenerateCommand extends BaseCommand
{
    protected string $name = 'triposr:generate';
    protected string $description = 'Generate 3D model from 2D image using TripoSR';
    protected string $detailedDescription = <<<'EOF'
    Generates 3D models from 2D images using TripoSR.

    Examples:
      php bin/atom triposr:generate --image=/path/to/image.jpg
      php bin/atom triposr:generate --image=/path/to/image.jpg --object-id=12345
      php bin/atom triposr:generate --image=/path/to/image.jpg --import
      php bin/atom triposr:generate --health
      php bin/atom triposr:generate --preload
      php bin/atom triposr:generate --stats
      php bin/atom triposr:generate --jobs

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

    protected function configure(): void
    {
        $this->addOption('image', null, 'Path to input image');
        $this->addOption('object-id', null, 'Information object ID to link');
        $this->addOption('import', null, 'Import generated model to AtoM');
        $this->addOption('remove-bg', null, 'Remove background (true/false)', 'true');
        $this->addOption('resolution', null, 'Marching cubes resolution', '256');
        $this->addOption('texture', null, 'Bake texture (exports as OBJ)');
        $this->addOption('health', null, 'Check TripoSR API health');
        $this->addOption('preload', null, 'Preload model into memory');
        $this->addOption('stats', null, 'Show job statistics');
        $this->addOption('jobs', null, 'List recent jobs');
    }

    protected function handle(): int
    {
        // Load service
        $servicePath = $this->getPluginsRoot() . '/ahg3DModelPlugin/lib/Services/TripoSRService.php';
        require_once $servicePath;
        $service = new \Ahg3DModel\Services\TripoSRService();

        // Handle options
        if ($this->hasOption('health')) {
            return $this->showHealth($service);
        }

        if ($this->hasOption('preload')) {
            return $this->preloadModel($service);
        }

        if ($this->hasOption('stats')) {
            return $this->showStats($service);
        }

        if ($this->hasOption('jobs')) {
            return $this->listJobs($service);
        }

        // Generate model
        if (!$this->hasOption('image')) {
            $this->error('No image specified. Use --image=/path/to/image.jpg');

            return 1;
        }

        return $this->generateModel($service);
    }

    /**
     * Show API health status.
     */
    private function showHealth($service): int
    {
        $this->info('Checking TripoSR API health...');

        $health = $service->checkHealth();

        if ($health['status'] === 'ok') {
            $this->success('API Status: OK');
            $this->line('API URL: ' . $health['api_url']);
            $this->line('CUDA Available: ' . ($health['cuda_available'] ? 'Yes' : 'No'));
            $this->line('Device: ' . $health['device']);
            $this->line('Mode: ' . $health['mode']);
            $this->line('Model Loaded: ' . ($health['model_loaded'] ? 'Yes' : 'No'));

            if ($health['remote_configured']) {
                $this->line('Remote GPU: Configured');
                if ($health['remote_status']) {
                    $this->line('Remote Status: ' . json_encode($health['remote_status']));
                }
            }

            return 0;
        }

        $this->error('API Status: ERROR');
        $this->error('Message: ' . ($health['message'] ?? 'Unknown error'));
        $this->error('API URL: ' . ($health['api_url'] ?? 'Not set'));

        return 1;
    }

    /**
     * Preload model into memory.
     */
    private function preloadModel($service): int
    {
        $this->info('Preloading TripoSR model (this may take a few minutes)...');

        $result = $service->preloadModel();

        if ($result['success'] ?? false) {
            $this->success('Model preloaded successfully');
            $this->line('Device: ' . ($result['device'] ?? 'unknown'));

            return 0;
        }

        $this->error('Failed to preload model: ' . ($result['error'] ?? 'Unknown error'));

        return 1;
    }

    /**
     * Show job statistics.
     */
    private function showStats($service): int
    {
        $stats = $service->getStats();

        $this->bold('TripoSR Job Statistics');
        $this->line(str_repeat('-', 40));
        $this->line(sprintf('Total Jobs:     %d', $stats['total_jobs']));
        $this->line(sprintf('Completed:      %d', $stats['completed']));
        $this->line(sprintf('Failed:         %d', $stats['failed']));
        $this->line(sprintf('Pending:        %d', $stats['pending']));
        $this->line(sprintf('Processing:     %d', $stats['processing']));
        $this->line(sprintf('Avg Time:       %.2fs', $stats['avg_processing_time']));

        return 0;
    }

    /**
     * List recent jobs.
     */
    private function listJobs($service): int
    {
        $jobs = $service->getRecentJobs(20);

        if (empty($jobs)) {
            $this->info('No jobs found');

            return 0;
        }

        $this->bold('Recent TripoSR Jobs');
        $this->line(str_repeat('-', 80));
        $this->line(sprintf('%-6s %-12s %-10s %-8s %-20s', 'ID', 'Status', 'Mode', 'Time', 'Created'));
        $this->line(str_repeat('-', 80));

        foreach ($jobs as $job) {
            $time = $job->processing_time ? sprintf('%.1fs', $job->processing_time) : '-';
            $this->line(sprintf(
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
     * Generate 3D model from image.
     */
    private function generateModel($service): int
    {
        $imagePath = $this->option('image');

        if (!file_exists($imagePath)) {
            $this->error('Image file not found: ' . $imagePath);

            return 1;
        }

        $objectId = $this->hasOption('object-id') ? (int) $this->option('object-id') : null;

        $this->info('Starting 3D model generation...');
        $this->line('Input: ' . $imagePath);

        if ($objectId) {
            $this->line('Object ID: ' . $objectId);
        }

        // Build options
        $genOptions = [
            'remove_bg' => $this->option('remove-bg') === 'true' ? '1' : '0',
            'mc_resolution' => $this->option('resolution'),
            'bake_texture' => $this->hasOption('texture') ? '1' : '0',
        ];

        // Generate
        $startTime = microtime(true);
        $result = $service->generateFromImage($imagePath, $objectId, $genOptions);
        $elapsed = microtime(true) - $startTime;

        if (!$result['success']) {
            $this->error('Generation failed: ' . ($result['error'] ?? 'Unknown error'));
            if (isset($result['job_id'])) {
                $this->error('Job ID: ' . $result['job_id']);
            }

            return 1;
        }

        $this->success('Model generated successfully!');
        $this->line('Job ID: ' . $result['job_id']);
        $this->line('Model Path: ' . ($result['model_path'] ?? 'N/A'));
        $this->line('Format: ' . strtoupper($result['format']));
        $this->line('Processing Mode: ' . $result['processing_mode']);
        $this->line('Device: ' . $result['device']);
        $this->line(sprintf('Processing Time: %.2fs', $result['processing_time']));
        $this->line(sprintf('Total Time: %.2fs', $elapsed));

        // Import to AtoM if requested
        if ($this->hasOption('import') && $objectId) {
            $this->info('Importing model to AtoM...');

            $importResult = $service->importToAtoM($result['job_id'], $objectId);

            if ($importResult['success']) {
                $this->success('Model imported to AtoM');
                $this->line('Model ID: ' . $importResult['model_id']);
                $this->line('File Path: ' . $importResult['file_path']);
            } else {
                $this->error('Import failed: ' . ($importResult['error'] ?? 'Unknown error'));
            }
        } elseif ($this->hasOption('import') && !$objectId) {
            $this->warning('Cannot import without --object-id');
        }

        return 0;
    }
}
