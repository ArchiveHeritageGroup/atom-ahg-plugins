<?php

/**
 * Install ahgAiConditionPlugin database tables.
 *
 * Usage: php symfony ai-condition:install
 */
class aiConditionInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
        ]);

        $this->namespace = 'ai-condition';
        $this->name = 'install';
        $this->briefDescription = 'Install AI Condition Assessment tables';
        $this->detailedDescription = 'Creates database tables for the AI-powered condition assessment plugin.';
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $this->logSection('ai-condition', 'Installing AI Condition Assessment plugin...');

        // Bootstrap Laravel DB
        $bootstrap = sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
        if (file_exists($bootstrap)) {
            require_once $bootstrap;
        }

        $sqlFile = sfConfig::get('sf_root_dir') . '/atom-ahg-plugins/ahgAiConditionPlugin/database/install.sql';

        if (!file_exists($sqlFile)) {
            $this->logSection('error', 'install.sql not found', null, 'ERROR');
            return 1;
        }

        $sql = file_get_contents($sqlFile);

        // Strip standalone comment lines (keep inline COMMENT 'x' in SQL)
        $lines = explode("\n", $sql);
        $cleaned = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip lines that are only comments or empty
            if ($trimmed === '' || strpos($trimmed, '--') === 0) {
                continue;
            }
            $cleaned[] = $line;
        }
        $sql = implode("\n", $cleaned);

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($stmt) {
                $stmt = trim($stmt);
                return !empty($stmt);
            }
        );

        $success = 0;
        $errors = 0;

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            // Skip lock/unlock table statements
            if (preg_match('/^(LOCK|UNLOCK)\s+TABLES/i', $statement)) {
                continue;
            }
            // Skip /*!40000 directives
            if (preg_match('/^\/\*!\d+/', $statement)) {
                continue;
            }

            try {
                \Illuminate\Database\Capsule\Manager::statement($statement);
                $success++;

                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`(\w+)`/i', $statement, $m);
                    $this->logSection('create', 'Table: ' . ($m[1] ?? 'unknown'));
                } elseif (stripos($statement, 'INSERT') !== false) {
                    $this->logSection('seed', 'Seed data inserted');
                }
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                // Ignore "already exists" errors
                if (stripos($msg, 'already exists') !== false || stripos($msg, 'Duplicate') !== false) {
                    $this->logSection('skip', 'Already exists (OK)');
                } else {
                    $this->logSection('error', $msg, null, 'ERROR');
                    $errors++;
                }
            }
        }

        $this->logSection('ai-condition', "Installation complete. {$success} statements OK, {$errors} errors.");
        $this->logSection('info', '');
        $this->logSection('info', 'Next steps:');
        $this->logSection('info', '  1. Enable plugin: php bin/atom extension:enable ahgAiConditionPlugin');
        $this->logSection('info', '  2. Start AI service: /usr/share/nginx/archive/ai-condition-service/scripts/start.sh');
        $this->logSection('info', '  3. Configure at: /ai-condition/settings');
        $this->logSection('info', '  4. Clear cache: php symfony cc');

        return $errors > 0 ? 1 : 0;
    }
}
