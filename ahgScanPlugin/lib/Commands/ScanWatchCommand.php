<?php

namespace AtomFramework\Console\Commands\Scan;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * scan:watch — ahgScanPlugin.
 *
 * Polls enabled scan_folder rows, detects new files, dedupes by SHA-256,
 * stages them as ingest_row records on each folder's backing ingest_session,
 * and (when auto_commit is on) launches the ahgIngestPlugin commit pipeline.
 *
 * One-shot (ideal from cron, every minute):
 *   php bin/atom scan:watch --once
 *
 * Continuous (under systemd / supervisord):
 *   php bin/atom scan:watch --interval=30
 *
 * Restrict to a single folder:
 *   php bin/atom scan:watch --folder=incoming-archive --once
 */
class ScanWatchCommand extends BaseCommand
{
    protected string $name = 'scan:watch';
    protected string $description = 'Watch scan folders and feed new files into the ingest pipeline';
    protected string $detailedDescription = <<<'EOF'
    Detects new files in configured watched folders and streams them into the
    ahgIngestPlugin commit pipeline. Files are deduplicated by SHA-256 within
    each folder's session, and disposed to the processed (archive) or failed
    (quarantine) directory according to the folder's disposition settings.

    Examples:
      php bin/atom scan:watch --once
      php bin/atom scan:watch --interval=30
      php bin/atom scan:watch --folder=incoming-archive --once
    EOF;

    protected function configure(): void
    {
        $this->addOption('once', null, 'Run a single pass then exit');
        $this->addOption('interval', 'i', 'Seconds between passes when not --once', '30');
        $this->addOption('folder', 'f', 'Process only this scan_folder code');
    }

    protected function handle(): int
    {
        $this->requireServices();

        $once = $this->hasOption('once');
        $interval = max(5, (int) $this->option('interval'));
        $onlyCode = $this->option('folder');

        $foldersSvc = new \AhgScanPlugin\Services\WatchedFolderService();
        $scanner = new \AhgScanPlugin\Services\ScannerService($foldersSvc);

        $log = function (string $level, string $msg): void {
            match ($level) {
                'error' => $this->error($msg),
                'warning' => $this->warning($msg),
                default => $this->line($msg),
            };
        };

        do {
            $list = $foldersSvc->enabledFolders();
            if ($onlyCode) {
                $list = array_values(array_filter($list, fn ($f) => $f->code === $onlyCode));
                if (empty($list)) {
                    $this->error("No enabled scan_folder with code '{$onlyCode}'.");

                    return 1;
                }
            }

            if (empty($list)) {
                $this->comment('No enabled watched folders.');
            }

            foreach ($list as $folder) {
                $counts = $scanner->scanFolder($folder, $log);
                if ($counts['enqueued'] > 0 || $counts['failed'] > 0 || $counts['skipped_duplicate'] > 0) {
                    $this->info(sprintf(
                        '[%s] detected=%d enqueued=%d dup=%d quiet=%d failed=%d status=%s',
                        $folder->code,
                        $counts['detected'],
                        $counts['enqueued'],
                        $counts['skipped_duplicate'],
                        $counts['skipped_quiet'],
                        $counts['failed'],
                        $counts['status']
                    ));
                }
            }

            if (!$once) {
                sleep($interval);
            }
        } while (!$once);

        return 0;
    }

    private function requireServices(): void
    {
        $base = $this->getPluginsRoot() . '/ahgScanPlugin/lib/Services';
        require_once $base . '/WatchedFolderService.php';
        require_once $base . '/ScannerService.php';

        // Sanity: ahgIngestPlugin tables must exist (dependency).
        if (!DB::schema()->hasTable('ingest_row')) {
            throw new \RuntimeException('ahgIngestPlugin tables not found. Enable ahgIngestPlugin first.');
        }
        if (!DB::schema()->hasTable('scan_folder')) {
            throw new \RuntimeException("scan_folder table not found. Run the plugin's database/install.sql.");
        }
    }
}
