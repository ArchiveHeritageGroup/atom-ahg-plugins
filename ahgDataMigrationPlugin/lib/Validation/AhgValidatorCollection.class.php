<?php

namespace ahgDataMigrationPlugin\Validation;

/**
 * Sector-aware validator collection that orchestrates multiple validators.
 *
 * This class manages a collection of validators and runs them in sequence,
 * merging their results into a single validation report.
 */
class AhgValidatorCollection
{
    /** @var array<AhgBaseValidator> */
    protected array $validators = [];

    protected ?AhgValidationReport $report = null;
    protected string $sectorCode = '';
    protected string $filename = '';

    public function __construct(string $sectorCode = '', string $filename = '')
    {
        $this->sectorCode = $sectorCode;
        $this->filename = $filename;
        $this->report = new AhgValidationReport($filename, $sectorCode);
    }

    /**
     * Add a validator to the collection.
     */
    public function addValidator(AhgBaseValidator $validator): self
    {
        $validator->setReport(new AhgValidationReport($this->filename, $this->sectorCode));

        if ('' !== $this->sectorCode) {
            $validator->setSector($this->sectorCode);
        }

        $this->validators[] = $validator;

        return $this;
    }

    /**
     * Get all validators in the collection.
     *
     * @return array<AhgBaseValidator>
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * Set the sector code for all validators.
     */
    public function setSector(string $sectorCode): self
    {
        $this->sectorCode = $sectorCode;
        $this->report = new AhgValidationReport($this->filename, $sectorCode);

        foreach ($this->validators as $validator) {
            $validator->setSector($sectorCode);
        }

        return $this;
    }

    /**
     * Set the filename for reporting.
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        $this->report = new AhgValidationReport($filename, $this->sectorCode);

        return $this;
    }

    /**
     * Run all validators against the provided rows.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validate(array $rows): AhgValidationReport
    {
        $this->report = new AhgValidationReport($this->filename, $this->sectorCode);
        $this->report->setTotalRows(count($rows));

        foreach ($this->validators as $validator) {
            $validatorReport = $validator->validateFile($rows);
            $this->report->merge($validatorReport);
        }

        return $this->report->finish();
    }

    /**
     * Run specific validator types based on options.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, bool>              $options Validator options
     */
    public function validateWithOptions(array $rows, array $options = []): AhgValidationReport
    {
        $this->report = new AhgValidationReport($this->filename, $this->sectorCode);
        $this->report->setTotalRows(count($rows));

        $defaults = [
            'schema' => true,
            'referential' => true,
            'duplicates' => true,
            'sector' => true,
            'checkDatabase' => true,
        ];

        $options = array_merge($defaults, $options);

        foreach ($this->validators as $validator) {
            // Skip validators based on options
            if (!$options['schema'] && $validator instanceof AhgSchemaValidator) {
                continue;
            }

            if (!$options['referential'] && $validator instanceof AhgReferentialValidator) {
                continue;
            }

            if (!$options['duplicates'] && $validator instanceof AhgDuplicateDetector) {
                continue;
            }

            // Configure validators
            if ($validator instanceof AhgDuplicateDetector) {
                $validator->setCheckDatabase($options['checkDatabase']);
            }

            if ($validator instanceof AhgReferentialValidator) {
                $validatorReport = $validator->validateFile($rows, $options['checkDatabase']);
            } else {
                $validatorReport = $validator->validateFile($rows);
            }

            $this->report->merge($validatorReport);
        }

        return $this->report->finish();
    }

    /**
     * Get the combined validation report.
     */
    public function getReport(): ?AhgValidationReport
    {
        return $this->report;
    }

    /**
     * Create a standard collection with all validators for a sector.
     */
    public static function createForSector(string $sectorCode, string $filename = ''): self
    {
        $collection = new self($sectorCode, $filename);

        // Add schema validator
        $schemaValidator = new AhgSchemaValidator();
        $schemaValidator->loadSchemaFromSector($sectorCode);
        $collection->addValidator($schemaValidator);

        // Add referential validator
        $referentialValidator = new AhgReferentialValidator();
        $collection->addValidator($referentialValidator);

        // Add duplicate detector
        $duplicateDetector = new AhgDuplicateDetector();
        $collection->addValidator($duplicateDetector);

        // Add sector-specific validator if exists
        $sectorValidator = self::createSectorValidator($sectorCode);
        if (null !== $sectorValidator) {
            $collection->addValidator($sectorValidator);
        }

        return $collection;
    }

    /**
     * Create a sector-specific validator if one exists.
     */
    protected static function createSectorValidator(string $sectorCode): ?AhgBaseValidator
    {
        $className = __NAMESPACE__.'\\Sectors\\'.ucfirst($sectorCode).'Validator';

        // Also try alternate naming conventions
        $classNames = [
            $className,
            __NAMESPACE__.'\\Sectors\\'.ucfirst(strtolower($sectorCode)).'sValidator',
            __NAMESPACE__.'\\Sectors\\'.strtoupper($sectorCode).'Validator',
        ];

        // Map common sector codes to validator names
        $sectorMap = [
            'archive' => 'ArchivesValidator',
            'archives' => 'ArchivesValidator',
            'museum' => 'MuseumValidator',
            'library' => 'LibraryValidator',
            'gallery' => 'GalleryValidator',
            'dam' => 'DamValidator',
        ];

        if (isset($sectorMap[$sectorCode])) {
            $classNames[] = __NAMESPACE__.'\\Sectors\\'.$sectorMap[$sectorCode];
        }

        foreach ($classNames as $name) {
            if (class_exists($name)) {
                return new $name();
            }
        }

        return null;
    }

    /**
     * Reset all validators for a new file.
     */
    public function reset(): self
    {
        foreach ($this->validators as $validator) {
            $validator->reset();
        }

        $this->report = new AhgValidationReport($this->filename, $this->sectorCode);

        return $this;
    }
}
