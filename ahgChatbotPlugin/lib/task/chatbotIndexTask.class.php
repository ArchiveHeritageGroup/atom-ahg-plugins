<?php

/**
 * chatbot:index - build or refresh the semantic index Ask the Archive searches.
 *
 * Embeds published descriptions through the AHG AI gateway into Qdrant, and
 * with --prune removes descriptions that are no longer published. Run nightly
 * with --prune so the index never drifts far from the catalogue.
 */
class chatbotIndexTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('culture', null, sfCommandOption::PARAMETER_OPTIONAL, 'Culture to index', 'en'),
            new sfCommandOption('batch', null, sfCommandOption::PARAMETER_OPTIONAL, 'Records per batch', 200),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Max records to index (0 = all)', 0),
            new sfCommandOption('prune', null, sfCommandOption::PARAMETER_NONE, 'After indexing, remove descriptions no longer published'),
            new sfCommandOption('prune-only', null, sfCommandOption::PARAMETER_NONE, 'Only prune; do not index'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Report only (counts; with --prune-only, what would be removed)'),
            new sfCommandOption('skip-text', null, sfCommandOption::PARAMETER_NONE, 'Do not index digital object text (transcripts)'),
        ]);
        $this->namespace = 'chatbot';
        $this->name = 'index';
        $this->briefDescription = 'Build the Ask the Archive semantic index (published descriptions only)';
        $this->detailedDescription = <<<'EOD'
Examples:
  php symfony chatbot:index --dry-run                 How many records would be indexed
  php symfony chatbot:index --prune                   Index, then drop unpublished records (nightly)
  php symfony chatbot:index --prune-only --dry-run    What pruning would remove
EOD;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);
        \AhgCore\Core\AhgDb::init();
        require_once __DIR__.'/../Services/ChatbotVectorIndex.php';

        $index = new \AhgChatbotPlugin\Services\ChatbotVectorIndex();
        $culture = $options['culture'] ?: 'en';
        $dryRun = (bool) $options['dry-run'];
        $pruneOnly = (bool) $options['prune-only'];

        $this->logSection('chatbot', sprintf("Published descriptions (%s): %d - collection %s", $culture, $index->publishedCount($culture), $index->getCollection()));
        $this->logSection('chatbot', sprintf('Digital object texts of published descriptions: %d - collection %s', $index->transcriptCount($culture), $index->textCollection()));

        if ($dryRun && !$pruneOnly) {
            return 0;
        }
        if (!$index->isEnabled()) {
            $this->logSection('chatbot', 'Index unavailable: no gateway API key, or Qdrant unreachable.', null, 'ERROR');

            return 1;
        }

        if (!$pruneOnly) {
            $batch = max(1, (int) $options['batch']);
            $limit = (int) $options['limit'];
            $offset = 0;
            $t = ['indexed' => 0, 'skipped' => 0, 'failed' => 0];
            do {
                $size = $limit > 0 ? min($batch, $limit - array_sum($t)) : $batch;
                if ($size <= 0) {
                    break;
                }
                $res = $index->indexBatch($size, $offset, $culture);
                foreach ($t as $k => $v) {
                    $t[$k] += $res[$k];
                }
                $offset = $res['next_offset'];
                $this->logSection('chatbot', sprintf('indexed=%d skipped=%d failed=%d (offset %d)', $t['indexed'], $t['skipped'], $t['failed'], $offset));
            } while (!$res['done']);
        }

        if (!$pruneOnly && !$options['skip-text']) {
            $offset = 0;
            $t = ['indexed' => 0, 'passages' => 0, 'skipped' => 0, 'failed' => 0];
            do {
                $res = $index->indexTextBatch(max(1, (int) $options['batch']), $offset, $culture);
                foreach ($t as $k => $v) {
                    $t[$k] += $res[$k];
                }
                $offset = $res['next_offset'];
                $this->logSection('chatbot', sprintf('texts indexed=%d passages=%d skipped=%d failed=%d', $t['indexed'], $t['passages'], $t['skipped'], $t['failed']));
            } while (!$res['done']);
        }

        if ($options['prune'] || $pruneOnly) {
            $p = $index->prune($culture, $dryRun);
            $this->logSection('chatbot', sprintf('%s: descriptions checked=%d %s=%d; text passages checked=%d %s=%d%s',
                $dryRun ? 'Prune (dry run)' : 'Pruned', $p['checked'], $dryRun ? 'would_remove' : 'removed', $p['removed'],
                $p['text_checked'], $dryRun ? 'would_remove' : 'removed', $p['text_removed'], $p['failed'] ? ' - FAILED part-way' : ''), null, $p['failed'] ? 'ERROR' : 'INFO');

            return $p['failed'] ? 1 : 0;
        }

        return 0;
    }
}
