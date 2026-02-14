<?php

namespace AhgPortableExportPlugin\Services;

/**
 * Collects digital object files (thumbnails, references, masters, PDFs)
 * for portable export.
 *
 * Resolves file paths from the AtoM uploads directory and copies them
 * to the export output directory with SHA-256 checksums.
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
     * @return array{files: array, total_size: int}
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

        $uploadsDir = $this->atomRoot . '/uploads';

        foreach ($descriptions as &$desc) {
            if (empty($desc['digital_objects'])) {
                continue;
            }

            foreach ($desc['digital_objects'] as &$do) {
                $masterPath = $this->resolveMasterPath($uploadsDir, $do);

                // Copy master / original
                if ($includeMasters && $masterPath && file_exists($masterPath)) {
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

                // Copy thumbnail
                if ($includeThumbnails) {
                    $thumbPath = $this->resolveDerivativePath($uploadsDir, $do, 'thumbnails');
                    if ($thumbPath && file_exists($thumbPath)) {
                        $destName = $this->safeFilename($do['id'], basename($thumbPath));
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

                // Copy reference image
                if ($includeReferences) {
                    $refPath = $this->resolveDerivativePath($uploadsDir, $do, 'previews');
                    if ($refPath && file_exists($refPath)) {
                        $destName = $this->safeFilename($do['id'], basename($refPath));
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

                // Copy PDF access copies
                if ($masterPath && $do['mime_type'] === 'application/pdf' && file_exists($masterPath)) {
                    $destName = $this->safeFilename($do['id'], $do['name']);
                    $dest = $objectsDir . '/pdf/' . $destName;
                    if ($this->copyFile($masterPath, $dest)) {
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
     * Resolve the master/original file path for a digital object.
     */
    protected function resolveMasterPath(string $uploadsDir, array $do): ?string
    {
        if (empty($do['path']) || empty($do['name'])) {
            return null;
        }

        // AtoM stores path relative to uploads directory
        $path = $uploadsDir . '/' . ltrim($do['path'], '/') . $do['name'];

        return file_exists($path) ? $path : null;
    }

    /**
     * Resolve the derivative file path (thumbnail or reference/preview).
     */
    protected function resolveDerivativePath(string $uploadsDir, array $do, string $derivativeType): ?string
    {
        if (empty($do['name'])) {
            return null;
        }

        // Common derivative naming: same name in derivatives/{type}/ directory
        // AtoM uses: uploads/r/{objectPath}/derivatives/{type}/{name}
        $basePath = '';
        if (!empty($do['path'])) {
            $basePath = ltrim($do['path'], '/');
        }

        // Try standard AtoM derivative path
        $derivPath = $uploadsDir . '/' . $basePath . $do['name'];
        $dir = dirname($derivPath);
        $derivDir = $dir . '/../' . $derivativeType;
        $derivDir = realpath($derivDir) ?: $derivDir;

        if (is_dir($derivDir)) {
            // Look for files matching the digital object name pattern
            $baseName = pathinfo($do['name'], PATHINFO_FILENAME);
            $files = @glob($derivDir . '/' . $baseName . '.*');
            if (!empty($files)) {
                return $files[0];
            }
        }

        return null;
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
