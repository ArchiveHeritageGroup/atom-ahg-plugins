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
        $db = \Illuminate\Database\Capsule\Manager::class;

        $this->log('');
        $this->log('=== Tenants ===');
        $tenants = \Illuminate\Database\Capsule\Manager::table('sharepoint_tenant')->get();
        foreach ($tenants as $t) {
            $this->log(sprintf(
                '  #%d %-30s %-12s last_token=%s%s',
                $t->id,
                substr($t->name, 0, 30),
                $t->status,
                $t->last_token_at ?? 'never',
                $t->last_error ? ' ERROR: ' . substr($t->last_error, 0, 80) : '',
            ));
        }

        $this->log('');
        $this->log('=== Drives (ingest-enabled) ===');
        $drives = \Illuminate\Database\Capsule\Manager::table('sharepoint_drive')
            ->where('ingest_enabled', 1)->get();
        foreach ($drives as $d) {
            $allowlist = $d->auto_ingest_labels ? json_decode($d->auto_ingest_labels, true) : [];
            $labels = is_array($allowlist) ? implode(', ', $allowlist) : 'none';
            $this->log(sprintf(
                '  #%d %s / %s  sector=%s  labels=[%s]',
                $d->id, $d->site_title ?? '?', $d->drive_name ?? '?', $d->sector, $labels,
            ));
        }

        $this->log('');
        $this->log('=== Sync state ===');
        $sync = \Illuminate\Database\Capsule\Manager::table('sharepoint_sync_state')->get();
        foreach ($sync as $s) {
            $this->log(sprintf(
                '  drive=%d  last_run=%s  status=%s  items=%d%s',
                $s->drive_id, $s->last_run_at ?? 'never', $s->last_status ?? '—', $s->items_processed,
                $s->last_error ? ' ERROR: ' . substr($s->last_error, 0, 80) : '',
            ));
        }

        $this->log('');
        $this->log('=== Active subscriptions ===');
        $subs = \Illuminate\Database\Capsule\Manager::table('sharepoint_subscription')
            ->where('status', 'active')->get();
        foreach ($subs as $sub) {
            $diff = strtotime($sub->expires_at) - time();
            $hoursLeft = round($diff / 3600, 1);
            $this->log(sprintf(
                '  drive=%d  resource=%s  expires_in=%sh',
                $sub->drive_id, substr($sub->resource, 0, 60), $hoursLeft,
            ));
        }

        $this->log('');
        $this->log('=== Event status (last 24h) ===');
        $rows = \Illuminate\Database\Capsule\Manager::table('sharepoint_event')
            ->select('status', \Illuminate\Database\Capsule\Manager::raw('COUNT(*) as n'))
            ->whereRaw('received_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)')
            ->groupBy('status')->get();
        foreach ($rows as $r) {
            $this->log(sprintf('  %-25s %d', $r->status, $r->n));
        }
        if (count($rows) === 0) {
            $this->log('  (no events in last 24h)');
        }
    }
}
