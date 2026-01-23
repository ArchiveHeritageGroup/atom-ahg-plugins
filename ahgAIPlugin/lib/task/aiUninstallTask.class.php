<?php

/**
 * AI Plugin Uninstall Task
 */
class aiUninstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('keep-data', null, sfCommandOption::PARAMETER_NONE, 'Keep database tables'),
        ]);

        $this->namespace = 'ai';
        $this->name = 'uninstall';
        $this->briefDescription = 'Uninstall ahgAIPlugin';
        $this->detailedDescription = <<<EOF
The [ai:uninstall|INFO] task removes ahgAIPlugin.

  [php symfony ai:uninstall|INFO]

Use --keep-data to preserve database tables:

  [php symfony ai:uninstall --keep-data|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->logSection('ai', 'Uninstalling ahgAIPlugin...');

        if (!$options['keep-data']) {
            $this->logSection('ai', 'Removing database tables...');

            $databaseManager = new sfDatabaseManager($this->configuration);

            // Load Laravel Query Builder
            $frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            if (file_exists($frameworkBootstrap)) {
                require_once $frameworkBootstrap;
            }

            $tables = [
                'ahg_ai_usage',
                'ahg_ai_settings',
                'ahg_ner_entity_link',
                'ahg_ner_entity',
                'ahg_ner_extraction',
                'ahg_translation_log',
                'ahg_translation_queue'
            ];

            foreach ($tables as $table) {
                try {
                    \Illuminate\Database\Capsule\Manager::statement("DROP TABLE IF EXISTS `{$table}`");
                    $this->logSection('ai', "Dropped table: {$table}");
                } catch (Exception $e) {
                    $this->logSection('ai', 'Warning: ' . $e->getMessage(), null, 'COMMENT');
                }
            }
        } else {
            $this->logSection('ai', 'Keeping database tables (--keep-data specified)');
        }

        $this->logSection('ai', 'Uninstall complete!');
        $this->logBlock([
            '',
            'ahgAIPlugin uninstalled.',
            '',
            'To completely remove:',
            '  1. Disable plugin:',
            '     php bin/atom extension:disable ahgAIPlugin',
            '',
            '  2. Remove plugin files:',
            '     rm -rf plugins/ahgAIPlugin',
            ''
        ], 'INFO');
    }
}
