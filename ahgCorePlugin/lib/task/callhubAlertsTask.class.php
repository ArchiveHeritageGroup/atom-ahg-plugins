<?php

/**
 * callhubAlertsTask - raise CallHub tickets for open errors.
 *
 * Copyright (C) 2026 Johan Pieterse
 * Plain Sailing Information Systems
 * The Archive and Heritage Group (Pty) Ltd
 * Licensed under the GNU Affero General Public License v3.0 or later.
 *
 * Usage:  php symfony callhub:alerts [--dry-run] [--limit=50]
 * Intended for cron:
 *   7 * * * *  cd /usr/share/nginx/archive && php symfony callhub:alerts
 *
 * Drains open ahg_error_log rows at error level into the workbench notification
 * spool, which raises the CallHub ticket. A CLI drain rather than an inline call so
 * a page render never waits on another application.
 *
 * No-ops unless callhub_alerts_enabled is on. Warnings and notices are never
 * ticketed - they stay in the error log.
 */
class callhubAlertsTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would be sent, claim nothing and write nothing'),
            new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'Maximum tickets to raise in one run', 50),
        ]);

        $this->namespace = 'callhub';
        $this->name = 'alerts';
        $this->briefDescription = 'Raise CallHub tickets for open error-level rows in the error log.';
        $this->detailedDescription = <<<'EOF'
The [callhub:alerts|INFO] task turns open error-log rows into CallHub tickets, via
the workbench notification spool.

One ticket per fault per ISO week. A fault is claimed in ahg_error_alert before
anything is written, and that table has a unique key on (signature, period), so a
re-run or a concurrent run cannot raise a second ticket for the same fault in the
same week. A fault that recurs next week raises a fresh ticket, which is why the
period is in the key at all.

Errors only. Warnings and notices stay in the error log.

  [php symfony callhub:alerts --dry-run|INFO]
  [php symfony callhub:alerts --limit=10|INFO]
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        $dryRun = (bool) ($options['dry-run'] ?? false);
        $limit = max(1, min((int) ($options['limit'] ?? 50), 500));

        if (!$dryRun && !$this->isEnabled()) {
            $this->logSection('callhub', 'callhub_alerts_enabled is off - nothing sent.');

            return 0;
        }

        if (!$dryRun && !$this->tableExists()) {
            $this->logSection('callhub', 'ahg_error_alert is missing - run database/add_error_alert_table.sql first. Nothing sent.');

            return 1;
        }

        // A workbench projects.id uuid, or empty. NOT a client name - CallHub
        // assigns the client. Empty means the ticket carries no project link.
        $project = $this->setting('callhub_project', '');
        $username = $this->setting('callhub_notify_username', 'johan');
        $configuredPriority = $this->setting('callhub_priority', '');
        $period = \AhgCore\Services\CallHubAlertService::period();

        try {
            $rows = \Illuminate\Database\Capsule\Manager::table('ahg_error_log')
                ->whereNull('resolved_at')
                ->whereIn('level', \AhgCore\Services\CallHubAlertService::TICKET_LEVELS)
                ->whereNotNull('signature')
                ->orderBy('id')
                ->limit($limit)
                ->get();
        } catch (\Throwable $e) {
            $this->logSection('callhub', 'error log read failed: ' . $e->getMessage());

            return 1;
        }

        $sent = $skipped = $failed = 0;

        foreach ($rows as $row) {
            $signature = (string) $row->signature;
            $ref = \AhgCore\Services\CallHubAlertService::externalRef($signature, $period);

            if ($dryRun) {
                $this->logSection('callhub', sprintf(
                    'would raise %s [%s] - %s',
                    $ref,
                    \AhgCore\Services\CallHubAlertService::priorityFor($row, $configuredPriority),
                    \AhgCore\Services\CallHubAlertService::title($row)
                ));
                ++$sent;

                continue;
            }

            // Claim before writing. A lost claim means already sent this period.
            if (!\AhgCore\Services\CallHubAlertService::claim($signature, $period, $ref, (int) $row->id)) {
                ++$skipped;

                continue;
            }

            $payload = \AhgCore\Services\CallHubAlertService::payload($row, $ref, $project, $username, \AhgCore\Services\CallHubAlertService::priorityFor($row, $configuredPriority));

            if (\AhgCore\Services\CallHubAlertService::writeSpool($payload)) {
                ++$sent;
                $this->logSection('callhub', 'raised ' . $ref);
            } else {
                // Give the claim back so the next run retries rather than losing it.
                \AhgCore\Services\CallHubAlertService::release($ref);
                ++$failed;
                $this->logSection('callhub', 'spool write failed, claim released: ' . $ref);
            }
        }

        $this->logSection('callhub', sprintf(
            '%s%d raised, %d already sent this period, %d failed (period %s).',
            $dryRun ? '[dry run] ' : '',
            $sent,
            $skipped,
            $failed,
            $period
        ));

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Without the claim table every claim() fails, which would read as "already
     * sent" in the counters. Check once so a missing schema is reported as one.
     */
    private function tableExists(): bool
    {
        try {
            return \Illuminate\Database\Capsule\Manager::schema()->hasTable('ahg_error_alert');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isEnabled(): bool
    {
        return in_array(strtolower((string) $this->setting('callhub_alerts_enabled', 'false')), ['1', 'true', 'on', 'yes'], true);
    }

    private function setting(string $key, string $default): string
    {
        try {
            if (class_exists('\AtomExtensions\Services\AhgSettingsService')) {
                $v = \AtomExtensions\Services\AhgSettingsService::get($key, $default);

                return is_string($v) && '' !== $v ? $v : $default;
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return $default;
    }
}
