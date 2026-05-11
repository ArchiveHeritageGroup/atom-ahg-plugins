<?php

namespace AhgShareLink\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * AccessService — validate an incoming share-link token and log the access.
 *
 * The middleware/controller calls `evaluate($token, $ip, $userAgent)` and gets
 * back an AccessResult. Every attempt — successful or denied — produces one
 * row in information_object_share_access and (for non-bogus tokens) one row
 * in ahg_audit_log.
 *
 * Guards:
 *   1. Token exists → if not, return notFound() with no audit (no FK target).
 *   2. revoked_at is null
 *   3. expires_at > now
 *   4. access_count < max_access (if max_access set)
 *
 * On allow: access_count is incremented atomically inside the same transaction
 * that records the access row, so two concurrent accessors get distinct counts
 * and the quota gate fires correctly under contention.
 *
 * @phase D
 */
class AccessService
{
    public const AUDIT_ACTION_ACCESSED = 'share_link_accessed';

    public function evaluate(string $token, ?string $ip, ?string $userAgent): AccessResult
    {
        $tokenService = new TokenService();
        $row = $tokenService->lookup($token);
        if ($row === null) {
            // No FK target — we can't even log this. Caller returns 410.
            return AccessResult::notFound();
        }

        $now = time();

        // Revoked?
        if ($row->revoked_at !== null) {
            $this->logAccess($row->id, $ip, $userAgent, 'denied_revoked');
            $this->writeAudit($row, 'denied_revoked', $ip);
            return AccessResult::deny($row, 'denied_revoked', 'This share link has been revoked.');
        }

        // Expired?
        if (strtotime((string) $row->expires_at) <= $now) {
            $this->logAccess($row->id, $ip, $userAgent, 'denied_expired');
            $this->writeAudit($row, 'denied_expired', $ip);
            return AccessResult::deny($row, 'denied_expired', 'This share link has expired.');
        }

        // Quota?
        if ($row->max_access !== null && (int) $row->access_count >= (int) $row->max_access) {
            $this->logAccess($row->id, $ip, $userAgent, 'denied_quota');
            $this->writeAudit($row, 'denied_quota', $ip);
            return AccessResult::deny($row, 'denied_quota', 'This share link has reached its maximum access count.');
        }

        // Allow: log + increment under a single transaction.
        $connection = DB::connection();
        $connection->transaction(function () use ($row, $ip, $userAgent) {
            $this->logAccess($row->id, $ip, $userAgent, 'view');
            DB::table('information_object_share_token')
                ->where('id', $row->id)
                ->increment('access_count');
        });
        $this->writeAudit($row, 'view', $ip);

        // Refresh access_count on the row we return so the caller sees the new value.
        $row->access_count = ((int) $row->access_count) + 1;

        return AccessResult::allow($row, 'view');
    }

    private function logAccess(int $tokenId, ?string $ip, ?string $userAgent, string $action): void
    {
        try {
            DB::table('information_object_share_access')->insert([
                'token_id'   => $tokenId,
                'accessed_at' => date('Y-m-d H:i:s'),
                'ip_address' => $ip,
                'user_agent' => $userAgent !== null ? substr($userAgent, 0, 500) : null,
                'action'     => $action,
            ]);
        } catch (\Throwable $e) {
            error_log('ahgTimeLimitedShareLinkPlugin AccessService logAccess failed: ' . $e->getMessage());
        }
    }

    private function writeAudit(object $tokenRow, string $accessAction, ?string $ip): void
    {
        try {
            $metadata = [
                'token_id'           => (int) $tokenRow->id,
                'access_action'      => $accessAction,
                'parent_entity_type' => 'information_object',
                'parent_entity_id'   => (int) $tokenRow->information_object_id,
                'recipient_email'    => $tokenRow->recipient_email,
            ];
            DB::table('ahg_audit_log')->insert([
                'uuid'           => $this->generateUuid(),
                'user_id'        => null,                      // anonymous accessor
                'username'       => null,
                'action'         => self::AUDIT_ACTION_ACCESSED,
                'entity_type'    => 'information_object_share_token',
                'entity_id'      => (int) $tokenRow->information_object_id,
                'module'         => 'share_link',
                'action_name'    => $accessAction,
                'request_method' => 'GET',
                'ip_address'     => $ip,
                'metadata'       => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status'         => str_starts_with($accessAction, 'denied_') ? 'failure' : 'success',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('ahgTimeLimitedShareLinkPlugin AccessService writeAudit failed: ' . $e->getMessage());
        }
    }

    private function generateUuid(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
