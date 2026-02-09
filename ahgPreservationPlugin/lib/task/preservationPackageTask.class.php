<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Task to manage OAIS packages (SIP/AIP/DIP).
 *
 * This task provides CLI operations for creating, building, validating,
 * and exporting preservation packages in BagIt format.
 */
class preservationPackageTask extends arBaseTask
{
    protected function configure()
    {
        $this->addArguments([
            new sfCommandArgument('action', sfCommandArgument::REQUIRED, 'Action: list, create, build, validate, export, show, add-objects, convert'),
        ]);

        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Package ID'),
            new sfCommandOption('uuid', null, sfCommandOption::PARAMETER_OPTIONAL, 'Package UUID'),
            new sfCommandOption('type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Package type: sip, aip, dip'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter by status'),
            new sfCommandOption('name', null, sfCommandOption::PARAMETER_OPTIONAL, 'Package name'),
            new sfCommandOption('description', null, sfCommandOption::PARAMETER_OPTIONAL, 'Package description'),
            new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'Export format: zip, tar, tar.gz', 'zip'),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output path'),
            new sfCommandOption('objects', null, sfCommandOption::PARAMETER_OPTIONAL, 'Comma-separated digital object IDs'),
            new sfCommandOption('query', null, sfCommandOption::PARAMETER_OPTIONAL, 'Query to select objects (e.g., "mime_type:image/*")'),
            new sfCommandOption('originator', null, sfCommandOption::PARAMETER_OPTIONAL, 'Organization originator'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Limit for list operations', 20),
        ]);

        $this->namespace = 'preservation';
        $this->name = 'package';
        $this->briefDescription = 'Manage OAIS preservation packages';
        $this->detailedDescription = <<<EOF
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
  # List all packages
  php symfony preservation:package list
  php symfony preservation:package list --type=sip --status=draft

  # Create a new SIP
  php symfony preservation:package create --type=sip --name="My Collection SIP"

  # Add objects to a package
  php symfony preservation:package add-objects --id=1 --objects=100,101,102
  php symfony preservation:package add-objects --id=1 --query="mime_type:application/pdf"

  # Build and validate
  php symfony preservation:package build --id=1
  php symfony preservation:package validate --id=1

  # Export to ZIP
  php symfony preservation:package export --id=1 --format=zip

  # Convert SIP to AIP
  php symfony preservation:package convert --id=1 --type=aip
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
        $action = $arguments['action'];

        switch ($action) {
            case 'list':
                return $this->listPackages($service, $options);
            case 'create':
                return $this->createPackage($service, $options);
            case 'show':
                return $this->showPackage($service, $options);
            case 'add-objects':
                return $this->addObjects($service, $options);
            case 'build':
                return $this->buildPackage($service, $options);
            case 'validate':
                return $this->validatePackage($service, $options);
            case 'export':
                return $this->exportPackage($service, $options);
            case 'convert':
                return $this->convertPackage($service, $options);
            default:
                $this->logSection('package', "Unknown action: {$action}", null, 'ERROR');

                return 1;
        }
    }

    protected function listPackages($service, $options)
    {
        $type = $options['type'] ?? null;
        $status = $options['status'] ?? null;
        $limit = (int) ($options['limit'] ?? 20);

        $packages = $service->getPackages($type, $status, $limit);

        $this->logSection('package', 'OAIS Packages');
        $this->logSection('package', '');

        if (empty($packages)) {
            $this->logSection('package', 'No packages found.');

            return;
        }

        // Statistics
        $stats = $service->getPackageStatistics();
        $this->logSection('package', "Total: {$stats['total_packages']} packages, {$stats['total_objects']} objects, {$stats['total_size_formatted']}");
        $this->logSection('package', '');

        // List packages
        foreach ($packages as $pkg) {
            $statusStyle = $this->getStatusStyle($pkg->status);
            $typeLabel = strtoupper($pkg->package_type);

            $this->logSection('package', "[{$pkg->id}] [{$typeLabel}] {$pkg->name}", null, $statusStyle);
            $this->logSection('package', "     UUID: {$pkg->uuid}");
            $this->logSection('package', "     Status: {$pkg->status} | Objects: {$pkg->object_count} | Size: ".$this->formatBytes($pkg->total_size));

            if ($pkg->created_at) {
                $this->logSection('package', "     Created: {$pkg->created_at}");
            }

            $this->logSection('package', '');
        }
    }

    protected function createPackage($service, $options)
    {
        $type = $options['type'] ?? null;
        $name = $options['name'] ?? null;

        if (!$type) {
            $this->logSection('package', 'Package type is required (--type=sip|aip|dip)', null, 'ERROR');

            return 1;
        }

        if (!in_array($type, ['sip', 'aip', 'dip'])) {
            $this->logSection('package', "Invalid package type: {$type}", null, 'ERROR');

            return 1;
        }

        if (!$name) {
            $name = strtoupper($type).' - '.date('Y-m-d H:i:s');
        }

        try {
            $packageId = $service->createPackage([
                'name' => $name,
                'description' => $options['description'] ?? null,
                'package_type' => $type,
                'originator' => $options['originator'] ?? null,
            ]);

            $package = $service->getPackage($packageId);

            $this->logSection('package', 'Package created successfully!', null, 'INFO');
            $this->logSection('package', "  ID: {$packageId}");
            $this->logSection('package', "  UUID: {$package->uuid}");
            $this->logSection('package', "  Type: ".strtoupper($type));
            $this->logSection('package', "  Name: {$name}");
            $this->logSection('package', '');
            $this->logSection('package', 'Next steps:');
            $this->logSection('package', "  1. Add objects: php symfony preservation:package add-objects --id={$packageId} --objects=ID1,ID2");
            $this->logSection('package', "  2. Build: php symfony preservation:package build --id={$packageId}");
            $this->logSection('package', "  3. Validate: php symfony preservation:package validate --id={$packageId}");
        } catch (Exception $e) {
            $this->logSection('package', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }

    protected function showPackage($service, $options)
    {
        $package = $this->getPackageFromOptions($service, $options);
        if (!$package) {
            return 1;
        }

        $this->logSection('package', 'Package Details');
        $this->logSection('package', '');
        $this->logSection('package', "ID: {$package->id}");
        $this->logSection('package', "UUID: {$package->uuid}");
        $this->logSection('package', "Name: {$package->name}");
        $this->logSection('package', "Type: ".strtoupper($package->package_type));
        $this->logSection('package', "Status: {$package->status}");
        $this->logSection('package', "Format: {$package->package_format}");
        $this->logSection('package', "Algorithm: {$package->manifest_algorithm}");
        $this->logSection('package', "Objects: {$package->object_count}");
        $this->logSection('package', 'Size: '.$this->formatBytes($package->total_size));

        if ($package->description) {
            $this->logSection('package', "Description: {$package->description}");
        }

        if ($package->originator) {
            $this->logSection('package', "Originator: {$package->originator}");
        }

        if ($package->source_path) {
            $this->logSection('package', "Source Path: {$package->source_path}");
        }

        if ($package->export_path) {
            $this->logSection('package', "Export Path: {$package->export_path}");
        }

        if ($package->package_checksum) {
            $this->logSection('package', "Checksum: {$package->package_checksum}");
        }

        $this->logSection('package', '');
        $this->logSection('package', 'Timeline:');
        $this->logSection('package', "  Created: {$package->created_at}");

        if ($package->built_at) {
            $this->logSection('package', "  Built: {$package->built_at}");
        }

        if ($package->validated_at) {
            $this->logSection('package', "  Validated: {$package->validated_at}");
        }

        if ($package->exported_at) {
            $this->logSection('package', "  Exported: {$package->exported_at}");
        }

        // Show objects
        $objects = $service->getPackageObjects($package->id);
        if (!empty($objects)) {
            $this->logSection('package', '');
            $this->logSection('package', 'Objects ('.count($objects).'):');

            foreach (array_slice($objects, 0, 10) as $obj) {
                $title = $obj->information_object_title ?? $obj->file_name;
                $checkStatus = $obj->checksum_value ? 'checksum' : 'no checksum';
                $this->logSection('package', "  [{$obj->digital_object_id}] {$title} ({$checkStatus})");
            }

            if (count($objects) > 10) {
                $this->logSection('package', '  ... and '.(count($objects) - 10).' more');
            }
        }

        // Show recent events
        $events = $service->getPackageEvents($package->id, 5);
        if (!empty($events)) {
            $this->logSection('package', '');
            $this->logSection('package', 'Recent Events:');

            foreach ($events as $event) {
                $outcomeStyle = 'success' === $event->event_outcome ? 'INFO' : ('failure' === $event->event_outcome ? 'ERROR' : 'COMMENT');
                $this->logSection('package', "  [{$event->event_datetime}] {$event->event_type}: {$event->event_detail}", null, $outcomeStyle);
            }
        }
    }

    protected function addObjects($service, $options)
    {
        $package = $this->getPackageFromOptions($service, $options);
        if (!$package) {
            return 1;
        }

        if ('draft' !== $package->status) {
            $this->logSection('package', 'Can only add objects to draft packages', null, 'ERROR');

            return 1;
        }

        $objectIds = [];

        // From comma-separated list
        if (!empty($options['objects'])) {
            $objectIds = array_map('intval', explode(',', $options['objects']));
        }

        // From query
        if (!empty($options['query'])) {
            $queryObjects = $this->queryDigitalObjects($options['query'], $options['limit'] ?? 100);
            $objectIds = array_merge($objectIds, $queryObjects);
        }

        if (empty($objectIds)) {
            $this->logSection('package', 'No objects specified. Use --objects=ID1,ID2 or --query="mime_type:..."', null, 'ERROR');

            return 1;
        }

        $objectIds = array_unique($objectIds);
        $added = 0;
        $skipped = 0;
        $errors = 0;

        $this->logSection('package', "Adding ".count($objectIds)." objects to package {$package->id}...");

        foreach ($objectIds as $objId) {
            try {
                $service->addObjectToPackage($package->id, $objId);
                ++$added;
            } catch (Exception $e) {
                if (false !== strpos($e->getMessage(), 'not found')) {
                    ++$errors;
                    $this->logSection('package', "  Object {$objId}: not found", null, 'ERROR');
                } else {
                    ++$skipped;
                }
            }
        }

        $this->logSection('package', '');
        $this->logSection('package', "Added: {$added}, Skipped (duplicate): {$skipped}, Errors: {$errors}");

        // Show updated count
        $updatedPackage = $service->getPackage($package->id);
        $this->logSection('package', "Package now contains {$updatedPackage->object_count} objects (".$this->formatBytes($updatedPackage->total_size).')');
    }

    protected function buildPackage($service, $options)
    {
        $package = $this->getPackageFromOptions($service, $options);
        if (!$package) {
            return 1;
        }

        $this->logSection('package', "Building BagIt package: {$package->name}...");

        try {
            $result = $service->buildBagItPackage($package->id, $options['output'] ?? null);

            if ($result['success']) {
                $this->logSection('package', 'Build completed successfully!', null, 'INFO');
                $this->logSection('package', "  Path: {$result['path']}");
                $this->logSection('package', "  Files: {$result['files']}");
                $this->logSection('package', '  Size: '.$this->formatBytes($result['size']));
                $this->logSection('package', "  Checksum: {$result['checksum']}");
                $this->logSection('package', '');
                $this->logSection('package', "Next: php symfony preservation:package validate --id={$package->id}");
            } else {
                $this->logSection('package', "Build failed: {$result['error']}", null, 'ERROR');

                return 1;
            }
        } catch (Exception $e) {
            $this->logSection('package', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }

    protected function validatePackage($service, $options)
    {
        $package = $this->getPackageFromOptions($service, $options);
        if (!$package) {
            return 1;
        }

        $this->logSection('package', "Validating package: {$package->name}...");

        try {
            $result = $service->validateBagItPackage($package->id);

            if ($result['valid']) {
                $this->logSection('package', 'Validation PASSED!', null, 'INFO');
                $this->logSection('package', "  Files verified: {$result['validated_files']}");
                $this->logSection('package', '');
                $this->logSection('package', "Next: php symfony preservation:package export --id={$package->id} --format=zip");
            } else {
                $this->logSection('package', 'Validation FAILED!', null, 'ERROR');
                $this->logSection('package', "  Validated: {$result['validated_files']}");
                $this->logSection('package', "  Failed: {$result['failed_files']}");
                $this->logSection('package', '');
                $this->logSection('package', 'Errors:');

                foreach ($result['errors'] as $error) {
                    $this->logSection('package', "  - {$error}", null, 'ERROR');
                }

                return 1;
            }
        } catch (Exception $e) {
            $this->logSection('package', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }

    protected function exportPackage($service, $options)
    {
        $package = $this->getPackageFromOptions($service, $options);
        if (!$package) {
            return 1;
        }

        $format = $options['format'] ?? 'zip';

        $this->logSection('package', "Exporting package to {$format}: {$package->name}...");

        try {
            $result = $service->exportPackage($package->id, $format, $options['output'] ?? null);

            if ($result['success']) {
                $this->logSection('package', 'Export completed successfully!', null, 'INFO');
                $this->logSection('package', "  Path: {$result['path']}");
                $this->logSection('package', "  Format: {$result['format']}");
                $this->logSection('package', '  Size: '.$this->formatBytes($result['size']));
                $this->logSection('package', "  Checksum: {$result['checksum']}");
            } else {
                $this->logSection('package', "Export failed: {$result['error']}", null, 'ERROR');

                return 1;
            }
        } catch (Exception $e) {
            $this->logSection('package', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }

    protected function convertPackage($service, $options)
    {
        $package = $this->getPackageFromOptions($service, $options);
        if (!$package) {
            return 1;
        }

        $targetType = $options['type'] ?? null;

        if (!$targetType) {
            $this->logSection('package', 'Target type required (--type=aip or --type=dip)', null, 'ERROR');

            return 1;
        }

        try {
            if ('aip' === $targetType && 'sip' === $package->package_type) {
                $this->logSection('package', "Converting SIP to AIP: {$package->name}...");
                $newId = $service->convertSipToAip($package->id, [
                    'name' => $options['name'] ?? null,
                    'description' => $options['description'] ?? null,
                ]);
            } elseif ('dip' === $targetType && 'aip' === $package->package_type) {
                $this->logSection('package', "Creating DIP from AIP: {$package->name}...");
                $newId = $service->createDipFromAip($package->id, [
                    'name' => $options['name'] ?? null,
                    'description' => $options['description'] ?? null,
                ]);
            } else {
                $this->logSection('package', "Invalid conversion: {$package->package_type} -> {$targetType}", null, 'ERROR');
                $this->logSection('package', 'Valid conversions: SIP -> AIP, AIP -> DIP');

                return 1;
            }

            $newPackage = $service->getPackage($newId);
            $this->logSection('package', 'Conversion completed!', null, 'INFO');
            $this->logSection('package', "  New Package ID: {$newId}");
            $this->logSection('package', "  UUID: {$newPackage->uuid}");
            $this->logSection('package', "  Type: ".strtoupper($newPackage->package_type));
            $this->logSection('package', "  Objects: {$newPackage->object_count}");
            $this->logSection('package', '');
            $this->logSection('package', "Next: php symfony preservation:package build --id={$newId}");
        } catch (Exception $e) {
            $this->logSection('package', "Error: {$e->getMessage()}", null, 'ERROR');

            return 1;
        }
    }

    protected function getPackageFromOptions($service, $options)
    {
        if (!empty($options['id'])) {
            $package = $service->getPackage((int) $options['id']);
        } elseif (!empty($options['uuid'])) {
            $package = $service->getPackageByUuid($options['uuid']);
        } else {
            $this->logSection('package', 'Package ID or UUID required (--id=N or --uuid=...)', null, 'ERROR');

            return null;
        }

        if (!$package) {
            $this->logSection('package', 'Package not found', null, 'ERROR');

            return null;
        }

        return $package;
    }

    protected function queryDigitalObjects($query, $limit)
    {
        // Parse query like "mime_type:image/*" or "repository:1"
        $parts = explode(':', $query, 2);
        if (2 !== count($parts)) {
            return [];
        }

        [$field, $value] = $parts;
        $ids = [];

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

        foreach ($results as $row) {
            $ids[] = $row->id;
        }

        return $ids;
    }

    protected function getStatusStyle($status)
    {
        switch ($status) {
            case 'draft':
                return 'COMMENT';
            case 'building':
                return 'COMMENT';
            case 'complete':
                return 'INFO';
            case 'validated':
                return 'INFO';
            case 'exported':
                return 'INFO';
            case 'error':
                return 'ERROR';
            default:
                return null;
        }
    }

    protected function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }
}
