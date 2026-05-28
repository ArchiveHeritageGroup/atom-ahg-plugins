<?php
/**
 * PSIS / AtoM-AHG - loads or generates the Ed25519 signing keypair for the inference chain.
 *
 * Mirrors Heratio's storage/keys/ layout under AtoM's data/ tree:
 *   data/ai-keys/inference-signing.sk  (raw 64-byte libsodium secret, mode 0600)
 *   data/ai-keys/inference-signing.pk  (raw 32-byte libsodium public key)
 *
 * The directory is created with mode 0700; if AtoM's data/ is owned by
 * www-data the same uid must run the install task (run with sudo -u www-data).
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

use AhgInferenceReceipts\KeyPair;
use AhgInferenceReceipts\Signer;

final class SignerFactory
{
    public const DEFAULT_SECRET_RELPATH = 'data/ai-keys/inference-signing.sk';
    public const DEFAULT_PUBLIC_RELPATH = 'data/ai-keys/inference-signing.pk';

    /**
     * Load the signer from disk. Throws if the key file is missing.
     */
    public static function load(?string $secretPath = null): Signer
    {
        $secretPath = $secretPath ?? self::secretPath();
        $keyPair    = KeyPair::loadFrom($secretPath);
        return new Signer($keyPair);
    }

    /**
     * Generate a fresh keypair, persist it to $secretPath/$publicPath, and
     * return a Signer wrapping it. Does NOT register the kid in the DB - the
     * install task does that step via KeyResolver::register().
     */
    public static function generateAndSave(
        ?string $secretPath = null,
        ?string $publicPath = null,
    ): array {
        $secretPath = $secretPath ?? self::secretPath();
        $publicPath = $publicPath ?? self::publicPath();

        $keyPair = KeyPair::generate();
        $keyPair->saveTo($secretPath, $publicPath);

        return [
            'keyPair'    => $keyPair,
            'signer'     => new Signer($keyPair),
            'secretPath' => $secretPath,
            'publicPath' => $publicPath,
        ];
    }

    public static function secretPath(): string
    {
        return self::root() . '/' . self::DEFAULT_SECRET_RELPATH;
    }

    public static function publicPath(): string
    {
        return self::root() . '/' . self::DEFAULT_PUBLIC_RELPATH;
    }

    /**
     * Resolve the AtoM root directory regardless of CLI / web context.
     */
    private static function root(): string
    {
        if (class_exists('sfConfig')) {
            $root = sfConfig::get('sf_root_dir');
            if (!empty($root)) {
                return rtrim((string) $root, '/');
            }
        }
        // Heuristic fallback: the plugin lives at <root>/plugins/ahgAiCompliancePlugin/lib
        return dirname(__DIR__, 3);
    }
}
