<?php
namespace AhgMigration\Parsers;

class CsvParser implements ParserInterface
{
    protected array $headers = [];
    protected int $rowCount = 0;
    protected string $delimiter = ',';
    protected string $enclosure = '"';
    protected string $encoding = 'UTF-8';
    
    public function parse(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("Cannot open file: $filePath");
        }
        
        // Detect settings
        $this->detectEncoding($filePath);
        $this->detectDelimiter($filePath);
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        // Read headers
        $headerRow = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
        if ($headerRow === false) {
            fclose($handle);
            throw new \RuntimeException("Cannot read CSV headers");
        }
        $this->headers = array_map(fn($h) => trim($h), $headerRow);
        
        $rowNumber = 0;
        while (($row = fgetcsv($handle, 0, $this->delimiter, $this->enclosure)) !== false) {
            $rowNumber++;
            
            // Skip empty rows
            if (count(array_filter($row, fn($v) => $v !== '')) === 0) {
                continue;
            }
            
            // Build associative array
            $data = [];
            foreach ($this->headers as $i => $header) {
                $value = $row[$i] ?? '';
                
                // Convert encoding if needed
                if ($this->encoding !== 'UTF-8' && $value !== '') {
                    $value = mb_convert_encoding($value, 'UTF-8', $this->encoding);
                }
                
                $data[$header] = trim($value);
            }
            
            yield [
                'row_number' => $rowNumber,
                'data' => $data
            ];
        }
        
        $this->rowCount = $rowNumber;
        fclose($handle);
    }
    
    public function getHeaders(): array
    {
        return $this->headers;
    }
    
    public function getRowCount(): int
    {
        return $this->rowCount;
    }
    
    public function getFormat(): string
    {
        return 'csv';
    }
    
    public function validate(string $filePath): array
    {
        $errors = [];
        
        if (!file_exists($filePath)) {
            return ["File not found: $filePath"];
        }
        
        if (!is_readable($filePath)) {
            return ["File is not readable: $filePath"];
        }
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ["Cannot open file"];
        }
        
        // Skip BOM
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        $this->detectDelimiter($filePath);
        $headerRow = fgetcsv($handle, 0, $this->delimiter, $this->enclosure);
        
        if (!$headerRow || count($headerRow) < 2) {
            $errors[] = "Invalid CSV: No headers or insufficient columns";
        } else {
            // Check for empty headers
            $emptyHeaders = array_filter($headerRow, fn($h) => trim($h) === '');
            if (!empty($emptyHeaders)) {
                $errors[] = "CSV contains empty column headers";
            }
            
            // Check for duplicate headers
            $trimmed = array_map('trim', $headerRow);
            $duplicates = array_diff_assoc($trimmed, array_unique($trimmed));
            if (!empty($duplicates)) {
                $errors[] = "Duplicate column headers: " . implode(', ', array_unique($duplicates));
            }
        }
        
        fclose($handle);
        return $errors;
    }
    
    public function getSample(string $filePath, int $count = 5): array
    {
        $samples = [];
        $i = 0;
        
        foreach ($this->parse($filePath) as $record) {
            $samples[] = $record;
            $i++;
            if ($i >= $count) {
                break;
            }
        }
        
        return [
            'headers' => $this->headers,
            'records' => $samples,
            'format' => 'csv',
            'delimiter' => $this->delimiter,
            'encoding' => $this->encoding
        ];
    }
    
    protected function detectDelimiter(string $filePath): void
    {
        $handle = fopen($filePath, 'r');
        $sample = fread($handle, 10000);
        fclose($handle);
        
        $delimiters = [
            ',' => substr_count($sample, ','),
            "\t" => substr_count($sample, "\t"),
            ';' => substr_count($sample, ';'),
            '|' => substr_count($sample, '|')
        ];
        
        $this->delimiter = array_search(max($delimiters), $delimiters);
    }
    
    protected function detectEncoding(string $filePath): void
    {
        $sample = file_get_contents($filePath, false, null, 0, 10000);
        
        // Check for BOM markers
        if (substr($sample, 0, 3) === "\xEF\xBB\xBF") {
            $this->encoding = 'UTF-8';
            return;
        }
        if (substr($sample, 0, 2) === "\xFF\xFE") {
            $this->encoding = 'UTF-16LE';
            return;
        }
        if (substr($sample, 0, 2) === "\xFE\xFF") {
            $this->encoding = 'UTF-16BE';
            return;
        }
        
        // Try to detect
        $detected = mb_detect_encoding($sample, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
        $this->encoding = $detected ?: 'UTF-8';
    }
    
    // Setters for manual override
    public function setDelimiter(string $d): self { $this->delimiter = $d; return $this; }
    public function setEnclosure(string $e): self { $this->enclosure = $e; return $this; }
    public function setEncoding(string $e): self { $this->encoding = $e; return $this; }
}
