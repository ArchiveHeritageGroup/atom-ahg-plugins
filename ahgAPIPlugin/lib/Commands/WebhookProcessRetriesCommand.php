<?php

namespace AtomFramework\Console\Commands\Api;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Process pending webhook retries.
 *
 * Run this via cron to retry failed webhook deliveries:
 *   php bin/atom api:webhook-process-retries
 *
 * Recommended cron schedule: every 5 minutes
 */
class WebhookProcessRetriesCommand extends BaseCommand
{
    protected string $name = 'api:webhook-process-retries';
    protected string $description = 'Process pending webhook retries';
    protected string $detailedDescription = <<<'EOF'
    Processes pending webhook deliveries that are due for retry.

    Examples:
      php bin/atom api:webhook-process-retries
      php bin/atom api:webhook-process-retries --limit=50
      php bin/atom api:webhook-process-retries --cleanup=30

    Options:
      --limit=N    Maximum deliveries to process (default: 100)
      --cleanup=N  Clean up deliveries older than N days (default: 0, disabled)
    EOF;

    protected function configure(): void
    {
        $this->addOption('limit', null, 'Maximum deliveries to process', '100');
        $this->addOption('cleanup', null, 'Clean up old deliveries (days to keep)', '0');
    }

    protected function handle(): int
    {
        // Load service
        $servicePath = $this->getAtomRoot() . '/plugins/ahgAPIPlugin/lib/Services/WebhookService.php';
        if (!file_exists($servicePath)) {
            $servicePath = $this->getPluginsRoot() . '/ahgAPIPlugin/lib/Services/WebhookService.php';
        }
        require_once $servicePath;

        $limit = (int) $this->option('limit');
        $cleanupDays = (int) $this->option('cleanup');

        $this->info('Processing webhook retries...');

        // Process retries
        $processed = \AhgAPI\Services\WebhookService::processRetries($limit);
        $this->line(sprintf('Processed %d deliveries', $processed));

        // Cleanup old deliveries if requested
        if ($cleanupDays > 0) {
            $deleted = \AhgAPI\Services\WebhookService::cleanupOldDeliveries($cleanupDays);
            $this->line(sprintf('Cleaned up %d old deliveries', $deleted));
        }

        $this->success('Done');

        return 0;
    }
}
