<?php

/**
 * AI Plugin Install Task
 */
class aiInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'ai';
        $this->name = 'install';
        $this->briefDescription = 'Install ahgAIPlugin database tables';
        $this->detailedDescription = <<<EOF
The [ai:install|INFO] task creates the database tables required by ahgAIPlugin.

  [php symfony ai:install|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->logSection('ai', 'Installing ahgAIPlugin...');

        // Load SQL file
        $sqlFile = sfConfig::get('sf_plugins_dir') . '/ahgAIPlugin/data/install.sql';

        if (!file_exists($sqlFile)) {
            throw new sfException('Install SQL file not found: ' . $sqlFile);
        }

        $this->logSection('ai', 'Running database migrations...');

        // Initialize database connection
        $databaseManager = new sfDatabaseManager($this->configuration);

        // Load Laravel Query Builder
        $frameworkBootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($frameworkBootstrap)) {
            require_once $frameworkBootstrap;
        }

        $sql = file_get_contents($sqlFile);

        // Split and execute statements
        $statements = array_filter(
            array_map('trim', preg_split('/;[\r\n]+/', $sql)),
            function ($stmt) {
                $stmt = trim($stmt);
                return !empty($stmt) &&
                       strpos($stmt, '--') !== 0 &&
                       strpos($stmt, '/*') !== 0;
            }
        );

        $executed = 0;
        foreach ($statements as $statement) {
            if (!empty(trim($statement))) {
                try {
                    \Illuminate\Database\Capsule\Manager::statement($statement);
                    $executed++;
                } catch (Exception $e) {
                    $this->logSection('ai', 'Warning: ' . $e->getMessage(), null, 'COMMENT');
                }
            }
        }

        $this->logSection('ai', "Executed {$executed} SQL statements");
        $this->logSection('ai', 'Installation complete!');

        $this->logBlock([
            '',
            'ahgAIPlugin installed successfully!',
            '',
            'Features available:',
            '  - NER: Named Entity Recognition',
            '  - Translation: Offline machine translation (Argos)',
            '  - Summarization: AI-powered text summarization',
            '  - Spellcheck: Spelling and grammar checking',
            '',
            'CLI Commands:',
            '  php symfony ai:ner-extract --help',
            '  php symfony ai:translate --help',
            '  php symfony ai:summarize --help',
            '  php symfony ai:spellcheck --help',
            '',
            'Next steps:',
            '  1. Clear cache: rm -rf cache/*',
            '  2. Install translation packages (optional):',
            '     pip install argostranslate',
            ''
        ], 'INFO');
    }
}
