<?php

/**
 * Add the "Provenance graph" navigation link (#149 strand 3) — idempotent.
 *
 * Inserts a menu node (path ricExplorer/provenance) as the last child of the
 * "Manage" menu, using nested-set (MPTT) surgery with an integrity check.
 *
 *   php symfony ric:install-provenance-menu
 *
 * Clear the cache + restart php-fpm afterwards so the nav refreshes.
 */
class ricInstallProvenanceMenuTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('parent', null, sfCommandOption::PARAMETER_OPTIONAL, 'Parent menu name', 'manage'),
        ]);

        $this->namespace = 'ric';
        $this->name = 'install-provenance-menu';
        $this->briefDescription = 'Add the Provenance graph nav link (idempotent)';
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        $conn = \Illuminate\Database\Capsule\Manager::connection();

        if ($conn->table('menu')->where('name', 'provenanceGraph')->exists()) {
            $this->logSection('ric', 'Provenance graph nav link already present — nothing to do.');

            return;
        }

        $parentName = $options['parent'] ?: 'manage';
        $parent = $conn->table('menu')->where('name', $parentName)->first();
        if (!$parent) {
            $this->logSection('ric', 'WARNING: no "'.$parentName.'" menu found — skipping nav link.', null, 'ERROR');

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
                'name' => 'provenanceGraph',
                'path' => 'ricExplorer/provenance',
                'lft' => $r,
                'rgt' => $r + 1,
                'source_culture' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $conn->table('menu_i18n')->insert([
                'id' => $id, 'culture' => 'en', 'label' => 'Provenance graph',
            ]);

            $agg = $conn->table('menu')->selectRaw('COUNT(*) n, MIN(lft) mn, MAX(rgt) mx')->first();
            $bad = (int) $conn->table('menu')->whereRaw('rgt <= lft')->count();
            $expected = (int) (((int) $agg->mx - (int) $agg->mn + 1) / 2);
            if ((int) $agg->n !== $expected || $bad > 0) {
                throw new \RuntimeException('nested-set integrity check failed (n='.$agg->n.' expected='.$expected.' bad='.$bad.')');
            }
        });

        $this->logSection('ric', 'Added "Provenance graph" nav link under '.$parentName.'. Clear cache + restart php-fpm to show it.');
    }
}
