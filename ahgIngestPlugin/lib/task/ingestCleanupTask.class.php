<?php

/**
 * Delete ingest working files older than the retention period (catalog #319).
 *
 * Usage:
 *   php symfony ingest:cleanup --dry-run
 *   php symfony ingest:cleanup [--days=30]
 *
 * The period comes from Admin > AHG Settings > Data Ingest Defaults
 * (ingest_work_retention_days, default 30). SIP, AIP and DIP packages are never
 * deleted. The commit job runs the same cleanup when it finishes.
 */
class ingestCleanupTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Override the retention period in days'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'List what would be deleted, delete nothing'),
        ]);

        $this->namespace = 'ingest';
        $this->name = 'cleanup';
        $this->briefDescription = 'Delete ingest working files past the retention period';
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();
        require_once sfConfig::get('sf_plugins_dir').'/ahgIngestPlugin/lib/Services/IngestService.php';

        $days = isset($options['days']) && '' !== $options['days'] ? (int) $options['days'] : null;
        $dry = !empty($options['dry-run']);
        $r = \AhgIngestPlugin\Services\IngestService::cleanupWorkFiles($days, $dry);

        foreach ($r['deleted'] as $path) {
            $this->logSection('ingest', ($dry ? 'would delete ' : 'deleted ').$path);
        }
        $this->logSection('ingest', sprintf('%s%d session folders, %d logs, %d manifests', $dry ? 'Dry run: ' : '', $r['sessions'], $r['logs'], $r['manifests']));

        return 0;
    }
}
