<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Spectrum Notification Service
 *
 * Handles task assignment notifications and user notification management
 * for the Spectrum workflow system.
 */
class ahgSpectrumNotificationService
{
    /**
     * Create an assignment notification for a user
     *
     * @param int $userId User ID to notify
     * @param int $objectId Information object ID
     * @param string $procedureType Procedure type (e.g., 'acquisition', 'cataloguing')
     * @param int $assignedBy User ID who made the assignment
     * @param string|null $state Current workflow state
     * @return int|null Notification ID or null on failure
     */
    public static function createAssignmentNotification($userId, $objectId, $procedureType, $assignedBy, $state = null)
    {
        if (!$userId || !$objectId || !$procedureType) {
            return null;
        }

        // Get object details
        $object = DB::table('information_object as io')
            ->select(['io.id', 'io.identifier', 'ioi18n.title', 'slug.slug'])
            ->leftJoin('information_object_i18n as ioi18n', function($join) {
                $join->on('io.id', '=', 'ioi18n.id')
                     ->where('ioi18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('io.id', $objectId)
            ->first();

        if (!$object) {
            return null;
        }

        // Get assigner name
        $assigner = DB::table('user')->where('id', $assignedBy)->first();
        $assignerName = $assigner ? $assigner->username : 'System';

        // Get procedure label
        $procedureLabel = self::getProcedureLabel($procedureType);

        // Get state label
        $stateLabel = $state ? self::getStateLabel($procedureType, $state) : '';

        $objectTitle = $object->title ?: $object->identifier ?: 'Untitled';
        $objectLink = '/' . $object->slug . '/spectrum';

        $subject = "Task Assigned: {$procedureLabel}";
        $message = "You have been assigned a task by {$assignerName}.\n\n" .
                   "Object: {$objectTitle}\n" .
                   "Procedure: {$procedureLabel}\n";

        if ($stateLabel) {
            $message .= "State: {$stateLabel}\n";
        }

        $message .= "\nView task: {$objectLink}";

        // Create notification
        $notificationId = DB::table('spectrum_notification')->insertGetId([
            'user_id' => $userId,
            'notification_type' => 'task_assignment',
            'subject' => $subject,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $notificationId;
    }

    /**
     * Get unread notification count for a user
     *
     * @param int $userId User ID
     * @return int Count of unread notifications
     */
    public static function getUnreadCount($userId)
    {
        if (!$userId) {
            return 0;
        }

        return DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get unread task assignment count for a user
     *
     * @param int $userId User ID
     * @return int Count of unread task assignment notifications
     */
    public static function getUnreadTaskCount($userId)
    {
        if (!$userId) {
            return 0;
        }

        return DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->where('notification_type', 'task_assignment')
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Mark a notification as read
     *
     * @param int $notificationId Notification ID
     * @param int|null $userId Optional user ID for security check
     * @return bool Success
     */
    public static function markAsRead($notificationId, $userId = null)
    {
        if (!$notificationId) {
            return false;
        }

        $query = DB::table('spectrum_notification')
            ->where('id', $notificationId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->update([
            'read_at' => date('Y-m-d H:i:s')
        ]) > 0;
    }

    /**
     * Mark all notifications as read for a user
     *
     * @param int $userId User ID
     * @param string|null $type Optional notification type filter
     * @return int Number of notifications marked as read
     */
    public static function markAllAsRead($userId, $type = null)
    {
        if (!$userId) {
            return 0;
        }

        $query = DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->whereNull('read_at');

        if ($type) {
            $query->where('notification_type', $type);
        }

        return $query->update([
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get notifications for a user
     *
     * @param int $userId User ID
     * @param int $limit Maximum number of notifications to return
     * @param bool $unreadOnly Only return unread notifications
     * @return array Notifications
     */
    public static function getUserNotifications($userId, $limit = 20, $unreadOnly = false)
    {
        if (!$userId) {
            return [];
        }

        $query = DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->get()->toArray();
    }

    /**
     * Get task assignment notifications for a user
     *
     * @param int $userId User ID
     * @param int $limit Maximum number to return
     * @return array Task assignment notifications
     */
    public static function getTaskNotifications($userId, $limit = 20)
    {
        if (!$userId) {
            return [];
        }

        return DB::table('spectrum_notification')
            ->where('user_id', $userId)
            ->where('notification_type', 'task_assignment')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Delete a notification
     *
     * @param int $notificationId Notification ID
     * @param int|null $userId Optional user ID for security check
     * @return bool Success
     */
    public static function deleteNotification($notificationId, $userId = null)
    {
        if (!$notificationId) {
            return false;
        }

        $query = DB::table('spectrum_notification')
            ->where('id', $notificationId);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->delete() > 0;
    }

    /**
     * Delete old notifications (cleanup)
     *
     * @param int $daysOld Delete notifications older than this many days
     * @return int Number of notifications deleted
     */
    public static function cleanupOldNotifications($daysOld = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return DB::table('spectrum_notification')
            ->where('created_at', '<', $cutoffDate)
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * Get procedure label from procedure type
     *
     * @param string $procedureType Procedure type
     * @return string Human-readable label
     */
    protected static function getProcedureLabel($procedureType)
    {
        $procedures = ahgSpectrumWorkflowService::getProcedures();
        return $procedures[$procedureType] ?? ucwords(str_replace('_', ' ', $procedureType));
    }

    /**
     * Get state label from workflow config
     *
     * @param string $procedureType Procedure type
     * @param string $state State key
     * @return string Human-readable state label
     */
    protected static function getStateLabel($procedureType, $state)
    {
        $config = DB::table('spectrum_workflow_config')
            ->where('procedure_type', $procedureType)
            ->where('is_active', 1)
            ->first();

        if ($config) {
            $configData = json_decode($config->config_json, true);
            if (isset($configData['state_labels'][$state])) {
                return $configData['state_labels'][$state];
            }
        }

        return ucwords(str_replace('_', ' ', $state));
    }

    /**
     * Mark task notifications as read when task reaches final state
     *
     * @param string $slug Object slug
     * @param string|null $procedureType Optional procedure type filter
     * @return int Number of notifications marked as read
     */
    public static function markTaskNotificationsAsReadBySlug($slug, $procedureType = null)
    {
        if (!$slug) {
            return 0;
        }

        // Build pattern to match in message: "View task: /slug/spectrum"
        $pattern = '/' . $slug . '/spectrum';

        $query = DB::table('spectrum_notification')
            ->where('notification_type', 'task_assignment')
            ->where('message', 'like', '%' . $pattern . '%')
            ->whereNull('read_at');

        // If procedure type provided, also match in subject
        if ($procedureType) {
            $procedureLabel = self::getProcedureLabel($procedureType);
            $query->where('subject', 'like', '%' . $procedureLabel . '%');
        }

        return $query->update([
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get assigned task count for a user
     *
     * @param int $userId User ID
     * @return int Number of tasks assigned to user
     */
    public static function getAssignedTaskCount($userId)
    {
        if (!$userId) {
            return 0;
        }

        return DB::table('spectrum_workflow_state')
            ->where('assigned_to', $userId)
            ->count();
    }

    /**
     * Get pending tasks for a user (tasks not in final states)
     *
     * @param int $userId User ID
     * @return array Pending tasks
     */
    public static function getPendingTasks($userId)
    {
        if (!$userId) {
            return [];
        }

        // Collect all final states from all procedures
        $allFinalStates = [];
        $configs = DB::table('spectrum_workflow_config')
            ->where('is_active', 1)
            ->get();
        foreach ($configs as $config) {
            $finalStates = ahgSpectrumWorkflowService::getFinalStates($config->procedure_type);
            $allFinalStates = array_merge($allFinalStates, $finalStates);
        }
        $allFinalStates = array_unique($allFinalStates);

        $query = DB::table('spectrum_workflow_state as sws')
            ->select([
                'sws.*',
                'io.identifier',
                'ioi18n.title as object_title',
                'slug.slug'
            ])
            ->leftJoin('information_object as io', 'sws.record_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi18n', function($join) {
                $join->on('io.id', '=', 'ioi18n.id')
                     ->where('ioi18n.culture', '=', 'en');
            })
            ->leftJoin('slug', 'io.id', '=', 'slug.object_id')
            ->where('sws.assigned_to', $userId);

        // Exclude all final states
        if (!empty($allFinalStates)) {
            $query->whereNotIn('sws.current_state', $allFinalStates);
        }

        return $query->orderBy('sws.assigned_at', 'desc')
            ->get()
            ->toArray();
    }
}
