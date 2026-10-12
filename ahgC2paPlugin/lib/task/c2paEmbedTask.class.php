<?php
/**
 * PSIS / AtoM-AHG - c2pa:embed.
 *
 * Port of Heratio's ahg:c2pa-embed. For provenance records that already carry
 * a signed manifest, (re)embed that manifest into the master file: JUMBF via
 * c2patool for JPEG/PNG/TIFF/MP4, an associated file for PDF. The original
 * master is never modified; the embedded copy is a sibling <stem>.c2pa.<ext>.
 * Distinct from c2pa:provenance-backfill, which creates missing records.
 *
 *   sudo -u www-data php symfony c2pa:embed --dry-run
 *   sudo -u www-data php symfony c2pa:embed --id=12345
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

use AhgC2pa\Services\C2paService;
use AhgC2pa\Services\DigitalObjectProvenanceService;
use Illuminate\Database\Capsule\Manager as DB;

class c2paEmbedTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Restrict to one information_object id'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max records to process (0 = no limit)', 0),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'List what would be embedded; write nothing'),
        ]);

        $this->namespace = 'c2pa';
        $this->name = 'embed';
        $this->briefDescription = 'Embed signed C2PA manifests into master files (JPEG/PNG/TIFF/MP4 via c2patool, PDF)';
        $this->detailedDescription = <<<'EOF'
The [c2pa:embed|INFO] task writes each signed provenance manifest into a sibling
copy of its master file. Originals are untouched.

  [sudo -u www-data php symfony c2pa:embed --dry-run|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }
        if (class_exists('\AhgCore\Core\AhgDb')) {
            \AhgCore\Core\AhgDb::init();
        }
        // CLI runs get no context.load_factories, so PSIS's own timezone was never
        // applied and records were stamped in AtoM's default (Vancouver) time.
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Listeners/TimezoneOverride.php';
        \AhgCore\Listeners\TimezoneOverride::apply(new sfEvent($this, 'context.load_factories'));
        require_once dirname(__DIR__) . '/c2pa_bootstrap.php';
        \C2paBootstrap::load();

        $c2pa = \C2paBootstrap::c2paService();
        if ($c2pa->canEmbed()) {
            $this->logSection('c2pa', 'Using c2patool: ' . $c2pa->toolPath());
        } else {
            $this->logSection('c2pa', 'c2patool not installed: only PDF masters can be embedded.', null, 'COMMENT');
        }

        if (!DB::schema()->hasTable('ahg_c2pa_provenance') || !DB::schema()->hasTable('ahg_c2pa_manifest')) {
            $this->logSection('c2pa', 'C2PA tables not installed; nothing to do.', null, 'ERROR');

            return 1;
        }

        $q = DB::table('ahg_c2pa_provenance')
            ->whereNotNull('manifest_id')
            ->where('sign_status', 'signed')
            ->whereNotNull('digital_object_id')
            ->orderBy('id');
        if (!empty($options['id'])) {
            $q->where('information_object_id', (int) $options['id']);
        }
        $limit = (int) ($options['limit'] ?? 0);
        if ($limit > 0) {
            $q->limit($limit);
        }
        $rows = $q->get();

        $dryRun = !empty($options['dry-run']);
        $this->logSection('c2pa', sprintf('%s: %d signed provenance record(s) with a linked master', $dryRun ? 'DRY-RUN' : 'RUN', count($rows)));

        $embedded = 0;
        $skipped = 0;
        foreach ($rows as $r) {
            $do = DigitalObjectProvenanceService::loadRow((int) $r->digital_object_id);
            $master = ($do !== null && (int) $do->object_id === (int) $r->information_object_id)
                ? DigitalObjectProvenanceService::resolveAssetPath($do)
                : null;
            if ($master === null) {
                $this->logSection('c2pa', "  prov#{$r->id} do#{$r->digital_object_id} - master not found on disk, skip");
                $skipped++;

                continue;
            }

            if (C2paService::isEncryptedAtRest($master)) {
                // An embedded copy would sit beside the encrypted master in plaintext.
                $this->logSection('c2pa', "  prov#{$r->id} {$master} - encrypted at rest, sidecar only, skip");
                $skipped++;

                continue;
            }

            $isPdf = (bool) preg_match('/\.pdf$/i', $master);
            if (!$isPdf && (!$c2pa->canEmbed() || !C2paService::isEmbeddableFormat($master))) {
                $this->logSection('c2pa', "  prov#{$r->id} {$master} - sidecar-only format, skip");
                $skipped++;

                continue;
            }

            $row = DB::table('ahg_c2pa_manifest')->where('id', (int) $r->manifest_id)->first(['manifest_json']);
            $manifest = $row === null ? null : json_decode((string) $row->manifest_json, true);
            if (!is_array($manifest)) {
                $this->logSection('c2pa', "  prov#{$r->id} - bound manifest #{$r->manifest_id} missing or corrupt, skip");
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->logSection('c2pa', "  prov#{$r->id} io#{$r->information_object_id} {$master} -> would embed");

                continue;
            }

            try {
                $out = $isPdf ? $c2pa->embedInPdf($master, $manifest) : $c2pa->embed($master, $manifest);
            } catch (\Throwable $e) {
                $out = null;
                error_log('[c2pa] embed threw for ' . $master . ': ' . $e->getMessage());
            }
            if ($out === null) {
                $this->logSection('c2pa', "  prov#{$r->id} {$master} - embed FAILED (see error log)", null, 'ERROR');
                $skipped++;

                continue;
            }
            $this->logSection('c2pa', "  prov#{$r->id} io#{$r->information_object_id} {$master} -> {$out}");
            $embedded++;
        }

        $this->logSection('c2pa', $dryRun
            ? 'Dry-run only; nothing written.'
            : "Done. Embedded {$embedded}, skipped {$skipped}. Originals untouched; embedded copies are sibling .c2pa.<ext> files.");

        return 0;
    }
}
