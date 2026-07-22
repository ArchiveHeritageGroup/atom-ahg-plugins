<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once __DIR__ . '/../FacetCacheRefresher.php';

/**
 * Refresh the display facet cache.
 *
 * Stores two sets of counts:
 * - Published only (facet_type = 'subject', 'place', etc.) for guest users
 * - All records (facet_type = 'subject_all', 'place_all', etc.) for authenticated users
 *
 * The actual SQL lives in FacetCacheRefresher so the same logic can be driven by
 * the standalone Illuminate-only runner (ahgDisplayPlugin/bin/refresh-facet-cache.php).
 *
 * ⚠️ For anything AUTOMATED (cron, seed scripts) use the standalone runner, NOT this
 * task: booting the full prod app from the symfony CLI can pin the web runtime to a
 * broken compiled config cache (site-wide HTTP 500) on hosts with
 * opcache.validate_timestamps=0. This task is for interactive/manual use only; if you
 * run it and the site 500s, recover with:
 *   rm -rf cache/qubit/prod/config/* && systemctl reload php8.3-fpm
 *
 * Run via: php symfony ahg:refresh-facet-cache
 */
class ahgRefreshFacetCacheTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'refresh-facet-cache';
        $this->briefDescription = 'Refresh display facet cache';
        $this->detailedDescription = <<<EOF
The [ahg:refresh-facet-cache|INFO] task refreshes the cached facet counts
for the display browse page — both guest (published) and authenticated (all) sets.

For cron / automation use the standalone runner instead:
  [ahgDisplayPlugin/bin/refresh-facet-cache.php|INFO]

Call this task with:

  [php symfony ahg:refresh-facet-cache|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $conn = DB::connection()->getPdo();

        $this->logSection('facet-cache', 'Starting facet cache refresh...');
        $startTime = microtime(true);

        $count = FacetCacheRefresher::refresh($conn, function ($msg) {
            $this->logSection('facet-cache', $msg);
        });

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->logSection('facet-cache', "Cache refresh complete: {$count} entries in {$elapsed}s");
    }
}
