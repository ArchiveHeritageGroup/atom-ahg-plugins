<?php

declare(strict_types=1);

namespace arMuseumMetadataPlugin\Services\Loan;

use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Loan Notification Service.
 *
 * Manages email notifications for loan events including:
 * - Due date reminders
 * - Status change notifications
 * - Overdue alerts
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class LoanNotificationService
{
    /** Notification triggers */
    public const TRIGGERS = [
        'due_date' => 'Due Date Reminder',
        'overdue' => 'Overdue Alert',
        'status_change' => 'Status Change',
        'approval' => 'Approval Notification',
        'dispatch' => 'Dispatch Notification',
        'receipt' => 'Receipt Confirmation',
        'return' => 'Return Confirmation',
        'extension' => 'Extension Notification',
    ];

    /** Notification statuses */
    public const STATUSES = [
        'pending' => 'Pending',
        'sent' => 'Sent',
        'failed' => 'Failed',
        'bounced' => 'Bounced',
    ];

    private ConnectionInterface $db;
    private LoggerInterface $logger;
    private array $institutionConfig;

    public function __construct(
        ConnectionInterface $db,
        ?LoggerInterface $logger = null,
        array $institutionConfig = []
    ) {
        $this->db = $db;
        $this->logger = $logger ?? new NullLogger();
        $this->institutionConfig = $institutionConfig;
    }

    /**
     * Set institution configuration.
     */
    public function setInstitutionConfig(array $config): void
    {
        $this->institutionConfig = $config;
    }

    /**
     * Get notification template.
     */
    public function getTemplate(string $code): ?array
    {
        $template = $this->db->table('loan_notification_template')
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        return $template ? (array) $template : null;
    }

    /**
     * Get all active templates.
     */
    public function getTemplates(): array
    {
        return $this->db->table('loan_notification_template')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Create or update notification template.
     */
    public function saveTemplate(array $data): int
    {
        $existing = $this->db->table('loan_notification_template')
            ->where('code', $data['code'])
            ->first();

        if ($existing) {
            $this->db->table('loan_notification_template')
                ->where('id', $existing->id)
                ->update([
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'subject_template' => $data['subject_template'],
                    'body_template' => $data['body_template'],
                    'trigger_event' => $data['trigger_event'] ?? null,
                    'trigger_days_before' => $data['trigger_days_before'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $existing->id;
        }

        return $this->db->table('loan_notification_template')->insertGetId([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'subject_template' => $data['subject_template'],
            'body_template' => $data['body_template'],
            'trigger_event' => $data['trigger_event'] ?? null,
            'trigger_days_before' => $data['trigger_days_before'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Queue a notification for a loan.
     *
     * @param int    $loanId       Loan ID
     * @param string $templateCode Template code
     * @param array  $extraData    Additional data for template
     *
     * @return int Notification log ID
     */
    public function queueNotification(int $loanId, string $templateCode, array $extraData = []): int
    {
        $template = $this->getTemplate($templateCode);
        if (!$template) {
            throw new \RuntimeException("Notification template '{$templateCode}' not found");
        }

        $loan = $this->getLoanData($loanId);
        if (!$loan) {
            throw new \RuntimeException("Loan {$loanId} not found");
        }

        // Merge loan data with extra data
        $variables = array_merge($loan, $extraData, $this->institutionConfig);

        // Process templates
        $subject = $this->processTemplate($template['subject_template'], $variables);
        $body = $this->processTemplate($template['body_template'], $variables);

        return $this->db->table('loan_notification_log')->insertGetId([
            'loan_id' => $loanId,
            'template_id' => $template['id'],
            'notification_type' => $template['trigger_event'] ?? $templateCode,
            'recipient_email' => $loan['partner_contact_email'],
            'recipient_name' => $loan['partner_contact_name'],
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Send a queued notification.
     *
     * @param int $notificationId Notification log ID
     *
     * @return bool Success
     */
    public function sendNotification(int $notificationId): bool
    {
        $notification = $this->db->table('loan_notification_log')
            ->where('id', $notificationId)
            ->first();

        if (!$notification) {
            return false;
        }

        if ('sent' === $notification->status) {
            return true; // Already sent
        }

        try {
            // Use PHP mail() function or integrate with your mail system
            $sent = $this->sendEmail(
                $notification->recipient_email,
                $notification->recipient_name,
                $notification->subject,
                $notification->body
            );

            if ($sent) {
                $this->db->table('loan_notification_log')
                    ->where('id', $notificationId)
                    ->update([
                        'status' => 'sent',
                        'sent_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->logger->info('Notification sent', [
                    'notification_id' => $notificationId,
                    'recipient' => $notification->recipient_email,
                ]);

                return true;
            }

            throw new \RuntimeException('Mail function returned false');
        } catch (\Exception $e) {
            $this->db->table('loan_notification_log')
                ->where('id', $notificationId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

            $this->logger->error('Notification failed', [
                'notification_id' => $notificationId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send email via PHP mail or configured mailer.
     */
    private function sendEmail(string $to, ?string $name, string $subject, string $body): bool
    {
        $fromEmail = $this->institutionConfig['email'] ?? 'noreply@example.com';
        $fromName = $this->institutionConfig['name'] ?? 'Loan Management';

        $headers = [
            'From: '.$fromName.' <'.$fromEmail.'>',
            'Reply-To: '.$fromEmail,
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: AtoM Loan Module',
        ];

        $recipient = $name ? "{$name} <{$to}>" : $to;

        return mail($recipient, $subject, $body, implode("\r\n", $headers));
    }

    /**
     * Process pending notifications.
     *
     * @return array Results [sent, failed]
     */
    public function processPendingNotifications(): array
    {
        $pending = $this->db->table('loan_notification_log')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->limit(50) // Process in batches
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($pending as $notification) {
            if ($this->sendNotification($notification->id)) {
                ++$sent;
            } else {
                ++$failed;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Check and queue due date reminders.
     *
     * @return int Number of notifications queued
     */
    public function checkDueDateReminders(): int
    {
        $queued = 0;

        // Get reminder templates
        $templates = $this->db->table('loan_notification_template')
            ->where('trigger_event', 'due_date')
            ->where('is_active', true)
            ->whereNotNull('trigger_days_before')
            ->get();

        foreach ($templates as $template) {
            $targetDate = date('Y-m-d', strtotime("+{$template->trigger_days_before} days"));

            // Find loans due on this date
            $loans = $this->db->table('loan as l')
                ->leftJoin('workflow_instance as wi', function ($join) {
                    $join->on('wi.entity_id', '=', 'l.id')
                        ->where('wi.entity_type', '=', 'loan');
                })
                ->where('l.end_date', $targetDate)
                ->whereNull('l.return_date')
                ->where('wi.is_complete', false)
                ->select('l.*')
                ->get();

            foreach ($loans as $loan) {
                // Check if already sent
                $alreadySent = $this->db->table('loan_notification_log')
                    ->where('loan_id', $loan->id)
                    ->where('template_id', $template->id)
                    ->whereIn('status', ['sent', 'pending'])
                    ->exists();

                if (!$alreadySent && $loan->partner_contact_email) {
                    $this->queueNotification($loan->id, $template->code);
                    ++$queued;
                }
            }
        }

        return $queued;
    }

    /**
     * Check and queue overdue notifications.
     *
     * @return int Number of notifications queued
     */
    public function checkOverdueNotifications(): int
    {
        $template = $this->getTemplate('loan_overdue');
        if (!$template) {
            return 0;
        }

        $queued = 0;

        // Find overdue loans
        $loans = $this->db->table('loan as l')
            ->leftJoin('workflow_instance as wi', function ($join) {
                $join->on('wi.entity_id', '=', 'l.id')
                    ->where('wi.entity_type', '=', 'loan');
            })
            ->where('l.end_date', '<', date('Y-m-d'))
            ->whereNull('l.return_date')
            ->where('wi.is_complete', false)
            ->select('l.*')
            ->get();

        foreach ($loans as $loan) {
            // Only send overdue once per week
            $lastSent = $this->db->table('loan_notification_log')
                ->where('loan_id', $loan->id)
                ->where('notification_type', 'overdue')
                ->where('status', 'sent')
                ->orderByDesc('sent_at')
                ->first();

            $shouldSend = !$lastSent ||
                strtotime($lastSent->sent_at) < strtotime('-7 days');

            if ($shouldSend && $loan->partner_contact_email) {
                $this->queueNotification($loan->id, 'loan_overdue');
                ++$queued;
            }
        }

        return $queued;
    }

    /**
     * Send status change notification.
     */
    public function notifyStatusChange(int $loanId, string $newStatus, ?string $comment = null): ?int
    {
        $templateMap = [
            'approved' => 'loan_approved',
            'dispatched' => 'loan_dispatched',
        ];

        $templateCode = $templateMap[$newStatus] ?? null;
        if (!$templateCode) {
            return null; // No template for this status
        }

        $template = $this->getTemplate($templateCode);
        if (!$template) {
            return null;
        }

        return $this->queueNotification($loanId, $templateCode, [
            'new_status' => $newStatus,
            'comment' => $comment,
        ]);
    }

    /**
     * Get notification log for a loan.
     */
    public function getNotificationLog(int $loanId): array
    {
        return $this->db->table('loan_notification_log as n')
            ->leftJoin('loan_notification_template as t', 't.id', '=', 'n.template_id')
            ->where('n.loan_id', $loanId)
            ->select('n.*', 't.name as template_name')
            ->orderByDesc('n.created_at')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * Get loan data for template processing.
     */
    private function getLoanData(int $loanId): ?array
    {
        $loan = $this->db->table('loan')
            ->where('id', $loanId)
            ->first();

        if (!$loan) {
            return null;
        }

        return (array) $loan;
    }

    /**
     * Process template with variable substitution.
     */
    private function processTemplate(string $template, array $variables): string
    {
        // Replace {{variable}} placeholders
        return preg_replace_callback(
            '/\{\{(\w+)\}\}/',
            function ($matches) use ($variables) {
                $key = $matches[1];

                return $variables[$key] ?? '';
            },
            $template
        );
    }

    /**
     * Get notification statistics.
     */
    public function getStatistics(): array
    {
        $stats = [
            'total' => $this->db->table('loan_notification_log')->count(),
            'pending' => $this->db->table('loan_notification_log')->where('status', 'pending')->count(),
            'sent' => $this->db->table('loan_notification_log')->where('status', 'sent')->count(),
            'failed' => $this->db->table('loan_notification_log')->where('status', 'failed')->count(),
        ];

        $stats['sent_today'] = $this->db->table('loan_notification_log')
            ->where('status', 'sent')
            ->where('sent_at', '>=', date('Y-m-d 00:00:00'))
            ->count();

        $stats['sent_this_week'] = $this->db->table('loan_notification_log')
            ->where('status', 'sent')
            ->where('sent_at', '>=', date('Y-m-d 00:00:00', strtotime('-7 days')))
            ->count();

        return $stats;
    }

    /**
     * Retry failed notifications.
     */
    public function retryFailed(int $notificationId): bool
    {
        $this->db->table('loan_notification_log')
            ->where('id', $notificationId)
            ->update([
                'status' => 'pending',
                'error_message' => null,
            ]);

        return $this->sendNotification($notificationId);
    }
}
