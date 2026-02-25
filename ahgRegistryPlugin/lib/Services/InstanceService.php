<?php

namespace AhgRegistry\Services;

use Illuminate\Database\Capsule\Manager as DB;

class InstanceService
{
    protected string $culture;
    protected string $table = 'registry_instance';

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
    }

    // =========================================================================
    // Queries
    // =========================================================================

    /**
     * List instances for a given institution.
     */
    public function findByInstitution(int $institutionId): array
    {
        return DB::table($this->table)
            ->where('institution_id', $institutionId)
            ->orderBy('instance_type', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->all();
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Create a new instance.
     */
    public function create(array $data): array
    {
        if (empty($data['institution_id'])) {
            return ['success' => false, 'error' => 'Institution ID is required'];
        }
        if (empty($data['name'])) {
            return ['success' => false, 'error' => 'Instance name is required'];
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Handle JSON fields
        if (isset($data['sync_data']) && is_array($data['sync_data'])) {
            $data['sync_data'] = json_encode($data['sync_data']);
        }

        $id = DB::table($this->table)->insertGetId($data);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Update an existing instance.
     */
    public function update(int $id, array $data): array
    {
        $instance = DB::table($this->table)->where('id', $id)->first();
        if (!$instance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }

        if (isset($data['sync_data']) && is_array($data['sync_data'])) {
            $data['sync_data'] = json_encode($data['sync_data']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        DB::table($this->table)->where('id', $id)->update($data);

        return ['success' => true];
    }

    /**
     * Delete an instance and its sync logs.
     */
    public function delete(int $id): array
    {
        $instance = DB::table($this->table)->where('id', $id)->first();
        if (!$instance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }

        // Remove sync logs
        DB::table('registry_sync_log')
            ->where('instance_id', $id)
            ->delete();

        DB::table($this->table)->where('id', $id)->delete();

        return ['success' => true];
    }

    // =========================================================================
    // Sync Token Management
    // =========================================================================

    /**
     * Generate a new SHA-256 sync token.
     */
    public function generateSyncToken(): string
    {
        return hash('sha256', random_bytes(32) . microtime(true));
    }

    /**
     * Validate a sync token and return the matching instance.
     */
    public function validateSyncToken(string $token): ?object
    {
        if (empty($token) || strlen($token) !== 64) {
            return null;
        }

        return DB::table($this->table)
            ->where('sync_token', $token)
            ->where('sync_enabled', 1)
            ->first();
    }

    // =========================================================================
    // Heartbeat
    // =========================================================================

    /**
     * Update instance from a heartbeat payload.
     */
    public function updateFromHeartbeat(int $instanceId, array $payload): array
    {
        $instance = DB::table($this->table)->where('id', $instanceId)->first();
        if (!$instance) {
            return ['success' => false, 'error' => 'Instance not found'];
        }

        $updateData = [
            'last_heartbeat_at' => date('Y-m-d H:i:s'),
            'status' => 'online',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($payload['software_version'])) {
            $updateData['software_version'] = $payload['software_version'];
        }
        if (isset($payload['record_count'])) {
            $updateData['record_count'] = (int) $payload['record_count'];
        }
        if (isset($payload['digital_object_count'])) {
            $updateData['digital_object_count'] = (int) $payload['digital_object_count'];
        }
        if (isset($payload['storage_gb'])) {
            $updateData['storage_gb'] = (float) $payload['storage_gb'];
        }
        if (!empty($payload['sync_data'])) {
            $updateData['sync_data'] = is_array($payload['sync_data'])
                ? json_encode($payload['sync_data'])
                : $payload['sync_data'];
        }

        DB::table($this->table)->where('id', $instanceId)->update($updateData);

        return ['success' => true];
    }

    /**
     * Find instances without heartbeat beyond threshold and mark offline.
     */
    public function markStaleOffline(int $thresholdDays = 7): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$thresholdDays} days"));

        $count = DB::table($this->table)
            ->where('sync_enabled', 1)
            ->where('status', 'online')
            ->where(function ($q) use ($cutoff) {
                $q->where('last_heartbeat_at', '<', $cutoff)
                  ->orWhereNull('last_heartbeat_at');
            })
            ->update([
                'status' => 'offline',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $count;
    }
}
