<?php

namespace AtomFramework\Console\Commands\Privacy;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Scan archival descriptions for PII (Personally Identifiable Information).
 */
class ScanPiiCommand extends BaseCommand
{
    protected string $name = 'privacy:scan-pii';
    protected string $description = 'Scan archival descriptions for PII (Personally Identifiable Information)';
    protected string $detailedDescription = <<<'EOF'
    Scan information objects for PII including:
      - Names (via NER)
      - ID numbers (SA ID, Nigerian NIN, Passport)
      - Email addresses
      - Phone numbers
      - Financial data (bank accounts, tax numbers)

    Results are stored in the NER entity table and linked to the privacy data inventory.

    Examples:
      php bin/atom privacy:scan-pii                    Scan all unscanned objects
      php bin/atom privacy:scan-pii --id=123           Scan specific object
      php bin/atom privacy:scan-pii --repository=5    Scan by repository
      php bin/atom privacy:scan-pii --limit=50        Limit batch size
      php bin/atom privacy:scan-pii --rescan          Re-scan already scanned
      php bin/atom privacy:scan-pii --stats           Show statistics only
    EOF;

    protected function configure(): void
    {
        $this->addOption('id', null, 'Scan specific object ID');
        $this->addOption('repository', null, 'Scan by repository ID');
        $this->addOption('limit', 'l', 'Batch limit', '100');
        $this->addOption('rescan', null, 'Re-scan already scanned objects');
        $this->addOption('stats', null, 'Show statistics only');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgPrivacyPlugin/lib/Service/PiiDetectionService.php';
        if (!file_exists($serviceFile)) {
            $this->error("PiiDetectionService not found at: {$serviceFile}");

            return 1;
        }

        require_once $serviceFile;

        $service = new \ahgPrivacyPlugin\Service\PiiDetectionService();

        $this->bold('  PII Detection Scanner');
        $this->newline();

        // Stats only
        if ($this->hasOption('stats')) {
            $this->showStatistics($service);

            return 0;
        }

        // Single object scan
        if ($this->hasOption('id')) {
            return $this->scanSingleObject($service, (int) $this->option('id'));
        }

        // Batch scan
        return $this->batchScan($service);
    }

    protected function scanSingleObject($service, int $objectId): int
    {
        $this->info("Scanning object ID: {$objectId}");

        $result = $service->scanObject($objectId);

        if ($result['summary']['total'] === 0) {
            $this->success('No PII detected');

            return 0;
        }

        $this->warning("Found {$result['summary']['total']} PII entities");

        $this->newline();
        $this->line("  Risk Score: {$result['risk_score']}/100");
        $this->line("  High Risk:  {$result['summary']['high_risk']}");
        $this->line("  Medium:     {$result['summary']['medium_risk']}");
        $this->line("  Low:        {$result['summary']['low_risk']}");
        $this->newline();

        if ($this->verbose) {
            $this->line('  Entities:');
            foreach ($result['entities'] as $entity) {
                $risk = strtoupper($entity['risk_level']);
                $this->line("    [{$risk}] {$entity['type']}: {$entity['value']} (field: {$entity['field']})");
            }
            $this->newline();
        }

        // Save results
        $extractionId = $service->saveScanResults($objectId, $result);
        $this->success("Saved extraction ID: {$extractionId}");

        if ($result['summary']['high_risk'] > 0) {
            $this->error('HIGH RISK PII DETECTED - Review required');
        }

        return 0;
    }

    protected function batchScan($service): int
    {
        $filters = [
            'rescan' => $this->hasOption('rescan'),
        ];

        if ($this->hasOption('repository')) {
            $filters['repository_id'] = (int) $this->option('repository');
        }

        $limit = (int) $this->option('limit', '100');

        $this->info("Starting batch scan (limit: {$limit})");
        $this->newline();

        $result = $service->batchScan($filters, $limit);

        // Show results
        $this->line("  Scanned: {$result['scanned']} objects");
        $this->line("  With PII: {$result['with_pii']} objects");
        $this->line("  High Risk: {$result['high_risk']} objects");
        $this->newline();

        if ($this->verbose && !empty($result['objects'])) {
            $this->line('  Top objects by risk score:');
            $this->line('  ' . str_repeat('-', 70));

            $count = 0;
            foreach ($result['objects'] as $obj) {
                if ($count++ >= 20) {
                    break;
                }
                $title = substr($obj['title'] ?? 'Untitled', 0, 40);
                $this->line(sprintf(
                    "  ID: %-6d | Risk: %-3d | PII: %-3d | %s",
                    $obj['id'],
                    $obj['risk_score'],
                    $obj['pii_count'],
                    $title
                ));
            }
            $this->newline();
        }

        if ($result['high_risk'] > 0) {
            $this->error("{$result['high_risk']} objects have HIGH RISK PII - Review required");
        }

        return 0;
    }

    protected function showStatistics($service): void
    {
        $stats = $service->getStatistics();

        $this->newline();
        $this->bold('  PII Detection Statistics');
        $this->line('  ' . str_repeat('=', 50));
        $this->newline();
        $this->line(sprintf('  Objects Scanned:      %d', $stats['total_scanned']));
        $this->line(sprintf('  Objects with PII:     %d', $stats['with_pii']));
        $this->line(sprintf('  High-Risk Entities:   %d', $stats['high_risk_entities']));
        $this->line(sprintf('  Pending Review:       %d', $stats['pending_review']));
        $this->line(sprintf('  Coverage:             %.1f%%', $stats['coverage_percent']));
        $this->newline();

        if (!empty($stats['by_type'])) {
            $this->info('Entities by Type:');
            $this->line('  ' . str_repeat('-', 40));
            foreach ($stats['by_type'] as $type => $count) {
                $this->line(sprintf('    %-20s %d', $type, $count));
            }
            $this->newline();
        }
    }
}
