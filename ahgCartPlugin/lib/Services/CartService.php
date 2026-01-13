<?php

namespace AtomAhgPlugins\ahgCartPlugin\Services;

require_once dirname(__DIR__) . '/Repositories/CartRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Repositories\CartRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Service - Business logic for shopping cart
 * Supports both Standard (Request to Publish) and E-Commerce modes
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class CartService
{
    private CartRepository $repository;

    public function __construct()
    {
        $this->repository = new CartRepository();
    }

    /**
     * Check if e-commerce is enabled for any repository
     */
    public function isEcommerceEnabled(?int $repositoryId = null): bool
    {
        $settings = DB::table('ahg_ecommerce_settings')
            ->where(function($q) use ($repositoryId) {
                if ($repositoryId) {
                    $q->where('repository_id', $repositoryId);
                } else {
                    $q->whereNull('repository_id');
                }
            })
            ->where('is_enabled', 1)
            ->first();

        return $settings !== null;
    }

    /**
     * Get user's cart items with full details
     */
    public function getUserCart(int $userId): array
    {
        $items = $this->repository->getByUserId($userId);
        $result = [];

        foreach ($items as $item) {
            $title = DB::table('information_object_i18n')
                ->where('id', $item->archival_description_id)
                ->where('culture', 'en')
                ->value('title');

            $slug = DB::table('slug')
                ->where('object_id', $item->archival_description_id)
                ->value('slug');

            $hasDigitalObject = DB::table('digital_object')
                ->where('object_id', $item->archival_description_id)
                ->exists();

            // Get product info if set
            $productName = null;
            if (isset($item->product_type_id) && $item->product_type_id) {
                $productName = DB::table('ahg_product_type')
                    ->where('id', $item->product_type_id)
                    ->value('name');
            }

            $result[] = (object) [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'archival_description_id' => $item->archival_description_id,
                'title' => $title ?? $item->archival_description ?? 'Untitled',
                'slug' => $slug ?? $item->slug,
                'has_digital_object' => $hasDigitalObject,
                'product_type_id' => $item->product_type_id ?? null,
                'product_name' => $productName,
                'quantity' => $item->quantity ?? 1,
                'unit_price' => $item->unit_price ?? null,
                'notes' => $item->notes ?? null,
                'created_at' => $item->created_at,
            ];
        }

        return $result;
    }

    /**
     * Add item to cart
     */
    public function addToCart(int $userId, int $objectId, ?string $title = null, ?string $slug = null): array
    {
        if ($this->repository->exists($userId, $objectId)) {
            return ['success' => false, 'message' => 'Item is already in your cart.'];
        }

        if (!$title) {
            $title = DB::table('information_object_i18n')
                ->where('id', $objectId)
                ->where('culture', 'en')
                ->value('title') ?? 'Untitled';
        }

        if (!$slug) {
            $slug = DB::table('slug')
                ->where('object_id', $objectId)
                ->value('slug');
        }

        $id = $this->repository->add([
            'user_id' => $userId,
            'archival_description_id' => $objectId,
            'archival_description' => $title,
            'slug' => $slug,
        ]);

        return ['success' => true, 'message' => 'Added to cart.', 'id' => $id];
    }

    /**
     * Remove item from cart
     */
    public function removeFromCart(int $userId, int $cartId): array
    {
        $item = $this->repository->getById($cartId);

        if (!$item) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        if ($item->user_id != $userId) {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        $this->repository->remove($cartId);
        return ['success' => true, 'message' => 'Item removed from cart.'];
    }

    /**
     * Clear all cart items
     */
    public function clearAll(int $userId): array
    {
        $count = $this->repository->clearByUserId($userId);
        return ['success' => true, 'message' => "Cleared {$count} item(s) from cart."];
    }

    /**
     * Update cart item product selection
     */
    public function updateItemProduct(int $cartId, int $productTypeId, int $userId): array
    {
        $item = $this->repository->getById($cartId);
        
        if (!$item || $item->user_id != $userId) {
            return ['success' => false, 'message' => 'Item not found.'];
        }

        $pricing = DB::table('ahg_product_pricing')
            ->where('product_type_id', $productTypeId)
            ->where('is_active', 1)
            ->first();

        if (!$pricing) {
            return ['success' => false, 'message' => 'Product not available.'];
        }

        $this->repository->update($cartId, [
            'product_type_id' => $productTypeId,
            'unit_price' => $pricing->price,
        ]);

        return ['success' => true, 'message' => 'Product updated.', 'price' => $pricing->price];
    }

    /**
     * Get cart count for user
     */
    public function getCartCount(int $userId): int
    {
        return $this->repository->getCount($userId);
    }

    /**
     * Check if item is in cart
     */
    public function isInCart(int $userId, int $objectId): bool
    {
        return $this->repository->exists($userId, $objectId);
    }

    /**
     * Get cart item by object ID
     */
    public function getCartItem(int $userId, int $objectId): ?object
    {
        $items = $this->repository->getByUserId($userId);
        foreach ($items as $item) {
            if ($item->archival_description_id == $objectId) {
                return $item;
            }
        }
        return null;
    }
}
