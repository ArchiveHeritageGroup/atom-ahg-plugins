<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;

require_once __DIR__ . '/JcsEncoder.php';
require_once __DIR__ . '/CborEncoder.php';
require_once __DIR__ . '/C2paKeyPair.php';
require_once __DIR__ . '/C2paSigner.php';
require_once __DIR__ . '/Assertion.php';
require_once __DIR__ . '/Claim.php';
require_once __DIR__ . '/StandardMetadataLoader.php';
require_once __DIR__ . '/ManifestBuilder.php';

/**
 * C2paManifestService — generate, sign, store and verify C2PA content
 * credentials for archival digital objects. Builds the three Standard
 * Metadata Assertions (GPS-gated by #751) plus a training-mining declaration,
 * signs with Ed25519, and persists the signed manifest JSON to
 * ahg_c2pa_manifest. Heratio #749/#753 parity.
 *
 * Key management: the signing key is read from app_c2pa_secret_key_b64
 * (sfConfig) or the ahg_settings 'c2pa_secret_key' row. Generate one with
 * generateKey() and store it before signing.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
class C2paManifestService
{
    private const TABLE = 'ahg_c2pa_manifest';

    private ?C2paKeyPair $keyPair;

    public function __construct(?C2paKeyPair $keyPair = null)
    {
        $this->keyPair = $keyPair ?: self::loadKeyFromConfig();
    }

    /** Generate a new Ed25519 key pair (admin stores secret_b64, publishes public_b64). */
    public static function generateKey(): array
    {
        $kp = C2paKeyPair::generate();

        return ['secret_b64' => $kp->secretKeyBase64(), 'public_b64' => $kp->publicKeyBase64(), 'kid' => $kp->kid()];
    }

    /** Load the signing key pair from config/settings, or null if unconfigured. */
    public static function loadKeyFromConfig(): ?C2paKeyPair
    {
        $b64 = (string) \sfConfig::get('app_c2pa_secret_key_b64', '');
        if ($b64 === '') {
            try {
                $b64 = (string) DB::table('ahg_settings')->where('setting_key', 'c2pa_secret_key')->value('setting_value');
            } catch (\Throwable $e) {
                $b64 = '';
            }
        }

        return $b64 !== '' ? C2paKeyPair::fromBase64($b64) : null;
    }

    public function isConfigured(): bool
    {
        return $this->keyPair !== null;
    }

    public function kid(): ?string
    {
        return $this->keyPair ? $this->keyPair->kid() : null;
    }

    public function publicKeyBase64(): ?string
    {
        return $this->keyPair ? $this->keyPair->publicKeyBase64() : null;
    }

    /**
     * Build + sign + store a C2PA manifest for an information object's master
     * digital object. Returns the signed manifest array.
     *
     * @param array $opts trainingPermitted(bool), action(string|null)
     */
    public function signInformationObject(int $ioId, array $opts = []): array
    {
        if (!$this->keyPair) {
            throw new RuntimeException('C2PA signing key not configured (set app_c2pa_secret_key_b64 or ahg_settings.c2pa_secret_key).');
        }

        $title = (string) (DB::table('information_object_i18n')->where('id', $ioId)->where('culture', 'en')->value('title') ?: ('Information object ' . $ioId));
        $do = DB::table('digital_object')->where('object_id', $ioId)
            ->orderByRaw('CASE WHEN usage_id = 1 THEN 0 ELSE 1 END')->orderBy('id')->first();

        $builder = new ManifestBuilder();
        $builder->withTitle($title)
            ->withFormat($do->mime_type ?? 'application/octet-stream')
            ->withClaimGenerator('AtoM Heratio/2.8.2')
            ->withManifestLabel('ahg.heratio.io.' . $ioId . ':' . bin2hex(random_bytes(4)));

        // Asset hash: prefer a stored sha256 checksum, else hash the title+id.
        $checksum = $do && isset($do->checksum) && is_string($do->checksum) && ctype_xdigit((string) $do->checksum) && strlen((string) $do->checksum) === 64
            ? strtolower((string) $do->checksum) : null;
        if ($checksum) {
            $builder->withAssetHash($checksum);
        } else {
            $builder->withAssetString($ioId . '|' . $title);
        }

        // Standard metadata assertions (GPS-gated by #751).
        if ($do) {
            $builder->withStandardMetadata((int) $do->id, $ioId);
        }

        // Provenance: AI training/mining stance + an optional action.
        $builder->addAssertion(Assertion::trainingMining((bool) ($opts['trainingPermitted'] ?? false), $opts['trainingReason'] ?? 'Archival rights — AI training not permitted without licence'));
        if (!empty($opts['action'])) {
            $builder->addAssertion(Assertion::action((string) $opts['action'], ['heratioVersion' => '2.8.2']));
        }

        $manifest = $builder->build();
        /** @var Claim $claim */
        $claim = $manifest['_claim_object'];

        $signer = new C2paSigner($this->keyPair);
        $signed = $signer->sign($claim);
        // Carry the manifest envelope (label + assertions) alongside the signed claim.
        $signed['manifest_label'] = $manifest['manifest_label'];
        $signed['assertions'] = $manifest['assertions'];

        $this->store($signed, $do ? (int) $do->id : null, $ioId, $claim->assetHash);

        return $signed;
    }

    private function store(array $signed, ?int $doId, ?int $ioId, string $assetHash): int
    {
        $now = date('Y-m-d H:i:s');

        return (int) DB::table(self::TABLE)->insertGetId([
            'digital_object_id'     => $doId,
            'information_object_id'  => $ioId,
            'manifest_label'        => (string) ($signed['manifest_label'] ?? ''),
            'asset_hash'            => $assetHash,
            'kid'                   => (string) ($signed['claim_signature']['kid'] ?? ''),
            'signature_hex'         => (string) ($signed['claim_signature']['sig'] ?? ''),
            'manifest_json'         => JcsEncoder::encode($signed),
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);
    }

    /**
     * Verify a stored manifest against the configured public key (matched by kid).
     */
    public function verifyStored(int $id): ?bool
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();
        if (!$row) {
            return null;
        }
        $manifest = json_decode((string) $row->manifest_json, true);
        if (!is_array($manifest)) {
            return false;
        }

        $expectedKid = $this->kid();
        $pub = $this->keyPair ? $this->keyPair->publicKey() : null;

        return C2paSigner::verify($manifest, function (string $kid) use ($expectedKid, $pub) {
            return ($pub !== null && $kid === $expectedKid) ? $pub : null;
        });
    }
}
