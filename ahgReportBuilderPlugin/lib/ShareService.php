<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Share Service for Report Builder.
 *
 * Manages public sharing of reports via secure tokens with optional expiry.
 */
class ShareService
{
    /**
     * Create a new share link for a report.
     *
     * Generates a cryptographically random 64-character token.
     *
     * @param int         $reportId        The report ID
     * @param int         $userId          The user creating the share
     * @param string|null $expiresAt       Optional expiry datetime (Y-m-d H:i:s)
     * @param string|null $emailRecipients Optional comma-separated email list
     *
     * @return array The share record including token
     */
    public function createShare(int $reportId, int $userId, ?string $expiresAt = null, ?string $emailRecipients = null): array
    {
        $token = bin2hex(random_bytes(32));
        $now = date('Y-m-d H:i:s');

        $id = DB::table('report_share')->insertGetId([
            'report_id' => $reportId,
            'share_token' => $token,
            'shared_by' => $userId,
            'expires_at' => $expiresAt,
            'access_count' => 0,
            'is_active' => 1,
            'email_recipients' => $emailRecipients,
            'created_at' => $now,
        ]);

        return [
            'id' => $id,
            'report_id' => $reportId,
            'share_token' => $token,
            'shared_by' => $userId,
            'expires_at' => $expiresAt,
            'email_recipients' => $emailRecipients,
            'url' => $this->getShareUrl($token),
            'created_at' => $now,
        ];
    }

    /**
     * Look up a share by its token.
     *
     * Validates that the share is active and not expired.
     * Increments the access count on successful lookup.
     *
     * @param string $token The share token
     *
     * @return object|null The share record, or null if invalid/expired
     */
    public function getShare(string $token): ?object
    {
        $share = DB::table('report_share')
            ->where('share_token', $token)
            ->first();

        if (!$share) {
            return null;
        }

        // Check if active
        if (!(int) $share->is_active) {
            return null;
        }

        // Check if expired
        if ($this->isExpired($share)) {
            return null;
        }

        // Increment access count
        DB::table('report_share')
            ->where('id', $share->id)
            ->increment('access_count');

        return $share;
    }

    /**
     * Get all shares for a report.
     *
     * @param int $reportId The report ID
     *
     * @return array The shares
     */
    public function getSharesForReport(int $reportId): array
    {
        return DB::table('report_share')
            ->where('report_id', $reportId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Deactivate a share (soft disable).
     *
     * @param int $shareId The share ID
     *
     * @return bool True if deactivated
     */
    public function deactivateShare(int $shareId): bool
    {
        return DB::table('report_share')
            ->where('id', $shareId)
            ->update(['is_active' => 0]) > 0;
    }

    /**
     * Delete a share permanently.
     *
     * @param int $shareId The share ID
     *
     * @return bool True if deleted
     */
    public function deleteShare(int $shareId): bool
    {
        return DB::table('report_share')
            ->where('id', $shareId)
            ->delete() > 0;
    }

    /**
     * Get the public URL for a share token.
     *
     * @param string $token The share token
     *
     * @return string The share URL path
     */
    public function getShareUrl(string $token): string
    {
        return '/reports/shared/' . $token;
    }

    /**
     * Check if a share has expired.
     *
     * @param object $share The share record
     *
     * @return bool True if expired
     */
    public function isExpired(object $share): bool
    {
        if (empty($share->expires_at)) {
            return false;
        }

        $expiresAt = strtotime($share->expires_at);
        if ($expiresAt === false) {
            return false;
        }

        return time() > $expiresAt;
    }
}
