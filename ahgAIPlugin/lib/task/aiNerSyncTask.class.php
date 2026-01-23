<?php
/**
 * NER Training Sync Task
 * Usage: php symfony ai:ner-sync [--export-file]
 */
class aiNerSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('export-file', null, sfCommandOption::PARAMETER_NONE, 'Export to file instead of pushing to server'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show training statistics'),
        ]);

        $this->namespace = 'ai';
        $this->name = 'ner-sync';
        $this->briefDescription = 'Sync NER corrections to training server';
        $this->detailedDescription = <<<EOF
Push NER entity corrections to central training server for model improvement.

  php symfony ai:ner-sync              # Push to central server
  php symfony ai:ner-sync --export-file # Export to JSON file
  php symfony ai:ner-sync --stats      # Show statistics
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        \AhgCore\Core\AhgDb::init();
        require_once dirname(__FILE__) . '/../NerTrainingSync.class.php';

        $sync = new NerTrainingSync();

        if ($options['stats']) {
            $this->logSection('ai', 'NER Training Statistics');
            $stats = $sync->getStats();
            foreach ($stats as $s) {
                $this->logSection('ai', sprintf(
                    '%s: %d total, %d exported, %d pending',
                    $s->correction_type,
                    $s->count,
                    $s->exported,
                    $s->count - $s->exported
                ));
            }
            return;
        }

        if ($options['export-file']) {
            $this->logSection('ai', 'Exporting corrections to file...');
            $result = $sync->exportToFile();
        } else {
            $this->logSection('ai', 'Pushing corrections to training server...');
            $result = $sync->pushCorrections();
        }

        if ($result['status'] === 'success') {
            $this->logSection('ai', sprintf('Exported %d corrections', $result['exported']));
            if (isset($result['file'])) {
                $this->logSection('ai', 'File: ' . $result['file']);
            }
        } elseif ($result['status'] === 'no_data') {
            $this->logSection('ai', 'No new corrections to export');
        } else {
            $this->logSection('ai', 'Error: ' . ($result['message'] ?? 'Unknown'), null, 'ERROR');
        }
    }
}
