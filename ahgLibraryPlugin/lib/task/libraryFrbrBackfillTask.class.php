<?php

declare(strict_types=1);

/**
 * libraryFrbrBackfillTask
 *
 * Generates frbr_work_key for all library_items that lack one.
 * Run once after the migration to populate the work key column.
 *
 * Usage:
 *   php symfony library:frbr-backfill [--batch=500]
 *
 * @package    ahgLibraryPlugin
 * @subpackage task
 */
class libraryFrbrBackfillTask extends sfBaseTask
{
    protected function configure(): void
    {
        $this->namespace        = 'library';
        $this->name             = 'frbr-backfill';
        $this->briefDescription = 'Compute and store FRBR work keys for all library items';
        $this->detailedDescription = <<<'EOT'
Generate frbr_work_key for every library_item row that lacks one.
Run this after migration_frbr_clustering.sql has been applied.

  php symfony library:frbr-backfill
  php symfony library:frbr-backfill --batch=1000

The task logs progress every 10 batches and reports final counts.
Items with no title (and no isbn/issn/lccn) are skipped — they cannot
receive a stable work key and are logged as warnings.
EOT;
        $this->addArgument('name', sfCommandArgument::OPTIONAL, '');
        $this->addOption('batch', null, sfCommandOption::PARAMETER_REQUIRED, 'Rows per MySQL batch', 500);
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->logSection('frbr-backfill', 'Starting FRBR work-key backfill');

        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once sfConfig::get('sf_root_dir')
            . '/atom-ahg-plugins/ahgLibraryPlugin/lib/Service/FrbrService.php';

        $batchSize = (int) $this->configuration->getOption('batch');
        $svc = FrbrService::getInstance();

        $done = 0;
        $skipped = 0;
        $total = 0;

        foreach ($svc->backfillWorkKeys($batchSize) as $progress) {
            $done  = $progress['done'];
            $total = $progress['total'];
            $batch = $progress['batches'];

            if ($batch % 10 === 0 || $progress['done'] === $progress['total']) {
                $pct = $total > 0 ? round(($done / $total) * 100, 1) : 0;
                $this->logSection('frbr-backfill',
                    sprintf('Batch %d — %d/%d items done (%s%%)',
                        $batch, $done, $total, $pct));
            }
        }

        $this->logSection('frbr-backfill',
            sprintf('Done. %d work keys generated, %d skipped.', $done, $skipped));

        // Summary of works created
        $workCount = $svc->countWorks();
        $this->logSection('frbr-backfill', sprintf('Distinct work sets: %d', $workCount));

        return 0;
    }
}