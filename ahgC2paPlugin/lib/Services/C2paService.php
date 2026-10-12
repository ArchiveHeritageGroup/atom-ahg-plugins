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
     * c2patool can write a C2PA manifest into these container formats. PDFs are
     * embedded separately via embedInPdf(); everything else stays sidecar-only.
     */
    private const EMBEDDABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'tif', 'tiff', 'mp4'];

    /**
     * Whether c2patool can embed a manifest into this file's container (by extension).
     */
    public static function isEmbeddableFormat(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($ext, self::EMBEDDABLE_EXTENSIONS, true);
    }

    /**
     * Embed a signed manifest into a media file via c2patool (JPEG/PNG/TIFF/MP4).
     * The source is never modified: c2patool writes a sibling <stem>.c2pa.<ext>
     * (or $destPath). Returns null - never throws - when the tool is absent, the
     * source is unreadable or not embeddable, or c2patool fails.
     *
     * @param array<string,mixed> $manifest signManifest() output
     */
    public function embed(string $srcPath, array $manifest, ?string $destPath = null): ?string
    {
        if ($this->c2paToolBinary === null) {
            error_log('[c2pa] c2patool not installed; embed skipped for ' . $srcPath);

            return null;
        }
        if (!is_readable($srcPath) || !self::isEmbeddableFormat($srcPath)) {
            return null;
        }

        if ($destPath === null) {
            $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
            $destPath = preg_replace('/\.[^.\/]+$/', '.c2pa.' . $ext, $srcPath) ?: ($srcPath . '.c2pa.' . $ext);
            if ($destPath === $srcPath) {
                $destPath = $srcPath . '.c2pa.' . $ext;
            }
        }

        $manifestPath = tempnam(sys_get_temp_dir(), 'c2pa-manifest-') ?: (sys_get_temp_dir() . '/c2pa-manifest-' . bin2hex(random_bytes(4)));
        if (@file_put_contents($manifestPath, ManifestBuilder::toCanonicalJson($manifest)) === false) {
            error_log('[c2pa] could not stage manifest for embed of ' . $srcPath);

            return null;
        }

        if (is_file($destPath)) {
            @unlink($destPath);
        }

        $cmd = sprintf(
            '%s %s --manifest %s --output %s --force 2>&1',
            escapeshellcmd($this->c2paToolBinary),
            escapeshellarg($srcPath),
            escapeshellarg($manifestPath),
            escapeshellarg($destPath),
        );

        $output = [];
        $exit = 0;
        exec($cmd, $output, $exit);
        @unlink($manifestPath);

        if ($exit !== 0 || !is_readable($destPath)) {
            error_log('[c2pa] c2patool embed failed (exit ' . $exit . ') for ' . $srcPath . ': ' . implode(' | ', $output));

            return null;
        }

        return $destPath;
    }

    /**
     * Embed a manifest in a JPEG via c2patool, falling back to a sidecar on any
     * degrade. Always returns a path (embedded .c2pa.jpg or .c2pa.json sidecar).
     *
     * @param array<string,mixed> $signedManifest
     */
    public function embedInJpeg(string $imagePath, array $signedManifest): string
    {
        if (!is_readable($imagePath)) {
            throw new RuntimeException("C2paService: input image not readable: {$imagePath}");
        }

        return $this->embed($imagePath, $signedManifest) ?? $this->sidecar($signedManifest, $imagePath);
    }

    /**
     * Embed the signed manifest INSIDE a PDF as a C2PA associated file
     * (/AFRelationship /C2PA_Manifest) via an append-only incremental update.
     * Original bytes are untouched. Returns null (caller falls back to a
     * sidecar) for anything it cannot modify safely: xref streams, encrypted,
     * already-signed or non-trivial catalogs. The output is round-tripped.
     *
     * @param array<string,mixed> $signedManifest
     */
    public function embedInPdf(string $srcPath, array $signedManifest, ?string $destPath = null): ?string
    {
        if (!is_readable($srcPath)) {
            return null;
        }
        $pdf = @file_get_contents($srcPath);
        if ($pdf === false || strncmp($pdf, '%PDF-', 5) !== 0) {
            return null;
        }

        if (!preg_match_all('/startxref\s+(\d+)\s+%%EOF/s', $pdf, $sx) || empty($sx[1])) {
            return null;
        }
        $prevStart = (int) end($sx[1]);
        if (substr($pdf, $prevStart, 4) !== 'xref') {
            return null;
        }
        if (!preg_match('/trailer\s*<<(.*?)>>\s*startxref/s', $pdf, $tm)) {
            return null;
        }
        $trailer = $tm[1];
        if (stripos($trailer, '/Encrypt') !== false) {
            return null;
        }
        if (stripos($pdf, '/Type /Sig') !== false || stripos($pdf, '/Type/Sig') !== false
            || stripos($pdf, '/SigFlags') !== false) {
            return null;
        }
        if (!preg_match('/\/Root\s+(\d+)\s+\d+\s+R/', $trailer, $rm)
            || !preg_match('/\/Size\s+(\d+)/', $trailer, $szm)) {
            return null;
        }
        $rootNum = (int) $rm[1];
        $size = (int) $szm[1];

        if (!preg_match('/(?<!\d)' . $rootNum . '\s+0\s+obj\s*<<(.*?)>>\s*endobj/s', $pdf, $cm)) {
            return null;
        }
        $catInner = trim($cm[1]);
        if ($catInner === '' || preg_match('/\/(Names|AF|Encrypt)\b/', $catInner)
            || stripos($catInner, '/Pages') === false || strpos($catInner, '<<') !== false) {
            return null;
        }

        $bytes = ManifestBuilder::toCanonicalJson($signedManifest);
        $len = strlen($bytes);
        $efNum = $size;
        $fsNum = $size + 1;
        $newSize = $size + 2;

        $dest = $destPath ?? (preg_replace('/\.pdf$/i', '.c2pa.pdf', $srcPath) ?: $srcPath . '.c2pa.pdf');
        if ($dest === $srcPath) {
            $dest = $srcPath . '.c2pa.pdf';
        }

        $out = rtrim($pdf, "\r\n") . "\n";
        $off = [];

        $off[$efNum] = strlen($out);
        $out .= "{$efNum} 0 obj\n<< /Type /EmbeddedFile /Subtype /application#2Fjson /Length {$len} >>\nstream\n"
              . $bytes . "\nendstream\nendobj\n";

        $off[$fsNum] = strlen($out);
        $out .= "{$fsNum} 0 obj\n<< /Type /Filespec /F (c2pa.json) /UF (c2pa.json) /AFRelationship /C2PA_Manifest"
              . " /Desc (C2PA Content Credential) /EF << /F {$efNum} 0 R /UF {$efNum} 0 R >> >>\nendobj\n";

        $off[$rootNum] = strlen($out);
        $out .= "{$rootNum} 0 obj\n<< {$catInner} /AF [ {$fsNum} 0 R ]"
              . " /Names << /EmbeddedFiles << /Names [ (c2pa.json) {$fsNum} 0 R ] >> >> >>\nendobj\n";

        ksort($off);
        $xrefStart = strlen($out);
        $xref = "xref\n";
        $nums = array_keys($off);
        $count = count($nums);
        for ($i = 0; $i < $count;) {
            $start = $nums[$i];
            $group = [$start];
            while ($i + 1 < $count && $nums[$i + 1] === $nums[$i] + 1) {
                $group[] = $nums[++$i];
            }
            $i++;
            $xref .= $start . ' ' . count($group) . "\n";
            foreach ($group as $n) {
                $xref .= sprintf("%010d %05d n \n", $off[$n], 0);
            }
        }
        $out .= $xref
              . "trailer\n<< /Size {$newSize} /Root {$rootNum} 0 R /Prev {$prevStart} >>\nstartxref\n{$xrefStart}\n%%EOF\n";

        if (@file_put_contents($dest, $out) === false) {
            return null;
        }

        if ($this->extractC2paFromPdf($dest) !== $bytes) {
            @unlink($dest);
            error_log('[c2pa] PDF embed self-check failed, falling back to sidecar for ' . $srcPath);

            return null;
        }

        return $dest;
    }

    /**
     * Read the C2PA manifest back out of a PDF's embedded file (counterpart to
     * embedInPdf). Returns the raw manifest bytes or null.
     */
    public function extractC2paFromPdf(string $pdfPath): ?string
    {
        $pdf = @file_get_contents($pdfPath);
        if ($pdf === false) {
            return null;
        }
        if (!preg_match_all('/\/Type\s*\/EmbeddedFile.*?stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m) || empty($m[1])) {
            return null;
        }

        return end($m[1]);
    }

    /**
     * Persist a signed manifest to ahg_c2pa_manifest. Best-effort - returns null
     * when the table is absent or the insert fails.
     *
     * The PSIS table is shared with ahgMetadataExportPlugin and was created from
     * that plugin's schema (manifest_label, asset_hash, signature_hex), not the
     * Heratio one (action, model_id, sidecar_path, claim_signature, ...). Write
     * every column of both shapes that actually exists, so this works before and
     * after database/upgrade_heratio_parity.sql is applied.
     *
     * @param array<string,mixed> $signedManifest
     */
    public function persist(
        array $signedManifest,
        int $informationObjectId,
        string $action,
        string $modelId,
        ?string $modelVersion,
        ?string $sidecarPath,
        ?int $digitalObjectId = null,
    ): ?int {
        try {
            $columns = self::manifestColumns();
            if ($columns === []) {
                return null;
            }

            $sig = (string) ($signedManifest['claim_signature']['sig'] ?? '');
            $kid = (string) ($signedManifest['claim_signature']['kid'] ?? '');
            $now = date('Y-m-d H:i:s');

            $row = [
                'information_object_id' => $informationObjectId > 0 ? $informationObjectId : null,
                'digital_object_id'     => $digitalObjectId,
                'manifest_label'        => (string) ($signedManifest['manifest_label'] ?? ('ahg.atom:' . bin2hex(random_bytes(16)))),
                'asset_hash'            => (string) ($signedManifest['claim']['asset_hash']['hash'] ?? str_repeat('0', 64)),
                'action'                => $action,
                'model_id'              => $modelId,
                'model_version'         => $modelVersion,
                'manifest_json'         => ManifestBuilder::toCanonicalJson($signedManifest),
                'manifest_cbor'         => ManifestBuilder::toCbor($signedManifest),
                'sidecar_path'          => $sidecarPath,
                'claim_signature'       => $sig,
                'signature_hex'         => $sig,
                'kid'                   => $kid,
                'created_at'            => $now,
                'updated_at'            => $now,
            ];

            return (int) DB::table('ahg_c2pa_manifest')->insertGetId(array_intersect_key($row, array_flip($columns)));
        } catch (Throwable $e) {
            error_log('[c2pa] persist failed: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Whether a file is encrypted at rest by the framework EncryptionService
     * (AHG-ENC-V1/V2 header). Such masters must be hashed and signed as their
     * plaintext, and never get an embedded copy written beside them.
     */
    public static function isEncryptedAtRest(string $path): bool
    {
        try {
            return class_exists(\AtomFramework\Core\Security\EncryptionService::class)
                && \AtomFramework\Core\Security\EncryptionService::isEncryptedFile($path);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Decrypt an encrypted-at-rest file to a private temp copy that keeps the
     * original basename (so the extension, and with it the MIME type, survives).
     * The CALLER must unlink the returned path. Throws when decryption fails.
     */
    public static function decryptToTemp(string $path): string
    {
        $dir = rtrim(sys_get_temp_dir(), '/') . '/c2pa-plain-' . bin2hex(random_bytes(8));
        if (!@mkdir($dir, 0700) && !is_dir($dir)) {
            throw new RuntimeException('C2paService: cannot create temp dir for decryption');
        }
        $out = $dir . '/' . basename($path);
        \AtomFramework\Core\Security\EncryptionService::decryptFile($path, $out);

        return $out;
    }

    /**
     * Remove a decryptToTemp() copy and its private directory.
     */
    public static function removeTemp(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }
        if (is_file($path)) {
            @unlink($path);
        }
        $dir = dirname($path);
        if (str_contains(basename($dir), 'c2pa-plain-')) {
            @rmdir($dir);
        }
    }

    /**
     * Column names of ahg_c2pa_manifest ([] when the table is missing), cached
     * per process.
     *
     * @return list<string>
     */
    public static function manifestColumns(): array
    {
        static $columns = null;
        if ($columns === null) {
            try {
                $columns = DB::schema()->hasTable('ahg_c2pa_manifest')
                    ? DB::schema()->getColumnListing('ahg_c2pa_manifest')
                    : [];
            } catch (Throwable) {
                $columns = [];
            }
        }

        return $columns;
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
        } catch (Throwable $ahgSwallowed) {
            \class_exists('AhgCore\\Core\\AhgLog') && \AhgCore\Core\AhgLog::swallowed($ahgSwallowed, basename(__FILE__).':'.__LINE__);
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
