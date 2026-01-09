<?php
namespace AhgMigration\Parsers;

interface ParserInterface
{
    /**
     * Parse file and yield records
     */
    public function parse(string $filePath): \Generator;
    
    /**
     * Get detected headers/field names
     */
    public function getHeaders(): array;
    
    /**
     * Get total row count after parsing
     */
    public function getRowCount(): int;
    
    /**
     * Get format identifier
     */
    public function getFormat(): string;
    
    /**
     * Validate file before parsing
     */
    public function validate(string $filePath): array;
    
    /**
     * Get sample records for preview
     */
    public function getSample(string $filePath, int $count = 5): array;
}
