<?php

/**
 * CLI task for monitoring POPIA Section 22(1) breach notification deadlines.
 *
 * Checks for breaches that require notification but have not yet been reported
 * to the regulator within the jurisdiction's deadline (default 72 hours).
 *
 * Usage:
 *   php symfony privacy:breach-check                  # Check all pending breaches
 *   php symfony privacy:breach-check --email=admin@x  # Send email alert
 *   php symfony privacy:breach-check --json            # Output as JSON (for cron)
 */
class privacyBreachCheckTask extends sfBaseTask
{
    protected function configure()
    {
        $this->addOptions([
            new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application', 'qubit'),
            new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'cli'),
            new sfCommandOption('email', null, sfCommandOption::PARAMETER_OPTIONAL, 'Email address to send alert to'),
            new sfCommandOption('json', null, sfCommandOption::PARAMETER_NONE, 'Output as JSON'),
        ]);

        $this->namespace = 'privacy';
        $this->name = 'breach-check';
        $this->briefDescription = 'Check breach notification deadlines (POPIA Section 22)';
        $this->detailedDescription = <<<EOF
The [privacy:breach-check|INFO] task monitors data breach notification deadlines.

POPIA Section 22(1) requires notification to the Information Regulator within
72 hours of becoming aware of a breach. Other jurisdictions have their own
deadlines (stored in privacy_jurisdiction.breach_hours).

This task checks all breaches where notification_required=1 but regulator_notified=0,
calculates time remaining, and alerts on overdue or critical (< 12 hours) breaches.

Examples:
  [php symfony privacy:breach-check|INFO]                           # Console report
  [php symfony privacy:breach-check --email=dpo@example.com|INFO]   # Email alert
  [php symfony privacy:breach-check --json|INFO]                    # JSON output (cron)

Recommended cron entry (hourly):
  0 * * * * cd /usr/share/nginx/archive && php symfony privacy:breach-check --email=dpo@example.com >> /var/log/atom/breach-check.log 2>&1
EOF;
    }

    protected function execute($arguments = [], $options = [])
    {
        sfContext::createInstance($this->configuration);

        require_once sfConfig::get('sf_plugins_dir') . '/ahgPrivacyPlugin/lib/Service/PrivacyBreachService.php';

        $service = new \ahgPrivacyPlugin\Service\PrivacyBreachService();
        $result = $service->checkDeadlines();

        // JSON output mode (for cron/monitoring)
        if (isset($options['json']) && $options['json']) {
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

            return $result['overdue_count'] > 0 ? 1 : 0;
        }

        // Console report
        $this->logSection('breach', '=== Breach Notification Deadline Check ===');
        echo "\n";

        if ($result['total_pending'] === 0) {
            $this->logSection('status', 'No breaches pending notification.', null, 'INFO');

            return;
        }

        $this->logSection('summary', sprintf(
            'Pending: %d | Overdue: %d | Critical (< 12h): %d',
            $result['total_pending'],
            $result['overdue_count'],
            $result['critical_count']
        ));
        echo "\n";

        // Show overdue breaches
        if ($result['overdue_count'] > 0) {
            $this->logSection('OVERDUE', 'The following breaches have EXCEEDED their notification deadline:', null, 'ERROR');
            echo "\n";

            foreach ($result['overdue'] as $breach) {
                $hoursOver = abs($breach->hours_remaining);
                echo sprintf(
                    "  [%s] %s — %s overdue (detected %s)\n",
                    $breach->reference_number ?? 'ID:' . $breach->id,
                    $breach->jurisdiction_name ?? $breach->jurisdiction ?? 'Unknown',
                    $this->formatHours($hoursOver) . ' overdue',
                    $breach->detected_date
                );
            }

            echo "\n";
        }

        // Show critical breaches (< 12h remaining)
        if ($result['critical_count'] > 0) {
            $this->logSection('CRITICAL', 'The following breaches are approaching their deadline:', null, 'COMMENT');
            echo "\n";

            foreach ($result['critical'] as $breach) {
                echo sprintf(
                    "  [%s] %s — %s remaining (detected %s)\n",
                    $breach->reference_number ?? 'ID:' . $breach->id,
                    $breach->jurisdiction_name ?? $breach->jurisdiction ?? 'Unknown',
                    $this->formatHours($breach->hours_remaining) . ' remaining',
                    $breach->detected_date
                );
            }

            echo "\n";
        }

        // Send email if requested
        if (!empty($options['email'])) {
            $this->sendAlert($options['email'], $result);
        }

        $this->logSection('help', 'View breaches: /privacy/breaches');
        $this->logSection('help', 'Schedule hourly: crontab -e → 0 * * * * cd ' . sfConfig::get('sf_root_dir') . ' && php symfony privacy:breach-check --email=<DPO email>');
    }

    /**
     * Format hours into a human-readable duration.
     */
    private function formatHours(float $hours): string
    {
        $hours = (int) abs($hours);

        if ($hours < 1) {
            return '< 1 hour';
        }

        if ($hours < 24) {
            return $hours . 'h';
        }

        $days = intdiv($hours, 24);
        $rem = $hours % 24;

        return $days . 'd ' . $rem . 'h';
    }

    /**
     * Send email alert for overdue/critical breaches.
     */
    private function sendAlert(string $to, array $result): void
    {
        if ($result['overdue_count'] === 0 && $result['critical_count'] === 0) {
            $this->logSection('email', 'No overdue or critical breaches — skipping email.', null, 'COMMENT');

            return;
        }

        $subject = sprintf(
            '[BREACH ALERT] %d overdue, %d critical — Action Required',
            $result['overdue_count'],
            $result['critical_count']
        );

        $body = "BREACH NOTIFICATION DEADLINE ALERT\n";
        $body .= str_repeat('=', 50) . "\n\n";
        $body .= "Generated: " . date('Y-m-d H:i:s T') . "\n";
        $body .= "Instance: " . sfConfig::get('app_siteBaseUrl', 'AtoM') . "\n\n";

        if ($result['overdue_count'] > 0) {
            $body .= "OVERDUE BREACHES ({$result['overdue_count']})\n";
            $body .= str_repeat('-', 40) . "\n";

            foreach ($result['overdue'] as $breach) {
                $hoursOver = abs($breach->hours_remaining);
                $body .= sprintf(
                    "  %s | %s | %s overdue | Detected: %s\n",
                    $breach->reference_number ?? 'ID:' . $breach->id,
                    $breach->jurisdiction_name ?? $breach->jurisdiction ?? '?',
                    $this->formatHours($hoursOver),
                    $breach->detected_date
                );
            }

            $body .= "\n";
        }

        if ($result['critical_count'] > 0) {
            $body .= "CRITICAL BREACHES ({$result['critical_count']})\n";
            $body .= str_repeat('-', 40) . "\n";

            foreach ($result['critical'] as $breach) {
                $body .= sprintf(
                    "  %s | %s | %s remaining | Detected: %s\n",
                    $breach->reference_number ?? 'ID:' . $breach->id,
                    $breach->jurisdiction_name ?? $breach->jurisdiction ?? '?',
                    $this->formatHours($breach->hours_remaining),
                    $breach->detected_date
                );
            }

            $body .= "\n";
        }

        $body .= "ACTION REQUIRED: Notify the relevant regulator immediately.\n";
        $body .= "Manage breaches: " . sfConfig::get('app_siteBaseUrl', '') . "/privacy/breaches\n";

        $headers = 'From: ' . sfConfig::get('app_mail_from', 'noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost')) . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "X-Priority: 1\r\n";

        if (@mail($to, $subject, $body, $headers)) {
            $this->logSection('email', "Alert sent to {$to}", null, 'INFO');
        } else {
            $this->logSection('email', "Failed to send alert to {$to}", null, 'ERROR');
        }
    }
}
