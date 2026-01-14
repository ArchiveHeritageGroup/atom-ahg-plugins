<?php

namespace AtomAhgPlugins\ahgCartPlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * E-Commerce Repository - Database operations for orders, payments, products
 *
 * @author Johan Pieterse <johan@theahg.co.za>
 */
class EcommerceRepository
{
    // ========================================================================
    // E-COMMERCE SETTINGS
    // ========================================================================

    public function getSettings(?int $repositoryId = null): ?object
    {
        return DB::table('ahg_ecommerce_settings')
            ->where('repository_id', $repositoryId)
            ->first();
    }

    public function isEcommerceEnabled(?int $repositoryId = null): bool
    {
        $settings = $this->getSettings($repositoryId);
        return $settings && $settings->is_enabled == 1;
    }

    public function saveSettings(array $data): int
    {
        $repositoryId = $data['repository_id'] ?? null;
        $existing = $this->getSettings($repositoryId);

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            DB::table('ahg_ecommerce_settings')
                ->where('id', $existing->id)
                ->update($data);
            return $existing->id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return DB::table('ahg_ecommerce_settings')->insertGetId($data);
    }

    // ========================================================================
    // PRODUCT TYPES
    // ========================================================================

    public function getProductTypes(bool $activeOnly = true): array
    {
        $query = DB::table('ahg_product_type');
        
        if ($activeOnly) {
            $query->where('is_active', 1);
        }
        
        return $query->orderBy('sort_order')->get()->all();
    }

    public function getProductType(int $id): ?object
    {
        return DB::table('ahg_product_type')->where('id', $id)->first();
    }

    // ========================================================================
    // PRODUCT PRICING
    // ========================================================================

    public function getPricing(?int $repositoryId = null, bool $activeOnly = true): array
    {
        $query = DB::table('ahg_product_pricing as p')
            ->join('ahg_product_type as t', 'p.product_type_id', '=', 't.id')
            ->select('p.*', 't.name as type_name', 't.is_digital', 't.requires_shipping')
            ->where(function($q) use ($repositoryId) {
                $q->where('p.repository_id', $repositoryId)
                  ->orWhereNull('p.repository_id');
            });

        if ($activeOnly) {
            $query->where('p.is_active', 1)->where('t.is_active', 1);
        }

        return $query->orderBy('t.sort_order')->get()->all();
    }

    public function getPrice(int $productTypeId, ?int $repositoryId = null): ?object
    {
        // First try repository-specific price
        $price = DB::table('ahg_product_pricing')
            ->where('product_type_id', $productTypeId)
            ->where('repository_id', $repositoryId)
            ->where('is_active', 1)
            ->first();

        // Fall back to global price
        if (!$price) {
            $price = DB::table('ahg_product_pricing')
                ->where('product_type_id', $productTypeId)
                ->whereNull('repository_id')
                ->where('is_active', 1)
                ->first();
        }

        return $price;
    }

    public function savePricing(array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Check if pricing already exists for this product_type_id and repository_id
        $existing = DB::table('ahg_product_pricing')
            ->where('product_type_id', $data['product_type_id'])
            ->where(function($query) use ($data) {
                if (isset($data['repository_id']) && $data['repository_id'] !== null) {
                    $query->where('repository_id', $data['repository_id']);
                } else {
                    $query->whereNull('repository_id');
                }
            })
            ->first();

        if ($existing) {
            // Update existing record
            DB::table('ahg_product_pricing')
                ->where('id', $existing->id)
                ->update($data);
            return $existing->id;
        }

        // Insert new record
        $data['created_at'] = date('Y-m-d H:i:s');
        return DB::table('ahg_product_pricing')->insertGetId($data);
    }

    // ========================================================================
    // ORDERS
    // ========================================================================

    public function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        return "{$prefix}-{$date}-{$random}";
    }

    public function createOrder(array $data): int
    {
        $data['order_number'] = $data['order_number'] ?? $this->generateOrderNumber();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return DB::table('ahg_order')->insertGetId($data);
    }

    public function getOrder(int $id): ?object
    {
        return DB::table('ahg_order')->where('id', $id)->first();
    }

    public function getOrderByNumber(string $orderNumber): ?object
    {
        return DB::table('ahg_order')->where('order_number', $orderNumber)->first();
    }

    public function getUserOrders(int $userId, ?string $status = null): array
    {
        $query = DB::table('ahg_order')
            ->where('user_id', $userId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get()->all();
    }

    public function updateOrderStatus(int $orderId, string $status, ?array $additionalData = null): bool
    {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === 'paid') {
            $data['paid_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'cancelled') {
            $data['cancelled_at'] = date('Y-m-d H:i:s');
        }

        if ($additionalData) {
            $data = array_merge($data, $additionalData);
        }

        return DB::table('ahg_order')->where('id', $orderId)->update($data) > 0;
    }

    // ========================================================================
    // ORDER ITEMS
    // ========================================================================

    public function addOrderItem(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        return DB::table('ahg_order_item')->insertGetId($data);
    }

    public function getOrderItems(int $orderId): array
    {
        return DB::table('ahg_order_item')
            ->where('order_id', $orderId)
            ->get()
            ->all();
    }

    // ========================================================================
    // PAYMENTS
    // ========================================================================

    public function createPayment(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        return DB::table('ahg_payment')->insertGetId($data);
    }

    public function getPayment(int $id): ?object
    {
        return DB::table('ahg_payment')->where('id', $id)->first();
    }

    public function getOrderPayments(int $orderId): array
    {
        return DB::table('ahg_payment')
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    public function updatePayment(int $paymentId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return DB::table('ahg_payment')->where('id', $paymentId)->update($data) > 0;
    }

    // ========================================================================
    // DOWNLOAD TOKENS
    // ========================================================================

    public function createDownloadToken(int $orderItemId, int $expiryDays = 7, int $maxDownloads = 5): string
    {
        $token = bin2hex(random_bytes(32));
        
        DB::table('ahg_download_token')->insert([
            'order_item_id' => $orderItemId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryDays} days")),
            'max_downloads' => $maxDownloads,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $token;
    }

    public function validateDownloadToken(string $token): ?object
    {
        $tokenRecord = DB::table('ahg_download_token')
            ->where('token', $token)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->whereRaw('download_count < max_downloads')
            ->first();

        return $tokenRecord;
    }

    public function incrementDownloadCount(string $token, ?string $ipAddress = null): bool
    {
        return DB::table('ahg_download_token')
            ->where('token', $token)
            ->update([
                'download_count' => DB::raw('download_count + 1'),
                'ip_address' => $ipAddress,
            ]) > 0;
    }

    // ========================================================================
    // ADMIN REPORTING
    // ========================================================================

    public function getOrderStats(?int $repositoryId = null, ?string $startDate = null, ?string $endDate = null): object
    {
        $query = DB::table('ahg_order');

        if ($repositoryId) {
            $query->where('repository_id', $repositoryId);
        }

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return (object) [
            'total_orders' => (clone $query)->count(),
            'pending_orders' => (clone $query)->where('status', 'pending')->count(),
            'paid_orders' => (clone $query)->where('status', 'paid')->count(),
            'completed_orders' => (clone $query)->where('status', 'completed')->count(),
            'total_revenue' => (clone $query)->whereIn('status', ['paid', 'completed'])->sum('total'),
        ];
    }
}
