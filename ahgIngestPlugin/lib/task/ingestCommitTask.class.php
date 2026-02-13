<?php

/**
 * Background task for ingest commit processing.
 *
 * Runs the full commit pipeline: record creation, digital object import,
 * AI processing (NER, OCR, summarize, etc.), OAIS packaging, and indexing.
 *
 * Usage:
 *   php symfony ingest:commit --job-id=123
 *   php symfony ingest:commit --session-id=456
 */
class ingestCommitTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('job-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Job ID to process'),
            new sfCommandOption('session-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Session ID (creates job if needed)'),
        ]);

        $this->namespace = 'ingest';
        $this->name = 'commit';
        $this->briefDescription = 'Process ingest commit job in background';
        $this->detailedDescription = <<<'EOF'
The [ingest:commit|INFO] task processes an ingest session commit in the background.

  [php symfony ingest:commit --job-id=123|INFO]
  [php symfony ingest:commit --session-id=456|INFO]

Provide either --job-id (existing queued job) or --session-id (creates a new job).
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        // Load services
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin';
        require_once $pluginDir . '/lib/Services/IngestService.php';
        require_once $pluginDir . '/lib/Services/IngestCommitService.php';

        $commitSvc = new \AhgIngestPlugin\Services\IngestCommitService();

        $jobId = !empty($options['job-id']) ? (int) $options['job-id'] : null;
        $sessionId = !empty($options['session-id']) ? (int) $options['session-id'] : null;

        if (!$jobId && !$sessionId) {
            $this->logSection('ingest', 'ERROR: Provide --job-id or --session-id', null, 'ERROR');

            return 1;
        }

        // If session-id provided, create a new job
        if (!$jobId && $sessionId) {
            $this->logSection('ingest', "Creating job for session {$sessionId}...");
            $jobId = $commitSvc->startJob($sessionId);
            $this->logSection('ingest', "Created job ID: {$jobId}");
        }

        // Verify job exists
        $job = $commitSvc->getJobStatus($jobId);
        if (!$job) {
            $this->logSection('ingest', "ERROR: Job {$jobId} not found", null, 'ERROR');

            return 1;
        }

        if ($job->status === 'completed') {
            $this->logSection('ingest', "Job {$jobId} is already completed");

            return 0;
        }

        if ($job->status === 'running') {
            $this->logSection('ingest', "WARNING: Job {$jobId} is already running", null, 'ERROR');

            return 1;
        }

        $this->logSection('ingest', "Starting job {$jobId} (session {$job->session_id}, {$job->total_rows} rows)...");
        $startTime = microtime(true);

        try {
            $commitSvc->executeJob($jobId);
        } catch (\Exception $e) {
            $this->logSection('ingest', 'FATAL ERROR: ' . $e->getMessage(), null, 'ERROR');

            // Mark job as failed
            \Illuminate\Database\Capsule\Manager::table('ingest_job')
                ->where('id', $jobId)
                ->update([
                    'status' => 'failed',
                    'error_log' => json_encode([['stage' => 'fatal', 'error' => $e->getMessage()]]),
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);

            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 1);

        // Get final status
        $job = $commitSvc->getJobStatus($jobId);
        $this->logSection('ingest', "Job {$jobId} {$job->status} in {$elapsed}s");
        $this->logSection('ingest', "Records: {$job->created_records}, DOs: {$job->created_dos}, Errors: {$job->error_count}");

        if ($job->manifest_path) {
            $this->logSection('ingest', "Manifest: {$job->manifest_path}");
        }

        return $job->status === 'completed' ? 0 : 1;
    }
}
