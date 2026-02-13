<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * NotificationService - In-App Notification System
 *
 * Creates and manages in-app notifications alongside email alerts.
 * Supports notification preferences per researcher.
 *
 * @package ahgResearchPlugin
 * @version 2.1.0
 */
class NotificationService
{
    /**
     * Create a notification.
     */
    public function createNotification(int $researcherId, string $type, string $title, ?string $message = null, ?string $link = null, ?string $relatedType = null, ?int $relatedId = null): int
    {
        if (!$this->shouldNotify($researcherId, $type, 'in_app')) {
            return 0;
        }

        return DB::table('research_notification')->insertGetId([
            'researcher_id' => $researcherId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'related_entity_type' => $relatedType,
            'related_entity_id' => $relatedId,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get notifications for a researcher.
     */
    public function getNotifications(int $researcherId, array $filters = []): array
    {
        $query = DB::table('research_notification')
            ->where('researcher_id', $researcherId);

        if (isset($filters['is_read'])) {
            $query->where('is_read', $filters['is_read']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $query->orderBy('created_at', 'desc');

        if (!empty($filters['limit'])) {
            $query->limit($filters['limit']);
        } else {
            $query->limit(50);
        }

        return $query->get()->toArray();
    }

    /**
     * Get unread count for badge display.
     */
    public function getUnreadCount(int $researcherId): int
    {
        return DB::table('research_notification')
            ->where('researcher_id', $researcherId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $id, int $researcherId): bool
    {
        return DB::table('research_notification')
            ->where('id', $id)
            ->where('researcher_id', $researcherId)
            ->update([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(int $researcherId): int
    {
        return DB::table('research_notification')
            ->where('researcher_id', $researcherId)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Delete old notifications (cleanup).
     */
    public function deleteOldNotifications(int $daysOld = 90): int
    {
        return DB::table('research_notification')
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime("-{$daysOld} days")))
            ->where('is_read', 1)
            ->delete();
    }

    /**
     * Get notification preferences for a researcher.
     */
    public function getPreferences(int $researcherId): array
    {
        $prefs = DB::table('research_notification_preference')
            ->where('researcher_id', $researcherId)
            ->get()
            ->keyBy('notification_type')
            ->toArray();

        // Fill in defaults for missing types
        $types = ['alert', 'invitation', 'comment', 'reply', 'system', 'reminder', 'collaboration'];
        foreach ($types as $type) {
            if (!isset($prefs[$type])) {
                $prefs[$type] = (object) [
                    'notification_type' => $type,
                    'email_enabled' => 1,
                    'in_app_enabled' => 1,
                    'digest_frequency' => 'immediate',
                ];
            }
        }

        return $prefs;
    }

    /**
     * Update a notification preference.
     */
    public function updatePreference(int $researcherId, string $type, array $data): void
    {
        DB::table('research_notification_preference')->updateOrInsert(
            ['researcher_id' => $researcherId, 'notification_type' => $type],
            [
                'email_enabled' => $data['email_enabled'] ?? 1,
                'in_app_enabled' => $data['in_app_enabled'] ?? 1,
                'digest_frequency' => $data['digest_frequency'] ?? 'immediate',
            ]
        );
    }

    /**
     * Check if a researcher should receive a notification of a given type via a given channel.
     */
    public function shouldNotify(int $researcherId, string $type, string $channel = 'in_app'): bool
    {
        $pref = DB::table('research_notification_preference')
            ->where('researcher_id', $researcherId)
            ->where('notification_type', $type)
            ->first();

        if (!$pref) {
            return true; // Default: notify
        }

        return $channel === 'email' ? (bool) $pref->email_enabled : (bool) $pref->in_app_enabled;
    }
}
