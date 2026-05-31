<?php

namespace AtomFramework\Console\Commands\Metadata;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Backfill full embedded metadata for existing master digital objects (#113).
 *
 * Re-runs the complete ExifTool capture (all groups) over masters already in the
 * repository and stores the grouped result in ahg_embedded_metadata, so existing
 * photos gain the full tag set without re-upload.
 *
 * Lives in lib/Commands (discovered by CommandRegistry via filesystem glob) rather
 * than lib/task, because ahgMetadataExtractionPlugin is filesystem-only (its
 * Symfony tasks are not registered).
 */
class MetadataBackfillEmbeddedCommand extends BaseCommand
{
    protected string $name = 'metadata:backfill-embedded';
    protected string $description = 'Backfill full ExifTool metadata for existing master digital objects (#113)';
    protected string $detailedDescription = <<<'EOF'
    Re-extracts the COMPLETE ExifTool tag set (all groups) for existing master
    digital objects and stores it in ahg_embedded_metadata, alongside the curated
    fields. Ensures the storage table exists first.

    Examples:
      php bin/atom metadata:backfill-embedded                 Image masters missing a row
      php bin/atom metadata:backfill-embedded --force         Re-extract all (refresh)
      php bin/atom metadata:backfill-embedded --limit=500     Cap rows processed
      php bin/atom metadata:backfill-embedded --id=1234        Single digital_object id
      php bin/atom metadata:backfill-embedded --dry-run        Report only, no writes
    EOF;

    protected function configure(): void
    {
        $this->addOption('id', null, 'Single digital_object id to backfill');
        $this->addOption('limit', null, 'Cap number of masters processed');
        $this->addOption('force', 'f', 'Re-extract even if a row already exists');
        $this->addOption('dry-run', null, 'Report only; make no changes');
    }

    protected function handle(): int
    {
        $serviceFile = $this->getAtomRoot() . '/plugins/ahgMetadataExtractionPlugin/lib/Services/EmbeddedMetadataService.php';
        if (!file_exists($serviceFile)) {
            $this->error("EmbeddedMetadataService not found at: {$serviceFile}");

            return 1;
        }
        require_once $serviceFile;
        $service = new \AtomExtensions\Extensions\MetadataExtraction\Services\EmbeddedMetadataService();

        $dryRun = $this->hasOption('dry-run');
        $force = $this->hasOption('force');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $singleId = $this->option('id') !== null ? (int) $this->option('id') : null;

        // Ensure storage table exists (covers existing installs).
        $sqlFile = $this->getAtomRoot() . '/plugins/ahgMetadataExtractionPlugin/database/install.sql';
        if (is_file($sqlFile)) {
            try {
                DB::unprepared(file_get_contents($sqlFile));
            } catch (\Throwable $e) {
                $this->warning('Could not ensure ahg_embedded_metadata table: ' . $e->getMessage());
            }
        }

        // Resolve the uploads directory (sfConfig if Symfony is loaded, else default).
        $uploadDir = (class_exists('\sfConfig') && \sfConfig::get('sf_upload_dir'))
            ? \sfConfig::get('sf_upload_dir')
            : $this->getAtomRoot() . '/uploads';

        // Master DOs = top-level digital objects (no derivative parent), images only.
        $query = DB::table('digital_object')
            ->whereNull('parent_id')
            ->where('mime_type', 'like', 'image/%')
            ->whereNotNull('path')
            ->whereNotNull('name');

        if ($singleId !== null) {
            $query->where('id', $singleId);
        }

        if (!$force) {
            $existing = DB::table(\AtomExtensions\Extensions\MetadataExtraction\Services\EmbeddedMetadataService::TABLE)
                ->pluck('digital_object_id')->all();
            if (!empty($existing)) {
                $query->whereNotIn('id', $existing);
            }
        }

        $query->orderBy('id');
        if ($limit !== null) {
            $query->limit($limit);
        }

        $masters = $query->get();
        $total = count($masters);
        $this->info(sprintf('%d master image object(s) to process%s.', $total, $dryRun ? ' (dry-run)' : ''));

        $stored = 0;
        $skipped = 0;
        foreach ($masters as $do) {
            $path = (string) $do->path;
            if (strpos($path, '/uploads/') === 0) {
                $path = substr($path, 9);
            }
            $absPath = rtrim($uploadDir, '/') . '/' . ltrim($path, '/') . $do->name;

            if (!is_readable($absPath)) {
                ++$skipped;
                $this->comment(sprintf('DO %d: file not readable (%s)', $do->id, $absPath));
                continue;
            }

            if ($dryRun) {
                $flat = $service->extractFull($absPath);
                $this->line(sprintf('  would store DO %d: %d tags  [%s]%s', $do->id, is_array($flat) ? count($flat) : 0, $absPath, is_file($absPath) ? '' : ' (NOT A FILE)'));
                continue;
            }

            if ($service->captureAndStore((int) $do->id, $absPath, isset($do->object_id) ? (int) $do->object_id : null)) {
                ++$stored;
            } else {
                ++$skipped;
            }
        }

        $this->success(sprintf('Done. processed=%d stored=%d skipped=%d%s', $total, $stored, $skipped, $dryRun ? ' (dry-run)' : ''));

        return 0;
    }
}
