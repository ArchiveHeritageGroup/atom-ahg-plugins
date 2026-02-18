<?php

/**
 * Import markdown documentation files into the help system.
 *
 * Scans the atom-extensions-catalog/docs/ directory for *.md files,
 * auto-detects category/subcategory/related_plugin, parses markdown,
 * and upserts into help_article + help_section tables.
 *
 * Usage:
 *   php symfony help:import                          # Import all docs
 *   php symfony help:import --dry-run                # Preview without writing
 *   php symfony help:import --file=researcher-user-guide.md  # Single file
 *   php symfony help:import --path=/custom/docs/path # Custom docs path
 *   php symfony help:import --force                  # Force re-import unchanged files
 */
class helpImportTask extends arBaseTask
{
    /** @var array Subcategory keyword mappings */
    protected static $subcategoryMap = [
        'Research' => ['researcher-', 'research-'],
        'GLAM Sectors' => ['glam-', 'dam-', 'gallery-', 'museum-', 'library-'],
        'AI & Automation' => ['ai-', 'ner-', 'semantic-', 'translation-', 'duplicate-', 'dedupe-', 'fuzzy-'],
        'Compliance' => ['privacy-', 'security-', 'audit-', 'embargo-', 'cdpa-', 'naz-', 'nmmz-'],
        'Viewers & Media' => ['iiif-', '3d-', 'openseadragon-', 'mirador-', 'audio-', 'pdf-merge-'],
        'Import/Export' => ['data-ingest-', 'export-', 'metadata-ex', 'migration-', 'portable-', 'data-migration-'],
        'Rights' => ['rights-', 'extended-rights-', 'icip-'],
        'Collection Mgmt' => ['condition-', 'provenance-', 'donor-', 'loan-', 'vendor-', 'spectrum-', 'contact-', 'contract-'],
        'Admin & Settings' => ['ahg-settings-', 'statistics-', 'reports-', 'report-builder-', 'workflow-', 'multi-tenant-', 'backup-', 'encryption-'],
        'Browse & Search' => ['repository-browse-', 'term-taxonomy-', 'advanced-search-', 'knowledge-graph-'],
        'Public Access' => ['access-request', 'cart-', 'favorites-', 'feedback-', 'request-to-publish-', 'heritage-site'],
        'Exhibitions' => ['exhibition-', 'landing-page-'],
        'Integration' => ['api-', 'graphql-', 'doi-', 'federation-', 'ric-'],
        'Labels & Forms' => ['label-', 'forms-', 'barcode-'],
        'Heritage Accounting' => ['heritage-accounting-', 'grap-', 'ipsas-'],
    ];

    /** @var array Plugin name detection from filenames */
    protected static $pluginMap = [
        '3d-model' => 'ahg3DModelPlugin',
        'access-request' => 'ahgAccessRequestPlugin',
        'advanced-search' => 'ahgSearchPlugin',
        'ahg-settings' => 'ahgSettingsPlugin',
        'ai-tools' => 'ahgAIPlugin',
        'api-' => 'ahgAPIPlugin',
        'audio-player' => 'ahgIiifPlugin',
        'audit-trail' => 'ahgAuditTrailPlugin',
        'backup-restore' => 'ahgBackupPlugin',
        'barcode' => 'ahgLabelPlugin',
        'cart-' => 'ahgCartPlugin',
        'condition-assessment' => 'ahgConditionPlugin',
        'contact-management' => 'ahgContactPlugin',
        'contract-management' => 'ahgDonorAgreementPlugin',
        'dam-module' => 'ahgDAMPlugin',
        'data-ingest' => 'ahgIngestPlugin',
        'data-migration' => 'ahgDataMigrationPlugin',
        'doi-' => 'ahgDoiPlugin',
        'donor-agreement' => 'ahgDonorAgreementPlugin',
        'duplicate-detection' => 'ahgDedupePlugin',
        'embargo-' => 'ahgExtendedRightsPlugin',
        'encryption-' => 'ahgBackupPlugin',
        'exhibition-' => 'ahgExhibitionPlugin',
        'export-data' => 'ahgExportPlugin',
        'extended-rights' => 'ahgExtendedRightsPlugin',
        'favorites-' => 'ahgFavoritesPlugin',
        'federation-' => 'ahgFederationPlugin',
        'feedback-' => 'ahgFeedbackPlugin',
        'forms-builder' => 'ahgFormsPlugin',
        'fuzzy-search' => 'ahgSearchPlugin',
        'gallery-module' => 'ahgGalleryPlugin',
        'glam-browse' => 'ahgDisplayPlugin',
        'graphql-' => 'ahgGraphQLPlugin',
        'heritage-accounting' => 'ahgHeritageAccountingPlugin',
        'heritage-site' => 'ahgHeritagePlugin',
        'icip-' => 'ahgICIPPlugin',
        'iiif-' => 'ahgIiifPlugin',
        'knowledge-graph' => 'ahgSemanticSearchPlugin',
        'label-printing' => 'ahgLabelPlugin',
        'landing-page' => 'ahgLandingPagePlugin',
        'library-module' => 'ahgLibraryPlugin',
        'loan-module' => 'ahgLoanPlugin',
        'metadata-export' => 'ahgMetadataExportPlugin',
        'metadata-extraction' => 'ahgMetadataExtractionPlugin',
        'migration-tools' => 'ahgMigrationPlugin',
        'mirador-' => 'ahgIiifPlugin',
        'multi-tenant' => 'ahgMultiTenantPlugin',
        'museum-module' => 'ahgMuseumPlugin',
        'ner-' => 'ahgAIPlugin',
        'openseadragon' => 'ahgIiifPlugin',
        'pdf-merge' => 'ahgTiffPdfMergePlugin',
        'portable-export' => 'ahgPortableExportPlugin',
        'preservation-' => 'ahgPreservationPlugin',
        'privacy-' => 'ahgPrivacyPlugin',
        'provenance-' => 'ahgProvenancePlugin',
        'report-builder' => 'ahgReportBuilderPlugin',
        'reports-dashboard' => 'ahgReportsPlugin',
        'repository-browse' => 'ahgRepositoryManagePlugin',
        'request-to-publish' => 'ahgRequestToPublishPlugin',
        'researcher-' => 'ahgResearchPlugin',
        'research-knowledge' => 'ahgResearchPlugin',
        'ric-' => 'ahgRicExplorerPlugin',
        'rights-management' => 'ahgRightsPlugin',
        'security-' => 'ahgSecurityClearancePlugin',
        'semantic-search' => 'ahgSemanticSearchPlugin',
        'spectrum-' => 'ahgSpectrumPlugin',
        'statistics-' => 'ahgStatisticsPlugin',
        'term-taxonomy' => 'ahgTermTaxonomyPlugin',
        'translation-' => 'ahgTranslationPlugin',
        'vendor-' => 'ahgVendorPlugin',
        'workflow-' => 'ahgWorkflowPlugin',
    ];

    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('path', null, sfCommandOption::PARAMETER_OPTIONAL, 'Path to docs directory'),
            new sfCommandOption('file', null, sfCommandOption::PARAMETER_OPTIONAL, 'Import a single file'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview without writing to database'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Force re-import even if unchanged'),
        ]);

        $this->namespace = 'help';
        $this->name = 'import';
        $this->briefDescription = 'Import markdown docs into the help system';
        $this->detailedDescription = <<<'EOF'
The [help:import|INFO] task imports markdown documentation files into the
help_article and help_section database tables for the online help system.

  [php symfony help:import|INFO]                     Import all docs
  [php symfony help:import --dry-run|INFO]           Preview without writing
  [php symfony help:import --file=researcher-user-guide.md|INFO]  Single file
  [php symfony help:import --path=/custom/path|INFO] Custom docs path
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        // Load services
        $pluginDir = sfConfig::get('sf_plugins_dir') . '/ahgHelpPlugin';
        require_once $pluginDir . '/lib/Services/HelpMarkdownParser.php';
        require_once $pluginDir . '/lib/Services/HelpArticleService.php';

        $dryRun = !empty($options['dry-run']);
        $force = !empty($options['force']);

        // Determine docs path
        $docsPath = !empty($options['path'])
            ? $options['path']
            : sfConfig::get('sf_root_dir') . '/atom-extensions-catalog/docs';

        if (!is_dir($docsPath)) {
            $this->logSection('error', "Docs directory not found: {$docsPath}");

            return 1;
        }

        // Collect markdown files
        $files = $this->collectFiles($docsPath, $options);

        if (empty($files)) {
            $this->logSection('help', 'No markdown files found.');

            return 0;
        }

        $this->logSection('help', sprintf('Found %d markdown files to process', count($files)));

        if ($dryRun) {
            $this->logSection('help', '*** DRY RUN — no database writes ***');
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($files as $fileInfo) {
            $relPath = $fileInfo['rel_path'];
            $fullPath = $fileInfo['full_path'];
            $filename = basename($fullPath, '.md');

            // Auto-detect metadata
            $slug = $this->filenameToSlug($filename);
            $category = $this->detectCategory($relPath, $filename);
            $subcategory = $this->detectSubcategory($filename);
            $relatedPlugin = $this->detectPlugin($relPath, $filename);

            // Promote subcategory to category for user guides
            if ($category === 'User Guide' && !empty($subcategory)) {
                $category = $subcategory;
                $subcategory = null;
            }

            if ($dryRun) {
                $this->logSection('dry-run', sprintf(
                    '%-50s → cat=%-18s sub=%-20s plugin=%s',
                    $slug,
                    $category,
                    $subcategory ?: '(none)',
                    $relatedPlugin ?: '(none)'
                ));
                $imported++;

                continue;
            }

            try {
                $markdown = file_get_contents($fullPath);
                if ($markdown === false || trim($markdown) === '') {
                    $this->logSection('skip', "Empty file: {$relPath}");
                    $skipped++;

                    continue;
                }

                // Parse markdown
                $parsed = \AhgHelp\Services\HelpMarkdownParser::parse($markdown);

                // Use parsed title or generate from filename
                $title = !empty($parsed['title']) ? $parsed['title'] : $this->slugToTitle($slug);

                $data = [
                    'title' => $title,
                    'category' => $category,
                    'subcategory' => $subcategory,
                    'source_file' => $relPath,
                    'body_markdown' => $markdown,
                    'body_html' => $parsed['body_html'],
                    'body_text' => $parsed['body_text'],
                    'toc' => $parsed['toc'],
                    'sections' => $parsed['sections'],
                    'word_count' => $parsed['word_count'],
                    'related_plugin' => $relatedPlugin,
                ];

                $articleId = \AhgHelp\Services\HelpArticleService::upsertFromMarkdown($slug, $data);

                if ($articleId) {
                    $this->logSection('import', sprintf(
                        '%-50s [%s] %d words, %d sections',
                        $slug,
                        $category,
                        $parsed['word_count'],
                        count($parsed['sections'])
                    ));
                    $imported++;
                } else {
                    $this->logSection('error', "Failed to upsert: {$slug}");
                    $errors++;
                }
            } catch (\Exception $e) {
                $this->logSection('error', "Error processing {$relPath}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->logSection('help', '');
        $this->logSection('help', sprintf(
            'Done: %d imported, %d skipped, %d errors (total: %d files)',
            $imported,
            $skipped,
            $errors,
            count($files)
        ));

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Collect markdown files to import.
     */
    protected function collectFiles(string $docsPath, array $options): array
    {
        $files = [];

        if (!empty($options['file'])) {
            // Single file mode
            $filename = $options['file'];
            $candidates = [
                $docsPath . '/' . $filename,
                $docsPath . '/technical/' . $filename,
            ];

            foreach ($candidates as $path) {
                if (file_exists($path)) {
                    $relPath = str_replace($docsPath . '/', '', $path);
                    $files[] = ['full_path' => $path, 'rel_path' => $relPath];

                    return $files;
                }
            }
            $this->logSection('error', "File not found: {$filename}");

            return [];
        }

        // Scan top-level docs
        $topLevel = glob($docsPath . '/*.md');
        foreach ($topLevel as $path) {
            $filename = basename($path);
            // Skip README.md
            if (strtoupper($filename) === 'README.MD') {
                continue;
            }
            $files[] = ['full_path' => $path, 'rel_path' => $filename];
        }

        // Scan technical/ subdirectory
        $techDir = $docsPath . '/technical';
        if (is_dir($techDir)) {
            $techFiles = glob($techDir . '/*.md');
            foreach ($techFiles as $path) {
                $filename = basename($path);
                if (strtoupper($filename) === 'README.MD') {
                    continue;
                }
                $files[] = ['full_path' => $path, 'rel_path' => 'technical/' . $filename];
            }
        }

        return $files;
    }

    /**
     * Convert filename to URL slug.
     */
    protected function filenameToSlug(string $filename): string
    {
        // Remove common suffixes
        $slug = preg_replace('/_?user[_-]?guide$/i', '-user-guide', $filename);
        $slug = preg_replace('/_?user[_-]?manual$/i', '-user-manual', $slug);

        // Convert underscores/spaces to hyphens, lowercase
        $slug = strtolower(str_replace(['_', ' '], '-', $slug));

        // Remove duplicate hyphens
        $slug = preg_replace('/-+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * Convert slug back to a human-readable title.
     */
    protected function slugToTitle(string $slug): string
    {
        return ucwords(str_replace('-', ' ', $slug));
    }

    /**
     * Detect category from file path and name.
     */
    protected function detectCategory(string $relPath, string $filename): string
    {
        $lower = strtolower($filename);

        // Technical directory
        if (strpos($relPath, 'technical/') === 0) {
            // Plugin reference files (ahg*.md)
            if (preg_match('/^ahg\w+plugin$/i', $filename) || preg_match('/^ahg\w+$/i', $filename)) {
                return 'Plugin Reference';
            }

            return 'Technical';
        }

        // User guides
        if (preg_match('/user[_-]?guide/i', $lower)) {
            return 'User Guide';
        }

        // User manuals
        if (preg_match('/user[_-]?manual/i', $lower)) {
            return 'User Manual';
        }

        // Known reference docs
        $referenceDocs = ['functions', 'workflows', 'roadmap'];
        if (in_array($lower, $referenceDocs)) {
            return 'Reference';
        }

        return 'Reference';
    }

    /**
     * Detect subcategory from filename keywords.
     */
    protected function detectSubcategory(string $filename): ?string
    {
        $lower = strtolower($filename) . '-';

        foreach (self::$subcategoryMap as $subcategory => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($lower, $keyword) !== false) {
                    return $subcategory;
                }
            }
        }

        return null;
    }

    /**
     * Detect related plugin from file path and name.
     */
    protected function detectPlugin(string $relPath, string $filename): ?string
    {
        // Technical docs: ahgXxxPlugin.md → plugin name directly
        if (strpos($relPath, 'technical/') === 0) {
            $base = basename($filename);
            if (preg_match('/^(ahg\w+Plugin)$/i', $base)) {
                return $base;
            }
            // ahgXxx.md without Plugin suffix
            if (preg_match('/^(ahg\w+)$/i', $base)) {
                return $base . 'Plugin';
            }
        }

        // User guide files: match by keyword
        $lower = strtolower($filename) . '-';

        foreach (self::$pluginMap as $keyword => $plugin) {
            if (strpos($lower, $keyword) !== false) {
                return $plugin;
            }
        }

        return null;
    }
}
