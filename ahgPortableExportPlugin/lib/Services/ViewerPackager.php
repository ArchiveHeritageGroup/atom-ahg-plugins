<?php

namespace AhgPortableExportPlugin\Services;

/**
 * Packages the static HTML/JS viewer with data for portable distribution.
 *
 * Copies viewer template files from web/viewer/, writes config.json,
 * bundles all assets locally (Bootstrap 5, FlexSearch), and creates
 * the final ZIP archive.
 */
class ViewerPackager
{
    /** @var string Plugin directory */
    protected $pluginDir;

    public function __construct(?string $pluginDir = null)
    {
        $this->pluginDir = $pluginDir
            ?: \sfConfig::get('sf_plugins_dir', '/usr/share/nginx/archive/plugins') . '/ahgPortableExportPlugin';
    }

    /**
     * Package the viewer into the export directory.
     *
     * @param string $exportDir Output directory containing data/ and objects/
     * @param array  $config    Viewer configuration
     */
    public function package(string $exportDir, array $config): void
    {
        $viewerSource = $this->resolveViewerSource();

        // Copy viewer HTML/JS/CSS files
        $this->copyViewerFiles($viewerSource, $exportDir);

        // Write config.json
        $configPath = $exportDir . '/data/config.json';
        @mkdir(dirname($configPath), 0755, true);
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Create a ZIP archive from the export directory.
     *
     * @param string $exportDir Source directory to archive
     * @param string $zipPath   Output ZIP file path
     *
     * @return int ZIP file size in bytes
     */
    public function createZip(string $exportDir, string $zipPath): int
    {
        $zip = new \ZipArchive();
        $result = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException("Failed to create ZIP archive at {$zipPath}: error code {$result}");
        }

        $this->addDirectoryToZip($zip, $exportDir, '');
        $zip->close();

        return filesize($zipPath);
    }

    /**
     * Resolve the viewer source directory.
     *
     * Checks multiple locations: the plugin's web/viewer/ directory
     * in atom-ahg-plugins, or the symlinked plugins/ directory.
     */
    protected function resolveViewerSource(): string
    {
        // Primary: atom-ahg-plugins source
        $ahgPluginsDir = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive')
            . '/atom-ahg-plugins/ahgPortableExportPlugin/web/viewer';
        if (is_dir($ahgPluginsDir)) {
            return $ahgPluginsDir;
        }

        // Fallback: symlinked plugins directory
        $symlinked = $this->pluginDir . '/web/viewer';
        if (is_dir($symlinked)) {
            return $symlinked;
        }

        throw new \RuntimeException('Viewer template directory not found. Expected at: ' . $ahgPluginsDir);
    }

    /**
     * Copy viewer template files to the export directory.
     */
    protected function copyViewerFiles(string $source, string $dest): void
    {
        $dirs = ['js', 'css', 'lib'];
        foreach ($dirs as $dir) {
            @mkdir($dest . '/assets/' . $dir, 0755, true);
        }

        // Copy index.html
        if (file_exists($source . '/index.html')) {
            copy($source . '/index.html', $dest . '/index.html');
        }

        // Copy JS files
        $jsFiles = ['app.js', 'search.js', 'tree.js', 'import.js'];
        foreach ($jsFiles as $file) {
            $src = $source . '/js/' . $file;
            if (file_exists($src)) {
                copy($src, $dest . '/assets/js/' . $file);
            }
        }

        // Copy CSS files
        if (file_exists($source . '/css/viewer.css')) {
            copy($source . '/css/viewer.css', $dest . '/assets/css/viewer.css');
        }

        // Copy library files
        $libFiles = [
            'flexsearch.min.js',
            'bootstrap.bundle.min.js',
            'bootstrap.min.css',
            'bootstrap-icons.min.css',
        ];
        foreach ($libFiles as $file) {
            $src = $source . '/lib/' . $file;
            if (file_exists($src)) {
                copy($src, $dest . '/assets/lib/' . $file);
            }
        }

        // Copy Bootstrap Icons font files if they exist
        $fontsDir = $source . '/lib/fonts';
        if (is_dir($fontsDir)) {
            @mkdir($dest . '/assets/lib/fonts', 0755, true);
            $fontFiles = @glob($fontsDir . '/*');
            foreach ($fontFiles ?: [] as $f) {
                copy($f, $dest . '/assets/lib/fonts/' . basename($f));
            }
        }
    }

    /**
     * Recursively add a directory to a ZipArchive.
     */
    protected function addDirectoryToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $prefix
                ? $prefix . '/' . substr($item->getPathname(), strlen($dir) + 1)
                : substr($item->getPathname(), strlen($dir) + 1);

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($item->getPathname(), $relativePath);
            }
        }
    }
}
