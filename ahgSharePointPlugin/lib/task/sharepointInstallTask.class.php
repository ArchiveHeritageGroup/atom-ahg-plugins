<?php

/**
 * sharepoint:install — create plugin tables + ingest_session migration.
 *
 * Idempotent. Safe to re-run. Reports tables touched and any errors.
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
        $this->detailedDescription = <<<EOF
Creates SharePoint integration tables and runs the ingest_session.source migration.

Tables:
  sharepoint_tenant, sharepoint_drive, sharepoint_mapping, sharepoint_sync_state,
  sharepoint_subscription, sharepoint_event

Re-running is safe; CREATE TABLE IF NOT EXISTS guards every statement.
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        $this->logSection('sharepoint', 'install starting');

        // TODO (Phase 1):
        //   1. Read database/install.sql relative to plugin dir (sfConfig::get('sf_plugins_dir') is unreliable; use __DIR__).
        //   2. Read database/migrations/20260510_add_source_to_ingest_session.sql.
        //   3. Execute each via Capsule connection (DB::statement).
        //   4. Honor --dry-run.
        //   5. Report rows affected per statement.

        throw new \RuntimeException('sharepoint:install not implemented yet');
    }
}
