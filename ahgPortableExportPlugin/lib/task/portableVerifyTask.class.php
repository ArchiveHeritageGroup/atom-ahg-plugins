<?php

/**
 * CLI command for verifying portable archive export packages.
 *
 * Usage:
 *   php symfony portable:verify --path=/path/to/export.zip
 *   php symfony portable:verify --path=/path/to/export-dir
 */
class portableVerifyTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('path', null, sfCommandOption::PARAMETER_REQUIRED, 'Path to export ZIP or extracted directory'),
        ]);

        $this->namespace = 'portable';
        $this->name = 'verify';
        $this->briefDescription = 'Verify integrity of a portable archive export package';
        $this->detailedDescription = <<<'EOF'
The [portable:verify|INFO] task verifies the integrity of an archive export
package by checking SHA-256 checksums against the manifest.

  [php symfony portable:verify --path=/path/to/export.zip|INFO]
  [php symfony portable:verify --path=/path/to/extracted-export|INFO]

Returns exit code 0 if all files pass verification, 1 if errors are found.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $path = $options['path'] ?? '';
        if (empty($path)) {
            $this->logSection('verify', 'ERROR: --path is required', null, 'ERROR');

            return 1;
        }

        if (!file_exists($path)) {
            $this->logSection('verify', 'ERROR: Path not found: ' . $path, null, 'ERROR');

            return 1;
        }

        // If ZIP, extract to temp directory
        $packageDir = $path;
        $tempDir = null;

        if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'zip') {
            $tempDir = sys_get_temp_dir() . '/portable-verify-' . uniqid();
            @mkdir($tempDir, 0755, true);

            $this->logSection('verify', 'Extracting ZIP to temporary directory...');

            $zip = new ZipArchive();
            $res = $zip->open($path);
            if ($res !== true) {
                $this->logSection('verify', 'ERROR: Failed to open ZIP archive', null, 'ERROR');

                return 1;
            }
            $zip->extractTo($tempDir);
            $zip->close();

            $packageDir = $tempDir;

            // If ZIP contains a single top-level directory, use that
            $items = @scandir($packageDir);
            $dirs = array_filter($items, function ($i) use ($packageDir) {
                return $i !== '.' && $i !== '..' && is_dir($packageDir . '/' . $i);
            });
            if (count($dirs) === 1 && !file_exists($packageDir . '/manifest.json')) {
                $packageDir = $packageDir . '/' . reset($dirs);
            }
        }

        // Find manifest.json
        $manifestPath = $packageDir . '/manifest.json';
        if (!file_exists($manifestPath)) {
            $this->logSection('verify', 'ERROR: manifest.json not found in package', null, 'ERROR');
            $this->cleanup($tempDir);

            return 1;
        }

        // Load ManifestBuilder for verification
        $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
        require_once $ahgDir . '/lib/Services/ManifestBuilder.php';

        $this->logSection('verify', 'Verifying package at: ' . $packageDir);

        // Read manifest metadata
        $manifest = json_decode(file_get_contents($manifestPath), true);
        if (!$manifest) {
            $this->logSection('verify', 'ERROR: manifest.json is invalid JSON', null, 'ERROR');
            $this->cleanup($tempDir);

            return 1;
        }

        $this->logSection('verify', 'Format: ' . ($manifest['format'] ?? 'unknown'));
        $this->logSection('verify', 'Version: ' . ($manifest['version'] ?? 'unknown'));
        $this->logSection('verify', 'Created: ' . ($manifest['created_at'] ?? 'unknown'));
        $this->logSection('verify', 'Scope: ' . ($manifest['scope']['type'] ?? 'unknown'));

        // Show entity counts
        if (!empty($manifest['counts'])) {
            $this->logSection('verify', 'Entity counts:');
            foreach ($manifest['counts'] as $type => $count) {
                $this->logSection('verify', "  {$type}: {$count}");
            }
        }

        // Run verification
        $result = \AhgPortableExportPlugin\Services\ManifestBuilder::verify($manifestPath, $packageDir);

        $this->logSection('verify', '');
        $this->logSection('verify', "Total files: {$result['total']}");
        $this->logSection('verify', "Verified OK: {$result['verified']}");

        if (!empty($result['missing'])) {
            $this->logSection('verify', 'Missing files: ' . count($result['missing']), null, 'ERROR');
            foreach ($result['missing'] as $f) {
                $this->logSection('verify', "  MISSING: {$f}", null, 'ERROR');
            }
        }

        if (!empty($result['mismatches'])) {
            $this->logSection('verify', 'Checksum mismatches: ' . count($result['mismatches']), null, 'ERROR');
            foreach ($result['mismatches'] as $m) {
                $this->logSection('verify', "  MISMATCH: {$m['path']}", null, 'ERROR');
                $this->logSection('verify', "    Expected: {$m['expected']}");
                $this->logSection('verify', "    Actual:   {$m['actual']}");
            }
        }

        $this->cleanup($tempDir);

        if ($result['ok']) {
            $this->logSection('verify', 'PASSED: All files verified successfully');

            return 0;
        }

        $this->logSection('verify', 'FAILED: Verification errors found', null, 'ERROR');

        return 1;
    }

    /**
     * Clean up temporary extraction directory.
     */
    protected function cleanup(?string $tempDir): void
    {
        if ($tempDir && is_dir($tempDir)) {
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($items as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
            @rmdir($tempDir);
        }
    }
}
