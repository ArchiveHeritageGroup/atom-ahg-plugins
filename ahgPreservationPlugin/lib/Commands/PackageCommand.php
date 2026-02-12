<?php

namespace AtomFramework\Console\Commands\Preservation;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class PackageCommand extends BaseCommand
{
    protected string $name = 'preservation:package';
    protected string $description = 'Manage OAIS preservation packages';
    protected string $detailedDescription = <<<'EOF'
Manage OAIS-compliant preservation packages (SIP, AIP, DIP) using BagIt format.

Actions:
  list              List all packages
  create            Create a new package
  show              Show package details
  add-objects       Add digital objects to a package
  build             Build the BagIt package
  validate          Validate a built package
  export            Export package to archive format
  convert           Convert SIP to AIP or AIP to DIP

Examples:
  php bin/atom preservation:package list
  php bin/atom preservation:package list --type=sip --status=draft
  php bin/atom preservation:package create --type=sip --name="My Collection SIP"
  php bin/atom preservation:package add-objects --id=1 --objects=100,101,102
  php bin/atom preservation:package add-objects --id=1 --query="mime_type:application/pdf"
  php bin/atom preservation:package build --id=1
  php bin/atom preservation:package validate --id=1
  php bin/atom preservation:package export --id=1 --format=zip
  php bin/atom preservation:package convert --id=1 --type=aip
EOF;

    protected function configure(): void
    {
        $this->addArgument('action', 'Action: list, create, build, validate, export, show, add-objects, convert', true);
        $this->addOption('id', null, 'Package ID');
        $this->addOption('uuid', null, 'Package UUID');
        $this->addOption('type', null, 'Package type: sip, aip, dip');
        $this->addOption('status', null, 'Filter by status');
        $this->addOption('name', null, 'Package name');
        $this->addOption('description', null, 'Package description');
        $this->addOption('format', null, 'Export format: zip, tar, tar.gz', 'zip');
        $this->addOption('output', null, 'Output path');
        $this->addOption('objects', null, 'Comma-separated digital object IDs');
        $this->addOption('query', null, 'Query to select objects (e.g., "mime_type:image/*")');
        $this->addOption('originator', null, 'Organization originator');
        $this->addOption('limit', 'l', 'Limit for list operations', '20');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/PreservationService.php';

        $service = new \PreservationService();
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listPackages($service);
            case 'create':
                return $this->createPackage($service);
            case 'show':
                return $this->showPackage($service);
            case 'add-objects':
                return $this->addObjects($service);
            case 'build':
                return $this->buildPackage($service);
            case 'validate':
                return $this->validatePackage($service);
            case 'export':
                return $this->exportPackage($service);
            case 'convert':
                return $this->convertPackage($service);
            default:
                $this->error("Unknown action: {$action}");

                return 1;
        }
    }

    private function listPackages(\PreservationService $service): int
    {
        $type = $this->hasOption('type') ? $this->option('type') : null;
        $status = $this->hasOption('status') ? $this->option('status') : null;
        $limit = (int) $this->option('limit', '20');

        $packages = $service->getPackages($type, $status, $limit);

        $this->bold('OAIS Packages');
        $this->newline();

        if (empty($packages)) {
            $this->info('No packages found.');

            return 0;
        }

        // Statistics
        $stats = $service->getPackageStatistics();
        $this->info("Total: {$stats['total_packages']} packages, {$stats['total_objects']} objects, {$stats['total_size_formatted']}");
        $this->newline();

        // List packages
        foreach ($packages as $pkg) {
            $typeLabel = strtoupper($pkg->package_type);
            $statusLabel = $pkg->status;

            if (in_array($statusLabel, ['complete', 'validated', 'exported'])) {
                $this->success("[{$pkg->id}] [{$typeLabel}] {$pkg->name}");
            } elseif ('error' === $statusLabel) {
                $this->error("[{$pkg->id}] [{$typeLabel}] {$pkg->name}");
            } else {
                $this->comment("[{$pkg->id}] [{$typeLabel}] {$pkg->name}");
            }

            $this->line("     UUID: {$pkg->uuid}");
            $this->line("     Status: {$pkg->status} | Objects: {$pkg->object_count} | Size: " . $this->formatBytes($pkg->total_size));

            if ($pkg->created_at) {
                $this->line("     Created: {$pkg->created_at}");
            }

            $this->newline();
        }

        return 0;
    }

    private function createPackage(\PreservationService $service): int
    {
        $type = $this->hasOption('type') ? $this->option('type') : null;
        $name = $this->hasOption('name') ? $this->option('name') : null;

        if (!$type) {
            $this->error('Package type is required (--type=sip|aip|dip)');

            return 1;
        }

        if (!in_array($type, ['sip', 'aip', 'dip'])) {
            $this->error("Invalid package type: {$type}");

            return 1;
        }

        if (!$name) {
            $name = strtoupper($type) . ' - ' . date('Y-m-d H:i:s');
        }

        try {
            $packageId = $service->createPackage([
                'name' => $name,
                'description' => $this->hasOption('description') ? $this->option('description') : null,
                'package_type' => $type,
                'originator' => $this->hasOption('originator') ? $this->option('originator') : null,
            ]);

            $package = $service->getPackage($packageId);

            $this->success('Package created successfully!');
            $this->line("  ID: {$packageId}");
            $this->line("  UUID: {$package->uuid}");
            $this->line("  Type: " . strtoupper($type));
            $this->line("  Name: {$name}");
            $this->newline();
            $this->info('Next steps:');
            $this->line("  1. Add objects: php bin/atom preservation:package add-objects --id={$packageId} --objects=ID1,ID2");
            $this->line("  2. Build: php bin/atom preservation:package build --id={$packageId}");
            $this->line("  3. Validate: php bin/atom preservation:package validate --id={$packageId}");
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function showPackage(\PreservationService $service): int
    {
        $package = $this->getPackageFromOptions($service);
        if (!$package) {
            return 1;
        }

        $this->bold('Package Details');
        $this->newline();
        $this->line("ID: {$package->id}");
        $this->line("UUID: {$package->uuid}");
        $this->line("Name: {$package->name}");
        $this->line("Type: " . strtoupper($package->package_type));
        $this->line("Status: {$package->status}");
        $this->line("Format: {$package->package_format}");
        $this->line("Algorithm: {$package->manifest_algorithm}");
        $this->line("Objects: {$package->object_count}");
        $this->line('Size: ' . $this->formatBytes($package->total_size));

        if ($package->description) {
            $this->line("Description: {$package->description}");
        }

        if ($package->originator) {
            $this->line("Originator: {$package->originator}");
        }

        if ($package->source_path) {
            $this->line("Source Path: {$package->source_path}");
        }

        if ($package->export_path) {
            $this->line("Export Path: {$package->export_path}");
        }

        if ($package->package_checksum) {
            $this->line("Checksum: {$package->package_checksum}");
        }

        $this->newline();
        $this->info('Timeline:');
        $this->line("  Created: {$package->created_at}");

        if ($package->built_at) {
            $this->line("  Built: {$package->built_at}");
        }

        if ($package->validated_at) {
            $this->line("  Validated: {$package->validated_at}");
        }

        if ($package->exported_at) {
            $this->line("  Exported: {$package->exported_at}");
        }

        // Show objects
        $objects = $service->getPackageObjects($package->id);
        if (!empty($objects)) {
            $this->newline();
            $this->info('Objects (' . count($objects) . '):');

            foreach (array_slice($objects, 0, 10) as $obj) {
                $title = $obj->information_object_title ?? $obj->file_name;
                $checkStatus = $obj->checksum_value ? 'checksum' : 'no checksum';
                $this->line("  [{$obj->digital_object_id}] {$title} ({$checkStatus})");
            }

            if (count($objects) > 10) {
                $this->line('  ... and ' . (count($objects) - 10) . ' more');
            }
        }

        // Show recent events
        $events = $service->getPackageEvents($package->id, 5);
        if (!empty($events)) {
            $this->newline();
            $this->info('Recent Events:');

            foreach ($events as $event) {
                if ('success' === $event->event_outcome) {
                    $this->success("  [{$event->event_datetime}] {$event->event_type}: {$event->event_detail}");
                } elseif ('failure' === $event->event_outcome) {
                    $this->error("  [{$event->event_datetime}] {$event->event_type}: {$event->event_detail}");
                } else {
                    $this->comment("  [{$event->event_datetime}] {$event->event_type}: {$event->event_detail}");
                }
            }
        }

        return 0;
    }

    private function addObjects(\PreservationService $service): int
    {
        $package = $this->getPackageFromOptions($service);
        if (!$package) {
            return 1;
        }

        if ('draft' !== $package->status) {
            $this->error('Can only add objects to draft packages');

            return 1;
        }

        $objectIds = [];

        // From comma-separated list
        if ($this->hasOption('objects')) {
            $objectIds = array_map('intval', explode(',', $this->option('objects')));
        }

        // From query
        if ($this->hasOption('query')) {
            $queryLimit = (int) $this->option('limit', '100');
            $queryObjects = $this->queryDigitalObjects($this->option('query'), $queryLimit);
            $objectIds = array_merge($objectIds, $queryObjects);
        }

        if (empty($objectIds)) {
            $this->error('No objects specified. Use --objects=ID1,ID2 or --query="mime_type:..."');

            return 1;
        }

        $objectIds = array_unique($objectIds);
        $added = 0;
        $skipped = 0;
        $errors = 0;

        $this->info('Adding ' . count($objectIds) . " objects to package {$package->id}...");

        foreach ($objectIds as $objId) {
            try {
                $service->addObjectToPackage($package->id, $objId);
                ++$added;
            } catch (\Exception $e) {
                if (false !== strpos($e->getMessage(), 'not found')) {
                    ++$errors;
                    $this->error("  Object {$objId}: not found");
                } else {
                    ++$skipped;
                }
            }
        }

        $this->newline();
        $this->line("Added: {$added}, Skipped (duplicate): {$skipped}, Errors: {$errors}");

        // Show updated count
        $updatedPackage = $service->getPackage($package->id);
        $this->info("Package now contains {$updatedPackage->object_count} objects (" . $this->formatBytes($updatedPackage->total_size) . ')');

        return 0;
    }

    private function buildPackage(\PreservationService $service): int
    {
        $package = $this->getPackageFromOptions($service);
        if (!$package) {
            return 1;
        }

        $this->info("Building BagIt package: {$package->name}...");

        try {
            $outputPath = $this->hasOption('output') ? $this->option('output') : null;
            $result = $service->buildBagItPackage($package->id, $outputPath);

            if ($result['success']) {
                $this->success('Build completed successfully!');
                $this->line("  Path: {$result['path']}");
                $this->line("  Files: {$result['files']}");
                $this->line('  Size: ' . $this->formatBytes($result['size']));
                $this->line("  Checksum: {$result['checksum']}");
                $this->newline();
                $this->info("Next: php bin/atom preservation:package validate --id={$package->id}");
            } else {
                $this->error("Build failed: {$result['error']}");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function validatePackage(\PreservationService $service): int
    {
        $package = $this->getPackageFromOptions($service);
        if (!$package) {
            return 1;
        }

        $this->info("Validating package: {$package->name}...");

        try {
            $result = $service->validateBagItPackage($package->id);

            if ($result['valid']) {
                $this->success('Validation PASSED!');
                $this->line("  Files verified: {$result['validated_files']}");
                $this->newline();
                $this->info("Next: php bin/atom preservation:package export --id={$package->id} --format=zip");
            } else {
                $this->error('Validation FAILED!');
                $this->line("  Validated: {$result['validated_files']}");
                $this->line("  Failed: {$result['failed_files']}");
                $this->newline();
                $this->error('Errors:');

                foreach ($result['errors'] as $err) {
                    $this->error("  - {$err}");
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function exportPackage(\PreservationService $service): int
    {
        $package = $this->getPackageFromOptions($service);
        if (!$package) {
            return 1;
        }

        $format = $this->option('format', 'zip');

        $this->info("Exporting package to {$format}: {$package->name}...");

        try {
            $outputPath = $this->hasOption('output') ? $this->option('output') : null;
            $result = $service->exportPackage($package->id, $format, $outputPath);

            if ($result['success']) {
                $this->success('Export completed successfully!');
                $this->line("  Path: {$result['path']}");
                $this->line("  Format: {$result['format']}");
                $this->line('  Size: ' . $this->formatBytes($result['size']));
                $this->line("  Checksum: {$result['checksum']}");
            } else {
                $this->error("Export failed: {$result['error']}");

                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function convertPackage(\PreservationService $service): int
    {
        $package = $this->getPackageFromOptions($service);
        if (!$package) {
            return 1;
        }

        $targetType = $this->hasOption('type') ? $this->option('type') : null;

        if (!$targetType) {
            $this->error('Target type required (--type=aip or --type=dip)');

            return 1;
        }

        try {
            if ('aip' === $targetType && 'sip' === $package->package_type) {
                $this->info("Converting SIP to AIP: {$package->name}...");
                $newId = $service->convertSipToAip($package->id, [
                    'name' => $this->hasOption('name') ? $this->option('name') : null,
                    'description' => $this->hasOption('description') ? $this->option('description') : null,
                ]);
            } elseif ('dip' === $targetType && 'aip' === $package->package_type) {
                $this->info("Creating DIP from AIP: {$package->name}...");
                $newId = $service->createDipFromAip($package->id, [
                    'name' => $this->hasOption('name') ? $this->option('name') : null,
                    'description' => $this->hasOption('description') ? $this->option('description') : null,
                ]);
            } else {
                $this->error("Invalid conversion: {$package->package_type} -> {$targetType}");
                $this->line('Valid conversions: SIP -> AIP, AIP -> DIP');

                return 1;
            }

            $newPackage = $service->getPackage($newId);
            $this->success('Conversion completed!');
            $this->line("  New Package ID: {$newId}");
            $this->line("  UUID: {$newPackage->uuid}");
            $this->line("  Type: " . strtoupper($newPackage->package_type));
            $this->line("  Objects: {$newPackage->object_count}");
            $this->newline();
            $this->info("Next: php bin/atom preservation:package build --id={$newId}");
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }

    private function getPackageFromOptions(\PreservationService $service): ?object
    {
        if ($this->hasOption('id')) {
            $package = $service->getPackage((int) $this->option('id'));
        } elseif ($this->hasOption('uuid')) {
            $package = $service->getPackageByUuid($this->option('uuid'));
        } else {
            $this->error('Package ID or UUID required (--id=N or --uuid=...)');

            return null;
        }

        if (!$package) {
            $this->error('Package not found');

            return null;
        }

        return $package;
    }

    private function queryDigitalObjects(string $query, int $limit): array
    {
        // Parse query like "mime_type:image/*" or "repository:1"
        $parts = explode(':', $query, 2);
        if (2 !== count($parts)) {
            return [];
        }

        [$field, $value] = $parts;

        $queryBuilder = DB::table('digital_object');

        switch ($field) {
            case 'mime_type':
                $value = str_replace('*', '%', $value);
                $queryBuilder->where('mime_type', 'LIKE', $value);
                break;
            case 'repository':
                $queryBuilder->join('information_object', 'digital_object.object_id', '=', 'information_object.id')
                    ->where('information_object.repository_id', (int) $value);
                break;
            case 'fonds':
            case 'parent':
                $queryBuilder->join('information_object', 'digital_object.object_id', '=', 'information_object.id')
                    ->where('information_object.parent_id', (int) $value);
                break;
            default:
                return [];
        }

        $results = $queryBuilder->select('digital_object.id')->limit($limit)->get();

        $ids = [];
        foreach ($results as $row) {
            $ids[] = $row->id;
        }

        return $ids;
    }

    private function formatBytes($bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' bytes';
    }
}
