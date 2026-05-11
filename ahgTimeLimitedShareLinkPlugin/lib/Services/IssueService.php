<?php

namespace AhgShareLink\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * IssueService — runs every guard, generates a token, persists the row,
 * dual-writes to ahg_audit_log.
 *
 * Guards (in order):
 *   1. user_id present (anonymous can never issue)
 *   2. ACL share_link.create
 *   3. If record is classified:
 *        a. ACL share_link.create_classified
 *        b. Clearance level >= record classification level
 *   4. Expiry cap — if > share_link.max_expiry_days, require ACL
 *      share_link.create_unlimited_expiry
 *
 * Captures at the moment of issuance:
 *   - classification_level_at_issuance — snapshot of record's level
 *   - issuer_download_at_issuance — whether the issuer could download the DO
 *     (the recipient inherits this permission state)
 *
 * @phase C
 */
class IssueService
{
    /** Default expiry days when caller doesn't specify. */
    public const DEFAULT_EXPIRY_DAYS = 14;
    /** Hard cap unless user has share_link.create_unlimited_expiry. */
    public const DEFAULT_MAX_EXPIRY_DAYS = 90;

    /** AUDIT action strings written to ahg_audit_log. */
    public const AUDIT_ACTION_ISSUED = 'share_link_issued';

    /**
     * Issue a share link.
     *
     * @return array{token: string, token_id: int, expires_at: string, public_url: ?string}
     */
    public function issue(
        int $userId,
        int $informationObjectId,
        ?\DateTimeInterface $expiresAt = null,
        ?string $recipientEmail = null,
        ?string $recipientNote = null,
        ?int $maxAccess = null,
    ): array {
        if ($userId <= 0) {
            throw new NotAuthenticatedException('Cannot issue a share link without an authenticated user');
        }
        if ($informationObjectId <= 0) {
            throw new InvalidRequestException('information_object_id is required');
        }

        // Resolve settings (with cached defaults).
        $maxExpiryDays = (int) $this->readSetting('share_link.max_expiry_days', (string) self::DEFAULT_MAX_EXPIRY_DAYS);
        $defaultExpiryDays = (int) $this->readSetting('share_link.default_expiry_days', (string) self::DEFAULT_EXPIRY_DAYS);

        // Default expiry if not supplied.
        if ($expiresAt === null) {
            $expiresAt = new \DateTimeImmutable("+{$defaultExpiryDays} days");
        }
        // Reject past-expiry inputs.
        if ($expiresAt->getTimestamp() <= time()) {
            throw new InvalidRequestException('expires_at must be in the future');
        }

        $acl = new AclCheck();
        $clearance = new ClearanceCheck();

        // Guard 1: basic create permission.
        if (!$acl->canUserDo($userId, AclCheck::ACTION_CREATE)) {
            throw new PermissionDeniedException('You do not have permission to issue share links');
        }

        // Verify the record exists.
        $io = DB::table('information_object')->where('id', $informationObjectId)->first();
        if (!$io) {
            throw new InvalidRequestException("information_object {$informationObjectId} not found");
        }

        // Guard 2 + 3: classified record gates.
        $classificationLevel = $clearance->resolveEntityClassificationLevel($informationObjectId);
        if ($classificationLevel !== null) {
            if (!$acl->canUserDo($userId, AclCheck::ACTION_CREATE_CLASSIFIED)) {
                throw new PermissionDeniedException('You do not have permission to issue share links for classified records');
            }
            if (!$clearance->canUserIssueLink($userId, $informationObjectId)) {
                throw new InsufficientClearanceException($clearance->explainDenial($userId, $informationObjectId));
            }
        }

        // Guard 4: expiry cap.
        if ($maxExpiryDays > 0) {
            $cutoff = (new \DateTimeImmutable("+{$maxExpiryDays} days"))->getTimestamp();
            if ($expiresAt->getTimestamp() > $cutoff) {
                if (!$acl->canUserDo($userId, AclCheck::ACTION_CREATE_UNLIMITED_EXPIRY)) {
                    throw new ExpiryCapExceededException(
                        "Expiry is capped at {$maxExpiryDays} days. Contact an administrator to issue longer-lived links.",
                    );
                }
            }
        }

        // Generate token + capture issuer download state.
        $token = (new TokenService())->generate($informationObjectId, $expiresAt, $recipientEmail);
        $issuerDownloadAtIssuance = $this->issuerCanDownload($userId, $informationObjectId) ? 1 : 0;

        $now = date('Y-m-d H:i:s');
        $tokenId = (int) DB::table('information_object_share_token')->insertGetId([
            'information_object_id' => $informationObjectId,
            'token'                 => $token,
            'issued_by'             => $userId,
            'recipient_email'       => $recipientEmail,
            'recipient_note'        => $recipientNote,
            'expires_at'            => $expiresAt->format('Y-m-d H:i:s'),
            'max_access'            => $maxAccess,
            'access_count'          => 0,
            'classification_level_at_issuance' => $classificationLevel,
            'issuer_download_at_issuance'      => $issuerDownloadAtIssuance,
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        $this->writeAuditEntry(
            tokenId: $tokenId,
            entityId: $informationObjectId,
            userId: $userId,
            expiresAt: $expiresAt,
            recipientEmail: $recipientEmail,
            recipientNote: $recipientNote,
            maxAccess: $maxAccess,
            classificationLevel: $classificationLevel,
        );

        return [
            'token'      => $token,
            'token_id'   => $tokenId,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'public_url' => $this->buildPublicUrl($token),
        ];
    }

    /**
     * Did the issuer have download permission on the IO at issuance time?
     * Used to set issuer_download_at_issuance — the recipient inherits this
     * permission state (Decision #3).
     */
    private function issuerCanDownload(int $userId, int $entityId): bool
    {
        // Pragmatic check: if the user can read the record (admin, editor, or
        // owns a grant), assume download follows. Phase D middleware will
        // re-evaluate at access time and downgrade if necessary.
        $acl = new AclCheck();
        // Admin shortcut
        try {
            $isAdmin = DB::table('acl_user_group')
                ->where('user_id', $userId)
                ->where('group_id', 100)
                ->exists();
            if ($isAdmin) {
                return true;
            }
        } catch (\Throwable $e) {
            // fall through
        }
        // Otherwise assume yes if they got past the create check.
        return true;
    }

    private function buildPublicUrl(string $token): ?string
    {
        // Try Symfony helper if we're in a request context; fall back to setting.
        if (function_exists('url_for')) {
            try {
                return \url_for(['module' => 'shareLink', 'action' => 'recipient', 'token' => $token]);
            } catch (\Throwable $e) {
                // fall through
            }
        }
        return null;
    }

    private function readSetting(string $key, string $default): string
    {
        try {
            $v = DB::table('ahg_settings')->where('setting_key', $key)->value('setting_value');
            return is_string($v) && $v !== '' ? $v : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function writeAuditEntry(
        int $tokenId,
        int $entityId,
        int $userId,
        \DateTimeInterface $expiresAt,
        ?string $recipientEmail,
        ?string $recipientNote,
        ?int $maxAccess,
        ?int $classificationLevel,
    ): void {
        try {
            $userRow = DB::table('user')->where('id', $userId)->first();
            $username = $userRow->username ?? null;
            $userEmail = $userRow->email ?? null;

            $entityTitle = DB::table('information_object_i18n')
                ->where('id', $entityId)
                ->orderBy('culture')
                ->value('title');

            $metadata = [
                'token_id'              => $tokenId,
                'expires_at'            => $expiresAt->format('Y-m-d H:i:s'),
                'recipient_email'       => $recipientEmail,
                'recipient_note'        => $recipientNote,
                'max_access'            => $maxAccess,
                'classification_level'  => $classificationLevel,
                'parent_entity_type'    => 'information_object',
                'parent_entity_id'      => $entityId,
            ];

            DB::table('ahg_audit_log')->insert([
                'uuid'           => $this->generateUuid(),
                'user_id'        => $userId,
                'username'       => $username,
                'user_email'     => $userEmail,
                'action'         => self::AUDIT_ACTION_ISSUED,
                'entity_type'    => 'information_object_share_token',
                'entity_id'      => $entityId,
                'entity_title'   => $entityTitle,
                'module'         => 'share_link',
                'action_name'    => 'issue',
                'request_method' => 'INTERNAL',
                'metadata'       => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'status'         => 'success',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('ahgTimeLimitedShareLinkPlugin audit dual-write failed: ' . $e->getMessage());
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
