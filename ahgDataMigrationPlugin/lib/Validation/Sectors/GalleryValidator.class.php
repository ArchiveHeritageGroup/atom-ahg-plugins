<?php

namespace ahgDataMigrationPlugin\Validation\Sectors;

use ahgDataMigrationPlugin\Validation\AhgBaseValidator;
use ahgDataMigrationPlugin\Validation\AhgValidationReport;

/**
 * Gallery sector validator implementing CCO (Cataloging Cultural Objects) validation rules.
 *
 * Validates:
 * - Work type classification
 * - Creator/artist information
 * - Measurements format
 * - Style/period terminology
 * - Provenance information
 * - Medium and support
 */
class GalleryValidator extends AhgBaseValidator
{
    /** AAT Work Types (simplified) */
    public const WORK_TYPES = [
        'painting',
        'drawing',
        'print',
        'photograph',
        'sculpture',
        'installation',
        'video',
        'mixed media',
        'textile',
        'ceramic',
        'metalwork',
        'furniture',
        'jewelry',
        'manuscript',
        'book',
        'poster',
        'collage',
        'assemblage',
        'performance',
        'digital art',
    ];

    /** Common art periods/styles */
    public const STYLE_PERIODS = [
        'prehistoric',
        'ancient',
        'medieval',
        'renaissance',
        'baroque',
        'rococo',
        'neoclassicism',
        'romanticism',
        'realism',
        'impressionism',
        'post-impressionism',
        'expressionism',
        'cubism',
        'surrealism',
        'abstract expressionism',
        'pop art',
        'minimalism',
        'conceptual art',
        'contemporary',
        'modern',
        'african',
        'south african',
    ];

    /** Creator roles */
    public const CREATOR_ROLES = [
        'artist',
        'painter',
        'sculptor',
        'photographer',
        'printmaker',
        'designer',
        'architect',
        'illustrator',
        'ceramicist',
        'jeweler',
        'weaver',
        'attributed to',
        'workshop of',
        'circle of',
        'follower of',
        'after',
        'manner of',
    ];

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Gallery (CCO) Validation';
        $this->sectorCode = 'gallery';
    }

    /**
     * Validate a row against CCO rules.
     *
     * @param array<string, mixed> $row
     */
    public function validateRow(array $row, int $rowNumber): bool
    {
        $isValid = true;

        // Validate work type
        if (!$this->validateWorkType($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate creator information
        if (!$this->validateCreator($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate creation date
        if (!$this->validateCreationDate($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate measurements
        if (!$this->validateMeasurements($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate style/period
        if (!$this->validateStylePeriod($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate medium and materials
        if (!$this->validateMedium($row, $rowNumber)) {
            $isValid = false;
        }

        // Validate provenance
        if (!$this->validateProvenance($row, $rowNumber)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Validate work type classification.
     *
     * @param array<string, mixed> $row
     */
    protected function validateWorkType(array $row, int $rowNumber): bool
    {
        $workType = $row['workType'] ?? $row['work_type'] ?? $row['objectType'] ?? null;

        if (null === $workType || '' === trim((string) $workType)) {
            $this->addRowError(
                $rowNumber,
                'workType',
                'Work type is recommended for artwork cataloging',
                AhgValidationReport::SEVERITY_INFO,
                'cco_worktype_recommended'
            );

            return true;
        }

        $workType = strtolower(trim((string) $workType));

        if (!in_array($workType, self::WORK_TYPES, true)) {
            // Check if it contains a known type
            $found = false;
            foreach (self::WORK_TYPES as $type) {
                if (str_contains($workType, $type)) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                $this->addRowError(
                    $rowNumber,
                    'workType',
                    sprintf(
                        "Work type '%s' is not a standard CCO type. Consider: %s",
                        $workType,
                        implode(', ', array_slice(self::WORK_TYPES, 0, 5)).'...'
                    ),
                    AhgValidationReport::SEVERITY_INFO,
                    'cco_worktype_nonstandard'
                );
            }
        }

        return true;
    }

    /**
     * Validate creator information.
     *
     * @param array<string, mixed> $row
     */
    protected function validateCreator(array $row, int $rowNumber): bool
    {
        $creator = $row['creator'] ?? $row['artist'] ?? $row['maker'] ?? null;
        $creatorRole = $row['creatorRole'] ?? $row['creator_role'] ?? null;

        if (null === $creator || '' === trim((string) $creator)) {
            // Creator is important for galleries
            $this->addRowError(
                $rowNumber,
                'creator',
                'Creator/artist information is highly recommended for artwork',
                AhgValidationReport::SEVERITY_WARNING,
                'cco_creator_recommended'
            );

            return true;
        }

        $creator = trim((string) $creator);

        // Check for "Unknown" or "Anonymous" - that's fine but note it
        if (preg_match('/^(unknown|anonymous|unidentified)/i', $creator)) {
            $this->addRowError(
                $rowNumber,
                'creator',
                'Creator is unknown - consider adding any known attribution information',
                AhgValidationReport::SEVERITY_INFO,
                'cco_creator_unknown'
            );

            return true;
        }

        // Check for preferred format: Surname, Forename
        if (!str_contains($creator, ',') && !preg_match('/^(attributed|workshop|circle|follower|after|manner)/i', $creator)) {
            $this->addRowError(
                $rowNumber,
                'creator',
                "Creator should preferably use 'Surname, Forename' format",
                AhgValidationReport::SEVERITY_INFO,
                'cco_creator_format'
            );
        }

        // Validate creator role if present
        if (null !== $creatorRole && '' !== trim((string) $creatorRole)) {
            $role = strtolower(trim((string) $creatorRole));

            if (!in_array($role, self::CREATOR_ROLES, true)) {
                $this->addRowError(
                    $rowNumber,
                    'creatorRole',
                    sprintf("Creator role '%s' is not a standard value", $creatorRole),
                    AhgValidationReport::SEVERITY_INFO,
                    'cco_role_nonstandard'
                );
            }
        }

        return true;
    }

    /**
     * Validate creation date.
     *
     * @param array<string, mixed> $row
     */
    protected function validateCreationDate(array $row, int $rowNumber): bool
    {
        $creationDate = $row['creationDate'] ?? $row['creation_date'] ?? $row['date'] ?? null;
        $earliestDate = $row['creationEarliestDate'] ?? $row['creation_earliest_date'] ?? null;
        $latestDate = $row['creationLatestDate'] ?? $row['creation_latest_date'] ?? null;

        if (null === $creationDate && null === $earliestDate) {
            $this->addRowError(
                $rowNumber,
                'creationDate',
                'Creation date is recommended for artwork cataloging',
                AhgValidationReport::SEVERITY_INFO,
                'cco_date_recommended'
            );

            return true;
        }

        // Validate date range if both provided
        if (null !== $earliestDate && null !== $latestDate) {
            if (preg_match('/(\d{4})/', (string) $earliestDate, $earlyMatch)
                && preg_match('/(\d{4})/', (string) $latestDate, $lateMatch)) {
                $early = (int) $earlyMatch[1];
                $late = (int) $lateMatch[1];

                if ($early > $late) {
                    $this->addRowError(
                        $rowNumber,
                        'creationDate',
                        sprintf('Earliest date (%d) is after latest date (%d)', $early, $late),
                        AhgValidationReport::SEVERITY_ERROR,
                        'cco_date_range_order'
                    );

                    return false;
                }

                // Check for very wide date ranges
                if (($late - $early) > 100) {
                    $this->addRowError(
                        $rowNumber,
                        'creationDate',
                        sprintf('Date range spans over 100 years (%d-%d) - consider narrowing if possible', $early, $late),
                        AhgValidationReport::SEVERITY_INFO,
                        'cco_date_range_wide'
                    );
                }
            }
        }

        return true;
    }

    /**
     * Validate measurements format.
     *
     * @param array<string, mixed> $row
     */
    protected function validateMeasurements(array $row, int $rowNumber): bool
    {
        $measurements = $row['measurements'] ?? $row['dimensions'] ?? $row['measurementsDimensions'] ?? null;

        if (null === $measurements || '' === trim((string) $measurements)) {
            $this->addRowError(
                $rowNumber,
                'measurements',
                'Measurements are recommended for artwork cataloging',
                AhgValidationReport::SEVERITY_INFO,
                'cco_measurements_recommended'
            );

            return true;
        }

        $measurements = trim((string) $measurements);

        // Check for unit of measure
        $hasUnit = preg_match('/\b(cm|mm|m|in|inches|ft|feet)\b/i', $measurements);

        if (!$hasUnit) {
            $this->addRowError(
                $rowNumber,
                'measurements',
                'Measurements should include unit (e.g., cm, in)',
                AhgValidationReport::SEVERITY_WARNING,
                'cco_measurements_unit'
            );
        }

        // Check for dimension specification
        if (!preg_match('/\b(h|w|d|height|width|depth|diameter)\b/i', $measurements)
            && !preg_match('/\d+\s*[x×]\s*\d+/i', $measurements)) {
            $this->addRowError(
                $rowNumber,
                'measurements',
                "Consider specifying dimension type (e.g., 'H x W x D: 30 x 20 x 5 cm')",
                AhgValidationReport::SEVERITY_INFO,
                'cco_measurements_format'
            );
        }

        return true;
    }

    /**
     * Validate style/period.
     *
     * @param array<string, mixed> $row
     */
    protected function validateStylePeriod(array $row, int $rowNumber): bool
    {
        $stylePeriod = $row['stylePeriod'] ?? $row['style_period'] ?? $row['style'] ?? $row['period'] ?? null;

        if (null === $stylePeriod || '' === trim((string) $stylePeriod)) {
            return true;
        }

        $stylePeriod = strtolower(trim((string) $stylePeriod));

        // Check against known styles
        $found = false;
        foreach (self::STYLE_PERIODS as $style) {
            if (str_contains($stylePeriod, $style)) {
                $found = true;

                break;
            }
        }

        if (!$found) {
            $this->addRowError(
                $rowNumber,
                'stylePeriod',
                sprintf(
                    "Style/period '%s' is not a standard art historical term",
                    $stylePeriod
                ),
                AhgValidationReport::SEVERITY_INFO,
                'cco_style_nonstandard'
            );
        }

        return true;
    }

    /**
     * Validate medium and materials.
     *
     * @param array<string, mixed> $row
     */
    protected function validateMedium(array $row, int $rowNumber): bool
    {
        $medium = $row['medium'] ?? $row['materials'] ?? $row['technique'] ?? null;

        if (null === $medium || '' === trim((string) $medium)) {
            $this->addRowError(
                $rowNumber,
                'medium',
                'Medium/materials information is recommended for artwork',
                AhgValidationReport::SEVERITY_INFO,
                'cco_medium_recommended'
            );

            return true;
        }

        $medium = trim((string) $medium);

        // Check for both medium and support (e.g., "oil on canvas")
        if (!preg_match('/\b(on|with)\b/i', $medium) && !str_contains($medium, ';')) {
            $this->addRowError(
                $rowNumber,
                'medium',
                "Consider specifying both medium and support (e.g., 'Oil on canvas')",
                AhgValidationReport::SEVERITY_INFO,
                'cco_medium_support'
            );
        }

        return true;
    }

    /**
     * Validate provenance information.
     *
     * @param array<string, mixed> $row
     */
    protected function validateProvenance(array $row, int $rowNumber): bool
    {
        $provenance = $row['provenance'] ?? $row['ownership_history'] ?? null;

        if (null === $provenance || '' === trim((string) $provenance)) {
            // Provenance is important for artwork authentication
            $this->addRowError(
                $rowNumber,
                'provenance',
                'Provenance information is recommended for artwork authentication',
                AhgValidationReport::SEVERITY_INFO,
                'cco_provenance_recommended'
            );

            return true;
        }

        $provenance = trim((string) $provenance);

        // Check for date information in provenance
        if (!preg_match('/\d{4}/', $provenance)) {
            $this->addRowError(
                $rowNumber,
                'provenance',
                'Consider including dates in provenance entries',
                AhgValidationReport::SEVERITY_INFO,
                'cco_provenance_dates'
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
