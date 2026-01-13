<?php

namespace AtomAhgPlugins\ahgCartPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Repository - Database operations for shopping cart
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CartRepository
{
    protected string $table = 'cart';

    public function getByUserId(int $userId): array
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function getById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function exists(int $userId, int $objectId): bool
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->whereNull('completed_at')
            ->exists();
    }

    public function add(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return DB::table($this->table)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return DB::table($this->table)->where('id', $id)->update($data) > 0;
    }

    public function remove(int $id): bool
    {
        return DB::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function clearByUserId(int $userId): int
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->delete();
    }

    public function markCompleted(int $userId): int
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->update([
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    public function getCount(int $userId): int
    {
        return DB::table($this->table)
            ->where('user_id', $userId)
            ->whereNull('completed_at')
            ->count();
    }
}
