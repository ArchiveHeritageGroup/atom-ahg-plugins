<?php

/**
 * Roll back a committed ingest job: deletes the information objects + digital
 * objects it created (via the framework delete, with the search-index hook
 * suppressed so it works in CLI/unattended context). Useful for undoing a bad
 * unattended hot-folder (ingest:watch) batch.
 *
 *   php symfony ingest:rollback --job-id=12
 *
 * After a CLI rollback the search index may hold stale entries for the deleted
 * records; run `php symfony search:populate` (or wait for the next reindex) to
 * reconcile.
 */
class ingestRollbackTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('job-id', null, sfCommandOption::PARAMETER_REQUIRED, 'Ingest job id to roll back'),
        ]);

        $this->namespace = 'ingest';
        $this->name = 'rollback';
        $this->briefDescription = 'Roll back an ingest job (delete the records + digital objects it created)';
        $this->detailedDescription = <<<'EOF'
The [ingest:rollback|INFO] task deletes the information objects and digital
objects created by an ingest commit job.

  [php symfony ingest:rollback --job-id=12|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgIngestPlugin';
        require_once $pluginDir . '/lib/Services/IngestService.php';
        require_once $pluginDir . '/lib/Services/IngestCommitService.php';

        $jobId = (int) $options['job-id'];
        if ($jobId <= 0) {
            $this->logSection('ingest', 'ERROR: --job-id is required', null, 'ERROR');

            return 1;
        }

        $commitSvc = new \AhgIngestPlugin\Services\IngestCommitService();
        $deleted = $commitSvc->rollback($jobId);

        $this->logSection('ingest', "Rolled back job {$jobId}: deleted {$deleted} record(s).");
        if ($deleted > 0) {
            $this->logSection('ingest', 'Run `php symfony search:populate` to clear any stale search-index entries.');
        }

        return 0;
    }
}
