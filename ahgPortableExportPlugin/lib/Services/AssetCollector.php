<?php

namespace AhgPortableExportPlugin\Services;

/**
 * Collects digital object files (thumbnails, references, masters, PDFs)
 * for portable export.
 *
 * AtoM stores digital objects as:
 * - Master (usage_id=140): {atomRoot}{path}{name}
 * - Reference (usage_id=141): {atomRoot}{path}{name}_141.{ext} (same dir)
 * - Thumbnail (usage_id=142): {atomRoot}{path}{name}_142.{ext} (same dir)
 *
 * The path column already includes /uploads/ prefix.
 */
class AssetCollector
{
    /** @var string AtoM root directory */
    protected $atomRoot;

    /** @var callable|null Progress callback: fn(int $current, int $total) */
    protected $progressCallback;

    public function __construct(?string $atomRoot = null, ?callable $progressCallback = null)
    {
        $this->atomRoot = $atomRoot ?: \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive');
        $this->progressCallback = $progressCallback;
    }

    /**
     * Collect digital object files for the exported descriptions.
     *
     * @param array  $descriptions  Extracted descriptions with digital_objects
     * @param string $outputDir     Export output directory
     * @param array  $options       include_thumbnails, include_references, include_masters
     *
     * @return array{files: array, total_size: int, descriptions: array}
     */
    public function collect(array $descriptions, string $outputDir, array $options = []): array
    {
        $includeThumbnails = $options['include_thumbnails'] ?? true;
        $includeReferences = $options['include_references'] ?? true;
        $includeMasters = $options['include_masters'] ?? false;

        // Create output directories
        $objectsDir = $outputDir . '/objects';
        if ($includeThumbnails) {
            @mkdir($objectsDir . '/thumb', 0755, true);
        }
        if ($includeReferences) {
            @mkdir($objectsDir . '/ref', 0755, true);
        }
        if ($includeMasters) {
            @mkdir($objectsDir . '/master', 0755, true);
        }
        @mkdir($objectsDir . '/pdf', 0755, true);

        $manifest = [];
        $totalSize = 0;
        $totalFiles = $this->countFiles($descriptions);
        $processed = 0;

        foreach ($descriptions as &$desc) {
            if (empty($desc['digital_objects'])) {
                continue;
            }

            foreach ($desc['digital_objects'] as &$do) {
                // Resolve base directory: atomRoot + path (path already includes /uploads/)
                $baseDir = $this->resolveBaseDir($do);

                // Copy master / original
                if ($includeMasters && $baseDir && !empty($do['name'])) {
                    $masterPath = $baseDir . $do['name'];
                    if (file_exists($masterPath)) {
                        $destName = $this->safeFilename($do['id'], $do['name']);
                        $dest = $objectsDir . '/master/' . $destName;
                        if ($this->copyFile($masterPath, $dest)) {
                            $size = filesize($dest);
                            $manifest[] = [
                                'type' => 'master',
                                'object_id' => $desc['id'],
                                'digital_object_id' => $do['id'],
                                'filename' => $destName,
                                'size' => $size,
                                'checksum' => hash_file('sha256', $dest),
                            ];
                            $totalSize += $size;
                            $do['master_file'] = 'objects/master/' . $destName;
                        }
                    }
                }

                // Copy thumbnail (usage_id=142)
                if ($includeThumbnails && $baseDir) {
                    $thumbName = $do['thumbnail_name'] ?? null;
                    if (!$thumbName) {
                        // Fallback: derive name from master with _142 suffix
                        $thumbName = $this->deriveFilename($do['name'], 142);
                    }
                    if ($thumbName) {
                        $thumbPath = $baseDir . $thumbName;
                        if (file_exists($thumbPath)) {
                            $destName = $this->safeFilename($do['id'], $thumbName);
                            $dest = $objectsDir . '/thumb/' . $destName;
                            if ($this->copyFile($thumbPath, $dest)) {
                                $size = filesize($dest);
                                $manifest[] = [
                                    'type' => 'thumbnail',
                                    'object_id' => $desc['id'],
                                    'digital_object_id' => $do['id'],
                                    'filename' => $destName,
                                    'size' => $size,
                                    'checksum' => hash_file('sha256', $dest),
                                ];
                                $totalSize += $size;
                                $do['thumbnail_file'] = 'objects/thumb/' . $destName;
                            }
                        }
                    }
                }

                // Copy reference image (usage_id=141)
                if ($includeReferences && $baseDir) {
                    $refName = $do['reference_name'] ?? null;
                    if (!$refName) {
                        // Fallback: derive name from master with _141 suffix
                        $refName = $this->deriveFilename($do['name'], 141);
                    }
                    if ($refName) {
                        $refPath = $baseDir . $refName;
                        if (file_exists($refPath)) {
                            $destName = $this->safeFilename($do['id'], $refName);
                            $dest = $objectsDir . '/ref/' . $destName;
                            if ($this->copyFile($refPath, $dest)) {
                                $size = filesize($dest);
                                $manifest[] = [
                                    'type' => 'reference',
                                    'object_id' => $desc['id'],
                                    'digital_object_id' => $do['id'],
                                    'filename' => $destName,
                                    'size' => $size,
                                    'checksum' => hash_file('sha256', $dest),
                                ];
                                $totalSize += $size;
                                $do['reference_file'] = 'objects/ref/' . $destName;
                            }
                        }
                    }
                }

                // Copy PDF access copies
                if (!empty($do['name']) && $do['mime_type'] === 'application/pdf' && $baseDir) {
                    $pdfPath = $baseDir . $do['name'];
                    if (file_exists($pdfPath)) {
                        $destName = $this->safeFilename($do['id'], $do['name']);
                        $dest = $objectsDir . '/pdf/' . $destName;
                        if ($this->copyFile($pdfPath, $dest)) {
                            $size = filesize($dest);
                            $manifest[] = [
                                'type' => 'pdf',
                                'object_id' => $desc['id'],
                                'digital_object_id' => $do['id'],
                                'filename' => $destName,
                                'size' => $size,
                                'checksum' => hash_file('sha256', $dest),
                            ];
                            $totalSize += $size;
                            $do['pdf_file'] = 'objects/pdf/' . $destName;
                        }
                    }
                }

                $processed++;
                if ($this->progressCallback && $processed % 10 === 0) {
                    ($this->progressCallback)($processed, $totalFiles);
                }
            }
        }

        return [
            'files' => $manifest,
            'total_size' => $totalSize,
            'descriptions' => $descriptions,
        ];
    }

    /**
     * Resolve the base directory for a digital object.
     *
     * AtoM's digital_object.path already includes /uploads/ prefix,
     * e.g. /uploads/r/repo-name/a/b/c/hash/
     * Full path = atomRoot + path
     */
    protected function resolveBaseDir(array $do): ?string
    {
        if (empty($do['path'])) {
            return null;
        }

        $dir = $this->atomRoot . $do['path'];

        return is_dir($dir) ? $dir : null;
    }

    /**
     * Derive a derivative filename from the master name.
     *
     * AtoM convention: master = "photo.jpg", reference = "photo_141.jpg", thumbnail = "photo_142.jpg"
     */
    protected function deriveFilename(?string $masterName, int $usageId): ?string
    {
        if (!$masterName) {
            return null;
        }

        $ext = pathinfo($masterName, PATHINFO_EXTENSION);
        $base = pathinfo($masterName, PATHINFO_FILENAME);

        return $base . '_' . $usageId . ($ext ? '.' . $ext : '');
    }

    /**
     * Generate a safe filename with digital object ID prefix.
     */
    protected function safeFilename(int $doId, string $originalName): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);

        return $doId . '_' . substr($safeName, 0, 60) . ($ext ? '.' . $ext : '');
    }

    /**
     * Copy a file, creating parent directories as needed.
     */
    protected function copyFile(string $source, string $dest): bool
    {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return @copy($source, $dest);
    }

    /**
     * Count total digital object files for progress tracking.
     */
    protected function countFiles(array $descriptions): int
    {
        $count = 0;
        foreach ($descriptions as $desc) {
            $count += count($desc['digital_objects'] ?? []);
        }

        return $count;
    }
}
