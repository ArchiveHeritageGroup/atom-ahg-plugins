<?php

namespace AtomExtensions\SharePoint\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * SharePointUserMappingRepository — CRUD for sharepoint_user_mapping rows.
 *
 * @phase 2.B
 */
class SharePointUserMappingRepository
{
    public function findByAadOid(string $oid): ?\stdClass
    {
        return DB::table('sharepoint_user_mapping')
            ->where('aad_object_id', $oid)
            ->first();
    }

    public function findByAtomUserId(int $userId): ?\stdClass
    {
        return DB::table('sharepoint_user_mapping')
            ->where('atom_user_id', $userId)
            ->first();
    }

    /** @return array<int, \stdClass> */
    public function all(): array
    {
        return DB::table('sharepoint_user_mapping')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function create(array $attributes): int
    {
        $attributes['created_at'] ??= date('Y-m-d H:i:s');
        return (int) DB::table('sharepoint_user_mapping')->insertGetId($attributes);
    }

    public function touchLastSeen(int $id): void
    {
        DB::table('sharepoint_user_mapping')
            ->where('id', $id)
            ->update(['last_seen_at' => date('Y-m-d H:i:s')]);
    }

    public function delete(int $id): void
    {
        DB::table('sharepoint_user_mapping')->where('id', $id)->delete();
    }
}
