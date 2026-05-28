<?php

declare(strict_types=1);

/**
 * libraryFrbrReindexTask
 *
 * Re-populates the frbr_work_key column for all library_items.
 * Run this after a bulk import, after the work-key algorithm was updated,
 * or to recover from a corrupted column.
 *
 * Usage:
 *   php symfony library:frbr-reindex [--batch=500]
 *
 * @package    ahgLibraryPlugin
 * @subpackage task
 */
class libraryFrbrReindexTask extends sfBaseTask
{
    protected function configure(): void
    {
        $this->namespace        = 'library';
        $this->name             = 'frbr-reindex';
        $this->briefDescription = 'Re-generate frbr_work_key for all library items (full re-index)';
        $this->detailedDescription = <<<'EOT'
Re-generate frbr_work_key for every library_item row.
Unlike frbr-backfill (which only fills empty keys), this task
overwrites ALL keys — useful after an algorithm change or a bulk import.

  php symfony library:frbr-reindex
  php symfony library:frbr-reindex --batch=1000

Reports: items processed, distinct work sets, timing.
EOT;
        $this->addArgument('name', sfCommandArgument::OPTIONAL, '');
        $this->addOption('batch', null, sfCommandOption::PARAMETER_REQUIRED, 'Rows per MySQL batch', 500);
    }

    protected function execute($context = []): int
    {
        $this->logSection('frbr-reindex', 'Starting full FRBR work-key re-index');

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/FrbrService.php';

        $batchSize = (int) $this->configuration->getOption('batch');
        $svc = FrbrService::getInstance();

        // Count total items
        $total = \Illuminate\Database\Capsule\Manager::connection()
            ->table('library_item')
            ->count();

        $this->logSection('frbr-reindex', sprintf('Total library items to re-index: %d', $total));

        if ($total === 0) {
            $this->logSection('frbr-reindex', 'No library items found. Nothing to do.');
            return 0;
        }

        $batches = (int) ceil($total / $batchSize);
        $done = 0;
        $errors = 0;

        for ($batch = 0; $batch < $batches; $batch++) {
            $offset = $batch * $batchSize;

            $items = \Illuminate\Database\Capsule\Manager::connection()
                ->table('library_item')
                ->select('id')
                ->offset($offset)
                ->limit($batchSize)
                ->get()
                ->all();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                try {
                    $key = $svc->computeAndStoreWorkKey((int) $item->id);
                    if ($key === null) {
                        $this->logBlock(
                            "Item {$item->id}: no stable key (title + identifiers all absent) — skipped",
                            'WARNING'
                        );
                    }
                    $done++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->logBlock(
                        "Item {$item->id} error: " . $e->getMessage(),
                        'ERROR'
                    );
                }
            }

            $pct = round(($done / $total) * 100, 1);
            $this->logSection('frbr-reindex',
                sprintf('Batch %d/%d — %d/%d items done (%s%%)',
                    $batch + 1, $batches, $done, $total, $pct));
        }

        $workCount = $svc->countWorks();

        $this->logSection('frbr-reindex',
            sprintf('Done. %d keys generated, %d errors, %d distinct work sets.',
                $done, $errors, $workCount));

        return $errors > 0 ? 1 : 0;
    }
}