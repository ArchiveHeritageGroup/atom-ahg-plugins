<?php

namespace AhgCore\Services;

use AtomFramework\Contracts\QueueJobInterface;
use AtomFramework\Services\QueueJobContext;

/**
 * Queue handler for async error alert emails.
 *
 * Dispatched by ErrorNotificationService when QueueService is available.
 * Falls back to sync EmailService::send() if queue is unavailable.
 */
class ErrorAlertQueueHandler implements QueueJobInterface
{
    /**
     * Process the error alert email job.
     */
    public function handle(array $payload, QueueJobContext $context): array
    {
        $to = $payload['to'] ?? '';
        $subject = $payload['subject'] ?? 'System Error Alert';
        $body = $payload['body'] ?? '';

        if (empty($to) || empty($body)) {
            return ['status' => 'skipped', 'reason' => 'Missing to or body'];
        }

        // Ensure EmailService is loaded
        $emailServiceFile = __DIR__ . '/EmailService.php';
        if (!class_exists('\AhgCore\Services\EmailService', false)) {
            if (file_exists($emailServiceFile)) {
                require_once $emailServiceFile;
            } else {
                return ['status' => 'error', 'reason' => 'EmailService not found'];
            }
        }

        $sent = EmailService::send($to, $subject, $body);

        return [
            'status' => $sent ? 'sent' : 'failed',
            'to' => $to,
        ];
    }

    /**
     * No retries for error alert emails.
     */
    public function maxAttempts(): int
    {
        return 1;
    }

    /**
     * 30-second timeout for sending email.
     */
    public function timeout(): int
    {
        return 30;
    }
}
