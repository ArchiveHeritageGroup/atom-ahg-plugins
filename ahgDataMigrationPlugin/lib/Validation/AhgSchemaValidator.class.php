<?php

namespace ahgDataMigrationPlugin\Validation;

/**
 * Schema validator for validating CSV data against sector-specific rules.
 *
 * Validates:
 * - Required fields
 * - Data types (integer, date, boolean, etc.)
 * - Regex patterns
 * - Max lengths
 * - Enum values
 */
class AhgSchemaValidator extends AhgBaseValidator
{
    protected array $schema = [];

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Schema Validation';
    }

    /**
     * Load schema from sector definition.
     */
    public function loadSchemaFromSector(string $sectorCode): self
    {
        $this->setSector($sectorCode);

        // Build schema from sector rules
        $this->schema = [
            'required' => $this->getRequiredFieldsFromRules(),
            'types' => $this->getTypeRulesFromRules(),
            'patterns' => $this->getPatternRulesFromRules(),
            'maxLengths' => $this->getMaxLengthRulesFromRules(),
            'enums' => $this->getEnumRulesFromRules(),
        ];

        return $this;
    }

    /**
     * Set schema directly.
     */
    public function setSchema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Get current schema.
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Validate a single row against the schema.
     *
     * @param array<string, mixed> $row Combined header => value array
     */
    public function validateRow(array $row, int $rowNumber): bool
    {
        $isValid = true;

        // Check required fields
        if (!$this->validateRequired($row, $rowNumber)) {
            $isValid = false;
        }

        // Check data types
        if (!$this->validateTypes($row, $rowNumber)) {
            $isValid = false;
        }

        // Check patterns
        if (!$this->validatePatterns($row, $rowNumber)) {
            $isValid = false;
        }

        // Check max lengths
        if (!$this->validateMaxLengths($row, $rowNumber)) {
            $isValid = false;
        }

        // Check enums
        if (!$this->validateEnums($row, $rowNumber)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Validate required fields are present and non-empty.
     *
     * @param array<string, mixed> $row
     */
    public function validateRequired(array $row, int $rowNumber): bool
    {
        $isValid = true;
        $requiredFields = $this->schema['required'] ?? [];

        foreach ($requiredFields as $field) {
            $value = $row[$field] ?? null;

            if (null === $value || '' === trim((string) $value)) {
                $this->addRowError(
                    $rowNumber,
                    $field,
                    sprintf("Required field '%s' is empty or missing", $field),
                    AhgValidationReport::SEVERITY_ERROR,
                    'required'
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Validate field data types.
     *
     * @param array<string, mixed> $row
     */
    public function validateTypes(array $row, int $rowNumber): bool
    {
        $isValid = true;
        $typeRules = $this->schema['types'] ?? [];

        foreach ($typeRules as $field => $type) {
            if (!isset($row[$field])) {
                continue;
            }

            $value = $row[$field];

            if (!$this->validateType($value, $type)) {
                $this->addRowError(
                    $rowNumber,
                    $field,
                    sprintf("Field '%s' must be of type '%s', got '%s'", $field, $type, $this->describeValue($value)),
                    AhgValidationReport::SEVERITY_ERROR,
                    'type'
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Validate fields against regex patterns.
     *
     * @param array<string, mixed> $row
     */
    public function validatePatterns(array $row, int $rowNumber): bool
    {
        $isValid = true;
        $patternRules = $this->schema['patterns'] ?? [];

        foreach ($patternRules as $field => $pattern) {
            if (!isset($row[$field])) {
                continue;
            }

            $value = $row[$field];

            if (!$this->validatePattern($value, $pattern)) {
                $this->addRowError(
                    $rowNumber,
                    $field,
                    sprintf("Field '%s' value '%s' does not match required pattern", $field, $this->truncateValue($value)),
                    AhgValidationReport::SEVERITY_ERROR,
                    'pattern'
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Validate field max lengths.
     *
     * @param array<string, mixed> $row
     */
    public function validateMaxLengths(array $row, int $rowNumber): bool
    {
        $isValid = true;
        $maxLengthRules = $this->schema['maxLengths'] ?? [];

        foreach ($maxLengthRules as $field => $maxLength) {
            if (!isset($row[$field])) {
                continue;
            }

            $value = $row[$field];

            if (!$this->validateMaxLength($value, $maxLength)) {
                $actualLength = mb_strlen((string) $value);
                $this->addRowError(
                    $rowNumber,
                    $field,
                    sprintf("Field '%s' exceeds max length of %d characters (actual: %d)", $field, $maxLength, $actualLength),
                    AhgValidationReport::SEVERITY_ERROR,
                    'maxLength'
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Validate fields against enum lists.
     *
     * @param array<string, mixed> $row
     */
    public function validateEnums(array $row, int $rowNumber): bool
    {
        $isValid = true;
        $enumRules = $this->schema['enums'] ?? [];

        foreach ($enumRules as $field => $allowedValues) {
            if (!isset($row[$field])) {
                continue;
            }

            $value = $row[$field];

            if (!$this->validateEnum($value, $allowedValues)) {
                $this->addRowError(
                    $rowNumber,
                    $field,
                    sprintf(
                        "Field '%s' value '%s' is not in allowed values: %s",
                        $field,
                        $this->truncateValue($value),
                        implode(', ', array_slice($allowedValues, 0, 5)).(count($allowedValues) > 5 ? '...' : '')
                    ),
                    AhgValidationReport::SEVERITY_ERROR,
                    'enum'
                );
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Validate entire CSV file.
     *
     * @param array<int, array<string, mixed>> $rows Array of combined header => value rows
     */
    public function validateFile(array $rows): AhgValidationReport
    {
        $this->report->setTotalRows(count($rows));

        foreach ($rows as $rowNumber => $row) {
            $this->validateRow($row, $rowNumber);
        }

        return $this->report->finish();
    }

    /**
     * Describe a value's type for error messages.
     */
    protected function describeValue(mixed $value): string
    {
        if (null === $value) {
            return 'null';
        }

        if ('' === $value) {
            return 'empty string';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return $this->truncateValue($value);
    }

    /**
     * Truncate a value for display in error messages.
     */
    protected function truncateValue(mixed $value, int $maxLength = 50): string
    {
        $str = (string) $value;

        if (mb_strlen($str) > $maxLength) {
            return mb_substr($str, 0, $maxLength).'...';
        }

        return $str;
    }
}
