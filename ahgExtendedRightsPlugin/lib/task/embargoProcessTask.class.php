<?php

/**
 * CLI task for processing embargo operations.
 *
 * Handles automatic embargo lifting and expiry notifications.
 * Intended for daily cron execution.
 *
 * Usage:
 *   php symfony embargo:process                    # Process all (lift + notify)
 *   php symfony embargo:process --dry-run          # Preview without changes
 *   php symfony embargo:process --notify-only      # Send notifications only
 *   php symfony embargo:process --lift-only        # Lift expired embargoes only
 *
 * Cron example (daily at 6am):
 *   0 6 * * * cd /usr/share/nginx/archive && php symfony embargo:process
 */
class embargoProcessTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('dry-run', null, sfCommandOption::PARAMETER_NONE, 'Preview changes without executing'),
            new sfCommandOption('notify-only', null, sfCommandOption::PARAMETER_NONE, 'Only send expiry notifications'),
            new sfCommandOption('lift-only', null, sfCommandOption::PARAMETER_NONE, 'Only lift expired embargoes'),
            new sfCommandOption('warn-days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Days before expiry to warn (comma-separated)', '30,7,1'),
        ]);

        $this->namespace = 'embargo';
        $this->name = 'process';
        $this->briefDescription = 'Process embargo expiry: auto-lift and send notifications';
        $this->detailedDescription = <<<EOF
The [embargo:process|INFO] task processes embargo expiry operations.

This command should be run daily via cron (recommended: 6am).

Operations performed:
1. Lift embargoes with end_date < today and auto_release=1
2. Send expiry warning notifications (30, 7, 1 days before)

Examples:
  [php symfony embargo:process|INFO]                    # Process all operations
  [php symfony embargo:process --dry-run|INFO]          # Preview without changes
  [php symfony embargo:process --notify-only|INFO]      # Only send notifications
  [php symfony embargo:process --lift-only|INFO]        # Only lift expired embargoes
  [php symfony embargo:process --warn-days=14,7,3|INFO] # Custom warning intervals

Cron setup:
  [0 6 * * * cd /usr/share/nginx/archive && php symfony embargo:process|COMMENT]
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        // Load required services
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoService.php';
        require_once sfConfig::get('sf_root_dir') . '/plugins/ahgExtendedRightsPlugin/lib/Services/EmbargoNotificationService.php';

        $dryRun = $options['dry-run'];
        $runAll = !$options['notify-only'] && !$options['lift-only'];
        $warnDays = array_map('intval', explode(',', $options['warn-days']));

        if ($dryRun) {
            $this->logSection('embargo', '*** DRY RUN MODE - No changes will be made ***', null, 'COMMENT');
        }

        $this->logSection('embargo', 'Starting embargo processing...');

        $results = [
            'lifted' => 0,
            'notifications_sent' => 0,
            'notifications_failed' => 0,
            'errors' => [],
        ];

        // 1. Lift expired embargoes
        if ($runAll || $options['lift-only']) {
            $liftResults = $this->liftExpiredEmbargoes($dryRun);
            $results['lifted'] = $liftResults['lifted'];
            $results['errors'] = array_merge($results['errors'], $liftResults['errors']);
        }

        // 2. Send expiry notifications
        if ($runAll || $options['notify-only']) {
            $notifyResults = $this->sendExpiryNotifications($warnDays, $dryRun);
            $results['notifications_sent'] = $notifyResults['sent'];
            $results['notifications_failed'] = $notifyResults['failed'];
        }

        // Summary
        $this->logSection('embargo', '=== Processing Complete ===');
        $this->logSection('embargo', "Embargoes lifted: {$results['lifted']}");
        $this->logSection('embargo', "Notifications sent: {$results['notifications_sent']}");
        $this->logSection('embargo', "Notifications failed: {$results['notifications_failed']}");

        if (!empty($results['errors'])) {
            $this->logSection('embargo', 'Errors encountered:', null, 'ERROR');
            foreach ($results['errors'] as $error) {
                $this->logSection('embargo', "  - {$error}", null, 'ERROR');
            }
        }
    }

    /**
     * Lift expired embargoes with auto_release enabled.
     *
     * @param bool $dryRun Preview mode
     *
     * @return array Results
     */
    protected function liftExpiredEmbargoes(bool $dryRun): array
    {
        $this->logSection('lift', 'Checking for expired embargoes to lift...');

        $today = date('Y-m-d');

        // Find embargoes to lift
        $expiredEmbargoes = \Illuminate\Database\Capsule\Manager::table('rights_embargo as e')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('e.object_id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->where('e.status', 'active')
            ->where('e.auto_release', true)
            ->whereNotNull('e.end_date')
            ->where('e.end_date', '<', $today)
            ->select(['e.*', 'ioi.title as object_title'])
            ->get();

        $results = [
            'lifted' => 0,
            'errors' => [],
        ];

        if ($expiredEmbargoes->isEmpty()) {
            $this->logSection('lift', 'No expired embargoes found');

            return $results;
        }

        $this->logSection('lift', "Found {$expiredEmbargoes->count()} expired embargoes");

        $notificationService = new \ahgExtendedRightsPlugin\Services\EmbargoNotificationService();

        foreach ($expiredEmbargoes as $embargo) {
            $title = $embargo->object_title ?? "Object #{$embargo->object_id}";

            if ($dryRun) {
                $this->logSection('lift', "[DRY RUN] Would lift: {$title} (embargo #{$embargo->id}, ended {$embargo->end_date})", null, 'INFO');
                $results['lifted']++;

                continue;
            }

            try {
                // Lift the embargo
                \Illuminate\Database\Capsule\Manager::table('rights_embargo')
                    ->where('id', $embargo->id)
                    ->update([
                        'status' => 'lifted',
                        'lifted_at' => date('Y-m-d H:i:s'),
                        'lift_reason' => 'Auto-released after expiry date',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $results['lifted']++;
                $this->logSection('lift', "Lifted: {$title} (embargo #{$embargo->id})", null, 'INFO');

                // Send notification
                try {
                    $notificationService->sendLiftedNotification($embargo, 'Auto-released after expiry date');
                } catch (\Exception $e) {
                    // Non-fatal - log but continue
                    $this->logSection('lift', "Warning: Failed to send lifted notification for embargo #{$embargo->id}: " . $e->getMessage(), null, 'COMMENT');
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Failed to lift embargo #{$embargo->id}: " . $e->getMessage();
                $this->logSection('lift', "Error lifting embargo #{$embargo->id}: " . $e->getMessage(), null, 'ERROR');
            }
        }

        return $results;
    }

    /**
     * Send expiry warning notifications.
     *
     * @param array $warnDays Days before expiry to warn
     * @param bool  $dryRun   Preview mode
     *
     * @return array Results
     */
    protected function sendExpiryNotifications(array $warnDays, bool $dryRun): array
    {
        $this->logSection('notify', 'Checking for embargoes expiring soon...');

        $results = [
            'sent' => 0,
            'failed' => 0,
        ];

        $notificationService = new \ahgExtendedRightsPlugin\Services\EmbargoNotificationService();

        foreach ($warnDays as $days) {
            $targetDate = date('Y-m-d', strtotime("+{$days} days"));

            // Find embargoes expiring on target date that haven't been notified yet
            $expiringEmbargoes = \Illuminate\Database\Capsule\Manager::table('rights_embargo as e')
                ->leftJoin('information_object_i18n as ioi', function ($join) {
                    $join->on('e.object_id', '=', 'ioi.id')
                        ->where('ioi.culture', '=', 'en');
                })
                ->where('e.status', 'active')
                ->where('e.end_date', $targetDate)
                ->where(function ($q) use ($days) {
                    // Only notify if notify_before_days >= current warning days
                    $q->whereNull('e.notify_before_days')
                        ->orWhere('e.notify_before_days', '>=', $days);
                })
                ->select(['e.*', 'ioi.title as object_title'])
                ->get();

            if ($expiringEmbargoes->isEmpty()) {
                continue;
            }

            $this->logSection('notify', "Found {$expiringEmbargoes->count()} embargoes expiring in {$days} days");

            foreach ($expiringEmbargoes as $embargo) {
                $title = $embargo->object_title ?? "Object #{$embargo->object_id}";

                // Skip if already notified for this warning level
                $alreadyNotified = $this->hasRecentNotification($embargo->id, $days);
                if ($alreadyNotified) {
                    continue;
                }

                if ($dryRun) {
                    $this->logSection('notify', "[DRY RUN] Would notify: {$title} ({$days} days warning)", null, 'INFO');
                    $results['sent']++;

                    continue;
                }

                try {
                    $sent = $notificationService->sendExpiryNotification($embargo, $days);
                    if ($sent) {
                        $results['sent']++;
                        $this->logSection('notify', "Sent {$days}-day warning: {$title}", null, 'INFO');
                    } else {
                        $results['failed']++;
                        $this->logSection('notify', "Failed to send warning for: {$title} (no recipients)", null, 'COMMENT');
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $this->logSection('notify', "Error sending notification for embargo #{$embargo->id}: " . $e->getMessage(), null, 'ERROR');
                }
            }
        }

        if ($results['sent'] === 0 && $results['failed'] === 0) {
            $this->logSection('notify', 'No embargoes require notification');
        }

        return $results;
    }

    /**
     * Check if embargo has received a recent notification.
     *
     * @param int $embargoId Embargo ID
     * @param int $days      Days before expiry
     *
     * @return bool
     */
    protected function hasRecentNotification(int $embargoId, int $days): bool
    {
        // Check embargo_notification_log if it exists
        try {
            $tableExists = \Illuminate\Database\Capsule\Manager::select("SHOW TABLES LIKE 'embargo_notification_log'");
            if (!empty($tableExists)) {
                $recent = \Illuminate\Database\Capsule\Manager::table('embargo_notification_log')
                    ->where('embargo_id', $embargoId)
                    ->where('notification_type', 'expiry_warning')
                    ->where('days_before', $days)
                    ->where('sent_at', '>=', date('Y-m-d 00:00:00'))
                    ->exists();

                return $recent;
            }

            // Fallback: check embargo_audit
            $recent = \Illuminate\Database\Capsule\Manager::table('embargo_audit')
                ->where('embargo_id', $embargoId)
                ->where('action', 'notification_expiry_warning')
                ->where('performed_at', '>=', date('Y-m-d 00:00:00'))
                ->whereRaw("JSON_EXTRACT(details, '$.days_before') = ?", [$days])
                ->exists();

            return $recent;
        } catch (\Exception $e) {
            return false;
        }
    }
}
