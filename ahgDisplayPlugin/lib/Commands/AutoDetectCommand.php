<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Auto-detect GLAM types for all information objects.
 */
class AutoDetectCommand extends BaseCommand
{
    protected string $name = 'display:auto-detect';
    protected string $description = 'Auto-detect GLAM object types';
    protected string $detailedDescription = <<<'EOF'
    Scans all information objects and auto-detects their GLAM type
    (Archive, Museum, Gallery, Library, DAM).

    ISAD levels (fonds, series, file, item) default to Archive.

    Options:
      --force          Re-detect all records, overwriting existing types
      --refresh-cache  Refresh facet cache after detection
    EOF;

    protected function configure(): void
    {
        $this->addOption('force', 'f', 'Re-detect all records, overwriting existing types');
        $this->addOption('refresh-cache', null, 'Refresh facet cache after detection');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/plugins/ahgDisplayPlugin';
        $detectorFile = $pluginDir . '/lib/Services/DisplayTypeDetector.php';

        if (!file_exists($detectorFile)) {
            $this->error("DisplayTypeDetector not found at: {$detectorFile}");

            return 1;
        }

        require_once $detectorFile;

        $force = $this->hasOption('force');
        $startTime = microtime(true);

        $this->info('Starting GLAM type auto-detection...');

        if ($force) {
            $this->info('Force mode: re-detecting all records');
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
        if (0 === $total) {
            $this->info('No records to process. Use --force to re-detect all.');

            return 0;
        }

        $this->info("Processing {$total} records...");

        $stats = [];
        $count = 0;

        foreach ($objects as $object) {
            $type = \DisplayTypeDetector::detectAndSave((int) $object->id, $force);
            $stats[$type] = ($stats[$type] ?? 0) + 1;
            $count++;

            if (0 === $count % 500) {
                $pct = round(($count / $total) * 100, 1);
                $this->line("  Processed {$count}/{$total} ({$pct}%)...");
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->success("Complete! Processed {$count} records in {$elapsed}s:");
        foreach ($stats as $type => $num) {
            if ($num > 0) {
                $this->line('  ' . ucfirst($type) . ": {$num}");
            }
        }

        // Refresh facet cache if requested
        if ($this->hasOption('refresh-cache')) {
            $this->info('Refreshing facet cache...');
            $refreshCmd = new RefreshFacetCacheCommand([]);
            $refreshCmd->run();
        }

        return 0;
    }
}
