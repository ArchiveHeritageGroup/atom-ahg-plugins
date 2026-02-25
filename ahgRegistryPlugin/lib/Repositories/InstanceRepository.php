<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class InstanceRepository
{
    protected string $table = 'registry_instance';

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findByInstitution(int $institutionId): array
    {
        return DB::table($this->table)
            ->where('institution_id', $institutionId)
            ->orderBy('instance_type', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->all();
    }

    public function findBySyncToken(string $token): ?object
    {
        return DB::table($this->table)->where('sync_token', $token)->first();
    }

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->table)->where('id', $id)->update($data) >= 0;
    }

    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function updateHeartbeat(int $id, ?array $syncData = null): bool
    {
        $data = [
            'last_heartbeat_at' => date('Y-m-d H:i:s'),
            'status' => 'online',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($syncData !== null) {
            $data['sync_data'] = json_encode($syncData);
            $data['last_sync_at'] = date('Y-m-d H:i:s');
        }

        return DB::table($this->table)->where('id', $id)->update($data) >= 0;
    }

    public function getStaleInstances(int $thresholdDays = 7): array
    {
        $threshold = date('Y-m-d H:i:s', strtotime("-{$thresholdDays} days"));

        return DB::table($this->table)
            ->where('status', '!=', 'decommissioned')
            ->where('sync_enabled', 1)
            ->where(function ($q) use ($threshold) {
                $q->where('last_heartbeat_at', '<', $threshold)
                  ->orWhereNull('last_heartbeat_at');
            })
            ->get()
            ->all();
    }

    public function countByStatus(): array
    {
        return DB::table($this->table)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();
    }
}
