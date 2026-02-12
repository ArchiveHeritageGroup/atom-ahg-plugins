<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class VirusScanCommand extends BaseCommand
{
    protected string $name = 'preservation:virus-scan';
    protected string $description = 'Scan digital objects for viruses using ClamAV';
    protected string $detailedDescription = <<<'EOF'
Scans digital objects for viruses using ClamAV (clamscan or clamdscan).

Examples:
  php bin/atom preservation:virus-scan                    # Scan up to 100 new objects
  php bin/atom preservation:virus-scan --limit=500        # Scan up to 500 objects
  php bin/atom preservation:virus-scan --object-id=123    # Scan specific object
  php bin/atom preservation:virus-scan --status           # Show ClamAV status

ClamAV must be installed:
  sudo apt install clamav clamav-daemon
  sudo freshclam
EOF;

    protected function configure(): void
    {
        $this->addOption('object-id', null, 'Specific digital object ID to scan');
        $this->addOption('limit', 'l', 'Maximum number of objects to scan', '100');
        $this->addOption('new-only', null, 'Only scan objects not previously scanned');
        $this->addOption('quarantine', null, 'Quarantine infected files (default: enabled)');
        $this->addOption('status', 's', 'Show ClamAV status only');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();

        // Status check only
        if ($this->hasOption('status')) {
            $this->showStatus($service);

            return 0;
        }

        // Check ClamAV availability
        if (!$service->isClamAvAvailable()) {
            $this->error('ClamAV is not installed or not accessible');
            $this->line('Install with: sudo apt install clamav clamav-daemon && sudo freshclam');

            return 1;
        }

        $quarantine = $this->hasOption('quarantine') ? true : true; // Default to enabled

        // Single object scan
        if ($this->hasOption('object-id')) {
            $objectId = (int) $this->option('object-id');
            $this->info("Scanning digital object ID: $objectId");

            $result = $service->scanForVirus($objectId, $quarantine, 'cli-task');

            if ('clean' === $result['status']) {
                $this->success('CLEAN - No threats detected');
            } elseif ('infected' === $result['status']) {
                $this->error("INFECTED - {$result['threat_name']}");
                if ($quarantine) {
                    $this->line('File has been quarantined');
                }
            } else {
                $this->warning("Status: {$result['status']} - {$result['message']}");
            }

            return 0;
        }

        // Batch scan
        $limit = (int) $this->option('limit', '100');
        $newOnly = true; // Default to new-only

        $this->info("Starting batch virus scan (limit: $limit, new-only: " . ($newOnly ? 'yes' : 'no') . ')');

        $results = $service->runBatchVirusScan($limit, $newOnly, 'cli-task');

        $this->newline();
        $this->bold('Scan Results:');
        $this->line("  Total scanned: {$results['total']}");
        $this->line("  Clean: {$results['clean']}");
        $this->line("  Infected: {$results['infected']}");
        $this->line("  Errors: {$results['errors']}");
        $this->line("  Skipped: {$results['skipped']}");

        if ($results['infected'] > 0) {
            $this->newline();
            $this->error('WARNING: Infected files detected!');

            foreach ($results['details'] as $detail) {
                if ('infected' === $detail['status']) {
                    $this->error("  Object {$detail['object_id']}: {$detail['threat_name']}");
                }
            }
        }

        return 0;
    }

    private function showStatus(\PreservationService $service): void
    {
        $this->bold('ClamAV Status Check');
        $this->newline();

        if (!$service->isClamAvAvailable()) {
            $this->error('ClamAV: NOT INSTALLED');
            $this->newline();
            $this->info('To install ClamAV:');
            $this->line('  sudo apt install clamav clamav-daemon');
            $this->line('  sudo freshclam');
            $this->line('  sudo systemctl start clamav-daemon');

            return;
        }

        $version = $service->getClamAvVersion();
        $this->success('ClamAV: INSTALLED');
        $this->line("  Version: {$version['version']}");
        $this->line("  Scanner: {$version['scanner']}");
        $this->line("  Database: {$version['database']}");

        // Show scan statistics
        $stats = DB::table('preservation_virus_scan')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->newline();
        $this->info('Scan Statistics:');
        $this->line('  Clean: ' . ($stats['clean'] ?? 0));
        $this->line('  Infected: ' . ($stats['infected'] ?? 0));
        $this->line('  Errors: ' . ($stats['error'] ?? 0));
        $this->line('  Skipped: ' . ($stats['skipped'] ?? 0));

        // Count unscanned objects
        $totalObjects = DB::table('digital_object')
            ->where('usage_id', 140)
            ->count();

        $scannedObjects = DB::table('preservation_virus_scan')
            ->distinct()
            ->count('digital_object_id');

        $unscanned = $totalObjects - $scannedObjects;

        $this->newline();
        $this->line("Objects pending scan: $unscanned");
    }
}
