<?php

/**
 * ahgWebPdf - web-optimized PDF helper for AtoM-AHG.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * Email: johan@plainsailingisystems.co.za
 *
 * This file is part of the AtoM-AHG plugins.
 *
 * Twin of the Heratio (Laravel) ahg:optimize-pdfs feature. Large PDF masters
 * (50-200MB+ scans) open slowly because they are not linearized and each page
 * is a full-resolution image. This generates a downsampled + linearized
 * "web" sibling next to the master on disk (e.g. 200MB -> a few MB) and the
 * digital-object viewer points its click-through link at that sibling instead
 * of the master, so the document opens page-1-fast. The master is never
 * touched and stays the download/preservation copy.
 *
 * Plugin-only: no AtoM base (apps/qubit) changes. Needs ghostscript + qpdf on
 * the host (the optimisation step no-ops cleanly when they are absent).
 */
class ahgWebPdf
{
    /** Suffix that marks an optimized web sibling on disk. */
    const SUFFIX = '.web.pdf';

    /** True when both Ghostscript and qpdf are on PATH. */
    public static function toolsAvailable()
    {
        return self::bin('gs') !== null && self::bin('qpdf') !== null;
    }

    /**
     * The web-sibling absolute disk path for a master file path
     * (e.g. /.../uploads/x/doc.pdf -> /.../uploads/x/doc.web.pdf).
     */
    public static function siblingPath($masterAbs)
    {
        $dir = dirname($masterAbs);
        $base = pathinfo($masterAbs, PATHINFO_FILENAME);

        return $dir.'/'.$base.self::SUFFIX;
    }

    /** The public /uploads URL of the web sibling given a master's web path + name. */
    public static function siblingUrl($webPath, $masterName)
    {
        $base = pathinfo((string) $masterName, PATHINFO_FILENAME);

        return rtrim((string) $webPath, '/').'/'.$base.self::SUFFIX;
    }

    /**
     * Resolve the master PDF QubitDigitalObject for whatever the show
     * component handed us (the master DO itself, a derivative whose parent is
     * the master, or an information object), or null when it is not a PDF.
     */
    public static function resolveMaster($resource)
    {
        if (!$resource) {
            return null;
        }

        $master = null;
        if ($resource instanceof QubitDigitalObject) {
            if (QubitTerm::MASTER_ID == $resource->usageId) {
                $master = $resource;
            } elseif ($resource->parentId) {
                $master = QubitDigitalObject::getById($resource->parentId);
            } else {
                $master = $resource;   // a top-level DO with no parent: treat as master
            }
        } elseif ($resource instanceof QubitInformationObject) {
            $dos = $resource->digitalObjectsRelatedByobjectId;
            if (isset($dos[0])) {
                $master = $dos[0];
            }
        }

        if ($master && 'application/pdf' === $master->mimeType) {
            return $master;
        }

        return null;
    }

    /**
     * Click-through link for a PDF representation: the web sibling when it
     * exists on disk, otherwise the supplied fallback (unchanged behaviour).
     * Safe to call for any media type - non-PDF / no-sibling returns $fallback.
     */
    public static function linkFor($resource, $fallback)
    {
        $master = self::resolveMaster($resource);
        if (!$master) {
            return $fallback;
        }
        $abs = sfConfig::get('sf_web_dir').$master->getPath().$master->getName();
        $sib = self::siblingPath($abs);
        if (@is_file($sib)) {
            return self::siblingUrl($master->getPath(), $master->getName());
        }

        return $fallback;
    }

    /**
     * Build a downsampled + linearized copy of $srcAbs at $dpi.
     * Returns an absolute temp-file path on success, or null on failure.
     * The caller owns the returned file.
     */
    public static function optimize($srcAbs, $dpi = 200)
    {
        if (!is_file($srcAbs) || !self::toolsAvailable()) {
            return null;
        }
        $dpi = max(72, min(600, (int) $dpi));
        $mono = min(600, (int) round($dpi * 1.5));
        $work = sys_get_temp_dir().'/ahg-pdfopt-'.bin2hex(random_bytes(6));
        @mkdir($work, 0775, true);
        $down = $work.'/down.pdf';
        $out = $work.'/web.pdf';
        $gs = self::bin('gs');
        $qpdf = self::bin('qpdf');

        $gsCmd = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.5 -dPDFSETTINGS=/ebook '
            .'-dNOPAUSE -dBATCH -dQUIET -dAutoRotatePages=/None -dDetectDuplicateImages=true '
            .'-dDownsampleColorImages=true -dColorImageResolution=%d -dColorImageDownsampleThreshold=1.0 '
            .'-dDownsampleGrayImages=true -dGrayImageResolution=%d -dGrayImageDownsampleThreshold=1.0 '
            .'-dDownsampleMonoImages=true -dMonoImageResolution=%d '
            .'-sOutputFile=%s %s 2>&1',
            escapeshellcmd($gs), $dpi, $dpi, $mono, escapeshellarg($down), escapeshellarg($srcAbs)
        );
        exec($gsCmd, $gsOut, $gsRc);
        if (0 !== $gsRc || !is_file($down) || filesize($down) < 1024) {
            error_log('[ahgWebPdf] ghostscript failed rc='.$gsRc.' src='.$srcAbs.' '.implode(' | ', array_slice((array) $gsOut, -2)));
            self::cleanup($work);

            return null;
        }

        $qCmd = sprintf('%s --linearize %s %s 2>&1', escapeshellcmd($qpdf), escapeshellarg($down), escapeshellarg($out));
        exec($qCmd, $qOut, $qRc);
        // qpdf returns 3 for warnings-only (output still valid).
        if ((0 !== $qRc && 3 !== $qRc) || !is_file($out) || filesize($out) < 1024) {
            if (is_file($down) && filesize($down) >= 1024) {
                @unlink($out);

                return $down;   // downsample-only fallback; still a big win
            }
            error_log('[ahgWebPdf] qpdf linearize failed rc='.$qRc.' src='.$srcAbs);
            self::cleanup($work);

            return null;
        }
        @unlink($down);

        return $out;
    }

    /** Remove the temp work dir that held an optimize() result. */
    public static function cleanupDirOf($file)
    {
        self::cleanup(dirname($file));
    }

    private static function cleanup($dir)
    {
        if (!$dir || !is_dir($dir) || false === strpos($dir, 'ahg-pdfopt-')) {
            return;
        }
        foreach (glob($dir.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }

    private static function bin($name)
    {
        $p = trim((string) @shell_exec('command -v '.escapeshellarg($name).' 2>/dev/null'));

        return '' !== $p ? $p : null;
    }
}
