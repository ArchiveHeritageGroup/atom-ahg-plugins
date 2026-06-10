<?php

/**
 * Add the "Researcher Copilot" navigation link (#149) — idempotent.
 *
 * Inserts a menu node (path ai/research) as the last child of the "Manage"
 * menu, using nested-set (MPTT) surgery with an integrity check. Safe to run
 * repeatedly: a no-op once the item exists.
 *
 *   php symfony ai:install-research-menu
 *
 * Clear the cache + restart php-fpm afterwards so the nav refreshes.
 */
class aiInstallResearchMenuTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('parent', null, sfCommandOption::PARAMETER_OPTIONAL, 'Parent menu name', 'manage'),
        ]);

        $this->namespace = 'ai';
        $this->name = 'install-research-menu';
        $this->briefDescription = 'Add the Researcher Copilot nav link (idempotent)';
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        $conn = \Illuminate\Database\Capsule\Manager::connection();

        if ($conn->table('menu')->where('name', 'researchCopilot')->exists()) {
            $this->logSection('ai', 'Researcher Copilot nav link already present — nothing to do.');

            return;
        }

        $parentName = $options['parent'] ?: 'manage';
        $parent = $conn->table('menu')->where('name', $parentName)->first();
        if (!$parent) {
            $this->logSection('ai', 'WARNING: no "'.$parentName.'" menu found — skipping nav link.', null, 'ERROR');

            return;
        }
        $parentId = (int) $parent->id;
        $r = (int) $parent->rgt;
        $now = date('Y-m-d H:i:s');

        $conn->transaction(function () use ($conn, $parentId, $r, $now) {
            $conn->update('UPDATE menu SET rgt = rgt + 2 WHERE rgt >= ?', [$r]);
            $conn->update('UPDATE menu SET lft = lft + 2 WHERE lft >= ?', [$r]);

            $id = $conn->table('menu')->insertGetId([
                'parent_id' => $parentId,
                'name' => 'researchCopilot',
                'path' => 'ai/research',
                'lft' => $r,
                'rgt' => $r + 1,
                'source_culture' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $conn->table('menu_i18n')->insert([
                'id' => $id, 'culture' => 'en', 'label' => 'Researcher Copilot',
            ]);

            $agg = $conn->table('menu')->selectRaw('COUNT(*) n, MIN(lft) mn, MAX(rgt) mx')->first();
            $bad = (int) $conn->table('menu')->whereRaw('rgt <= lft')->count();
            $expected = (int) (((int) $agg->mx - (int) $agg->mn + 1) / 2);
            if ((int) $agg->n !== $expected || $bad > 0) {
                throw new \RuntimeException('nested-set integrity check failed (n='.$agg->n.' expected='.$expected.' bad='.$bad.')');
            }
        });

        $this->logSection('ai', 'Added "Researcher Copilot" nav link under '.$parentName.'. Clear cache + restart php-fpm to show it.');
    }
}
