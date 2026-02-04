<?php

/**
 * Process pending webhook retries
 *
 * Run this via cron to retry failed webhook deliveries:
 *   php symfony api:webhook-process-retries
 *
 * Recommended cron schedule: every 5 minutes
 *   /5 * * * * cd /path/to/atom && php symfony api:webhook-process-retries >> /var/log/atom/webhooks.log 2>&1
 */
class webhookProcessRetriesTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum deliveries to process', 100),
            new sfCommandOption('cleanup', null, sfCommandOption::PARAMETER_OPTIONAL, 'Clean up old deliveries (days to keep)', 0),
        ]);

        $this->namespace = 'api';
        $this->name = 'webhook-process-retries';
        $this->briefDescription = 'Process pending webhook retries';
        $this->detailedDescription = <<<EOF
The [api:webhook-process-retries|INFO] task processes pending webhook
deliveries that are due for retry.

Call it with:

  [php symfony api:webhook-process-retries|INFO]

Options:
  --limit=N    Maximum deliveries to process (default: 100)
  --cleanup=N  Clean up deliveries older than N days (default: 0, disabled)
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        // Load framework bootstrap
        $bootstrapPath = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrapPath)) {
            require_once $bootstrapPath;
        }

        // Load service
        require_once sfConfig::get('sf_plugins_dir') . '/ahgAPIPlugin/lib/Services/WebhookService.php';

        $limit = (int) $options['limit'];
        $cleanupDays = (int) $options['cleanup'];

        $this->logSection('webhook', 'Processing webhook retries...');

        // Process retries
        $processed = \AhgAPI\Services\WebhookService::processRetries($limit);
        $this->logSection('webhook', sprintf('Processed %d deliveries', $processed));

        // Cleanup old deliveries if requested
        if ($cleanupDays > 0) {
            $deleted = \AhgAPI\Services\WebhookService::cleanupOldDeliveries($cleanupDays);
            $this->logSection('webhook', sprintf('Cleaned up %d old deliveries', $deleted));
        }

        $this->logSection('webhook', 'Done');
    }
}
