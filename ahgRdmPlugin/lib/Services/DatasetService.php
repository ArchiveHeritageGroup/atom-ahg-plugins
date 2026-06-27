<?php

/**
 * @author    Johan Pieterse <johan@theahg.co.za>
 * @copyright The Archive and Heritage Group (Pty) Ltd
 * @license   AGPL-3.0-or-later
 */

namespace AhgRdm\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Dataset orchestration — reverse port of Heratio ahg-rdm DatasetService
 * (heratio#1338). The only net-new logic in ahgRdmPlugin; everything else wires
 * into existing AHG plugins.
 *
 * A Dataset is backed by a container information_object (io_parent_id). Files
 * are deposited as child IOs under it, each with a master digital_object — the
 * same shape ahgIngestPlugin produces — so digital_object stays the single
 * storage source of truth; no bespoke file handling here.
 */
class DatasetService
{
    /** AtoM root information_object id (datasets hang as top-level containers). */
    private const ROOT_IO = 1;

    /**
     * Create a Dataset: a container information_object + the rdm_dataset row.
     *
     * @return int new rdm_dataset.id
     */
    public function create(string $title, ?string $description, ?int $projectId, ?int $userId): int
    {
        // Container IO via the canonical create (handles object/io/i18n/slug/
        // nested-set). sourceStandard tags it as RDM-owned.
        $ioId = \AhgInformationObjectManage\Services\InformationObjectCrudService::create([
            'title'           => $title,
            'scopeAndContent' => $description,
            'parentId'        => self::ROOT_IO,
            'sourceStandard'  => 'rdm',
        ], 'en');

        $now = date('Y-m-d H:i:s');

        return (int) DB::table('rdm_dataset')->insertGetId([
            'project_id'   => $projectId,
            'io_parent_id' => (int) $ioId,
            'title'        => $title,
            'description'  => $description,
            'status'       => 'draft',
            'created_by'   => $userId,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    /**
     * Deposit files into a Dataset. Each staged file becomes a child IO + master
     * digital_object under the dataset's container IO; the (io_id, do_id) link is
     * recorded per file in rdm_dataset_file.
     *
     * @param  array<int,array{tmp_path:string,original_name:string}>  $files
     * @return array{stored:int, skipped:int}
     */
    public function deposit(int $datasetId, array $files, ?int $userId): array
    {
        $dataset = DB::table('rdm_dataset')->where('id', $datasetId)->first();
        if (!$dataset) {
            throw new \RuntimeException("Dataset {$datasetId} not found.");
        }

        $parentIoId = (int) $dataset->io_parent_id;
        $stored = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $tmpPath = $file['tmp_path'] ?? null;
            $original = $file['original_name'] ?? null;

            if (!$tmpPath || !$original || !is_file($tmpPath)) {
                $skipped++;
                continue;
            }

            // 1. Child IO for this file (title = filename stem), under the container.
            $childIoId = \AhgInformationObjectManage\Services\InformationObjectCrudService::create([
                'title'          => pathinfo($original, PATHINFO_FILENAME) ?: $original,
                'parentId'       => $parentIoId,
                'sourceStandard' => 'rdm',
            ], 'en');

            // 2. Attach the upload as a master digital_object.
            $doId = $this->attachDigitalObject((int) $childIoId, $tmpPath, $original);

            DB::table('rdm_dataset_file')->insert([
                'dataset_id'    => $datasetId,
                'io_id'         => (int) $childIoId,
                'do_id'         => $doId,
                'original_name' => $original,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $stored++;
        }

        DB::table('rdm_dataset')->where('id', $datasetId)->update([
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['stored' => $stored, 'skipped' => $skipped];
    }

    /**
     * Create a master digital_object on $ioId from $sourcePath using AtoM's
     * Propel model (handles store + derivatives). Falls back to a manual
     * hash-bucket import if the Propel save's post-hook (OpenSearch) crashes.
     *
     * @return int|null digital_object.id, or null on failure
     */
    private function attachDigitalObject(int $ioId, string $sourcePath, string $originalName): ?int
    {
        try {
            $do = new \QubitDigitalObject();
            $do->objectId = $ioId;
            $do->usageId = \QubitTerm::MASTER_ID ?? 140;
            $do->assets = [new \QubitAsset($originalName, file_get_contents($sourcePath))];
            $do->save();

            if ($do->id) {
                return (int) $do->id;
            }
        } catch (\Throwable $e) {
            // Propel may have created the DO before a post-save hook failed.
            if (isset($do) && !empty($do->id)) {
                return (int) $do->id;
            }
            $existing = DB::table('digital_object')->where('object_id', $ioId)->first();
            if ($existing) {
                return (int) $existing->id;
            }
        }

        return $this->manualImportDigitalObject($ioId, $sourcePath, $originalName);
    }

    /**
     * Last-resort digital_object import matching AtoM's uploads/r/<…>/<checksum>
     * path structure, used when the Propel save throws before completing.
     */
    private function manualImportDigitalObject(int $ioId, string $sourcePath, string $originalName): ?int
    {
        $webDir = \sfConfig::get('sf_web_dir');
        $checksum = hash_file('sha256', $sourcePath);
        $hashPath = substr($checksum, 0, 1) . '/' . substr($checksum, 1, 1) . '/'
                  . substr($checksum, 2, 1) . '/' . $checksum;

        $uploadsDir = $webDir . '/uploads/r/default/' . $hashPath;
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0755, true);
        }

        $destPath = $uploadsDir . '/' . basename($originalName);
        if (!@copy($sourcePath, $destPath)) {
            return null;
        }

        $relativePath = str_replace($webDir, '', $uploadsDir) . '/';

        $objectId = DB::table('object')->insertGetId([
            'class_name' => 'QubitDigitalObject',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        DB::table('digital_object')->insert([
            'id'            => $objectId,
            'object_id'     => $ioId,
            'usage_id'      => \QubitTerm::MASTER_ID ?? 140,
            'name'          => basename($originalName),
            'path'          => $relativePath,
            'mime_type'     => mime_content_type($sourcePath) ?: 'application/octet-stream',
            'byte_size'     => filesize($sourcePath),
            'checksum_type' => 'sha256',
            'checksum'      => $checksum,
        ]);

        return (int) $objectId;
    }

    /** A dataset row + its project title. */
    public function get(int $datasetId): ?object
    {
        return DB::table('rdm_dataset as d')
            ->leftJoin('research_project as p', 'p.id', '=', 'd.project_id')
            ->where('d.id', $datasetId)
            ->select('d.*', 'p.title as project_title')
            ->first();
    }

    /** Files deposited into a dataset. */
    public function files(int $datasetId): array
    {
        return DB::table('rdm_dataset_file as f')
            ->where('f.dataset_id', $datasetId)
            ->orderBy('f.id')
            ->get()
            ->all();
    }

    /** All datasets (most recent first) for the index, with project + file count. */
    public function list(): array
    {
        return DB::table('rdm_dataset as d')
            ->leftJoin('research_project as p', 'p.id', '=', 'd.project_id')
            ->leftJoin('rdm_dataset_file as f', 'f.dataset_id', '=', 'd.id')
            ->groupBy('d.id', 'd.title', 'd.status', 'd.created_at', 'p.title')
            ->orderByDesc('d.id')
            ->select('d.id', 'd.title', 'd.status', 'd.created_at', 'p.title as project_title', DB::raw('COUNT(f.id) as file_count'))
            ->get()
            ->all();
    }
}
