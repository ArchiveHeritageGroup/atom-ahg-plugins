<?php

namespace AhgCustomFieldsPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class FieldDefinitionRepository
{
    protected string $table = 'custom_field_definition';

    /**
     * Get all active definitions for an entity type, ordered by sort_order.
     */
    public function getByEntityType(string $entityType): array
    {
        return DB::table($this->table)
            ->where('entity_type', $entityType)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Get definitions grouped by field_group for an entity type.
     */
    public function getByEntityTypeGrouped(string $entityType): array
    {
        $defs = $this->getByEntityType($entityType);
        $grouped = [];
        foreach ($defs as $def) {
            $group = $def->field_group ?: 'General';
            $grouped[$group][] = $def;
        }

        return $grouped;
    }

    /**
     * Get all definitions (active + inactive) for admin listing.
     */
    public function getAll(): array
    {
        return DB::table($this->table)
            ->orderBy('entity_type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * Get all definitions grouped by entity_type for admin.
     */
    public function getAllGroupedByEntity(): array
    {
        $defs = $this->getAll();
        $grouped = [];
        foreach ($defs as $def) {
            $grouped[$def->entity_type][] = $def;
        }

        return $grouped;
    }

    /**
     * Find a definition by ID.
     */
    public function find(int $id): ?object
    {
        $row = DB::table($this->table)->where('id', $id)->first();

        return $row ?: null;
    }

    /**
     * Find a definition by field_key and entity_type.
     */
    public function findByKey(string $fieldKey, string $entityType): ?object
    {
        $row = DB::table($this->table)
            ->where('field_key', $fieldKey)
            ->where('entity_type', $entityType)
            ->first();

        return $row ?: null;
    }

    /**
     * Create a new field definition.
     */
    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        return (int) DB::table($this->table)->insertGetId($data);
    }

    /**
     * Update a field definition.
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->table)->where('id', $id)->update($data);
    }

    /**
     * Soft-delete (deactivate) a field definition.
     */
    public function deactivate(int $id): int
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * Hard-delete a field definition (only if no values exist).
     */
    public function delete(int $id): int
    {
        return DB::table($this->table)->where('id', $id)->delete();
    }

    /**
     * Check if a field_key is unique within an entity_type (excluding a given ID).
     */
    public function isKeyUnique(string $fieldKey, string $entityType, ?int $excludeId = null): bool
    {
        $query = DB::table($this->table)
            ->where('field_key', $fieldKey)
            ->where('entity_type', $entityType);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->count() === 0;
    }

    /**
     * Update sort_order for an ordered array of IDs.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            DB::table($this->table)
                ->where('id', (int) $id)
                ->update(['sort_order' => $index * 10, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * Get distinct field groups.
     */
    public function getFieldGroups(): array
    {
        return DB::table($this->table)
            ->whereNotNull('field_group')
            ->where('field_group', '!=', '')
            ->distinct()
            ->pluck('field_group')
            ->all();
    }

    /**
     * Get distinct entity types in use.
     */
    public function getUsedEntityTypes(): array
    {
        return DB::table($this->table)
            ->where('is_active', 1)
            ->distinct()
            ->pluck('entity_type')
            ->all();
    }

    /**
     * Export definitions for an entity type.
     */
    public function exportByEntityType(string $entityType): array
    {
        return DB::table($this->table)
            ->where('entity_type', $entityType)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($row) {
                unset($row->id, $row->created_at, $row->updated_at);

                return (array) $row;
            })
            ->all();
    }

    /**
     * Count values that exist for a definition.
     */
    public function countValues(int $defId): int
    {
        return DB::table('custom_field_value')
            ->where('field_definition_id', $defId)
            ->count();
    }
}
