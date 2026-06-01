<?php

namespace AhgScanPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * WatchedFolderService — ahgScanPlugin.
 *
 * CRUD + helpers for scan_folder rows. Each folder binds one-to-one with an
 * ahgIngestPlugin ingest_session (session_kind='watched_folder') that holds
 * the processing config (sector, standard, parent, repository, derivatives,
 * AI flags, OAIS output). The scanner stages detected files as ingest_row
 * records on that session and launches ingest:commit to process them.
 *
 * Mirrors Heratio ahg-scan's WatchedFolderService, adapted to AtoM's
 * row-based ingest pipeline (ingest_row + ingest:commit) instead of
 * Heratio's per-file ProcessScanFile job.
 */
class WatchedFolderService
{
    /**
     * List all folders joined with their backing session for display.
     *
     * @return array<int, object>
     */
    public function listAll(): array
    {
        return DB::table('scan_folder as sf')
            ->leftJoin('ingest_session as s', 'sf.ingest_session_id', '=', 's.id')
            ->select(
                'sf.*',
                's.title as session_title',
                's.sector',
                's.standard',
                's.parent_id',
                's.repository_id',
                's.status as session_status'
            )
            ->orderBy('sf.label')
            ->get()
            ->all();
    }

    public function find(int $id): ?object
    {
        return DB::table('scan_folder')->where('id', $id)->first();
    }

    public function findByCode(string $code): ?object
    {
        return DB::table('scan_folder')->where('code', $code)->first();
    }

    /**
     * @return array<int, object>
     */
    public function enabledFolders(): array
    {
        return DB::table('scan_folder')
            ->where('enabled', 1)
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Create a scan_folder and its backing ingest_session in one transaction.
     *
     * @param array $data code, label, path, layout, parent_id, repository_id,
     *                    sector, standard, auto_commit, derivative_*, process_*,
     *                    disposition_success, disposition_failure,
     *                    processed_path, failed_path, min_quiet_seconds, enabled
     * @param int   $userId creator
     *
     * @return int scan_folder.id
     */
    public function create(array $data, int $userId): int
    {
        return DB::connection()->transaction(function () use ($data, $userId) {
            $now = date('Y-m-d H:i:s');

            $sessionRow = [
                'user_id' => $userId,
                'title' => ($data['label'] ?? $data['code']) . ' (watched folder)',
                'entity_type' => 'description',
                'sector' => $data['sector'] ?? 'archive',
                'standard' => $data['standard'] ?? 'isadg',
                'repository_id' => $data['repository_id'] ?? null,
                'parent_id' => $data['parent_id'] ?? null,
                'parent_placement' => !empty($data['parent_id']) ? 'existing' : 'top_level',
                'status' => 'configure',
                'output_create_records' => 1,
                'derivative_thumbnails' => (int) ($data['derivative_thumbnails'] ?? 1),
                'derivative_reference' => (int) ($data['derivative_reference'] ?? 1),
                'process_virus_scan' => (int) ($data['process_virus_scan'] ?? 1),
                'process_ocr' => (int) ($data['process_ocr'] ?? 0),
                'process_ner' => (int) ($data['process_ner'] ?? 0),
                'output_generate_sip' => (int) ($data['output_generate_sip'] ?? 0),
                'output_generate_aip' => (int) ($data['output_generate_aip'] ?? 0),
                'output_generate_dip' => (int) ($data['output_generate_dip'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // session_kind / source_ref are added by this plugin's install.sql.
            if ($this->sessionHasColumn('session_kind')) {
                $sessionRow['session_kind'] = 'watched_folder';
            }
            if ($this->sessionHasColumn('source_ref')) {
                $sessionRow['source_ref'] = $data['code'];
            }

            $sessionId = DB::table('ingest_session')->insertGetId($sessionRow);

            return DB::table('scan_folder')->insertGetId([
                'code' => $data['code'],
                'label' => $data['label'] ?? $data['code'],
                'path' => rtrim($data['path'], '/'),
                'layout' => $data['layout'] ?? 'flat',
                'ingest_session_id' => $sessionId,
                'disposition_success' => $data['disposition_success'] ?? 'move',
                'disposition_failure' => $data['disposition_failure'] ?? 'quarantine',
                'processed_path' => !empty($data['processed_path']) ? rtrim($data['processed_path'], '/') : null,
                'failed_path' => !empty($data['failed_path']) ? rtrim($data['failed_path'], '/') : null,
                'min_quiet_seconds' => (int) ($data['min_quiet_seconds'] ?? 10),
                'auto_commit' => (int) ($data['auto_commit'] ?? 1),
                'enabled' => (int) ($data['enabled'] ?? 1),
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function update(int $id, array $data): void
    {
        DB::connection()->transaction(function () use ($id, $data) {
            $folder = $this->find($id);
            if (!$folder) {
                throw new \RuntimeException("scan_folder {$id} not found");
            }

            DB::table('scan_folder')->where('id', $id)->update([
                'label' => $data['label'] ?? $folder->label,
                'path' => rtrim($data['path'] ?? $folder->path, '/'),
                'layout' => $data['layout'] ?? $folder->layout,
                'disposition_success' => $data['disposition_success'] ?? $folder->disposition_success,
                'disposition_failure' => $data['disposition_failure'] ?? $folder->disposition_failure,
                'processed_path' => array_key_exists('processed_path', $data)
                    ? ($data['processed_path'] ? rtrim($data['processed_path'], '/') : null)
                    : $folder->processed_path,
                'failed_path' => array_key_exists('failed_path', $data)
                    ? ($data['failed_path'] ? rtrim($data['failed_path'], '/') : null)
                    : $folder->failed_path,
                'min_quiet_seconds' => (int) ($data['min_quiet_seconds'] ?? $folder->min_quiet_seconds),
                'auto_commit' => isset($data['auto_commit']) ? (int) $data['auto_commit'] : $folder->auto_commit,
                'enabled' => isset($data['enabled']) ? (int) $data['enabled'] : $folder->enabled,
            ]);

            if (!empty($folder->ingest_session_id)) {
                $sessionUpdates = array_filter([
                    'sector' => $data['sector'] ?? null,
                    'standard' => $data['standard'] ?? null,
                    'parent_id' => $data['parent_id'] ?? null,
                    'repository_id' => $data['repository_id'] ?? null,
                    'derivative_thumbnails' => isset($data['derivative_thumbnails']) ? (int) $data['derivative_thumbnails'] : null,
                    'derivative_reference' => isset($data['derivative_reference']) ? (int) $data['derivative_reference'] : null,
                    'process_virus_scan' => isset($data['process_virus_scan']) ? (int) $data['process_virus_scan'] : null,
                    'process_ocr' => isset($data['process_ocr']) ? (int) $data['process_ocr'] : null,
                    'process_ner' => isset($data['process_ner']) ? (int) $data['process_ner'] : null,
                ], fn ($v) => $v !== null);

                if (!empty($sessionUpdates)) {
                    if (!empty($data['parent_id'])) {
                        $sessionUpdates['parent_placement'] = 'existing';
                    }
                    $sessionUpdates['updated_at'] = date('Y-m-d H:i:s');
                    DB::table('ingest_session')->where('id', $folder->ingest_session_id)->update($sessionUpdates);
                }
            }
        });
    }

    /**
     * Remove a folder. Its backing session + ingest history is kept for audit
     * (marked cancelled), mirroring Heratio's behaviour.
     */
    public function delete(int $id): void
    {
        $folder = $this->find($id);
        if (!$folder) {
            return;
        }
        DB::connection()->transaction(function () use ($folder) {
            DB::table('scan_event')->where('folder_id', $folder->id)->delete();
            DB::table('scan_folder')->where('id', $folder->id)->delete();
            if (!empty($folder->ingest_session_id)) {
                DB::table('ingest_session')->where('id', $folder->ingest_session_id)
                    ->update(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')]);
            }
        });
    }

    public function touchScanned(int $folderId): void
    {
        DB::table('scan_folder')->where('id', $folderId)
            ->update(['last_scanned_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Resolve the processed (success) disposition directory for a folder.
     */
    public function processedDir(object $folder): string
    {
        if (!empty($folder->processed_path)) {
            return rtrim($folder->processed_path, '/');
        }

        return rtrim($folder->path, '/') . '/.processed';
    }

    /**
     * Resolve the failed (quarantine) disposition directory for a folder.
     */
    public function failedDir(object $folder): string
    {
        if (!empty($folder->failed_path)) {
            return rtrim($folder->failed_path, '/');
        }

        return rtrim($folder->path, '/') . '/.failed';
    }

    /**
     * Recent scan_event rows for a folder (audit history).
     *
     * @return array<int, object>
     */
    public function recentEvents(int $folderId, int $limit = 20): array
    {
        return DB::table('scan_event')
            ->where('folder_id', $folderId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function sessionHasColumn(string $column): bool
    {
        try {
            return DB::schema()->hasColumn('ingest_session', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
