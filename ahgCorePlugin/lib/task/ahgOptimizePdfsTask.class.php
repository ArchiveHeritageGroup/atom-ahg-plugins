<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Generate web-optimized PDF siblings so large documents open fast.
 *
 * Twin of the Heratio (Laravel) ahg:optimize-pdfs command. For each large PDF
 * master it writes a downsampled + linearized "<base>.web.pdf" next to the
 * master on disk (gs + qpdf). No database rows are added - the viewer detects
 * the sibling by filename (see ahgWebPdf). Masters are never modified.
 *
 * Idempotent: a master that already has a .web.pdf sibling is skipped, so this
 * is safe to schedule / re-run.
 *
 * Run via: php symfony ahg:optimize-pdfs [--commit] [--min-mb=20] [--dpi=200]
 */
class ahgOptimizePdfsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('commit', null, sfCommandOption::PARAMETER_NONE, 'Actually generate siblings (otherwise dry-run)'),
            new sfCommandOption('min-mb', null, sfCommandOption::PARAMETER_REQUIRED, 'Only PDFs larger than this (MB)', 20),
            new sfCommandOption('dpi', null, sfCommandOption::PARAMETER_REQUIRED, 'Target DPI for colour/grey images', 200),
            new sfCommandOption('max-ratio', null, sfCommandOption::PARAMETER_REQUIRED, 'Keep sibling only if <= this fraction of the master', 0.8),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Max PDFs to process (0 = all)', 0),
            new sfCommandOption('id', null, sfCommandOption::PARAMETER_REQUIRED, 'Restrict to one digital_object id', null),
        ]);

        $this->namespace = 'ahg';
        $this->name = 'optimize-pdfs';
        $this->briefDescription = 'Generate web-optimized PDF siblings so large documents load fast';
        $this->detailedDescription = <<<'EOF'
The [ahg:optimize-pdfs|INFO] task writes a downsampled + linearized
"<base>.web.pdf" next to each large PDF master so the viewer opens page 1
fast. Masters are never touched; the sibling is detected by filename.

  [php symfony ahg:optimize-pdfs|INFO]                       (dry-run)
  [php symfony ahg:optimize-pdfs --commit --min-mb=20|INFO]  (apply)

Requires ghostscript + qpdf on the host (no-ops without them).
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        new sfDatabaseManager($this->configuration);

        if (!ahgWebPdf::toolsAvailable()) {
            $this->logSection('optimize-pdfs', 'ghostscript + qpdf not installed on this host - nothing to do.', null, 'ERROR');

            return 1;
        }

        $min = (float) $options['min-mb'] * 1048576;
        $dpi = (int) $options['dpi'];
        $maxRatio = (float) $options['max-ratio'];
        $commit = (bool) $options['commit'];
        $webDir = rtrim((string) sfConfig::get('sf_web_dir'), '/');

        // A "master" PDF = a top-level digital object (no parent). AtoM stores some
        // uploads as usage_id 142 rather than 140, so match on parent_id IS NULL
        // (not usage_id) - this catches both, and excludes derivative children.
        $q = DB::table('digital_object')
            ->whereNull('parent_id')
            ->where('mime_type', 'application/pdf')
            ->where('byte_size', '>', $min);
        if (null !== $options['id']) {
            $q->where('id', (int) $options['id']);
        }
        $rows = $q->orderBy('byte_size', 'desc')->get();
        if ((int) $options['limit'] > 0) {
            $rows = array_slice(is_array($rows) ? $rows : $rows->all(), 0, (int) $options['limit']);
        }

        $count = is_array($rows) ? count($rows) : count($rows->all());
        $this->logSection('optimize-pdfs', ($commit ? 'COMMIT' : 'DRY-RUN').": {$count} master PDF(s) over {$options['min-mb']}MB (dpi={$dpi})");

        $done = 0;
        $skipped = 0;
        foreach ($rows as $r) {
            $r = (object) $r;
            $mb = round(($r->byte_size ?: 0) / 1048576, 1);
            $abs = $webDir.$r->path.$r->name;
            $sib = ahgWebPdf::siblingPath($abs);

            if (!@is_file($abs)) {
                $this->logSection('optimize-pdfs', "  do#{$r->id} {$r->name} {$mb}MB - file missing on disk, skip", null, 'COMMENT');
                $skipped++;
                continue;
            }
            if (@is_file($sib)) {
                $skipped++;   // already optimized
                continue;
            }
            if (!$commit) {
                $this->logSection('optimize-pdfs', "  do#{$r->id} {$r->name} {$mb}MB -> would optimize");
                continue;
            }

            $out = ahgWebPdf::optimize($abs, $dpi);
            if (!$out) {
                $this->logSection('optimize-pdfs', "  do#{$r->id} {$r->name} - optimize FAILED (see error log)", null, 'ERROR');
                $skipped++;
                continue;
            }
            $newBytes = filesize($out);
            if ($newBytes > ($r->byte_size * $maxRatio)) {
                $this->logSection('optimize-pdfs', "  do#{$r->id} {$r->name} {$mb}MB -> ".round($newBytes / 1048576, 1).'MB not small enough, skip', null, 'COMMENT');
                ahgWebPdf::cleanupDirOf($out);
                $skipped++;
                continue;
            }
            if (!@copy($out, $sib)) {
                $this->logSection('optimize-pdfs', "  do#{$r->id} - could not write ".basename($sib), null, 'ERROR');
                ahgWebPdf::cleanupDirOf($out);
                $skipped++;
                continue;
            }
            ahgWebPdf::cleanupDirOf($out);
            @chmod($sib, 0664);
            @chown($sib, 'www-data');
            @chgrp($sib, 'www-data');
            $this->logSection('optimize-pdfs', "  do#{$r->id} {$r->name} {$mb}MB -> ".basename($sib).' '.round(filesize($sib) / 1048576, 2).'MB');
            $done++;
        }

        $this->logSection('optimize-pdfs', "Done. {$done} optimized, {$skipped} skipped. Masters untouched.");

        return 0;
    }
}
