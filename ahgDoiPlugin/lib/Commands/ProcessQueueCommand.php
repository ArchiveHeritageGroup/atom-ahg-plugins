<?php

namespace AtomFramework\Console\Commands\Doi;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Process the DOI minting queue.
 */
class ProcessQueueCommand extends BaseCommand
{
    protected string $name = 'doi:process-queue';
    protected string $description = 'Process pending DOI queue';
    protected string $detailedDescription = <<<'EOF'
    Process pending DOI minting operations from the queue.

    Examples:
      php bin/atom doi:process-queue              Process pending items
      php bin/atom doi:process-queue --limit=50   Process up to 50 items
      php bin/atom doi:process-queue --retry-failed Retry failed items

    Cron setup (every 5 minutes):
      */5 * * * * cd /usr/share/nginx/archive && php bin/atom doi:process-queue
    EOF;

    protected function configure(): void
    {
        $this->addOption('limit', 'l', 'Maximum items to process', '10');
        $this->addOption('retry-failed', null, 'Retry failed items');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgDoiPlugin/lib/Services/DoiService.php';
        if (!file_exists($serviceFile)) {
            $this->error("DoiService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgDoiPlugin\Services\DoiService();
        $limit = (int) $this->option('limit', '10');

        // Retry failed items if requested
        if ($this->hasOption('retry-failed')) {
            $retried = DB::table('ahg_doi_queue')
                ->where('status', 'failed')
                ->update([
                    'status' => 'pending',
                    'attempts' => 0,
                    'scheduled_at' => date('Y-m-d H:i:s'),
                ]);

            $this->info("Retrying {$retried} failed items");
        }

        // Show queue status
        $pending = DB::table('ahg_doi_queue')->where('status', 'pending')->count();
        $failed = DB::table('ahg_doi_queue')->where('status', 'failed')->count();

        $this->info("Queue status: {$pending} pending, {$failed} failed");

        if ($pending === 0) {
            $this->info('No pending items to process');

            return 0;
        }

        // Process queue
        $this->info("Processing up to {$limit} items...");

        $results = $service->processQueue($limit);

        $this->newline();
        $this->line("  Processed: {$results['processed']}");
        $this->success("Success: {$results['success']}");

        if ($results['failed'] > 0) {
            $this->error("Failed: {$results['failed']}");
        } else {
            $this->line("  Failed: 0");
        }

        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $err) {
                $this->error("  - {$err}");
            }
        }

        return $results['failed'] > 0 ? 1 : 0;
    }
}
