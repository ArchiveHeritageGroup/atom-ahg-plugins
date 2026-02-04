<?php

namespace ahgDataMigrationPlugin\Validation;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Duplicate detection validator with configurable matching strategies.
 *
 * Strategies:
 * - STRATEGY_IDENTIFIER: Match on identifier field
 * - STRATEGY_LEGACY_ID: Match on legacyId field
 * - STRATEGY_TITLE_DATE: Match on title + date combination
 * - STRATEGY_COMPOSITE: Match on multiple configurable fields
 */
class AhgDuplicateDetector extends AhgBaseValidator
{
    public const STRATEGY_IDENTIFIER = 'identifier';
    public const STRATEGY_LEGACY_ID = 'legacyId';
    public const STRATEGY_TITLE_DATE = 'title_date';
    public const STRATEGY_COMPOSITE = 'composite';

    protected string $strategy = self::STRATEGY_IDENTIFIER;

    /** @var array<string> Fields to use for composite matching */
    protected array $compositeFields = [];

    /** @var array<string, array<int>> Map of hash => row numbers for duplicate detection */
    protected array $hashMap = [];

    /** @var array<string, int|null> Map of hash => database ID for existing record detection */
    protected array $existingMap = [];

    protected bool $checkDatabase = true;
    protected bool $caseSensitive = false;

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Duplicate Detection';
    }

    /**
     * Set the matching strategy.
     */
    public function setStrategy(string $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Set composite fields for STRATEGY_COMPOSITE.
     *
     * @param array<string> $fields
     */
    public function setCompositeFields(array $fields): self
    {
        $this->compositeFields = $fields;

        return $this;
    }

    /**
     * Enable/disable database checking for existing records.
     */
    public function setCheckDatabase(bool $check): self
    {
        $this->checkDatabase = $check;

        return $this;
    }

    /**
     * Enable/disable case-sensitive matching.
     */
    public function setCaseSensitive(bool $caseSensitive): self
    {
        $this->caseSensitive = $caseSensitive;

        return $this;
    }

    /**
     * Generate a hash for a row based on the current strategy.
     *
     * @param array<string, mixed> $row
     */
    public function generateHash(array $row): string
    {
        $values = match ($this->strategy) {
            self::STRATEGY_IDENTIFIER => [$row['identifier'] ?? ''],
            self::STRATEGY_LEGACY_ID => [$row['legacyId'] ?? $row['legacy_id'] ?? ''],
            self::STRATEGY_TITLE_DATE => [
                $row['title'] ?? '',
                $row['date'] ?? $row['dateRange'] ?? $row['date_range'] ?? '',
            ],
            self::STRATEGY_COMPOSITE => array_map(
                fn ($field) => $row[$field] ?? '',
                $this->compositeFields
            ),
            default => [$row['identifier'] ?? '']
        };

        // Normalize values
        $normalized = array_map(function ($value) {
            $value = trim((string) $value);

            return $this->caseSensitive ? $value : strtolower($value);
        }, $values);

        return md5(implode('|', $normalized));
    }

    /**
     * Get the key value for display in error messages.
     *
     * @param array<string, mixed> $row
     */
    public function getKeyValue(array $row): string
    {
        return match ($this->strategy) {
            self::STRATEGY_IDENTIFIER => $row['identifier'] ?? '(empty)',
            self::STRATEGY_LEGACY_ID => $row['legacyId'] ?? $row['legacy_id'] ?? '(empty)',
            self::STRATEGY_TITLE_DATE => sprintf(
                '%s [%s]',
                $row['title'] ?? '(no title)',
                $row['date'] ?? $row['dateRange'] ?? $row['date_range'] ?? '(no date)'
            ),
            self::STRATEGY_COMPOSITE => implode(' | ', array_map(
                fn ($field) => $row[$field] ?? '(empty)',
                $this->compositeFields
            )),
            default => $row['identifier'] ?? '(empty)'
        };
    }

    /**
     * Detect duplicates within the file.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function detectWithinFile(array $rows): self
    {
        $this->hashMap = [];

        foreach ($rows as $rowNumber => $row) {
            $hash = $this->generateHash($row);

            if ('' === $hash || 'd41d8cd98f00b204e9800998ecf8427e' === $hash) {
                // Empty hash (md5 of empty string)
                continue;
            }

            if (isset($this->hashMap[$hash])) {
                $keyValue = $this->getKeyValue($row);
                $duplicateRows = $this->hashMap[$hash];

                $this->addRowError(
                    $rowNumber,
                    $this->getStrategyField(),
                    sprintf(
                        "Duplicate record '%s' found (also at row%s %s)",
                        $keyValue,
                        count($duplicateRows) > 1 ? 's' : '',
                        implode(', ', $duplicateRows)
                    ),
                    AhgValidationReport::SEVERITY_WARNING,
                    'duplicate_within_file'
                );

                $this->hashMap[$hash][] = $rowNumber;
            } else {
                $this->hashMap[$hash] = [$rowNumber];
            }
        }

        return $this;
    }

    /**
     * Detect records that already exist in the database.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function detectExisting(array $rows): self
    {
        if (!$this->checkDatabase) {
            return $this;
        }

        foreach ($rows as $rowNumber => $row) {
            $existingId = $this->findExistingRecord($row);

            if (null !== $existingId) {
                $keyValue = $this->getKeyValue($row);

                $this->addRowError(
                    $rowNumber,
                    $this->getStrategyField(),
                    sprintf("Record '%s' already exists in database (ID: %d)", $keyValue, $existingId),
                    AhgValidationReport::SEVERITY_INFO,
                    'duplicate_in_database'
                );
            }
        }

        return $this;
    }

    /**
     * Find an existing record in the database.
     *
     * @param array<string, mixed> $row
     */
    protected function findExistingRecord(array $row): ?int
    {
        return match ($this->strategy) {
            self::STRATEGY_IDENTIFIER => $this->findByIdentifier($row['identifier'] ?? ''),
            self::STRATEGY_LEGACY_ID => $this->findByLegacyId($row['legacyId'] ?? $row['legacy_id'] ?? ''),
            self::STRATEGY_TITLE_DATE => $this->findByTitleDate(
                $row['title'] ?? '',
                $row['date'] ?? $row['dateRange'] ?? $row['date_range'] ?? ''
            ),
            self::STRATEGY_COMPOSITE => $this->findByComposite($row),
            default => $this->findByIdentifier($row['identifier'] ?? '')
        };
    }

    /**
     * Find record by identifier.
     */
    protected function findByIdentifier(string $identifier): ?int
    {
        if ('' === trim($identifier)) {
            return null;
        }

        $record = DB::table('information_object')
            ->where('identifier', $identifier)
            ->first()
        ;

        return $record ? (int) $record->id : null;
    }

    /**
     * Find record by legacy ID.
     */
    protected function findByLegacyId(string $legacyId): ?int
    {
        if ('' === trim($legacyId)) {
            return null;
        }

        $record = DB::table('keymap')
            ->where('source_name', 'legacyId')
            ->where('source_id', $legacyId)
            ->first()
        ;

        return $record ? (int) $record->target_id : null;
    }

    /**
     * Find record by title and date.
     */
    protected function findByTitleDate(string $title, string $date): ?int
    {
        if ('' === trim($title)) {
            return null;
        }

        $query = DB::table('information_object as io')
            ->join('information_object_i18n as io_i18n', 'io.id', '=', 'io_i18n.id')
            ->where('io_i18n.title', $title)
        ;

        if ('' !== trim($date)) {
            $query->join('event as e', 'io.id', '=', 'e.object_id')
                ->join('event_i18n as e_i18n', 'e.id', '=', 'e_i18n.id')
                ->where('e_i18n.date', $date)
            ;
        }

        $record = $query->first();

        return $record ? (int) $record->id : null;
    }

    /**
     * Find record by composite fields.
     *
     * @param array<string, mixed> $row
     */
    protected function findByComposite(array $row): ?int
    {
        if (empty($this->compositeFields)) {
            return null;
        }

        // For composite, first try identifier if present
        if (in_array('identifier', $this->compositeFields, true)) {
            $id = $this->findByIdentifier($row['identifier'] ?? '');
            if (null !== $id) {
                return $id;
            }
        }

        // Then try legacy ID
        if (in_array('legacyId', $this->compositeFields, true) || in_array('legacy_id', $this->compositeFields, true)) {
            $id = $this->findByLegacyId($row['legacyId'] ?? $row['legacy_id'] ?? '');
            if (null !== $id) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Get the primary field name for the current strategy.
     */
    protected function getStrategyField(): string
    {
        return match ($this->strategy) {
            self::STRATEGY_IDENTIFIER => 'identifier',
            self::STRATEGY_LEGACY_ID => 'legacyId',
            self::STRATEGY_TITLE_DATE => 'title',
            self::STRATEGY_COMPOSITE => $this->compositeFields[0] ?? 'identifier',
            default => 'identifier'
        };
    }

    /**
     * Validate entire file for duplicates.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validateFile(array $rows): AhgValidationReport
    {
        $this->report->setTotalRows(count($rows));

        // Detect duplicates within file
        $this->detectWithinFile($rows);

        // Detect existing records in database
        $this->detectExisting($rows);

        return $this->report->finish();
    }

    /**
     * Get summary of duplicates found.
     *
     * @return array{within_file: int, in_database: int}
     */
    public function getDuplicateSummary(): array
    {
        $withinFile = 0;
        $inDatabase = 0;

        foreach ($this->report->getRuleViolations() as $rule => $count) {
            if ('duplicate_within_file' === $rule) {
                $withinFile = $count;
            } elseif ('duplicate_in_database' === $rule) {
                $inDatabase = $count;
            }
        }

        return [
            'within_file' => $withinFile,
            'in_database' => $inDatabase,
        ];
    }
}
