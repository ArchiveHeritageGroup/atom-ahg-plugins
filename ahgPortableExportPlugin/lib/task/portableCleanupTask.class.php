<?php

/**
 * CLI command for cleaning up expired portable exports.
 *
 * Usage:
 *   php symfony portable:cleanup                    # Delete expired exports
 *   php symfony portable:cleanup --dry-run          # Preview what would be deleted
 *   php symfony portable:cleanup --older-than=7     # Override retention (days)
 */
class portableCleanupTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview deletions without removing anything'),
            new sfCommandOption('older-than', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override retention period (days)'),
        ]);

        $this->namespace = 'portable';
        $this->name = 'cleanup';
        $this->briefDescription = 'Delete expired portable exports and their files';
        $this->detailedDescription = <<<'EOF'
The [portable:cleanup|INFO] task removes expired portable exports
and their associated files from disk.

  [php symfony portable:cleanup|INFO]
  [php symfony portable:cleanup --dry-run|INFO]
  [php symfony portable:cleanup --older-than=7|INFO]

Exports are considered expired when their `expires_at` date has passed,
or when they exceed the configured retention period in AHG Settings.
Add this to a cron job for automatic cleanup.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $DB = \Illuminate\Database\Capsule\Manager::class;

        // Load services
        $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
        require_once $ahgDir . '/lib/Services/ExportPipelineService.php';

        $dryRun = !empty($options['dry-run']);
        $now = date('Y-m-d H:i:s');

        // Determine retention period
        if (!empty($options['older-than'])) {
            $retentionDays = (int) $options['older-than'];
        } else {
            $retentionDays = (int) ($DB::table('ahg_settings')
                ->where('setting_key', 'portable_export_retention_days')
                ->value('setting_value') ?: 30);
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

        $this->logSection('portable', $dryRun ? 'DRY RUN - no files will be deleted' : 'Cleaning up expired exports...');
        $this->logSection('portable', "Retention: {$retentionDays} days (cutoff: {$cutoffDate})");

        // Find exports to delete:
        // 1. Exports with explicit expires_at that has passed
        // 2. Completed/failed exports older than retention period
        $expired = $DB::table('portable_export')
            ->where(function ($q) use ($now, $cutoffDate) {
                $q->where(function ($q2) use ($now) {
                    $q2->whereNotNull('expires_at')
                        ->where('expires_at', '<', $now);
                })->orWhere(function ($q2) use ($cutoffDate) {
                    $q2->whereIn('status', ['completed', 'failed'])
                        ->where('created_at', '<', $cutoffDate);
                });
            })
            ->get();

        if ($expired->isEmpty()) {
            $this->logSection('portable', 'No expired exports found.');

            return 0;
        }

        $this->logSection('portable', "Found {$expired->count()} expired export(s):");

        $pipeline = new \AhgPortableExportPlugin\Services\ExportPipelineService();
        $totalSize = 0;

        foreach ($expired as $export) {
            $sizeMB = $export->output_size ? round($export->output_size / 1048576, 1) : 0;
            $totalSize += $export->output_size ?? 0;

            $this->logSection('portable', sprintf(
                '  #%d: "%s" (%s, %s MB, created %s)',
                $export->id,
                $export->title,
                $export->status,
                $sizeMB,
                $export->created_at
            ));

            if (!$dryRun) {
                $pipeline->deleteExport((int) $export->id);
            }
        }

        $totalMB = round($totalSize / 1048576, 1);

        if ($dryRun) {
            $this->logSection('portable', "Would delete {$expired->count()} export(s) ({$totalMB} MB)");
        } else {
            $this->logSection('portable', "Deleted {$expired->count()} export(s) ({$totalMB} MB freed)");
        }

        return 0;
    }
}
