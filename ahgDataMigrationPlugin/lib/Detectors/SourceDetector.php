<?php
namespace ahgDataMigrationPlugin\Detectors;

class SourceDetector
{
    protected static $signatures = [
        'archivesspace' => [
            'columns' => ['resource_id', 'ref_id', 'component_id'],
            'xml_root' => 'archival_objects',
        ],
        'vernon' => [
            'columns' => ['object_number', 'object_name', 'primary_maker'],
            'xml_root' => 'vernon',
        ],
        'collectiveaccess' => [
            'columns' => ['ca_objects', 'idno', 'type_id'],
            'xml_root' => 'collectiveaccess',
        ],
        'pastperfect' => [
            'columns' => ['objectid', 'objname', 'cat', 'subcat'],
            'xml_root' => 'export',
        ],
        'ead' => [
            'xml_root' => 'ead',
            'namespaces' => ['urn:isbn:1-931666-22-9'],
        ],
        'ead3' => [
            'xml_root' => 'ead',
            'namespaces' => ['http://ead3.archivists.org/schema/'],
        ],
        'dc' => [
            'xml_root' => 'metadata',
            'namespaces' => ['http://purl.org/dc/elements/1.1/'],
        ],
        'marc' => [
            'xml_root' => 'collection',
            'namespaces' => ['http://www.loc.gov/MARC21/slim'],
        ],
        'atom_csv' => [
            'columns' => ['legacyId', 'parentId', 'identifier', 'title', 'levelOfDescription'],
        ],
    ];

    protected static $sourceNames = [
        'archivesspace' => 'ArchivesSpace',
        'vernon' => 'Vernon CMS',
        'collectiveaccess' => 'CollectiveAccess',
        'pastperfect' => 'PastPerfect',
        'ead' => 'EAD 2002',
        'ead3' => 'EAD3',
        'dc' => 'Dublin Core XML',
        'marc' => 'MARC XML',
        'atom_csv' => 'AtoM CSV',
        'generic_csv' => 'Generic CSV',
    ];

    public static function detect(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (in_array($extension, ['csv', 'tsv', 'txt'])) {
            return self::detectCsv($filePath);
        }
        
        if (in_array($extension, ['xml', 'ead'])) {
            return self::detectXml($filePath);
        }
        
        return [
            'detected' => false,
            'source_type' => 'unknown',
            'source_name' => 'Unknown Format',
            'confidence' => 0,
            'file_type' => $extension,
        ];
    }

    protected static function detectCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['detected' => false, 'error' => 'Cannot read file'];
        }
        
        $delimiter = self::detectDelimiter($filePath);
        $headers = fgetcsv($handle, 0, $delimiter);
        fclose($handle);
        
        if (!$headers) {
            return ['detected' => false, 'error' => 'No headers found'];
        }
        
        $headers = array_map('strtolower', array_map('trim', $headers));
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach (self::$signatures as $source => $sig) {
            if (!isset($sig['columns'])) continue;
            
            $matches = 0;
            foreach ($sig['columns'] as $col) {
                if (in_array(strtolower($col), $headers)) {
                    $matches++;
                }
            }
            
            $score = $matches / count($sig['columns']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $source;
            }
        }
        
        if ($bestScore >= 0.5) {
            return [
                'detected' => true,
                'source_type' => $bestMatch,
                'source_name' => self::$sourceNames[$bestMatch],
                'confidence' => round($bestScore * 100),
                'file_type' => 'csv',
                'delimiter' => $delimiter,
                'headers' => $headers,
                'row_count' => self::countRows($filePath, $delimiter),
            ];
        }
        
        return [
            'detected' => true,
            'source_type' => 'generic_csv',
            'source_name' => 'Generic CSV',
            'confidence' => 100,
            'file_type' => 'csv',
            'delimiter' => $delimiter,
            'headers' => $headers,
            'row_count' => self::countRows($filePath, $delimiter),
        ];
    }

    protected static function detectXml(string $filePath): array
    {
        $content = file_get_contents($filePath, false, null, 0, 4096);
        
        foreach (self::$signatures as $source => $sig) {
            if (!isset($sig['xml_root'])) continue;
            
            if (preg_match('/<' . $sig['xml_root'] . '[\s>]/i', $content)) {
                $confidence = 70;
                
                if (isset($sig['namespaces'])) {
                    foreach ($sig['namespaces'] as $ns) {
                        if (strpos($content, $ns) !== false) {
                            $confidence = 95;
                            break;
                        }
                    }
                }
                
                return [
                    'detected' => true,
                    'source_type' => $source,
                    'source_name' => self::$sourceNames[$source],
                    'confidence' => $confidence,
                    'file_type' => 'xml',
                ];
            }
        }
        
        return [
            'detected' => false,
            'source_type' => 'unknown_xml',
            'source_name' => 'Unknown XML',
            'confidence' => 0,
            'file_type' => 'xml',
        ];
    }

    protected static function detectDelimiter(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        $line = fgets($handle);
        fclose($handle);
        
        $delimiters = [',', "\t", ';', '|'];
        $counts = [];
        
        foreach ($delimiters as $d) {
            $counts[$d] = substr_count($line, $d);
        }
        
        return array_search(max($counts), $counts);
    }

    protected static function countRows(string $filePath, string $delimiter): int
    {
        $count = 0;
        $handle = fopen($filePath, 'r');
        while (fgetcsv($handle, 0, $delimiter)) {
            $count++;
        }
        fclose($handle);
        return max(0, $count - 1);
    }

    public static function getSupportedSources(): array
    {
        return self::$sourceNames;
    }
}
