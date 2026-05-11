<?php

/**
 * sharepoint:auto-ingest — cron-driven SharePoint→AtoM ingest.
 *
 *   php symfony sharepoint:auto-ingest                    # all due rules
 *   php symfony sharepoint:auto-ingest --rule=42          # one rule
 *   php symfony sharepoint:auto-ingest --dry-run          # log what would happen
 *   php symfony sharepoint:auto-ingest --force            # ignore schedule_cron
 *
 * @phase 2 (v2 ingest plan, step 3)
 */
class sharepointAutoIngestTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('rule', null, sfCommandOption::PARAMETER_OPTIONAL, 'Run one rule by id'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Log what would happen, do not download or commit'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Ignore schedule_cron, run all enabled rules'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'auto-ingest';
        $this->briefDescription = 'Cron-driven SharePoint→AtoM ingest';
    }

    protected function execute($arguments = [], $options = [])
    {
        $base = __DIR__ . '/../Services';
        require_once $base . '/GraphTokenCache.php';
        require_once $base . '/GraphClientService.php';
        require_once $base . '/SharePointBrowserService.php';
        require_once $base . '/SharePointAutoIngestService.php';
        require_once __DIR__ . '/../Repositories/SharePointTenantRepository.php';
        require_once __DIR__ . '/../Repositories/SharePointDriveRepository.php';

        $svc = new \AtomExtensions\SharePoint\Services\SharePointAutoIngestService();

        $dryRun = !empty($options['dry-run']);
        $force = !empty($options['force']);

        if (!empty($options['rule'])) {
            $results = [$svc->runRule((int) $options['rule'], $dryRun)];
        } else {
            $results = $svc->runDueRules($force, $dryRun);
        }

        $this->logSection('sharepoint', sprintf('processed %d rule(s)', count($results)));
        foreach ($results as $r) {
            $this->log(sprintf(
                '  rule=%d  status=%s  new=%d  skipped=%d  %s%s',
                $r['rule_id'],
                $r['status'],
                $r['items_new'] ?? 0,
                $r['items_skipped'] ?? 0,
                isset($r['session_id']) ? "session={$r['session_id']} job={$r['job_id']}" : '',
                isset($r['error']) ? "  ERROR: {$r['error']}" : '',
            ));
        }
    }
}
