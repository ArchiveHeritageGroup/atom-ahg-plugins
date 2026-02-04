<?php

namespace ahgDataMigrationPlugin\Validation\Sectors;

use ahgDataMigrationPlugin\Validation\AhgBaseValidator;
use ahgDataMigrationPlugin\Validation\AhgValidationReport;

/**
 * Archives sector validator implementing ISAD(G) validation rules.
 *
 * Validates:
 * - ISAD(G) mandatory elements
 * - Level of description validity
 * - Hierarchy rules (fonds must not have parent, etc.)
 * - Reference code format
 * - Date range format and logic
 */
class ArchivesValidator extends AhgBaseValidator
{
    /** Valid ISAD(G) levels of description */
    public const LEVELS_OF_DESCRIPTION = [
        'fonds',
        'subfonds',
        'collection',
        'series',
        'subseries',
        'file',
        'item',
        'part',
    ];

    /** Levels that should typically be top-level */
    public const TOP_LEVELS = ['fonds', 'collection'];

    /** Levels that must have a parent */
    public const CHILD_LEVELS = ['subfonds', 'subseries', 'file', 'item', 'part'];

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Archives (ISAD-G) Validation';
        $this->sectorCode = 'archive';
    }

    /**
     * Validate a row against ISAD(G) rules.
     *
     * @param array<string, mixed> $row
     */
    public function validateRow(array $row, int $rowNumber): bool
    {
        $isValid = true;

        // Validate level of description
        if (!$this->validateLevelOfDescription($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate reference code / identifier format
        if (!$this->validateReferenceCode($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate date format
        if (!$this->validateDateRange($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate hierarchy rules
        if (!$this->validateHierarchyRules($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate extent and medium
        if (!$this->validateExtentAndMedium($row, $rowNumber)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Validate level of description.
     *
     * @param array<string, mixed> $row
     */
    protected function validateLevelOfDescription(array $row, int $rowNumber): bool
    {
        $level = $row['levelOfDescription'] ?? $row['level_of_description'] ?? $row['level'] ?? null;

        if (null === $level || '' === trim((string) $level)) {
            // Level is optional but recommended
            $this->addRowError(
                $rowNumber,
                'levelOfDescription',
                'Level of description is recommended for archival records',
                AhgValidationReport::SEVERITY_WARNING,
                'isadg_level_recommended'
            );

            return true;
        }

        $level = strtolower(trim((string) $level));

        if (!in_array($level, self::LEVELS_OF_DESCRIPTION, true)) {
            $this->addRowError(
                $rowNumber,
                'levelOfDescription',
                sprintf(
                    "Invalid level of description '%s'. Valid values: %s",
                    $level,
                    implode(', ', self::LEVELS_OF_DESCRIPTION)
                ),
                AhgValidationReport::SEVERITY_ERROR,
                'isadg_invalid_level'
            );

            return false;
        }

        return true;
    }

    /**
     * Validate reference code / identifier format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateReferenceCode(array $row, int $rowNumber): bool
    {
        $identifier = $row['identifier'] ?? $row['referenceCode'] ?? $row['reference_code'] ?? null;

        if (null === $identifier || '' === trim((string) $identifier)) {
            return true; // Required check is handled by schema validator
        }

        $identifier = trim((string) $identifier);

        // Check for problematic characters
        if (preg_match('/[<>"\']/', $identifier)) {
            $this->addRowError(
                $rowNumber,
                'identifier',
                "Identifier contains problematic characters (<, >, \", ')",
                AhgValidationReport::SEVERITY_WARNING,
                'isadg_identifier_chars'
            );
        }

        // Check for very long identifiers
        if (mb_strlen($identifier) > 255) {
            $this->addRowError(
                $rowNumber,
                'identifier',
                'Identifier exceeds recommended maximum length of 255 characters',
                AhgValidationReport::SEVERITY_WARNING,
                'isadg_identifier_length'
            );
        }

        return true;
    }

    /**
     * Validate date range format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateDateRange(array $row, int $rowNumber): bool
    {
        $dateFields = ['dateRange', 'date_range', 'date', 'eventDates', 'event_dates'];
        $date = null;

        foreach ($dateFields as $field) {
            if (isset($row[$field]) && '' !== trim((string) $row[$field])) {
                $date = trim((string) $row[$field]);

                break;
            }
        }

        if (null === $date) {
            return true;
        }

        // Check for obviously invalid dates
        if (preg_match('/^\d{5,}$/', $date)) {
            $this->addRowError(
                $rowNumber,
                'dateRange',
                sprintf("Date '%s' appears to be an invalid number, not a date", $date),
                AhgValidationReport::SEVERITY_ERROR,
                'isadg_invalid_date'
            );

            return false;
        }

        // Check date range logic (start should be before end)
        if (preg_match('/^(\d{4})\s*-\s*(\d{4})$/', $date, $matches)) {
            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];

            if ($startYear > $endYear) {
                $this->addRowError(
                    $rowNumber,
                    'dateRange',
                    sprintf('Date range start year (%d) is after end year (%d)', $startYear, $endYear),
                    AhgValidationReport::SEVERITY_ERROR,
                    'isadg_date_range_order'
                );

                return false;
            }

            // Check for unreasonable dates
            $currentYear = (int) date('Y');
            if ($endYear > $currentYear + 10) {
                $this->addRowError(
                    $rowNumber,
                    'dateRange',
                    sprintf('End year (%d) appears to be in the distant future', $endYear),
                    AhgValidationReport::SEVERITY_WARNING,
                    'isadg_date_future'
                );
            }
        }

        return true;
    }

    /**
     * Validate hierarchy rules.
     *
     * @param array<string, mixed> $row
     */
    protected function validateHierarchyRules(array $row, int $rowNumber): bool
    {
        $level = strtolower(trim((string) ($row['levelOfDescription'] ?? $row['level_of_description'] ?? $row['level'] ?? '')));
        $parentId = $row['parentId'] ?? $row['parent_id'] ?? $row['qubitParentSlug'] ?? null;
        $hasParent = null !== $parentId && '' !== trim((string) $parentId);

        if ('' === $level) {
            return true;
        }

        // Fonds/collections typically shouldn't have parents (warning only)
        if (in_array($level, self::TOP_LEVELS, true) && $hasParent) {
            $this->addRowError(
                $rowNumber,
                'levelOfDescription',
                sprintf("A '%s' typically should not have a parent record", $level),
                AhgValidationReport::SEVERITY_WARNING,
                'isadg_hierarchy_toplevel'
            );
        }

        // Items/files/subseries should have parents (warning only)
        if (in_array($level, self::CHILD_LEVELS, true) && !$hasParent) {
            $this->addRowError(
                $rowNumber,
                'levelOfDescription',
                sprintf("A '%s' typically should have a parent record", $level),
                AhgValidationReport::SEVERITY_WARNING,
                'isadg_hierarchy_child'
            );
        }

        return true;
    }

    /**
     * Validate extent and medium field.
     *
     * @param array<string, mixed> $row
     */
    protected function validateExtentAndMedium(array $row, int $rowNumber): bool
    {
        $extent = $row['extentAndMedium'] ?? $row['extent_and_medium'] ?? $row['physicalDescription'] ?? null;

        if (null === $extent || '' === trim((string) $extent)) {
            // Extent is recommended but not required
            $level = strtolower(trim((string) ($row['levelOfDescription'] ?? $row['level_of_description'] ?? '')));

            if (in_array($level, ['fonds', 'collection', 'series'], true)) {
                $this->addRowError(
                    $rowNumber,
                    'extentAndMedium',
                    sprintf("Extent and medium is recommended for '%s' level records", $level),
                    AhgValidationReport::SEVERITY_INFO,
                    'isadg_extent_recommended'
                );
            }
        }

        return true;
    }

    /**
     * Validate entire file.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validateFile(array $rows): AhgValidationReport
    {
        $this->report->setTotalRows(count($rows));

        foreach ($rows as $rowNumber => $row) {
            $this->validateRow($row, $rowNumber);
        }

        return $this->report->finish();
    }
}
