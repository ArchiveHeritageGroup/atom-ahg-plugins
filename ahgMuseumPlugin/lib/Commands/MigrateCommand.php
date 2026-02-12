<?php

namespace AtomFramework\Console\Commands\Museum;

use AtomFramework\Console\BaseCommand;
use Illuminate\Database\Capsule\Manager as DB;

class MigrateCommand extends BaseCommand
{
    protected string $name = 'museum:migrate';
    protected string $description = 'Run museum metadata database migrations';
    protected string $detailedDescription = <<<'EOF'
    Run database migrations for the museum metadata plugin.

    Examples:
      php bin/atom museum:migrate             # Run pending migrations
      php bin/atom museum:migrate --status    # Show migration status
      php bin/atom museum:migrate --rollback  # Rollback migrations
    EOF;

    protected function configure(): void
    {
        $this->addOption('rollback', null, 'Rollback migrations');
        $this->addOption('status', null, 'Show migration status');
    }

    protected function handle(): int
    {
        $frameworkDir = $this->getAtomRoot() . '/atom-framework';
        $migrationRunnerClass = '\\AtomFramework\\Museum\\Database\\Migrations\\MigrationRunner';

        // Try to load the migration runner
        $migrationRunnerFile = $frameworkDir . '/src/Museum/Database/Migrations/MigrationRunner.php';
        if (file_exists($migrationRunnerFile)) {
            require_once $migrationRunnerFile;
        }

        if (!class_exists($migrationRunnerClass)) {
            $this->error('MigrationRunner class not found. Ensure atom-framework Museum module is installed.');

            return 1;
        }

        $migrationRunner = new $migrationRunnerClass();

        if ($this->hasOption('status')) {
            return $this->showStatus($migrationRunner);
        }

        if ($this->hasOption('rollback')) {
            return $this->rollbackMigrations($migrationRunner);
        }

        return $this->runMigrations($migrationRunner);
    }

    private function runMigrations($runner): int
    {
        $this->info('Running museum metadata migrations...');

        try {
            $results = $runner->runAll();

            foreach ($results as $name => $status) {
                if ('success' === $status) {
                    $this->success($name);
                } elseif ('skipped' === $status) {
                    $this->comment("{$name} (already run)");
                } else {
                    $this->error("{$name}: {$status}");
                }
            }

            $this->success('Migrations complete!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());

            return 1;
        }
    }

    private function rollbackMigrations($runner): int
    {
        $this->info('Rolling back museum metadata migrations...');

        try {
            $success = $runner->rollback('create_museum_object_properties_table');

            if ($success) {
                $this->success('Rollback complete!');

                return 0;
            } else {
                $this->error('Rollback failed');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Rollback failed: ' . $e->getMessage());

            return 1;
        }
    }

    private function showStatus($runner): int
    {
        $this->info('Museum metadata migration status:');

        try {
            $status = $runner->getStatus();

            foreach ($status as $name => $state) {
                if ('run' === $state) {
                    $this->success("{$name}: {$state}");
                } else {
                    $this->comment("{$name}: {$state}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Status check failed: ' . $e->getMessage());

            return 1;
        }
    }
}
