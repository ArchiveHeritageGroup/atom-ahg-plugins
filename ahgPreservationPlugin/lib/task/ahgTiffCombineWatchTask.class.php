<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Drop-folder watcher for the TIFF -> PDF/A combine pipeline.
 *
 * Convention: drop a folder of page TIFFs (via FTP) under the watch base named
 * with the target record reference:
 *
 *   <watch-base>/<record-ref>/page0001.tif, page0002.tif, ...
 *
 * When the folder is "ready" (a `.ready` marker file, or no file modified in
 * the last --stable-minutes), this task maps <record-ref> -> information object
 * (slug, then identifier), creates a combine job referencing the files in place,
 * queues it, and drops a `.queued` marker so it is not re-queued. The
 * ahg:tiff-pdf-process worker then runs the memory-safe merge and notifies.
 *
 * Run via cron, e.g.:  *\/5 * * * *  www-data  php symfony ahg:tiff-combine-watch
 */
class ahgTiffCombineWatchTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('base', null, sfCommandOption::PARAMETER_REQUIRED, 'Watch base dir (overrides app_tiff_combine_watch_dir)', null),
            new sfCommandOption('stable-minutes', null, sfCommandOption::PARAMETER_REQUIRED, 'A folder with no file changed for this many minutes is considered complete', 5),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'tiff-combine-watch';
        $this->briefDescription = 'Watch a drop-folder and auto-queue TIFF->PDF/A combine jobs per record';
        $this->detailedDescription = <<<'EOF'
The [ahg:tiff-combine-watch|INFO] task scans <watch-base>/<record-ref>/ folders
and auto-queues a TIFF->PDF/A combine for each completed folder.

  [*/5 * * * *  www-data  cd /usr/share/nginx/<instance> && php symfony ahg:tiff-combine-watch|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);

        $base = $options['base'] ?: (sfConfig::get('app_tiff_combine_watch_dir')
            ?: sfConfig::get('sf_web_dir') . '/uploads/tiff-combine');
        if (!is_dir($base)) {
            return 0;   // nothing to watch
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgPreservationPlugin/lib/Repositories/TiffPdfMergeRepository.php';
        $repo = new \AtomFramework\Repositories\TiffPdfMergeRepository();

        $stableSecs = max(0, (int) $options['stable-minutes'] * 60);
        $now = time();

        foreach (scandir($base) ?: [] as $name) {
            if ('.' === $name[0]) {
                continue;   // skip ., .., .processed, dotfiles
            }
            $dir = $base . '/' . $name;
            if (!is_dir($dir)) {
                continue;
            }
            if (file_exists($dir . '/.queued')) {
                continue;   // already handled
            }

            $images = $this->listImageFiles($dir);
            if (empty($images)) {
                continue;
            }

            // Readiness: explicit .ready marker, OR all files stable.
            $ready = file_exists($dir . '/.ready');
            if (!$ready) {
                $newest = 0;
                foreach ($images as $p) {
                    $newest = max($newest, (int) @filemtime($p));
                }
                $ready = ($now - $newest) >= $stableSecs;
            }
            if (!$ready) {
                continue;
            }

            // Map the folder name to a record. If none matches, still create the
            // PDF/A (attach_to_record = 0) so it is produced now and linked later.
            $ioId = $this->resolveRecord($name);
            $attach = $ioId ? 1 : 0;

            try {
                $jobId = $repo->createJob([
                    'user_id' => 0,
                    'job_name' => $name,
                    'information_object_id' => $ioId,
                    'pdf_standard' => 'pdfa-2b',
                    'attach_to_record' => $attach,
                    'status' => 'queued',
                ]);
                $mimeMap = ['tif' => 'image/tiff', 'tiff' => 'image/tiff', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'bmp' => 'image/bmp', 'gif' => 'image/gif', 'webp' => 'image/webp'];
                $order = 0;
                foreach ($images as $p) {
                    ++$order;
                    $repo->addFile($jobId, [
                        'file_path' => $p,
                        'original_filename' => basename($p),
                        'stored_filename' => basename($p),   // referenced in place
                        'mime_type' => $mimeMap[strtolower(pathinfo($p, PATHINFO_EXTENSION))] ?? 'application/octet-stream',
                        'page_order' => $order,
                        'file_size' => @filesize($p) ?: 0,
                    ]);
                }
                @file_put_contents($dir . '/.queued', "job_id={$jobId}\nqueued_at=" . date('c') . "\n");
                $this->logSection('combine-watch', "Queued job {$jobId} for '{$name}' (" . count($images) . ' pages)'
                    . ($ioId ? " -> record #{$ioId} (auto-link)" : ' -> no record matched (PDF/A created, link later)'));
            } catch (\Throwable $e) {
                $this->logSection('combine-watch', "Failed to queue '{$name}': " . $e->getMessage(), null, 'ERROR');
            }
        }

        return 0;
    }

    /** Map a folder name to an information object id by slug, then identifier. */
    protected function resolveRecord(string $ref): ?int
    {
        $io = QubitInformationObject::getBySlug($ref);
        if ($io) {
            return (int) $io->id;
        }
        $id = DB::table('information_object')->where('identifier', $ref)->value('id');

        return $id ? (int) $id : null;
    }

    /** List image files in a folder, natural-sorted so page order follows filenames. */
    protected function listImageFiles(string $dir): array
    {
        $out = [];
        foreach (scandir($dir) ?: [] as $f) {
            if ('.' === $f[0]) {
                continue;
            }
            $p = $dir . '/' . $f;
            if (!is_file($p)) {
                continue;
            }
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, ['tif', 'tiff', 'jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp'], true)) {
                $out[] = $p;
            }
        }
        natcasesort($out);

        return array_values($out);
    }
}
