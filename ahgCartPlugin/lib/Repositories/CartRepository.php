<?php

namespace AtomAhgPlugins\ahgCartPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Repository - Database operations for shopping cart
 * Supports both user-based and session-based (guest) carts
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CartRepository
{
    protected string $table = 'cart';

    /**
     * Get cart items by user ID or session ID
     */
    public function getCart($userId = null, $sessionId = null): array
    {
        $query = DB::table($this->table)->whereNull('completed_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return [];
        }

        return $query->orderBy('created_at', 'desc')->get()->all();
    }

    /**
     * Get by user ID (backward compatibility)
     */
    public function getByUserId($userId): array
    {
        return $this->getCart($userId, null);
    }

    /**
     * Get by session ID (guests)
     */
    public function getBySessionId(string $sessionId): array
    {
        return $this->getCart(null, $sessionId);
    }

    public function getById(int $id): ?object
    {
        return DB::table($this->table)->where('id', $id)->first();
    }

    public function exists($userId, int $objectId, $sessionId = null): bool
    {
        $query = DB::table($this->table)
            ->where('archival_description_id', $objectId)
            ->whereNull('completed_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->exists();
    }

    public function add(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
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

    public function clearCart($userId = null, $sessionId = null): int
    {
        $query = DB::table($this->table)->whereNull('completed_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return 0;
        }

        return $query->delete();
    }

    public function clearByUserId($userId): int
    {
        return $this->clearCart($userId, null);
    }

    public function markCompleted($userId = null, $sessionId = null): int
    {
        $query = DB::table($this->table)->whereNull('completed_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return 0;
        }

        return $query->update([
            'completed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getCount($userId = null, $sessionId = null): int
    {
        $query = DB::table($this->table)->whereNull('completed_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return 0;
        }

        return $query->count();
    }
}
