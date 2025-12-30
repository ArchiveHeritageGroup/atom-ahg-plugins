<?php

/**
 * Symfony task for running museum metadata migrations.
 */
class museumMigrateTask extends sfBaseTask
{
    /**
     * @see sfTask
     */
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('rollback', null, sfCommandOption::PARAMETER_NONE, 'Rollback migrations'),
            new sfCommandOption('status', null, sfCommandOption::PARAMETER_NONE, 'Show migration status'),
        ]);

        $this->namespace = 'museum';
        $this->name = 'migrate';
        $this->briefDescription = 'Run museum metadata database migrations';

        $this->detailedDescription = <<<'EOF'
The [museum:migrate|INFO] task runs database migrations for the museum metadata plugin.

  [./symfony museum:migrate|INFO]

To check migration status without running:

  [./symfony museum:migrate --status|INFO]

To rollback migrations:

  [./symfony museum:migrate --rollback|INFO]
EOF;
    }

    /**
     * @see sfTask
     *
     * @param mixed $arguments
     * @param mixed $options
     */
    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Bootstrap the framework
        $container = require sfConfig::get('sf_root_dir').'/atom-framework/bootstrap.php';

        $migrationRunner = $container->get(\AtomFramework\Museum\Database\Migrations\MigrationRunner::class);

        if ($options['status']) {
            $this->showStatus($migrationRunner);

            return;
        }

        if ($options['rollback']) {
            $this->rollbackMigrations($migrationRunner);

            return;
        }

        $this->runMigrations($migrationRunner);
    }

    /**
     * Run all pending migrations.
     *
     * @param \AtomFramework\Museum\Database\Migrations\MigrationRunner $runner
     */
    private function runMigrations($runner)
    {
        $this->logSection('museum', 'Running museum metadata migrations...');

        try {
            $results = $runner->runAll();

            foreach ($results as $name => $status) {
                if ('success' === $status) {
                    $this->logSection('museum', "✓ {$name}", null, 'INFO');
                } elseif ('skipped' === $status) {
                    $this->logSection('museum', "- {$name} (already run)", null, 'COMMENT');
                } else {
                    $this->logSection('museum', "✗ {$name}: {$status}", null, 'ERROR');
                }
            }

            $this->logSection('museum', 'Migrations complete!', null, 'INFO');
        } catch (Exception $e) {
            $this->logSection('museum', 'Migration failed: '.$e->getMessage(), null, 'ERROR');

            throw $e;
        }
    }

    /**
     * Rollback migrations.
     *
     * @param \AtomFramework\Museum\Database\Migrations\MigrationRunner $runner
     */
    private function rollbackMigrations($runner)
    {
        $this->logSection('museum', 'Rolling back museum metadata migrations...');

        try {
            $success = $runner->rollback('create_museum_object_properties_table');

            if ($success) {
                $this->logSection('museum', 'Rollback complete!', null, 'INFO');
            } else {
                $this->logSection('museum', 'Rollback failed', null, 'ERROR');
            }
        } catch (Exception $e) {
            $this->logSection('museum', 'Rollback failed: '.$e->getMessage(), null, 'ERROR');

            throw $e;
        }
    }

    /**
     * Show migration status.
     *
     * @param \AtomFramework\Museum\Database\Migrations\MigrationRunner $runner
     */
    private function showStatus($runner)
    {
        $this->logSection('museum', 'Museum metadata migration status:');

        try {
            $status = $runner->getStatus();

            foreach ($status as $name => $state) {
                $symbol = 'run' === $state ? '✓' : '○';
                $color = 'run' === $state ? 'INFO' : 'COMMENT';
                $this->logSection('museum', "{$symbol} {$name}: {$state}", null, $color);
            }
        } catch (Exception $e) {
            $this->logSection('museum', 'Status check failed: '.$e->getMessage(), null, 'ERROR');

            throw $e;
        }
    }
}
