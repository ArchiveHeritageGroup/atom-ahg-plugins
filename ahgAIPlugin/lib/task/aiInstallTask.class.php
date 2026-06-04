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

    public function execute($arguments = [], $options = [])
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

        // Add the Collection assistant nav link (idempotent; same as ai:install-menu).
        try {
            $conn = \Illuminate\Database\Capsule\Manager::connection();
            if ($conn->table('menu')->where('name', 'collectionAssistant')->exists()) {
                $this->logSection('ai', 'Collection assistant nav link already present.');
            } elseif ($parent = $conn->table('menu')->where('name', 'manage')->first()) {
                $r = (int) $parent->rgt;
                $pid = (int) $parent->id;
                $now = date('Y-m-d H:i:s');
                $conn->transaction(function () use ($conn, $r, $pid, $now) {
                    $conn->update('UPDATE menu SET rgt = rgt + 2 WHERE rgt >= ?', [$r]);
                    $conn->update('UPDATE menu SET lft = lft + 2 WHERE lft >= ?', [$r]);
                    $id = $conn->table('menu')->insertGetId([
                        'parent_id' => $pid, 'name' => 'collectionAssistant', 'path' => 'ai/assistant',
                        'lft' => $r, 'rgt' => $r + 1, 'source_culture' => 'en',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $conn->table('menu_i18n')->insert(['id' => $id, 'culture' => 'en', 'label' => 'Collection assistant']);
                    $agg = $conn->table('menu')->selectRaw('COUNT(*) n, MIN(lft) mn, MAX(rgt) mx')->first();
                    $bad = (int) $conn->table('menu')->whereRaw('rgt <= lft')->count();
                    if ((int) $agg->n !== (int) ((($agg->mx - $agg->mn) + 1) / 2) || $bad > 0) {
                        throw new \RuntimeException('menu nested-set integrity check failed');
                    }
                });
                $this->logSection('ai', 'Added Collection assistant nav link under Manage.');
            }
        } catch (Exception $e) {
            $this->logSection('ai', 'Nav link skipped: ' . $e->getMessage(), null, 'COMMENT');
        }

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
