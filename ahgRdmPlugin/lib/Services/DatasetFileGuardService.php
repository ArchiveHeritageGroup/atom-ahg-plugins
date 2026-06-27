<?php

/**
 * @author    Johan Pieterse <johan@theahg.co.za>
 * @copyright The Archive and Heritage Group (Pty) Ltd
 * @license   AGPL-3.0-or-later
 */

namespace AhgRdm\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Byte-level enforcement for restricted datasets (#176, parity heratio#1347).
 *
 * AtoM serves digital_object files as STATIC files via nginx from /uploads, so a
 * guessed raw URL would bypass the ODRL catalogue gate. When a dataset is
 * restricted/embargoed this service MOVES its digital_object files out of the
 * public /uploads tree into a non-web-served protected directory and records the
 * mapping in rdm_protected_object. The authed download controller then serves
 * them via X-Accel-Redirect after an ODRL check. release() moves them back.
 *
 * Protected store + X-Accel prefix are configurable (no hardcoded paths):
 *   ahg_settings / sfConfig 'app_rdm_protected_dir'   (default below)
 *   ahg_settings / sfConfig 'app_rdm_protected_url'    (internal nginx location)
 */
class DatasetFileGuardService
{
    public function protectedDir(): string
    {
        return rtrim((string) \sfConfig::get('app_rdm_protected_dir', '/mnt/nas/heratio/rdm-protected'), '/');
    }

    public function accelPrefix(): string
    {
        return '/' . trim((string) \sfConfig::get('app_rdm_protected_url', '/rdm-protected'), '/');
    }

    /** Container IO + every deposited file's child IO. */
    private function datasetIoIds(int $datasetId): array
    {
        $container = (int) DB::table('rdm_dataset')->where('id', $datasetId)->value('io_parent_id');
        $ids = DB::table('rdm_dataset_file')->where('dataset_id', $datasetId)->pluck('io_id')->map(fn ($v) => (int) $v)->all();
        if ($container) {
            array_unshift($ids, $container);
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** Absolute on-disk path of a digital_object (web-relative path + name). */
    private function publicAbsPath(object $do): ?string
    {
        if (empty($do->path)) {
            return null;
        }
        $webDir = rtrim((string) \sfConfig::get('sf_web_dir'), '/');

        return $webDir . '/' . ltrim((string) $do->path, '/') . ($do->name ?? '');
    }

    /**
     * Move every digital_object of the dataset's IOs out of the public tree.
     * Idempotent: already-protected DOs are skipped.
     *
     * @return int number of files relocated
     */
    public function protect(int $datasetId): int
    {
        $ioIds = $this->datasetIoIds($datasetId);
        if (!$ioIds) {
            return 0;
        }

        $moved = 0;
        $dos = DB::table('digital_object')->whereIn('object_id', $ioIds)->get();
        foreach ($dos as $do) {
            if (DB::table('rdm_protected_object')->where('do_id', $do->id)->exists()) {
                continue; // already protected
            }
            $src = $this->publicAbsPath($do);
            if (!$src || !is_file($src)) {
                continue;
            }

            $targetDir = $this->protectedDir() . '/' . (int) $do->id;
            if (!is_dir($targetDir) && !@mkdir($targetDir, 0750, true) && !is_dir($targetDir)) {
                continue;
            }
            $target = $targetDir . '/' . basename((string) $do->name);

            if (!$this->moveFile($src, $target)) {
                continue;
            }

            DB::table('rdm_protected_object')->insert([
                'dataset_id'     => $datasetId,
                'io_id'          => (int) $do->object_id,
                'do_id'          => (int) $do->id,
                'original_path'  => '/' . ltrim((string) $do->path, '/') . ($do->name ?? ''),
                'protected_path' => $target,
                'moved_at'       => date('Y-m-d H:i:s'),
            ]);
            $moved++;
        }

        return $moved;
    }

    /**
     * Move protected files back to their public /uploads location and clear the
     * map (called on open release).
     *
     * @return int number of files restored
     */
    public function release(int $datasetId): int
    {
        $webDir = rtrim((string) \sfConfig::get('sf_web_dir'), '/');
        $restored = 0;

        foreach (DB::table('rdm_protected_object')->where('dataset_id', $datasetId)->get() as $row) {
            $dest = $webDir . '/' . ltrim((string) $row->original_path, '/');
            $destDir = dirname($dest);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            if (is_file($row->protected_path)) {
                $this->moveFile($row->protected_path, $dest);
            }
            DB::table('rdm_protected_object')->where('id', $row->id)->delete();
            $restored++;
        }

        return $restored;
    }

    /** Protected absolute path for a digital_object, or null when not protected. */
    public function protectedPathForDo(int $doId): ?string
    {
        $p = DB::table('rdm_protected_object')->where('do_id', $doId)->value('protected_path');

        return $p ?: null;
    }

    /** X-Accel-Redirect internal URI for a protected DO (mirrors protectedDir layout). */
    public function accelUri(int $doId, string $name): string
    {
        return $this->accelPrefix() . '/' . $doId . '/' . rawurlencode(basename($name));
    }

    /** rename() with a cross-filesystem copy+unlink fallback (the NAS mount). */
    private function moveFile(string $src, string $dest): bool
    {
        if (@rename($src, $dest)) {
            return true;
        }
        if (@copy($src, $dest)) {
            @unlink($src);

            return true;
        }

        return false;
    }
}
