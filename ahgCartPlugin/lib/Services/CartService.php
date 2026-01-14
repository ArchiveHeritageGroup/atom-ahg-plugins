<?php

namespace AtomAhgPlugins\ahgCartPlugin\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Service - Handles cart operations for both users and guests
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CartService
{
    /**
     * Add item to cart
     */
    public function addToCart($userId, $objectId, $title, $slug, $sessionId = null): array
    {
        // Check if item already in cart
        $query = DB::table('cart')
            ->where('archival_description_id', $objectId)
            ->whereNull('completed_at');
        
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }
        
        $existing = $query->first();
        
        if ($existing) {
            return ['success' => false, 'message' => 'Item is already in your cart.'];
        }
        
        // Add to cart
        $cartId = DB::table('cart')->insertGetId([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'archival_description_id' => $objectId,
            'archival_description' => $title ?? 'Untitled',
            'slug' => $slug,
            'quantity' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        return ['success' => true, 'message' => 'Item added to cart.', 'cart_id' => $cartId];
    }
    
    /**
     * Get cart items for user or session
     */
    public function getCart($userId = null, $sessionId = null): array
    {
        $query = DB::table('cart')
            ->whereNull('completed_at');
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return [];
        }
        
        return $query->orderBy('created_at', 'desc')->get()->toArray();
    }
    
    /**
     * Get cart for user (backward compatibility)
     */
    public function getUserCart($userId): array
    {
        return $this->getCart($userId, null);
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($cartId, $userId = null, $sessionId = null): bool
    {
        $query = DB::table('cart')->where('id', $cartId);
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        
        return $query->delete() > 0;
    }
    
    /**
     * Clear all cart items
     */
    public function clearAll($userId = null, $sessionId = null): int
    {
        $query = DB::table('cart')->whereNull('completed_at');
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        
        return $query->delete();
    }
    
    /**
     * Merge guest cart with user cart after login
     */
    public function mergeGuestCart($sessionId, $userId): int
    {
        if (empty($sessionId) || empty($userId)) {
            return 0;
        }
        
        // Get guest cart items
        $guestItems = DB::table('cart')
            ->where('session_id', $sessionId)
            ->whereNull('completed_at')
            ->get();
        
        $merged = 0;
        
        foreach ($guestItems as $item) {
            // Check if user already has this item
            $exists = DB::table('cart')
                ->where('user_id', $userId)
                ->where('archival_description_id', $item->archival_description_id)
                ->whereNull('completed_at')
                ->exists();
            
            if (!$exists) {
                // Transfer to user
                DB::table('cart')
                    ->where('id', $item->id)
                    ->update([
                        'user_id' => $userId,
                        'session_id' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                $merged++;
            } else {
                // Delete duplicate
                DB::table('cart')->where('id', $item->id)->delete();
            }
        }
        
        return $merged;
    }
    
    /**
     * Get cart count
     */
    public function getCartCount($userId = null, $sessionId = null): int
    {
        $query = DB::table('cart')->whereNull('completed_at');
        
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return 0;
        }
        
        return $query->count();
    }

    /**
     * Clear all cart items for a session (guest users)
     */
    public function clearAllBySession($sessionId): int
    {
        return DB::table('cart')
            ->where('session_id', $sessionId)
            ->whereNull('completed_at')
            ->delete();
    }
}