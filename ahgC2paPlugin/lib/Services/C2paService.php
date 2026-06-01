<?php
/**
 * PSIS / AtoM-AHG - high-level C2PA orchestration: build, sign, embed/sidecar,
 * persist, verify.
 *
 * Symfony 1.4 / PHP 8.3 port of Heratio's AhgC2pa\Services\C2paService. The
 * Laravel DB/Log/Schema facades are replaced by the Capsule manager and
 * error_log(). Signing is optional: when no Ed25519 key / crypto library is
 * available the service still builds + sidecars manifests, and embedInJpeg()
 * falls back to a sidecar when the c2patool binary is absent.
 *
 * Four ways in:
 *   manifestForAiSuggestion()  - assemble + return unsigned manifest dict
 *   manifestForDigitalObject() - assemble from a DAM asset + its stds metadata
 *   signManifest()             - sign it (requires a Signer)
 *   sidecar() / embedInJpeg()  - write .c2pa.json / embed JUMBF via c2patool
 *   verify()                   - re-hash assertions + verify the claim signature
 *
 * Every signed manifest can be persisted to ahg_c2pa_manifest for audit + reissue.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use AhgC2pa\Manifest\Assertion;
use AhgC2pa\Manifest\C2paSigner;
use AhgC2pa\Manifest\Claim;
use AhgC2pa\Manifest\ManifestBuilder;
use AhgC2pa\Manifest\StandardMetadataLoader;
use AhgInferenceReceipts\Signer as ReceiptSigner;
use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;
use Throwable;

final class C2paService
{
    /**
     * @param ReceiptSigner|null $receiptSigner Ed25519 signer, or null for
     *                                          build/sidecar/verify-only operation
     * @param string|null        $c2paToolBinary path to c2patool, or null to autodetect
     */
    public function __construct(
        private ?ReceiptSigner $receiptSigner = null,
        private ?string $c2paToolBinary = null,
    ) {
        if ($this->c2paToolBinary === null) {
            $this->c2paToolBinary = self::autodetectBinary();
        }
    }

    /**
     * True when a signing key is available (signManifest will work).
     */
    public function canSign(): bool
    {
        return $this->receiptSigner !== null;
    }

    /**
     * True when the c2patool binary was found (embedInJpeg can embed JUMBF).
     */
    public function canEmbed(): bool
    {
        return $this->c2paToolBinary !== null;
    }

    /**
     * Resolved c2patool path, or null.
     */
    public function toolPath(): ?string
    {
        return $this->c2paToolBinary;
    }

    private static function autodetectBinary(): ?string
    {
        foreach (['/usr/local/bin/c2patool', '/usr/bin/c2patool'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }
        $which = @shell_exec('command -v c2patool 2>/dev/null');
        if (is_string($which) && trim($which) !== '') {
            return trim($which);
        }

        return null;
    }

    /**
     * Build (unsigned) an AI-suggestion manifest.
     *
     * @return array<string,mixed>
     */
    public function manifestForAiSuggestion(
        int $informationObjectId,
        string $action,
        string $modelId,
        ?string $modelVersion,
        string $output,
        ?string $assetPath = null,
        ?string $frameworkVersion = null,
        ?int $digitalObjectId = null,
    ): array {
        if (!in_array($action, ['ai-generated', 'ai-assisted'], true)) {
            throw new RuntimeException("C2paService: action must be ai-generated or ai-assisted, got '{$action}'");
        }

        $builder = (new ManifestBuilder())
            ->withTitle("AtoM-AHG AI {$action} for IO #{$informationObjectId}")
            ->withFormat($assetPath !== null ? self::mimeOfFile($assetPath) : 'text/plain')
            ->withClaimGenerator('AtoM-AHG/' . ($frameworkVersion ?? 'unknown') . ' c2pa-php/1.0')
            ->addAssertion(Assertion::action($action, [
                'model_id'         => $modelId,
                'model_version'    => $modelVersion,
                'output_sha256'    => hash('sha256', $output),
                'atom_io_id'       => $informationObjectId,
                'frameworkVersion' => $frameworkVersion ?? 'unknown',
            ]))
            ->addAssertion(Assertion::trainingMining(
                permitted: false,
                reason: 'AI-derived artefact in archival custody; downstream training requires explicit licence',
            ));

        if ($digitalObjectId !== null) {
            $builder->withStandardMetadata($digitalObjectId, $informationObjectId);
        }

        if ($assetPath !== null) {
            $builder->withAssetFile($assetPath);
        } else {
            $builder->withAssetString($output);
        }

        return $builder->build();
    }

    /**
     * Build an unsigned C2PA manifest wrapping a digital object's host file
     * plus its three Standard Metadata Assertions.
     *
     * @return array<string,mixed>
     */
    public function manifestForDigitalObject(
        int $informationObjectId,
        int $digitalObjectId,
        string $assetPath,
        ?string $frameworkVersion = null,
        ?StandardMetadataLoader $loader = null,
    ): array {
        if (!is_readable($assetPath)) {
            throw new RuntimeException("C2paService: asset not readable: {$assetPath}");
        }

        $builder = (new ManifestBuilder())
            ->withTitle("AtoM-AHG digital object #{$digitalObjectId} (IO #{$informationObjectId})")
            ->withFormat(self::mimeOfFile($assetPath))
            ->withClaimGenerator('AtoM-AHG/' . ($frameworkVersion ?? 'unknown') . ' c2pa-php/1.0')
            ->withAssetFile($assetPath)
            ->withStandardMetadata($digitalObjectId, $informationObjectId, $loader);

        // ManifestBuilder requires at least one assertion. Fall back to a
        // minimal "placed" action when there were no sidecar assertions.
        try {
            return $builder->build();
        } catch (Throwable) {
            $builder->addAssertion(Assertion::action('placed', [
                'softwareAgent' => ['name' => 'AtoM-AHG', 'version' => $frameworkVersion ?? 'unknown'],
            ]));

            return $builder->build();
        }
    }

    /**
     * Verify a signed manifest end-to-end.
     *
     * @param array<string,mixed> $signedManifest
     * @param callable(string $kid): ?string $publicKeyResolver
     * @return array{ok:bool, errors:list<string>, assertion_hashes:array<string,string>}
     */
    public static function verify(array $signedManifest, callable $publicKeyResolver): array
    {
        $errors = [];
        $assertionHashes = [];

        $assertions = $signedManifest['assertions'] ?? null;
        $claimRefs = $signedManifest['claim']['assertions'] ?? null;

        if (!is_array($assertions)) {
            $errors[] = 'missing assertions array';
        }
        if (!is_array($claimRefs)) {
            $errors[] = 'missing claim.assertions array';
        }

        if (is_array($assertions) && is_array($claimRefs)) {
            foreach ($assertions as $i => $a) {
                if (!is_array($a) || !isset($a['label'], $a['data'])) {
                    $errors[] = "assertion #{$i}: missing label or data";

                    continue;
                }
                try {
                    $obj = new Assertion(
                        (string) $a['label'],
                        is_array($a['data']) ? $a['data'] : [],
                        (int) ($a['instance'] ?? 1),
                    );
                } catch (Throwable $e) {
                    $errors[] = "assertion #{$i}: " . $e->getMessage();

                    continue;
                }
                $hash = $obj->hashHex();
                $assertionHashes[$obj->uri()] = $hash;

                $found = false;
                foreach ($claimRefs as $ref) {
                    if (!is_array($ref)) {
                        continue;
                    }
                    if (($ref['url'] ?? null) === $obj->uri()) {
                        $found = true;
                        if (($ref['hash'] ?? null) !== $hash) {
                            $errors[] = "assertion {$obj->uri()}: hash mismatch (label '{$obj->label}' tampered)";
                        }

                        break;
                    }
                }
                if (!$found) {
                    $errors[] = "assertion {$obj->uri()}: not referenced by claim";
                }
            }
        }

        $sigOk = false;
        try {
            $sigOk = C2paSigner::verify($signedManifest, $publicKeyResolver);
        } catch (Throwable $e) {
            $errors[] = 'signature: ' . $e->getMessage();
        }
        if (!$sigOk) {
            $errors[] = 'claim signature did not verify';
        }

        return [
            'ok'               => $errors === [],
            'errors'           => $errors,
            'assertion_hashes' => $assertionHashes,
        ];
    }

    /**
     * Sign a manifest.
     *
     * @param array<string,mixed> $manifest from manifestForAiSuggestion() / ManifestBuilder::build()
     * @return array<string,mixed> {manifest_label, assertions, claim, claim_signature}
     */
    public function signManifest(array $manifest): array
    {
        if ($this->receiptSigner === null) {
            throw new RuntimeException('C2paService: no signing key installed; run `php symfony ai-compliance:install-key` (ahgAiCompliancePlugin) first.');
        }

        $claimObj = $manifest['_claim_object'] ?? null;
        if (!$claimObj instanceof Claim) {
            throw new RuntimeException('C2paService: manifest missing _claim_object; was it built by ManifestBuilder?');
        }

        $signer = new C2paSigner($this->receiptSigner);
        $signed = $signer->sign($claimObj);

        return [
            'manifest_label'  => $manifest['manifest_label'],
            'assertions'      => $manifest['assertions'],
            'claim'           => $signed['claim'],
            'claim_signature' => $signed['claim_signature'],
        ];
    }

    /**
     * Write a signed manifest as `<artefactPath>.c2pa.json`. Always works.
     *
     * @param array<string,mixed> $signedManifest
     * @return string absolute path of the sidecar file
     */
    public function sidecar(array $signedManifest, string $artefactPath): string
    {
        $sidecarPath = $artefactPath . '.c2pa.json';
        $dir = dirname($sidecarPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("C2paService: cannot create sidecar directory {$dir}");
        }

        $json = ManifestBuilder::toCanonicalJson($signedManifest);
        if (@file_put_contents($sidecarPath, $json, LOCK_EX) === false) {
            throw new RuntimeException("C2paService: cannot write sidecar to {$sidecarPath}");
        }

        return $sidecarPath;
    }

    /**
     * Embed a manifest in a JPEG via the c2patool CLI. Falls back to a sidecar
     * when the CLI is not installed or the embed fails.
     *
     * @param array<string,mixed> $signedManifest
     * @return string absolute path of the produced artefact (.c2pa.jpg or .c2pa.json)
     */
    public function embedInJpeg(string $imagePath, array $signedManifest): string
    {
        if (!is_readable($imagePath)) {
            throw new RuntimeException("C2paService: input image not readable: {$imagePath}");
        }

        if ($this->c2paToolBinary === null) {
            error_log('[c2pa] c2patool not installed, falling back to sidecar for ' . $imagePath);

            return $this->sidecar($signedManifest, $imagePath);
        }

        $outputPath = preg_replace('/\.jpe?g$/i', '.c2pa.jpg', $imagePath) ?: ($imagePath . '.c2pa.jpg');
        if ($outputPath === $imagePath) {
            $outputPath = $imagePath . '.c2pa.jpg';
        }

        $manifestPath = tempnam(sys_get_temp_dir(), 'c2pa-manifest-') ?: ('/tmp/c2pa-manifest-' . bin2hex(random_bytes(4)));
        file_put_contents($manifestPath, ManifestBuilder::toCanonicalJson($signedManifest));

        $cmd = sprintf(
            '%s %s --manifest %s --output %s 2>&1',
            escapeshellcmd($this->c2paToolBinary),
            escapeshellarg($imagePath),
            escapeshellarg($manifestPath),
            escapeshellarg($outputPath),
        );

        $output = [];
        $exit = 0;
        exec($cmd, $output, $exit);
        @unlink($manifestPath);

        if ($exit !== 0 || !is_readable($outputPath)) {
            error_log('[c2pa] c2patool embed failed (exit ' . $exit . '); falling back to sidecar for ' . $imagePath . ': ' . implode(' | ', $output));

            return $this->sidecar($signedManifest, $imagePath);
        }

        return $outputPath;
    }

    /**
     * Persist a signed manifest to ahg_c2pa_manifest. Best-effort - skips
     * silently if the table does not exist yet.
     *
     * @param array<string,mixed> $signedManifest
     * @return int|null inserted row id, or null if persistence skipped/failed
     */
    public function persist(
        array $signedManifest,
        int $informationObjectId,
        string $action,
        string $modelId,
        ?string $modelVersion,
        ?string $sidecarPath,
    ): ?int {
        try {
            if (!DB::schema()->hasTable('ahg_c2pa_manifest')) {
                return null;
            }

            $canonical = ManifestBuilder::toCanonicalJson($signedManifest);
            $cbor = ManifestBuilder::toCbor($signedManifest);

            $sig = $signedManifest['claim_signature']['sig'] ?? '';
            $kid = $signedManifest['claim_signature']['kid'] ?? '';

            return (int) DB::table('ahg_c2pa_manifest')->insertGetId([
                'information_object_id' => $informationObjectId,
                'action'                => $action,
                'model_id'              => $modelId,
                'model_version'         => $modelVersion,
                'manifest_json'         => $canonical,
                'manifest_cbor'         => $cbor,
                'sidecar_path'          => $sidecarPath,
                'claim_signature'       => $sig,
                'kid'                   => $kid,
                'created_at'            => date('Y-m-d H:i:s.v'),
            ]);
        } catch (Throwable $e) {
            error_log('[c2pa] persist failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Resolve a kid to its raw 32-byte public key. Prefers the
     * ai_inference_key registry (shared with ahgAiCompliancePlugin); falls
     * back to the on-disk signing public key. Returns null when unresolved.
     */
    public static function resolvePublicKey(string $kid): ?string
    {
        try {
            if (DB::schema()->hasTable('ai_inference_key')) {
                $row = DB::table('ai_inference_key')->where('kid', $kid)->first(['public_key']);
                if ($row !== null && is_string($row->public_key) && $row->public_key !== '') {
                    return (string) $row->public_key;
                }
            }
        } catch (Throwable) {
            // table missing or DB down - fall through
        }

        // On-disk fallback: data/ai-keys/inference-signing.pk
        $root = class_exists('sfConfig') ? (string) \sfConfig::get('sf_root_dir', '') : '';
        if ($root === '' && defined('ATOM_ROOT')) {
            $root = (string) ATOM_ROOT;
        }
        if ($root === '') {
            return null;
        }
        $pkPath = rtrim($root, '/') . '/data/ai-keys/inference-signing.pk';
        if (!is_readable($pkPath)) {
            return null;
        }
        $raw = @file_get_contents($pkPath);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $candidateKid = substr(hash('sha256', $raw), 0, 16);

        return $candidateKid === $kid ? $raw : null;
    }

    /**
     * A resolver closure compatible with verify().
     */
    public static function publicKeyResolver(): callable
    {
        return static fn (string $kid): ?string => self::resolvePublicKey($kid);
    }

    private static function mimeOfFile(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'tif', 'tiff' => 'image/tiff',
            'jp2'         => 'image/jp2',
            'pdf'         => 'application/pdf',
            'mp4'         => 'video/mp4',
            'mp3'         => 'audio/mpeg',
            'txt'         => 'text/plain',
            'json'        => 'application/json',
            default       => 'application/octet-stream',
        };
    }
}
