<?php

/**
 * sharepoint:status — print health summary across all configured tenants.
 *
 * @phase 1
 */
class sharepointStatusTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'status';
        $this->briefDescription = 'Print SharePoint integration health (tenants, drives, subs, queue depth)';
    }

    protected function execute($arguments = [], $options = [])
    {
        // TODO (Phase 1):
        //   - List sharepoint_tenant rows: name, status, last_token_at, last_error.
        //   - List sharepoint_drive rows: site_title, drive_name, ingest_enabled, last_full_sync_at.
        //   - List sharepoint_sync_state rows: drive, last_run_at, last_status, items_processed.
        // (Phase 2 additions)
        //   - List sharepoint_subscription: drive, expires_at countdown, status.
        //   - sharepoint_event counts by status (last 24h).
        //   - ahg_queue_job depth for queue=integrations.

        throw new \RuntimeException('sharepoint:status not implemented yet');
    }
}
