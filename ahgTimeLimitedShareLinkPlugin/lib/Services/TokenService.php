<?php

namespace AhgShareLink\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * TokenService — generates and parses time-limited share-link tokens.
 *
 * Token structure (per locked decision #4):
 *   1. A random 16-byte nonce ensures two same-input calls produce different tokens.
 *   2. HMAC-SHA256 input = {io_id "|" expiry_unix "|" recipient_email "|" nonce_hex}.
 *   3. HMAC key = the per-install secret in ahg_settings.share_link.hmac_secret
 *      (auto-bootstrapped to 32 random bytes hex on first call).
 *   4. HMAC output (32 bytes) is base64url-encoded without padding → 43 chars.
 *
 * The HMAC's job is unguessability, not at-rest secrecy — tokens are stored
 * plain in the DB for direct lookup. Equality comparison is enough; the
 * 256-bit nonce gives the unguessable bit.
 *
 * @phase B
 */
class TokenService
{
    private const SECRET_SETTING_KEY = 'share_link.hmac_secret';
    private const HMAC_ALGO = 'sha256';

    /**
     * Generate a fresh URL-safe token for the given record/expiry/recipient.
     */
    public function generate(int $informationObjectId, \DateTimeInterface $expiresAt, ?string $recipientEmail = null): string
    {
        $nonce = bin2hex(random_bytes(16));
        $input = sprintf(
            '%d|%d|%s|%s',
            $informationObjectId,
            $expiresAt->getTimestamp(),
            (string) $recipientEmail,
            $nonce,
        );
        $digest = hash_hmac(self::HMAC_ALGO, $input, $this->getSecret(), true); // raw 32 bytes
        return $this->base64urlEncode($digest);
    }

    /**
     * Extract a token from a recipient URL. Accepts either:
     *   https://host/share/{token}
     *   https://host/share/{token}?…
     *   /share/{token}
     *   {token} alone
     *
     * Returns null when the input doesn't look like a token.
     */
    public function extractFromUrl(string $url): ?string
    {
        if (preg_match('#/share/([A-Za-z0-9_\-]{32,64})\b#', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#^[A-Za-z0-9_\-]{32,64}$#', trim($url))) {
            return trim($url);
        }
        return null;
    }

    /**
     * Look up a stored share-link row by token. Returns null on miss.
     */
    public function lookup(string $token): ?object
    {
        $row = DB::table('information_object_share_token')->where('token', $token)->first();
        return $row ?: null;
    }

    /**
     * Resolve (or auto-bootstrap) the per-install HMAC secret.
     * Secret rotation is a future enhancement — out of scope for v1.
     */
    private function getSecret(): string
    {
        $row = DB::table('ahg_settings')->where('setting_key', self::SECRET_SETTING_KEY)->first();
        if ($row && is_string($row->setting_value) && $row->setting_value !== '') {
            return $row->setting_value;
        }
        // Bootstrap.
        $secret = bin2hex(random_bytes(32)); // 64 hex chars
        DB::table('ahg_settings')->updateOrInsert(
            ['setting_key' => self::SECRET_SETTING_KEY],
            [
                'setting_value' => $secret,
                'setting_type'  => 'string',
                'setting_group' => 'share_link',
                'description'   => 'Auto-generated HMAC secret used by the TokenService. Rotate via secret-rotation runbook (future enhancement).',
                'is_sensitive'  => 1,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        );
        return $secret;
    }

    /**
     * RFC 4648 §5 — base64url, no padding. 32 bytes → 43 chars.
     */
    private function base64urlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
