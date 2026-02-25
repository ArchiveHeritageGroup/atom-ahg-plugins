<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class ContactRepository
{
    protected string $table = 'registry_contact';

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findByEntity(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('last_name', 'asc')
            ->get()
            ->all();
    }

    public function findPrimary(string $type, int $id): ?object
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->where('is_primary', 1)
            ->first();
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
}
