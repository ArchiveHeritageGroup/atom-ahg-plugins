<?php
namespace AhgMigration\Parsers;

class ParserFactory
{
    /**
     * Create parser based on format
     */
    public static function create(string $format): ParserInterface
    {
        return match(strtolower($format)) {
            'csv', 'tsv', 'txt' => new CsvParser(),
            'xml' => new XmlParser(),
            'ead' => new EadParser(),
            default => throw new \InvalidArgumentException("Unsupported format: $format")
        };
    }
    
    /**
     * Detect format from file
     */
    public static function detectFormat(string $filePath): array
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $result = [
            'format' => 'unknown',
            'source_system' => 'custom',
            'confidence' => 0
        ];
        
        // Check by extension first
        if (in_array($extension, ['csv', 'tsv', 'txt'])) {
            $result['format'] = 'csv';
            $result['confidence'] = 0.8;
            
            // Try to detect source system from headers
            $parser = new CsvParser();
            $sample = $parser->getSample($filePath, 1);
            if (!empty($sample['headers'])) {
                $result['headers'] = $sample['headers'];
                $result['source_system'] = self::detectSourceSystem($sample['headers']);
                if ($result['source_system'] !== 'custom') {
                    $result['confidence'] = 0.9;
                }
            }
            
        } elseif ($extension === 'xml') {
            // Read first 2KB to check content
            $content = file_get_contents($filePath, false, null, 0, 2000);
            
            // Check for EAD
            if (preg_match('/<ead|urn:isbn:1-931666-22-9|ead3\.archivists\.org/i', $content)) {
                $result['format'] = 'ead';
                $result['source_system'] = 'ead';
                $result['confidence'] = 0.95;
            } else {
                $result['format'] = 'xml';
                $result['confidence'] = 0.8;
                
                // Check for Vernon
                if (stripos($content, 'vernonsystems') !== false || preg_match('/<Object>|<Record>/i', $content)) {
                    $result['source_system'] = 'vernon';
                    $result['confidence'] = 0.9;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Detect source system from CSV headers
     */
    public static function detectSourceSystem(array $headers): string
    {
        $headerStr = strtolower(implode(' ', $headers));
        
        // Vernon CMS patterns
        if (preg_match('/object.?number|primary.?maker|vernon|accession.?number.*maker/i', $headerStr)) {
            return 'vernon';
        }
        
        // ArchivesSpace patterns
        if (preg_match('/res_uri|ao_uri|archivesspace|component_id.*level/i', $headerStr)) {
            return 'archivesspace';
        }
        
        // DB/TextWorks patterns
        if (preg_match('/scopenote|adminhistory|custodial|accession.*title.*dates/i', $headerStr)) {
            return 'dbtextworks';
        }
        
        // PastPerfect patterns
        if (preg_match('/objectid|objectname|cat.*date|lexicon/i', $headerStr)) {
            return 'pastperfect';
        }
        
        // CollectiveAccess patterns
        if (preg_match('/ca_objects|idno|preferred_labels/i', $headerStr)) {
            return 'collectiveaccess';
        }
        
        return 'custom';
    }
}
