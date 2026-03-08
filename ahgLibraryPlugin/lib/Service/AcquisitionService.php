<?php

declare(strict_types=1);

/**
 * AcquisitionService
 *
 * Manages library acquisitions — purchase orders, order lines, budgets.
 * All statuses driven by ahg_dropdown taxonomies.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class AcquisitionService
{
    protected static ?AcquisitionService $instance = null;
    protected Logger $logger;

    public function __construct()
    {
        $this->initLogger();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function initLogger(): void
    {
        $this->logger = new Logger('library.acquisition');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // PURCHASE ORDERS
    // ========================================================================

    /**
     * Create a purchase order.
     */
    public function createOrder(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_order')->insertGetId([
            'order_number'   => $data['order_number'] ?? $this->generateOrderNumber(),
            'vendor_name'    => $data['vendor_name'],
            'vendor_account' => $data['vendor_account'] ?? null,
            'order_date'     => $data['order_date'] ?? date('Y-m-d'),
            'order_type'     => $data['order_type'] ?? 'purchase',
            'order_status'   => 'pending',
            'budget_id'      => $data['budget_id'] ?? null,
            'currency'       => $data['currency'] ?? 'USD',
            'total_amount'   => 0,
            'notes'          => $data['notes'] ?? null,
            'created_by'     => $this->getCurrentUserId(),
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        $this->logger->info('Order created', ['id' => $id, 'number' => $data['order_number'] ?? '']);

        return $id;
    }

    /**
     * Add a line item to an order.
     */
    public function addOrderLine(int $orderId, array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $unitPrice = (float) ($data['unit_price'] ?? 0);

        $id = DB::table('library_order_line')->insertGetId([
            'order_id'           => $orderId,
            'library_item_id'    => $data['library_item_id'] ?? null,
            'title'              => $data['title'],
            'isbn'               => $data['isbn'] ?? null,
            'quantity'           => $quantity,
            'unit_price'         => $unitPrice,
            'line_total'         => $quantity * $unitPrice,
            'quantity_received'  => 0,
            'line_status'        => 'pending',
            'fund_code'          => $data['fund_code'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        // Recalculate order total
        $this->recalculateOrderTotal($orderId);

        return $id;
    }

    /**
     * Receive items on an order line.
     */
    public function receiveOrderLine(int $orderLineId, int $quantityReceived): array
    {
        $line = DB::table('library_order_line')->where('id', $orderLineId)->first();
        if (!$line) {
            return ['success' => false, 'error' => 'Order line not found'];
        }

        $newReceived = (int) $line->quantity_received + $quantityReceived;
        $status = ($newReceived >= $line->quantity) ? 'received' : 'partial';

        $now = date('Y-m-d H:i:s');

        DB::table('library_order_line')
            ->where('id', $orderLineId)
            ->update([
                'quantity_received' => $newReceived,
                'line_status'       => $status,
                'received_date'     => $now,
                'updated_at'        => $now,
            ]);

        // Check if all lines are received → update order status
        $this->updateOrderStatus($line->order_id);

        $this->logger->info('Order line received', [
            'line_id'  => $orderLineId,
            'received' => $quantityReceived,
            'total'    => $newReceived,
        ]);

        return ['success' => true, 'status' => $status, 'quantity_received' => $newReceived];
    }

    /**
     * Update order status based on line statuses.
     */
    protected function updateOrderStatus(int $orderId): void
    {
        $lines = DB::table('library_order_line')->where('order_id', $orderId)->get();
        if ($lines->isEmpty()) {
            return;
        }

        $allReceived = $lines->every(fn($l) => $l->line_status === 'received');
        $anyReceived = $lines->contains(fn($l) => in_array($l->line_status, ['received', 'partial']));

        $status = 'pending';
        if ($allReceived) {
            $status = 'received';
        } elseif ($anyReceived) {
            $status = 'partial';
        }

        DB::table('library_order')
            ->where('id', $orderId)
            ->update(['order_status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Recalculate order total from lines.
     */
    protected function recalculateOrderTotal(int $orderId): void
    {
        $total = DB::table('library_order_line')
            ->where('order_id', $orderId)
            ->sum('line_total');

        DB::table('library_order')
            ->where('id', $orderId)
            ->update(['total_amount' => $total, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get order with lines.
     */
    public function getOrder(int $orderId): ?array
    {
        $order = DB::table('library_order')->where('id', $orderId)->first();
        if (!$order) {
            return null;
        }

        $lines = DB::table('library_order_line as ol')
            ->leftJoin('library_item as li', 'ol.library_item_id', '=', 'li.id')
            ->where('ol.order_id', $orderId)
            ->select(['ol.*', 'li.call_number', 'li.isbn as item_isbn'])
            ->orderBy('ol.id')
            ->get()
            ->all();

        return [
            'order' => $order,
            'lines' => $lines,
        ];
    }

    /**
     * Search orders.
     */
    public function searchOrders(array $params = []): array
    {
        $query = DB::table('library_order');

        if (!empty($params['q'])) {
            $q = '%' . $params['q'] . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('order_number', 'LIKE', $q)
                    ->orWhere('vendor_name', 'LIKE', $q);
            });
        }

        if (!empty($params['order_status'])) {
            $query->where('order_status', $params['order_status']);
        }

        if (!empty($params['order_type'])) {
            $query->where('order_type', $params['order_type']);
        }

        $total = $query->count();
        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 25)));

        $rows = $query->orderBy('order_date', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->all();

        return ['items' => $rows, 'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / $limit)];
    }

    // ========================================================================
    // BUDGETS
    // ========================================================================

    /**
     * Create a budget.
     */
    public function createBudget(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return DB::table('library_budget')->insertGetId([
            'budget_name'    => $data['budget_name'],
            'budget_code'    => $data['budget_code'],
            'fiscal_year'    => $data['fiscal_year'] ?? date('Y'),
            'allocated_amount' => (float) ($data['allocated_amount'] ?? 0),
            'spent_amount'   => 0,
            'encumbered_amount' => 0,
            'currency'       => $data['currency'] ?? 'USD',
            'category'       => $data['category'] ?? 'general',
            'notes'          => $data['notes'] ?? null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }

    /**
     * Get budget summary.
     */
    public function getBudgetSummary(int $budgetId): ?object
    {
        return DB::table('library_budget')
            ->where('id', $budgetId)
            ->selectRaw('*, (allocated_amount - spent_amount - encumbered_amount) as available_amount')
            ->first();
    }

    /**
     * Get all budgets for a fiscal year.
     */
    public function getBudgets(?string $fiscalYear = null): array
    {
        $query = DB::table('library_budget');

        if ($fiscalYear) {
            $query->where('fiscal_year', $fiscalYear);
        }

        return $query
            ->selectRaw('*, (allocated_amount - spent_amount - encumbered_amount) as available_amount')
            ->orderBy('budget_name')
            ->get()
            ->all();
    }

    /**
     * Record spending against a budget.
     */
    public function recordExpenditure(int $budgetId, float $amount): bool
    {
        return DB::table('library_budget')
            ->where('id', $budgetId)
            ->update([
                'spent_amount' => DB::raw('spent_amount + ' . $amount),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    /**
     * Generate a unique order number.
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'PO-' . date('Y') . '-';
        $last = DB::table('library_order')
            ->where('order_number', 'LIKE', $prefix . '%')
            ->max('order_number');

        if ($last) {
            $seq = (int) substr($last, strlen($prefix)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    protected function getCurrentUserId(): ?int
    {
        try {
            $user = \sfContext::getInstance()->getUser()->getAttribute('user_id');
            return $user ? (int) $user : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get acquisition statistics.
     */
    public function getStatistics(): array
    {
        $year = date('Y');

        return [
            'orders_this_year'   => DB::table('library_order')->where('fiscal_year', $year)->count(),
            'pending_orders'     => DB::table('library_order')->where('order_status', 'pending')->count(),
            'total_spent'        => (float) DB::table('library_budget')->where('fiscal_year', $year)->sum('spent_amount'),
            'total_allocated'    => (float) DB::table('library_budget')->where('fiscal_year', $year)->sum('allocated_amount'),
        ];
    }
}
