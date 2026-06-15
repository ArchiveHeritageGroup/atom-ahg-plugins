<?php

namespace AtoM\Framework\Plugins\AuditTrail\Services;

/**
 * Ed25519 cryptographic seal for the ahg_audit_log hash chain (#5 / audit seal).
 *
 * The hash chain (ChainedAuditWriter) already makes the log tamper-EVIDENT: any
 * edit, deletion or insertion breaks the SHA-256 linkage. Signing each entry's
 * entry_hash with an operator-held Ed25519 key makes it tamper-PROOF — an
 * attacker who rewrites the chain to keep it internally consistent still cannot
 * forge the per-entry signatures without the private key.
 *
 * Self-contained (plugins are autonomous — this duplicates, by design, the
 * Ed25519 scheme used elsewhere rather than depending on another plugin). The
 * private key lives outside every plugin git repo (the AtoM install's data/
 * tree) and never enters the database; only the detached signature + a short
 * key id are persisted on the row.
 *
 * Opt-in: until `audit:chain --keygen` mints a keypair, sign() returns null and
 * rows are written chained-but-unsigned — the audit writer never fails because
 * signing is unconfigured.
 */
class AuditSigner
{
    private string $keyDir;

    public function __construct(?string $keyDir = null)
    {
        $this->keyDir = $keyDir ?? self::defaultKeyDir();
    }

    /** Key directory under the AtoM install's data/ tree (outside any plugin repo). */
    public static function defaultKeyDir(): string
    {
        if (class_exists('sfConfig')) {
            $root = \sfConfig::get('sf_root_dir');
            if (is_string($root) && '' !== $root) {
                return $root . '/data/ahg-audit-signing';
            }
        }

        return sys_get_temp_dir() . '/ahg-audit-signing';
    }

    private function privatePath(): string { return $this->keyDir . '/ed25519.private'; }
    private function publicPath(): string  { return $this->keyDir . '/ed25519.public'; }
    private function keyIdPath(): string   { return $this->keyDir . '/key_id'; }

    public function keyDir(): string
    {
        return $this->keyDir;
    }

    /** Generate the Ed25519 keypair. Refuses to overwrite unless $force. Returns the key id. */
    public function generateKeypair(bool $force = false): string
    {
        if (!is_dir($this->keyDir) && !@mkdir($this->keyDir, 0700, true) && !is_dir($this->keyDir)) {
            throw new \RuntimeException('Could not create key directory: ' . $this->keyDir);
        }
        if (!is_file($this->keyDir . '/.gitignore')) {
            @file_put_contents($this->keyDir . '/.gitignore', "*\n");
        }
        if (!$force && is_file($this->privatePath())) {
            throw new \RuntimeException('A signing keypair already exists at ' . $this->keyDir . ' — pass --force to replace it.');
        }

        $keypair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($keypair);
        $public = sodium_crypto_sign_publickey($keypair);
        $keyId = $this->deriveKeyId($public);

        file_put_contents($this->privatePath(), base64_encode($secret));
        @chmod($this->privatePath(), 0600);
        file_put_contents($this->publicPath(), base64_encode($public));
        @chmod($this->publicPath(), 0640);
        file_put_contents($this->keyIdPath(), $keyId);

        sodium_memzero($secret);

        return $keyId;
    }

    /** True once a usable private key is present. */
    public function isEnabled(): bool
    {
        return is_file($this->privatePath());
    }

    /** The key id (e.g. "ed25519:1a2b3c4d5e6f7081"), or null. Fits ahg_audit_log.kid (32). */
    public function keyId(): ?string
    {
        return is_file($this->keyIdPath()) ? trim((string) file_get_contents($this->keyIdPath())) : null;
    }

    /** Raw (decoded) public key bytes, or null. */
    public function publicKey(): ?string
    {
        if (!is_file($this->publicPath())) {
            return null;
        }
        $k = base64_decode(trim((string) file_get_contents($this->publicPath())), true);

        return false === $k ? null : $k;
    }

    /**
     * Sign an entry hash. Returns a base64 detached Ed25519 signature, or null
     * when signing is not configured. The entry_hash is already a canonical
     * SHA-256 hex string covering the row's content + chain linkage, so it is
     * signed directly with no further canonicalisation.
     */
    public function sign(string $entryHash): ?string
    {
        if ('' === $entryHash || !$this->isEnabled()) {
            return null;
        }
        $secret = base64_decode(trim((string) file_get_contents($this->privatePath())), true);
        if (false === $secret) {
            return null;
        }
        $signature = sodium_crypto_sign_detached($entryHash, $secret);
        sodium_memzero($secret);

        return base64_encode($signature);
    }

    /** Verify a base64 detached signature over an entry hash against a raw public key. */
    public function verify(string $signatureB64, string $entryHash, string $publicKey): bool
    {
        $signature = base64_decode($signatureB64, true);
        if (false === $signature || SODIUM_CRYPTO_SIGN_BYTES !== strlen($signature)) {
            return false;
        }

        return sodium_crypto_sign_verify_detached($signature, $entryHash, $publicKey);
    }

    private function deriveKeyId(string $publicKey): string
    {
        return 'ed25519:' . substr(hash('sha256', $publicKey), 0, 16);
    }
}
