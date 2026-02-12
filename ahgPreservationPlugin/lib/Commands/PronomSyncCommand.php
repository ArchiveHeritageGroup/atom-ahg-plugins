<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class PronomSyncCommand extends BaseCommand
{
    protected string $name = 'preservation:pronom-sync';
    protected string $description = 'Sync format registry from PRONOM (UK National Archives)';
    protected string $detailedDescription = <<<'EOF'
Synchronizes format information from the UK National Archives PRONOM registry.
This provides authoritative format identification data and preservation risk assessment.

Examples:
  php bin/atom preservation:pronom-sync --status          # Show sync status
  php bin/atom preservation:pronom-sync --puid=fmt/18     # Sync specific PUID
  php bin/atom preservation:pronom-sync --unregistered    # Sync unregistered PUIDs
  php bin/atom preservation:pronom-sync --common          # Sync common archival formats
  php bin/atom preservation:pronom-sync --all             # Sync all known PUIDs
  php bin/atom preservation:pronom-sync --lookup=fmt/43   # Look up PUID info

PRONOM provides:
  - Official format names and versions
  - MIME types and file extensions
  - Binary signatures for identification
  - Format risk information
  - Preservation recommendations

More info: https://www.nationalarchives.gov.uk/pronom/
EOF;

    protected function configure(): void
    {
        $this->addOption('puid', null, 'Specific PUID to sync (e.g., fmt/18)');
        $this->addOption('all', null, 'Sync all PUIDs found in identified objects');
        $this->addOption('unregistered', null, 'Sync only unregistered PUIDs');
        $this->addOption('common', null, 'Sync common archival format PUIDs');
        $this->addOption('status', null, 'Show PRONOM sync status');
        $this->addOption('lookup', null, 'Look up a PUID without syncing');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();

        // Status check
        if ($this->hasOption('status')) {
            $this->showStatus($service);

            return 0;
        }

        // Lookup without sync
        if ($this->hasOption('lookup')) {
            $this->lookupPuid($service, $this->option('lookup'));

            return 0;
        }

        // Specific PUID sync
        if ($this->hasOption('puid')) {
            $this->syncSinglePuid($service, $this->option('puid'));

            return 0;
        }

        // Common formats sync
        if ($this->hasOption('common')) {
            $this->syncCommonFormats($service);

            return 0;
        }

        // Unregistered PUIDs sync
        if ($this->hasOption('unregistered')) {
            $this->syncUnregistered($service);

            return 0;
        }

        // Sync all known PUIDs
        if ($this->hasOption('all')) {
            $this->syncAll($service);

            return 0;
        }

        // Default: show status and help
        $this->showStatus($service);
        $this->newline();
        $this->info('Use --help to see available options');

        return 0;
    }

    /**
     * Show PRONOM sync status.
     */
    private function showStatus(\PreservationService $service): void
    {
        $this->bold('PRONOM Registry Sync Status');
        $this->newline();

        $status = $service->getPronomSyncStatus();

        $this->info('Format Registry:');
        $this->line("  Total formats:        {$status['registered_formats']}");
        $this->line("  With PUID:            {$status['formats_with_puid']}");
        $this->line("  Without PUID:         {$status['formats_without_puid']}");

        $this->newline();
        $this->info('Object Identification:');
        $this->line("  Unique PUIDs found:   {$status['unique_object_puids']}");
        $this->line("  Unregistered PUIDs:   {$status['unregistered_count']}");

        if (!empty($status['unregistered_puids'])) {
            $preview = array_slice($status['unregistered_puids'], 0, 5);
            $this->line('    ' . implode(', ', $preview) . (count($status['unregistered_puids']) > 5 ? '...' : ''));
        }

        $this->newline();
        $this->info('Format Risk Distribution:');
        foreach (['low', 'medium', 'high', 'critical'] as $level) {
            $count = $status['risk_distribution'][$level] ?? 0;
            $method = in_array($level, ['high', 'critical']) ? 'error' : 'info';
            $this->$method(sprintf('  %-10s: %d', ucfirst($level), $count));
        }

        if ($status['last_sync']) {
            $this->newline();
            $this->info('Last Sync:');
            $this->line("  Date:    {$status['last_sync']['datetime']}");
            $this->line("  Outcome: {$status['last_sync']['outcome']}");
        }
    }

    /**
     * Look up a PUID from PRONOM.
     */
    private function lookupPuid(\PreservationService $service, string $puid): void
    {
        $this->info("Looking up PUID: {$puid}");
        $this->newline();

        // First check local registry
        $local = $service->getFormatByPuid($puid);

        if ($local) {
            $this->info('Local Registry Entry:');
            $this->line("  Name:        {$local['name']}");
            $this->line("  Version:     " . ($local['version'] ?? '-'));
            $this->line("  MIME Type:   {$local['mime_type']}");
            $this->line("  Extension:   " . ($local['extension'] ?? '-'));
            $this->line("  Risk Level:  {$local['risk_level']}");
            $this->line("  Objects:     {$local['object_count']}");
            $this->newline();
        }

        // Fetch from PRONOM
        $this->info('Fetching from PRONOM...');

        $formatData = $service->fetchPronomFormat($puid);

        if (!$formatData) {
            $this->error('Failed to fetch from PRONOM');
            $this->line("URL: https://www.nationalarchives.gov.uk/pronom/{$puid}");

            return;
        }

        $this->newline();
        $this->info('PRONOM Data:');
        $this->line("  Name:        {$formatData['name']}");
        $this->line("  Version:     " . ($formatData['version'] ?? '-'));
        $this->line("  MIME Type:   " . ($formatData['mime_type'] ?? '-'));
        $this->line("  Extension:   " . ($formatData['extension'] ?? '-'));
        $this->line("  Has Sig:     " . ($formatData['binary_signature'] ? 'Yes' : 'No'));

        if ($formatData['description']) {
            $this->newline();
            $this->info('Description:');
            // Word wrap the description
            $wrapped = wordwrap($formatData['description'], 60, "\n");
            foreach (explode("\n", $wrapped) as $line) {
                $this->line("  {$line}");
            }
        }

        $this->newline();
        $this->line("PRONOM URL: https://www.nationalarchives.gov.uk/pronom/{$puid}");
    }

    /**
     * Sync a single PUID.
     */
    private function syncSinglePuid(\PreservationService $service, string $puid): void
    {
        $this->info("Syncing PUID: {$puid}");

        $results = $service->syncPronomRegistry([$puid]);

        if ($results['synced'] > 0) {
            if ($results['created'] > 0) {
                $this->success('Format created in registry');
            } else {
                $this->success('Format updated in registry');
            }
        }

        if ($results['failed'] > 0) {
            $this->error('Sync failed');
            foreach ($results['errors'] as $error) {
                $this->line("  {$error}");
            }
        }
    }

    /**
     * Sync common archival formats.
     */
    private function syncCommonFormats(\PreservationService $service): void
    {
        $this->info('Syncing common archival formats from PRONOM...');
        $this->newline();

        $results = $service->syncCommonFormats();

        $this->bold('Sync Complete:');
        $this->line("  Total synced: {$results['synced']}");
        $this->line("  Created:      {$results['created']}");
        $this->line("  Updated:      {$results['updated']}");
        $this->line("  Failed:       {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->newline();
            $this->error('Errors:');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->line("  {$error}");
            }
        }
    }

    /**
     * Sync unregistered PUIDs.
     */
    private function syncUnregistered(\PreservationService $service): void
    {
        $this->info('Syncing unregistered PUIDs from PRONOM...');

        $status = $service->getPronomSyncStatus();

        if (empty($status['unregistered_puids'])) {
            $this->success('All PUIDs are already registered!');

            return;
        }

        $this->line("Found {$status['unregistered_count']} unregistered PUIDs");
        $this->newline();

        $results = $service->syncAllUnregisteredPuids();

        $this->bold('Sync Complete:');
        $this->line("  Total synced: {$results['synced']}");
        $this->line("  Created:      {$results['created']}");
        $this->line("  Updated:      {$results['updated']}");
        $this->line("  Failed:       {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->newline();
            $this->error('Errors:');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->line("  {$error}");
            }
        }
    }

    /**
     * Sync all known PUIDs.
     */
    private function syncAll(\PreservationService $service): void
    {
        $this->info('Syncing all known PUIDs from PRONOM...');
        $this->newline();

        // Get all unique PUIDs
        $puids = DB::table('preservation_object_format')
            ->whereNotNull('puid')
            ->distinct()
            ->pluck('puid')
            ->toArray();

        if (empty($puids)) {
            $this->error('No PUIDs found to sync');
            $this->line('Run format identification first: preservation:identify');

            return;
        }

        $this->line('Found ' . count($puids) . ' unique PUIDs to sync');
        $this->newline();

        $results = $service->syncPronomRegistry($puids);

        $this->bold('Sync Complete:');
        $this->line("  Total synced: {$results['synced']}");
        $this->line("  Created:      {$results['created']}");
        $this->line("  Updated:      {$results['updated']}");
        $this->line("  Failed:       {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->newline();
            $this->error("Errors ({$results['failed']}):");
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->line("  {$error}");
            }
            if (count($results['errors']) > 10) {
                $this->line('  ... and ' . (count($results['errors']) - 10) . ' more');
            }
        }
    }
}
