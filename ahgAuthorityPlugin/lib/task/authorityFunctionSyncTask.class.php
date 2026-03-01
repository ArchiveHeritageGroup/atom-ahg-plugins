<?php

/**
 * CLI Task: authority:function-sync
 *
 * Sync actor-function links. Validates that linked functions
 * still exist and reports orphaned links.
 *
 * Usage:
 *   php symfony authority:function-sync
 */
class authorityFunctionSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('clean', null, sfCommandOption::PARAMETER_NONE, 'Remove orphaned links'),
        ]);

        $this->namespace = 'authority';
        $this->name = 'function-sync';
        $this->briefDescription = 'Sync and validate actor-function links';
        $this->detailedDescription = <<<'EOF'
Validates actor-function links, reports orphaned references,
and optionally cleans up invalid links.

  php symfony authority:function-sync
  php symfony authority:function-sync --clean
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/src/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $db = \Illuminate\Database\Capsule\Manager::class;
        $clean = isset($options['clean']) && $options['clean'];

        $this->logSection('authority', 'Function link sync starting...');

        $links = $db::table('ahg_actor_function_link')->get()->all();
        $orphanedActors = 0;
        $orphanedFunctions = 0;
        $valid = 0;

        foreach ($links as $link) {
            // Check actor exists
            $actorExists = $db::table('actor')->where('id', $link->actor_id)->exists();
            if (!$actorExists) {
                $orphanedActors++;
                $this->logSection('authority', sprintf('  Orphaned: link #%d references missing actor #%d', $link->id, $link->actor_id));
                if ($clean) {
                    $db::table('ahg_actor_function_link')->where('id', $link->id)->delete();
                }
                continue;
            }

            // Check function exists
            $funcExists = $db::table('object')->where('id', $link->function_id)->exists();
            if (!$funcExists) {
                $orphanedFunctions++;
                $this->logSection('authority', sprintf('  Orphaned: link #%d references missing function #%d', $link->id, $link->function_id));
                if ($clean) {
                    $db::table('ahg_actor_function_link')->where('id', $link->id)->delete();
                }
                continue;
            }

            $valid++;
        }

        $this->logSection('authority', sprintf('Results: %d total links, %d valid, %d orphaned actors, %d orphaned functions',
            count($links), $valid, $orphanedActors, $orphanedFunctions));

        if ($clean && ($orphanedActors + $orphanedFunctions) > 0) {
            $this->logSection('authority', sprintf('Cleaned %d orphaned links', $orphanedActors + $orphanedFunctions));
        }

        require_once sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAuthorityPlugin/lib/Services/AuthorityFunctionService.php';
        $stats = (new \AhgAuthority\Services\AuthorityFunctionService())->getStats();
        $this->logSection('authority', sprintf('Stats: %d links, %d unique actors, %d unique functions',
            $stats['total_links'], $stats['unique_actors'], $stats['unique_functions']));
    }
}
