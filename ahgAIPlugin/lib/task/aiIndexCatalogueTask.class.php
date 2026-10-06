<?php

/**
 * CLI task: build / refresh the gateway-fed semantic search index.
 *
 * Embeds published information objects via the AHG AI gateway
 * (nomic-embed-text) and upserts them into the Qdrant "{db}_io_nomic"
 * collection used by the collection chatbot's hybrid retrieval.
 *
 * Requires a gateway API key in ahg_ai_settings (feature='gateway').
 */
class aiIndexCatalogueTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture to index', 'en'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_OPTIONAL, 'Records per batch', 200),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max records to process (0 = all)', 0),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Count + report only, no embedding/upsert'),
            new sfCommandOption('prune', null, sfCommandOption::PARAMETER_NONE, 'After indexing, remove points for descriptions no longer published'),
            new sfCommandOption('prune-only', null, sfCommandOption::PARAMETER_NONE, 'Only prune; do not (re)index'),
        ]);
        $this->namespace = 'ai';
        $this->name = 'index-catalogue';
        $this->briefDescription = 'Build the gateway-fed semantic search index (Qdrant) for published descriptions';
        $this->detailedDescription = <<<EOD
The [ai:index-catalogue|INFO] task embeds published descriptions through the AHG
AI gateway and stores the vectors in Qdrant for semantic catalogue search.

Examples:
  [php symfony ai:index-catalogue --dry-run|INFO]      Show how many records would be indexed
  [php symfony ai:index-catalogue|INFO]                Index all published descriptions
  [php symfony ai:index-catalogue --limit=500|INFO]    Index up to 500
  [php symfony ai:index-catalogue --prune|INFO]        Index, then drop unpublished/deleted records (nightly)
  [php symfony ai:index-catalogue --prune-only --dry-run|INFO]  Report what pruning would remove
EOD;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();

        require_once \sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/lib/Services/CatalogueVectorService.php';

        $svc = new \CatalogueVectorService();

        $culture = $options['culture'] ?: 'en';
        $batch = max(1, (int) $options['batch']);
        $hardLimit = (int) $options['limit'];
        $dryRun = (bool) $options['dry-run'];

        $total = $svc->publishedCount($culture);
        $this->logSection('ai', "Published descriptions for culture '{$culture}': {$total}");

        if ($dryRun && !$options['prune-only']) {
            $this->logSection('ai', 'Dry run — no embedding/upsert performed.');

            return 0;
        }

        if (!$svc->isEnabled()) {
            $this->logSection('ai', 'Semantic index disabled: gateway key not set (ahg_ai_settings feature=gateway) or Qdrant unreachable.', null, 'ERROR');

            return 1;
        }

        $this->logSection('ai', 'Collection: ' . $svc->getCollection());

        $offset = 0;
        $totals = ['indexed' => 0, 'skipped' => 0, 'failed' => 0];
        while (!$options['prune-only']) {
            $thisBatch = $batch;
            if ($hardLimit > 0) {
                $remaining = $hardLimit - ($totals['indexed'] + $totals['skipped'] + $totals['failed']);
                if ($remaining <= 0) {
                    break;
                }
                $thisBatch = min($batch, $remaining);
            }

            $res = $svc->indexBatch($thisBatch, $offset, $culture, false);
            $totals['indexed'] += $res['indexed'];
            $totals['skipped'] += $res['skipped'];
            $totals['failed'] += $res['failed'];
            $offset = $res['next_offset'];

            $this->logSection('ai', sprintf(
                'progress: indexed=%d skipped=%d failed=%d (offset %d)',
                $totals['indexed'], $totals['skipped'], $totals['failed'], $offset
            ));

            if ($res['done']) {
                break;
            }
        }

        if (!$options['prune-only']) {
            $this->logSection('ai', sprintf(
                'Done. indexed=%d skipped=%d failed=%d',
                $totals['indexed'], $totals['skipped'], $totals['failed']
            ));
        }

        // Indexing only adds. Without this, a description deleted or
        // unpublished after it was embedded stays in the index for good.
        if ($options['prune'] || $options['prune-only']) {
            $p = $svc->prune($culture, $dryRun);
            $this->logSection('ai', sprintf(
                '%s: checked=%d %s=%d%s',
                $dryRun ? 'Prune (dry run)' : 'Pruned',
                $p['checked'],
                $dryRun ? 'would_remove' : 'removed',
                $p['removed'],
                $p['failed'] ? ' - FAILED part-way, see Qdrant' : ''
            ), null, $p['failed'] ? 'ERROR' : 'INFO');

            if ($p['failed']) {
                return 1;
            }
        }

        return 0;
    }
}
