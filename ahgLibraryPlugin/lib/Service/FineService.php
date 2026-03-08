<?php

declare(strict_types=1);

/**
 * FineService
 *
 * Manages library fines — overdue, lost item, damage, and payments.
 * Fine types and statuses driven by ahg_dropdown.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class FineService
{
    protected static ?FineService $instance = null;
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
        $this->logger = new Logger('library.fine');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // FINE CREATION
    // ========================================================================

    /**
     * Create a fine for a patron.
     */
    public function createFine(int $patronId, string $fineType, float $amount, ?int $checkoutId = null, ?string $description = null): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_fine')->insertGetId([
            'patron_id'   => $patronId,
            'checkout_id' => $checkoutId,
            'fine_type'   => $fineType,
            'amount'      => $amount,
            'amount_paid' => 0,
            'fine_status' => 'outstanding',
            'description' => $description,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        $this->logger->info('Fine created', [
            'id'        => $id,
            'patron_id' => $patronId,
            'type'      => $fineType,
            'amount'    => $amount,
        ]);

        return $id;
    }

    /**
     * Create a lost item fine — charges replacement value.
     */
    public function createLostItemFine(int $checkoutId): array
    {
        $checkout = DB::table('library_checkout')->where('id', $checkoutId)->first();
        if (!$checkout) {
            return ['success' => false, 'error' => 'Checkout not found'];
        }

        $copy = DB::table('library_copy')->where('id', $checkout->copy_id)->first();
        $item = DB::table('library_item')->where('id', $copy->library_item_id)->first();

        // Use replacement_value from heritage accounting fields, or default
        $replacementValue = (float) ($item->replacement_value ?? 0);
        if ($replacementValue <= 0) {
            $replacementValue = (float) ($item->acquisition_cost ?? 25.00);
        }

        $now = date('Y-m-d H:i:s');

        // Mark copy as lost
        DB::table('library_copy')
            ->where('id', $checkout->copy_id)
            ->update(['copy_status' => 'lost', 'updated_at' => $now]);

        // Mark checkout as lost
        DB::table('library_checkout')
            ->where('id', $checkoutId)
            ->update(['checkout_status' => 'lost', 'updated_at' => $now]);

        // Update copy counts
        $total = DB::table('library_copy')
            ->where('library_item_id', $copy->library_item_id)
            ->whereNotIn('copy_status', ['withdrawn', 'lost'])
            ->count();
        $available = DB::table('library_copy')
            ->where('library_item_id', $copy->library_item_id)
            ->where('copy_status', 'available')
            ->count();
        DB::table('library_item')
            ->where('id', $copy->library_item_id)
            ->update(['total_copies' => $total, 'available_copies' => $available]);

        // Create the fine
        $fineId = $this->createFine(
            $checkout->patron_id,
            'lost_item',
            $replacementValue,
            $checkoutId,
            'Lost item replacement charge'
        );

        return [
            'success' => true,
            'fine_id' => $fineId,
            'amount'  => $replacementValue,
        ];
    }

    // ========================================================================
    // PAYMENTS
    // ========================================================================

    /**
     * Record a payment against a fine.
     */
    public function recordPayment(int $fineId, float $amount, string $paymentMethod = 'cash', ?string $receiptNumber = null): array
    {
        $fine = DB::table('library_fine')->where('id', $fineId)->first();
        if (!$fine) {
            return ['success' => false, 'error' => 'Fine not found'];
        }

        if ($fine->fine_status === 'paid') {
            return ['success' => false, 'error' => 'Fine is already fully paid'];
        }

        $remaining = (float) $fine->amount - (float) $fine->amount_paid;
        $payment = min($amount, $remaining);

        $newPaid = (float) $fine->amount_paid + $payment;
        $newStatus = ($newPaid >= (float) $fine->amount) ? 'paid' : 'partial';

        $now = date('Y-m-d H:i:s');

        DB::table('library_fine')
            ->where('id', $fineId)
            ->update([
                'amount_paid'    => $newPaid,
                'fine_status'    => $newStatus,
                'payment_method' => $paymentMethod,
                'payment_date'   => $now,
                'updated_at'     => $now,
            ]);

        $this->logger->info('Payment recorded', [
            'fine_id' => $fineId,
            'amount'  => $payment,
            'method'  => $paymentMethod,
            'status'  => $newStatus,
        ]);

        return [
            'success'    => true,
            'paid'       => $payment,
            'remaining'  => max(0, (float) $fine->amount - $newPaid),
            'status'     => $newStatus,
        ];
    }

    /**
     * Waive a fine (forgive the balance).
     */
    public function waiveFine(int $fineId, ?string $reason = null): bool
    {
        $fine = DB::table('library_fine')->where('id', $fineId)->first();
        if (!$fine || $fine->fine_status === 'paid') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        DB::table('library_fine')
            ->where('id', $fineId)
            ->update([
                'fine_status'  => 'waived',
                'description'  => trim(($fine->description ?? '') . "\n[Waived " . date('Y-m-d') . '] ' . ($reason ?? '')),
                'updated_at'   => $now,
            ]);

        $this->logger->info('Fine waived', ['fine_id' => $fineId, 'reason' => $reason]);

        return true;
    }

    // ========================================================================
    // QUERIES
    // ========================================================================

    /**
     * Get all outstanding fines for a patron.
     */
    public function getPatronFines(int $patronId, bool $outstandingOnly = true): array
    {
        $query = DB::table('library_fine as f')
            ->leftJoin('library_checkout as c', 'f.checkout_id', '=', 'c.id')
            ->leftJoin('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->leftJoin('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('f.patron_id', $patronId);

        if ($outstandingOnly) {
            $query->whereIn('f.fine_status', ['outstanding', 'partial']);
        }

        return $query->select([
                'f.*',
                'cp.barcode as copy_barcode',
                'li.call_number',
                'ioi.title',
            ])
            ->orderBy('f.created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get total outstanding balance for a patron.
     */
    public function getPatronBalance(int $patronId): float
    {
        $total = DB::table('library_fine')
            ->where('patron_id', $patronId)
            ->whereIn('fine_status', ['outstanding', 'partial'])
            ->selectRaw('SUM(amount - amount_paid) as balance')
            ->value('balance');

        return (float) ($total ?? 0);
    }

    /**
     * Search fines (admin view).
     */
    public function search(array $params = []): array
    {
        $query = DB::table('library_fine as f')
            ->join('library_patron as p', 'f.patron_id', '=', 'p.id');

        if (!empty($params['fine_status'])) {
            $query->where('f.fine_status', $params['fine_status']);
        }

        if (!empty($params['fine_type'])) {
            $query->where('f.fine_type', $params['fine_type']);
        }

        if (!empty($params['patron_id'])) {
            $query->where('f.patron_id', $params['patron_id']);
        }

        $total = $query->count();

        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 25)));

        $rows = $query->select([
                'f.*',
                'p.first_name', 'p.last_name', 'p.patron_barcode',
            ])
            ->orderBy('f.created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->all();

        return [
            'items' => $rows,
            'total' => $total,
            'page'  => $page,
            'pages' => (int) ceil($total / $limit),
        ];
    }

    // ========================================================================
    // BATCH OPERATIONS
    // ========================================================================

    /**
     * Generate overdue fines for all currently overdue checkouts.
     * Should be run daily via cron.
     */
    public function generateDailyOverdueFines(): int
    {
        $today = date('Y-m-d');
        $count = 0;

        // Get all overdue checkouts that don't already have a fine for today
        $overdueCheckouts = DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->join('library_patron as p', 'c.patron_id', '=', 'p.id')
            ->where('c.checkout_status', 'checked_out')
            ->where('c.due_date', '<', $today)
            ->select([
                'c.id as checkout_id',
                'c.patron_id',
                'c.due_date',
                'li.material_type',
                'p.patron_type',
            ])
            ->get();

        foreach ($overdueCheckouts as $co) {
            // Check if a fine already exists for this checkout
            $existingFine = DB::table('library_fine')
                ->where('checkout_id', $co->checkout_id)
                ->where('fine_type', 'overdue')
                ->first();

            if ($existingFine) {
                // Update amount if fine is accumulating
                $loanRule = DB::table('library_loan_rule')
                    ->where('material_type', $co->material_type)
                    ->where('patron_type', $co->patron_type)
                    ->first();

                if (!$loanRule) {
                    $loanRule = DB::table('library_loan_rule')
                        ->where('material_type', $co->material_type)
                        ->where('patron_type', 'default')
                        ->first();
                }

                if (!$loanRule) {
                    $loanRule = DB::table('library_loan_rule')
                        ->where('material_type', 'default')
                        ->where('patron_type', 'default')
                        ->first();
                }

                $finePerDay = $loanRule ? (float) $loanRule->fine_per_day : 1.00;
                $maxFine = $loanRule ? (float) $loanRule->max_fine : 50.00;

                $daysOverdue = max(0, (int) ((strtotime($today) - strtotime($co->due_date)) / 86400));
                $newAmount = min($daysOverdue * $finePerDay, $maxFine);

                if ($newAmount > (float) $existingFine->amount) {
                    DB::table('library_fine')
                        ->where('id', $existingFine->id)
                        ->update([
                            'amount'     => $newAmount,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    $count++;
                }
            }
            // New fines are created at checkin time, not daily
        }

        if ($count > 0) {
            $this->logger->info('Daily fine update', ['updated' => $count]);
        }

        return $count;
    }

    // ========================================================================
    // STATISTICS
    // ========================================================================

    /**
     * Get fine statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_outstanding'  => (float) DB::table('library_fine')
                ->whereIn('fine_status', ['outstanding', 'partial'])
                ->sum(DB::raw('amount - amount_paid')),
            'total_collected'    => (float) DB::table('library_fine')->sum('amount_paid'),
            'total_waived'       => (float) DB::table('library_fine')
                ->where('fine_status', 'waived')
                ->sum(DB::raw('amount - amount_paid')),
            'outstanding_count'  => DB::table('library_fine')
                ->whereIn('fine_status', ['outstanding', 'partial'])
                ->count(),
            'patrons_with_fines' => DB::table('library_fine')
                ->whereIn('fine_status', ['outstanding', 'partial'])
                ->distinct('patron_id')
                ->count('patron_id'),
        ];
    }
}
