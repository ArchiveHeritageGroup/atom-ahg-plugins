<?php

namespace ahgDataMigrationPlugin\Parsers;

/**
 * Factory for creating the appropriate parser based on detected source type.
 */
class ParserFactory
{
    /**
     * Create a parser for the given file and source type.
     */
    public static function create(string $filepath, string $sourceType): object
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        switch ($sourceType) {
            case 'preservica_opex':
                require_once __DIR__ . '/OpexParser.php';
                return new OpexParser($filepath);

            case 'preservica_xip':
                require_once __DIR__ . '/PaxParser.php';
                return new PaxParser($filepath);

            case 'ead':
            case 'ead3':
                require_once __DIR__ . '/EadParser.php';
                return new EadParser($filepath);

            case 'dc':
                require_once __DIR__ . '/DublinCoreParser.php';
                return new DublinCoreParser($filepath);

            case 'marc':
                require_once __DIR__ . '/MarcParser.php';
                return new MarcParser($filepath);

            case 'atom_csv':
            case 'archivesspace':
            case 'vernon':
            case 'collectiveaccess':
            case 'pastperfect':
            case 'generic_csv':
            default:
                // For CSV-based formats
                if (in_array($extension, ['csv', 'tsv', 'txt'])) {
                    require_once __DIR__ . '/CsvParser.php';
                    $delimiter = $extension === 'tsv' ? "\t" : ',';
                    return new CsvParser($filepath, $delimiter);
                }
                
                // For XML formats not specifically handled
                if (in_array($extension, ['xml'])) {
                    require_once __DIR__ . '/GenericXmlParser.php';
                    return new GenericXmlParser($filepath);
                }

                throw new \InvalidArgumentException("No parser available for source type: {$sourceType}");
        }
    }

    /**
     * Get available parsers.
     */
    public static function getAvailableParsers(): array
    {
        return [
            'csv' => ['CsvParser', 'CSV files'],
            'preservica_opex' => ['OpexParser', 'Preservica OPEX XML'],
            'preservica_xip' => ['PaxParser', 'Preservica PAX/XIP archives'],
            'ead' => ['EadParser', 'EAD 2002 XML'],
            'ead3' => ['EadParser', 'EAD3 XML'],
            'dc' => ['DublinCoreParser', 'Dublin Core XML'],
            'marc' => ['MarcParser', 'MARC XML'],
        ];
    }

    /**
     * Check if a parser exists for the given source type.
     */
    public static function hasParser(string $sourceType): bool
    {
        $available = [
            'preservica_opex', 'preservica_xip',
            'ead', 'ead3', 'dc', 'marc',
            'atom_csv', 'archivesspace', 'vernon',
            'collectiveaccess', 'pastperfect', 'generic_csv'
        ];

        return in_array($sourceType, $available);
    }
}
