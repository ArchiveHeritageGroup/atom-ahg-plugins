<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

use RuntimeException;

/**
 * Ed25519 key pair for C2PA claim signing (ext-sodium). The kid is derived
 * from the public key so verifiers can resolve it deterministically.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
final class C2paKeyPair
{
    private string $secret; // 64-byte sodium sign secret key
    private string $public; // 32-byte public key

    public function __construct(string $secretKey)
    {
        if (strlen($secretKey) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('C2paKeyPair: secret key must be ' . SODIUM_CRYPTO_SIGN_SECRETKEYBYTES . ' bytes');
        }
        $this->secret = $secretKey;
        $this->public = sodium_crypto_sign_publickey_from_secretkey($secretKey);
    }

    /** Generate a fresh key pair. */
    public static function generate(): self
    {
        $kp = sodium_crypto_sign_keypair();

        return new self(sodium_crypto_sign_secretkey($kp));
    }

    /** Load from a base64-encoded secret key. */
    public static function fromBase64(string $secretKeyB64): self
    {
        $raw = base64_decode($secretKeyB64, true);
        if ($raw === false) {
            throw new RuntimeException('C2paKeyPair: invalid base64 secret key');
        }

        return new self($raw);
    }

    public function secretKey(): string
    {
        return $this->secret;
    }

    public function publicKey(): string
    {
        return $this->public;
    }

    public function secretKeyBase64(): string
    {
        return base64_encode($this->secret);
    }

    public function publicKeyBase64(): string
    {
        return base64_encode($this->public);
    }

    /** Deterministic key id: ed25519:<first 16 hex of sha256(pubkey)>. */
    public function kid(): string
    {
        return 'ed25519:' . substr(hash('sha256', $this->public), 0, 16);
    }
}
