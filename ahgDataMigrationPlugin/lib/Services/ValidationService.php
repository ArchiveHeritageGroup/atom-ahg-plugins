<?php

namespace ahgDataMigrationPlugin\Services;

use ahgDataMigrationPlugin\Validation\AhgBaseValidator;
use ahgDataMigrationPlugin\Validation\AhgDuplicateDetector;
use ahgDataMigrationPlugin\Validation\AhgReferentialValidator;
use ahgDataMigrationPlugin\Validation\AhgSchemaValidator;
use ahgDataMigrationPlugin\Validation\AhgValidationReport;
use ahgDataMigrationPlugin\Validation\AhgValidatorCollection;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Validation service for orchestrating CSV/data validation across sectors.
 *
 * Provides:
 * - Full validation with all validators
 * - Validate-only mode (no import)
 * - Sector-specific validation
 * - Validation logging to database
 * - Validation report generation
 */
class ValidationService
{
    protected ?AhgValidationReport $lastReport = null;
    protected string $sectorCode = '';
    protected array $options = [];

    /**
     * Default validation options.
     */
    protected array $defaultOptions = [
        'schema' => true,
        'referential' => true,
        'duplicates' => true,
        'sector' => true,
        'checkDatabase' => true,
        'maxHierarchyDepth' => 10,
        'duplicateStrategy' => AhgDuplicateDetector::STRATEGY_IDENTIFIER,
        'logToDatabase' => false,
        'jobId' => null,
    ];

    public function __construct(string $sectorCode = '', array $options = [])
    {
        $this->sectorCode = $sectorCode;
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Set the sector code.
     */
    public function setSector(string $sectorCode): self
    {
        $this->sectorCode = $sectorCode;

        return $this;
    }

    /**
     * Set validation options.
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Validate a CSV file.
     *
     * @param string                           $filepath Path to CSV file
     * @param array<string, string>            $mapping  Column mapping (source => target)
     * @param array<int, array<string, mixed>> $rows     Optional pre-parsed rows
     */
    public function validate(string $filepath, array $mapping = [], array $rows = []): AhgValidationReport
    {
        // Parse file if rows not provided
        if (empty($rows)) {
            $rows = $this->parseFile($filepath, $mapping);
        }

        // Create validator collection
        $collection = $this->createValidatorCollection(basename($filepath));

        // Run validation
        $this->lastReport = $collection->validateWithOptions($rows, $this->options);

        // Log to database if enabled
        if ($this->options['logToDatabase'] && null !== $this->options['jobId']) {
            $this->logValidationResults($this->lastReport, $this->options['jobId']);
        }

        return $this->lastReport;
    }

    /**
     * Validate without importing (validate-only mode).
     *
     * @param string                $filepath Path to CSV file
     * @param array<string, string> $mapping  Column mapping
     */
    public function validateOnly(string $filepath, array $mapping = []): AhgValidationReport
    {
        // Parse and validate
        $rows = $this->parseFile($filepath, $mapping);

        return $this->validate($filepath, $mapping, $rows);
    }

    /**
     * Parse a CSV file and apply column mapping.
     *
     * @param string                $filepath Path to CSV file
     * @param array<string, string> $mapping  Column mapping (source => target)
     *
     * @return array<int, array<string, mixed>>
     */
    protected function parseFile(string $filepath, array $mapping = []): array
    {
        $rows = [];

        if (!file_exists($filepath)) {
            throw new \RuntimeException("File not found: {$filepath}");
        }

        $handle = fopen($filepath, 'r');
        if (false === $handle) {
            throw new \RuntimeException("Could not open file: {$filepath}");
        }

        $header = fgetcsv($handle);
        if (false === $header) {
            fclose($handle);
            throw new \RuntimeException('Could not read header row');
        }

        // Clean header
        $header = array_map('trim', $header);

        $rowNumber = 2; // Start at 2 since header is row 1
        while (false !== ($row = fgetcsv($handle))) {
            // Combine with header
            $combined = [];
            foreach ($header as $i => $col) {
                $value = $row[$i] ?? '';
                $targetCol = $mapping[$col] ?? $col;
                $combined[$targetCol] = $value;

                // Also keep original column name for reference
                if ($targetCol !== $col) {
                    $combined['_orig_'.$col] = $value;
                }
            }

            $rows[$rowNumber] = $combined;
            ++$rowNumber;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Create a validator collection for the current sector.
     */
    protected function createValidatorCollection(string $filename = ''): AhgValidatorCollection
    {
        $collection = new AhgValidatorCollection($this->sectorCode, $filename);

        // Add schema validator if enabled
        if ($this->options['schema']) {
            $schemaValidator = new AhgSchemaValidator();
            $schemaValidator->loadSchemaFromSector($this->sectorCode);
            $collection->addValidator($schemaValidator);
        }

        // Add referential validator if enabled
        if ($this->options['referential']) {
            $refValidator = new AhgReferentialValidator();
            $collection->addValidator($refValidator);
        }

        // Add duplicate detector if enabled
        if ($this->options['duplicates']) {
            $dupDetector = new AhgDuplicateDetector();
            $dupDetector->setStrategy($this->options['duplicateStrategy']);
            $dupDetector->setCheckDatabase($this->options['checkDatabase']);
            $collection->addValidator($dupDetector);
        }

        // Add sector-specific validator if enabled
        if ($this->options['sector']) {
            $sectorValidator = $this->createSectorValidator();
            if (null !== $sectorValidator) {
                $collection->addValidator($sectorValidator);
            }
        }

        return $collection;
    }

    /**
     * Create the sector-specific validator.
     */
    protected function createSectorValidator(): ?AhgBaseValidator
    {
        $sectorMap = [
            'archive' => \ahgDataMigrationPlugin\Validation\Sectors\ArchivesValidator::class,
            'archives' => \ahgDataMigrationPlugin\Validation\Sectors\ArchivesValidator::class,
            'museum' => \ahgDataMigrationPlugin\Validation\Sectors\MuseumValidator::class,
            'library' => \ahgDataMigrationPlugin\Validation\Sectors\LibraryValidator::class,
            'gallery' => \ahgDataMigrationPlugin\Validation\Sectors\GalleryValidator::class,
            'dam' => \ahgDataMigrationPlugin\Validation\Sectors\DamValidator::class,
        ];

        $className = $sectorMap[$this->sectorCode] ?? null;

        if (null !== $className && class_exists($className)) {
            return new $className();
        }

        return null;
    }

    /**
     * Log validation results to database.
     */
    protected function logValidationResults(AhgValidationReport $report, int $jobId): void
    {
        $errors = $report->getRowErrors();

        foreach ($errors as $row => $columns) {
            foreach ($columns as $column => $issues) {
                foreach ($issues as $issue) {
                    DB::table('atom_validation_log')->insert([
                        'job_id' => $jobId,
                        'row_number' => $row,
                        'column_name' => $column,
                        'rule_type' => $issue['rule'] ?: 'general',
                        'severity' => $issue['severity'],
                        'message' => $issue['message'],
                        'created_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Get the last validation report.
     */
    public function getLastReport(): ?AhgValidationReport
    {
        return $this->lastReport;
    }

    /**
     * Load validation rules from database for a sector.
     *
     * @return array<array<string, mixed>>
     */
    public function loadRulesFromDatabase(string $sectorCode): array
    {
        return DB::table('atom_validation_rule')
            ->where('sector_code', $sectorCode)
            ->where('is_active', 1)
            ->get()
            ->map(function ($rule) {
                return [
                    'id' => $rule->id,
                    'field_name' => $rule->field_name,
                    'rule_type' => $rule->rule_type,
                    'rule_config' => json_decode($rule->rule_config, true),
                    'error_message' => $rule->error_message,
                    'severity' => $rule->severity,
                ];
            })
            ->toArray()
        ;
    }

    /**
     * Save a custom validation rule to database.
     *
     * @param array<string, mixed> $ruleConfig
     */
    public function saveRule(
        string $sectorCode,
        string $fieldName,
        string $ruleType,
        array $ruleConfig,
        string $errorMessage = '',
        string $severity = 'error'
    ): int {
        return DB::table('atom_validation_rule')->insertGetId([
            'sector_code' => $sectorCode,
            'field_name' => $fieldName,
            'rule_type' => $ruleType,
            'rule_config' => json_encode($ruleConfig),
            'error_message' => $errorMessage,
            'severity' => $severity,
            'is_active' => 1,
        ]);
    }

    /**
     * Get validation summary for a job.
     *
     * @return array<string, mixed>
     */
    public function getJobValidationSummary(int $jobId): array
    {
        $counts = DB::table('atom_validation_log')
            ->where('job_id', $jobId)
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray()
        ;

        $byRule = DB::table('atom_validation_log')
            ->where('job_id', $jobId)
            ->selectRaw('rule_type, COUNT(*) as count')
            ->groupBy('rule_type')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'rule_type')
            ->toArray()
        ;

        $affectedRows = DB::table('atom_validation_log')
            ->where('job_id', $jobId)
            ->where('severity', 'error')
            ->distinct()
            ->count('row_number')
        ;

        return [
            'error_count' => $counts['error'] ?? 0,
            'warning_count' => $counts['warning'] ?? 0,
            'info_count' => $counts['info'] ?? 0,
            'total_issues' => array_sum($counts),
            'affected_rows' => $affectedRows,
            'by_rule' => $byRule,
        ];
    }

    /**
     * Get detailed validation errors for a job.
     *
     * @return array<array<string, mixed>>
     */
    public function getJobValidationErrors(int $jobId, int $limit = 100, int $offset = 0): array
    {
        return DB::table('atom_validation_log')
            ->where('job_id', $jobId)
            ->where('severity', 'error')
            ->orderBy('row_number')
            ->orderBy('column_name')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->map(function ($row) {
                return [
                    'row' => $row->row_number,
                    'column' => $row->column_name,
                    'rule' => $row->rule_type,
                    'message' => $row->message,
                ];
            })
            ->toArray()
        ;
    }

    /**
     * Clear validation log for a job.
     */
    public function clearJobValidationLog(int $jobId): int
    {
        return DB::table('atom_validation_log')
            ->where('job_id', $jobId)
            ->delete()
        ;
    }

    /**
     * Validate a single row (for AJAX preview).
     *
     * @param array<string, mixed>  $row
     * @param array<string, string> $mapping
     */
    public function validateSingleRow(array $row, array $mapping = [], int $rowNumber = 1): AhgValidationReport
    {
        // Apply mapping
        $mappedRow = [];
        foreach ($row as $col => $value) {
            $targetCol = $mapping[$col] ?? $col;
            $mappedRow[$targetCol] = $value;
        }

        // Create validators
        $collection = $this->createValidatorCollection();

        // Validate single row
        return $collection->validate([$rowNumber => $mappedRow]);
    }

    /**
     * Get available validation rules for a sector.
     *
     * @return array<string, mixed>
     */
    public function getAvailableRules(string $sectorCode): array
    {
        $rulesPath = dirname(__DIR__, 2).'/data/validation/'.$sectorCode.'_rules.json';

        if (file_exists($rulesPath)) {
            $content = file_get_contents($rulesPath);

            return json_decode($content, true) ?: [];
        }

        return [];
    }
}
