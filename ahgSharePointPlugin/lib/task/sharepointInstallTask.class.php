<?php

/**
 * sharepoint:install — create plugin tables + ingest_session migration.
 *
 * Idempotent. Safe to re-run. The framework rule forbids ADD COLUMN IF NOT
 * EXISTS, so this task introspects information_schema before applying ALTER
 * statements.
 *
 * @phase 1
 */
class sharepointInstallTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Print SQL without executing'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'install';
        $this->briefDescription = 'Install ahgSharePointPlugin schema (idempotent)';
    }

    public function execute($arguments = [], $options = [])
    {
        $this->logSection('sharepoint', 'install starting');
        $dryRun = !empty($options['dry-run']);

        $pluginDir = realpath(__DIR__ . '/../../');
        $installSql = $pluginDir . '/database/install.sql';
        $migrationsDir = $pluginDir . '/database/migrations';
        $viewsDir = $pluginDir . '/database/views';

        if (!file_exists($installSql)) {
            throw new \RuntimeException("install.sql missing at {$installSql}");
        }

        // Phase 1+2 base tables (CREATE TABLE IF NOT EXISTS — safe to re-run)
        $this->runSqlFile($installSql, $dryRun);

        // ingest_session.source migration — guarded by information_schema check
        $this->ensureIngestSessionSourceColumns($dryRun);

        // sharepoint_drive.auto_ingest_labels migration — same pattern
        $this->ensureColumnExists('sharepoint_drive', 'auto_ingest_labels', "TEXT DEFAULT NULL COMMENT 'JSON array of compliance tag names that trigger auto-ingest in mode B'", $dryRun);

        // Reporting views
        if (is_dir($viewsDir)) {
            foreach (glob($viewsDir . '/*.sql') as $viewFile) {
                $this->runSqlFile($viewFile, $dryRun);
            }
        }

        $this->logSection('sharepoint', 'install complete');
    }

    private function runSqlFile(string $path, bool $dryRun): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new \RuntimeException("Cannot read {$path}");
        }
        if ($dryRun) {
            $this->log('--- ' . basename($path) . ' ---');
            $this->log($sql);
            return;
        }
        // heratio#130: run the whole file as one batch. The previous naive
        // preg_split('/;\s*\n/') + skip-if-starts-with-'--' dropped every
        // statement whose chunk began with a comment header, silently skipping
        // most CREATE TABLEs. unprepared() executes the file verbatim and
        // throws on any SQL error rather than skipping it.
        \Illuminate\Database\Capsule\Manager::unprepared($sql);
        $this->logSection('sharepoint', 'applied ' . basename($path));
    }

    private function ensureIngestSessionSourceColumns(bool $dryRun): void
    {
        if (!$this->tableExists('ingest_session')) {
            $this->logSection('sharepoint', 'skip ingest_session migration — table absent (ahgIngestPlugin not installed?)');
            return;
        }
        $this->ensureColumnExists(
            'ingest_session',
            'source',
            "VARCHAR(20) NOT NULL DEFAULT 'wizard' COMMENT 'wizard, sharepoint_auto, sharepoint_push, api'",
            $dryRun,
        );
        $this->ensureColumnExists(
            'ingest_session',
            'source_id',
            "INT DEFAULT NULL COMMENT 'Origin record id (e.g., sharepoint_event.id)'",
            $dryRun,
        );
    }

    private function ensureColumnExists(string $table, string $column, string $definition, bool $dryRun): void
    {
        if (!$this->tableExists($table)) {
            return;
        }
        if ($this->columnExists($table, $column)) {
            return;
        }
        $sql = "ALTER TABLE {$table} ADD COLUMN {$column} {$definition}";
        if ($dryRun) {
            $this->log($sql);
            return;
        }
        \Illuminate\Database\Capsule\Manager::statement($sql);
        $this->logSection('sharepoint', "added column {$table}.{$column}");
    }

    private function tableExists(string $table): bool
    {
        $row = \Illuminate\Database\Capsule\Manager::selectOne(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );
        return $row !== null;
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = \Illuminate\Database\Capsule\Manager::selectOne(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );
        return $row !== null;
    }
}
