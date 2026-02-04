<?php

namespace ahgDataMigrationPlugin\Validation;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Referential integrity validator for validating parent/child relationships.
 *
 * Validates:
 * - Parent ID references exist (either in file or database)
 * - Hierarchical relationships are valid
 * - No circular references
 * - Referenced entities exist in database
 */
class AhgReferentialValidator extends AhgBaseValidator
{
    /** @var array<string, mixed> Map of identifier => row data for internal file references */
    protected array $identifierMap = [];

    /** @var array<int, string> Map of row number => identifier */
    protected array $rowIdentifiers = [];

    /** @var array<string, array<string>> Map of identifier => child identifiers for cycle detection */
    protected array $childrenMap = [];

    protected string $identifierField = 'legacyId';
    protected string $parentField = 'parentId';

    public function __construct(?array $options = null)
    {
        parent::__construct($options);
        $this->title = 'Referential Integrity Validation';
    }

    /**
     * Set the field names used for referential validation.
     */
    public function setFieldNames(string $identifierField, string $parentField): self
    {
        $this->identifierField = $identifierField;
        $this->parentField = $parentField;

        return $this;
    }

    /**
     * Build the identifier map from all rows (first pass).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function buildIdentifierMap(array $rows): self
    {
        $this->identifierMap = [];
        $this->rowIdentifiers = [];
        $this->childrenMap = [];

        foreach ($rows as $rowNumber => $row) {
            $identifier = $row[$this->identifierField] ?? null;
            $parentId = $row[$this->parentField] ?? null;

            if (null !== $identifier && '' !== trim((string) $identifier)) {
                $identifier = trim((string) $identifier);

                // Check for duplicate identifiers
                if (isset($this->identifierMap[$identifier])) {
                    $this->addRowError(
                        $rowNumber,
                        $this->identifierField,
                        sprintf("Duplicate identifier '%s' found (also at row %d)", $identifier, $this->identifierMap[$identifier]['_row']),
                        AhgValidationReport::SEVERITY_ERROR,
                        'duplicate_identifier'
                    );
                } else {
                    $this->identifierMap[$identifier] = array_merge($row, ['_row' => $rowNumber]);
                    $this->rowIdentifiers[$rowNumber] = $identifier;
                }

                // Build children map for cycle detection
                if (null !== $parentId && '' !== trim((string) $parentId)) {
                    $parentId = trim((string) $parentId);
                    if (!isset($this->childrenMap[$parentId])) {
                        $this->childrenMap[$parentId] = [];
                    }
                    $this->childrenMap[$parentId][] = $identifier;
                }
            }
        }

        return $this;
    }

    /**
     * Validate parent references (second pass).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validateParentReferences(array $rows, bool $checkDatabase = true): self
    {
        foreach ($rows as $rowNumber => $row) {
            $parentId = $row[$this->parentField] ?? null;

            if (null === $parentId || '' === trim((string) $parentId)) {
                continue; // No parent reference, this is a top-level record
            }

            $parentId = trim((string) $parentId);

            // Check if parent exists in file
            if (isset($this->identifierMap[$parentId])) {
                continue; // Parent found in file
            }

            // Check if parent exists in database
            if ($checkDatabase && $this->parentExistsInDatabase($parentId)) {
                continue; // Parent found in database
            }

            $this->addRowError(
                $rowNumber,
                $this->parentField,
                sprintf("Parent reference '%s' not found in file or database", $parentId),
                AhgValidationReport::SEVERITY_ERROR,
                'parent_not_found'
            );
        }

        return $this;
    }

    /**
     * Check if a parent exists in the database.
     */
    protected function parentExistsInDatabase(string $parentId): bool
    {
        // Check by legacyId in keymap
        $exists = DB::table('keymap')
            ->where('source_name', 'legacyId')
            ->where('source_id', $parentId)
            ->exists()
        ;

        if ($exists) {
            return true;
        }

        // Check by identifier in information_object
        $exists = DB::table('information_object')
            ->where('identifier', $parentId)
            ->exists()
        ;

        if ($exists) {
            return true;
        }

        // Check by slug
        return DB::table('slug')
            ->where('slug', $parentId)
            ->where('object_id', '>', 0)
            ->exists();
    }

    /**
     * Detect circular references in parent-child relationships.
     */
    public function detectCircularReferences(): self
    {
        foreach ($this->identifierMap as $identifier => $rowData) {
            $visited = [];
            $current = $identifier;

            while (null !== $current && '' !== $current) {
                if (isset($visited[$current])) {
                    // Found a cycle
                    $cycle = array_slice(array_keys($visited), array_search($current, array_keys($visited), true));
                    $cycle[] = $current;

                    $this->addRowError(
                        $rowData['_row'],
                        $this->parentField,
                        sprintf('Circular reference detected: %s', implode(' -> ', $cycle)),
                        AhgValidationReport::SEVERITY_ERROR,
                        'circular_reference'
                    );

                    break;
                }

                $visited[$current] = true;

                // Get parent of current
                $currentData = $this->identifierMap[$current] ?? null;
                if (null === $currentData) {
                    break;
                }

                $parentId = $currentData[$this->parentField] ?? null;
                $current = (null !== $parentId && '' !== trim((string) $parentId)) ? trim((string) $parentId) : null;
            }
        }

        return $this;
    }

    /**
     * Validate hierarchy depth (optional max depth check).
     */
    public function validateHierarchyDepth(int $maxDepth = 10): self
    {
        foreach ($this->identifierMap as $identifier => $rowData) {
            $depth = 0;
            $current = $identifier;

            while (null !== $current && '' !== $current && $depth <= $maxDepth + 1) {
                $currentData = $this->identifierMap[$current] ?? null;
                if (null === $currentData) {
                    break;
                }

                $parentId = $currentData[$this->parentField] ?? null;
                $current = (null !== $parentId && '' !== trim((string) $parentId)) ? trim((string) $parentId) : null;
                ++$depth;
            }

            if ($depth > $maxDepth) {
                $this->addRowError(
                    $rowData['_row'],
                    $this->parentField,
                    sprintf('Hierarchy depth (%d) exceeds maximum allowed (%d)', $depth, $maxDepth),
                    AhgValidationReport::SEVERITY_WARNING,
                    'hierarchy_depth'
                );
            }
        }

        return $this;
    }

    /**
     * Validate ordering within hierarchy (children should appear after parents in file).
     */
    public function validateHierarchyOrdering(): self
    {
        foreach ($this->identifierMap as $identifier => $rowData) {
            $parentId = $rowData[$this->parentField] ?? null;

            if (null === $parentId || '' === trim((string) $parentId)) {
                continue;
            }

            $parentId = trim((string) $parentId);
            $parentData = $this->identifierMap[$parentId] ?? null;

            if (null === $parentData) {
                continue; // Parent not in file, will be caught by other validation
            }

            // Check if parent appears after child in file
            if ($parentData['_row'] > $rowData['_row']) {
                $this->addRowError(
                    $rowData['_row'],
                    $this->parentField,
                    sprintf("Parent '%s' (row %d) should appear before child '%s' (row %d) in the file", $parentId, $parentData['_row'], $identifier, $rowData['_row']),
                    AhgValidationReport::SEVERITY_WARNING,
                    'hierarchy_ordering'
                );
            }
        }

        return $this;
    }

    /**
     * Validate entire file for referential integrity.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function validateFile(array $rows, bool $checkDatabase = true, int $maxDepth = 10): AhgValidationReport
    {
        $this->report->setTotalRows(count($rows));

        // First pass: build identifier map
        $this->buildIdentifierMap($rows);

        // Second pass: validate references
        $this->validateParentReferences($rows, $checkDatabase);

        // Third pass: detect cycles
        $this->detectCircularReferences();

        // Fourth pass: validate depth
        $this->validateHierarchyDepth($maxDepth);

        // Fifth pass: validate ordering
        $this->validateHierarchyOrdering();

        return $this->report->finish();
    }

    /**
     * Get the identifier map (useful for debugging).
     */
    public function getIdentifierMap(): array
    {
        return $this->identifierMap;
    }
}
