<?php

declare(strict_types=1);

/**
 * CirculationService
 *
 * Core circulation operations — checkout, return, renew.
 * Loan rules driven by library_loan_rule table (material_type × patron_type).
 * All status values from ahg_dropdown.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class CirculationService
{
    protected static ?CirculationService $instance = null;
    protected PatronService $patronService;
    protected Logger $logger;

    public function __construct()
    {
        $this->patronService = PatronService::getInstance();
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
        $this->logger = new Logger('library.circulation');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // CHECKOUT
    // ========================================================================

    /**
     * Check out a copy to a patron.
     *
     * @return array{success: bool, checkout_id?: int, due_date?: string, error?: string}
     */
    public function checkout(int $copyId, int $patronId, ?string $dueDate = null): array
    {
        // Validate patron can borrow
        $canBorrow = $this->patronService->canBorrow($patronId);
        if (!$canBorrow['allowed']) {
            return ['success' => false, 'error' => $canBorrow['reason']];
        }

        // Get copy and item
        $copy = DB::table('library_copy')->where('id', $copyId)->first();
        if (!$copy) {
            return ['success' => false, 'error' => 'Copy not found'];
        }

        if ($copy->copy_status !== 'available') {
            return ['success' => false, 'error' => 'Copy is not available (status: ' . $copy->copy_status . ')'];
        }

        $item = DB::table('library_item')->where('id', $copy->library_item_id)->first();
        if (!$item) {
            return ['success' => false, 'error' => 'Library item not found'];
        }

        // Get loan rule for this material_type + patron_type
        $patron = $this->patronService->find($patronId);
        $loanRule = $this->getLoanRule($item->material_type, $patron->patron_type);

        if (!$loanRule || !$loanRule->is_loanable) {
            return ['success' => false, 'error' => 'This material type is not available for loan'];
        }

        // Calculate due date
        if (!$dueDate) {
            $dueDate = date('Y-m-d', strtotime('+' . $loanRule->loan_days . ' days'));
        }

        $now = date('Y-m-d H:i:s');

        // Begin transaction
        DB::connection()->beginTransaction();
        try {
            // Create checkout record
            $checkoutId = DB::table('library_checkout')->insertGetId([
                'copy_id'          => $copyId,
                'patron_id'        => $patronId,
                'checkout_date'    => $now,
                'due_date'         => $dueDate,
                'checkout_status'  => 'checked_out',
                'renewals_count'   => 0,
                'max_renewals'     => $loanRule->max_renewals,
                'checked_out_by'   => $this->getCurrentUserId(),
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            // Update copy status
            DB::table('library_copy')
                ->where('id', $copyId)
                ->update([
                    'copy_status' => 'checked_out',
                    'updated_at' => $now,
                ]);

            // Update item available copies count
            $this->updateAvailableCopies($copy->library_item_id);

            // Fulfill any pending hold for this patron + item
            DB::table('library_hold')
                ->where('patron_id', $patronId)
                ->where('library_item_id', $copy->library_item_id)
                ->whereIn('hold_status', ['pending', 'ready'])
                ->update([
                    'hold_status' => 'fulfilled',
                    'fulfilled_date' => $now,
                    'updated_at' => $now,
                ]);

            DB::connection()->commit();

            $this->logger->info('Checkout', [
                'checkout_id' => $checkoutId,
                'copy_id'     => $copyId,
                'patron_id'   => $patronId,
                'due_date'    => $dueDate,
            ]);

            return [
                'success'     => true,
                'checkout_id' => $checkoutId,
                'due_date'    => $dueDate,
            ];
        } catch (\Exception $e) {
            DB::connection()->rollBack();
            $this->logger->error('Checkout failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Checkout failed: ' . $e->getMessage()];
        }
    }

    /**
     * Check out by barcode (station mode).
     */
    public function checkoutByBarcode(string $copyBarcode, string $patronBarcode): array
    {
        $copy = DB::table('library_copy')->where('barcode', $copyBarcode)->first();
        if (!$copy) {
            return ['success' => false, 'error' => 'Copy barcode not found: ' . $copyBarcode];
        }

        $patron = $this->patronService->findByBarcode($patronBarcode);
        if (!$patron) {
            return ['success' => false, 'error' => 'Patron barcode not found: ' . $patronBarcode];
        }

        return $this->checkout($copy->id, $patron->id);
    }

    // ========================================================================
    // RETURN
    // ========================================================================

    /**
     * Return a checked-out copy.
     *
     * @return array{success: bool, fine_amount?: float, error?: string}
     */
    public function checkin(int $checkoutId): array
    {
        $checkout = DB::table('library_checkout')->where('id', $checkoutId)->first();
        if (!$checkout) {
            return ['success' => false, 'error' => 'Checkout record not found'];
        }

        if ($checkout->checkout_status !== 'checked_out') {
            return ['success' => false, 'error' => 'Item is not currently checked out'];
        }

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $fineAmount = 0.0;

        DB::connection()->beginTransaction();
        try {
            // Check for overdue fine
            if ($checkout->due_date < $today) {
                $fineAmount = $this->calculateOverdueFine($checkout);
                if ($fineAmount > 0) {
                    DB::table('library_fine')->insert([
                        'patron_id'   => $checkout->patron_id,
                        'checkout_id' => $checkoutId,
                        'fine_type'   => 'overdue',
                        'amount'      => $fineAmount,
                        'fine_status' => 'outstanding',
                        'description' => 'Overdue fine — due ' . $checkout->due_date . ', returned ' . $today,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }

            // Update checkout record
            DB::table('library_checkout')
                ->where('id', $checkoutId)
                ->update([
                    'return_date'     => $now,
                    'checkout_status' => 'returned',
                    'checked_in_by'   => $this->getCurrentUserId(),
                    'updated_at'      => $now,
                ]);

            // Update copy status
            DB::table('library_copy')
                ->where('id', $checkout->copy_id)
                ->update([
                    'copy_status' => 'available',
                    'updated_at' => $now,
                ]);

            // Update available count
            $copy = DB::table('library_copy')->where('id', $checkout->copy_id)->first();
            $this->updateAvailableCopies($copy->library_item_id);

            // Check for pending holds on this item — notify next in queue
            $this->processHoldQueue($copy->library_item_id);

            DB::connection()->commit();

            $this->logger->info('Checkin', [
                'checkout_id' => $checkoutId,
                'copy_id'     => $checkout->copy_id,
                'patron_id'   => $checkout->patron_id,
                'fine'        => $fineAmount,
            ]);

            return [
                'success'     => true,
                'fine_amount' => $fineAmount,
            ];
        } catch (\Exception $e) {
            DB::connection()->rollBack();
            $this->logger->error('Checkin failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'Checkin failed: ' . $e->getMessage()];
        }
    }

    /**
     * Return by copy barcode.
     */
    public function checkinByBarcode(string $copyBarcode): array
    {
        $checkout = DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->where('cp.barcode', $copyBarcode)
            ->where('c.checkout_status', 'checked_out')
            ->select('c.id')
            ->first();

        if (!$checkout) {
            return ['success' => false, 'error' => 'No active checkout found for barcode: ' . $copyBarcode];
        }

        return $this->checkin($checkout->id);
    }

    // ========================================================================
    // RENEW
    // ========================================================================

    /**
     * Renew a checkout.
     */
    public function renew(int $checkoutId): array
    {
        $checkout = DB::table('library_checkout')->where('id', $checkoutId)->first();
        if (!$checkout) {
            return ['success' => false, 'error' => 'Checkout not found'];
        }

        if ($checkout->checkout_status !== 'checked_out') {
            return ['success' => false, 'error' => 'Item is not currently checked out'];
        }

        if ($checkout->renewals_count >= $checkout->max_renewals) {
            return ['success' => false, 'error' => 'Maximum renewals reached (' . $checkout->max_renewals . ')'];
        }

        // Check if there are pending holds — block renewal if so
        $copy = DB::table('library_copy')->where('id', $checkout->copy_id)->first();
        $item = DB::table('library_item')->where('id', $copy->library_item_id)->first();

        $pendingHolds = DB::table('library_hold')
            ->where('library_item_id', $item->id)
            ->whereIn('hold_status', ['pending', 'ready'])
            ->count();

        if ($pendingHolds > 0) {
            return ['success' => false, 'error' => 'Cannot renew — item has pending holds'];
        }

        // Get patron for loan rule
        $patron = $this->patronService->find($checkout->patron_id);
        $loanRule = $this->getLoanRule($item->material_type, $patron->patron_type);

        $renewalDays = $loanRule ? $loanRule->loan_days : 14;
        $newDueDate = date('Y-m-d', strtotime('+' . $renewalDays . ' days'));

        DB::table('library_checkout')
            ->where('id', $checkoutId)
            ->update([
                'due_date'        => $newDueDate,
                'renewals_count'  => $checkout->renewals_count + 1,
                'last_renewal_date' => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ]);

        $this->logger->info('Renewal', [
            'checkout_id'  => $checkoutId,
            'new_due_date' => $newDueDate,
            'renewal_num'  => $checkout->renewals_count + 1,
        ]);

        return [
            'success'      => true,
            'new_due_date' => $newDueDate,
            'renewals_left' => $checkout->max_renewals - $checkout->renewals_count - 1,
        ];
    }

    // ========================================================================
    // OVERDUE & FINES
    // ========================================================================

    /**
     * Get all overdue checkouts.
     */
    public function getOverdueCheckouts(): array
    {
        return DB::table('library_checkout as c')
            ->join('library_patron as p', 'c.patron_id', '=', 'p.id')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('c.checkout_status', 'checked_out')
            ->where('c.due_date', '<', date('Y-m-d'))
            ->select([
                'c.*',
                'p.first_name', 'p.last_name', 'p.email', 'p.patron_barcode',
                'cp.barcode as copy_barcode',
                'li.call_number', 'li.isbn',
                'ioi.title',
                DB::raw('DATEDIFF(CURDATE(), c.due_date) as days_overdue'),
            ])
            ->orderBy('c.due_date')
            ->get()
            ->all();
    }

    /**
     * Calculate overdue fine for a checkout.
     */
    protected function calculateOverdueFine(object $checkout): float
    {
        $copy = DB::table('library_copy')->where('id', $checkout->copy_id)->first();
        $item = DB::table('library_item')->where('id', $copy->library_item_id)->first();
        $patron = $this->patronService->find($checkout->patron_id);

        $loanRule = $this->getLoanRule($item->material_type, $patron->patron_type);

        $finePerDay = $loanRule ? (float) $loanRule->fine_per_day : 1.00;
        $maxFine = $loanRule ? (float) $loanRule->max_fine : 50.00;

        $daysOverdue = max(0, (int) ((strtotime(date('Y-m-d')) - strtotime($checkout->due_date)) / 86400));

        $fine = $daysOverdue * $finePerDay;

        return min($fine, $maxFine);
    }

    // ========================================================================
    // LOAN RULES
    // ========================================================================

    /**
     * Get applicable loan rule for a material_type + patron_type.
     * Falls back: specific → material default → global default.
     */
    public function getLoanRule(string $materialType, string $patronType): ?object
    {
        // Exact match
        $rule = DB::table('library_loan_rule')
            ->where('material_type', $materialType)
            ->where('patron_type', $patronType)
            ->first();

        if ($rule) {
            return $rule;
        }

        // Material type default (patron_type = 'default')
        $rule = DB::table('library_loan_rule')
            ->where('material_type', $materialType)
            ->where('patron_type', 'default')
            ->first();

        if ($rule) {
            return $rule;
        }

        // Global default
        return DB::table('library_loan_rule')
            ->where('material_type', 'default')
            ->where('patron_type', 'default')
            ->first();
    }

    // ========================================================================
    // COPY MANAGEMENT
    // ========================================================================

    /**
     * Add a copy to a library item.
     */
    public function addCopy(int $libraryItemId, array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_copy')->insertGetId([
            'library_item_id' => $libraryItemId,
            'barcode'         => $data['barcode'] ?? $this->generateCopyBarcode(),
            'copy_number'     => $data['copy_number'] ?? $this->getNextCopyNumber($libraryItemId),
            'copy_status'     => $data['copy_status'] ?? 'available',
            'condition_note'  => $data['condition_note'] ?? null,
            'location'        => $data['location'] ?? null,
            'acquisition_date' => $data['acquisition_date'] ?? date('Y-m-d'),
            'acquisition_cost' => $data['acquisition_cost'] ?? null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $this->updateCopyCounts($libraryItemId);

        $this->logger->info('Copy added', ['id' => $id, 'item_id' => $libraryItemId]);

        return $id;
    }

    /**
     * Update copy counts on the library item.
     */
    protected function updateCopyCounts(int $libraryItemId): void
    {
        $total = DB::table('library_copy')
            ->where('library_item_id', $libraryItemId)
            ->whereNotIn('copy_status', ['withdrawn', 'lost'])
            ->count();

        $available = DB::table('library_copy')
            ->where('library_item_id', $libraryItemId)
            ->where('copy_status', 'available')
            ->count();

        DB::table('library_item')
            ->where('id', $libraryItemId)
            ->update([
                'total_copies'     => $total,
                'available_copies' => $available,
            ]);
    }

    /**
     * Alias for updateCopyCounts.
     */
    protected function updateAvailableCopies(int $libraryItemId): void
    {
        $this->updateCopyCounts($libraryItemId);
    }

    /**
     * Get next copy number for an item.
     */
    protected function getNextCopyNumber(int $libraryItemId): int
    {
        $max = DB::table('library_copy')
            ->where('library_item_id', $libraryItemId)
            ->max('copy_number');

        return ($max ?? 0) + 1;
    }

    /**
     * Generate unique copy barcode.
     */
    protected function generateCopyBarcode(): string
    {
        do {
            $barcode = 'C' . str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
        } while (DB::table('library_copy')->where('barcode', $barcode)->exists());

        return $barcode;
    }

    // ========================================================================
    // HOLDS
    // ========================================================================

    /**
     * Process hold queue after a return — mark next hold as ready.
     */
    protected function processHoldQueue(int $libraryItemId): void
    {
        $nextHold = DB::table('library_hold')
            ->where('library_item_id', $libraryItemId)
            ->where('hold_status', 'pending')
            ->orderBy('hold_date')
            ->first();

        if ($nextHold) {
            $expiry = date('Y-m-d', strtotime('+7 days'));

            DB::table('library_hold')
                ->where('id', $nextHold->id)
                ->update([
                    'hold_status' => 'ready',
                    'expiry_date' => $expiry,
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

            $this->logger->info('Hold ready', [
                'hold_id'   => $nextHold->id,
                'patron_id' => $nextHold->patron_id,
                'item_id'   => $libraryItemId,
            ]);
        }
    }

    // ========================================================================
    // STATISTICS
    // ========================================================================

    /**
     * Get circulation statistics.
     */
    public function getStatistics(): array
    {
        $today = date('Y-m-d');

        return [
            'active_checkouts'  => DB::table('library_checkout')->where('checkout_status', 'checked_out')->count(),
            'overdue'           => DB::table('library_checkout')
                ->where('checkout_status', 'checked_out')
                ->where('due_date', '<', $today)
                ->count(),
            'today_checkouts'   => DB::table('library_checkout')
                ->whereDate('checkout_date', $today)
                ->count(),
            'today_returns'     => DB::table('library_checkout')
                ->whereDate('return_date', $today)
                ->count(),
            'pending_holds'     => DB::table('library_hold')->where('hold_status', 'pending')->count(),
            'ready_holds'       => DB::table('library_hold')->where('hold_status', 'ready')->count(),
            'total_copies'      => DB::table('library_copy')->count(),
            'available_copies'  => DB::table('library_copy')->where('copy_status', 'available')->count(),
            'outstanding_fines' => DB::table('library_fine')->where('fine_status', 'outstanding')->sum('amount'),
        ];
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    /**
     * Get current user ID.
     */
    protected function getCurrentUserId(): ?int
    {
        try {
            $user = \sfContext::getInstance()->getUser()->getAttribute('user_id');
            return $user ? (int) $user : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
