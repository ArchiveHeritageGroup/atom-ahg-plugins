<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

use InvalidArgumentException;

/**
 * The C2PA "claim" — the one structure inside a manifest that gets signed.
 * References every assertion by URI + content hash, pins the asset hash, and
 * declares generator/title/format. Ported from Heratio ahg-c2pa.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
final class Claim
{
    private string $instanceId;
    public string $title;
    public string $format;
    public string $claimGenerator;
    public array $assertions;
    public string $assetHash;
    public string $ts;
    public array $extra;

    /**
     * @param Assertion[] $assertions
     */
    public function __construct(string $title, string $format, string $claimGenerator, array $assertions, string $assetHash, string $ts, array $extra = [])
    {
        if ($title === '') {
            throw new InvalidArgumentException('Claim: title must not be empty');
        }
        if ($format === '') {
            throw new InvalidArgumentException('Claim: format must not be empty');
        }
        foreach ($assertions as $a) {
            if (!$a instanceof Assertion) {
                throw new InvalidArgumentException('Claim: assertions must all be Assertion instances');
            }
        }
        if ($assetHash === '' || !ctype_xdigit($assetHash)) {
            throw new InvalidArgumentException('Claim: assetHash must be a hex string');
        }
        $this->title = $title;
        $this->format = $format;
        $this->claimGenerator = $claimGenerator;
        $this->assertions = $assertions;
        $this->assetHash = $assetHash;
        $this->ts = $ts;
        $this->extra = $extra;
        $this->instanceId = (isset($extra['instanceID']) && is_string($extra['instanceID']) && $extra['instanceID'] !== '')
            ? $extra['instanceID']
            : ('xmp:iid:' . bin2hex(random_bytes(8)));
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        $base = [
            'claim_generator' => $this->claimGenerator,
            'title'           => $this->title,
            'format'          => $this->format,
            'instanceID'      => $this->instanceId,
            'signature'       => 'self#jumbf=c2pa.signature',
            'alg'             => 'sha256',
            'created'         => $this->ts,
            'asset_hash'      => ['alg' => 'sha256', 'hash' => $this->assetHash],
            'assertions'      => array_map(fn (Assertion $a) => $a->hashedUri(), $this->assertions),
        ];
        foreach ($this->extra as $k => $v) {
            if ($k === 'instanceID') {
                continue;
            }
            $base[$k] = $v;
        }

        return $base;
    }

    public function canonicalBytes(): string
    {
        return JcsEncoder::encode($this->toArray());
    }

    public function digestHex(): string
    {
        return hash('sha256', $this->canonicalBytes());
    }
}
