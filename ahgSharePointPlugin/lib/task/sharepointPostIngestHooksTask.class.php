<?php

/**
 * php symfony sharepoint:post-ingest-hooks --job-id=N
 *
 * Runs the LOCAL post-ingest hook chain on every IO created by the given
 * ingest job:
 *   - sp_* cross-reference columns
 *   - v1 version baseline
 *   - retention-label → security classification mapping
 *   - OAIS AIP package
 *   - PII scan
 *
 * LOCAL ONLY per SP NO-PUSH policy. Never commit this file.
 */
class sharepointPostIngestHooksTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('job-id', null, sfCommandOption::PARAMETER_REQUIRED, 'ingest_job.id whose created IOs receive the hooks'),
        ]);
        $this->namespace = 'sharepoint';
        $this->name = 'post-ingest-hooks';
        $this->briefDescription = 'Run compliance hooks (sp_xref + version baseline + classification + AIP + PII) on IOs from a given ingest job';
    }

    public function execute($arguments = [], $options = [])
    {
        $cfg = ProjectConfiguration::getApplicationConfiguration('qubit', 'cli', false);
        sfContext::createInstance($cfg);

        $jobId = (int) ($options['job-id'] ?? 0);
        if ($jobId <= 0) {
            $this->logBlock('--job-id is required', 'ERROR_LARGE');
            return 1;
        }

        require_once sfConfig::get('sf_plugins_dir') . '/ahgSharePointPlugin/lib/Services/PostIngestHookService.php';
        $svc = new \AtomExtensions\SharePoint\Services\PostIngestHookService();
        $stats = $svc->runForJob($jobId);

        $this->logSection('sharepoint', sprintf(
            'Job %d: processed=%d sp_xref=%d baselines=%d classifications=%d aips=%d pii_scans=%d errors=%d',
            $jobId,
            $stats['processed'],
            $stats['sp_xref'],
            $stats['baselines'],
            $stats['classifications'],
            $stats['aips'],
            $stats['pii_scans'],
            $stats['errors'],
        ));
        return $stats['errors'] > 0 ? 1 : 0;
    }
}
