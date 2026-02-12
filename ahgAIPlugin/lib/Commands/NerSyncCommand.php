<?php

namespace AtomFramework\Console\Commands\Ai;

use AtomFramework\Console\BaseCommand;

/**
 * NER Training Sync Command.
 */
class NerSyncCommand extends BaseCommand
{
    protected string $name = 'ai:ner-sync';
    protected string $description = 'Sync NER corrections to training server';
    protected string $detailedDescription = <<<'EOF'
    Push NER entity corrections to central training server for model improvement.

    Examples:
      php bin/atom ai:ner-sync                 # Push to central server
      php bin/atom ai:ner-sync --export-file   # Export to JSON file
      php bin/atom ai:ner-sync --stats         # Show statistics
    EOF;

    protected function configure(): void
    {
        $this->addOption('export-file', null, 'Export to file instead of pushing to server');
        $this->addOption('stats', null, 'Show training statistics');
    }

    protected function handle(): int
    {
        require_once dirname(__DIR__) . '/NerTrainingSync.class.php';

        $sync = new \NerTrainingSync();

        if ($this->hasOption('stats')) {
            $this->info('NER Training Statistics');
            $stats = $sync->getStats();
            foreach ($stats as $s) {
                $this->line(sprintf(
                    '  %s: %d total, %d exported, %d pending',
                    $s->correction_type,
                    $s->count,
                    $s->exported,
                    $s->count - $s->exported
                ));
            }
            return 0;
        }

        if ($this->hasOption('export-file')) {
            $this->info('Exporting corrections to file...');
            $result = $sync->exportToFile();
        } else {
            $this->info('Pushing corrections to training server...');
            $result = $sync->pushCorrections();
        }

        if ($result['status'] === 'success') {
            $this->success(sprintf('Exported %d corrections', $result['exported']));
            if (isset($result['file'])) {
                $this->info('File: ' . $result['file']);
            }
        } elseif ($result['status'] === 'no_data') {
            $this->info('No new corrections to export');
        } else {
            $this->error('Error: ' . ($result['message'] ?? 'Unknown'));
        }

        return 0;
    }
}
