<?php
/**
 * PSIS / AtoM-AHG - c2pa:provenance-backfill.
 *
 * Port of Heratio's ahg:c2pa-provenance-backfill. Records signed C2PA
 * digitisation provenance for every master digital object that has none yet
 * (sign + sidecar, plus a c2patool embed for JPEG/PNG/TIFF/MP4 and an
 * associated-file embed for PDF). Idempotent: an object that already has a
 * record is skipped, so it is safe to re-run or schedule nightly as the safety
 * net behind the per-request create watcher.
 *
 * Run as the web user so the signing key is readable and the Symfony cache is
 * not written as another user:
 *
 *   sudo -u www-data php symfony c2pa:provenance-backfill --dry-run
 *   sudo -u www-data php symfony c2pa:provenance-backfill --limit=100
 *   sudo -u www-data php symfony c2pa:provenance-backfill --id=12345
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use Illuminate\Database\Capsule\Manager as DB;

class c2paProvenanceBackfillTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Restrict to one information_object id'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max masters to process (0 = no limit)', 0),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'List what would be recorded; write nothing'),
        ]);

        $this->namespace = 'c2pa';
        $this->name = 'provenance-backfill';
        $this->briefDescription = 'Record signed C2PA provenance for master digital objects that have none';
        $this->detailedDescription = <<<'EOF'
The [c2pa:provenance-backfill|INFO] task records a signed digitisation-provenance
entry for every master digital object without one. Idempotent.

  [sudo -u www-data php symfony c2pa:provenance-backfill --dry-run|INFO]
  [sudo -u www-data php symfony c2pa:provenance-backfill --limit=100|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        $this->bootFramework();
        // CLI runs get no context.load_factories, so PSIS's own timezone was never
        // applied and records were stamped in AtoM's default (Vancouver) time.
        require_once \sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Listeners/TimezoneOverride.php';
        \AhgCore\Listeners\TimezoneOverride::apply(new \sfEvent($this, 'context.load_factories'));

        if (!DB::schema()->hasTable('ahg_c2pa_provenance')) {
            $this->logSection('c2pa', 'ahg_c2pa_provenance is not installed (database/install.sql); nothing to do.', null, 'ERROR');

            return 1;
        }

        $bridge = \C2paBootstrap::digitalObjectService();
        $cap = $bridge->provenance()->capability();
        $this->logSection('c2pa', 'Capability: ' . $cap['summary']);

        $dryRun = !empty($options['dry-run']);
        if (!$cap['can_sign_manifest'] && !$dryRun) {
            $this->logSection('c2pa', 'Cannot sign on this host/user, so no records are written (an unsigned record would block later signing). Run as www-data with the key installed.', null, 'ERROR');

            return 1;
        }

        // Parentless digital objects on a description with no provenance yet.
        $q = DB::table('digital_object as d')
            ->join('information_object as io', 'io.id', '=', 'd.object_id')
            ->leftJoin('ahg_c2pa_provenance as p', 'p.digital_object_id', '=', 'd.id')
            ->whereNull('p.id')
            ->whereNull('d.parent_id')
            ->where('d.path', 'not like', 'http%')
            ->where('d.path', 'not like', 'ftp%')
            ->orderBy('d.id')
            ->select(['d.id', 'd.object_id', 'd.name']);
        if (!empty($options['id'])) {
            $q->where('d.object_id', (int) $options['id']);
        }
        $limit = (int) ($options['limit'] ?? 0);
        if ($limit > 0) {
            $q->limit($limit);
        }
        $rows = $q->get();

        $this->logSection('c2pa', sprintf('%s: %d master(s) without a provenance record', $dryRun ? 'DRY-RUN' : 'RUN', count($rows)));

        $recorded = 0;
        $skipped = 0;
        foreach ($rows as $r) {
            if ($dryRun) {
                $this->logSection('c2pa', "  do#{$r->id} io#{$r->object_id} {$r->name} -> would record");

                continue;
            }

            $id = $bridge->recordForDigitalObject((int) $r->id, 'Backfilled via c2pa:provenance-backfill');
            if ($id === null) {
                $this->logSection('c2pa', "  do#{$r->id} io#{$r->object_id} {$r->name} -> skipped (see error log)");
                $skipped++;

                continue;
            }
            $this->logSection('c2pa', "  do#{$r->id} io#{$r->object_id} {$r->name} -> provenance #{$id}");
            $recorded++;
        }

        if ($dryRun) {
            $this->logSection('c2pa', 'Dry-run only; nothing written.');
        } else {
            $this->logSection('c2pa', "Done. Recorded {$recorded}, skipped {$skipped}.");
        }

        return 0;
    }

    /**
     * Database + framework only. No sfContext: the task needs neither routing
     * nor a user, and creating a context can write the Symfony cache.
     */
    private function bootFramework(): void
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        if (class_exists('\AhgCore\Core\AhgDb')) {
            \AhgCore\Core\AhgDb::init();
        }
        require_once dirname(__DIR__) . '/c2pa_bootstrap.php';
        \C2paBootstrap::load();
    }
}
