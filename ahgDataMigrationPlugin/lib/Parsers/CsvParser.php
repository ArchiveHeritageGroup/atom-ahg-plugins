<?php
namespace ahgDataMigrationPlugin\Parsers;

class CsvParser
{
    protected $filepath;
    protected $delimiter;
    protected $headers = [];
    
    public function __construct(string $filepath, string $delimiter = ',')
    {
        $this->filepath = $filepath;
        $this->delimiter = $delimiter;
    }
    
    public function getHeaders(): array
    {
        if (empty($this->headers)) {
            $handle = fopen($this->filepath, 'r');
            $this->headers = fgetcsv($handle, 0, $this->delimiter);
            fclose($handle);
            $this->headers = array_map('trim', $this->headers);
        }
        return $this->headers;
    }
    
    public function parse(int $limit = 0): array
    {
        $rows = [];
        $handle = fopen($this->filepath, 'r');
        $headers = fgetcsv($handle, 0, $this->delimiter);
        $headers = array_map('trim', $headers);
        
        $count = 0;
        while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            if ($limit > 0 && $count >= $limit) break;
            
            $data = [];
            foreach ($headers as $i => $header) {
                $data[$header] = isset($row[$i]) ? trim($row[$i]) : '';
            }
            $rows[] = $data;
            $count++;
        }
        fclose($handle);
        
        return $rows;
    }
    
    public function parseWithMapping(array $mapping, int $limit = 0): array
    {
        $rows = $this->parse($limit);
        $mapped = [];
        
        foreach ($rows as $row) {
            $record = [];
            foreach ($mapping as $source => $target) {
                if (!empty($target) && isset($row[$source])) {
                    $record[$target] = $row[$source];
                }
            }
            if (!empty($record)) {
                $mapped[] = $record;
            }
        }
        
        return $mapped;
    }
}
