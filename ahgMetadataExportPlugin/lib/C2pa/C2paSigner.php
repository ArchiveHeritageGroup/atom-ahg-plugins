<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

/**
 * Ed25519 signer for C2PA claims (ext-sodium). Signature is a detached
 * Ed25519 over SHA-256(JCS(claim)) — JCS so cross-language verifiers agree
 * on bytes, SHA-256 first so the signed input is fixed-length. Ported from
 * Heratio ahg-c2pa (inlined sodium signer instead of the receipts package).
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
final class C2paSigner
{
    public const ALG_LABEL = 'Ed25519';

    public function __construct(private C2paKeyPair $keyPair)
    {
    }

    public function kid(): string
    {
        return $this->keyPair->kid();
    }

    /**
     * Sign a claim. Returns the signed-manifest fragment (claim + signature).
     *
     * @return array<string,mixed>
     */
    public function sign(Claim $claim): array
    {
        $digest   = hash('sha256', $claim->canonicalBytes(), true);
        $sigBytes = sodium_crypto_sign_detached($digest, $this->keyPair->secretKey());

        return [
            'claim'           => $claim->toArray(),
            'claim_signature' => [
                'alg' => self::ALG_LABEL,
                'kid' => $this->keyPair->kid(),
                'sig' => bin2hex($sigBytes),
                'pad' => '',
            ],
        ];
    }

    /**
     * Verify a signed manifest. $publicKeyResolver(kid) returns the raw
     * 32-byte public key (or null).
     *
     * @param array<string,mixed> $signedManifest
     */
    public static function verify(array $signedManifest, callable $publicKeyResolver): bool
    {
        if (!isset($signedManifest['claim'], $signedManifest['claim_signature'])
            || !is_array($signedManifest['claim']) || !is_array($signedManifest['claim_signature'])) {
            return false;
        }
        $sig = $signedManifest['claim_signature'];
        if (($sig['alg'] ?? null) !== self::ALG_LABEL) {
            return false;
        }
        $kid    = $sig['kid'] ?? '';
        $sigHex = $sig['sig'] ?? '';
        if (!is_string($kid) || !is_string($sigHex) || !ctype_xdigit($sigHex)
            || strlen($sigHex) !== SODIUM_CRYPTO_SIGN_BYTES * 2) {
            return false;
        }

        $publicKey = $publicKeyResolver($kid);
        if (!is_string($publicKey) || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        $digest = hash('sha256', JcsEncoder::encode($signedManifest['claim']), true);

        try {
            return sodium_crypto_sign_verify_detached(hex2bin($sigHex), $digest, $publicKey);
        } catch (\SodiumException $e) {
            return false;
        }
    }
}
