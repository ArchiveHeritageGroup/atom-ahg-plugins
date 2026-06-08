<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Background worker for queued TIFF -> PDF/A combine jobs.
 *
 * The web action and the "recreate" action only set a job to 'queued'; this
 * task (run from cron every minute) runs the memory-safe batched merge
 * (TiffPdfMergeJob -> TiffPdfMergeService) so large volumes never hit the web
 * request's time/memory limits. Notifies the job's user on completion/failure.
 *
 * Run via: php symfony ahg:tiff-pdf-process   (cron: * * * * *)
 */
class ahgTiffPdfProcessTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Max jobs to process this run', 3),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'tiff-pdf-process';
        $this->briefDescription = 'Process queued TIFF->PDF/A combine jobs (background worker)';
        $this->detailedDescription = <<<'EOF'
The [ahg:tiff-pdf-process|INFO] task runs queued TIFF->PDF/A combine jobs in
the background using the memory-safe batched merge. Run it from cron:

  [* * * * *  www-data  cd /usr/share/nginx/<instance> && php symfony ahg:tiff-pdf-process|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        @set_time_limit(0);
        new sfDatabaseManager($this->configuration);

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgPreservationPlugin';
        require_once $pluginDir . '/lib/Repositories/TiffPdfMergeRepository.php';
        require_once $pluginDir . '/lib/Services/TiffPdfMergeService.php';
        require_once $pluginDir . '/lib/Jobs/TiffPdfMergeJob.php';

        $repo = new \AtomFramework\Repositories\TiffPdfMergeRepository();
        $jobs = $repo->getQueuedJobs((int) $options['limit']);

        if ($jobs->isEmpty()) {
            return 0;
        }

        $this->logSection('tiff-pdf', 'Processing ' . $jobs->count() . ' queued job(s)');

        foreach ($jobs as $job) {
            // Atomically claim the job so concurrent runs can't double-process it.
            $claimed = DB::table('tiff_pdf_merge_job')
                ->where('id', $job->id)
                ->where('status', $job->status)
                ->update(['status' => 'processing', 'updated_at' => date('Y-m-d H:i:s')]);
            if (!$claimed) {
                continue;
            }

            $this->logSection('tiff-pdf', "Job {$job->id}: {$job->job_name}");
            try {
                $ok = (new \AtomFramework\Jobs\TiffPdfMergeJob((int) $job->id))->handle();
            } catch (\Throwable $e) {
                $ok = false;
                DB::table('tiff_pdf_merge_job')->where('id', $job->id)->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            $final = DB::table('tiff_pdf_merge_job')->where('id', $job->id)->first();
            $this->logSection('tiff-pdf', "  -> " . ($final->status ?? 'unknown'));
            $this->makeWebDerivative($final);
            $this->notify($final);
        }

        return 0;
    }

    /**
     * Immediately create the fast web-optimized PDF sibling for the combined
     * master (instead of waiting for the daily ahg:optimize-pdfs cron), so the
     * big document opens page-1-fast as soon as the combine finishes.
     * Best-effort; never throws.
     */
    protected function makeWebDerivative($job): void
    {
        try {
            if (!$job || ($job->status ?? '') !== 'completed' || empty($job->output_digital_object_id)) {
                return;
            }
            if (!class_exists('ahgWebPdf')) {
                $f = sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/ahgWebPdf.class.php';
                if (is_file($f)) {
                    require_once $f;
                }
            }
            if (!class_exists('ahgWebPdf') || !ahgWebPdf::toolsAvailable()) {
                return;
            }
            $do = DB::table('digital_object')->where('id', (int) $job->output_digital_object_id)->first();
            if (!$do || 'application/pdf' !== $do->mime_type) {
                return;
            }
            $abs = rtrim((string) sfConfig::get('sf_web_dir'), '/') . $do->path . $do->name;
            if (!is_file($abs)) {
                return;
            }
            $sib = ahgWebPdf::siblingPath($abs);
            if (is_file($sib)) {
                return;   // already created
            }
            $tmp = ahgWebPdf::optimize($abs, 200);
            if (!$tmp) {
                return;
            }
            if (@copy($tmp, $sib)) {
                @chmod($sib, 0664);
                @chown($sib, 'www-data');
                @chgrp($sib, 'www-data');
                $this->logSection('tiff-pdf', '  web derivative: ' . basename($sib));
            }
            ahgWebPdf::cleanupDirOf($tmp);
        } catch (\Throwable $e) {
            error_log('[ahg:tiff-pdf-process] web derivative failed: ' . $e->getMessage());
        }
    }

    /** Best-effort completion notification to the job's user (email). Never throws. */
    protected function notify($job): void
    {
        try {
            if (!$job || empty($job->user_id)) {
                return;
            }
            $user = DB::table('user')->where('id', (int) $job->user_id)->first();
            $email = $user->email ?? null;
            if (!$email) {
                return;
            }

            if ($job->status === 'completed') {
                $subject = 'PDF/A ready: ' . $job->job_name;
                $body = "Your document combine is complete.\n\nJob: {$job->job_name}\nPages: "
                    . ($job->processed_files ?? '?') . "\n\nThe PDF/A has been attached to the record.";
            } elseif ($job->status === 'failed') {
                $subject = 'PDF/A combine failed: ' . $job->job_name;
                $body = "Your document combine failed.\n\nJob: {$job->job_name}\nError: "
                    . ($job->error_message ?? 'unknown') . "\n\nYou can retry it with Recreate.";
            } else {
                return;
            }

            if (class_exists('AhgCore\Services\EmailService')) {
                \AhgCore\Services\EmailService::send($email, $subject, $body);
            }
        } catch (\Throwable $e) {
            // Notification is best-effort; never let it fail the worker.
            error_log('[ahg:tiff-pdf-process] notify failed: ' . $e->getMessage());
        }
    }
}
