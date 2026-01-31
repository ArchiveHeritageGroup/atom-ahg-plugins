<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * CLI task to scan for duplicate records.
 */
class dedupeScanTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID to scan'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Scan entire system'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum records to scan'),
        ]);

        $this->namespace = 'dedupe';
        $this->name = 'scan';
        $this->briefDescription = 'Scan for duplicate records';
        $this->detailedDescription = <<<EOF
Scan the system for duplicate records using configured detection rules.

Examples:
  php symfony dedupe:scan --repository=1    # Scan specific repository
  php symfony dedupe:scan --all             # Scan entire system
  php symfony dedupe:scan --limit=1000      # Limit to 1000 records
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgDedupePlugin/lib/Services/DedupeService.php';

        $service = new \ahgDedupePlugin\Services\DedupeService();

        $repositoryId = $options['repository'] ? (int) $options['repository'] : null;

        if (!$options['all'] && !$repositoryId) {
            $this->logSection('dedupe', 'Please specify --repository=ID or --all', null, 'ERROR');

            return 1;
        }

        // Create scan job
        $scanId = $service->startScan($repositoryId);
        $this->logSection('dedupe', "Started scan job #{$scanId}");

        // Get scan info
        $scan = DB::table('ahg_dedupe_scan')->where('id', $scanId)->first();
        $this->logSection('dedupe', "Total records to scan: {$scan->total_records}");

        // Run scan with progress
        $self = $this;
        $results = $service->runScan($scanId, function ($processed, $total) use ($self) {
            $percent = round(($processed / $total) * 100, 1);
            $self->logSection('dedupe', "Progress: {$processed}/{$total} ({$percent}%)");
        });

        $this->logSection('dedupe', '=== Scan Complete ===');
        $this->logSection('dedupe', "Processed: {$results['processed']}");
        $this->logSection('dedupe', "Duplicates found: {$results['duplicates_found']}", null, $results['duplicates_found'] > 0 ? 'COMMENT' : 'INFO');

        if ($results['duplicates_found'] > 0) {
            $this->logSection('dedupe', 'Review duplicates at: /admin/dedupe/browse');
        }

        return 0;
    }
}
