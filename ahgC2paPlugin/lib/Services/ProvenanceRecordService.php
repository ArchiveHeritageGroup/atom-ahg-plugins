<?php
/**
 * PSIS / AtoM-AHG - digitisation provenance / content-credentials records.
 *
 * Symfony 1.4 port of Heratio's AhgC2pa\Services\ProvenanceRecordService.
 * Records who digitised an asset, when, on what device and software, plus any
 * AI-inference steps that touched it, and (when a signing key is installed)
 * binds the record to an Ed25519-signed C2PA manifest in ahg_c2pa_manifest.
 *
 * @copyright Copyright (c) 2026, Plain Sailing Information Systems
 * @author    Johan Pieterse <johan@plainsailingisystems.co.za>
 * @license   AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace AhgC2pa\Services;

use AhgC2pa\Manifest\Assertion;
use AhgC2pa\Manifest\ManifestBuilder;
use AhgInferenceReceipts\JcsEncoder;
use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;
use Throwable;

final class ProvenanceRecordService
{
    public function __construct(private C2paService $c2pa)
    {
    }

    /**
     * Honest capability report for the UI. On PSIS signing depends on the
     * shared key from ahgAiCompliancePlugin being readable by this process,
     * not only on ext-sodium.
     *
     * @return array{sodium:bool, can_sign_manifest:bool, c2patool:bool, can_embed_media:bool, summary:string}
     */
    public function capability(): array
    {
        $sodium = extension_loaded('sodium') || function_exists('sodium_crypto_sign');
        $canSign = $sodium && $this->c2pa->canSign();
        $canEmbed = $this->c2pa->canEmbed();

        if ($canSign && $canEmbed) {
            $summary = 'Full: manifests are Ed25519-signed and can be embedded into media via c2patool.';
        } elseif ($canSign) {
            $summary = 'Manifests are Ed25519-signed and stored as verifiable records and sidecars. c2patool is not installed, so nothing is embedded into the media itself.';
        } elseif (!$sodium) {
            $summary = 'ext-sodium is not available: provenance records are stored but cannot be signed on this host.';
        } else {
            $summary = 'No signing key is readable by this process (install one with php symfony ai-compliance:install-key): provenance records are stored but not signed.';
        }

        return [
            'sodium'            => $sodium,
            'can_sign_manifest' => $canSign,
            'c2patool'          => $canEmbed,
            'can_embed_media'   => $canEmbed,
            'summary'           => $summary,
        ];
    }

    /**
     * Record a digitisation-provenance entry and, when possible, build + sign a
     * C2PA capture manifest bound to it.
     *
     * @param array<string,mixed> $input digital_object_id, captured_by, captured_at,
     *                                   capture_device, capture_software, notes,
     *                                   asset_path, inference_steps, version
     *
     * @return int the ahg_c2pa_provenance row id
     */
    public function record(int $informationObjectId, array $input): int
    {
        if (!self::tableExists('ahg_c2pa_provenance')) {
            throw new RuntimeException('ProvenanceRecordService: ahg_c2pa_provenance table not installed');
        }

        $assetPath = $input['asset_path'] ?? null;
        if (!is_string($assetPath) || $assetPath === '' || !is_readable($assetPath)) {
            $assetPath = null;
        }

        // An encrypted-at-rest master is hashed and signed as its plaintext;
        // the decrypted copy lives only for this call.
        $plainCopy = null;
        if ($assetPath !== null && C2paService::isEncryptedAtRest($assetPath)) {
            try {
                $plainCopy = C2paService::decryptToTemp($assetPath);
            } catch (Throwable $e) {
                error_log('[c2pa] cannot decrypt ' . $assetPath . ' for provenance: ' . $e->getMessage());
                $assetPath = null;
            }
        }

        try {
            return $this->recordWithAsset($informationObjectId, $input, $assetPath, $plainCopy);
        } finally {
            C2paService::removeTemp($plainCopy);
        }
    }

    /**
     * @param array<string,mixed> $input
     * @param string|null         $assetPath the master as stored (sidecar goes next to it)
     * @param string|null         $plainCopy decrypted temp copy when the master is encrypted
     */
    private function recordWithAsset(int $informationObjectId, array $input, ?string $assetPath, ?string $plainCopy): int
    {
        $digitalObjectId = isset($input['digital_object_id']) ? (int) $input['digital_object_id'] : null;
        if ($digitalObjectId !== null && $digitalObjectId <= 0) {
            $digitalObjectId = null;
        }

        $hashPath = $plainCopy ?? $assetPath;
        $assetSha = null;
        if ($hashPath !== null) {
            $h = hash_file('sha256', $hashPath);
            $assetSha = $h === false ? null : $h;
        }

        $inferenceSteps = $input['inference_steps'] ?? [];
        if (!is_array($inferenceSteps)) {
            $inferenceSteps = [];
        }

        $now = date('Y-m-d H:i:s.v');

        $rowId = (int) DB::table('ahg_c2pa_provenance')->insertGetId([
            'information_object_id' => $informationObjectId,
            'digital_object_id'     => $digitalObjectId,
            'captured_by'           => $this->str($input['captured_by'] ?? null, 255),
            'captured_at'           => $this->dateOrNull($input['captured_at'] ?? null),
            'capture_device'        => $this->str($input['capture_device'] ?? null, 255),
            'capture_software'      => $this->str($input['capture_software'] ?? null, 255),
            'notes'                 => $this->str($input['notes'] ?? null, 65535),
            'asset_sha256'          => $assetSha,
            'inference_steps'       => $inferenceSteps === [] ? null : json_encode(array_values($inferenceSteps)),
            'manifest_id'           => null,
            'sign_status'           => 'unsigned',
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        // Never let a signing failure lose the provenance record.
        if ($this->capability()['can_sign_manifest']) {
            try {
                $manifestId = $this->buildAndSignManifest($rowId, $informationObjectId, $digitalObjectId, $assetPath, $plainCopy, $assetSha, $input, $inferenceSteps);
                if ($manifestId !== null) {
                    DB::table('ahg_c2pa_provenance')->where('id', $rowId)->update([
                        'manifest_id' => $manifestId,
                        'sign_status' => 'signed',
                        'updated_at'  => date('Y-m-d H:i:s.v'),
                    ]);
                }
            } catch (Throwable $e) {
                error_log('[c2pa] provenance manifest sign failed for record ' . $rowId . ': ' . $e->getMessage());
            }
        }

        return $rowId;
    }

    /**
     * Build a c2pa.actions.v2 capture manifest (c2pa.created + one c2pa.edited per
     * AI step), sign it, sidecar + embed next to the asset, persist it.
     *
     * @param list<array<string,mixed>> $inferenceSteps
     * @param array<string,mixed>       $input
     */
    private function buildAndSignManifest(
        int $provenanceId,
        int $informationObjectId,
        ?int $digitalObjectId,
        ?string $assetPath,
        ?string $plainCopy,
        ?string $assetSha,
        array $input,
        array $inferenceSteps,
    ): ?int {
        $version = $this->str($input['version'] ?? null, 64) ?? self::frameworkVersion();

        $builder = (new ManifestBuilder())
            ->withTitle("AtoM-AHG digitisation provenance #{$provenanceId} (IO #{$informationObjectId})")
            ->withFormat($assetPath !== null ? self::mimeOfFile($assetPath) : 'application/json')
            ->withClaimGenerator('AtoM-AHG/' . $version . ' c2pa-php/1.0');

        $softwareAgent = [
            'name'    => $this->str($input['capture_device'] ?? null, 255) ?? 'AtoM-AHG',
            'version' => $this->str($input['capture_software'] ?? null, 255) ?? $version,
        ];

        $actions = [[
            'action'        => 'c2pa.created',
            'when'          => $this->dateOrNull($input['captured_at'] ?? null) ?? gmdate('Y-m-d\TH:i:s\Z'),
            'softwareAgent' => $softwareAgent,
            'parameters'    => array_filter([
                'capturedBy'       => $this->str($input['captured_by'] ?? null, 255),
                'atom_io_id'       => $informationObjectId,
                'frameworkVersion' => $version,
            ], static fn ($v) => $v !== null && $v !== ''),
        ]];

        foreach ($inferenceSteps as $step) {
            if (!is_array($step)) {
                continue;
            }
            $actions[] = [
                'action'        => 'c2pa.edited',
                'when'          => gmdate('Y-m-d\TH:i:s\Z'),
                'softwareAgent' => ['name' => 'AtoM-AHG', 'version' => $version],
                'parameters'    => array_filter([
                    'inferenceStep' => $this->str($step['step'] ?? null, 128),
                    'model_id'      => $this->str($step['model_id'] ?? null, 128),
                    'model_version' => $this->str($step['model_version'] ?? null, 64),
                    'output_sha256' => $this->str($step['output_sha256'] ?? null, 64),
                ], static fn ($v) => $v !== null && $v !== ''),
            ];
        }

        $builder->addAssertion(new Assertion('c2pa.actions.v2', ['actions' => $actions]));
        $builder->addAssertion(Assertion::trainingMining(
            permitted: false,
            reason: 'Digitised heritage asset in archival custody; downstream AI training requires explicit licence',
        ));

        if ($assetPath !== null) {
            $builder->withAssetFile($plainCopy ?? $assetPath);
        } else {
            // No file on disk: bind to a synthetic hash of the canonical record.
            $builder->withAssetString(JcsEncoder::encode([
                'provenance_id' => $provenanceId,
                'io'            => $informationObjectId,
                'asset_sha256'  => $assetSha,
            ]));
        }

        $signed = $this->c2pa->signManifest($builder->build());

        $sidecarPath = null;
        if ($assetPath !== null) {
            try {
                $sidecarPath = $this->c2pa->sidecar($signed, $assetPath);
            } catch (Throwable $e) {
                error_log('[c2pa] sidecar write skipped for ' . $assetPath . ': ' . $e->getMessage());
            }

            // An embedded copy of an encrypted master would sit beside it as
            // plaintext, so encrypted masters keep the sidecar only.
            if ($plainCopy === null && $this->c2pa->canEmbed() && C2paService::isEmbeddableFormat($assetPath)) {
                try {
                    $this->c2pa->embed($assetPath, $signed);
                } catch (Throwable $e) {
                    error_log('[c2pa] master embed skipped for ' . $assetPath . ': ' . $e->getMessage());
                }
            }

            if ($plainCopy === null && preg_match('/\.pdf$/i', $assetPath)) {
                try {
                    $this->c2pa->embedInPdf($assetPath, $signed);
                } catch (Throwable $e) {
                    error_log('[c2pa] PDF embed skipped for ' . $assetPath . ': ' . $e->getMessage());
                }
            }
        }

        return $this->c2pa->persist(
            $signed,
            $informationObjectId,
            'c2pa.created',
            'digitisation-capture',
            $version,
            $sidecarPath,
            $digitalObjectId,
        );
    }

    /**
     * Verify a stored provenance record end-to-end.
     *
     * @return array{status:string, ok:bool, errors:list<string>, manifest:array<string,mixed>|null, kid:?string}
     */
    public function verifyRecord(int $provenanceId): array
    {
        $row = $this->find($provenanceId);
        if ($row === null) {
            return ['status' => 'not-found', 'ok' => false, 'errors' => ['provenance record not found'], 'manifest' => null, 'kid' => null];
        }
        if ($row->manifest_id === null) {
            return ['status' => 'unsigned', 'ok' => false, 'errors' => ['no signed manifest bound to this record'], 'manifest' => null, 'kid' => null];
        }

        $manifestRow = DB::table('ahg_c2pa_manifest')->where('id', $row->manifest_id)->first(['manifest_json']);
        if ($manifestRow === null) {
            return ['status' => 'manifest-missing', 'ok' => false, 'errors' => ['bound manifest row is missing'], 'manifest' => null, 'kid' => null];
        }

        $manifest = json_decode((string) $manifestRow->manifest_json, true);
        if (!is_array($manifest)) {
            return ['status' => 'corrupt', 'ok' => false, 'errors' => ['manifest_json is not valid JSON'], 'manifest' => null, 'kid' => null];
        }

        $result = C2paService::verify($manifest, C2paService::publicKeyResolver());

        return [
            'status'   => $result['ok'] ? 'verified' : 'failed',
            'ok'       => $result['ok'],
            'errors'   => $result['errors'],
            'manifest' => $manifest,
            'kid'      => $manifest['claim_signature']['kid'] ?? null,
        ];
    }

    /**
     * @return list<object> newest first
     */
    public function listForObject(int $informationObjectId): array
    {
        if (!self::tableExists('ahg_c2pa_provenance')) {
            return [];
        }

        return DB::table('ahg_c2pa_provenance')
            ->where('information_object_id', $informationObjectId)
            ->orderByDesc('created_at')
            ->get()
            ->all();
    }

    /**
     * @return list<object> oldest first, so a chain reads capture-then-edit
     */
    public function listForDigitalObject(int $digitalObjectId): array
    {
        if ($digitalObjectId <= 0 || !self::tableExists('ahg_c2pa_provenance')) {
            return [];
        }

        return DB::table('ahg_c2pa_provenance')
            ->where('digital_object_id', $digitalObjectId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->all();
    }

    public function find(int $provenanceId): ?object
    {
        if (!self::tableExists('ahg_c2pa_provenance')) {
            return null;
        }

        return DB::table('ahg_c2pa_provenance')->where('id', $provenanceId)->first();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function decodeSteps(object $record): array
    {
        if (!isset($record->inference_steps) || !is_string($record->inference_steps) || $record->inference_steps === '') {
            return [];
        }
        $decoded = json_decode($record->inference_steps, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * id, identifier, title (user culture, then en) and slug of a description.
     */
    public static function loadInformationObject(int $ioId): ?object
    {
        $io = DB::table('information_object')->where('id', $ioId)->first(['id', 'identifier']);
        if ($io === null) {
            return null;
        }
        $culture = \sfContext::hasInstance() ? (string) \sfContext::getInstance()->getUser()->getCulture() : 'en';
        $io->title = DB::table('information_object_i18n')
            ->where('id', $ioId)
            ->orderByRaw('culture = ? DESC', [$culture])
            ->orderByRaw("culture = 'en' DESC")
            ->value('title');
        $io->slug = DB::table('slug')->where('object_id', $ioId)->value('slug');

        return $io;
    }

    public static function frameworkVersion(): string
    {
        static $version = null;
        if ($version === null) {
            $version = 'unknown';
            $root = class_exists('sfConfig') ? (string) \sfConfig::get('sf_root_dir', '') : '';
            $file = rtrim($root, '/') . '/atom-framework/version.json';
            if ($root !== '' && is_readable($file)) {
                $data = json_decode((string) file_get_contents($file), true);
                if (is_array($data) && isset($data['version'])) {
                    $version = (string) $data['version'];
                }
            }
        }

        return $version;
    }

    public static function tableExists(string $table): bool
    {
        static $cache = [];
        if (!array_key_exists($table, $cache)) {
            try {
                $cache[$table] = DB::schema()->hasTable($table);
            } catch (Throwable) {
                $cache[$table] = false;
            }
        }

        return $cache[$table];
    }

    private function str(mixed $v, int $max): ?string
    {
        if ($v === null) {
            return null;
        }
        $s = trim((string) $v);

        return $s === '' ? null : mb_substr($s, 0, $max);
    }

    private function dateOrNull(mixed $v): ?string
    {
        if (!is_string($v) || trim($v) === '') {
            return null;
        }
        $ts = strtotime($v);

        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }

    private static function mimeOfFile(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'tif', 'tiff' => 'image/tiff',
            'jp2'         => 'image/jp2',
            'webp'        => 'image/webp',
            'gif'         => 'image/gif',
            'pdf'         => 'application/pdf',
            'glb', 'gltf' => 'model/gltf-binary',
            'ply'         => 'model/ply',
            'obj'         => 'model/obj',
            'mp4'         => 'video/mp4',
            'mp3'         => 'audio/mpeg',
            default       => 'application/octet-stream',
        };
    }
}
