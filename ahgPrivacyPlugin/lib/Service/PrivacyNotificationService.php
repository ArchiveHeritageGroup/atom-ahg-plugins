<?php

namespace ahgPrivacyPlugin\Service;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Collection;

/**
 * Privacy Notification and Approval Workflow Service
 * Extracted from PrivacyService for better separation of concerns.
 */
class PrivacyNotificationService
{
    /**
     * Create a notification
     */
    public function createNotification(
        int $userId,
        string $entityType,
        int $entityId,
        string $type,
        string $subject,
        ?string $message = null,
        ?string $link = null,
        ?int $createdBy = null
    ): int {
        return DB::table('privacy_notification')->insertGetId([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'created_by' => $createdBy,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): Collection
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get notification count for a user
     */
    public function getNotificationCount(int $userId): int
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(int $id, int $userId): bool
    {
        return DB::table('privacy_notification')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]) > 0;
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllNotificationsRead(int $userId): int
    {
        return DB::table('privacy_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Submit ROPA for approval
     */
    public function submitRopaForApproval(int $id, int $userId, ?int $assignedOfficerId = null): bool
    {
        $activity = DB::table('privacy_ropa')->where('id', $id)->first();
        if (!$activity) {
            return false;
        }

        $oldStatus = $activity->approval_status;

        // Update status
        DB::table('privacy_ropa')
            ->where('id', $id)
            ->update([
                'approval_status' => 'pending',
                'submitted_by' => $userId,
                'submitted_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Log the action
        $this->logApprovalAction($id, 'ropa', 'submitted', $oldStatus, 'pending', null, $userId);

        // Notify assigned officer or all privacy officers
        if ($assignedOfficerId) {
            $this->createNotification(
                $assignedOfficerId,
                'ropa',
                $id,
                'approval_required',
                'ROPA Approval Required',
                "Processing activity '{$activity->activity_name}' requires your approval.",
                "/privacy/ropa/{$id}",
                $userId
            );
        } else {
            $officers = $this->getPrivacyOfficers();
            foreach ($officers as $officer) {
                $this->createNotification(
                    $officer->id,
                    'ropa',
                    $id,
                    'approval_required',
                    'ROPA Approval Required',
                    "Processing activity '{$activity->activity_name}' requires approval.",
                    "/privacy/ropa/{$id}",
                    $userId
                );
            }
        }

        return true;
    }

    /**
     * Approve ROPA
     */
    public function approveRopa(int $id, int $userId, ?string $comment = null): bool
    {
        $activity = DB::table('privacy_ropa')->where('id', $id)->first();
        if (!$activity || $activity->approval_status !== 'pending') {
            return false;
        }

        $oldStatus = $activity->approval_status;

        DB::table('privacy_ropa')
            ->where('id', $id)
            ->update([
                'approval_status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_comment' => $comment,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logApprovalAction($id, 'ropa', 'approved', $oldStatus, 'approved', $comment, $userId);

        // Notify submitter
        if ($activity->submitted_by) {
            $this->createNotification(
                $activity->submitted_by,
                'ropa',
                $id,
                'approval_result',
                'ROPA Approved',
                "Your processing activity '{$activity->activity_name}' has been approved.",
                "/privacy/ropa/{$id}",
                $userId
            );
        }

        return true;
    }

    /**
     * Reject ROPA
     */
    public function rejectRopa(int $id, int $userId, string $reason): bool
    {
        $activity = DB::table('privacy_ropa')->where('id', $id)->first();
        if (!$activity || $activity->approval_status !== 'pending') {
            return false;
        }

        $oldStatus = $activity->approval_status;

        DB::table('privacy_ropa')
            ->where('id', $id)
            ->update([
                'approval_status' => 'rejected',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
                'approval_comment' => $reason,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $this->logApprovalAction($id, 'ropa', 'rejected', $oldStatus, 'rejected', $reason, $userId);

        // Notify submitter
        if ($activity->submitted_by) {
            $this->createNotification(
                $activity->submitted_by,
                'ropa',
                $id,
                'approval_result',
                'ROPA Rejected',
                "Your processing activity '{$activity->activity_name}' was rejected. Reason: {$reason}",
                "/privacy/ropa/{$id}",
                $userId
            );
        }

        return true;
    }

    /**
     * Log approval action
     */
    protected function logApprovalAction(
        int $entityId,
        string $entityType,
        string $action,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $comment,
        int $userId
    ): int {
        return DB::table('privacy_approval_log')->insertGetId([
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get approval history
     */
    public function getApprovalHistory(int $entityId, string $entityType = 'ropa'): Collection
    {
        return DB::table('privacy_approval_log as l')
            ->leftJoin('user as u', 'l.user_id', '=', 'u.id')
            ->where('l.entity_id', $entityId)
            ->where('l.entity_type', $entityType)
            ->select('l.*', 'u.username')
            ->orderBy('l.created_at', 'desc')
            ->get();
    }

    /**
     * Get privacy officers
     */
    public function getPrivacyOfficers(): Collection
    {
        return DB::table('privacy_officer as po')
            ->join('user as u', 'po.user_id', '=', 'u.id')
            ->where('po.is_active', 1)
            ->select('u.id', 'u.username', 'u.email', 'po.jurisdiction')
            ->get();
    }

    /**
     * Check if user is a privacy officer
     */
    public function isPrivacyOfficer(int $userId): bool
    {
        return DB::table('privacy_officer')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->exists();
    }
}
