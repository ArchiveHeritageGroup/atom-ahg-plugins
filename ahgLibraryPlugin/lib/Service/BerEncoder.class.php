<?php

declare(strict_types=1);

/**
 * BerEncoder
 *
 * BER (Basic Encoding Rules) encode/decode for Z39.50 APDUs.
 * Implements ITU-T X.690 (BER) and ISO 23950 Z39.50 APDU binary encoding.
 *
 * This is the PSIS / AtoM (Symfony 1.x) port of the Heratio ahg-z3950
 * BerEncoder. It is dependency-free pure PHP and is used by the
 * Z39.50 *server* daemon (z3950:server) to answer raw binary
 * Z39.50 client queries against the AtoM library catalogue.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 *
 * @see https://www.loc.gov/z3950/agency/
 */

namespace AtomExtensions\Services;

class BerEncoder
{
    // Universal tag numbers
    public const TAG_BOOLEAN      = 0x01;
    public const TAG_INTEGER      = 0x02;
    public const TAG_OCTET        = 0x04;
    public const TAG_NULL         = 0x05;
    public const TAG_OID          = 0x06;
    public const TAG_SEQUENCE     = 0x30;
    public const TAG_SET          = 0x31;
    public const TAG_UTF8STR      = 0x0c;
    public const TAG_NUMERICSTR   = 0x12;
    public const TAG_PRINTABLESTR = 0x13;
    public const TAG_IA5STR       = 0x16;
    public const TAG_VISIBLE      = 0x1a;

    // Z39.50 APDU OID: 1.2.840.10003.9.100
    public const OID_INIT_REQUEST      = [1, 2, 840, 10003, 9, 100, 1];
    public const OID_INIT_RESPONSE     = [1, 2, 840, 10003, 9, 100, 2];
    public const OID_SEARCH_REQUEST    = [1, 2, 840, 10003, 9, 100, 6];
    public const OID_SEARCH_RESPONSE   = [1, 2, 840, 10003, 9, 100, 7];
    public const OID_PRESENT_REQUEST   = [1, 2, 840, 10003, 9, 100, 13];
    public const OID_PRESENT_RESPONSE  = [1, 2, 840, 10003, 9, 100, 14];
    public const OID_CLOSE             = [1, 2, 840, 10003, 9, 100, 23];
    public const OID_DELETE_RESULT_SET = [1, 2, 840, 10003, 9, 100, 19];

    public const REFERENCE_ID_PREFIX = 'PSIS';

    // ──── High-level encoding ─────────────────────────────────────────────

    public function encodeInteger(int $value): string
    {
        $body = $this->encodeIntegerValue($value);

        return $this->encodeTagLength(self::TAG_INTEGER, $body) . $body;
    }

    public function encodeOid(array $oid): string
    {
        $body = $this->encodeOidValue($oid);

        return $this->encodeTagLength(self::TAG_OID, $body) . $body;
    }

    public function encodeUtf8(string $value): string
    {
        return $this->encodeTagLength(self::TAG_UTF8STR, $value) . $value;
    }

    public function encodeVisible(string $value): string
    {
        return $this->encodeTagLength(self::TAG_VISIBLE, $value) . $value;
    }

    public function encodeOctet(string $bytes): string
    {
        return $this->encodeTagLength(self::TAG_OCTET, $bytes) . $bytes;
    }

    public function encodeSequence(string $content): string
    {
        return $this->encodeTagLength(self::TAG_SEQUENCE, $content) . $content;
    }

    public function encodeOidSequence(array $oid): string
    {
        return $this->encodeSequence($this->encodeOid($oid));
    }

    public function encodeNull(): string
    {
        return "\x05\x00";
    }

    // ──── Z39.50 APDU encoding ─────────────────────────────────────────────

    /**
     * Encode the options BIT STRING + preferred syntax for InitResponse.
     */
    public function encodeInitResponseOptions(array $options = []): string
    {
        $defaults = [
            'search'           => true,
            'present'          => true,
            'delSet'           => true,
            'namedResultsSets' => false,
        ];
        $opts = array_merge($defaults, $options);

        $bits = 0;
        if (!empty($opts['search'])) {
            $bits |= 1 << 0;
        }
        if (!empty($opts['present'])) {
            $bits |= 1 << 1;
        }
        if (!empty($opts['delSet'])) {
            $bits |= 1 << 2;
        }
        if (!empty($opts['namedResultsSets'])) {
            $bits |= 1 << 8;
        }

        // BIT STRING: tag 0x03, len 5, body (4 unused octets + flags)
        $bitStringBody = "\x00\x00\x00\x00" . chr($bits);
        $bitString = "\x03\x05" . $bitStringBody;

        $prefSyntax = $this->encodeOidSequence([1, 2, 840, 10003, 5, 1]);

        return $this->encodeSequence($bitString . $prefSyntax);
    }

    public function encodeInitResponse(string $referenceId, int $result = 1): string
    {
        $content = '';
        $content .= $this->encodeOctet($referenceId);
        if (!$result) {
            $content .= "\x01\x01\x00";
        }
        // protocolVersion BIT STRING: 4 unused + flags 0x03
        $content .= "\x03\x05\x00\x00\x00\x03";
        $content .= $this->encodeInitResponseOptions();
        $content .= $this->encodeOidSequence([1, 2, 840, 10003, 5, 1]);
        $content .= $this->encodeVisible('PSIS');
        $content .= $this->encodeVisible('PSIS Z39.50 Server');
        $content .= $this->encodeVisible('1.0');

        $apdu = $this->encodeOidSequence(self::OID_INIT_RESPONSE)
              . $this->encodeSequence($content);

        return $this->wrapInPackageHeader($apdu);
    }

    public function encodeSearchResponse(
        string $referenceId,
        int $resultCount,
        string $resultSetId = 'default',
        string $records = '',
        int $nextResultSetPosition = 0
    ): string {
        $content = '';
        $content .= $this->encodeOctet($referenceId);
        $content .= "\x01\x01\xff";
        $content .= $this->encodeInteger($resultCount);
        $content .= $this->encodeInteger(substr_count($records, "\x1d") ?: 0);
        $content .= $this->encodeInteger($nextResultSetPosition ?: 0);
        $content .= $this->encodeVisible($resultSetId ?: 'default');
        $content .= $this->encodeSequence('');

        $apdu = $this->encodeOidSequence(self::OID_SEARCH_RESPONSE)
              . $this->encodeSequence($content);

        if ($records !== '') {
            $apdu .= $records;
        }

        return $this->wrapInPackageHeader($apdu);
    }

    public function encodePresentResponse(
        string $referenceId,
        int $nextPosition,
        int $numberReturned,
        string $records = '',
        int $resultSetStatus = 0
    ): string {
        $content = '';
        $content .= $this->encodeOctet($referenceId);
        $content .= "\x01\x01\xff";
        $content .= $this->encodeInteger($nextPosition);
        $content .= $this->encodeInteger($numberReturned);
        $content .= $this->encodeInteger($resultSetStatus);
        $content .= $this->encodeSequence('');

        $recordContent = '';
        if (strlen($records) > 0) {
            $recordContent .= $this->encodeOctet($records);
        }
        $content .= $this->encodeSequence($recordContent);

        $apdu = $this->encodeOidSequence(self::OID_PRESENT_RESPONSE)
              . $this->encodeSequence($content);

        return $this->wrapInPackageHeader($apdu);
    }

    public function encodeClose(string $referenceId, int $closeStatus = 0): string
    {
        $content = '';
        $content .= $this->encodeOctet($referenceId);
        $content .= $this->encodeInteger($closeStatus);

        $apdu = $this->encodeOidSequence(self::OID_CLOSE)
              . $this->encodeSequence($content);

        return $this->wrapInPackageHeader($apdu);
    }

    public function encodeDeleteResultSetResponse(string $referenceId, int $deleteStatus = 0): string
    {
        $content = '';
        $content .= $this->encodeOctet($referenceId);
        $content .= $this->encodeInteger($deleteStatus);

        $apdu = $this->encodeOidSequence(self::OID_DELETE_RESULT_SET)
              . $this->encodeSequence($content);

        return $this->wrapInPackageHeader($apdu);
    }

    // ──── BER decoding ─────────────────────────────────────────────────────

    public function decodeSearchRequest(string $body): array
    {
        $result = [
            'referenceId'    => '',
            'query'          => '',
            'resultSetName'  => 'default',
            'maxRecords'     => 0,
            'recordSyntax'   => '',
            'elementSetName' => '',
        ];

        $pos = 0;
        $len = strlen($body);

        while ($pos < $len) {
            $tag = ord($body[$pos]);
            [$lenBytes, $length] = $this->decodeLengthRet($body, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($body, $valStart, $length);

            if ($tag === self::TAG_OCTET) {
                if ($result['referenceId'] === '') {
                    $result['referenceId'] = $value;
                } else {
                    $result['query'] = $value;
                }
            } elseif ($tag === self::TAG_SEQUENCE) {
                $this->decodeSearchRequestInner($value, $result);
            } elseif ($tag === self::TAG_INTEGER) {
                $result['maxRecords'] = $this->decodeIntegerValue($value);
            } elseif (in_array($tag, [self::TAG_VISIBLE, self::TAG_IA5STR, self::TAG_UTF8STR], true)) {
                if ($result['elementSetName'] === '' && strlen($value) <= 10) {
                    $result['elementSetName'] = $value;
                }
            } elseif ($tag === self::TAG_OID) {
                $result['recordSyntax'] = implode('.', $this->decodeOidValue($value));
            }

            $pos = $valStart + $length;
        }

        return $result;
    }

    private function decodeSearchRequestInner(string $content, array &$result): void
    {
        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            $tag = ord($content[$pos]);
            [$lenBytes, $length] = $this->decodeLengthRet($content, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($content, $valStart, $length);

            if ($tag === self::TAG_OCTET) {
                $result['query'] = $value;
            } elseif ($tag === self::TAG_VISIBLE) {
                if ($result['resultSetName'] === 'default') {
                    $result['resultSetName'] = $value;
                }
            } elseif ($tag === self::TAG_INTEGER) {
                if ($result['maxRecords'] === 0) {
                    $result['maxRecords'] = $this->decodeIntegerValue($value);
                }
            }

            $pos = $valStart + $length;
        }
    }

    public function decodeInitRequest(string $body): array
    {
        $result = [
            'referenceId'           => '',
            'options'               => 0,
            'preferredRecordSyntax' => '',
            'implementationId'      => '',
            'implementationName'    => '',
            'implementationVersion' => '',
        ];

        $pos = 0;
        $len = strlen($body);

        while ($pos < $len) {
            $tag = ord($body[$pos]);
            [$lenBytes, $length] = $this->decodeLengthRet($body, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($body, $valStart, $length);

            if ($tag === self::TAG_OCTET) {
                if ($result['referenceId'] === '') {
                    $result['referenceId'] = $value;
                }
            } elseif ($tag === self::TAG_SEQUENCE) {
                $this->decodeInitRequestInner($value, $result);
            }

            $pos = $valStart + $length;
        }

        return $result;
    }

    private function decodeInitRequestInner(string $content, array &$result): void
    {
        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            $tag = ord($content[$pos]);
            [$lenBytes, $length] = $this->decodeLengthRet($content, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($content, $valStart, $length);

            if ($tag === self::TAG_OCTET) {
                if (strlen($value) >= 1) {
                    $result['options'] = ord($value[strlen($value) - 1]);
                }
            } elseif ($tag === self::TAG_OID) {
                $result['preferredRecordSyntax'] = implode('.', $this->decodeOidValue($value));
            } elseif (in_array($tag, [self::TAG_VISIBLE, self::TAG_UTF8STR], true)) {
                if ($result['implementationId'] === '') {
                    $result['implementationId'] = $value;
                } elseif ($result['implementationName'] === '') {
                    $result['implementationName'] = $value;
                } else {
                    $result['implementationVersion'] = $value;
                }
            }

            $pos = $valStart + $length;
        }
    }

    public function decodePresentRequest(string $body): array
    {
        $result = [
            'referenceId'         => '',
            'resultSetId'         => 'default',
            'resultSetStartPoint' => 1,
            'maxRecords'          => 0,
            'recordSyntax'        => '',
            'elementSetNames'     => '',
        ];

        $pos = 0;
        $len = strlen($body);

        while ($pos < $len) {
            $tag = ord($body[$pos]);
            [$lenBytes, $length] = $this->decodeLengthRet($body, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($body, $valStart, $length);

            if ($tag === self::TAG_OCTET) {
                if ($result['referenceId'] === '') {
                    $result['referenceId'] = $value;
                }
            } elseif ($tag === self::TAG_SEQUENCE) {
                $this->decodePresentRequestInner($value, $result);
            } elseif ($tag === self::TAG_INTEGER) {
                $result['resultSetStartPoint'] = $this->decodeIntegerValue($value);
            }

            $pos = $valStart + $length;
        }

        return $result;
    }

    private function decodePresentRequestInner(string $content, array &$result): void
    {
        $pos = 0;
        $len = strlen($content);

        while ($pos < $len) {
            $tag = ord($content[$pos]);
            [$lenBytes, $length] = $this->decodeLengthRet($content, $pos + 1);
            if ($lenBytes === 0) {
                break;
            }
            $valStart = $pos + 1 + $lenBytes;
            $value = substr($content, $valStart, $length);

            if ($tag === self::TAG_VISIBLE || $tag === self::TAG_UTF8STR) {
                if ($result['resultSetId'] === 'default') {
                    $result['resultSetId'] = $value;
                } elseif ($result['elementSetNames'] === '') {
                    $result['elementSetNames'] = $value;
                }
            } elseif ($tag === self::TAG_INTEGER) {
                if ($result['maxRecords'] === 0) {
                    $result['maxRecords'] = $this->decodeIntegerValue($value);
                }
            }

            $pos = $valStart + $length;
        }
    }

    // ──── Core encoding helpers ────────────────────────────────────────────

    /**
     * Encode an INTEGER body as BER DER bytes (two's complement).
     */
    public function encodeIntegerValue(int $value): string
    {
        if ($value === 0) {
            return "\x00";
        }

        $bytes = [];

        if ($value > 0) {
            $v = $value;
            while ($v !== 0) {
                $bytes[] = chr($v & 0xff);
                $v >>= 8;
            }
            $bytes = array_reverse($bytes);

            if (ord($bytes[0]) & 0x80) {
                array_unshift($bytes, "\x00");
            }
        } else {
            $v = $value;
            do {
                $bytes[] = chr($v & 0xff);
                $v >>= 8;
            } while ($v !== -1);
            $bytes = array_reverse($bytes);

            if (count($bytes) > 1 && ord($bytes[0]) === 0xff && (ord($bytes[1]) & 0x80)) {
                array_shift($bytes);
            } elseif (!(ord($bytes[0]) & 0x80)) {
                array_unshift($bytes, "\xff");
            }
        }

        return implode('', $bytes);
    }

    /**
     * Encode an OID as BER DER bytes (base-128, ITU-T X.690).
     */
    public function encodeOidValue(array $oid): string
    {
        if (count($oid) < 2) {
            return '';
        }

        $bytes = [];

        $first = ($oid[0] * 40) + ($oid[1] ?? 0);
        $bytes[] = chr($first);

        $count = count($oid);
        for ($i = 2; $i < $count; ++$i) {
            $val = (int) $oid[$i];
            $chunk = [];

            while ($val > 0) {
                $chunk[] = chr($val & 0x7f);
                $val >>= 7;
            }

            if (empty($chunk)) {
                $chunk[] = "\x00";
            }

            $chunkCount = count($chunk);
            for ($j = 1; $j < $chunkCount; ++$j) {
                $chunk[$j] = chr(ord($chunk[$j]) | 0x80);
            }

            $bytes = array_merge($bytes, array_reverse($chunk));
        }

        return implode('', $bytes);
    }

    /**
     * Encode tag + length header bytes.
     */
    public function encodeTagLength(int $tag, string $body): string
    {
        $len = strlen($body);
        if ($len < 128) {
            return chr($tag) . chr($len);
        }

        $lenBytes = [];
        while ($len > 0) {
            array_unshift($lenBytes, $len & 0xff);
            $len >>= 8;
        }

        $first = 0x80 | count($lenBytes);

        return chr($tag) . chr($first) . implode('', array_map('chr', $lenBytes));
    }

    // ──── Core decoding helpers ────────────────────────────────────────────

    /**
     * Decode a BER length field. Returns [lenBytes, length].
     */
    public function decodeLengthRet(string $s, int $offset): array
    {
        if ($offset >= strlen($s)) {
            return [0, 0];
        }

        $first = ord($s[$offset]);

        if (($first & 0x80) === 0) {
            return [1, $first];
        }

        $numBytes = $first & 0x7f;
        if ($numBytes === 0 || $offset + $numBytes >= strlen($s)) {
            return [1, 0];
        }

        $length = 0;
        for ($i = 1; $i <= $numBytes; ++$i) {
            $length = ($length << 8) | ord($s[$offset + $i]);
        }

        return [1 + $numBytes, $length];
    }

    /**
     * Decode a BER INTEGER body to a PHP int.
     */
    public function decodeIntegerValue(string $body): int
    {
        if ($body === '') {
            return 0;
        }

        $value = 0;
        $len = strlen($body);

        for ($i = 0; $i < $len; ++$i) {
            $value = ($value << 8) | ord($body[$i]);
        }

        if (ord($body[0]) & 0x80) {
            $bitLen = $len * 8;
            $mask = ~0 << $bitLen;
            $value = $mask | $value;
        }

        return $value;
    }

    /**
     * Decode a BER OID body to an array of integers.
     */
    public function decodeOidValue(string $body): array
    {
        if ($body === '') {
            return [];
        }

        $arcs = [];
        $value = 0;
        $len = strlen($body);
        $first = true;

        for ($i = 0; $i < $len; ++$i) {
            $byte = ord($body[$i]);

            if ($byte & 0x80) {
                $value = ($value << 7) | ($byte & 0x7f);
            } else {
                $value = ($value << 7) | $byte;
                if ($first) {
                    $arc0 = intdiv($value, 40);
                    if ($arc0 > 2) {
                        $arc0 = 2;
                    }
                    $arcs[] = $arc0;
                    $arcs[] = $value - ($arc0 * 40);
                    $first = false;
                } else {
                    $arcs[] = $value;
                }
                $value = 0;
            }
        }

        return $arcs;
    }

    // ──── Package header (Z39.50 connection layer) ─────────────────────────

    /**
     * Length in bytes of the Z39.50 framing header written by
     * wrapInPackageHeader(): uint16 size + uint8 id + uint8 flags + uint16
     * reserved = 6 bytes. The size field counts the whole package (header +
     * APDU payload).
     */
    public const PACKAGE_HEADER_LEN = 6;

    /**
     * Wrap an APDU in the Z39.50 package header.
     */
    public function wrapInPackageHeader(string $apdu): string
    {
        $size = strlen($apdu) + self::PACKAGE_HEADER_LEN;

        return pack('nCCn', $size, 0x00, 0x00, 0x0000) . $apdu;
    }

    /**
     * Strip the Z39.50 package header to recover the APDU bytes.
     */
    public function unwrapPackageHeader(string $packet): string
    {
        if (strlen($packet) < self::PACKAGE_HEADER_LEN) {
            return '';
        }

        return substr($packet, self::PACKAGE_HEADER_LEN);
    }
}
