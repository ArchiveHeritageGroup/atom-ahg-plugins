<?php

/**
 * sharepoint:sync — manual / cron-driven delta poll for one or all drives.
 *
 * In Phase 1 this is the primary ingest mechanism (no webhooks yet).
 * In Phase 2+ it serves as a fallback when webhooks miss events.
 *
 * @phase 1
 */
class sharepointSyncTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
            new sfCommandOption('drive', null, sfCommandOption::PARAMETER_OPTIONAL, 'sharepoint_drive.id (omit to sync all ingest-enabled drives)'),
            new sfCommandOption('full', null, sfCommandOption::PARAMETER_NONE, 'Discard delta cursor and resync from scratch'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_OPTIONAL, 'Cap items per drive', 0),
        ]);

        $this->namespace = 'sharepoint';
        $this->name = 'sync';
        $this->briefDescription = 'Delta-poll one or all ingest-enabled SharePoint drives';
    }

    protected function execute($arguments = [], $options = [])
    {
        // TODO (Phase 1):
        //   1. Resolve drive list (one or all ingest_enabled).
        //   2. For each drive:
        //      - Read sharepoint_sync_state.delta_link (or null if --full).
        //      - GET delta page; iterate .value; for each item, hand to SharePointIngestAdapter.
        //      - Persist returned @odata.deltaLink as new delta_link.
        //      - Update last_run_at, last_status, items_processed.
        //   3. On error: write last_error, set last_status='error', do NOT advance delta_link.

        throw new \RuntimeException('sharepoint:sync not implemented yet');
    }
}
