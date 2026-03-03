<?php

namespace AhgCustomFieldsPlugin\Repository;

use Illuminate\Database\Capsule\Manager as DB;

class FieldValueRepository
{
    protected string $table = 'custom_field_value';

    /**
     * Get all values for an object, keyed by field_definition_id.
     */
    public function getByObject(int $objectId): array
    {
        return DB::table($this->table)
            ->where('object_id', $objectId)
            ->orderBy('field_definition_id')
            ->orderBy('sequence')
            ->get()
            ->all();
    }

    /**
     * Get all values for an object joined with definitions for a given entity type.
     */
    public function getByObjectAndEntity(int $objectId, string $entityType): array
    {
        return DB::table($this->table . ' as v')
            ->join('custom_field_definition as d', 'd.id', '=', 'v.field_definition_id')
            ->where('v.object_id', $objectId)
            ->where('d.entity_type', $entityType)
            ->where('d.is_active', 1)
            ->orderBy('d.sort_order')
            ->orderBy('v.sequence')
            ->select('v.*', 'd.field_key', 'd.field_label', 'd.field_type', 'd.field_group',
                'd.dropdown_taxonomy', 'd.is_repeatable', 'd.is_visible_public', 'd.is_visible_edit')
            ->get()
            ->all();
    }

    /**
     * Get values for a specific definition and object.
     */
    public function getByDefinitionAndObject(int $defId, int $objectId): array
    {
        return DB::table($this->table)
            ->where('field_definition_id', $defId)
            ->where('object_id', $objectId)
            ->orderBy('sequence')
            ->get()
            ->all();
    }

    /**
     * Upsert a single value. For non-repeatable fields, replaces existing.
     */
    public function upsertValue(int $defId, int $objectId, array $valueData, int $sequence = 0): void
    {
        $existing = DB::table($this->table)
            ->where('field_definition_id', $defId)
            ->where('object_id', $objectId)
            ->where('sequence', $sequence)
            ->first();

        $valueData['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            DB::table($this->table)
                ->where('id', $existing->id)
                ->update($valueData);
        } else {
            $valueData['field_definition_id'] = $defId;
            $valueData['object_id'] = $objectId;
            $valueData['sequence'] = $sequence;
            $valueData['created_at'] = date('Y-m-d H:i:s');
            DB::table($this->table)->insert($valueData);
        }
    }

    /**
     * Delete all values for a definition + object (used before re-saving repeatable).
     */
    public function deleteByDefinitionAndObject(int $defId, int $objectId): int
    {
        return DB::table($this->table)
            ->where('field_definition_id', $defId)
            ->where('object_id', $objectId)
            ->delete();
    }

    /**
     * Delete all values for an object (cleanup on entity delete).
     */
    public function deleteByObject(int $objectId): int
    {
        return DB::table($this->table)
            ->where('object_id', $objectId)
            ->delete();
    }

    /**
     * Delete all values for a definition (cleanup on definition delete).
     */
    public function deleteByDefinition(int $defId): int
    {
        return DB::table($this->table)
            ->where('field_definition_id', $defId)
            ->delete();
    }

    /**
     * Count values for a specific definition.
     */
    public function countByDefinition(int $defId): int
    {
        return DB::table($this->table)
            ->where('field_definition_id', $defId)
            ->count();
    }

    /**
     * Count objects that have values for a specific definition.
     */
    public function countObjectsByDefinition(int $defId): int
    {
        return DB::table($this->table)
            ->where('field_definition_id', $defId)
            ->distinct()
            ->count('object_id');
    }
}
