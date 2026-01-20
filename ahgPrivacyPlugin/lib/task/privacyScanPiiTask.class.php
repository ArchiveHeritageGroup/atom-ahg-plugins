<?php

/**
 * PII Scan CLI Task
 *
 * Usage:
 *   php symfony privacy:scan-pii                    # Scan all unscanned objects
 *   php symfony privacy:scan-pii --id=123           # Scan specific object
 *   php symfony privacy:scan-pii --repository=5    # Scan by repository
 *   php symfony privacy:scan-pii --limit=50        # Limit batch size
 *   php symfony privacy:scan-pii --rescan          # Re-scan already scanned
 *   php symfony privacy:scan-pii --stats           # Show statistics only
 *
 * @author Johan Pieterse - The Archive and Heritage Group
 */
class privacyScanPiiTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Scan specific object ID'),
            new sfCommandOption('repository', null, sfCommandOption::PARAMETER_OPTIONAL, 'Scan by repository ID'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Batch limit', 100),
            new sfCommandOption('rescan', null, sfCommandOption::PARAMETER_NONE, 'Re-scan already scanned objects'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show statistics only'),
            new sfCommandOption('verbose', 'v', sfCommandOption::PARAMETER_NONE, 'Verbose output'),
        ]);

        $this->namespace = 'privacy';
        $this->name = 'scan-pii';
        $this->briefDescription = 'Scan archival descriptions for PII (Personally Identifiable Information)';
        $this->detailedDescription = <<<EOF
The [privacy:scan-pii|INFO] task scans information objects for PII including:
  - Names (via NER)
  - ID numbers (SA ID, Nigerian NIN, Passport)
  - Email addresses
  - Phone numbers
  - Financial data (bank accounts, tax numbers)

Results are stored in the NER entity table and linked to the privacy data inventory.

Examples:
  [php symfony privacy:scan-pii|INFO]                  Scan all unscanned objects
  [php symfony privacy:scan-pii --id=123|INFO]         Scan specific object
  [php symfony privacy:scan-pii --stats|INFO]          Show statistics
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $this->logSection('pii-scan', 'PII Detection Scanner');
        $this->log('');

        // Load service
        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PiiDetectionService.php';
        $service = new \ahgPrivacyPlugin\Service\PiiDetectionService();

        // Stats only
        if ($options['stats']) {
            $this->showStatistics($service);
            return 0;
        }

        // Single object scan
        if (!empty($options['id'])) {
            return $this->scanSingleObject($service, (int)$options['id'], !empty($options['verbose']));
        }

        // Batch scan
        return $this->batchScan($service, $options);
    }

    protected function scanSingleObject($service, int $objectId, bool $verbose): int
    {
        $this->logSection('pii-scan', "Scanning object ID: {$objectId}");

        $result = $service->scanObject($objectId);

        if ($result['summary']['total'] === 0) {
            $this->logSection('pii-scan', 'No PII detected', null, 'INFO');
            return 0;
        }

        $this->logSection('pii-scan', "Found {$result['summary']['total']} PII entities", null, 'COMMENT');

        // Show summary
        $this->log('');
        $this->log("  Risk Score: {$result['risk_score']}/100");
        $this->log("  High Risk:  {$result['summary']['high_risk']}");
        $this->log("  Medium:     {$result['summary']['medium_risk']}");
        $this->log("  Low:        {$result['summary']['low_risk']}");
        $this->log('');

        if ($verbose) {
            $this->log('  Entities:');
            foreach ($result['entities'] as $entity) {
                $risk = strtoupper($entity['risk_level']);
                $this->log("    [{$risk}] {$entity['type']}: {$entity['value']} (field: {$entity['field']})");
            }
            $this->log('');
        }

        // Save results
        $extractionId = $service->saveScanResults($objectId, $result);
        $this->logSection('pii-scan', "Saved extraction ID: {$extractionId}", null, 'INFO');

        if ($result['summary']['high_risk'] > 0) {
            $this->logSection('pii-scan', 'HIGH RISK PII DETECTED - Review required', null, 'ERROR');
        }

        return 0;
    }

    protected function batchScan($service, array $options): int
    {
        $filters = [
            'rescan' => !empty($options['rescan']),
        ];

        if (!empty($options['repository'])) {
            $filters['repository_id'] = (int)$options['repository'];
        }

        $limit = (int)($options['limit'] ?? 100);
        $verbose = !empty($options['verbose']);

        $this->logSection('pii-scan', "Starting batch scan (limit: {$limit})");
        $this->log('');

        $result = $service->batchScan($filters, $limit);

        // Show results
        $this->logSection('pii-scan', "Scanned: {$result['scanned']} objects");
        $this->logSection('pii-scan', "With PII: {$result['with_pii']} objects");
        $this->logSection('pii-scan', "High Risk: {$result['high_risk']} objects");
        $this->log('');

        if ($verbose && !empty($result['objects'])) {
            $this->log('  Top objects by risk score:');
            $this->log('  ' . str_repeat('-', 70));

            $count = 0;
            foreach ($result['objects'] as $obj) {
                if ($count++ >= 20) break;
                $title = substr($obj['title'] ?? 'Untitled', 0, 40);
                $this->log(sprintf(
                    "  ID: %-6d | Risk: %-3d | PII: %-3d | %s",
                    $obj['id'],
                    $obj['risk_score'],
                    $obj['pii_count'],
                    $title
                ));
            }
            $this->log('');
        }

        if ($result['high_risk'] > 0) {
            $this->logSection('pii-scan', "{$result['high_risk']} objects have HIGH RISK PII - Review required", null, 'ERROR');
        }

        return 0;
    }

    protected function showStatistics($service): void
    {
        $stats = $service->getStatistics();

        $this->log('');
        $this->log('  ╔════════════════════════════════════════════════════════╗');
        $this->log('  ║              PII Detection Statistics                  ║');
        $this->log('  ╚════════════════════════════════════════════════════════╝');
        $this->log('');
        $this->log(sprintf('  Objects Scanned:      %d', $stats['total_scanned']));
        $this->log(sprintf('  Objects with PII:     %d', $stats['with_pii']));
        $this->log(sprintf('  High-Risk Entities:   %d', $stats['high_risk_entities']));
        $this->log(sprintf('  Pending Review:       %d', $stats['pending_review']));
        $this->log(sprintf('  Coverage:             %.1f%%', $stats['coverage_percent']));
        $this->log('');

        if (!empty($stats['by_type'])) {
            $this->log('  Entities by Type:');
            $this->log('  ' . str_repeat('-', 40));
            foreach ($stats['by_type'] as $type => $count) {
                $this->log(sprintf('    %-20s %d', $type, $count));
            }
            $this->log('');
        }
    }
}
