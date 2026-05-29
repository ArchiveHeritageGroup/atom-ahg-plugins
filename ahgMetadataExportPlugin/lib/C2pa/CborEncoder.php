<?php

declare(strict_types=1);

namespace AhgMetadataExport\C2pa;

use InvalidArgumentException;

/**
 * Minimal deterministic CBOR encoder (CTAP2 canonical) for C2PA manifests —
 * the binary form used when embedding into JUMBF. Pure PHP. Supports the CBOR
 * subset C2PA manifests use: ints, byte/text strings, arrays, string-keyed
 * maps, bool, null. Floats are rejected (manifests are integer/string/
 * structural). Ported verbatim from Heratio ahg-c2pa.
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage C2pa
 */
final class CborEncoder
{
    public static function encode($value): string
    {
        return self::encodeValue($value);
    }

    private static function encodeValue($value): string
    {
        if ($value === null) {
            return chr(0xF6);
        }
        if ($value === true) {
            return chr(0xF5);
        }
        if ($value === false) {
            return chr(0xF4);
        }
        if (is_int($value)) {
            return self::encodeInt($value);
        }
        if (is_string($value)) {
            return self::encodeTextString($value);
        }
        if (is_array($value)) {
            if ($value === []) {
                return self::encodeHeader(4, 0);
            }
            if (array_is_list($value)) {
                $out = self::encodeHeader(4, count($value));
                foreach ($value as $item) {
                    $out .= self::encodeValue($item);
                }

                return $out;
            }

            return self::encodeMap($value);
        }
        if (is_float($value)) {
            throw new InvalidArgumentException('CborEncoder: floats not supported in C2PA manifest payloads');
        }

        throw new InvalidArgumentException('CborEncoder: unsupported type ' . get_debug_type($value));
    }

    private static function encodeMap(array $map): string
    {
        $entries = [];
        foreach ($map as $k => $v) {
            if (!is_string($k)) {
                throw new InvalidArgumentException('CborEncoder: map keys must be strings');
            }
            $entries[] = [self::encodeTextString($k), self::encodeValue($v)];
        }
        usort($entries, function (array $a, array $b): int {
            $la = strlen($a[0]);
            $lb = strlen($b[0]);

            return $la !== $lb ? $la <=> $lb : strcmp($a[0], $b[0]);
        });

        $out = self::encodeHeader(5, count($entries));
        foreach ($entries as [$k, $v]) {
            $out .= $k . $v;
        }

        return $out;
    }

    private static function encodeInt(int $n): string
    {
        return $n >= 0 ? self::encodeHeader(0, $n) : self::encodeHeader(1, -1 - $n);
    }

    private static function encodeTextString(string $s): string
    {
        return self::encodeHeader(3, strlen($s)) . $s;
    }

    private static function encodeHeader(int $major, int $value): string
    {
        $tag = ($major & 0x07) << 5;
        if ($value < 0) {
            throw new InvalidArgumentException('CborEncoder: header value must be non-negative');
        }
        if ($value < 24) {
            return chr($tag | $value);
        }
        if ($value < 0x100) {
            return chr($tag | 24) . chr($value);
        }
        if ($value < 0x10000) {
            return chr($tag | 25) . pack('n', $value);
        }
        if ($value < 0x100000000) {
            return chr($tag | 26) . pack('N', $value);
        }

        return chr($tag | 27) . pack('J', $value);
    }
}
