<?php

namespace AtomExtensions\SharePoint\Services;

/**
 * Private working storage for files fetched from SharePoint.
 *
 * Same rule as ahgIngestPlugin's IngestService::workDir(), so a SharePoint
 * download lands in the ingest session folder the wizard reads: setting
 * ingest_work_dir, else /var/lib/ahg-ingest/<instance>. Not under uploads/,
 * which the web root serves without a sign-in.
 */
final class SharePointWorkDir
{
    public static function path(string $sub = ''): string
    {
        $base = class_exists(\AtomExtensions\Services\AhgSettingsService::class)
            ? trim((string) \AtomExtensions\Services\AhgSettingsService::get('ingest_work_dir', ''))
            : '';
        if ('' === $base) {
            $base = '/var/lib/ahg-ingest/'.basename(rtrim((string) \sfConfig::get('sf_root_dir'), '/'));
        }
        $dir = rtrim($base, '/').('' !== $sub ? '/'.$sub : '');
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new \RuntimeException("Cannot create the ingest work folder {$dir}. Create it, owned by www-data.");
        }

        return $dir;
    }
}
