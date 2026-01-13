<?php
/**
 * NER Training Sync Task
 * Usage: php symfony ner:sync [--export-file]
 */
class nerSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('export-file', null, sfCommandOption::PARAMETER_NONE, 'Export to file instead of pushing to server'),
            new sfCommandOption('stats', null, sfCommandOption::PARAMETER_NONE, 'Show training statistics'),
        ]);
        
        $this->namespace = 'ner';
        $this->name = 'sync';
        $this->briefDescription = 'Sync NER corrections to training server';
        $this->detailedDescription = <<<EOF
Push NER entity corrections to central training server for model improvement.

  php symfony ner:sync              # Push to central server
  php symfony ner:sync --export-file # Export to JSON file
  php symfony ner:sync --stats      # Show statistics
EOF;
    }
    
    protected function execute($arguments = [], $options = [])
    {
        require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        require_once dirname(__FILE__) . '/../NerTrainingSync.class.php';
        
        $sync = new NerTrainingSync();
        
        if ($options['stats']) {
            $this->logSection('ner', 'Training Statistics');
            $stats = $sync->getStats();
            foreach ($stats as $s) {
                $this->logSection('ner', sprintf(
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
            $this->logSection('ner', 'Exporting corrections to file...');
            $result = $sync->exportToFile();
        } else {
            $this->logSection('ner', 'Pushing corrections to training server...');
            $result = $sync->pushCorrections();
        }
        
        if ($result['status'] === 'success') {
            $this->logSection('ner', sprintf('Exported %d corrections', $result['exported']));
            if (isset($result['file'])) {
                $this->logSection('ner', 'File: ' . $result['file']);
            }
        } elseif ($result['status'] === 'no_data') {
            $this->logSection('ner', 'No new corrections to export');
        } else {
            $this->logSection('ner', 'Error: ' . ($result['message'] ?? 'Unknown'), null, 'ERROR');
        }
    }
}
