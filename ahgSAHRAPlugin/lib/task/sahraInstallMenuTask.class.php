<?php

/**
 * Add the SAHRA heritage-permit navigation links - idempotent.
 *
 * Inserts two menu nodes with nested-set (MPTT) surgery + integrity check:
 *   - "SAHRA permits"    under "manage"     -> sahra/index          (staff)
 *   - "Heritage permits" under "quickLinks" -> sahra/my-applications (researchers)
 *
 * Safe to run repeatedly (a no-op once the items exist).
 *
 *   php symfony sahra:install-menu
 *
 * Clear the cache + reload php-fpm afterwards so the nav refreshes.
 */
class sahraInstallMenuTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'sahra';
        $this->name = 'install-menu';
        $this->briefDescription = 'Add the SAHRA heritage-permit nav links (idempotent)';
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        $conn = \Illuminate\Database\Capsule\Manager::connection();

        $this->addLink($conn, 'manage', 'sahraPermits', 'sahra/index', 'SAHRA permits');
        $this->addLink($conn, 'quickLinks', 'sahraMyPermits', 'sahra/my-applications', 'Heritage permits');

        $this->logSection('sahra', 'Done. Clear cache + reload php-fpm to show the nav links.');
    }

    protected function addLink($conn, string $parentName, string $name, string $path, string $label): void
    {
        if ($conn->table('menu')->where('name', $name)->exists()) {
            $this->logSection('sahra', sprintf('"%s" nav link already present - skipping.', $label));

            return;
        }

        $parent = $conn->table('menu')->where('name', $parentName)->first();
        if (!$parent) {
            $this->logSection('sahra', 'WARNING: no "' . $parentName . '" menu found - skipping "' . $label . '".', null, 'ERROR');

            return;
        }

        $parentId = (int) $parent->id;
        $r = (int) $parent->rgt;
        $now = date('Y-m-d H:i:s');

        $conn->transaction(function () use ($conn, $parentId, $r, $now, $name, $path, $label) {
            $conn->update('UPDATE menu SET rgt = rgt + 2 WHERE rgt >= ?', [$r]);
            $conn->update('UPDATE menu SET lft = lft + 2 WHERE lft >= ?', [$r]);

            $id = $conn->table('menu')->insertGetId([
                'parent_id' => $parentId,
                'name' => $name,
                'path' => $path,
                'lft' => $r,
                'rgt' => $r + 1,
                'source_culture' => 'en',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $conn->table('menu_i18n')->insert([
                'id' => $id, 'culture' => 'en', 'label' => $label,
            ]);

            // nested-set integrity check before commit
            $agg = $conn->table('menu')->selectRaw('COUNT(*) n, MIN(lft) mn, MAX(rgt) mx')->first();
            $bad = (int) $conn->table('menu')->whereRaw('rgt <= lft')->count();
            $expected = (int) (((int) $agg->mx - (int) $agg->mn + 1) / 2);
            if ((int) $agg->n !== $expected || $bad > 0) {
                throw new \RuntimeException('nested-set integrity check failed (n=' . $agg->n . ' expected=' . $expected . ' bad=' . $bad . ')');
            }
        });

        $this->logSection('sahra', sprintf('Added "%s" nav link under %s.', $label, $parentName));
    }
}
