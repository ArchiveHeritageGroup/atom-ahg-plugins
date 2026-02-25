<?php

namespace AhgRegistry\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class AttachmentRepository
{
    protected string $table = 'registry_attachment';

    public function findById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function findByEntity(string $type, int $id): array
    {
        return DB::table($this->table)
            ->where('entity_type', $type)
            ->where('entity_id', $id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->all();
    }

    public function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

        return DB::table($this->table)->insertGetId($data);
    }

    public function delete(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function incrementDownloadCount(int $id): void
    {
        DB::table($this->table)->where('id', $id)->increment('download_count');
    }
}
