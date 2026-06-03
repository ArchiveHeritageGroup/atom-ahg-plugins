<?php

/**
 * php symfony share-link:prune [--dry-run]
 *
 * Runs PruneService — applies retention rules to share-link tables.
 *
 * Retention settings (read from ahg_settings):
 *   share_link.token_retain_days       (default 365)
 *   share_link.access_log_retain_days  (default 180)
 *
 * The token sweep deletes rows where expires_at OR revoked_at is older than
 * token_retain_days; CASCADE removes their access_log children.
 *
 * The access-log sweep deletes ONLY access rows older than access_log_retain_days,
 * regardless of parent token state — lets you keep tokens around for audit while
 * trimming their access history.
 *
 * @phase H
 */
class shareLinkPruneTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report what would be pruned, delete nothing'),
        ]);
        $this->namespace = 'share-link';
        $this->name = 'prune';
        $this->briefDescription = 'Apply retention rules to share-link tokens + access log.';
    }

    public function execute($arguments = [], $options = [])
    {
        $libDir = realpath(__DIR__ . '/../Services');
        require_once $libDir . '/PruneService.php';

        $dryRun = !empty($options['dry-run']);
        $svc = new \AhgShareLink\Services\PruneService();
        $summary = $svc->prune($dryRun);

        $this->logSection('share-link:prune', sprintf(
            'token_retain_days=%d  access_log_retain_days=%d  dry_run=%s',
            $summary['token_retain_days'],
            $summary['access_log_retain_days'],
            $dryRun ? 'yes' : 'no',
        ));
        $this->logSection('share-link:prune', sprintf(
            '%s %d token row(s)',
            $dryRun ? 'would delete' : 'deleted',
            $summary['tokens_deleted'],
        ));
        $this->logSection('share-link:prune', sprintf(
            '%s %d access-log row(s)',
            $dryRun ? 'would delete' : 'deleted',
            $summary['access_rows_deleted'],
        ));
    }
}
