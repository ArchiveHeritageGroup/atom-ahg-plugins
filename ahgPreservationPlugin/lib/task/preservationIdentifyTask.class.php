<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to identify file formats using Siegfried (PRONOM-based identification).
 */
class preservationIdentifyTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('object-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Specific digital object ID to identify'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Maximum number of objects to identify', 100),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be identified without identifying'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show Siegfried status and statistics'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Identify all objects (including already identified)'),
            new sfCommandOption('reidentify', null, sfCommandOption::PARAMETER_NONE, 'Force re-identification of already identified objects'),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'identify';
        $this->briefDescription = 'Identify file formats using Siegfried (PRONOM)';
        $this->detailedDescription = <<<EOF
Identifies file formats for digital objects using Siegfried, which provides
PRONOM-based format identification compatible with DROID.

Examples:
  php symfony preservation:identify --status                    # Show Siegfried status
  php symfony preservation:identify --dry-run                   # Preview identification
  php symfony preservation:identify --object-id=123             # Identify specific object
  php symfony preservation:identify --limit=500                 # Identify up to 500 objects
  php symfony preservation:identify --all --limit=1000          # Re-identify all objects
  php symfony preservation:identify --reidentify --object-id=123 # Force re-identify

Identification results include:
  - PRONOM Unique Identifier (PUID)
  - Format name and version
  - MIME type
  - Confidence level (certain, high, medium, low)
  - Identification basis (signature, extension, container)
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $bootstrap = sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        require_once dirname(__DIR__).'/PreservationService.php';

        $service = new PreservationService();

        // Status check
        if ($options['status']) {
            $this->showStatus($service);

            return;
        }

        $dryRun = !empty($options['dry-run']);
        $limit = (int) ($options['limit'] ?? 100);
        $identifyAll = !empty($options['all']);
        $reidentify = !empty($options['reidentify']);

        // Single object identification
        if (!empty($options['object-id'])) {
            $objectId = (int) $options['object-id'];

            $this->logSection('identify', "Identifying digital object ID: $objectId");

            if ($dryRun) {
                $this->logSection('identify', '[DRY RUN] Would identify object', null, 'COMMENT');

                return;
            }

            try {
                if ($reidentify) {
                    $result = $service->reidentifyFormat($objectId);
                } else {
                    $result = $service->identifyFormat($objectId);
                }

                if ($result['success']) {
                    $this->logSection('identify', 'SUCCESS - Format identified', null, 'INFO');
                    $this->logSection('identify', "  PUID: {$result['puid']}");
                    $this->logSection('identify', "  Format: {$result['format_name']}");
                    if ($result['format_version']) {
                        $this->logSection('identify', "  Version: {$result['format_version']}");
                    }
                    $this->logSection('identify', "  MIME: {$result['mime_type']}");
                    $this->logSection('identify', "  Confidence: {$result['confidence']}");
                    $this->logSection('identify', "  Basis: {$result['basis']}");
                    if ($result['warning']) {
                        $this->logSection('identify', "  Warning: {$result['warning']}", null, 'COMMENT');
                    }
                    $this->logSection('identify', "  Duration: {$result['duration_ms']}ms");
                } else {
                    $this->logSection('identify', "FAILED - {$result['error']}", null, 'ERROR');
                }
            } catch (Exception $e) {
                $this->logSection('identify', "ERROR - {$e->getMessage()}", null, 'ERROR');

                return 1;
            }

            return;
        }

        // Batch identification
        $this->logSection('identify', 'Searching for objects to identify...');

        $query = DB::table('digital_object as do')
            ->where('do.usage_id', 140) // Masters only
            ->select('do.id', 'do.name', 'do.mime_type');

        if (!$identifyAll) {
            $query->leftJoin('preservation_object_format as pof', 'do.id', '=', 'pof.digital_object_id')
                ->whereNull('pof.id');
        }

        $objects = $query->limit($limit)->get();

        if ($objects->isEmpty()) {
            $this->logSection('identify', 'No objects found requiring identification');

            return;
        }

        $this->logSection('identify', "Found {$objects->count()} objects to identify".($dryRun ? ' [DRY RUN]' : ''));
        $this->logSection('identify', '');

        $success = 0;
        $failed = 0;

        foreach ($objects as $obj) {
            $this->logSection('identify', "Object {$obj->id}: {$obj->name}");
            $this->logSection('identify', "  Current MIME: {$obj->mime_type}");

            if ($dryRun) {
                $this->logSection('identify', '  [WOULD IDENTIFY]', null, 'COMMENT');

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
                    $this->logSection('identify', "  IDENTIFIED: {$puid} - {$formatName} [{$confidence}]", null, 'INFO');
                    ++$success;
                } else {
                    $this->logSection('identify', "  FAILED: {$result['error']}", null, 'ERROR');
                    ++$failed;
                }
            } catch (Exception $e) {
                $this->logSection('identify', "  ERROR: {$e->getMessage()}", null, 'ERROR');
                ++$failed;
            }
        }

        if (!$dryRun) {
            $this->logSection('identify', '');
            $this->logSection('identify', "Identification complete: $success succeeded, $failed failed");
        }
    }

    protected function showStatus($service)
    {
        $this->logSection('identify', 'Format Identification Status (Siegfried)');
        $this->logSection('identify', '');

        // Check Siegfried availability
        if ($service->isSiegfriedAvailable()) {
            $version = $service->getSiegfriedVersion();
            $this->logSection('identify', 'Siegfried: AVAILABLE', null, 'INFO');
            $this->logSection('identify', "  Version: {$version['version']}");
            if ($version['signature_date']) {
                $this->logSection('identify', "  Signature Date: {$version['signature_date']}");
            }
        } else {
            $this->logSection('identify', 'Siegfried: NOT INSTALLED', null, 'ERROR');
            $this->logSection('identify', '  Install with: curl -sL "https://github.com/richardlehane/siegfried/releases/download/v1.11.1/siegfried_1.11.1-1_amd64.deb" -o /tmp/sf.deb && sudo dpkg -i /tmp/sf.deb');

            return;
        }

        // Get statistics
        $stats = $service->getIdentificationStatistics();

        $this->logSection('identify', '');
        $this->logSection('identify', 'Identification Statistics:');
        $this->logSection('identify', "  Total Master Objects: {$stats['total_objects']}");
        $this->logSection('identify', "  Identified: {$stats['identified']}");
        $this->logSection('identify', "  Unidentified: {$stats['unidentified']}");
        $this->logSection('identify', "  Coverage: {$stats['coverage_percent']}%");
        $this->logSection('identify', "  With Warnings: {$stats['with_warnings']}");

        if (!empty($stats['by_confidence'])) {
            $this->logSection('identify', '');
            $this->logSection('identify', 'By Confidence:');
            foreach ($stats['by_confidence'] as $confidence => $count) {
                $this->logSection('identify', "  {$confidence}: {$count}");
            }
        }

        if (!empty($stats['top_formats'])) {
            $this->logSection('identify', '');
            $this->logSection('identify', 'Top Formats:');
            foreach ($stats['top_formats'] as $format) {
                $puid = $format->puid ?? 'N/A';
                $this->logSection('identify', "  {$format->format_name} ({$puid}): {$format->count}");
            }
        }

        // Show format registry stats
        $registryStats = DB::table('preservation_format')
            ->selectRaw('risk_level, COUNT(*) as count')
            ->groupBy('risk_level')
            ->pluck('count', 'risk_level')
            ->toArray();

        $this->logSection('identify', '');
        $this->logSection('identify', 'Format Registry:');
        $this->logSection('identify', '  Low Risk: '.($registryStats['low'] ?? 0));
        $this->logSection('identify', '  Medium Risk: '.($registryStats['medium'] ?? 0));
        $this->logSection('identify', '  High Risk: '.($registryStats['high'] ?? 0));
        $this->logSection('identify', '  Critical Risk: '.($registryStats['critical'] ?? 0));
    }
}
