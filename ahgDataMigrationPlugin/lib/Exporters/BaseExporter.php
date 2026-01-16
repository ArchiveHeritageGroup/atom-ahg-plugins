<?php

namespace ahgDataMigrationPlugin\Exporters;

/**
 * Base class for sector-specific CSV exporters.
 */
abstract class BaseExporter
{
    protected array $data = [];
    protected array $errors = [];
    protected array $warnings = [];

    /**
     * Get the sector code.
     */
    abstract public function getSectorCode(): string;

    /**
     * Get the required CSV columns for AtoM import.
     */
    abstract public function getColumns(): array;

    /**
     * Map a transformed record to the sector-specific CSV format.
     */
    abstract public function mapRecord(array $record): array;

    /**
     * Set the data to export.
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Export to CSV string.
     */
    public function export(): string
    {
        $columns = $this->getColumns();
        $output = fopen('php://temp', 'r+');

        // Write header
        fputcsv($output, $columns);

        // Write data rows
        foreach ($this->data as $record) {
            $mapped = $this->mapRecord($record);
            $row = [];
            foreach ($columns as $col) {
                $row[] = $mapped[$col] ?? '';
            }
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Get export filename.
     */
    public function getFilename(string $baseName): string
    {
        return pathinfo($baseName, PATHINFO_FILENAME) . '_' . $this->getSectorCode() . '_import.csv';
    }

    public function getErrors(): array { return $this->errors; }
    public function getWarnings(): array { return $this->warnings; }
}
