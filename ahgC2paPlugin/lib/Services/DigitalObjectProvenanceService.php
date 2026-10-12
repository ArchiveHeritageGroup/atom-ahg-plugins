<?php
/**
 * PSIS / AtoM-AHG - auto-provenance for digital objects.
 *
 * Symfony 1.4 port of Heratio's AhgC2pa\Services\DigitalObjectProvenanceService.
 * Turns a digital_object row into a signed digitisation-provenance record:
 * resolves the master on disk, decides whether it is worth signing, records
 * it, and never double-records. Shared by the per-request create watcher
 * (DigitalObjectCreateWatcher), the c2pa:provenance-backfill task and the
 * download-with-credentials action.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

final class DigitalObjectProvenanceService
{
    /** digital_object.usage_id for a master file (taxonomy 47). */
    public const USAGE_MASTER = 140;

    public function __construct(private ProvenanceRecordService $service)
    {
    }

    public function provenance(): ProvenanceRecordService
    {
        return $this->service;
    }

    /**
     * Record digitisation provenance for one digital object when it is a master
     * worth signing and has no record yet. Returns the new provenance row id,
     * or null when nothing was recorded. Never throws.
     *
     * Unlike Heratio, an unsigned record is NOT written when signing is
     * unavailable: the idempotency check would then stop a later run (with the
     * key readable) from ever signing that object.
     */
    public function recordForDigitalObject(int $digitalObjectId, ?string $reason = null): ?int
    {
        try {
            if ($digitalObjectId <= 0 || !ProvenanceRecordService::tableExists('ahg_c2pa_provenance')) {
                return null;
            }
            if (!$this->service->capability()['can_sign_manifest']) {
                return null;
            }

            $do = self::loadRow($digitalObjectId);
            if ($do === null || empty($do->object_id) || !self::isSignableMaster($do)) {
                return null;
            }

            if (DB::table('ahg_c2pa_provenance')->where('digital_object_id', $digitalObjectId)->exists()) {
                return null;
            }

            // Actor / repository images also live in digital_object; provenance
            // records are keyed to descriptions only.
            if (!DB::table('information_object')->where('id', (int) $do->object_id)->exists()) {
                return null;
            }

            return $this->service->record((int) $do->object_id, [
                'digital_object_id' => (int) $do->id,
                'captured_by'       => null,
                'capture_software'  => 'AtoM-AHG ingest',
                'notes'             => $reason ?? 'Auto-recorded on digital-object ingest',
                'asset_path'        => self::resolveAssetPath($do),
            ]);
        } catch (Throwable $e) {
            error_log('[c2pa] auto-provenance for digital object ' . $digitalObjectId . ' failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Record provenance for every master created after $afterId (the highest
     * digital_object.id seen at request start). Bounded by $cap so a large
     * upload batch cannot hold a request open; the remainder is picked up by
     * c2pa:provenance-backfill.
     *
     * @return int number of records written
     */
    public function recordCreatedAfter(int $afterId, int $cap): int
    {
        try {
            $ids = DB::table('digital_object')
                ->where('id', '>', $afterId)
                ->whereNull('parent_id')
                ->orderBy('id')
                ->limit($cap)
                ->pluck('id')
                ->all();
        } catch (Throwable $e) {
            error_log('[c2pa] new digital object scan failed: ' . $e->getMessage());

            return 0;
        }

        $written = 0;
        foreach ($ids as $id) {
            if ($this->recordForDigitalObject((int) $id, 'Auto-recorded on digital-object create') !== null) {
                $written++;
            }
        }

        return $written;
    }

    /**
     * Resolve a digital object's master for the download-with-credentials path.
     *
     * @return array{path:string, name:string, mime:?string, object_id:int}|null
     */
    public function resolveMasterForDownload(int $digitalObjectId): ?array
    {
        try {
            $do = self::loadRow($digitalObjectId);
            if ($do === null || preg_match('#^(https?|ftp)://#i', (string) ($do->path ?? ''))) {
                return null;
            }

            $resolved = self::resolveAssetPath($do);
            if ($resolved === null) {
                return null;
            }

            $name = (string) ($do->name ?? '');

            return [
                'path'      => $resolved,
                'name'      => $name !== '' ? $name : basename($resolved),
                'mime'      => (string) ($do->mime_type ?? '') !== '' ? (string) $do->mime_type : null,
                'object_id' => (int) ($do->object_id ?? 0),
            ];
        } catch (Throwable $e) {
            error_log('[c2pa] resolveMasterForDownload failed for ' . $digitalObjectId . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * A signable master: a parentless row (AtoM masters carry no parent_id;
     * reference and thumbnail derivatives do) that is not an external URL link.
     */
    public static function isSignableMaster(object $do): bool
    {
        if (!empty($do->parent_id)) {
            return false;
        }

        return !preg_match('#^(https?|ftp)://#i', (string) ($do->path ?? ''));
    }

    /**
     * Resolve a digital object's file on disk. AtoM stores path as a web path
     * ("/uploads/r/<repo>/a/b/c/<hash>/") under sf_web_dir, with the file name
     * separate. Returns null when no readable file is found.
     */
    public static function resolveAssetPath(object $do): ?string
    {
        $path = (string) ($do->path ?? '');
        $name = (string) ($do->name ?? '');
        if ($path === '' && $name === '') {
            return null;
        }

        $bases = [];
        if (class_exists('sfConfig')) {
            foreach (['sf_web_dir', 'sf_root_dir'] as $key) {
                $dir = (string) \sfConfig::get($key, '');
                if ($dir !== '') {
                    $bases[] = rtrim($dir, '/');
                }
            }
        }
        $bases = array_unique($bases);

        $candidates = [];
        foreach ($bases as $base) {
            $candidates[] = $base . '/' . ltrim($path . $name, '/');
            $candidates[] = $base . '/' . ltrim($path, '/');
        }
        $candidates[] = $path . $name;

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_file($candidate) && is_readable($candidate)) {
                // Never resolve outside the AtoM tree via a crafted ../ path.
                if (str_contains($candidate, '/../')) {
                    continue;
                }

                return $candidate;
            }
        }

        return null;
    }

    public static function loadRow(int $digitalObjectId): ?object
    {
        if ($digitalObjectId <= 0) {
            return null;
        }

        return DB::table('digital_object')
            ->where('id', $digitalObjectId)
            ->first(['id', 'object_id', 'usage_id', 'mime_type', 'name', 'path', 'parent_id', 'media_type_id']);
    }
}
