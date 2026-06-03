<?php

/**
 * CLI task: Execute due scheduled backups.
 *
 * Usage:
 *   php symfony backup:run-scheduled          # Run all due schedules
 *   php symfony backup:run-scheduled --dry-run # Show what would run
 *   php symfony backup:run-scheduled --force   # Run regardless of schedule
 *
 * Cron (every hour):
 *   0 * * * * cd /usr/share/nginx/archive && php symfony backup:run-scheduled >> /var/log/atom/backup-cron.log 2>&1
 */
class backupRunScheduledTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Show what would run without executing'),
            new sfCommandOption('force', null, sfCommandOption::PARAMETER_NONE, 'Run all active schedules regardless of timing'),
        ]);

        $this->namespace = 'backup';
        $this->name = 'run-scheduled';
        $this->briefDescription = 'Execute due scheduled backups';
        $this->detailedDescription = <<<'EOF'
Checks backup_schedule table for active schedules that are due to run,
then executes each one via BackupService::createBackup().

Designed to be called by cron every hour:
  0 * * * * cd /usr/share/nginx/archive && php symfony backup:run-scheduled
EOF;
    }

    public function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Init Laravel DB
        require_once sfConfig::get('sf_plugins_dir') . '/ahgCorePlugin/lib/Core/AhgDb.php';
        \AhgCore\Core\AhgDb::init();

        $dryRun = isset($options['dry-run']) && $options['dry-run'];
        $force = isset($options['force']) && $options['force'];

        $now = new \DateTime();
        $this->logSection('backup', 'Checking scheduled backups at ' . $now->format('Y-m-d H:i:s'));

        $schedules = \Illuminate\Database\Capsule\Manager::table('backup_schedule')
            ->where('is_active', 1)
            ->get();

        if ($schedules->isEmpty()) {
            $this->logSection('backup', 'No active schedules found');
            return;
        }

        $executed = 0;

        foreach ($schedules as $schedule) {
            $isDue = $force || $this->isDue($schedule, $now);

            if (!$isDue) {
                $this->logSection('backup', sprintf('  [skip] "%s" (%s) — not due', $schedule->name, $schedule->frequency));
                continue;
            }

            $this->logSection('backup', sprintf('  [due] "%s" (%s)', $schedule->name, $schedule->frequency));

            if ($dryRun) {
                $this->logSection('backup', '  [dry-run] Would execute backup');
                continue;
            }

            // Execute the backup
            try {
                $backupService = new \AhgBackup\Services\BackupService();
                $result = $backupService->createBackup([
                    'database' => (bool) $schedule->include_database,
                    'uploads' => (bool) $schedule->include_uploads,
                    'plugins' => (bool) $schedule->include_plugins,
                    'framework' => (bool) $schedule->include_framework,
                    'type' => 'scheduled',
                    'schedule_id' => $schedule->id,
                ]);

                // Update schedule last_run and next_run
                \Illuminate\Database\Capsule\Manager::table('backup_schedule')
                    ->where('id', $schedule->id)
                    ->update([
                        'last_run' => $now->format('Y-m-d H:i:s'),
                        'next_run' => $this->calculateNextRun($schedule, $now)->format('Y-m-d H:i:s'),
                        'updated_at' => $now->format('Y-m-d H:i:s'),
                    ]);

                if ($result['status'] === 'completed') {
                    $size = $backupService->formatSize($result['size'] ?? 0);
                    $this->logSection('backup', "  [ok] Backup {$result['id']} completed ({$size})");
                    $this->sendNotification($schedule, $result, 'success');
                    $executed++;
                } else {
                    $this->logSection('backup', '  [fail] ' . ($result['error'] ?? 'Unknown error'));
                    $this->sendNotification($schedule, $result, 'failure');
                }

                // Enforce per-schedule retention
                if ($schedule->retention_days > 0) {
                    $this->enforceRetention($schedule);
                }
            } catch (\Exception $e) {
                $this->logSection('backup', '  [error] ' . $e->getMessage());
                $this->sendNotification($schedule, ['error' => $e->getMessage()], 'failure');
            }
        }

        $this->logSection('backup', "Done. Executed {$executed} backup(s).");
    }

    /**
     * Check if a schedule is due to run now.
     */
    private function isDue(object $schedule, \DateTime $now): bool
    {
        $currentHour = (int) $now->format('G');
        $currentMinute = (int) $now->format('i');
        $scheduleTime = explode(':', $schedule->time ?? '02:00:00');
        $scheduleHour = (int) ($scheduleTime[0] ?? 2);
        $scheduleMinute = (int) ($scheduleTime[1] ?? 0);

        // Only trigger within the scheduled hour (cron runs hourly)
        if ($currentHour !== $scheduleHour) {
            return false;
        }

        // Check if already ran today/this period
        if ($schedule->last_run) {
            $lastRun = new \DateTime($schedule->last_run);
            $diffHours = ($now->getTimestamp() - $lastRun->getTimestamp()) / 3600;

            switch ($schedule->frequency) {
                case 'hourly':
                    if ($diffHours < 0.9) return false;
                    break;
                case 'daily':
                    if ($diffHours < 23) return false;
                    break;
                case 'weekly':
                    if ($diffHours < 167) return false; // ~7 days
                    $dayOfWeek = (int) $now->format('w'); // 0=Sunday
                    if ($schedule->day_of_week !== null && $dayOfWeek !== (int) $schedule->day_of_week) return false;
                    break;
                case 'monthly':
                    if ($diffHours < 672) return false; // ~28 days
                    $dayOfMonth = (int) $now->format('j');
                    if ($schedule->day_of_month !== null && $dayOfMonth !== (int) $schedule->day_of_month) return false;
                    break;
            }
        } else {
            // Never ran — check day constraints
            if ($schedule->frequency === 'weekly' && $schedule->day_of_week !== null) {
                if ((int) $now->format('w') !== (int) $schedule->day_of_week) return false;
            }
            if ($schedule->frequency === 'monthly' && $schedule->day_of_month !== null) {
                if ((int) $now->format('j') !== (int) $schedule->day_of_month) return false;
            }
        }

        return true;
    }

    /**
     * Calculate the next run time for a schedule.
     */
    private function calculateNextRun(object $schedule, \DateTime $from): \DateTime
    {
        $next = clone $from;
        $time = explode(':', $schedule->time ?? '02:00:00');
        $next->setTime((int) ($time[0] ?? 2), (int) ($time[1] ?? 0));

        switch ($schedule->frequency) {
            case 'hourly':
                $next->modify('+1 hour');
                break;
            case 'daily':
                $next->modify('+1 day');
                break;
            case 'weekly':
                $next->modify('+1 week');
                if ($schedule->day_of_week !== null) {
                    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    $next->modify('next ' . $days[(int) $schedule->day_of_week]);
                    $next->setTime((int) ($time[0] ?? 2), (int) ($time[1] ?? 0));
                }
                break;
            case 'monthly':
                $next->modify('+1 month');
                if ($schedule->day_of_month !== null) {
                    $day = min((int) $schedule->day_of_month, (int) $next->format('t'));
                    $next->setDate((int) $next->format('Y'), (int) $next->format('m'), $day);
                }
                break;
        }

        return $next;
    }

    /**
     * Enforce per-schedule retention (delete old backups created by this schedule).
     */
    private function enforceRetention(object $schedule): void
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$schedule->retention_days} days"));

        $old = \Illuminate\Database\Capsule\Manager::table('backup_history')
            ->where('backup_type', 'scheduled')
            ->where('status', 'completed')
            ->where('created_at', '<', $cutoff)
            ->get();

        if ($old->isEmpty()) return;

        $backupService = new \AhgBackup\Services\BackupService();
        foreach ($old as $backup) {
            try {
                $backupService->deleteBackup($backup->backup_id);
                $this->logSection('backup', "  [retention] Deleted old backup {$backup->backup_id}");
            } catch (\Exception $e) {
                // Continue
            }
        }
    }

    /**
     * Send email notification on backup success/failure.
     */
    private function sendNotification(object $schedule, array $result, string $type): void
    {
        $settingsService = new \AtomExtensions\Services\BackupSettingsService();
        $email = $settingsService->get('notify_email', '');

        if (empty($email)) return;

        $notifySuccess = $settingsService->get('notify_on_success', false);
        $notifyFailure = $settingsService->get('notify_on_failure', true);

        if ($type === 'success' && !$notifySuccess) return;
        if ($type === 'failure' && !$notifyFailure) return;

        $siteName = \sfConfig::get('app_siteTitle', 'AtoM');
        $host = gethostname();

        if ($type === 'success') {
            $subject = "[{$siteName}] Backup completed: {$schedule->name}";
            $body = "Scheduled backup \"{$schedule->name}\" completed successfully.\n\n"
                . "Backup ID: " . ($result['id'] ?? 'N/A') . "\n"
                . "Size: " . (new \AhgBackup\Services\BackupService())->formatSize($result['size'] ?? 0) . "\n"
                . "Server: {$host}\n"
                . "Time: " . date('Y-m-d H:i:s') . "\n";
        } else {
            $subject = "[{$siteName}] Backup FAILED: {$schedule->name}";
            $body = "Scheduled backup \"{$schedule->name}\" FAILED.\n\n"
                . "Error: " . ($result['error'] ?? 'Unknown error') . "\n"
                . "Server: {$host}\n"
                . "Time: " . date('Y-m-d H:i:s') . "\n"
                . "\nPlease check /var/log/atom/backup.log for details.\n";
        }

        @mail($email, $subject, $body, "From: noreply@{$host}\r\nContent-Type: text/plain; charset=utf-8");
    }
}
