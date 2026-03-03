<?php

namespace AhgCustomFieldsPlugin\Service;

use AhgCustomFieldsPlugin\Repository\FieldDefinitionRepository;
use AhgCustomFieldsPlugin\Repository\FieldValueRepository;
use Illuminate\Database\Capsule\Manager as DB;

class CustomFieldService
{
    protected FieldDefinitionRepository $defRepo;
    protected FieldValueRepository $valRepo;

    public function __construct()
    {
        $this->defRepo = new FieldDefinitionRepository();
        $this->valRepo = new FieldValueRepository();
    }

    // ----------------------------------------------------------------
    // Definition queries
    // ----------------------------------------------------------------

    /**
     * Get active field definitions for an entity type.
     */
    public function getDefinitionsForEntity(string $entityType): array
    {
        return $this->defRepo->getByEntityType($entityType);
    }

    /**
     * Get definitions grouped by field_group for an entity type.
     */
    public function getDefinitionsByGroup(string $entityType): array
    {
        return $this->defRepo->getByEntityTypeGrouped($entityType);
    }

    /**
     * Get a single definition by ID.
     */
    public function getDefinition(int $id): ?object
    {
        return $this->defRepo->find($id);
    }

    // ----------------------------------------------------------------
    // Value queries
    // ----------------------------------------------------------------

    /**
     * Get all custom field values for an object, keyed by field_key.
     */
    public function getValuesForObject(int $objectId, string $entityType): array
    {
        $rows = $this->valRepo->getByObjectAndEntity($objectId, $entityType);
        $result = [];

        foreach ($rows as $row) {
            $key = $row->field_key;
            $value = $this->extractValue($row);

            if ($row->is_repeatable) {
                $result[$key][] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get raw value rows for an object and entity type.
     */
    public function getRawValuesForObject(int $objectId, string $entityType): array
    {
        return $this->valRepo->getByObjectAndEntity($objectId, $entityType);
    }

    // ----------------------------------------------------------------
    // Value persistence
    // ----------------------------------------------------------------

    /**
     * Save field values from form submission.
     *
     * $fieldValues format: ['field_key' => 'value', 'repeatable_key' => ['val1', 'val2']]
     */
    public function saveValues(int $objectId, string $entityType, array $fieldValues): void
    {
        $definitions = $this->defRepo->getByEntityType($entityType);

        foreach ($definitions as $def) {
            $key = $def->field_key;
            if (!array_key_exists($key, $fieldValues)) {
                // Boolean fields: unchecked checkboxes don't submit
                if ($def->field_type === 'boolean') {
                    $this->saveSingleValue($def, $objectId, false);
                }
                continue;
            }

            $rawValue = $fieldValues[$key];

            if ($def->is_repeatable && is_array($rawValue)) {
                $this->saveRepeatableValues($def, $objectId, $rawValue);
            } else {
                $this->saveSingleValue($def, $objectId, $rawValue);
            }
        }
    }

    /**
     * Save a single (non-repeatable) field value.
     */
    protected function saveSingleValue(object $def, int $objectId, $rawValue): void
    {
        $valueData = $this->buildValueData($def, $rawValue);
        $this->valRepo->upsertValue((int) $def->id, $objectId, $valueData, 0);
    }

    /**
     * Save repeatable field values — delete existing then re-insert.
     */
    protected function saveRepeatableValues(object $def, int $objectId, array $rawValues): void
    {
        // Remove empty entries
        $rawValues = array_values(array_filter($rawValues, function ($v) {
            return $v !== '' && $v !== null;
        }));

        $this->valRepo->deleteByDefinitionAndObject((int) $def->id, $objectId);

        foreach ($rawValues as $seq => $rawValue) {
            $valueData = $this->buildValueData($def, $rawValue);
            $this->valRepo->upsertValue((int) $def->id, $objectId, $valueData, $seq);
        }
    }

    /**
     * Build the value column data based on field type.
     */
    protected function buildValueData(object $def, $rawValue): array
    {
        // Reset all value columns
        $data = [
            'value_text' => null,
            'value_number' => null,
            'value_date' => null,
            'value_boolean' => null,
            'value_dropdown' => null,
        ];

        switch ($def->field_type) {
            case 'text':
            case 'textarea':
            case 'url':
                $data['value_text'] = is_string($rawValue) ? trim($rawValue) : null;
                break;
            case 'number':
                $data['value_number'] = is_numeric($rawValue) ? (float) $rawValue : null;
                break;
            case 'date':
                $data['value_date'] = !empty($rawValue) ? $rawValue : null;
                break;
            case 'boolean':
                $data['value_boolean'] = $rawValue ? 1 : 0;
                break;
            case 'dropdown':
                $data['value_dropdown'] = is_string($rawValue) ? trim($rawValue) : null;
                break;
        }

        return $data;
    }

    /**
     * Extract the typed value from a value row.
     */
    protected function extractValue(object $row): mixed
    {
        switch ($row->field_type) {
            case 'text':
            case 'textarea':
            case 'url':
                return $row->value_text;
            case 'number':
                return $row->value_number;
            case 'date':
                return $row->value_date;
            case 'boolean':
                return (bool) $row->value_boolean;
            case 'dropdown':
                return $row->value_dropdown;
            default:
                return $row->value_text;
        }
    }

    /**
     * Delete all custom field values for an object.
     */
    public function deleteValuesForObject(int $objectId): void
    {
        $this->valRepo->deleteByObject($objectId);
    }

    // ----------------------------------------------------------------
    // Definition CRUD (admin)
    // ----------------------------------------------------------------

    /**
     * Create a field definition.
     */
    public function createDefinition(array $data): int
    {
        return $this->defRepo->create($data);
    }

    /**
     * Update a field definition.
     */
    public function updateDefinition(int $id, array $data): void
    {
        $this->defRepo->update($id, $data);
    }

    /**
     * Soft-delete a field definition.
     */
    public function deleteDefinition(int $id): void
    {
        $this->defRepo->deactivate($id);
    }

    /**
     * Hard-delete a definition (only if no values exist).
     */
    public function hardDeleteDefinition(int $id): bool
    {
        $valueCount = $this->defRepo->countValues($id);
        if ($valueCount > 0) {
            return false;
        }
        $this->defRepo->delete($id);

        return true;
    }

    /**
     * Reorder definitions by ID array.
     */
    public function reorderDefinitions(array $orderedIds): void
    {
        $this->defRepo->reorder($orderedIds);
    }

    // ----------------------------------------------------------------
    // Validation
    // ----------------------------------------------------------------

    /**
     * Validate a value against its field definition rules.
     */
    public function validateValue(object $definition, $value): bool|string
    {
        // Required check
        if ($definition->is_required && ($value === null || $value === '')) {
            return $definition->field_label . ' is required.';
        }

        // Skip further validation if empty and not required
        if ($value === null || $value === '') {
            return true;
        }

        // Type-specific validation
        switch ($definition->field_type) {
            case 'number':
                if (!is_numeric($value)) {
                    return $definition->field_label . ' must be a number.';
                }
                break;
            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return $definition->field_label . ' must be a valid date (YYYY-MM-DD).';
                }
                break;
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return $definition->field_label . ' must be a valid URL.';
                }
                break;
        }

        // Custom regex validation
        if (!empty($definition->validation_rule)) {
            $rule = $definition->validation_rule;

            // Handle max:N rule
            if (preg_match('/^max:(\d+)$/', $rule, $m)) {
                if (strlen((string) $value) > (int) $m[1]) {
                    return $definition->field_label . ' must be at most ' . $m[1] . ' characters.';
                }
            }
            // Handle regex rule
            elseif (preg_match('/^regex:(.+)$/', $rule, $m)) {
                if (!preg_match($m[1], (string) $value)) {
                    return $definition->field_label . ' does not match the required format.';
                }
            }
        }

        return true;
    }

    // ----------------------------------------------------------------
    // Import / Export
    // ----------------------------------------------------------------

    /**
     * Export field definitions for an entity type as array.
     */
    public function exportDefinitions(string $entityType): array
    {
        return $this->defRepo->exportByEntityType($entityType);
    }

    /**
     * Import field definitions from an array (e.g., from JSON).
     */
    public function importDefinitions(array $definitions): int
    {
        $count = 0;
        foreach ($definitions as $def) {
            $entityType = $def['entity_type'] ?? '';
            $fieldKey = $def['field_key'] ?? '';

            if (empty($entityType) || empty($fieldKey)) {
                continue;
            }

            // Skip if already exists
            if (!$this->defRepo->isKeyUnique($fieldKey, $entityType)) {
                continue;
            }

            // Remove any leftover ID
            unset($def['id']);
            $this->defRepo->create($def);
            $count++;
        }

        return $count;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Get available entity types.
     */
    public function getEntityTypes(): array
    {
        return [
            'informationobject' => 'Information Object',
            'actor' => 'Actor / Authority Record',
            'accession' => 'Accession',
            'repository' => 'Repository',
            'donor' => 'Donor',
            'function' => 'Function',
        ];
    }

    /**
     * Get available field types.
     */
    public function getFieldTypes(): array
    {
        return [
            'text' => 'Text',
            'textarea' => 'Textarea',
            'date' => 'Date',
            'number' => 'Number',
            'boolean' => 'Boolean (Checkbox)',
            'dropdown' => 'Dropdown (from ahg_dropdown)',
            'url' => 'URL',
        ];
    }

    /**
     * Get dropdown taxonomies from ahg_dropdown.
     */
    public function getDropdownTaxonomies(): array
    {
        try {
            return DB::table('ahg_dropdown')
                ->select('taxonomy', 'taxonomy_label')
                ->where('is_active', 1)
                ->distinct()
                ->orderBy('taxonomy_label')
                ->get()
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get dropdown options for a specific taxonomy.
     */
    public function getDropdownOptions(string $taxonomy): array
    {
        try {
            return DB::table('ahg_dropdown')
                ->where('taxonomy', $taxonomy)
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->get()
                ->all();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generate a machine-friendly key from a label.
     */
    public function generateFieldKey(string $label): string
    {
        $key = strtolower(trim($label));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);
        $key = trim($key, '_');

        return substr($key, 0, 100);
    }
}
