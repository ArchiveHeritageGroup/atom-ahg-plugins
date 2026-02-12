<?php

namespace AtomFramework\Console\Commands\Display;

use AtomFramework\Console\BaseCommand;

/**
 * Reindex display data in Elasticsearch.
 */
class ReindexCommand extends BaseCommand
{
    protected string $name = 'display:reindex';
    protected string $description = 'Reindex display data in Elasticsearch';
    protected string $detailedDescription = <<<'EOF'
    Updates Elasticsearch documents with display-specific fields.

    Options:
      --batch=100          Number of documents per batch
      --update-mapping     Update ES mapping before reindexing
    EOF;

    protected function configure(): void
    {
        $this->addOption('batch', 'b', 'Batch size for reindexing', '100');
        $this->addOption('update-mapping', null, 'Update ES mapping before reindexing');
    }

    protected function handle(): int
    {
        $pluginDir = $this->getAtomRoot() . '/plugins/ahgDisplayPlugin';
        $esServiceFile = $pluginDir . '/lib/Elasticsearch/DisplayElasticsearchService.php';

        if (!file_exists($esServiceFile)) {
            $this->error("DisplayElasticsearchService not found at: {$esServiceFile}");

            return 1;
        }

        require_once $esServiceFile;

        $service = new \DisplayElasticsearchService();
        $batchSize = (int) $this->option('batch', '100');

        $this->info('Starting display data reindex...');

        // Update mapping if requested
        if ($this->hasOption('update-mapping')) {
            $this->info('Updating Elasticsearch mapping...');
            if ($service->updateMapping()) {
                $this->success('Mapping updated successfully');
            } else {
                $this->error('Failed to update mapping');

                return 1;
            }
        }

        // Check mapping
        if (!$service->hasDisplayMapping()) {
            $this->error('Display mapping not found. Run with --update-mapping first.');

            return 1;
        }

        // Reindex
        $this->info(sprintf('Reindexing with batch size %d...', $batchSize));

        $count = $service->reindexDisplayData($batchSize, function ($processed, $total) {
            $pct = ($total > 0) ? ($processed / $total) * 100 : 0;
            $this->line(sprintf('  Processed %d / %d (%.1f%%)', $processed, $total, $pct));
        });

        $this->success(sprintf('Reindex complete. Updated %d documents.', $count));

        return 0;
    }
}
