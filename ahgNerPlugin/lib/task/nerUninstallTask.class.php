<?php

/**
 * NER Plugin Uninstall Task
 */
class nerUninstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('keep-data', null, sfCommandOption::PARAMETER_NONE, 'Keep database tables'),
        ]);

        $this->namespace = 'ner';
        $this->name = 'uninstall';
        $this->briefDescription = 'Uninstall ahgNerPlugin';
        $this->detailedDescription = <<<EOF
The [ner:uninstall|INFO] task removes ahgNerPlugin.

  [php symfony ner:uninstall|INFO]

Use --keep-data to preserve database tables:

  [php symfony ner:uninstall --keep-data|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->logSection('ner', 'Uninstalling ahgNerPlugin...');

        if (!$options['keep-data']) {
            $this->logSection('ner', 'Removing database tables...');
            
            $databaseManager = new sfDatabaseManager($this->configuration);
            $conn = Propel::getConnection();

            $tables = [
                'ahg_ner_usage',
                'ahg_ner_entity',
                'ahg_ner_extraction',
                'ahg_ner_settings'
            ];

            foreach ($tables as $table) {
                try {
                    $conn->exec("DROP TABLE IF EXISTS {$table}");
                    $this->logSection('ner', "Dropped table: {$table}");
                } catch (Exception $e) {
                    $this->logSection('ner', 'Warning: ' . $e->getMessage(), null, 'COMMENT');
                }
            }
        } else {
            $this->logSection('ner', 'Keeping database tables (--keep-data specified)');
        }

        $this->logSection('ner', 'Uninstall complete!');
        $this->logBlock([
            '',
            'ahgNerPlugin uninstalled.',
            '',
            'To completely remove:',
            '  1. Disable plugin:',
            '     php bin/atom extension:disable ahgNerPlugin',
            '',
            '  2. Stop Python service:',
            '     systemctl stop ahg-ner',
            '     systemctl disable ahg-ner',
            '',
            '  3. Remove plugin files:',
            '     rm -rf plugins/ahgNerPlugin',
            ''
        ], 'INFO');
    }
}
