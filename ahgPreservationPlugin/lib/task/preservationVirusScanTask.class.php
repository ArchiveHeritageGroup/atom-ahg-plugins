<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to perform virus scanning on digital objects using ClamAV.
 */
class preservationVirusScanTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific digital object ID to scan'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number of objects to scan', 100),
            new sfCommandOption('new-only', null, sfCommandOption::PARAMETER_NONE, 'Only scan objects not previously scanned'),
            new sfCommandOption('quarantine', null, sfCommandOption::PARAMETER_NONE, 'Quarantine infected files (default: enabled)'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show ClamAV status only'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'virus-scan';
        $this->briefDescription = 'Scan digital objects for viruses using ClamAV';
        $this->detailedDescription = <<<EOF
Scans digital objects for viruses using ClamAV (clamscan or clamdscan).

Examples:
  php symfony preservation:virus-scan                    # Scan up to 100 new objects
  php symfony preservation:virus-scan --limit=500        # Scan up to 500 objects
  php symfony preservation:virus-scan --object-id=123    # Scan specific object
  php symfony preservation:virus-scan --status           # Show ClamAV status

ClamAV must be installed:
  sudo apt install clamav clamav-daemon
  sudo freshclam
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        require_once sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        require_once dirname(__DIR__).'/PreservationService.php';

        $service = new PreservationService();

        // Status check only
        if ($options['status']) {
            $this->showStatus($service);

            return;
        }

        // Check ClamAV availability
        if (!$service->isClamAvAvailable()) {
            $this->logSection('virus-scan', 'ClamAV is not installed or not accessible', null, 'ERROR');
            $this->logSection('virus-scan', 'Install with: sudo apt install clamav clamav-daemon && sudo freshclam');

            return 1;
        }

        $quarantine = isset($options['quarantine']) ? (bool) $options['quarantine'] : true;

        // Single object scan
        if (!empty($options['object-id'])) {
            $objectId = (int) $options['object-id'];
            $this->logSection('virus-scan', "Scanning digital object ID: $objectId");

            $result = $service->scanForVirus($objectId, $quarantine, 'cli-task');

            if ('clean' === $result['status']) {
                $this->logSection('virus-scan', 'CLEAN - No threats detected', null, 'INFO');
            } elseif ('infected' === $result['status']) {
                $this->logSection('virus-scan', "INFECTED - {$result['threat_name']}", null, 'ERROR');
                if ($quarantine) {
                    $this->logSection('virus-scan', 'File has been quarantined');
                }
            } else {
                $this->logSection('virus-scan', "Status: {$result['status']} - {$result['message']}", null, 'COMMENT');
            }

            return;
        }

        // Batch scan
        $limit = (int) ($options['limit'] ?? 100);
        $newOnly = isset($options['new-only']) ? true : true; // Default to new-only

        $this->logSection('virus-scan', "Starting batch virus scan (limit: $limit, new-only: ".($newOnly ? 'yes' : 'no').')');

        $results = $service->runBatchVirusScan($limit, $newOnly, 'cli-task');

        $this->logSection('virus-scan', '');
        $this->logSection('virus-scan', 'Scan Results:');
        $this->logSection('virus-scan', "  Total scanned: {$results['total']}");
        $this->logSection('virus-scan', "  Clean: {$results['clean']}");
        $this->logSection('virus-scan', "  Infected: {$results['infected']}");
        $this->logSection('virus-scan', "  Errors: {$results['errors']}");
        $this->logSection('virus-scan', "  Skipped: {$results['skipped']}");

        if ($results['infected'] > 0) {
            $this->logSection('virus-scan', '');
            $this->logSection('virus-scan', 'WARNING: Infected files detected!', null, 'ERROR');

            foreach ($results['details'] as $detail) {
                if ('infected' === $detail['status']) {
                    $this->logSection('virus-scan', "  Object {$detail['object_id']}: {$detail['threat_name']}", null, 'ERROR');
                }
            }
        }
    }

    protected function showStatus($service)
    {
        $this->logSection('virus-scan', 'ClamAV Status Check');
        $this->logSection('virus-scan', '');

        if (!$service->isClamAvAvailable()) {
            $this->logSection('virus-scan', 'ClamAV: NOT INSTALLED', null, 'ERROR');
            $this->logSection('virus-scan', '');
            $this->logSection('virus-scan', 'To install ClamAV:');
            $this->logSection('virus-scan', '  sudo apt install clamav clamav-daemon');
            $this->logSection('virus-scan', '  sudo freshclam');
            $this->logSection('virus-scan', '  sudo systemctl start clamav-daemon');

            return;
        }

        $version = $service->getClamAvVersion();
        $this->logSection('virus-scan', 'ClamAV: INSTALLED', null, 'INFO');
        $this->logSection('virus-scan', "  Version: {$version['version']}");
        $this->logSection('virus-scan', "  Scanner: {$version['scanner']}");
        $this->logSection('virus-scan', "  Database: {$version['database']}");

        // Show scan statistics
        $stats = DB::table('preservation_virus_scan')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->logSection('virus-scan', '');
        $this->logSection('virus-scan', 'Scan Statistics:');
        $this->logSection('virus-scan', '  Clean: '.($stats['clean'] ?? 0));
        $this->logSection('virus-scan', '  Infected: '.($stats['infected'] ?? 0));
        $this->logSection('virus-scan', '  Errors: '.($stats['error'] ?? 0));
        $this->logSection('virus-scan', '  Skipped: '.($stats['skipped'] ?? 0));

        // Count unscanned objects
        $totalObjects = DB::table('digital_object')
            ->where('usage_id', 140)
            ->count();

        $scannedObjects = DB::table('preservation_virus_scan')
            ->distinct()
            ->count('digital_object_id');

        $unscanned = $totalObjects - $scannedObjects;

        $this->logSection('virus-scan', '');
        $this->logSection('virus-scan', "Objects pending scan: $unscanned");
    }
}
