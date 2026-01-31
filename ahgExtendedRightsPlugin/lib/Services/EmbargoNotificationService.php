<?php

namespace ahgExtendedRightsPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * EmbargoNotificationService - Handles email notifications for embargo events.
 *
 * Features:
 * - Send expiry warning notifications (30/7/1 days before)
 * - Send lifted notifications when embargo is released
 * - Log all notifications for audit purposes
 * - Get notification recipients from embargo, donor, or repository contacts
 */
class EmbargoNotificationService
{
    /**
     * Send expiry warning notification.
     *
     * @param object $embargo The embargo record
     * @param int    $daysRemaining Days until expiry
     *
     * @return bool Success status
     */
    public function sendExpiryNotification(object $embargo, int $daysRemaining): bool
    {
        $recipients = $this->getNotificationRecipients($embargo);

        if (empty($recipients)) {
            $this->logNotification($embargo->id, 'expiry_warning', [], $daysRemaining, false, 'No recipients configured');

            return false;
        }

        $objectInfo = $this->getObjectInfo($embargo->object_id);
        $embargoTypeLabel = $this->getEmbargoTypeLabel($embargo->embargo_type);

        $subject = "Embargo Expiry Warning: {$daysRemaining} days remaining";

        $body = $this->buildExpiryNotificationBody([
            'object_title' => $objectInfo['title'] ?? 'Unknown',
            'object_slug' => $objectInfo['slug'] ?? '',
            'embargo_type' => $embargoTypeLabel,
            'end_date' => $embargo->end_date,
            'days_remaining' => $daysRemaining,
            'reason' => $embargo->reason ?? 'Not specified',
        ]);

        $sent = $this->sendEmail($recipients, $subject, $body);

        $this->logNotification(
            $embargo->id,
            'expiry_warning',
            $recipients,
            $daysRemaining,
            $sent,
            $sent ? null : 'Email delivery failed'
        );

        // Update notification_sent flag to prevent duplicate notifications
        if ($sent) {
            DB::table('rights_embargo')
                ->where('id', $embargo->id)
                ->update(['notification_sent' => true]);
        }

        return $sent;
    }

    /**
     * Send notification when embargo is lifted.
     *
     * @param object      $embargo The embargo record
     * @param string|null $reason  Reason for lifting
     *
     * @return bool Success status
     */
    public function sendLiftedNotification(object $embargo, ?string $reason = null): bool
    {
        $recipients = $this->getNotificationRecipients($embargo);

        if (empty($recipients)) {
            $this->logNotification($embargo->id, 'lifted', [], null, false, 'No recipients configured');

            return false;
        }

        $objectInfo = $this->getObjectInfo($embargo->object_id);
        $embargoTypeLabel = $this->getEmbargoTypeLabel($embargo->embargo_type);

        $subject = 'Embargo Lifted: ' . ($objectInfo['title'] ?? 'Record');

        $body = $this->buildLiftedNotificationBody([
            'object_title' => $objectInfo['title'] ?? 'Unknown',
            'object_slug' => $objectInfo['slug'] ?? '',
            'embargo_type' => $embargoTypeLabel,
            'lift_reason' => $reason ?? 'Auto-released after expiry',
            'lifted_at' => date('Y-m-d H:i:s'),
        ]);

        $sent = $this->sendEmail($recipients, $subject, $body);

        $this->logNotification(
            $embargo->id,
            'lifted',
            $recipients,
            null,
            $sent,
            $sent ? null : 'Email delivery failed'
        );

        return $sent;
    }

    /**
     * Send access granted notification.
     *
     * @param object $embargo   The embargo record
     * @param object $exception The exception granted
     * @param object $user      The user granted access
     *
     * @return bool Success status
     */
    public function sendAccessGrantedNotification(object $embargo, object $exception, object $user): bool
    {
        $userEmail = $this->getUserEmail($user);

        if (!$userEmail) {
            return false;
        }

        $objectInfo = $this->getObjectInfo($embargo->object_id);

        $subject = 'Access Granted: ' . ($objectInfo['title'] ?? 'Record');

        $body = $this->buildAccessGrantedBody([
            'object_title' => $objectInfo['title'] ?? 'Unknown',
            'object_slug' => $objectInfo['slug'] ?? '',
            'valid_from' => $exception->valid_from ?? 'Immediately',
            'valid_until' => $exception->valid_until ?? 'Indefinitely',
        ]);

        $sent = $this->sendEmail([$userEmail], $subject, $body);

        $this->logNotification(
            $embargo->id,
            'access_granted',
            [$userEmail],
            null,
            $sent
        );

        return $sent;
    }

    /**
     * Get notification recipients for an embargo.
     *
     * @param object $embargo The embargo record
     *
     * @return array Email addresses
     */
    protected function getNotificationRecipients(object $embargo): array
    {
        $recipients = [];

        // 1. Check embargo's notify_emails field
        if (!empty($embargo->notify_emails)) {
            $embargoEmails = array_map('trim', explode(',', $embargo->notify_emails));
            $recipients = array_merge($recipients, array_filter($embargoEmails, 'filter_var', FILTER_VALIDATE_EMAIL));
        }

        // 2. Check donor contact if object has a donor
        $donorEmail = $this->getDonorContactEmail($embargo->object_id);
        if ($donorEmail) {
            $recipients[] = $donorEmail;
        }

        // 3. Check repository contact
        $repoEmail = $this->getRepositoryContactEmail($embargo->object_id);
        if ($repoEmail) {
            $recipients[] = $repoEmail;
        }

        // 4. Check created_by user email
        if ($embargo->created_by) {
            $creatorEmail = $this->getUserEmailById($embargo->created_by);
            if ($creatorEmail) {
                $recipients[] = $creatorEmail;
            }
        }

        return array_unique(array_filter($recipients));
    }

    /**
     * Get object information for notifications.
     *
     * @param int $objectId Information object ID
     *
     * @return array Object title and slug
     */
    protected function getObjectInfo(int $objectId): array
    {
        $result = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as ioi', function ($join) {
                $join->on('io.id', '=', 'ioi.id')
                    ->where('ioi.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $objectId)
            ->select(['ioi.title', 'slug.slug'])
            ->first();

        return [
            'title' => $result->title ?? null,
            'slug' => $result->slug ?? null,
        ];
    }

    /**
     * Get donor contact email for an object.
     *
     * @param int $objectId Information object ID
     *
     * @return string|null
     */
    protected function getDonorContactEmail(int $objectId): ?string
    {
        return DB::table('object_rights_holder as orh')
            ->join('donor as d', 'd.id', '=', 'orh.donor_id')
            ->where('orh.object_id', $objectId)
            ->value('d.email');
    }

    /**
     * Get repository contact email for an object.
     *
     * @param int $objectId Information object ID
     *
     * @return string|null
     */
    protected function getRepositoryContactEmail(int $objectId): ?string
    {
        return DB::table('information_object as io')
            ->join('repository as r', 'r.id', '=', 'io.repository_id')
            ->leftJoin('contact_information as ci', 'ci.actor_id', '=', 'r.id')
            ->where('io.id', $objectId)
            ->whereNotNull('ci.email')
            ->value('ci.email');
    }

    /**
     * Get user email by ID.
     *
     * @param int $userId User ID
     *
     * @return string|null
     */
    protected function getUserEmailById(int $userId): ?string
    {
        return DB::table('user')
            ->where('id', $userId)
            ->value('email');
    }

    /**
     * Get user email from user object.
     *
     * @param object $user User object
     *
     * @return string|null
     */
    protected function getUserEmail(object $user): ?string
    {
        if (isset($user->email)) {
            return $user->email;
        }

        if (isset($user->id)) {
            return $this->getUserEmailById($user->id);
        }

        return null;
    }

    /**
     * Get human-readable embargo type label.
     *
     * @param string $type Embargo type
     *
     * @return string
     */
    protected function getEmbargoTypeLabel(string $type): string
    {
        $labels = [
            'full' => 'Full Access Restriction',
            'metadata_only' => 'Metadata Only (Digital Content Restricted)',
            'digital_only' => 'Digital Content Restricted',
            'partial' => 'Partial Restriction',
        ];

        return $labels[$type] ?? 'Access Restriction';
    }

    /**
     * Build expiry notification email body.
     *
     * @param array $data Template data
     *
     * @return string
     */
    protected function buildExpiryNotificationBody(array $data): string
    {
        $baseUrl = $this->getBaseUrl();

        return <<<BODY
Embargo Expiry Warning

The following embargo will expire in {$data['days_remaining']} days:

Record: {$data['object_title']}
Embargo Type: {$data['embargo_type']}
End Date: {$data['end_date']}
Reason: {$data['reason']}

Action Required:
- Review the embargo and extend if necessary
- Or allow it to auto-release on the end date

View Record: {$baseUrl}/index.php/{$data['object_slug']}
Manage Embargoes: {$baseUrl}/extendedRights/embargoes

This is an automated notification from the Archive system.
BODY;
    }

    /**
     * Build lifted notification email body.
     *
     * @param array $data Template data
     *
     * @return string
     */
    protected function buildLiftedNotificationBody(array $data): string
    {
        $baseUrl = $this->getBaseUrl();

        return <<<BODY
Embargo Lifted

The following embargo has been lifted:

Record: {$data['object_title']}
Previous Embargo Type: {$data['embargo_type']}
Lifted At: {$data['lifted_at']}
Reason: {$data['lift_reason']}

The record is now accessible according to standard access rules.

View Record: {$baseUrl}/index.php/{$data['object_slug']}

This is an automated notification from the Archive system.
BODY;
    }

    /**
     * Build access granted notification email body.
     *
     * @param array $data Template data
     *
     * @return string
     */
    protected function buildAccessGrantedBody(array $data): string
    {
        $baseUrl = $this->getBaseUrl();

        return <<<BODY
Access Granted

You have been granted access to an embargoed record:

Record: {$data['object_title']}
Access Valid From: {$data['valid_from']}
Access Valid Until: {$data['valid_until']}

You can now view this record by visiting:
{$baseUrl}/index.php/{$data['object_slug']}

This is an automated notification from the Archive system.
BODY;
    }

    /**
     * Send email using EmailService.
     *
     * @param array  $recipients Email addresses
     * @param string $subject    Email subject
     * @param string $body       Email body
     *
     * @return bool Success status
     */
    protected function sendEmail(array $recipients, string $subject, string $body): bool
    {
        // Load AhgCore EmailService
        $emailServicePath = \sfConfig::get('sf_root_dir') . '/plugins/ahgCorePlugin/lib/Services/EmailService.php';
        if (file_exists($emailServicePath)) {
            require_once $emailServicePath;
        }

        if (!class_exists('AhgCore\Services\EmailService')) {
            error_log('EmbargoNotificationService: EmailService not available');

            return false;
        }

        $success = true;
        foreach ($recipients as $recipient) {
            if (!\AhgCore\Services\EmailService::send($recipient, $subject, $body)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Log notification for audit purposes.
     *
     * @param int         $embargoId        Embargo ID
     * @param string      $notificationType Type of notification
     * @param array       $recipients       Recipients list
     * @param int|null    $daysBefore       Days before expiry (for warnings)
     * @param bool        $sent             Whether email was sent
     * @param string|null $errorMessage     Error message if failed
     */
    protected function logNotification(
        int $embargoId,
        string $notificationType,
        array $recipients,
        ?int $daysBefore,
        bool $sent,
        ?string $errorMessage = null
    ): void {
        try {
            // Check if embargo_notification_log table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'embargo_notification_log'");
            if (empty($tableExists)) {
                // Log to embargo_audit instead
                DB::table('embargo_audit')->insert([
                    'embargo_id' => $embargoId,
                    'action' => 'notification_' . $notificationType,
                    'details' => json_encode([
                        'recipients' => $recipients,
                        'days_before' => $daysBefore,
                        'sent' => $sent,
                        'error' => $errorMessage,
                    ]),
                    'performed_at' => date('Y-m-d H:i:s'),
                ]);

                return;
            }

            DB::table('embargo_notification_log')->insert([
                'embargo_id' => $embargoId,
                'notification_type' => $notificationType,
                'recipients' => json_encode($recipients),
                'days_before' => $daysBefore,
                'sent_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            error_log('EmbargoNotificationService: Failed to log notification: ' . $e->getMessage());
        }
    }

    /**
     * Get base URL for links in emails.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        if (class_exists('sfConfig')) {
            return \sfConfig::get('app_siteBaseUrl', '');
        }

        return '';
    }
}
