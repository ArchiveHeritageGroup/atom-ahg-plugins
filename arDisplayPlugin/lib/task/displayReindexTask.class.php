<?php
/**
 * CLI Task: Reindex display data in Elasticsearch
 * 
 * Usage: php symfony display:reindex [--batch=100]
 */

class displayReindexTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_OPTIONAL, 'Batch size', 100),
            new sfCommandOption('update-mapping', null, sfCommandOption::PARAMETER_NONE, 'Update ES mapping first'),
        ]);

        $this->namespace = 'display';
        $this->name = 'reindex';
        $this->briefDescription = 'Reindex display data in Elasticsearch';
        $this->detailedDescription = <<<EOF
The [display:reindex|INFO] task updates Elasticsearch documents with display-specific fields.

Call it with:

  [php symfony display:reindex|INFO]

Options:
  [--batch=100|INFO]         Number of documents per batch
  [--update-mapping|INFO]    Update ES mapping before reindexing
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $context = sfContext::createInstance($this->configuration);
        
        require_once sfConfig::get('sf_plugins_dir') . '/arDisplayPlugin/lib/Elasticsearch/DisplayElasticsearchService.php';
        
        $service = new DisplayElasticsearchService();
        $batchSize = (int) $options['batch'];
        
        $this->logSection('display', 'Starting display data reindex...');
        
        // Update mapping if requested
        if ($options['update-mapping']) {
            $this->logSection('display', 'Updating Elasticsearch mapping...');
            if ($service->updateMapping()) {
                $this->logSection('display', 'Mapping updated successfully');
            } else {
                $this->logSection('display', 'Failed to update mapping', null, 'ERROR');
                return 1;
            }
        }
        
        // Check mapping
        if (!$service->hasDisplayMapping()) {
            $this->logSection('display', 'Display mapping not found. Run with --update-mapping first.', null, 'ERROR');
            return 1;
        }
        
        // Reindex
        $this->logSection('display', sprintf('Reindexing with batch size %d...', $batchSize));
        
        $count = $service->reindexDisplayData($batchSize, function($processed, $total) {
            $this->logSection('display', sprintf('Processed %d / %d (%.1f%%)', 
                $processed, $total, ($processed / $total) * 100));
        });
        
        $this->logSection('display', sprintf('Reindex complete. Updated %d documents.', $count));
        
        return 0;
    }
}
