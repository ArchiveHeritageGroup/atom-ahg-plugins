<?php

/**
 * Iso2709Serializer - MARCXML to MARC21 binary (ISO 2709).
 *
 * Marc21Exporter already produces correct MARCXML. This turns that into the
 * binary exchange format library systems actually ingest, so there is one set of
 * field-mapping rules rather than two implementations that drift apart.
 *
 * It works on any conformant MARCXML, not just ours, so records that arrive from
 * elsewhere can be converted without going back through the exporter.
 *
 * @see https://www.loc.gov/marc/specifications/specrecstruc.html
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Serializers
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Serializers;

class Iso2709Serializer
{
    /** ISO 2709 delimiters. */
    public const FIELD_TERMINATOR = "\x1E";
    public const RECORD_TERMINATOR = "\x1D";
    public const SUBFIELD_DELIMITER = "\x1F";

    /** A leader is 24 bytes and a directory entry is 12. Both are fixed by the standard. */
    public const LEADER_LENGTH = 24;
    public const DIRECTORY_ENTRY_LENGTH = 12;

    /**
     * The record length and base address are 5-digit fields, so a record cannot
     * exceed 99999 bytes. Longer records must be split by the caller.
     */
    public const MAX_RECORD_LENGTH = 99999;

    public const NS_MARC = 'http://www.loc.gov/MARC21/slim';

    /**
     * Convert MARCXML to one or more binary MARC21 records.
     *
     * @param string $xml MARCXML: a single <record> or a <collection> of them
     *
     * @throws \InvalidArgumentException on unparseable XML or no records
     * @throws \RuntimeException         if a record exceeds the ISO 2709 size limit
     *
     * @return string concatenated binary records
     */
    public function fromMarcXml(string $xml): string
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();

        // MARCXML carries no entities; refusing them costs nothing and keeps a
        // hostile document from reading local files during conversion.
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOENT);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            $first = $errors ? trim($errors[0]->message) : 'unknown parse error';

            throw new \InvalidArgumentException('Could not parse MARCXML: '.$first);
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('marc', self::NS_MARC);

        // Namespace-qualified first, then bare, since plenty of MARCXML in the
        // wild omits the namespace entirely.
        $records = $xpath->query('//marc:record');
        if (0 === $records->length) {
            $records = $xpath->query('//record');
        }

        if (0 === $records->length) {
            throw new \InvalidArgumentException('No <record> elements found in MARCXML.');
        }

        $out = '';
        foreach ($records as $record) {
            $out .= $this->serializeRecord($record);
        }

        return $out;
    }

    /**
     * Serialize one <record> element to a binary MARC21 record.
     */
    public function serializeRecord(\DOMElement $record): string
    {
        $leader = $this->extractLeader($record);
        $fields = $this->extractFields($record);

        $directory = '';
        $data = '';
        $position = 0;

        foreach ($fields as [$tag, $content]) {
            // Lengths are byte counts, not character counts: a UTF-8 record whose
            // lengths were measured in characters is silently corrupt, and readers
            // fail on the field after the first non-ASCII one.
            $length = strlen($content);

            $directory .= sprintf(
                '%s%04d%05d',
                substr(str_pad($tag, 3, '0', STR_PAD_LEFT), 0, 3),
                $length,
                $position
            );

            $data .= $content;
            $position += $length;
        }

        // Directory ends with a field terminator, then the data area begins.
        $baseAddress = self::LEADER_LENGTH + strlen($directory) + 1;
        $recordLength = $baseAddress + strlen($data) + 1;

        if ($recordLength > self::MAX_RECORD_LENGTH) {
            throw new \RuntimeException(sprintf(
                'MARC21 record is %d bytes, over the ISO 2709 limit of %d. Split it or export MARCXML instead.',
                $recordLength,
                self::MAX_RECORD_LENGTH
            ));
        }

        // The exporter leaves 00-04 and 12-16 as zeroes because only now, with the
        // directory built, are they knowable.
        $leader = sprintf('%05d', $recordLength).substr($leader, 5, 7)
            .sprintf('%05d', $baseAddress).substr($leader, 17, 7);

        return $leader.$directory.self::FIELD_TERMINATOR.$data.self::RECORD_TERMINATOR;
    }

    /**
     * Pull the leader, padded or truncated to exactly 24 bytes.
     *
     * A short leader would shift every directory offset, so a malformed one is
     * replaced rather than patched.
     */
    protected function extractLeader(\DOMElement $record): string
    {
        $nodes = $record->getElementsByTagNameNS(self::NS_MARC, 'leader');
        if (0 === $nodes->length) {
            $nodes = $record->getElementsByTagName('leader');
        }

        $leader = $nodes->length ? $nodes->item(0)->textContent : '';

        if (self::LEADER_LENGTH !== strlen($leader)) {
            $leader = $this->defaultLeader();
        }

        return $leader;
    }

    /**
     * A minimal valid leader: new record, language material, monograph,
     * UTF-8 coded, standard indicator and subfield counts.
     */
    protected function defaultLeader(): string
    {
        return '00000nam a2200000 u 4500';
    }

    /**
     * Extract control and data fields in document order.
     *
     * @return array<int, array{0: string, 1: string}> [tag, encoded content]
     */
    protected function extractFields(\DOMElement $record): array
    {
        $fields = [];

        foreach ($record->childNodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $name = $node->localName;

            if ('controlfield' === $name) {
                $tag = $node->getAttribute('tag');
                if ('' === $tag) {
                    continue;
                }

                // No indicators and no subfields in a control field.
                $fields[] = [$tag, $node->textContent.self::FIELD_TERMINATOR];

                continue;
            }

            if ('datafield' === $name) {
                $tag = $node->getAttribute('tag');
                if ('' === $tag) {
                    continue;
                }

                $fields[] = [$tag, $this->encodeDataField($node)];
            }
        }

        return $fields;
    }

    /**
     * Encode a data field: two indicators, then delimiter-prefixed subfields.
     */
    protected function encodeDataField(\DOMElement $field): string
    {
        $content = $this->indicator($field, 'ind1').$this->indicator($field, 'ind2');

        foreach ($field->childNodes as $node) {
            if (!$node instanceof \DOMElement || 'subfield' !== $node->localName) {
                continue;
            }

            $code = $node->getAttribute('code');
            if ('' === $code) {
                continue;
            }

            // A subfield code is a single character by definition.
            $content .= self::SUBFIELD_DELIMITER.substr($code, 0, 1).$node->textContent;
        }

        return $content.self::FIELD_TERMINATOR;
    }

    /**
     * An indicator is exactly one byte; blank is a space, never an empty string.
     */
    protected function indicator(\DOMElement $field, string $name): string
    {
        $value = $field->getAttribute($name);

        return '' === $value ? ' ' : substr($value, 0, 1);
    }
}
