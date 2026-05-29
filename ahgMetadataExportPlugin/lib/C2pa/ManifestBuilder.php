<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

use InvalidArgumentException;

/**
 * Assembles a C2PA 2.1 manifest from assertions + a host asset. The signed
 * sidecar JSON form is the deliverable; toCbor() produces the binary form for
 * JUMBF/media embedding on demand. Ported from Heratio ahg-c2pa.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
final class ManifestBuilder
{
    /** @var Assertion[] */
    private array $assertions = [];
    private string $title = '';
    private string $format = 'application/octet-stream';
    private string $claimGenerator = 'AtoM Heratio/1.0';
    private ?string $assetHash = null;
    private ?string $manifestLabel = null;
    private array $claimExtra = [];

    public function withTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function withFormat(string $mimeType): self
    {
        $this->format = $mimeType;

        return $this;
    }

    public function withClaimGenerator(string $generator): self
    {
        $this->claimGenerator = $generator;

        return $this;
    }

    public function withAssetHash(string $hexHash): self
    {
        if (!ctype_xdigit($hexHash)) {
            throw new InvalidArgumentException('ManifestBuilder: asset hash must be hex');
        }
        $this->assetHash = strtolower($hexHash);

        return $this;
    }

    public function withAssetFile(string $path): self
    {
        if (!is_readable($path)) {
            throw new InvalidArgumentException("ManifestBuilder: asset file not readable: {$path}");
        }
        $hash = hash_file('sha256', $path);
        if ($hash === false) {
            throw new InvalidArgumentException("ManifestBuilder: failed to hash {$path}");
        }

        return $this->withAssetHash($hash);
    }

    public function withAssetString(string $body): self
    {
        return $this->withAssetHash(hash('sha256', $body));
    }

    public function withManifestLabel(string $label): self
    {
        $this->manifestLabel = $label;

        return $this;
    }

    public function withClaimExtra(string $key, $value): self
    {
        $this->claimExtra[$key] = $value;

        return $this;
    }

    public function addAssertion(Assertion $a): self
    {
        $this->assertions[] = $a;

        return $this;
    }

    /**
     * Attach stds.exif / stds.iptc / stds.xmp for a digital object from the
     * sidecar tables (empty assertions skipped, GPS gated by #751).
     */
    public function withStandardMetadata(int $digitalObjectId, ?int $objectId = null, ?StandardMetadataLoader $loader = null): self
    {
        $loader = $loader ?? new StandardMetadataLoader();
        foreach ($loader->loadAssertions($digitalObjectId, $objectId) as $a) {
            $this->assertions[] = $a;
        }

        return $this;
    }

    /** @return array<string,mixed> */
    public function build(): array
    {
        if ($this->title === '') {
            throw new InvalidArgumentException('ManifestBuilder: title is required');
        }
        if ($this->assetHash === null) {
            throw new InvalidArgumentException('ManifestBuilder: asset hash is required (call withAssetFile/withAssetString)');
        }
        if ($this->assertions === []) {
            throw new InvalidArgumentException('ManifestBuilder: at least one assertion is required');
        }

        $ts = gmdate('Y-m-d\TH:i:s\Z');
        $label = $this->manifestLabel ?? ('ahg.heratio:' . self::uuidv4());

        $claim = new Claim($this->title, $this->format, $this->claimGenerator, $this->assertions, $this->assetHash, $ts, $this->claimExtra);

        return [
            'manifest_label' => $label,
            'assertions'     => array_map(fn (Assertion $a) => ['label' => $a->label, 'instance' => $a->instance, 'data' => $a->data], $this->assertions),
            'claim'          => $claim->toArray(),
            '_claim_object'  => $claim,
        ];
    }

    /** @param array<string,mixed> $manifest */
    public static function toCanonicalJson(array $manifest): string
    {
        unset($manifest['_claim_object']);

        return JcsEncoder::encode($manifest);
    }

    /** @param array<string,mixed> $manifest */
    public static function toCbor(array $manifest): string
    {
        unset($manifest['_claim_object']);

        return CborEncoder::encode($manifest);
    }

    private static function uuidv4(): string
    {
        $b = random_bytes(16);
        $b[6] = chr((ord($b[6]) & 0x0f) | 0x40);
        $b[8] = chr((ord($b[8]) & 0x3f) | 0x80);
        $h = bin2hex($b);

        return sprintf('%s-%s-%s-%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 12, 4), substr($h, 16, 4), substr($h, 20, 12));
    }
}
