<?php

namespace AtomAhgPlugins\ahgCartPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Repository - Laravel Query Builder
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CartRepository
{
    public function getByUserId(int $userId): array
    {
        return DB::table('cart')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function exists(int $userId, int $objectId): bool
    {
        return DB::table('cart')
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->exists();
    }

    public function getByUserAndObject(int $userId, int $objectId): ?object
    {
        return DB::table('cart')
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->first();
    }

    public function getById(int $id): ?object
    {
        return DB::table('cart')->where('id', $id)->first();
    }

    public function add(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return DB::table('cart')->insertGetId($data);
    }

    public function remove(int $id): bool
    {
        return DB::table('cart')->where('id', $id)->delete() > 0;
    }

    public function removeByUserAndObject(int $userId, int $objectId): bool
    {
        return DB::table('cart')
            ->where('user_id', $userId)
            ->where('archival_description_id', $objectId)
            ->delete() > 0;
    }

    public function clearByUser(int $userId): int
    {
        return DB::table('cart')->where('user_id', $userId)->delete();
    }

    public function countByUser(int $userId): int
    {
        return DB::table('cart')->where('user_id', $userId)->count();
    }
}
