<?php

/**
 * Backfill full embedded metadata for existing master digital objects (#113).
 *
 * Re-runs the complete ExifTool capture (all groups) over masters already in the
 * repository and stores the grouped result in ahg_embedded_metadata, so existing
 * photos gain the full tag set without re-upload.
 *
 * Usage:
 *   php symfony metadata:backfill-embedded                 # all image masters missing a row
 *   php symfony metadata:backfill-embedded --force         # re-extract even if a row exists
 *   php symfony metadata:backfill-embedded --limit=500     # cap rows processed
 *   php symfony metadata:backfill-embedded --id=1234       # single digital_object id
 *   php symfony metadata:backfill-embedded --dry-run       # report only, no writes
 */
class metadataBackfillEmbeddedTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Single digital_object id to backfill'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Cap number of masters processed'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Re-extract even if a row already exists'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report only; make no changes'),
        ]);

        $this->namespace = 'metadata';
        $this->name = 'backfill-embedded';
        $this->briefDescription = 'Backfill full ExifTool metadata for existing master digital objects (#113)';
        $this->detailedDescription = <<<'EOF'
The [metadata:backfill-embedded|INFO] task re-extracts the COMPLETE ExifTool tag
set (all groups) for existing master digital objects and stores it in
ahg_embedded_metadata, alongside the curated fields.

  [php symfony metadata:backfill-embedded|INFO]              # image masters missing a row
  [php symfony metadata:backfill-embedded --force|INFO]      # re-extract all (refresh)
  [php symfony metadata:backfill-embedded --limit=500|INFO]  # cap rows
  [php symfony metadata:backfill-embedded --id=1234|INFO]    # one DO
  [php symfony metadata:backfill-embedded --dry-run|INFO]    # no writes
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgMetadataExtractionPlugin/lib/Services/EmbeddedMetadataService.php';
        $service = new \AtomExtensions\Extensions\MetadataExtraction\Services\EmbeddedMetadataService();

        $dryRun = (bool) ($options['dry-run'] ?? false);
        $force = (bool) ($options['force'] ?? false);
        $limit = (isset($options['limit']) && null !== $options['limit']) ? (int) $options['limit'] : null;
        $uploadDir = sfConfig::get('sf_upload_dir');

        $DB = '\\Illuminate\\Database\\Capsule\\Manager';

        // Master DOs = top-level digital objects (no parent derivative link), images only.
        $query = $DB::table('digital_object')
            ->whereNull('parent_id')
            ->where('mime_type', 'like', 'image/%')
            ->whereNotNull('path')
            ->whereNotNull('name');

        if (isset($options['id']) && null !== $options['id']) {
            $query->where('id', (int) $options['id']);
        }

        if (!$force) {
            // Skip masters that already have a stored row.
            $existing = $DB::table(\AtomExtensions\Extensions\MetadataExtraction\Services\EmbeddedMetadataService::TABLE)
                ->pluck('digital_object_id')->all();
            if (!empty($existing)) {
                $query->whereNotIn('id', $existing);
            }
        }

        $query->orderBy('id');
        if (null !== $limit) {
            $query->limit($limit);
        }

        $masters = $query->get();
        $total = count($masters);

        $this->logSection('metadata', sprintf('%d master image object(s) to process%s.', $total, $dryRun ? ' (dry-run)' : ''));

        $done = 0;
        $stored = 0;
        $skipped = 0;
        foreach ($masters as $do) {
            ++$done;

            $path = (string) $do->path;
            if (0 === strpos($path, '/uploads/')) {
                $path = substr($path, 9);
            }
            $absPath = rtrim($uploadDir, '/') . '/' . ltrim($path, '/') . $do->name;

            if (!is_readable($absPath)) {
                ++$skipped;
                $this->logSection('skip', sprintf('DO %d: file not readable (%s)', $do->id, $absPath), null, 'COMMENT');
                continue;
            }

            if ($dryRun) {
                $flat = $service->extractFull($absPath);
                $this->logSection('would', sprintf('DO %d: %d tags', $do->id, is_array($flat) ? count($flat) : 0));
                continue;
            }

            if ($service->captureAndStore((int) $do->id, $absPath, isset($do->object_id) ? (int) $do->object_id : null)) {
                ++$stored;
                if (0 === $stored % 25) {
                    $this->logSection('metadata', sprintf('  ... %d/%d stored', $stored, $total));
                }
            } else {
                ++$skipped;
            }
        }

        $this->logSection('metadata', sprintf('Done. processed=%d stored=%d skipped=%d%s', $done, $stored, $skipped, $dryRun ? ' (dry-run)' : ''));
    }
}
