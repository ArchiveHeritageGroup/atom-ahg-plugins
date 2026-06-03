<?php

/**
 * authResCacheClearTask - Symfony 1.4 task for AtoM Heratio
 *
 * Task 10 (CLI consolidation) external-authority cache evictor. DELETEs from
 * ahg_authority_lookup_cache scoped to either a single source or every row.
 * --force skips the interactive confirmation; without it the task prints
 * what it would delete and exits non-zero unless the operator passes
 * --force explicitly. STDIN-based prompting is intentionally NOT used
 * (Symfony 1.4 task readline + sudo + cron is a footgun).
 *
 * Usage:
 *   php symfony auth-res:cache-clear --source=viaf            # dry-run preview
 *   php symfony auth-res:cache-clear --source=viaf --force    # actual delete
 *   php symfony auth-res:cache-clear --all --force            # nuke everything
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * Licensed under the GNU General Public License v3.0 or later.
 */

// No service dependencies. Capsule is enough for the DELETE + preview SELECTs.
use Illuminate\Database\Capsule\Manager as DB;

class authResCacheClearTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'Application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'Environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'Connection', 'propel'),
            new sfCommandOption('source', null, sfCommandOption::PARAMETER_REQUIRED, 'Single source to clear (viaf, wikidata, geonames, tgn, gnd, isni, sagnc)'),
            new sfCommandOption('all', null, sfCommandOption::PARAMETER_NONE, 'Clear EVERY row across all sources'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Required to actually delete. Without --force the task previews and exits non-zero.'),
        ]);

        $this->namespace = 'auth-res';
        $this->name = 'cache-clear';
        $this->briefDescription = 'Evict rows from ahg_authority_lookup_cache by source or wholesale.';
        $this->detailedDescription = <<<EOF
Task 10 of the AHG Authority Resolution Engine. DELETEs from
ahg_authority_lookup_cache, scoped by --source=NAME or --all.

Safety: without --force the task only previews the row count and exits 2.
This avoids the "I tab-completed the wrong source" class of mistake on a
warm cache.

Useful workflows:
  - VIAF API contract changed -> clear viaf rows, let the adapter refill.
  - Stale GeoNames endpoint -> clear geonames, point to the new host, retry.
  - Full cache reset before benchmarking adapter cold-start latency.

Pairs with auth-res:cache-stats for the read-side.

Usage:
  php symfony auth-res:cache-clear --source=viaf
  php symfony auth-res:cache-clear --source=viaf --force
  php symfony auth-res:cache-clear --all --force
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        parent::execute($arguments, $options);

        $source = isset($options['source']) ? trim((string) $options['source']) : '';
        $all = !empty($options['all']);
        $force = !empty($options['force']);

        if ($source === '' && !$all) {
            $this->logSection('auth-res', 'Provide --source=NAME or --all.', null, 'ERROR');
            return 1;
        }
        if ($source !== '' && $all) {
            $this->logSection('auth-res', 'Pass --source OR --all, not both.', null, 'ERROR');
            return 1;
        }

        if ($all) {
            $count = (int) DB::table('ahg_authority_lookup_cache')->count();
        } else {
            $count = (int) DB::table('ahg_authority_lookup_cache')
                ->where('source', '=', $source)
                ->count();
        }

        $scope = $all ? 'all sources' : "source={$source}";

        if ($count === 0) {
            $this->log(sprintf('No rows match (%s). Nothing to do.', $scope));
            return 0;
        }

        if (!$force) {
            $this->log(sprintf(
                'Would delete %d row(s) from ahg_authority_lookup_cache (%s).',
                $count,
                $scope
            ));
            $this->log('Re-run with --force to actually delete.');
            return 2;
        }

        if ($all) {
            $deleted = (int) DB::table('ahg_authority_lookup_cache')->delete();
        } else {
            $deleted = (int) DB::table('ahg_authority_lookup_cache')
                ->where('source', '=', $source)
                ->delete();
        }

        $this->logSection('auth-res', sprintf(
            'Deleted %d row(s) from ahg_authority_lookup_cache (%s).',
            $deleted,
            $scope
        ));
        return 0;
    }
}
