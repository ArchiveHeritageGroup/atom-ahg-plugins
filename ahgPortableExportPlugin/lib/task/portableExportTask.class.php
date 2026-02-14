<?php

/**
 * CLI command for generating portable catalogue exports.
 *
 * Usage:
 *   php symfony portable:export --scope=all --zip --output=/tmp/portable-export.zip
 *   php symfony portable:export --scope=fonds --slug=example-fonds --output=/tmp/export
 *   php symfony portable:export --scope=repository --repository-id=123
 *   php symfony portable:export --scope=all --mode=editable --no-objects
 */
class portableExportTask extends arBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('scope', null, sfCommandOption::PARAMETER_REQUIRED, 'Export scope: all, fonds, repository, custom', 'all'),
            new sfCommandOption('slug', null, sfCommandOption::PARAMETER_OPTIONAL, 'Fonds/description slug (for scope=fonds or custom)'),
            new sfCommandOption('repository-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Repository ID (for scope=repository or custom)'),
            new sfCommandOption('mode', null, sfCommandOption::PARAMETER_OPTIONAL, 'Viewer mode: read_only or editable', 'read_only'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture/language code', 'en'),
            new sfCommandOption('title', null, sfCommandOption::PARAMETER_OPTIONAL, 'Export title', 'Portable Catalogue'),
            new sfCommandOption('output', null, sfCommandOption::PARAMETER_OPTIONAL, 'Output path (directory or .zip file)'),
            new sfCommandOption('zip', null, sfCommandOption::PARAMETER_NONE, 'Create ZIP archive'),
            new sfCommandOption('no-objects', null, sfCommandOption::PARAMETER_NONE, 'Skip digital objects (metadata only)'),
            new sfCommandOption('no-thumbnails', null, sfCommandOption::PARAMETER_NONE, 'Skip thumbnails'),
            new sfCommandOption('no-references', null, sfCommandOption::PARAMETER_NONE, 'Skip reference images'),
            new sfCommandOption('include-masters', null, sfCommandOption::PARAMETER_NONE, 'Include master files'),
            new sfCommandOption('export-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Process existing export job by ID'),
        ]);

        $this->namespace = 'portable';
        $this->name = 'export';
        $this->briefDescription = 'Generate a portable standalone catalogue viewer';
        $this->detailedDescription = <<<'EOF'
The [portable:export|INFO] task generates a self-contained HTML/JS catalogue
viewer for offline access on CD, USB, or downloadable ZIP.

  [php symfony portable:export --scope=all --zip --output=/tmp/catalogue.zip|INFO]
  [php symfony portable:export --scope=fonds --slug=my-fonds|INFO]
  [php symfony portable:export --scope=repository --repository-id=5|INFO]
  [php symfony portable:export --scope=all --mode=editable|INFO]
  [php symfony portable:export --scope=all --no-objects|INFO]
  [php symfony portable:export --export-id=42|INFO]

The generated viewer opens in any modern browser with no server or internet.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        // Load services
        $ahgDir = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgPortableExportPlugin';
        require_once $ahgDir . '/lib/Services/ExportPipelineService.php';
        require_once $ahgDir . '/lib/Services/CatalogueExtractor.php';
        require_once $ahgDir . '/lib/Services/AssetCollector.php';
        require_once $ahgDir . '/lib/Services/SearchIndexBuilder.php';
        require_once $ahgDir . '/lib/Services/ViewerPackager.php';

        $DB = \Illuminate\Database\Capsule\Manager::class;

        // If export-id provided, run existing job
        if (!empty($options['export-id'])) {
            return $this->runExistingExport((int) $options['export-id']);
        }

        // Create a new export record
        $exportId = $DB::table('portable_export')->insertGetId([
            'user_id' => 1, // CLI user
            'title' => $options['title'] ?? 'Portable Catalogue',
            'scope_type' => $options['scope'] ?? 'all',
            'scope_slug' => $options['slug'] ?? null,
            'scope_repository_id' => !empty($options['repository-id']) ? (int) $options['repository-id'] : null,
            'mode' => $options['mode'] ?? 'read_only',
            'include_objects' => empty($options['no-objects']) ? 1 : 0,
            'include_thumbnails' => empty($options['no-thumbnails']) ? 1 : 0,
            'include_references' => empty($options['no-references']) ? 1 : 0,
            'include_masters' => !empty($options['include-masters']) ? 1 : 0,
            'culture' => $options['culture'] ?? 'en',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->logSection('portable', "Created export job #{$exportId}");

        return $this->runExistingExport($exportId, $options);
    }

    protected function runExistingExport(int $exportId, array $options = []): int
    {
        $startTime = microtime(true);
        $pipeline = new \AhgPortableExportPlugin\Services\ExportPipelineService();

        try {
            $this->logSection('portable', "Starting export #{$exportId}...");
            $pipeline->runExport($exportId);
        } catch (\Exception $e) {
            $this->logSection('portable', 'ERROR: ' . $e->getMessage(), null, 'ERROR');

            return 1;
        }

        $elapsed = round(microtime(true) - $startTime, 1);
        $export = \Illuminate\Database\Capsule\Manager::table('portable_export')
            ->where('id', $exportId)->first();

        $this->logSection('portable', "Export completed in {$elapsed}s");
        $this->logSection('portable', "Descriptions: {$export->total_descriptions}, Objects: {$export->total_objects}");

        if ($export->output_path) {
            $sizeMB = round($export->output_size / 1048576, 1);
            $this->logSection('portable', "Output: {$export->output_path} ({$sizeMB} MB)");

            // If user specified custom output path, copy there
            if (!empty($options['output'])) {
                $destPath = $options['output'];
                if (pathinfo($destPath, PATHINFO_EXTENSION) !== 'zip') {
                    $destPath .= '.zip';
                }
                copy($export->output_path, $destPath);
                $this->logSection('portable', "Copied to: {$destPath}");
            }
        }

        return 0;
    }
}
