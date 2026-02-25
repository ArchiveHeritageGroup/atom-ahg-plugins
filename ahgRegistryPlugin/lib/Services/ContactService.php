<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class ContactService
{
    protected string $culture;
    protected string $table = 'registry_contact';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Queries
    // =========================================================================

    /**
     * List contacts for an entity (institution or vendor).
     */
    public function findByEntity(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc')
            ->get()
            ->all();
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new contact.
     */
    public function create(array $data): array
    {
        if (empty($data['entity_type']) || empty($data['entity_id'])) {
            return ['success' => false, 'error' => 'Entity type and entity ID are required'];
        }
        if (empty($data['first_name']) || empty($data['last_name'])) {
            return ['success' => false, 'error' => 'First name and last name are required'];
        }
        if (!in_array($data['entity_type'], ['institution', 'vendor'])) {
            return ['success' => false, 'error' => 'Entity type must be institution or vendor'];
        }

        // Handle JSON fields
        if (isset($data['roles']) && is_array($data['roles'])) {
            $data['roles'] = json_encode($data['roles']);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Update an existing contact.
     */
    public function update(int $id, array $data): array
    {
        $contact = DB::table($this->table)->where('id', $id)->first();
        if (!$contact) {
            return ['success' => false, 'error' => 'Contact not found'];
        }

        if (isset($data['roles']) && is_array($data['roles'])) {
            $data['roles'] = json_encode($data['roles']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Delete a contact.
     */
    public function delete(int $id): array
    {
        $contact = DB::table($this->table)->where('id', $id)->first();
        if (!$contact) {
            return ['success' => false, 'error' => 'Contact not found'];
        }

        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Primary Contact
    // =========================================================================

    /**
     * Set a contact as primary, unsetting others for the same entity.
     */
    public function setPrimary(int $id, string $entityType, int $entityId): array
    {
        $contact = DB::table($this->table)->where('id', $id)->first();
        if (!$contact) {
            return ['success' => false, 'error' => 'Contact not found'];
        }

        // Verify the contact belongs to the specified entity
        if ($contact->entity_type !== $entityType || (int) $contact->entity_id !== $entityId) {
            return ['success' => false, 'error' => 'Contact does not belong to the specified entity'];
        }

        // Unset all primary contacts for this entity
        DB::table($this->table)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('is_primary', 1)
            ->update([
                'is_primary' => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        // Set the specified contact as primary
        DB::table($this->table)->where('id', $id)->update([
            'is_primary' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true];
    }
}
