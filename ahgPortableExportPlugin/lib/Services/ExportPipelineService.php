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

    /** @var string|null #1389 disclosure summary JSON for the current run */
    protected $disclosureSummaryJson = null;

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
            require_once $ahgDir . '/lib/Services/DisclosureGate.php';
            require_once $ahgDir . '/lib/Services/CatalogueExtractor.php';
            require_once $ahgDir . '/lib/Services/AssetCollector.php';
            require_once $ahgDir . '/lib/Services/SearchIndexBuilder.php';
            require_once $ahgDir . '/lib/Services/ViewerPackager.php';
            require_once $ahgDir . '/lib/Services/ArchiveExtractor.php';
            require_once $ahgDir . '/lib/Services/ManifestBuilder.php';
            require_once $ahgDir . '/lib/Services/ExportEstimator.php';
            require_once $ahgDir . '/lib/Services/ArchiveImporter.php';
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
        // Branch: archive mode runs a different pipeline
        if ($export->mode === 'archive') {
            $this->executeArchiveSteps($exportId, $export);

            return;
        }

        $outputDir = $this->resolveOutputDir($exportId);
        @mkdir($outputDir . '/data', 0755, true);

        // Step 1: Extract catalogue (0-40%)
        $this->updateProgress($exportId, 5);
        $extractor = new CatalogueExtractor($export->culture, function ($current, $total) use ($exportId) {
            $pct = 5 + (int) (($current / max($total, 1)) * 35);
            $this->updateProgress($exportId, min($pct, 40));
        });

        $itemIds = null;
        if ($export->scope_items) {
            $itemIds = json_decode($export->scope_items, true);
        }

        $catalogueData = $extractor->extract(
            $export->scope_type,
            $export->scope_slug,
            $export->scope_repository_id ? (int) $export->scope_repository_id : null,
            $itemIds
        );

        $descriptions = $catalogueData['descriptions'];
        $totalDescriptions = count($descriptions);

        // #1389 — build the disclosure summary from what the gate withheld and
        // write it into the package so the recipient (and the operator, via the
        // stamped column) can see what was NOT exported.
        $excluded = $extractor->getDisclosureExcluded();
        $withheldTotal = $excluded['unpublished'] + $excluded['icip'] + $excluded['odrl'] + $excluded['redacted_objects'];
        $disclosure = [
            'generated_at' => date('c'),
            'records_included' => $totalDescriptions,
            'withheld' => $excluded,
            'note' => 'Records/objects were excluded from this offline package to honour publication status, ICIP/TK cultural protocols, ODRL access policies, and PII redaction. Counts reflect what was NOT exported.',
        ];
        $this->disclosureSummaryJson = json_encode($disclosure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(
            $outputDir . '/data/disclosure-summary.json',
            json_encode($disclosure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // Write catalogue data as both JSON and JS (JS for file:// compatibility)
        $catalogueJson = json_encode($descriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($outputDir . '/data/catalogue.json', $catalogueJson);
        file_put_contents($outputDir . '/data/catalogue.js', 'window.DATA_CATALOGUE=' . $catalogueJson . ';');

        $taxonomiesJson = json_encode($catalogueData['taxonomies'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($outputDir . '/data/taxonomies.json', $taxonomiesJson);
        file_put_contents($outputDir . '/data/taxonomies.js', 'window.DATA_TAXONOMIES=' . $taxonomiesJson . ';');

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

            // Re-write catalogue data with updated file paths
            $catalogueJson = json_encode($descriptions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($outputDir . '/data/catalogue.json', $catalogueJson);
            file_put_contents($outputDir . '/data/catalogue.js', 'window.DATA_CATALOGUE=' . $catalogueJson . ';');

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
        $indexJson = json_encode($indexData, JSON_UNESCAPED_UNICODE);
        file_put_contents($outputDir . '/data/search-index.json', $indexJson);
        file_put_contents($outputDir . '/data/search-index.js', 'window.DATA_SEARCH_INDEX=' . $indexJson . ';');
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

        // Step 5: Finalise output (ZIP, or an uncompressed folder dump when the
        // destination is a folder/drive) (90-100%)
        $final = $this->finaliseOutput($exportId, $outputDir, $packager);
        $zipPath = $final['path'];
        $zipSize = $final['size'];
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

        // #1389 — stamp the disclosure summary separately and tolerantly, so the
        // export still completes on installations where migration_disclosure_summary.sql
        // has not been applied yet (the column simply stays unpopulated).
        $this->stampDisclosureSummary($exportId);

        // Insert notification for the user who started the export
        $this->notifyCompletion($export, $totalDescriptions, $totalObjects, $zipSize);
    }

    /**
     * #1389 — persist the disclosure summary onto the export row if the column
     * exists (added by migration_disclosure_summary.sql). No-op otherwise.
     */
    protected function stampDisclosureSummary(int $exportId): void
    {
        if ($this->disclosureSummaryJson === null) {
            return;
        }
        try {
            DB::table('portable_export')
                ->where('id', $exportId)
                ->update(['disclosure_summary' => $this->disclosureSummaryJson]);
        } catch (\Throwable $e) {
            // Column not migrated yet — the disclosure-summary.json is still in the
            // package; the stamped column is a convenience for the operator view.
            error_log('portable-export: disclosure_summary column not present yet: ' . $e->getMessage());
        }
    }

    /**
     * Execute archive export pipeline: ArchiveExtractor → AssetCollector → ManifestBuilder → ZIP.
     */
    protected function executeArchiveSteps(int $exportId, object $export): void
    {
        $outputDir = $this->resolveOutputDir($exportId);
        @mkdir($outputDir . '/data', 0755, true);

        $options = [
            'scope_type' => $export->scope_type,
            'scope_slug' => $export->scope_slug,
            'scope_repository_id' => $export->scope_repository_id ? (int) $export->scope_repository_id : null,
            'scope_items' => $export->scope_items ? json_decode($export->scope_items, true) : null,
            'culture' => $export->culture,
        ];

        // Parse entity_types from export config if set, otherwise use all
        $entityTypes = null;
        if (!empty($export->branding)) {
            $branding = json_decode($export->branding, true);
            if (!empty($branding['entity_types'])) {
                $entityTypes = $branding['entity_types'];
                $options['entity_types'] = $entityTypes;
            }
        }

        // Step 1: Extract entities (0-60%)
        $this->updateProgress($exportId, 5);
        $archiveExtractor = new ArchiveExtractor($export->culture, function ($current, $total) use ($exportId) {
            $pct = 5 + (int) (($current / max($total, 1)) * 55);
            $this->updateProgress($exportId, min($pct, 60));
        });

        $entityFiles = $archiveExtractor->extract($exportId, $options, $outputDir);
        $this->updateProgress($exportId, 60);

        // Calculate total descriptions from extracted data
        $totalDescriptions = $entityFiles['descriptions']['count'] ?? 0;

        // #1389 — archive mode is gated too: record what was withheld and write the
        // disclosure summary into the package + stamp the column at completion.
        $excluded = $archiveExtractor->getDisclosureExcluded();
        $disclosure = [
            'generated_at' => date('c'),
            'records_included' => $totalDescriptions,
            'withheld' => $excluded,
            'note' => 'Records/objects were excluded from this offline package to honour publication status, ICIP/TK cultural protocols, ODRL access policies, and PII redaction. Counts reflect what was NOT exported.',
        ];
        $this->disclosureSummaryJson = json_encode($disclosure, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents(
            $outputDir . '/data/disclosure-summary.json',
            json_encode($disclosure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $totalObjects = 0;

        // Step 2: Collect digital assets (60-80%)
        if ($export->include_objects) {
            // Re-read descriptions to get digital object paths
            $descriptionsFile = $outputDir . '/data/descriptions.json';
            if (file_exists($descriptionsFile)) {
                $descriptions = json_decode(file_get_contents($descriptionsFile), true) ?: [];

                $collector = new AssetCollector(null, function ($current, $total) use ($exportId) {
                    $pct = 60 + (int) (($current / max($total, 1)) * 20);
                    $this->updateProgress($exportId, min($pct, 80));
                });

                $assetResult = $collector->collect($descriptions, $outputDir, [
                    'include_thumbnails' => (bool) $export->include_thumbnails,
                    'include_references' => (bool) $export->include_references,
                    'include_masters' => (bool) $export->include_masters,
                ]);

                $totalObjects = count($assetResult['files']);
            }
        }
        $this->updateProgress($exportId, 80);

        // Step 3: Build manifest with checksums (80-90%)
        $manifestBuilder = new ManifestBuilder();
        $manifestBuilder->build($outputDir, $entityFiles, $options);
        $this->updateProgress($exportId, 90);

        // Step 4: Finalise output (ZIP, or an uncompressed folder dump when the
        // destination is a folder/drive) (90-100%)
        $this->loadServices();
        $packager = new ViewerPackager();
        $final = $this->finaliseOutput($exportId, $outputDir, $packager);
        $zipPath = $final['path'];
        $zipSize = $final['size'];
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

        // #1389 — stamp the archive-mode disclosure summary (column-tolerant).
        $this->stampDisclosureSummary($exportId);

        $this->notifyCompletion($export, $totalDescriptions, $totalObjects, $zipSize);
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
        $export = DB::table('portable_export')->where('id', $exportId)->first();

        // Folder destination: build the bundle DIRECTLY on the operator-chosen
        // directory / mounted drive (no temp staging, never zipped) — for
        // collections too large for a ZIP.
        if ($export
            && ($export->destination ?? 'zip') === 'folder'
            && !empty($export->destination_path)
            && is_dir($export->destination_path)
            && is_writable($export->destination_path)
        ) {
            $folderName = preg_replace('/[^A-Za-z0-9_-]/', '-', (string) $export->title) . '-' . $exportId;
            $dir = rtrim($export->destination_path, '/') . '/' . $folderName;
            @mkdir($dir, 0755, true);

            return $dir;
        }

        $baseDir = \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive')
            . '/downloads/portable-exports';
        @mkdir($baseDir, 0755, true);

        return $baseDir . '/export-' . $exportId;
    }

    /**
     * Finalise the output: for a 'folder' destination the bundle IS the built
     * directory (no zip); otherwise create the downloadable ZIP. Returns
     * ['path' => string, 'size' => int].
     */
    protected function finaliseOutput(int $exportId, string $outputDir, $packager): array
    {
        $export = DB::table('portable_export')->where('id', $exportId)->first();
        $isFolder = $export
            && ($export->destination ?? 'zip') === 'folder'
            && !empty($export->destination_path);

        if ($isFolder) {
            return ['path' => $outputDir, 'size' => $this->directorySize($outputDir)];
        }

        $zipPath = $outputDir . '.zip';
        $size = $packager->createZip($outputDir, $zipPath);

        return ['path' => $zipPath, 'size' => $size];
    }

    /** Total byte size of a directory tree. */
    protected function directorySize(string $dir): int
    {
        $size = 0;
        if (!is_dir($dir)) {
            return 0;
        }
        try {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (\Throwable $e) {
            // best-effort
        }

        return $size;
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
     * Notify user that export is complete.
     * Uses audit_trail if available, otherwise logs to error_log.
     */
    protected function notifyCompletion(object $export, int $totalDescriptions, int $totalObjects, int $zipSize): void
    {
        $sizeMB = round($zipSize / 1048576, 1);
        $message = sprintf(
            'Portable export "%s" completed: %d descriptions, %d objects (%s MB)',
            $export->title,
            $totalDescriptions,
            $totalObjects,
            $sizeMB
        );

        // Log to audit trail if the plugin is enabled
        try {
            if (DB::getSchemaBuilder()->hasTable('audit_trail')) {
                DB::table('audit_trail')->insert([
                    'user_id' => (int) $export->user_id,
                    'object_type' => 'portable_export',
                    'object_id' => (int) $export->id,
                    'action' => 'export_completed',
                    'description' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            // Audit trail not available — just log
            error_log('[PortableExport] ' . $message);
        }
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
