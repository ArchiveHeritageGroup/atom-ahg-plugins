<?php

declare(strict_types=1);

/**
 * HoldService
 *
 * Manages patron hold requests on library items.
 * Holds are placed on items (not copies) — system assigns copy at fulfillment.
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class HoldService
{
    protected static ?HoldService $instance = null;
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
        $this->logger = new Logger('library.hold');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // PLACE HOLD
    // ========================================================================

    /**
     * Place a hold on a library item for a patron.
     */
    public function placeHold(int $libraryItemId, int $patronId, ?string $notes = null): array
    {
        // Check patron is active
        $patron = DB::table('library_patron')->where('id', $patronId)->first();
        if (!$patron) {
            return ['success' => false, 'error' => 'Patron not found'];
        }
        if ($patron->borrowing_status !== 'active') {
            return ['success' => false, 'error' => 'Patron account is ' . $patron->borrowing_status];
        }

        // Check max holds
        $currentHolds = DB::table('library_hold')
            ->where('patron_id', $patronId)
            ->whereIn('hold_status', ['pending', 'ready'])
            ->count();

        if ($currentHolds >= $patron->max_holds) {
            return ['success' => false, 'error' => 'Maximum holds reached (' . $patron->max_holds . ')'];
        }

        // Check item exists
        $item = DB::table('library_item')->where('id', $libraryItemId)->first();
        if (!$item) {
            return ['success' => false, 'error' => 'Library item not found'];
        }

        // Check for duplicate hold
        $existing = DB::table('library_hold')
            ->where('patron_id', $patronId)
            ->where('library_item_id', $libraryItemId)
            ->whereIn('hold_status', ['pending', 'ready'])
            ->first();

        if ($existing) {
            return ['success' => false, 'error' => 'Patron already has an active hold on this item'];
        }

        // Check patron doesn't already have this item checked out
        $hasCheckout = DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->where('cp.library_item_id', $libraryItemId)
            ->where('c.patron_id', $patronId)
            ->where('c.checkout_status', 'checked_out')
            ->exists();

        if ($hasCheckout) {
            return ['success' => false, 'error' => 'Patron already has this item checked out'];
        }

        $now = date('Y-m-d H:i:s');

        // Determine position in queue
        $position = DB::table('library_hold')
            ->where('library_item_id', $libraryItemId)
            ->whereIn('hold_status', ['pending', 'ready'])
            ->count() + 1;

        // If a copy is available, mark hold as ready immediately
        $status = 'pending';
        $expiryDate = null;
        if ($item->available_copies > 0) {
            $status = 'ready';
            $expiryDate = date('Y-m-d', strtotime('+7 days'));
        }

        $holdId = DB::table('library_hold')->insertGetId([
            'library_item_id' => $libraryItemId,
            'patron_id'       => $patronId,
            'hold_date'       => $now,
            'hold_status'     => $status,
            'queue_position'  => $position,
            'expiry_date'     => $expiryDate,
            'pickup_location' => $notes, // re-use notes for pickup if needed
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $this->logger->info('Hold placed', [
            'hold_id'   => $holdId,
            'item_id'   => $libraryItemId,
            'patron_id' => $patronId,
            'status'    => $status,
            'position'  => $position,
        ]);

        return [
            'success'  => true,
            'hold_id'  => $holdId,
            'status'   => $status,
            'position' => $position,
        ];
    }

    // ========================================================================
    // CANCEL HOLD
    // ========================================================================

    /**
     * Cancel a hold.
     */
    public function cancelHold(int $holdId, ?int $patronId = null): array
    {
        $hold = DB::table('library_hold')->where('id', $holdId)->first();
        if (!$hold) {
            return ['success' => false, 'error' => 'Hold not found'];
        }

        // If patron ID given, verify ownership
        if ($patronId !== null && $hold->patron_id !== $patronId) {
            return ['success' => false, 'error' => 'Hold does not belong to this patron'];
        }

        if (!in_array($hold->hold_status, ['pending', 'ready'])) {
            return ['success' => false, 'error' => 'Hold cannot be cancelled (status: ' . $hold->hold_status . ')'];
        }

        DB::table('library_hold')
            ->where('id', $holdId)
            ->update([
                'hold_status' => 'cancelled',
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

        // Reorder queue positions
        $this->reorderQueue($hold->library_item_id);

        $this->logger->info('Hold cancelled', ['hold_id' => $holdId]);

        return ['success' => true];
    }

    // ========================================================================
    // QUEUE MANAGEMENT
    // ========================================================================

    /**
     * Get hold queue for an item.
     */
    public function getQueue(int $libraryItemId): array
    {
        return DB::table('library_hold as h')
            ->join('library_patron as p', 'h.patron_id', '=', 'p.id')
            ->where('h.library_item_id', $libraryItemId)
            ->whereIn('h.hold_status', ['pending', 'ready'])
            ->select([
                'h.*',
                'p.first_name', 'p.last_name', 'p.patron_barcode', 'p.email',
            ])
            ->orderBy('h.queue_position')
            ->get()
            ->all();
    }

    /**
     * Reorder queue positions after a cancel/fulfill.
     */
    protected function reorderQueue(int $libraryItemId): void
    {
        $holds = DB::table('library_hold')
            ->where('library_item_id', $libraryItemId)
            ->whereIn('hold_status', ['pending', 'ready'])
            ->orderBy('hold_date')
            ->get();

        $pos = 1;
        foreach ($holds as $hold) {
            DB::table('library_hold')
                ->where('id', $hold->id)
                ->update(['queue_position' => $pos++]);
        }
    }

    /**
     * Expire holds that have passed their pickup window.
     */
    public function expireOverdueHolds(): int
    {
        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $expired = DB::table('library_hold')
            ->where('hold_status', 'ready')
            ->where('expiry_date', '<', $today)
            ->get();

        $count = 0;
        foreach ($expired as $hold) {
            DB::table('library_hold')
                ->where('id', $hold->id)
                ->update([
                    'hold_status' => 'expired',
                    'updated_at'  => $now,
                ]);

            // Reorder queue and promote next hold
            $this->reorderQueue($hold->library_item_id);
            $this->promoteNextHold($hold->library_item_id);

            $count++;
        }

        if ($count > 0) {
            $this->logger->info('Expired holds', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Promote next pending hold to ready if copies are available.
     */
    protected function promoteNextHold(int $libraryItemId): void
    {
        $item = DB::table('library_item')->where('id', $libraryItemId)->first();
        if (!$item || $item->available_copies <= 0) {
            return;
        }

        $nextHold = DB::table('library_hold')
            ->where('library_item_id', $libraryItemId)
            ->where('hold_status', 'pending')
            ->orderBy('queue_position')
            ->first();

        if ($nextHold) {
            DB::table('library_hold')
                ->where('id', $nextHold->id)
                ->update([
                    'hold_status' => 'ready',
                    'expiry_date' => date('Y-m-d', strtotime('+7 days')),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

            $this->logger->info('Hold promoted to ready', [
                'hold_id'   => $nextHold->id,
                'patron_id' => $nextHold->patron_id,
            ]);
        }
    }

    // ========================================================================
    // STATISTICS
    // ========================================================================

    /**
     * Get hold statistics.
     */
    public function getStatistics(): array
    {
        return [
            'pending'   => DB::table('library_hold')->where('hold_status', 'pending')->count(),
            'ready'     => DB::table('library_hold')->where('hold_status', 'ready')->count(),
            'fulfilled' => DB::table('library_hold')->where('hold_status', 'fulfilled')->count(),
            'expired'   => DB::table('library_hold')->where('hold_status', 'expired')->count(),
            'cancelled' => DB::table('library_hold')->where('hold_status', 'cancelled')->count(),
        ];
    }
}
