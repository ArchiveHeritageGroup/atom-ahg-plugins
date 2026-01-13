<?php

namespace AtomAhgPlugins\ahgCartPlugin\Services;

require_once dirname(__DIR__).'/Repositories/CartRepository.php';

use AtomAhgPlugins\ahgCartPlugin\Repositories\CartRepository;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Cart Service - Business Logic
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

            // Check if has digital object
            $hasDigitalObject = DB::table('digital_object')
                ->where('object_id', $item->archival_description_id)
                ->exists();

            $result[] = (object) [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'archival_description_id' => $item->archival_description_id,
                'title' => $title ?? $item->archival_description ?? 'Untitled',
                'slug' => $slug ?? $item->slug,
                'has_digital_object' => $hasDigitalObject,
                'created_at' => $item->created_at,
            ];
        }

        return $result;
    }

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

        return ['success' => true, 'message' => 'Removed from cart.'];
    }

    public function clearAll(int $userId): array
    {
        $count = $this->repository->clearByUser($userId);
        return ['success' => true, 'message' => "Cleared {$count} items from cart."];
    }

    public function isInCart(int $userId, int $objectId): bool
    {
        return $this->repository->exists($userId, $objectId);
    }

    public function getCount(int $userId): int
    {
        return $this->repository->countByUser($userId);
    }
}
