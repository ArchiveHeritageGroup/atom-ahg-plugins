<?php

/**
 * OcflService - central wiring for the OCFL preservation storage layer.
 *
 * Responsibilities:
 *   - resolve OCFL configuration (storage-root path, layout, digest algorithm)
 *     from ahg_settings with safe defaults
 *   - build a StorageRoot bound to a local-filesystem adapter
 *   - resolve AtoM information_object ids to stable OCFL object ids
 *   - locate digital_object files on disk the AtoM way (sf_root_dir + path + name)
 *   - snapshot an IO's digital objects into OCFL (new object or new version)
 *   - maintain the ahg_ocfl_object_map IO -> OCFL object lookup table
 *
 * Settings (group "ocfl"):
 *   ocfl_storage_root     absolute path to the OCFL storage root
 *   ocfl_storage_layout   flat-id | pairtree | hashed-n-tuple
 *   ocfl_digest_algorithm sha512 | sha256
 *   ocfl_export_path      absolute path for ocfl:export tarballs
 *
 * Mirrors the Heratio ahg-ocfl Console commands' service logic.
 *
 * @copyright  The Archive and Heritage Group (Pty) Ltd
 * @license    AGPL-3.0-or-later
 */

namespace AtomExtensions\Ocfl\Services;

use AtomExtensions\Ocfl\Layout\ContentAddressing;
use AtomExtensions\Ocfl\Layout\OcflObject;
use AtomExtensions\Ocfl\Layout\StorageLayout;
use AtomExtensions\Ocfl\Layout\StorageRoot;
use AtomExtensions\Ocfl\Storage\OcflStorageAdapter;
use Illuminate\Database\Capsule\Manager as DB;

class OcflService
{
    private ?StorageRoot $storageRoot = null;

    public function rootDir(): string
    {
        return (string) \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive');
    }

    public function setting(string $key, string $default): string
    {
        if (class_exists('\AtomExtensions\Services\AhgSettingsService')) {
            try {
                $val = \AtomExtensions\Services\AhgSettingsService::get($key, $default);
                if (null !== $val && '' !== $val) {
                    return (string) $val;
                }
            } catch (\Throwable $e) {
                // fall through to default
            }
        }

        return $default;
    }

    public function storageRootPath(): string
    {
        return $this->setting('ocfl_storage_root', $this->rootDir() . '/ocfl');
    }

    public function layout(): string
    {
        return $this->setting('ocfl_storage_layout', StorageLayout::FLAT_ID);
    }

    public function digestAlgorithm(): string
    {
        return $this->setting('ocfl_digest_algorithm', ContentAddressing::ALG_SHA512);
    }

    public function exportPath(): string
    {
        return $this->setting('ocfl_export_path', $this->rootDir() . '/cache/ocfl-exports');
    }

    /** Build (and cache) the StorageRoot for the configured storage root path. */
    public function storageRoot(?string $overridePath = null): StorageRoot
    {
        if (null !== $overridePath && '' !== $overridePath) {
            $adapter = new OcflStorageAdapter($overridePath);

            return new StorageRoot($adapter, $this->layout(), $this->digestAlgorithm());
        }

        if (null === $this->storageRoot) {
            $adapter = new OcflStorageAdapter($this->storageRootPath());
            $this->storageRoot = new StorageRoot($adapter, $this->layout(), $this->digestAlgorithm());
        }

        return $this->storageRoot;
    }

    /** Stable, namespaced OCFL object id for an AtoM information object. */
    public function objectIdForIo(int $ioId): string
    {
        // Prefer a previously-recorded id (preserves identity across layout changes).
        try {
            $row = DB::table('ahg_ocfl_object_map')->where('information_object_id', $ioId)->first();
            if (null !== $row && !empty($row->ocfl_object_id)) {
                return (string) $row->ocfl_object_id;
            }
        } catch (\Throwable $e) {
            // map table may not exist yet
        }

        return "urn:atom:io:{$ioId}";
    }

    /**
     * Load every digital_object row tied to an information object.
     *
     * @return array<int, object>
     */
    public function loadDigitalObjects(int $ioId): array
    {
        try {
            return DB::table('digital_object')
                ->where('object_id', $ioId)
                ->whereNotNull('path')
                ->whereNotNull('name')
                ->get()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Resolve a digital_object row to its absolute on-disk file path the AtoM
     * way: the DB path is relative to sf_root_dir (e.g. /uploads/r/.../hash/).
     */
    public function resolveFilePath(object $doRow): string
    {
        $path = (string) ($doRow->path ?? '');
        $name = (string) ($doRow->name ?? '');
        $rootDir = $this->rootDir();

        if ('' !== $path && '' !== $name) {
            return $rootDir . '/' . ltrim($path, '/') . $name;
        }

        return $rootDir . '/uploads/unknown_' . ($doRow->id ?? '0');
    }

    /**
     * Snapshot an information object's digital files into OCFL.
     *
     * @return array{status:string,object_id:?string,head:?string,staged:int,message:string}
     */
    public function ingestInformationObject(int $ioId, ?string $message, ?string $userName, ?string $userAddress): array
    {
        if ($ioId <= 0) {
            return ['status' => 'error', 'object_id' => null, 'head' => null, 'staged' => 0, 'message' => 'A positive information_object id is required'];
        }

        $digitalObjects = $this->loadDigitalObjects($ioId);
        if ([] === $digitalObjects) {
            return ['status' => 'empty', 'object_id' => null, 'head' => null, 'staged' => 0, 'message' => "No digital_object rows for information_object {$ioId}"];
        }

        $root = $this->storageRoot();
        $objectId = $this->objectIdForIo($ioId);
        $ocfl = OcflObject::fresh($objectId, $root->digester->algorithm);

        $staged = 0;
        $skipped = [];
        foreach ($digitalObjects as $do) {
            $local = $this->resolveFilePath($do);
            if (!is_file($local) || !is_readable($local)) {
                $skipped[] = $local;

                continue;
            }
            $logical = ltrim((string) $do->path, '/') . (string) $do->name;
            $ocfl->stageContent($logical, $local);
            ++$staged;
        }

        if (0 === $staged) {
            return ['status' => 'error', 'object_id' => $objectId, 'head' => null, 'staged' => 0, 'message' => "No readable digital_object files for IO {$ioId}"];
        }

        $msg = $message ?: "Ingest of information_object {$ioId} ({$staged} files)";
        $inventory = $root->write($ocfl, $msg, $userName, $userAddress);

        $this->upsertObjectMap($ioId, $inventory->id, $inventory->head);

        return [
            'status' => 'ok',
            'object_id' => $inventory->id,
            'head' => $inventory->head,
            'staged' => $staged,
            'message' => 'Wrote OCFL object ' . $inventory->id . ' head=' . $inventory->head
                . ' (' . $staged . ' files, alg=' . $inventory->digestAlgorithm . ')'
                . ([] === $skipped ? '' : ', skipped ' . count($skipped) . ' unreadable file(s)'),
        ];
    }

    public function upsertObjectMap(int $ioId, string $objectId, string $head): void
    {
        try {
            $now = date('Y-m-d H:i:s');
            $existing = DB::table('ahg_ocfl_object_map')->where('information_object_id', $ioId)->first();
            if (null === $existing) {
                DB::table('ahg_ocfl_object_map')->insert([
                    'information_object_id' => $ioId,
                    'ocfl_object_id' => $objectId,
                    'storage_root' => $this->storageRootPath(),
                    'head_version' => $head,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('ahg_ocfl_object_map')
                    ->where('information_object_id', $ioId)
                    ->update([
                        'ocfl_object_id' => $objectId,
                        'storage_root' => $this->storageRootPath(),
                        'head_version' => $head,
                        'updated_at' => $now,
                    ]);
            }
        } catch (\Throwable $e) {
            // Non-fatal: the OCFL write itself is the source of truth.
        }
    }

    /** Map an IO id to its recorded OCFL object id (falls back to the urn). */
    public function resolveObjectId(int $ioId): string
    {
        return $this->objectIdForIo($ioId);
    }

    /**
     * Export one OCFL object to a tar archive under the configured export path.
     *
     * @return array{status:string,path:?string,files:int,message:string}
     */
    public function exportObject(int $ioId): array
    {
        if (!class_exists('PharData')) {
            return ['status' => 'error', 'path' => null, 'files' => 0, 'message' => 'Export requires the PHP Phar extension (PharData), which is unavailable on this server'];
        }

        $root = $this->storageRoot();
        $objectId = $this->resolveObjectId($ioId);

        if (!$root->exists($objectId)) {
            return ['status' => 'error', 'path' => null, 'files' => 0, 'message' => "OCFL object for IO {$ioId} not found ({$objectId})"];
        }

        $exportDir = $this->exportPath();
        if (!is_dir($exportDir) && !@mkdir($exportDir, 0775, true) && !is_dir($exportDir)) {
            return ['status' => 'error', 'path' => null, 'files' => 0, 'message' => "Cannot create export directory {$exportDir}"];
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $objectId) ?: 'ocfl-object';
        $tarPath = rtrim($exportDir, '/') . '/' . $safeName . '.tar';

        $objectRoot = $root->objectRoot($objectId);
        $files = $root->adapter->files($objectRoot);
        if ([] === $files) {
            return ['status' => 'error', 'path' => null, 'files' => 0, 'message' => "OCFL object {$objectId} appears empty on disk"];
        }

        if (file_exists($tarPath)) {
            @unlink($tarPath);
        }

        $phar = new \PharData($tarPath);
        foreach ($files as $relPath) {
            $bytes = $root->adapter->get($relPath);
            $insideTar = $safeName . '/' . ltrim(substr($relPath, strlen($objectRoot)), '/');
            $phar->addFromString($insideTar, $bytes);
        }
        unset($phar);

        return ['status' => 'ok', 'path' => $tarPath, 'files' => count($files), 'message' => "Exported {$objectId} -> {$tarPath} (" . count($files) . ' files)'];
    }
}
