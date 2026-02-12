<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class IdentifyCommand extends BaseCommand
{
    protected string $name = 'preservation:identify';
    protected string $description = 'Identify file formats using Siegfried (PRONOM)';
    protected string $detailedDescription = <<<'EOF'
Identifies file formats for digital objects using Siegfried, which provides
PRONOM-based format identification compatible with DROID.

Examples:
  php bin/atom preservation:identify --status                    # Show Siegfried status
  php bin/atom preservation:identify --dry-run                   # Preview identification
  php bin/atom preservation:identify --object-id=123             # Identify specific object
  php bin/atom preservation:identify --limit=500                 # Identify up to 500 objects
  php bin/atom preservation:identify --all --limit=1000          # Re-identify all objects
  php bin/atom preservation:identify --reidentify --object-id=123 # Force re-identify

Identification results include:
  - PRONOM Unique Identifier (PUID)
  - Format name and version
  - MIME type
  - Confidence level (certain, high, medium, low)
  - Identification basis (signature, extension, container)
EOF;

    protected function configure(): void
    {
        $this->addOption('object-id', null, 'Specific digital object ID to identify');
        $this->addOption('limit', 'l', 'Maximum number of objects to identify', '100');
        $this->addOption('dry-run', null, 'Show what would be identified without identifying');
        $this->addOption('status', 's', 'Show Siegfried status and statistics');
        $this->addOption('all', 'a', 'Identify all objects (including already identified)');
        $this->addOption('reidentify', null, 'Force re-identification of already identified objects');
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

        $dryRun = $this->hasOption('dry-run');
        $limit = (int) $this->option('limit', '100');
        $identifyAll = $this->hasOption('all');
        $reidentify = $this->hasOption('reidentify');

        // Single object identification
        if ($this->hasOption('object-id')) {
            $objectId = (int) $this->option('object-id');

            $this->info("Identifying digital object ID: $objectId");

            if ($dryRun) {
                $this->comment('[DRY RUN] Would identify object');

                return 0;
            }

            try {
                if ($reidentify) {
                    $result = $service->reidentifyFormat($objectId);
                } else {
                    $result = $service->identifyFormat($objectId);
                }

                if ($result['success']) {
                    $this->success('Format identified');
                    $this->line("  PUID: {$result['puid']}");
                    $this->line("  Format: {$result['format_name']}");
                    if ($result['format_version']) {
                        $this->line("  Version: {$result['format_version']}");
                    }
                    $this->line("  MIME: {$result['mime_type']}");
                    $this->line("  Confidence: {$result['confidence']}");
                    $this->line("  Basis: {$result['basis']}");
                    if ($result['warning']) {
                        $this->warning("  Warning: {$result['warning']}");
                    }
                    $this->line("  Duration: {$result['duration_ms']}ms");
                } else {
                    $this->error("FAILED - {$result['error']}");
                }
            } catch (\Exception $e) {
                $this->error("ERROR - {$e->getMessage()}");

                return 1;
            }

            return 0;
        }

        // Batch identification
        $this->info('Searching for objects to identify...');

        $query = DB::table('digital_object as do')
            ->where('do.usage_id', 140) // Masters only
            ->select('do.id', 'do.name', 'do.mime_type');

        if (!$identifyAll) {
            $query->leftJoin('preservation_object_format as pof', 'do.id', '=', 'pof.digital_object_id')
                ->whereNull('pof.id');
        }

        $objects = $query->limit($limit)->get();

        if ($objects->isEmpty()) {
            $this->info('No objects found requiring identification');

            return 0;
        }

        $this->info("Found {$objects->count()} objects to identify" . ($dryRun ? ' [DRY RUN]' : ''));
        $this->newline();

        $success = 0;
        $failed = 0;

        foreach ($objects as $obj) {
            $this->line("Object {$obj->id}: {$obj->name}");
            $this->line("  Current MIME: {$obj->mime_type}");

            if ($dryRun) {
                $this->comment('  [WOULD IDENTIFY]');

                continue;
            }

            try {
                if ($reidentify || $identifyAll) {
                    $result = $service->reidentifyFormat($obj->id);
                } else {
                    $result = $service->identifyFormat($obj->id);
                }

                if ($result['success']) {
                    $puid = $result['puid'] ?? 'UNKNOWN';
                    $formatName = $result['format_name'] ?? 'Unknown';
                    $confidence = $result['confidence'] ?? 'medium';
                    $this->success("  IDENTIFIED: {$puid} - {$formatName} [{$confidence}]");
                    ++$success;
                } else {
                    $this->error("  FAILED: {$result['error']}");
                    ++$failed;
                }
            } catch (\Exception $e) {
                $this->error("  ERROR: {$e->getMessage()}");
                ++$failed;
            }
        }

        if (!$dryRun) {
            $this->newline();
            $this->bold("Identification complete: $success succeeded, $failed failed");
        }

        return 0;
    }

    private function showStatus(\PreservationService $service): void
    {
        $this->bold('Format Identification Status (Siegfried)');
        $this->newline();

        // Check Siegfried availability
        if ($service->isSiegfriedAvailable()) {
            $version = $service->getSiegfriedVersion();
            $this->success('Siegfried: AVAILABLE');
            $this->line("  Version: {$version['version']}");
            if ($version['signature_date']) {
                $this->line("  Signature Date: {$version['signature_date']}");
            }
        } else {
            $this->error('Siegfried: NOT INSTALLED');
            $this->line('  Install with: curl -sL "https://github.com/richardlehane/siegfried/releases/download/v1.11.1/siegfried_1.11.1-1_amd64.deb" -o /tmp/sf.deb && sudo dpkg -i /tmp/sf.deb');

            return;
        }

        // Get statistics
        $stats = $service->getIdentificationStatistics();

        $this->newline();
        $this->info('Identification Statistics:');
        $this->line("  Total Master Objects: {$stats['total_objects']}");
        $this->line("  Identified: {$stats['identified']}");
        $this->line("  Unidentified: {$stats['unidentified']}");
        $this->line("  Coverage: {$stats['coverage_percent']}%");
        $this->line("  With Warnings: {$stats['with_warnings']}");

        if (!empty($stats['by_confidence'])) {
            $this->newline();
            $this->info('By Confidence:');
            foreach ($stats['by_confidence'] as $confidence => $count) {
                $this->line("  {$confidence}: {$count}");
            }
        }

        if (!empty($stats['top_formats'])) {
            $this->newline();
            $this->info('Top Formats:');
            foreach ($stats['top_formats'] as $format) {
                $puid = $format->puid ?? 'N/A';
                $this->line("  {$format->format_name} ({$puid}): {$format->count}");
            }
        }

        // Show format registry stats
        $registryStats = DB::table('preservation_format')
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        $this->newline();
        $this->info('Format Registry:');
        $this->line('  Low Risk: ' . ($registryStats['low'] ?? 0));
        $this->line('  Medium Risk: ' . ($registryStats['medium'] ?? 0));
        $this->line('  High Risk: ' . ($registryStats['high'] ?? 0));
        $this->line('  Critical Risk: ' . ($registryStats['critical'] ?? 0));
    }
}
