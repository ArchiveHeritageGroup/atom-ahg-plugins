<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

use InvalidArgumentException;

/**
 * A single C2PA assertion (smallest unit of provenance). The claim references
 * each assertion by JUMBF URI + SHA-256 of its canonical (JCS) bytes, so any
 * tampering fails re-hash on verify. Ported from Heratio ahg-c2pa.
 *
 * Labels: c2pa.actions.v2, c2pa.training-mining, c2pa.ingredients,
 * stds.exif, stds.iptc, stds.xmp (C2PA 2.1 Standard Metadata Assertions).
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
final class Assertion
{
    public string $label;
    public array $data;
    public int $instance;

    public function __construct(string $label, array $data, int $instance = 1)
    {
        if ($label === '') {
            throw new InvalidArgumentException('Assertion: label must not be empty');
        }
        if ($instance < 1) {
            throw new InvalidArgumentException('Assertion: instance must be >= 1');
        }
        $this->label = $label;
        $this->data = $data;
        $this->instance = $instance;
    }

    public function uriFragment(): string
    {
        return $this->label . '__' . $this->instance;
    }

    public function uri(): string
    {
        return 'self#jumbf=c2pa.assertions/' . $this->uriFragment();
    }

    public function canonicalBytes(): string
    {
        return JcsEncoder::encode($this->data);
    }

    public function hashHex(): string
    {
        return hash('sha256', $this->canonicalBytes());
    }

    /** @return array<string,string> */
    public function hashedUri(): array
    {
        return ['alg' => 'sha256', 'hash' => $this->hashHex(), 'url' => $this->uri()];
    }

    public static function action(string $action, array $parameters = []): self
    {
        return new self('c2pa.actions.v2', [
            'actions' => [[
                'action'        => $action,
                'when'          => gmdate('Y-m-d\TH:i:s\Z'),
                'softwareAgent' => $parameters['softwareAgent'] ?? [
                    'name'    => 'AtoM Heratio',
                    'version' => $parameters['heratioVersion'] ?? 'unknown',
                ],
                'parameters' => array_diff_key($parameters, array_flip(['softwareAgent', 'heratioVersion'])),
            ]],
        ]);
    }

    public static function trainingMining(bool $permitted, ?string $reason = null): self
    {
        $use = $permitted ? 'allowed' : 'notAllowed';
        $data = [
            'entries' => [
                'c2pa.ai_generative_training' => ['use' => $use],
                'c2pa.ai_inference'           => ['use' => $use],
                'c2pa.ai_training'            => ['use' => $use],
                'c2pa.data_mining'            => ['use' => $use],
            ],
        ];
        if ($reason !== null && $reason !== '') {
            $data['reason'] = $reason;
        }

        return new self('c2pa.training-mining', $data);
    }

    public static function ingredients(array $ingredients): self
    {
        return new self('c2pa.ingredients', ['ingredients' => $ingredients]);
    }

    public static function stdsExif(array $entries): self
    {
        return new self('stds.exif', $entries);
    }

    public static function stdsIptc(array $entries): self
    {
        return new self('stds.iptc', $entries);
    }

    public static function stdsXmp(array $entries): self
    {
        return new self('stds.xmp', $entries);
    }
}
