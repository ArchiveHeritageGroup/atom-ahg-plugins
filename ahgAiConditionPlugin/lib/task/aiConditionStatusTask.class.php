<?php

/**
 * Check AI Condition Service status.
 *
 * Usage: php symfony ai-condition:status
 */
class aiConditionStatusTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'ai-condition';
        $this->name = 'status';
        $this->briefDescription = 'Check AI Condition Service health';
        $this->detailedDescription = 'Checks connectivity to the AI condition assessment FastAPI service.';
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $serviceFile = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/lib/Services/AiConditionService.php';
        require_once $serviceFile;

        $service = new \ahgAiConditionPlugin\Services\AiConditionService();
        $result = $service->healthCheck();

        if ($result['success']) {
            $data = $result['data'];
            $this->logSection('health', 'Service is ONLINE', null, 'INFO');
            $this->logSection('info', 'Version: ' . ($data['version'] ?? 'unknown'));
            $this->logSection('info', 'GPU: ' . ($data['gpu_available'] ? 'Yes' : 'No'));

            if (!empty($data['models'])) {
                foreach ($data['models'] as $model => $status) {
                    $this->logSection('model', $model . ': ' . ($status ? 'loaded' : 'not loaded'));
                }
            }
        } else {
            $this->logSection('error', 'Service is OFFLINE: ' . ($result['error'] ?? 'unknown'), null, 'ERROR');
            $this->logSection('info', 'Start with: /usr/share/nginx/archive/ai-condition-service/scripts/start.sh');
        }

        return $result['success'] ? 0 : 1;
    }
}
