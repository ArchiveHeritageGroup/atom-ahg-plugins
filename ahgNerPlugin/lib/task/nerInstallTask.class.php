<?php

/**
 * NER Plugin Install Task
 */
class nerInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'ner';
        $this->name = 'install';
        $this->briefDescription = 'Install ahgNerPlugin database tables';
        $this->detailedDescription = <<<EOF
The [ner:install|INFO] task creates the database tables required by ahgNerPlugin.

  [php symfony ner:install|INFO]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->logSection('ner', 'Installing ahgNerPlugin...');

        // Load SQL file
        $sqlFile = sfConfig::get('sf_plugins_dir') . '/ahgNerPlugin/data/install.sql';
        
        if (!file_exists($sqlFile)) {
            throw new sfException('Install SQL file not found: ' . $sqlFile);
        }

        $this->logSection('ner', 'Running database migrations...');

        // Get database connection
        $databaseManager = new sfDatabaseManager($this->configuration);
        $conn = Propel::getConnection();

        $sql = file_get_contents($sqlFile);
        
        // Split and execute statements
        $statements = array_filter(
            array_map('trim', preg_split('/;[\r\n]+/', $sql)),
            function($stmt) {
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
                    $conn->exec($statement);
                    $executed++;
                } catch (Exception $e) {
                    $this->logSection('ner', 'Warning: ' . $e->getMessage(), null, 'COMMENT');
                }
            }
        }

        $this->logSection('ner', "Executed {$executed} SQL statements");
        $this->logSection('ner', 'Installation complete!');
        
        $this->logBlock([
            '',
            'ahgNerPlugin installed successfully!',
            '',
            'Next steps:',
            '  1. Ensure Python NER service is running:',
            '     systemctl status ahg-ner',
            '',
            '  2. Test the API:',
            '     curl http://192.168.0.112:5002/ner/v1/health',
            '',
            '  3. Clear cache:',
            '     rm -rf cache/*',
            '',
            '  4. Access review dashboard:',
            '     /ner/review',
            ''
        ], 'INFO');
    }
}
