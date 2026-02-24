<?php

namespace AtomAhgPlugins\ahgMarketplacePlugin\Repositories;

use Illuminate\Database\Capsule\Manager as DB;

class TransactionRepository
{
    protected string $txnTable = 'marketplace_transaction';
    protected string $payoutTable = 'marketplace_payout';

    // =========================================================================
    // Transaction CRUD
    // =========================================================================

    public function getById(int $id): ?object
    {
        return DB::table($this->txnTable)->where('id', $id)->first();
    }

    public function getByNumber(string $number): ?object
    {
        return DB::table($this->txnTable)->where('transaction_number', $number)->first();
    }

    public function create(array $data): int
    {
        return DB::table($this->txnTable)->insertGetId($data);
    }

    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->txnTable)->where('id', $id)->update($data) >= 0;
    }

    public function generateTransactionNumber(): string
    {
        $date = date('Ymd');
        $last = DB::table($this->txnTable)
            ->where('transaction_number', 'LIKE', 'TXN-' . $date . '-%')
            ->orderBy('id', 'DESC')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->transaction_number);
            $seq = (int) end($parts) + 1;
        }

        return sprintf('TXN-%s-%04d', $date, $seq);
    }

    // =========================================================================
    // Transaction Queries
    // =========================================================================

    public function getBuyerTransactions(int $userId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->txnTable . ' as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->leftJoin('marketplace_seller as s', 't.seller_id', '=', 's.id')
            ->select('t.*', 'l.title', 'l.slug', 'l.featured_image_path', 's.display_name as seller_name')
            ->where('t.buyer_id', $userId);

        $total = $query->count();
        $items = $query->orderBy('t.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getSellerTransactions(int $sellerId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->txnTable . ' as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->select('t.*', 'l.title', 'l.slug', 'l.featured_image_path')
            ->where('t.seller_id', $sellerId);

        $total = $query->count();
        $items = $query->orderBy('t.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getTransactionWithDetails(int $id): ?object
    {
        return DB::table($this->txnTable . ' as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->leftJoin('marketplace_seller as s', 't.seller_id', '=', 's.id')
            ->select('t.*', 'l.title', 'l.slug', 'l.featured_image_path', 'l.description', 's.display_name as seller_name', 's.slug as seller_slug', 's.email as seller_email')
            ->where('t.id', $id)
            ->first();
    }

    public function getAllForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->txnTable . ' as t')
            ->join('marketplace_listing as l', 't.listing_id', '=', 'l.id')
            ->leftJoin('marketplace_seller as s', 't.seller_id', '=', 's.id')
            ->select('t.*', 'l.title', 's.display_name as seller_name');

        if (!empty($filters['status'])) {
            $query->where('t.status', $filters['status']);
        }
        if (!empty($filters['payment_status'])) {
            $query->where('t.payment_status', $filters['payment_status']);
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('t.transaction_number', 'LIKE', '%' . $filters['search'] . '%')
                  ->orWhere('l.title', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        $total = $query->count();
        $items = $query->orderBy('t.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    // =========================================================================
    // Revenue Stats
    // =========================================================================

    public function getRevenueStats(?int $sellerId = null): array
    {
        $query = DB::table($this->txnTable)->where('payment_status', 'paid');
        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }

        return [
            'total_sales' => (clone $query)->count(),
            'total_revenue' => (clone $query)->sum('sale_price') ?? 0,
            'total_commission' => (clone $query)->sum('platform_commission_amount') ?? 0,
            'total_seller_amount' => (clone $query)->sum('seller_amount') ?? 0,
        ];
    }

    public function getMonthlyRevenue(?int $sellerId = null, int $months = 12): array
    {
        $query = DB::table($this->txnTable)
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', date('Y-m-d', strtotime("-{$months} months")))
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(sale_price) as revenue, SUM(platform_commission_amount) as commission, COUNT(*) as sales")
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        if ($sellerId) {
            $query->where('seller_id', $sellerId);
        }

        return $query->get()->all();
    }

    // =========================================================================
    // Payouts
    // =========================================================================

    public function createPayout(array $data): int
    {
        return DB::table($this->payoutTable)->insertGetId($data);
    }

    public function updatePayout(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        return DB::table($this->payoutTable)->where('id', $id)->update($data) >= 0;
    }

    public function getPayoutById(int $id): ?object
    {
        return DB::table($this->payoutTable)->where('id', $id)->first();
    }

    public function getSellerPayouts(int $sellerId, int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->payoutTable)->where('seller_id', $sellerId);

        $total = $query->count();
        $items = $query->orderBy('created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }

    public function getPendingPayouts(int $limit = 100): array
    {
        return DB::table($this->payoutTable . ' as p')
            ->join('marketplace_seller as s', 'p.seller_id', '=', 's.id')
            ->select('p.*', 's.display_name as seller_name', 's.payout_method', 's.payout_details', 's.payout_currency')
            ->where('p.status', 'pending')
            ->orderBy('p.created_at', 'ASC')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function generatePayoutNumber(): string
    {
        $date = date('Ymd');
        $last = DB::table($this->payoutTable)
            ->where('payout_number', 'LIKE', 'PAY-' . $date . '-%')
            ->orderBy('id', 'DESC')
            ->first();

        $seq = 1;
        if ($last) {
            $parts = explode('-', $last->payout_number);
            $seq = (int) end($parts) + 1;
        }

        return sprintf('PAY-%s-%04d', $date, $seq);
    }

    public function getSellerPendingPayoutAmount(int $sellerId): float
    {
        // Transactions completed but not yet paid out
        $paid = DB::table($this->payoutTable)
            ->where('seller_id', $sellerId)
            ->whereIn('status', ['pending', 'processing', 'completed'])
            ->sum('amount') ?? 0;

        $earned = DB::table($this->txnTable)
            ->where('seller_id', $sellerId)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->sum('seller_amount') ?? 0;

        return max(0, $earned - $paid);
    }

    public function getAllPayoutsForAdmin(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $query = DB::table($this->payoutTable . ' as p')
            ->join('marketplace_seller as s', 'p.seller_id', '=', 's.id')
            ->select('p.*', 's.display_name as seller_name');

        if (!empty($filters['status'])) {
            $query->where('p.status', $filters['status']);
        }

        $total = $query->count();
        $items = $query->orderBy('p.created_at', 'DESC')
                       ->limit($limit)
                       ->offset($offset)
                       ->get()
                       ->all();

        return ['items' => $items, 'total' => $total];
    }
}
