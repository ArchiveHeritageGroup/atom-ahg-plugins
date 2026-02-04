<?php

namespace ahgDataMigrationPlugin\Validation;

/**
 * Base validator class that extends AtoM's CsvBaseValidator with sector-aware validation.
 *
 * This class provides the foundation for all AHG validation, adding:
 * - Sector-specific rule loading
 * - Row/column error tracking via AhgValidationReport
 * - Validate-only mode (no import)
 * - JSON rule file support
 */
class AhgBaseValidator extends \CsvBaseValidator
{
    protected ?AhgValidationReport $report = null;
    protected string $sectorCode = '';
    protected array $sectorRules = [];
    protected bool $validateOnly = false;

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->report = new AhgValidationReport();
    }

    /**
     * Set the sector code and load its validation rules.
     */
    public function setSector(string $sectorCode): self
    {
        $this->sectorCode = $sectorCode;
        $this->loadSectorRules($sectorCode);

        return $this;
    }

    /**
     * Load validation rules from JSON file for the sector.
     */
    public function loadSectorRules(string $sectorCode): self
    {
        $rulesPath = $this->getRulesPath($sectorCode);

        if (file_exists($rulesPath)) {
            $content = file_get_contents($rulesPath);
            $rules = json_decode($content, true);

            if (JSON_ERROR_NONE === json_last_error() && is_array($rules)) {
                $this->sectorRules = $rules;
            }
        }

        return $this;
    }

    /**
     * Get the path to the rules JSON file for a sector.
     */
    protected function getRulesPath(string $sectorCode): string
    {
        return dirname(__DIR__, 2).'/data/validation/'.$sectorCode.'_rules.json';
    }

    /**
     * Get loaded sector rules.
     */
    public function getSectorRules(): array
    {
        return $this->sectorRules;
    }

    /**
     * Set validate-only mode (no import, just validation).
     */
    public function setValidateOnly(bool $value): self
    {
        $this->validateOnly = $value;

        return $this;
    }

    /**
     * Check if in validate-only mode.
     */
    public function isValidateOnly(): bool
    {
        return $this->validateOnly;
    }

    /**
     * Add a row-level error to the report.
     */
    public function addRowError(int $row, string $column, string $message, string $severity = AhgValidationReport::SEVERITY_ERROR, string $rule = ''): self
    {
        if (null !== $this->report) {
            $this->report->addIssue($row, $column, $message, $severity, $rule);
        }

        return $this;
    }

    /**
     * Add an error for the current row.
     */
    public function addCurrentRowError(string $column, string $message, string $severity = AhgValidationReport::SEVERITY_ERROR, string $rule = ''): self
    {
        return $this->addRowError($this->rowNumber, $column, $message, $severity, $rule);
    }

    /**
     * Get the validation report.
     */
    public function getReport(): ?AhgValidationReport
    {
        return $this->report;
    }

    /**
     * Set a new report instance.
     */
    public function setReport(AhgValidationReport $report): self
    {
        $this->report = $report;

        return $this;
    }

    /**
     * Initialize a new report for validation.
     */
    public function initReport(string $filename = '', string $sectorCode = ''): self
    {
        $this->report = new AhgValidationReport(
            $filename ?: $this->filename,
            $sectorCode ?: $this->sectorCode
        );

        return $this;
    }

    /**
     * Override testRow to integrate with AhgValidationReport.
     */
    public function testRow(array $header, array $row)
    {
        $result = parent::testRow($header, $row);

        if (!$result && null !== $this->report) {
            foreach ($this->requiredColumns as $column) {
                if (!$this->columnPresent($column)) {
                    $this->report->addError(
                        $this->rowNumber,
                        $column,
                        sprintf("Required column '%s' is missing from the file", $column),
                        'required_column'
                    );
                }

                if ($this->columnDuplicated($column)) {
                    $this->report->addError(
                        $this->rowNumber,
                        $column,
                        sprintf("Column '%s' appears more than once in the file", $column),
                        'duplicate_column'
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Reset the validator for processing a new file.
     */
    public function reset()
    {
        parent::reset();
        $this->report = new AhgValidationReport();
        $this->sectorRules = [];
        $this->sectorCode = '';
    }

    /**
     * Get required fields from sector rules.
     *
     * @return array<string>
     */
    public function getRequiredFieldsFromRules(): array
    {
        return $this->sectorRules['rules']['required'] ?? [];
    }

    /**
     * Get type validations from sector rules.
     *
     * @return array<string, string>
     */
    public function getTypeRulesFromRules(): array
    {
        return $this->sectorRules['rules']['types'] ?? [];
    }

    /**
     * Get pattern validations from sector rules.
     *
     * @return array<string, string>
     */
    public function getPatternRulesFromRules(): array
    {
        return $this->sectorRules['rules']['patterns'] ?? [];
    }

    /**
     * Get max length validations from sector rules.
     *
     * @return array<string, int>
     */
    public function getMaxLengthRulesFromRules(): array
    {
        return $this->sectorRules['rules']['maxLengths'] ?? [];
    }

    /**
     * Get enum validations from sector rules.
     *
     * @return array<string, array<string>>
     */
    public function getEnumRulesFromRules(): array
    {
        return $this->sectorRules['rules']['enums'] ?? [];
    }

    /**
     * Get referential integrity rules from sector rules.
     *
     * @return array<string, string>
     */
    public function getReferentialRulesFromRules(): array
    {
        return $this->sectorRules['rules']['referential'] ?? [];
    }

    /**
     * Validate a value against a type.
     */
    public function validateType(mixed $value, string $type): bool
    {
        if (null === $value || '' === $value) {
            return true; // Empty values are valid for type checking
        }

        return match ($type) {
            'integer', 'int' => false !== filter_var($value, FILTER_VALIDATE_INT),
            'float', 'decimal', 'number' => is_numeric($value),
            'boolean', 'bool' => in_array(strtolower((string) $value), ['0', '1', 'true', 'false', 'yes', 'no'], true),
            'date' => $this->isValidDate($value),
            'datetime' => $this->isValidDateTime($value),
            'email' => false !== filter_var($value, FILTER_VALIDATE_EMAIL),
            'url' => false !== filter_var($value, FILTER_VALIDATE_URL),
            'string', 'text' => true,
            default => true
        };
    }

    /**
     * Check if a value is a valid date.
     */
    protected function isValidDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Common date formats
        $formats = [
            'Y-m-d',
            'Y/m/d',
            'd-m-Y',
            'd/m/Y',
            'Y',
            'Y-m',
            'c. Y', // circa year
        ];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed && $parsed->format($format) === $value) {
                return true;
            }
        }

        // Also accept free-text dates like "c. 1920" or "1920-1960"
        if (preg_match('/^c\.\s*\d{4}/', $value) || preg_match('/^\d{4}\s*-\s*\d{4}/', $value)) {
            return true;
        }

        return false;
    }

    /**
     * Check if a value is a valid datetime.
     */
    protected function isValidDateTime(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:sP',
            \DateTime::ISO8601,
            \DateTime::RFC3339,
        ];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $value);
            if ($parsed) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate a value against a regex pattern.
     */
    public function validatePattern(mixed $value, string $pattern): bool
    {
        if (null === $value || '' === $value) {
            return true;
        }

        return (bool) preg_match('/'.$pattern.'/', (string) $value);
    }

    /**
     * Validate a value against max length.
     */
    public function validateMaxLength(mixed $value, int $maxLength): bool
    {
        if (null === $value || '' === $value) {
            return true;
        }

        return mb_strlen((string) $value) <= $maxLength;
    }

    /**
     * Validate a value against an enum list.
     */
    public function validateEnum(mixed $value, array $allowedValues, bool $caseSensitive = false): bool
    {
        if (null === $value || '' === $value) {
            return true;
        }

        if ($caseSensitive) {
            return in_array($value, $allowedValues, true);
        }

        $lowerAllowed = array_map('strtolower', $allowedValues);

        return in_array(strtolower((string) $value), $lowerAllowed, true);
    }
}
