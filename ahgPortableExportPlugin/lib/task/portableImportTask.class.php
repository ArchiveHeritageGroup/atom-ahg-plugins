<?php

/**
 * CLI command for importing archive packages.
 *
 * Usage:
 *   php symfony portable:import --zip=/path/to/archive.zip
 *   php symfony portable:import --zip=/path/to/archive.zip --mode=dry_run
 *   php symfony portable:import --zip=/path/to/archive.zip --mode=merge
 *   php symfony portable:import --zip=/path/to/archive.zip --mode=replace
 *   php symfony portable:import --import-id=42
 */
class portableImportTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('zip', null, sfCommandOption::PARAMETER_OPTIONAL, 'Path to archive ZIP file'),
            new sfCommandOption('path', null, sfCommandOption::PARAMETER_OPTIONAL, 'Path to extracted archive directory'),
            new sfCommandOption('mode', null, sfCommandOption::PARAMETER_OPTIONAL, 'Import mode: merge, replace, or dry_run', 'merge'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture/language code', 'en'),
            new sfCommandOption('import-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process existing import job by ID'),
            new sfCommandOption('entity-types', null, sfCommandOption::PARAMETER_OPTIONAL, 'Comma-separated list of entity types to import (default: all)'),
        ]);

        $this->namespace = 'portable';
        $this->name = 'import';
        $this->briefDescription = 'Import an AtoM Heratio archive package';
        $this->detailedDescription = <<<'EOF'
The [portable:import|INFO] task imports an archive package (ZIP or directory)
produced by the portable:export command in archive mode.

  [php symfony portable:import --zip=/path/to/archive.zip|INFO]
  [php symfony portable:import --zip=/path/to/archive.zip --mode=dry_run|INFO]
  [php symfony portable:import --zip=/path/to/archive.zip --mode=merge|INFO]
  [php symfony portable:import --zip=/path/to/archive.zip --mode=replace|INFO]
  [php symfony portable:import --path=/path/to/extracted-archive|INFO]
  [php symfony portable:import --import-id=42|INFO]

Import modes:
  merge    - Skip existing records, import only new ones (default)
  replace  - Clear target tables before import (dangerous)
  dry_run  - Validate and report without writing to database
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        // Load services
        $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
        require_once $ahgDir . '/lib/Services/ArchiveImporter.php';
        require_once $ahgDir . '/lib/Services/ManifestBuilder.php';

        $DB = \Illuminate\Database\Capsule\Manager::class;

        // If import-id provided, run existing job
        if (!empty($options['import-id'])) {
            return $this->runExistingImport((int) $options['import-id']);
        }

        // Resolve archive directory
        $archiveDir = null;
        $tempDir = null;

        if (!empty($options['zip'])) {
            $zipPath = $options['zip'];
            if (!file_exists($zipPath)) {
                $this->logSection('import', "ZIP file not found: {$zipPath}", null, 'ERROR');

                return 1;
            }

            // Extract ZIP to temp directory
            $tempDir = sys_get_temp_dir() . '/portable-import-' . uniqid();
            @mkdir($tempDir, 0755, true);

            $this->logSection('import', "Extracting {$zipPath}...");
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                $this->logSection('import', 'Failed to open ZIP file', null, 'ERROR');

                return 1;
            }
            $zip->extractTo($tempDir);
            $zip->close();

            $archiveDir = $tempDir;

            // Check if extraction created a subdirectory
            $items = @scandir($tempDir);
            $subdirs = array_filter($items ?: [], function ($item) use ($tempDir) {
                return $item !== '.' && $item !== '..' && is_dir($tempDir . '/' . $item);
            });
            if (count($subdirs) === 1 && !file_exists($tempDir . '/manifest.json')) {
                $archiveDir = $tempDir . '/' . reset($subdirs);
            }
        } elseif (!empty($options['path'])) {
            $archiveDir = $options['path'];
            if (!is_dir($archiveDir)) {
                $this->logSection('import', "Directory not found: {$archiveDir}", null, 'ERROR');

                return 1;
            }
        } else {
            $this->logSection('import', 'Please provide --zip or --path parameter', null, 'ERROR');

            return 1;
        }

        $mode = $options['mode'] ?? 'merge';
        $culture = $options['culture'] ?? 'en';

        // Validate
        $this->logSection('import', 'Validating archive...');
        $importer = new \AhgPortableExportPlugin\Services\ArchiveImporter($culture, function ($current, $total) {
            $pct = $total > 0 ? round(($current / $total) * 100) : 0;
            $this->logSection('import', "Progress: {$pct}% ({$current}/{$total})");
        });

        $validation = $importer->validate($archiveDir);

        if (!$validation['valid']) {
            $this->logSection('import', 'Validation FAILED:', null, 'ERROR');
            foreach ($validation['errors'] as $err) {
                $this->logSection('import', "  - {$err}", null, 'ERROR');
            }

            if ($tempDir) {
                $this->recursiveDelete($tempDir);
            }

            return 1;
        }

        $this->logSection('import', 'Validation passed.');
        $manifest = $validation['manifest'];

        // Display summary
        $this->logSection('import', 'Archive summary:');
        $this->logSection('import', '  Source: ' . ($manifest['source']['url'] ?? 'unknown'));
        $this->logSection('import', '  Framework: ' . ($manifest['source']['framework'] ?? 'unknown'));
        $this->logSection('import', '  Culture: ' . ($manifest['culture'] ?? 'en'));
        $this->logSection('import', '  Mode: ' . $mode);

        if (!empty($validation['entity_counts'])) {
            $this->logSection('import', '  Entity counts:');
            foreach ($validation['entity_counts'] as $type => $count) {
                $this->logSection('import', "    {$type}: {$count}");
            }
        }

        // Parse entity types filter
        $entityTypes = null;
        if (!empty($options['entity-types'])) {
            $entityTypes = array_map('trim', explode(',', $options['entity-types']));
        }

        // Create import record
        $importId = $DB::table('portable_import')->insertGetId([
            'user_id' => 1, // CLI user
            'title' => 'CLI Import — ' . basename($options['zip'] ?? $options['path'] ?? 'archive'),
            'source_url' => $manifest['source']['url'] ?? null,
            'source_version' => $manifest['source']['framework'] ?? null,
            'archive_path' => $archiveDir,
            'mode' => $mode,
            'entity_types' => $entityTypes ? json_encode($entityTypes) : null,
            'status' => 'importing',
            'total_entities' => array_sum($validation['entity_counts']),
            'started_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logSection('import', "Created import job #{$importId}");

        // Run import
        $startTime = microtime(true);

        try {
            $result = $importer->import($importId, $archiveDir, $mode);
        } catch (\Exception $e) {
            $this->logSection('import', 'IMPORT FAILED: ' . $e->getMessage(), null, 'ERROR');

            $DB::table('portable_import')->where('id', $importId)->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            if ($tempDir) {
                $this->recursiveDelete($tempDir);
            }

            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 1);

        // Report
        $this->logSection('import', '');
        $this->logSection('import', "Import completed in {$elapsed}s");
        $this->logSection('import', "  Imported: {$result['imported']}");
        $this->logSection('import', "  Skipped:  {$result['skipped']}");
        $this->logSection('import', "  Errors:   {$result['errors']}");

        if (!empty($result['error_log'])) {
            $this->logSection('import', '');
            $this->logSection('import', 'Error log:');
            foreach ($result['error_log'] as $err) {
                $this->logSection('import', "  {$err}", null, 'ERROR');
            }
        }

        // Cleanup temp directory
        if ($tempDir) {
            $this->recursiveDelete($tempDir);
        }

        return $result['errors'] > 0 ? 1 : 0;
    }

    protected function runExistingImport(int $importId): int
    {
        $DB = \Illuminate\Database\Capsule\Manager::class;
        $importRow = $DB::table('portable_import')->where('id', $importId)->first();

        if (!$importRow) {
            $this->logSection('import', "Import #{$importId} not found", null, 'ERROR');

            return 1;
        }

        if (!$importRow->archive_path || !is_dir($importRow->archive_path)) {
            $this->logSection('import', "Archive directory not found: {$importRow->archive_path}", null, 'ERROR');

            return 1;
        }

        $culture = 'en';
        $manifest = null;
        $manifestPath = $importRow->archive_path . '/manifest.json';
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            $culture = $manifest['culture'] ?? 'en';
        }

        $importer = new \AhgPortableExportPlugin\Services\ArchiveImporter($culture, function ($current, $total) {
            $pct = $total > 0 ? round(($current / $total) * 100) : 0;
            $this->logSection('import', "Progress: {$pct}% ({$current}/{$total})");
        });

        $DB::table('portable_import')->where('id', $importId)->update([
            'status' => 'importing',
            'started_at' => date('Y-m-d H:i:s'),
        ]);

        $startTime = microtime(true);

        try {
            $result = $importer->import($importId, $importRow->archive_path, $importRow->mode ?? 'merge');
        } catch (\Exception $e) {
            $this->logSection('import', 'IMPORT FAILED: ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 1);
        $this->logSection('import', "Import #{$importId} completed in {$elapsed}s");
        $this->logSection('import', "  Imported: {$result['imported']}, Skipped: {$result['skipped']}, Errors: {$result['errors']}");

        return $result['errors'] > 0 ? 1 : 0;
    }

    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}
