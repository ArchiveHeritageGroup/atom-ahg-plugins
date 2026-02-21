<?php

/**
 * Bulk scan digital objects for condition assessment.
 *
 * Usage: php symfony ai-condition:bulk-scan --repository=5 --limit=100
 */
class aiConditionBulkScanTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID to scan'),
            new sfCommandOption('collection', null, sfCommandOption::PARAMETER_OPTIONAL, 'Top-level collection ID'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max objects to scan', 100),
            new sfCommandOption('confidence', null, sfCommandOption::PARAMETER_OPTIONAL, 'Min confidence threshold', 0.25),
        ]);

        $this->namespace = 'ai-condition';
        $this->name = 'bulk-scan';
        $this->briefDescription = 'Bulk scan digital objects for condition assessment';
        $this->detailedDescription = 'Scans all digital objects in a repository or collection through the AI condition service.';
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib/Services/AiConditionService.php';
        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib/Repositories/AiConditionRepository.php';

        $service = new \ahgAiConditionPlugin\Services\AiConditionService();
        $repo = new \ahgAiConditionPlugin\Repositories\AiConditionRepository();

        // Check service health first
        $health = $service->healthCheck();
        if (!$health['success']) {
            $this->logSection('error', 'AI service offline: ' . ($health['error'] ?? 'unreachable'), null, 'ERROR');
            return 1;
        }

        $this->logSection('bulk', 'AI service online. Starting bulk scan...');

        // Build query for digital objects
        $query = \Illuminate\Database\Capsule\Manager::table('digital_object as do')
            ->join('information_object as io', 'do.object_id', '=', 'io.id')
            ->whereNotNull('do.path')
            ->where('do.path', '!=', '');

        if (!empty($options['repository'])) {
            $query->where('io.repository_id', (int) $options['repository']);
        }

        if (!empty($options['collection'])) {
            $collId = (int) $options['collection'];
            $query->where(function ($q) use ($collId) {
                $q->where('io.id', $collId)
                  ->orWhere('io.parent_id', $collId);
            });
        }

        $limit = (int) ($options['limit'] ?? 100);
        $confidence = (float) ($options['confidence'] ?? 0.25);

        $objects = $query->select('do.id as digital_object_id', 'do.object_id', 'do.path')
            ->limit($limit)
            ->get();

        $total = count($objects);
        $this->logSection('bulk', "Found {$total} digital objects to scan");

        $scanned = 0;
        $errors = 0;
        $uploadDir = sfConfig::get('sf_upload_dir', sfConfig::get('sf_root_dir') . '/uploads');

        foreach ($objects as $i => $obj) {
            $filePath = $uploadDir . '/' . $obj->path;

            if (!file_exists($filePath)) {
                $this->logSection('skip', "[" . ($i + 1) . "/{$total}] File not found: {$obj->path}");
                continue;
            }

            $this->logSection('scan', "[" . ($i + 1) . "/{$total}] Scanning object {$obj->object_id}...");

            $result = $service->assessFile($filePath, [
                'information_object_id' => $obj->object_id,
                'confidence' => $confidence,
                'store' => true,
                'overlay' => false,
            ]);

            if (!empty($result['success'])) {
                $assessmentId = $repo->saveAssessment([
                    'information_object_id' => $obj->object_id,
                    'digital_object_id'     => $obj->digital_object_id,
                    'image_path'            => $obj->path,
                    'overall_score'         => $result['overall_score'] ?? null,
                    'condition_grade'       => $result['condition_grade'] ?? null,
                    'damage_count'          => count($result['damages'] ?? []),
                    'recommendations'       => $result['recommendations'] ?? null,
                    'model_version'         => $result['model_version'] ?? null,
                    'processing_time_ms'    => $result['processing_time_ms'] ?? null,
                    'confidence_threshold'  => $confidence,
                    'source'                => 'bulk',
                ]);

                if (!empty($result['damages'])) {
                    $repo->saveDamages($assessmentId, $result['damages']);
                }

                if ($obj->object_id && !empty($result['overall_score'])) {
                    $repo->saveHistory(
                        $obj->object_id,
                        $assessmentId,
                        $result['overall_score'],
                        $result['condition_grade'] ?? 'unknown',
                        count($result['damages'] ?? [])
                    );
                }

                $score = $result['overall_score'] ?? 'N/A';
                $grade = $result['condition_grade'] ?? 'unknown';
                $this->logSection('result', "Score: {$score}, Grade: {$grade}");
                $scanned++;
            } else {
                $this->logSection('error', 'Failed: ' . ($result['error'] ?? 'unknown'));
                $errors++;
            }

            // Brief pause to avoid overwhelming the service
            usleep(200000);
        }

        $this->logSection('bulk', "Complete. Scanned: {$scanned}, Errors: {$errors}, Total: {$total}");

        return $errors > 0 ? 1 : 0;
    }
}
