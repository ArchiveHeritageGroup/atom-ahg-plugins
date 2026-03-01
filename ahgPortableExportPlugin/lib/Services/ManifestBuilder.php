<?php

namespace AhgPortableExportPlugin\Services;

/**
 * Generates manifest.json with checksums, entity counts, and metadata
 * for archive export packages.
 */
class ManifestBuilder
{
    /**
     * Build manifest.json for the archive export.
     *
     * @param string $outputDir  Root directory of the export package
     * @param array  $entityFiles  Map of entity type => ['path' => ..., 'count' => ...]
     * @param array  $options      Export options (scope, culture, etc.)
     * @return string  Path to generated manifest.json
     */
    public function build(string $outputDir, array $entityFiles, array $options = []): string
    {
        $manifest = [
            'version' => '2.0.0',
            'format' => 'atom-heratio-archive',
            'created_at' => date('c'),
            'source' => [
                'url' => \sfConfig::get('app_siteBaseUrl', ''),
                'site_title' => \sfConfig::get('app_siteTitle', 'AtoM'),
                'framework' => $this->getFrameworkVersion(),
                'plugin_version' => '2.0.0',
            ],
            'scope' => [
                'type' => $options['scope_type'] ?? 'all',
                'slug' => $options['scope_slug'] ?? null,
                'repository_id' => $options['scope_repository_id'] ?? null,
            ],
            'culture' => $options['culture'] ?? 'en',
            'counts' => [],
            'files' => [],
        ];

        // Entity counts
        foreach ($entityFiles as $type => $info) {
            $manifest['counts'][$type] = $info['count'] ?? 0;
        }

        // Compute checksums for all files in the package
        $allFiles = $this->inventoryFiles($outputDir);
        foreach ($allFiles as $relativePath) {
            $fullPath = $outputDir . '/' . $relativePath;
            if (!is_file($fullPath)) {
                continue;
            }

            $manifest['files'][] = [
                'path' => $relativePath,
                'sha256' => hash_file('sha256', $fullPath),
                'size' => filesize($fullPath),
            ];
        }

        $manifestPath = $outputDir . '/manifest.json';
        file_put_contents(
            $manifestPath,
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return $manifestPath;
    }

    /**
     * Recursively inventory all files in the output directory.
     *
     * @return string[] Relative file paths
     */
    protected function inventoryFiles(string $dir, string $prefix = ''): array
    {
        $files = [];
        $items = @scandir($dir);
        if (!$items) {
            return $files;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            $relative = $prefix ? $prefix . '/' . $item : $item;

            // Skip manifest.json itself — it gets written after this runs
            if ($relative === 'manifest.json') {
                continue;
            }

            if (is_dir($path)) {
                $files = array_merge($files, $this->inventoryFiles($path, $relative));
            } else {
                $files[] = $relative;
            }
        }

        return $files;
    }

    /**
     * Get framework version from atom-framework.
     */
    protected function getFrameworkVersion(): string
    {
        $versionFile = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive')
            . '/atom-framework/version.txt';

        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }

        return 'unknown';
    }

    /**
     * Verify a manifest against its package files.
     *
     * @param string $manifestPath  Path to manifest.json
     * @param string $packageDir    Root directory of extracted package
     * @return array{ok: bool, total: int, verified: int, mismatches: array, missing: array}
     */
    public static function verify(string $manifestPath, string $packageDir): array
    {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest || empty($manifest['files'])) {
            return [
                'ok' => false,
                'total' => 0,
                'verified' => 0,
                'mismatches' => [],
                'missing' => ['manifest.json has no files or is invalid'],
            ];
        }

        $total = count($manifest['files']);
        $verified = 0;
        $mismatches = [];
        $missing = [];

        foreach ($manifest['files'] as $fileEntry) {
            $fullPath = $packageDir . '/' . $fileEntry['path'];

            if (!file_exists($fullPath)) {
                $missing[] = $fileEntry['path'];
                continue;
            }

            $actualHash = hash_file('sha256', $fullPath);
            if ($actualHash !== $fileEntry['sha256']) {
                $mismatches[] = [
                    'path' => $fileEntry['path'],
                    'expected' => $fileEntry['sha256'],
                    'actual' => $actualHash,
                ];
            } else {
                $verified++;
            }
        }

        return [
            'ok' => empty($mismatches) && empty($missing),
            'total' => $total,
            'verified' => $verified,
            'mismatches' => $mismatches,
            'missing' => $missing,
        ];
    }
}
