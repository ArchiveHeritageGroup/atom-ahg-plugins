<?php

/**
 * Marc21BinaryExporter - MARC21 in the binary exchange format (ISO 2709, .mrc).
 *
 * Deliberately thin: it inherits every field-mapping decision from
 * Marc21Exporter and only changes the serialisation, so the two formats can
 * never disagree about what a record contains.
 *
 * @see https://www.loc.gov/marc/specifications/specrecstruc.html
 *
 * @package    ahgMetadataExportPlugin
 * @subpackage Exporters
 * @author     The Archive and Heritage Group (Pty) Ltd
 */

namespace AhgMetadataExport\Exporters;

use AhgMetadataExport\Serializers\Iso2709Serializer;

class Marc21BinaryExporter extends Marc21Exporter
{
    public function getFormat(): string
    {
        return 'marc21-binary';
    }

    public function getFormatName(): string
    {
        return 'MARC21 (binary .mrc)';
    }

    public function getMimeType(): string
    {
        return 'application/marc';
    }

    public function getFileExtension(): string
    {
        return 'mrc';
    }

    /**
     * {@inheritdoc}
     *
     * Builds the same MARCXML the parent would, then converts it.
     */
    public function export($resource, array $options = []): string
    {
        return (new Iso2709Serializer())->fromMarcXml(parent::export($resource, $options));
    }

    /**
     * {@inheritdoc}
     *
     * Binary MARC21 has no document wrapper: records are simply concatenated,
     * each ending in its own record terminator. So a batch is the records run
     * together, with no header, footer or separator.
     */
    public function exportBatch(array $resources, array $options = []): \Generator
    {
        foreach ($resources as $resource) {
            yield $this->export($resource, $options);
        }
    }
}
