<?php

declare(strict_types=1);

/**
 * Marc21DecoderService
 *
 * ISO 2709 / MARC21 binary decoder. Pure parser — no database, no framework
 * dependencies. Produces the same logical structure as a MARCXML <record>
 * (leader / control fields / data fields) so MarcService can map a decoded
 * record through exactly the same column/creator/subject logic it already
 * uses for MARCXML import (single source of truth — see
 * MarcService::parseDecodedRecord()).
 *
 * Ported from the Heratio (Laravel) AhgLibrary\Services\Marc21DecoderService.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */
class Marc21DecoderService
{
    /** MARC record terminator (end of each record in a .mrc stream). */
    private const RECORD_TERMINATOR = "\x1D";

    /** MARC field terminator. */
    private const FIELD_TERMINATOR = "\x1E";

    /** MARC subfield delimiter. */
    private const SUBFIELD_DELIMITER = "\x1F";

    /**
     * Detect the MARC syntax of raw bytes.
     *
     * @return string 'marcxml' | 'marc21' | 'unknown'
     */
    public function detectSyntax(string $raw): string
    {
        if (str_contains($raw, '<?xml') || str_contains($raw, '<record') || str_contains($raw, ':record')) {
            return 'marcxml';
        }
        // Field terminator within the first directory entries is a strong marker.
        if (isset($raw[9]) && ord($raw[9]) === 0x1D) {
            return 'marc21';
        }
        // A valid leader has digit base-address in bytes 12-16.
        if (strlen($raw) >= 24 && ctype_digit(substr($raw, 12, 5))) {
            return 'marc21';
        }
        return 'unknown';
    }

    /**
     * Split a raw multi-record ISO 2709 stream into individual records.
     *
     * @return string[] Each element is one complete record (terminator stripped).
     */
    public function splitRecords(string $raw): array
    {
        $records = [];
        foreach (explode(self::RECORD_TERMINATOR, $raw) as $chunk) {
            // Trailing newline padding / final empty chunk after the last 0x1D.
            $chunk = trim($chunk, "\r\n");
            if (strlen($chunk) >= 24) {
                $records[] = $chunk;
            }
        }
        return $records;
    }

    /**
     * Decode a single binary MARC21 record (ISO 2709) into a structured array.
     *
     * Returned shape:
     *   [
     *     'leader'  => string (24 chars),
     *     'control' => ['001' => text, '008' => text, ...],
     *     'data'    => [
     *       ['tag' => '245', 'ind1' => '0', 'ind2' => '1',
     *        'subfields' => ['a' => 'Title', 'b' => 'subtitle', 'a2' => '...']],
     *       ...
     *     ],
     *   ]
     *
     * Repeatable subfields are suffixed (a, a2, a3, ...) — strip the numeric
     * suffix to recover the code.
     *
     * ISO 2709 layout: leader(24) + directory(12/entry, 0x1E-terminated) +
     * data area beginning at the base address (leader bytes 12-16).
     */
    public function decode(string $raw): array
    {
        if (strlen($raw) < 24) {
            return ['leader' => '', 'control' => [], 'data' => []];
        }

        $leader = substr($raw, 0, 24);

        $indicatorLen = (isset($leader[10]) && ctype_digit($leader[10])) ? (int) $leader[10] : 2;
        $baseAddress  = (int) substr($leader, 12, 5);

        $dirStart = 24;
        $dirEnd   = $baseAddress - 1; // the 0x1E that terminates the directory

        $control = [];
        $data    = [];

        $dpos = $dirStart;
        while ($dpos + 12 <= $dirEnd) {
            $tag   = substr($raw, $dpos, 3);
            $flen  = (int) substr($raw, $dpos + 3, 4);
            $start = (int) substr($raw, $dpos + 7, 5);
            $dpos += 12;

            if (!ctype_digit($tag)) {
                continue;
            }

            $dataStart = $baseAddress + $start;
            $dataEnd   = $dataStart + $flen; // flen includes the trailing 0x1E

            if ($dataEnd > strlen($raw)) {
                continue; // truncated record — skip this field
            }

            // Field data excluding the trailing field terminator.
            $rawField = rtrim(substr($raw, $dataStart, $flen), self::FIELD_TERMINATOR);

            if ((int) $tag <= 9) {
                // Control field (001-009): no indicators, no subfields.
                $control[sprintf('%03d', (int) $tag)] = $rawField;
                continue;
            }

            // Data field: 2 indicators then subfields.
            $ind1 = $rawField[0] ?? ' ';
            $ind2 = $rawField[1] ?? ' ';
            $rawSubfields = substr($rawField, $indicatorLen);

            $subfields = [];
            foreach (explode(self::SUBFIELD_DELIMITER, $rawSubfields) as $chunk) {
                if ($chunk === '') {
                    continue;
                }
                $code = $chunk[0];
                $val  = substr($chunk, 1);

                if (isset($subfields[$code])) {
                    $seq = 2;
                    while (isset($subfields[$code . $seq])) {
                        $seq++;
                    }
                    $subfields[$code . $seq] = $val;
                } else {
                    $subfields[$code] = $val;
                }
            }

            $data[] = [
                'tag'       => $tag,
                'ind1'      => $ind1,
                'ind2'      => $ind2,
                'subfields' => $subfields,
            ];
        }

        return [
            'leader'  => $leader,
            'control' => $control,
            'data'    => $data,
        ];
    }

    /**
     * Convenience accessor: first non-suffixed value of a subfield code.
     */
    public static function subfield(array $field, string $code): ?string
    {
        return $field['subfields'][$code] ?? null;
    }
}
