<?php

use Illuminate\Database\Capsule\Manager as DB;

class displayAutoDetectTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Re-detect all records (overwrite existing types)'),
            new sfCommandOption('refresh-cache', null, sfCommandOption::PARAMETER_NONE, 'Refresh facet cache after detection'),
        ]);

        $this->namespace = 'display';
        $this->name = 'auto-detect';
        $this->briefDescription = 'Auto-detect GLAM types for all information objects';
        $this->detailedDescription = <<<EOF
The [display:auto-detect|INFO] task scans all information objects and
auto-detects their GLAM type (Archive, Museum, Gallery, Library, DAM).

ISAD levels (fonds, series, file, item) default to Archive.

Call it with:

  [php symfony display:auto-detect|INFO]

Options:
  [--force|INFO]          Re-detect all records, overwriting existing types
  [--refresh-cache|INFO]  Refresh facet cache after detection
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $databaseManager = new sfDatabaseManager($this->configuration);

        require_once __DIR__ . '/../Services/DisplayTypeDetector.php';

        $force = !empty($options['force']);
        $startTime = microtime(true);

        $this->logSection('display', 'Starting GLAM type auto-detection...');

        if ($force) {
            $this->logSection('display', 'Force mode: re-detecting all records');
            $query = DB::table('information_object')->where('id', '>', 1);
        } else {
            // Only detect records without a type
            $query = DB::table('information_object as io')
                ->leftJoin('display_object_config as doc', 'io.id', '=', 'doc.object_id')
                ->whereNull('doc.object_id')
                ->where('io.id', '>', 1)
                ->select('io.id', 'io.parent_id', 'io.lft');
        }

        // Order by lft (nested set left value) to process parents before children
        $objects = $query->orderBy('lft', 'asc')->get();

        $total = count($objects);
        if ($total === 0) {
            $this->logSection('display', 'No records to process. Use --force to re-detect all.');

            return 0;
        }

        $this->logSection('display', "Processing {$total} records...");

        $stats = [];
        $count = 0;

        foreach ($objects as $object) {
            $type = DisplayTypeDetector::detectAndSave((int) $object->id, $force);
            $stats[$type] = ($stats[$type] ?? 0) + 1;
            $count++;

            if ($count % 500 === 0) {
                $pct = round(($count / $total) * 100, 1);
                $this->logSection('display', "Processed {$count}/{$total} ({$pct}%)...");
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->logSection('display', "Complete! Processed {$count} records in {$elapsed}s:");
        foreach ($stats as $type => $num) {
            if ($num > 0) {
                $this->logSection('display', "  " . ucfirst($type) . ": {$num}");
            }
        }

        // Refresh facet cache if requested
        if (!empty($options['refresh-cache'])) {
            $this->logSection('display', 'Refreshing facet cache...');
            $task = new ahgRefreshFacetCacheTask($this->dispatcher, $this->formatter);
            $task->run([], ['application' => 'qubit', 'env' => 'prod', 'connection' => 'propel']);
        }

        return 0;
    }
}
