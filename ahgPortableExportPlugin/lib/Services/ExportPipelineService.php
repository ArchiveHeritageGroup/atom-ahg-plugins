<?php

namespace AhgPortableExportPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Orchestrates the full portable export pipeline.
 *
 * Steps: validate scope → extract catalogue → collect assets →
 * build search index → package viewer → create ZIP.
 *
 * Updates portable_export.progress at each step for AJAX polling.
 */
class ExportPipelineService
{
    /** @var string Plugin directory for require_once */
    protected $pluginDir;

    public function __construct(?string $pluginDir = null)
    {
        $this->pluginDir = $pluginDir
            ?: \sfConfig::get('sf_plugins_dir', '/usr/share/nginx/archive/plugins') . '/ahgPortableExportPlugin';
    }

    /**
     * Load service classes (lazy loading for Symfony 1.x compatibility).
     */
    protected function loadServices(): void
    {
        static $loaded = false;
        if (!$loaded) {
            $ahgDir = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive')
                . '/atom-ahg-plugins/ahgPortableExportPlugin';
            require_once $ahgDir . '/lib/Services/CatalogueExtractor.php';
            require_once $ahgDir . '/lib/Services/AssetCollector.php';
            require_once $ahgDir . '/lib/Services/SearchIndexBuilder.php';
            require_once $ahgDir . '/lib/Services/ViewerPackager.php';
            $loaded = true;
        }
    }

    /**
     * Run the full export pipeline for the given export ID.
     *
     * @param int $exportId portable_export.id
     */
    public function runExport(int $exportId): void
    {
        $this->loadServices();

        $export = DB::table('portable_export')->where('id', $exportId)->first();
        if (!$export) {
            throw new \RuntimeException("Export #{$exportId} not found");
        }

        // Mark as processing
        DB::table('portable_export')->where('id', $exportId)->update([
            'status' => 'processing',
            'started_at' => date('Y-m-d H:i:s'),
            'progress' => 0,
        ]);

        try {
            $this->executeSteps($exportId, $export);
        } catch (\Exception $e) {
            DB::table('portable_export')->where('id', $exportId)->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 5000),
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            throw $e;
        }
    }

    /**
     * Execute all export steps sequentially.
     */
    protected function executeSteps(int $exportId, object $export): void
    {
        $outputDir = $this->resolveOutputDir($exportId);
        @mkdir($outputDir . '/data', 0755, true);

        // Step 1: Extract catalogue (0-40%)
        $this->updateProgress($exportId, 5);
        $extractor = new CatalogueExtractor($export->culture, function ($current, $total) use ($exportId) {
            $pct = 5 + (int) (($current / max($total, 1)) * 35);
            $this->updateProgress($exportId, min($pct, 40));
        });

        $catalogueData = $extractor->extract(
            $export->scope_type,
            $export->scope_slug,
            $export->scope_repository_id ? (int) $export->scope_repository_id : null
        );

        $descriptions = $catalogueData['descriptions'];
        $totalDescriptions = count($descriptions);

        // Write catalogue.json
        file_put_contents(
            $outputDir . '/data/catalogue.json',
            json_encode($descriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        // Write taxonomies.json
        file_put_contents(
            $outputDir . '/data/taxonomies.json',
            json_encode($catalogueData['taxonomies'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $this->updateProgress($exportId, 40);

        // Step 2: Collect assets (40-70%)
        $totalObjects = 0;
        if ($export->include_objects) {
            $collector = new AssetCollector(null, function ($current, $total) use ($exportId) {
                $pct = 40 + (int) (($current / max($total, 1)) * 30);
                $this->updateProgress($exportId, min($pct, 70));
            });

            $assetResult = $collector->collect($descriptions, $outputDir, [
                'include_thumbnails' => (bool) $export->include_thumbnails,
                'include_references' => (bool) $export->include_references,
                'include_masters' => (bool) $export->include_masters,
            ]);

            $totalObjects = count($assetResult['files']);

            // Update descriptions with file paths from asset collector
            $descriptions = $assetResult['descriptions'];

            // Re-write catalogue.json with updated file paths
            file_put_contents(
                $outputDir . '/data/catalogue.json',
                json_encode($descriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // Write manifest
            file_put_contents(
                $outputDir . '/data/manifest.json',
                json_encode($assetResult['files'], JSON_PRETTY_PRINT)
            );
        }

        $this->updateProgress($exportId, 70);

        // Step 3: Build search index (70-80%)
        $indexBuilder = new SearchIndexBuilder();
        $indexData = $indexBuilder->buildIndex($descriptions);
        file_put_contents(
            $outputDir . '/data/search-index.json',
            json_encode($indexData, JSON_UNESCAPED_UNICODE)
        );
        $this->updateProgress($exportId, 80);

        // Step 4: Package viewer (80-90%)
        $branding = $export->branding ? json_decode($export->branding, true) : [];
        $config = [
            'title' => $export->title,
            'mode' => $export->mode,
            'culture' => $export->culture,
            'exported_at' => date('c'),
            'total_descriptions' => $totalDescriptions,
            'total_objects' => $totalObjects,
            'scope_type' => $export->scope_type,
            'branding' => $branding,
            'hierarchy' => $catalogueData['hierarchy'],
            'repositories' => $catalogueData['repositories'],
        ];

        $packager = new ViewerPackager();
        $packager->package($outputDir, $config);
        $this->updateProgress($exportId, 90);

        // Step 5: Create ZIP (90-100%)
        $zipPath = $outputDir . '.zip';
        $zipSize = $packager->createZip($outputDir, $zipPath);
        $this->updateProgress($exportId, 98);

        // Mark as completed
        DB::table('portable_export')->where('id', $exportId)->update([
            'status' => 'completed',
            'progress' => 100,
            'total_descriptions' => $totalDescriptions,
            'total_objects' => $totalObjects,
            'output_path' => $zipPath,
            'output_size' => $zipSize,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update progress for AJAX polling.
     */
    protected function updateProgress(int $exportId, int $progress): void
    {
        DB::table('portable_export')
            ->where('id', $exportId)
            ->update(['progress' => $progress]);
    }

    /**
     * Resolve the output directory for an export.
     */
    public function resolveOutputDir(int $exportId): string
    {
        $baseDir = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive')
            . '/downloads/portable-exports';
        @mkdir($baseDir, 0755, true);

        return $baseDir . '/export-' . $exportId;
    }

    /**
     * Delete an export's files and database record.
     */
    public function deleteExport(int $exportId): void
    {
        $export = DB::table('portable_export')->where('id', $exportId)->first();
        if (!$export) {
            return;
        }

        // Delete output directory
        $outputDir = $this->resolveOutputDir($exportId);
        if (is_dir($outputDir)) {
            $this->recursiveDelete($outputDir);
        }

        // Delete ZIP
        if ($export->output_path && file_exists($export->output_path)) {
            @unlink($export->output_path);
        }

        // Delete database records (tokens cascade)
        DB::table('portable_export')->where('id', $exportId)->delete();
    }

    /**
     * Recursively delete a directory.
     */
    protected function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}
