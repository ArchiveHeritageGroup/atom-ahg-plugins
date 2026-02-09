<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to process DOI queue.
 */
class doiProcessQueueTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum items to process', 10),
            new sfCommandOption('retry-failed', null, sfCommandOption::PARAMETER_NONE, 'Retry failed items'),
        ]);

        $this->namespace = 'doi';
        $this->name = 'process-queue';
        $this->briefDescription = 'Process the DOI minting queue';
        $this->detailedDescription = <<<EOF
Process pending DOI minting operations from the queue.

Examples:
  php symfony doi:process-queue              # Process pending items
  php symfony doi:process-queue --limit=50   # Process up to 50 items
  php symfony doi:process-queue --retry-failed # Retry failed items

Cron setup (every 5 minutes):
  */5 * * * * cd /usr/share/nginx/archive && php symfony doi:process-queue
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';

        $service = new \ahgDoiPlugin\Services\DoiService();
        $limit = (int) ($options['limit'] ?? 10);

        // Retry failed items if requested
        if ($options['retry-failed']) {
            $retried = DB::table('ahg_doi_queue')
                ->where('status', 'failed')
                ->update([
                    'status' => 'pending',
                    'attempts' => 0,
                    'scheduled_at' => date('Y-m-d H:i:s'),
                ]);

            $this->logSection('doi', "Retrying {$retried} failed items");
        }

        // Show queue status
        $pending = DB::table('ahg_doi_queue')->where('status', 'pending')->count();
        $failed = DB::table('ahg_doi_queue')->where('status', 'failed')->count();

        $this->logSection('doi', "Queue status: {$pending} pending, {$failed} failed");

        if ($pending === 0) {
            $this->logSection('doi', 'No pending items to process');

            return 0;
        }

        // Process queue
        $this->logSection('doi', "Processing up to {$limit} items...");

        $results = $service->processQueue($limit);

        $this->logSection('doi', "Processed: {$results['processed']}");
        $this->logSection('doi', "Success: {$results['success']}", null, 'INFO');
        $this->logSection('doi', "Failed: {$results['failed']}", null, $results['failed'] > 0 ? 'ERROR' : 'INFO');

        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                $this->logSection('doi', "  - {$error}", null, 'ERROR');
            }
        }

        return $results['failed'] > 0 ? 1 : 0;
    }
}
