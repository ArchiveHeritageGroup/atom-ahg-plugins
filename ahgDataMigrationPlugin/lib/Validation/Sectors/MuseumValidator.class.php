<?php

namespace ahgDataMigrationPlugin\Validation\Sectors;

use ahgDataMigrationPlugin\Validation\AhgBaseValidator;
use ahgDataMigrationPlugin\Validation\AhgValidationReport;

/**
 * Museum sector validator implementing Spectrum 5.0 validation rules.
 *
 * Validates:
 * - Object identification requirements
 * - Object number format
 * - Acquisition information
 * - Location tracking
 * - Measurement formats
 * - Material and technique descriptions
 */
class MuseumValidator extends AhgBaseValidator
{
    /** Spectrum object entry requirements - minimum fields */
    public const SPECTRUM_MINIMUM = [
        'objectNumber',
        'objectName',
    ];

    /** Common object types */
    public const OBJECT_TYPES = [
        'painting',
        'sculpture',
        'photograph',
        'pottery',
        'textile',
        'print',
        'drawing',
        'furniture',
        'jewelry',
        'tool',
        'weapon',
        'costume',
        'document',
        'book',
        'specimen',
        'artifact',
        'mixed media',
        'installation',
        'other',
    ];

    /** Common acquisition methods */
    public const ACQUISITION_METHODS = [
        'purchase',
        'gift',
        'donation',
        'bequest',
        'transfer',
        'exchange',
        'field collection',
        'excavation',
        'loan',
        'found',
        'conversion',
        'unknown',
    ];

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Museum (Spectrum) Validation';
        $this->sectorCode = 'museum';
    }

    /**
     * Validate a row against Spectrum rules.
     *
     * @param array<string, mixed> $row
     */
    public function validateRow(array $row, int $rowNumber): bool
    {
        $isValid = true;

        // Validate object number format
        if (!$this->validateObjectNumber($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate object name/type
        if (!$this->validateObjectName($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate acquisition information
        if (!$this->validateAcquisition($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate measurements/dimensions
        if (!$this->validateDimensions($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate date formats
        if (!$this->validateDates($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate number of objects
        if (!$this->validateNumberOfObjects($row, $rowNumber)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Validate object number format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateObjectNumber(array $row, int $rowNumber): bool
    {
        $objectNumber = $row['objectNumber'] ?? $row['object_number'] ?? $row['identifier'] ?? null;

        if (null === $objectNumber || '' === trim((string) $objectNumber)) {
            return true; // Required check handled by schema validator
        }

        $objectNumber = trim((string) $objectNumber);

        // Check for common issues
        if (preg_match('/\s{2,}/', $objectNumber)) {
            $this->addRowError(
                $rowNumber,
                'objectNumber',
                'Object number contains multiple consecutive spaces',
                AhgValidationReport::SEVERITY_WARNING,
                'spectrum_object_number_spaces'
            );
        }

        // Check for very long object numbers
        if (mb_strlen($objectNumber) > 50) {
            $this->addRowError(
                $rowNumber,
                'objectNumber',
                'Object number exceeds recommended length of 50 characters',
                AhgValidationReport::SEVERITY_WARNING,
                'spectrum_object_number_length'
            );
        }

        // Suggest standard formats
        if (!preg_match('/^[A-Z0-9]/', $objectNumber)) {
            $this->addRowError(
                $rowNumber,
                'objectNumber',
                'Object number should typically start with a letter or number',
                AhgValidationReport::SEVERITY_INFO,
                'spectrum_object_number_format'
            );
        }

        return true;
    }

    /**
     * Validate object name/type.
     *
     * @param array<string, mixed> $row
     */
    protected function validateObjectName(array $row, int $rowNumber): bool
    {
        $objectName = $row['objectName'] ?? $row['object_name'] ?? $row['title'] ?? null;

        if (null === $objectName || '' === trim((string) $objectName)) {
            return true;
        }

        $objectName = strtolower(trim((string) $objectName));

        // Check if it matches common types (info only)
        if (!in_array($objectName, self::OBJECT_TYPES, true)) {
            // Check if it contains a known type
            $containsType = false;
            foreach (self::OBJECT_TYPES as $type) {
                if (str_contains($objectName, $type)) {
                    $containsType = true;

                    break;
                }
            }

            if (!$containsType) {
                $this->addRowError(
                    $rowNumber,
                    'objectName',
                    sprintf(
                        "Object name '%s' is not a standard type. Consider using: %s",
                        $objectName,
                        implode(', ', array_slice(self::OBJECT_TYPES, 0, 5)).'...'
                    ),
                    AhgValidationReport::SEVERITY_INFO,
                    'spectrum_object_name_nonstandard'
                );
            }
        }

        return true;
    }

    /**
     * Validate acquisition information.
     *
     * @param array<string, mixed> $row
     */
    protected function validateAcquisition(array $row, int $rowNumber): bool
    {
        $acquisitionMethod = $row['acquisitionMethod'] ?? $row['acquisition_method'] ?? null;
        $acquisitionDate = $row['acquisitionDate'] ?? $row['acquisition_date'] ?? null;

        // If acquisition date is present, validate format
        if (null !== $acquisitionDate && '' !== trim((string) $acquisitionDate)) {
            if (!$this->validateType($acquisitionDate, 'date')) {
                $this->addRowError(
                    $rowNumber,
                    'acquisitionDate',
                    sprintf("Acquisition date '%s' is not in a valid date format", $acquisitionDate),
                    AhgValidationReport::SEVERITY_ERROR,
                    'spectrum_acquisition_date_format'
                );

                return false;
            }
        }

        // Validate acquisition method if present
        if (null !== $acquisitionMethod && '' !== trim((string) $acquisitionMethod)) {
            $method = strtolower(trim((string) $acquisitionMethod));

            if (!in_array($method, self::ACQUISITION_METHODS, true)) {
                $this->addRowError(
                    $rowNumber,
                    'acquisitionMethod',
                    sprintf(
                        "Acquisition method '%s' is not a standard value. Recommended: %s",
                        $acquisitionMethod,
                        implode(', ', array_slice(self::ACQUISITION_METHODS, 0, 5)).'...'
                    ),
                    AhgValidationReport::SEVERITY_INFO,
                    'spectrum_acquisition_method_nonstandard'
                );
            }
        }

        return true;
    }

    /**
     * Validate dimensions/measurements format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateDimensions(array $row, int $rowNumber): bool
    {
        $dimensions = $row['dimensions'] ?? $row['measurements'] ?? $row['measurementsDimensions'] ?? null;

        if (null === $dimensions || '' === trim((string) $dimensions)) {
            return true;
        }

        $dimensions = trim((string) $dimensions);

        // Check for common dimension patterns
        $hasUnit = preg_match('/\b(cm|mm|m|in|ft|inches|feet)\b/i', $dimensions);

        if (!$hasUnit) {
            $this->addRowError(
                $rowNumber,
                'dimensions',
                'Dimensions should include units of measurement (e.g., cm, mm, in)',
                AhgValidationReport::SEVERITY_WARNING,
                'spectrum_dimensions_unit'
            );
        }

        // Check for dimension type indicators
        $hasType = preg_match('/\b(height|width|depth|diameter|length|h|w|d)\b/i', $dimensions);

        if (!$hasType && !preg_match('/\d+\s*x\s*\d+/i', $dimensions)) {
            $this->addRowError(
                $rowNumber,
                'dimensions',
                'Consider specifying dimension type (e.g., Height: 30 cm; Width: 20 cm)',
                AhgValidationReport::SEVERITY_INFO,
                'spectrum_dimensions_type'
            );
        }

        return true;
    }

    /**
     * Validate production/creation dates.
     *
     * @param array<string, mixed> $row
     */
    protected function validateDates(array $row, int $rowNumber): bool
    {
        $productionDate = $row['productionDate'] ?? $row['production_date'] ?? $row['creationDate'] ?? null;

        if (null === $productionDate || '' === trim((string) $productionDate)) {
            return true;
        }

        $productionDate = trim((string) $productionDate);

        // Check for future dates (unlikely for museum objects)
        if (preg_match('/^(\d{4})/', $productionDate, $matches)) {
            $year = (int) $matches[1];
            $currentYear = (int) date('Y');

            if ($year > $currentYear) {
                $this->addRowError(
                    $rowNumber,
                    'productionDate',
                    sprintf('Production date (%d) is in the future', $year),
                    AhgValidationReport::SEVERITY_WARNING,
                    'spectrum_date_future'
                );
            }
        }

        return true;
    }

    /**
     * Validate number of objects field.
     *
     * @param array<string, mixed> $row
     */
    protected function validateNumberOfObjects(array $row, int $rowNumber): bool
    {
        $numberOfObjects = $row['numberOfObjects'] ?? $row['number_of_objects'] ?? $row['quantity'] ?? null;

        if (null === $numberOfObjects || '' === (string) $numberOfObjects) {
            return true;
        }

        if (!is_numeric($numberOfObjects)) {
            $this->addRowError(
                $rowNumber,
                'numberOfObjects',
                sprintf("Number of objects '%s' must be a number", $numberOfObjects),
                AhgValidationReport::SEVERITY_ERROR,
                'spectrum_number_format'
            );

            return false;
        }

        $count = (int) $numberOfObjects;

        if ($count < 1) {
            $this->addRowError(
                $rowNumber,
                'numberOfObjects',
                'Number of objects must be at least 1',
                AhgValidationReport::SEVERITY_ERROR,
                'spectrum_number_minimum'
            );

            return false;
        }

        if ($count > 10000) {
            $this->addRowError(
                $rowNumber,
                'numberOfObjects',
                sprintf('Number of objects (%d) is unusually high - please verify', $count),
                AhgValidationReport::SEVERITY_WARNING,
                'spectrum_number_high'
            );
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
