<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to sync format registry from UK National Archives PRONOM database.
 */
class preservationPronomSyncTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('puid', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific PUID to sync (e.g., fmt/18)'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Sync all PUIDs found in identified objects'),
            new sfCommandOption('unregistered', null, sfCommandOption::PARAMETER_NONE, 'Sync only unregistered PUIDs'),
            new sfCommandOption('common', null, sfCommandOption::PARAMETER_NONE, 'Sync common archival format PUIDs'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show PRONOM sync status'),
            new sfCommandOption('lookup', null, sfCommandOption::PARAMETER_OPTIONAL, 'Look up a PUID without syncing'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'pronom-sync';
        $this->briefDescription = 'Sync format registry from PRONOM (UK National Archives)';
        $this->detailedDescription = <<<EOF
Synchronizes format information from the UK National Archives PRONOM registry.
This provides authoritative format identification data and preservation risk assessment.

Examples:
  php symfony preservation:pronom-sync --status          # Show sync status
  php symfony preservation:pronom-sync --puid=fmt/18     # Sync specific PUID
  php symfony preservation:pronom-sync --unregistered    # Sync unregistered PUIDs
  php symfony preservation:pronom-sync --common          # Sync common archival formats
  php symfony preservation:pronom-sync --all             # Sync all known PUIDs
  php symfony preservation:pronom-sync --lookup=fmt/43   # Look up PUID info

PRONOM provides:
  - Official format names and versions
  - MIME types and file extensions
  - Binary signatures for identification
  - Format risk information
  - Preservation recommendations

More info: https://www.nationalarchives.gov.uk/pronom/
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once dirname(__DIR__).'/PreservationService.php';

        $service = new PreservationService();

        // Status check
        if ($options['status']) {
            $this->showStatus($service);

            return;
        }

        // Lookup without sync
        if (!empty($options['lookup'])) {
            $this->lookupPuid($service, $options['lookup']);

            return;
        }

        // Specific PUID sync
        if (!empty($options['puid'])) {
            $this->syncSinglePuid($service, $options['puid']);

            return;
        }

        // Common formats sync
        if ($options['common']) {
            $this->syncCommonFormats($service);

            return;
        }

        // Unregistered PUIDs sync
        if ($options['unregistered']) {
            $this->syncUnregistered($service);

            return;
        }

        // Sync all known PUIDs
        if ($options['all']) {
            $this->syncAll($service);

            return;
        }

        // Default: show status and help
        $this->showStatus($service);
        $this->logSection('pronom', '');
        $this->logSection('pronom', 'Use --help to see available options');
    }

    /**
     * Show PRONOM sync status
     */
    protected function showStatus(PreservationService $service)
    {
        $this->logSection('pronom', 'PRONOM Registry Sync Status');
        $this->logSection('pronom', '');

        $status = $service->getPronomSyncStatus();

        $this->logSection('pronom', 'Format Registry:');
        $this->logSection('pronom', "  Total formats:        {$status['registered_formats']}");
        $this->logSection('pronom', "  With PUID:            {$status['formats_with_puid']}");
        $this->logSection('pronom', "  Without PUID:         {$status['formats_without_puid']}");

        $this->logSection('pronom', '');
        $this->logSection('pronom', 'Object Identification:');
        $this->logSection('pronom', "  Unique PUIDs found:   {$status['unique_object_puids']}");
        $this->logSection('pronom', "  Unregistered PUIDs:   {$status['unregistered_count']}");

        if (!empty($status['unregistered_puids'])) {
            $preview = array_slice($status['unregistered_puids'], 0, 5);
            $this->logSection('pronom', '    '.implode(', ', $preview).(count($status['unregistered_puids']) > 5 ? '...' : ''));
        }

        $this->logSection('pronom', '');
        $this->logSection('pronom', 'Format Risk Distribution:');
        foreach (['low', 'medium', 'high', 'critical'] as $level) {
            $count = $status['risk_distribution'][$level] ?? 0;
            $color = in_array($level, ['high', 'critical']) ? 'ERROR' : 'INFO';
            $this->logSection('pronom', sprintf('  %-10s: %d', ucfirst($level), $count), null, $color);
        }

        if ($status['last_sync']) {
            $this->logSection('pronom', '');
            $this->logSection('pronom', 'Last Sync:');
            $this->logSection('pronom', "  Date:    {$status['last_sync']['datetime']}");
            $this->logSection('pronom', "  Outcome: {$status['last_sync']['outcome']}");
        }
    }

    /**
     * Look up a PUID from PRONOM
     */
    protected function lookupPuid(PreservationService $service, string $puid)
    {
        $this->logSection('pronom', "Looking up PUID: {$puid}");
        $this->logSection('pronom', '');

        // First check local registry
        $local = $service->getFormatByPuid($puid);

        if ($local) {
            $this->logSection('pronom', 'Local Registry Entry:');
            $this->logSection('pronom', "  Name:        {$local['name']}");
            $this->logSection('pronom', "  Version:     ".($local['version'] ?? '-'));
            $this->logSection('pronom', "  MIME Type:   {$local['mime_type']}");
            $this->logSection('pronom', "  Extension:   ".($local['extension'] ?? '-'));
            $this->logSection('pronom', "  Risk Level:  {$local['risk_level']}");
            $this->logSection('pronom', "  Objects:     {$local['object_count']}");
            $this->logSection('pronom', '');
        }

        // Fetch from PRONOM
        $this->logSection('pronom', 'Fetching from PRONOM...');

        $formatData = $service->fetchPronomFormat($puid);

        if (!$formatData) {
            $this->logSection('pronom', 'Failed to fetch from PRONOM', null, 'ERROR');
            $this->logSection('pronom', "URL: https://www.nationalarchives.gov.uk/pronom/{$puid}");

            return;
        }

        $this->logSection('pronom', '');
        $this->logSection('pronom', 'PRONOM Data:');
        $this->logSection('pronom', "  Name:        {$formatData['name']}");
        $this->logSection('pronom', "  Version:     ".($formatData['version'] ?? '-'));
        $this->logSection('pronom', "  MIME Type:   ".($formatData['mime_type'] ?? '-'));
        $this->logSection('pronom', "  Extension:   ".($formatData['extension'] ?? '-'));
        $this->logSection('pronom', "  Has Sig:     ".($formatData['binary_signature'] ? 'Yes' : 'No'));

        if ($formatData['description']) {
            $this->logSection('pronom', '');
            $this->logSection('pronom', 'Description:');
            // Word wrap the description
            $wrapped = wordwrap($formatData['description'], 60, "\n");
            foreach (explode("\n", $wrapped) as $line) {
                $this->logSection('pronom', "  {$line}");
            }
        }

        $this->logSection('pronom', '');
        $this->logSection('pronom', "PRONOM URL: https://www.nationalarchives.gov.uk/pronom/{$puid}");
    }

    /**
     * Sync a single PUID
     */
    protected function syncSinglePuid(PreservationService $service, string $puid)
    {
        $this->logSection('pronom', "Syncing PUID: {$puid}");

        $results = $service->syncPronomRegistry([$puid]);

        if ($results['synced'] > 0) {
            if ($results['created'] > 0) {
                $this->logSection('pronom', 'Format created in registry', null, 'INFO');
            } else {
                $this->logSection('pronom', 'Format updated in registry', null, 'INFO');
            }
        }

        if ($results['failed'] > 0) {
            $this->logSection('pronom', 'Sync failed', null, 'ERROR');
            foreach ($results['errors'] as $error) {
                $this->logSection('pronom', "  {$error}");
            }
        }
    }

    /**
     * Sync common archival formats
     */
    protected function syncCommonFormats(PreservationService $service)
    {
        $this->logSection('pronom', 'Syncing common archival formats from PRONOM...');
        $this->logSection('pronom', '');

        $results = $service->syncCommonFormats();

        $this->logSection('pronom', 'Sync Complete:');
        $this->logSection('pronom', "  Total synced: {$results['synced']}");
        $this->logSection('pronom', "  Created:      {$results['created']}");
        $this->logSection('pronom', "  Updated:      {$results['updated']}");
        $this->logSection('pronom', "  Failed:       {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->logSection('pronom', '');
            $this->logSection('pronom', 'Errors:', null, 'ERROR');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->logSection('pronom', "  {$error}");
            }
        }
    }

    /**
     * Sync unregistered PUIDs
     */
    protected function syncUnregistered(PreservationService $service)
    {
        $this->logSection('pronom', 'Syncing unregistered PUIDs from PRONOM...');

        $status = $service->getPronomSyncStatus();

        if (empty($status['unregistered_puids'])) {
            $this->logSection('pronom', 'All PUIDs are already registered!', null, 'INFO');

            return;
        }

        $this->logSection('pronom', "Found {$status['unregistered_count']} unregistered PUIDs");
        $this->logSection('pronom', '');

        $results = $service->syncAllUnregisteredPuids();

        $this->logSection('pronom', 'Sync Complete:');
        $this->logSection('pronom', "  Total synced: {$results['synced']}");
        $this->logSection('pronom', "  Created:      {$results['created']}");
        $this->logSection('pronom', "  Updated:      {$results['updated']}");
        $this->logSection('pronom', "  Failed:       {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->logSection('pronom', '');
            $this->logSection('pronom', 'Errors:', null, 'ERROR');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->logSection('pronom', "  {$error}");
            }
        }
    }

    /**
     * Sync all known PUIDs
     */
    protected function syncAll(PreservationService $service)
    {
        $this->logSection('pronom', 'Syncing all known PUIDs from PRONOM...');
        $this->logSection('pronom', '');

        // Get all unique PUIDs
        $puids = DB::table('preservation_object_format')
            ->whereNotNull('puid')
            ->distinct()
            ->pluck('puid')
            ->toArray();

        if (empty($puids)) {
            $this->logSection('pronom', 'No PUIDs found to sync', null, 'ERROR');
            $this->logSection('pronom', 'Run format identification first: preservation:identify');

            return;
        }

        $this->logSection('pronom', "Found ".count($puids)." unique PUIDs to sync");
        $this->logSection('pronom', '');

        $results = $service->syncPronomRegistry($puids);

        $this->logSection('pronom', 'Sync Complete:');
        $this->logSection('pronom', "  Total synced: {$results['synced']}");
        $this->logSection('pronom', "  Created:      {$results['created']}");
        $this->logSection('pronom', "  Updated:      {$results['updated']}");
        $this->logSection('pronom', "  Failed:       {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->logSection('pronom', '');
            $this->logSection('pronom', "Errors ({$results['failed']}):", null, 'ERROR');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->logSection('pronom', "  {$error}");
            }
            if (count($results['errors']) > 10) {
                $this->logSection('pronom', '  ... and '.(count($results['errors']) - 10).' more');
            }
        }
    }
}
