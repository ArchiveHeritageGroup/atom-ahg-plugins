<?php

namespace AtomFramework\Console\Commands\Dedupe;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Scan for duplicate records.
 */
class ScanCommand extends BaseCommand
{
    protected string $name = 'dedupe:scan';
    protected string $description = 'Scan for duplicate records';
    protected string $detailedDescription = <<<'EOF'
    Scan the system for duplicate records using configured detection rules.

    Examples:
      php bin/atom dedupe:scan --repository=1    Scan specific repository
      php bin/atom dedupe:scan --all             Scan entire system
      php bin/atom dedupe:scan --limit=1000      Limit to 1000 records
    EOF;

    protected function configure(): void
    {
        $this->addOption('repository', 'r', 'Repository ID to scan');
        $this->addOption('all', 'a', 'Scan entire system');
        $this->addOption('limit', 'l', 'Maximum records to scan');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';
        if (!file_exists($serviceFile)) {
            $this->error("DedupeService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgDedupePlugin\Services\DedupeService();

        $repositoryOpt = $this->option('repository');
        $repositoryId = $repositoryOpt ? (int) $repositoryOpt : null;

        if (!$this->hasOption('all') && !$repositoryId) {
            $this->error('Please specify --repository=ID or --all');

            return 1;
        }

        // Create scan job
        $scanId = $service->startScan($repositoryId);
        $this->info("Started scan job #{$scanId}");

        // Get scan info
        $scan = DB::table('ahg_dedupe_scan')->where('id', $scanId)->first();
        $this->info("Total records to scan: {$scan->total_records}");

        // Run scan with progress
        $results = $service->runScan($scanId, function ($processed, $total) {
            $percent = round(($processed / $total) * 100, 1);
            $this->line("  Progress: {$processed}/{$total} ({$percent}%)");
        });

        $this->newline();
        $this->bold('  === Scan Complete ===');
        $this->line("  Processed: {$results['processed']}");

        if ($results['duplicates_found'] > 0) {
            $this->warning("  Duplicates found: {$results['duplicates_found']}");
            $this->line('  Review duplicates at: /admin/dedupe/browse');
        } else {
            $this->success("  Duplicates found: {$results['duplicates_found']}");
        }

        return 0;
    }
}
