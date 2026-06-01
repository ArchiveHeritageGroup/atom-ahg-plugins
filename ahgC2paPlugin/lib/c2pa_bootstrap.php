<?php
/**
 * PSIS / AtoM-AHG - C2PA plugin bootstrap.
 *
 * AtoM does not PSR-4 autoload namespaced plugin classes, so this file
 * require_once's the AhgC2pa\Manifest\* classes and the C2paService in
 * dependency order. It also best-effort loads the framework-installed
 * ahg/inference-receipts library (AhgInferenceReceipts\{JcsEncoder,Signer,
 * KeyPair}) via the AtoM root composer autoloader if not already present.
 *
 * Call C2paBootstrap::load() before instantiating any AhgC2pa class.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

final class C2paBootstrap
{
    private static bool $loaded = false;

    /**
     * Idempotently load the C2PA manifest classes + their dependency library.
     *
     * @return bool true when the ahg/inference-receipts crypto library is
     *              available (signing possible); false when only the
     *              encoding-only classes loaded (verify-by-hash still works,
     *              but Ed25519 signing/verifying needs the library).
     */
    public static function load(): bool
    {
        if (self::$loaded) {
            return class_exists(\AhgInferenceReceipts\Signer::class);
        }

        // 1) Make sure the crypto library is on the autoload path. The library
        //    ships as a framework composer dependency under the AtoM root
        //    vendor tree. atom-framework/bootstrap.php normally pulls it in,
        //    but CLI / lazy contexts may not have booted yet.
        if (!class_exists(\AhgInferenceReceipts\JcsEncoder::class)) {
            foreach (self::candidateAutoloaders() as $autoload) {
                if (is_readable($autoload)) {
                    require_once $autoload;
                    if (class_exists(\AhgInferenceReceipts\JcsEncoder::class)) {
                        break;
                    }
                }
            }
        }

        // 2) Load the plugin's own manifest + service classes in order.
        $dir = __DIR__ . '/Manifest';
        require_once $dir . '/CborEncoder.php';
        require_once $dir . '/Assertion.php';
        require_once $dir . '/Claim.php';
        require_once $dir . '/StandardMetadataLoader.php';
        require_once $dir . '/C2paSigner.php';
        require_once $dir . '/ManifestBuilder.php';
        require_once __DIR__ . '/Services/C2paService.php';

        self::$loaded = true;

        return class_exists(\AhgInferenceReceipts\Signer::class);
    }

    /**
     * @return list<string>
     */
    private static function candidateAutoloaders(): array
    {
        $root = '';
        if (class_exists('sfConfig')) {
            $root = (string) \sfConfig::get('sf_root_dir', '');
        }
        if ($root === '' && defined('ATOM_ROOT')) {
            $root = (string) ATOM_ROOT;
        }
        if ($root === '') {
            // plugin lives at <root>/atom-ahg-plugins/ahgC2paPlugin/lib
            $root = dirname(__DIR__, 3);
        }
        $root = rtrim($root, '/');

        return [
            $root . '/vendor/composer/autoload.php',
            $root . '/vendor/autoload.php',
            $root . '/atom-framework/vendor/autoload.php',
        ];
    }

    /**
     * Load the active Ed25519 Signer for C2PA signing, reusing the
     * ahgAiCompliancePlugin SignerFactory + key material when present.
     * Returns null when no signing key is installed or the crypto library
     * is absent - callers fall back to sidecar-only / hash-only operation.
     */
    public static function loadSigner(): ?\AhgInferenceReceipts\Signer
    {
        if (!self::load()) {
            return null;
        }

        // Prefer the shared key the AI-compliance plugin installs.
        $secretPath = self::signingSecretPath();
        if ($secretPath !== null && is_readable($secretPath)) {
            try {
                $keyPair = \AhgInferenceReceipts\KeyPair::loadFrom($secretPath);

                return new \AhgInferenceReceipts\Signer($keyPair);
            } catch (\Throwable $e) {
                error_log('[c2pa] loadSigner: failed to load key at ' . $secretPath . ': ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Resolve the shared inference-signing secret-key path. Mirrors
     * ahgAiCompliancePlugin\SignerFactory::DEFAULT_SECRET_RELPATH.
     */
    public static function signingSecretPath(): ?string
    {
        $root = '';
        if (class_exists('sfConfig')) {
            $root = (string) \sfConfig::get('sf_root_dir', '');
        }
        if ($root === '' && defined('ATOM_ROOT')) {
            $root = (string) ATOM_ROOT;
        }
        if ($root === '') {
            $root = dirname(__DIR__, 3);
        }
        $root = rtrim($root, '/');

        return $root . '/data/ai-keys/inference-signing.sk';
    }
}
