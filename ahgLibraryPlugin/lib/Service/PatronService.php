<?php

declare(strict_types=1);

/**
 * PatronService
 *
 * Manages library patrons — registration, status, borrowing privileges.
 * All patron types driven by ahg_dropdown (patron_type taxonomy).
 *
 * @package    ahgLibraryPlugin
 * @subpackage Service
 */

use Illuminate\Database\Capsule\Manager as DB;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class PatronService
{
    protected static ?PatronService $instance = null;
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
        $this->logger = new Logger('library.patron');
        $logPath = \sfConfig::get('sf_log_dir', '/tmp') . '/library.log';
        $this->logger->pushHandler(
            new RotatingFileHandler($logPath, 30, Logger::DEBUG)
        );
    }

    // ========================================================================
    // CRUD
    // ========================================================================

    /**
     * Create a new patron.
     */
    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        $id = DB::table('library_patron')->insertGetId([
            'patron_barcode'     => $data['patron_barcode'] ?? $this->generateBarcode(),
            'first_name'         => $data['first_name'],
            'last_name'          => $data['last_name'],
            'email'              => $data['email'] ?? null,
            'phone'              => $data['phone'] ?? null,
            'patron_type'        => $data['patron_type'] ?? 'general',
            'borrowing_status'   => 'active',
            'max_checkouts'      => $data['max_checkouts'] ?? 5,
            'max_holds'          => $data['max_holds'] ?? 3,
            'expiry_date'        => $data['expiry_date'] ?? null,
            'user_id'            => $data['user_id'] ?? null,
            'notes'              => $data['notes'] ?? null,
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);

        $this->logger->info('Patron created', ['id' => $id, 'name' => $data['first_name'] . ' ' . $data['last_name']]);

        return $id;
    }

    /**
     * Update an existing patron.
     */
    public function update(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // Only allow updatable fields
        $allowed = [
            'first_name', 'last_name', 'email', 'phone', 'patron_type',
            'borrowing_status', 'max_checkouts', 'max_holds', 'expiry_date',
            'user_id', 'notes', 'updated_at',
        ];

        $update = array_intersect_key($data, array_flip($allowed));

        $affected = DB::table('library_patron')->where('id', $id)->update($update);

        if ($affected) {
            $this->logger->info('Patron updated', ['id' => $id]);
        }

        return $affected > 0;
    }

    /**
     * Get patron by ID.
     */
    public function find(int $id): ?object
    {
        return DB::table('library_patron')->where('id', $id)->first();
    }

    /**
     * Get patron by barcode.
     */
    public function findByBarcode(string $barcode): ?object
    {
        return DB::table('library_patron')->where('patron_barcode', $barcode)->first();
    }

    /**
     * Get patron by user ID.
     */
    public function findByUserId(int $userId): ?object
    {
        return DB::table('library_patron')->where('user_id', $userId)->first();
    }

    /**
     * Search patrons.
     */
    public function search(array $params = []): array
    {
        $query = DB::table('library_patron');

        if (!empty($params['q'])) {
            $q = '%' . $params['q'] . '%';
            $query->where(function ($qb) use ($q) {
                $qb->where('first_name', 'LIKE', $q)
                    ->orWhere('last_name', 'LIKE', $q)
                    ->orWhere('email', 'LIKE', $q)
                    ->orWhere('patron_barcode', 'LIKE', $q);
            });
        }

        if (!empty($params['patron_type'])) {
            $query->where('patron_type', $params['patron_type']);
        }

        if (!empty($params['borrowing_status'])) {
            $query->where('borrowing_status', $params['borrowing_status']);
        }

        $total = $query->count();

        $page = max(1, (int) ($params['page'] ?? 1));
        $limit = min(100, max(1, (int) ($params['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $rows = $query->orderBy('last_name')
            ->orderBy('first_name')
            ->offset($offset)
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
    // STATUS & VALIDATION
    // ========================================================================

    /**
     * Check if patron can borrow.
     */
    public function canBorrow(int $patronId): array
    {
        $patron = $this->find($patronId);

        if (!$patron) {
            return ['allowed' => false, 'reason' => 'Patron not found'];
        }

        if ($patron->borrowing_status !== 'active') {
            return ['allowed' => false, 'reason' => 'Patron account is ' . $patron->borrowing_status];
        }

        if ($patron->expiry_date && $patron->expiry_date < date('Y-m-d')) {
            return ['allowed' => false, 'reason' => 'Patron membership expired on ' . $patron->expiry_date];
        }

        // Check current checkout count
        $currentCheckouts = DB::table('library_checkout')
            ->where('patron_id', $patronId)
            ->where('checkout_status', 'checked_out')
            ->count();

        if ($currentCheckouts >= $patron->max_checkouts) {
            return ['allowed' => false, 'reason' => 'Maximum checkouts reached (' . $patron->max_checkouts . ')'];
        }

        // Check outstanding fines
        $outstandingFines = DB::table('library_fine')
            ->where('patron_id', $patronId)
            ->where('fine_status', 'outstanding')
            ->sum('amount');

        $fineThreshold = (float) $this->getSetting('fine_block_threshold', '10.00');
        if ($outstandingFines >= $fineThreshold) {
            return ['allowed' => false, 'reason' => sprintf('Outstanding fines (%.2f) exceed threshold (%.2f)', $outstandingFines, $fineThreshold)];
        }

        return ['allowed' => true, 'reason' => null, 'current_checkouts' => $currentCheckouts, 'max_checkouts' => $patron->max_checkouts];
    }

    /**
     * Suspend a patron's borrowing privileges.
     */
    public function suspend(int $patronId, ?string $reason = null): bool
    {
        $updated = DB::table('library_patron')
            ->where('id', $patronId)
            ->update([
                'borrowing_status' => 'suspended',
                'notes' => DB::raw("CONCAT(IFNULL(notes,''), '\n[" . date('Y-m-d') . "] Suspended: " . addslashes($reason ?? 'No reason given') . "')"),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if ($updated) {
            $this->logger->info('Patron suspended', ['id' => $patronId, 'reason' => $reason]);
        }

        return $updated > 0;
    }

    /**
     * Reactivate a patron.
     */
    public function reactivate(int $patronId): bool
    {
        $updated = DB::table('library_patron')
            ->where('id', $patronId)
            ->update([
                'borrowing_status' => 'active',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if ($updated) {
            $this->logger->info('Patron reactivated', ['id' => $patronId]);
        }

        return $updated > 0;
    }

    // ========================================================================
    // PATRON ACTIVITY
    // ========================================================================

    /**
     * Get patron's current checkouts.
     */
    public function getCheckouts(int $patronId): array
    {
        return DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('c.patron_id', $patronId)
            ->where('c.checkout_status', 'checked_out')
            ->select([
                'c.*',
                'cp.barcode as copy_barcode',
                'cp.copy_number',
                'li.call_number',
                'li.isbn',
                'ioi.title',
            ])
            ->orderBy('c.due_date')
            ->get()
            ->all();
    }

    /**
     * Get patron's active holds.
     */
    public function getHolds(int $patronId): array
    {
        return DB::table('library_hold as h')
            ->join('library_item as li', 'h.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('h.patron_id', $patronId)
            ->whereIn('h.hold_status', ['pending', 'ready'])
            ->select([
                'h.*',
                'li.call_number',
                'li.isbn',
                'ioi.title',
            ])
            ->orderBy('h.hold_date')
            ->get()
            ->all();
    }

    /**
     * Get patron's outstanding fines.
     */
    public function getFines(int $patronId): array
    {
        return DB::table('library_fine')
            ->where('patron_id', $patronId)
            ->where('fine_status', 'outstanding')
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();
    }

    /**
     * Get patron's checkout history.
     */
    public function getHistory(int $patronId, int $limit = 50): array
    {
        return DB::table('library_checkout as c')
            ->join('library_copy as cp', 'c.copy_id', '=', 'cp.id')
            ->join('library_item as li', 'cp.library_item_id', '=', 'li.id')
            ->leftJoin('information_object as io', 'li.information_object_id', '=', 'io.id')
            ->leftJoin('information_object_i18n as ioi', function ($j) {
                $j->on('io.id', '=', 'ioi.id')->where('ioi.culture', '=', 'en');
            })
            ->where('c.patron_id', $patronId)
            ->select([
                'c.*',
                'cp.barcode as copy_barcode',
                'li.call_number',
                'ioi.title',
            ])
            ->orderBy('c.checkout_date', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    // ========================================================================
    // HELPERS
    // ========================================================================

    /**
     * Generate a unique patron barcode.
     */
    protected function generateBarcode(): string
    {
        do {
            $barcode = 'P' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (DB::table('library_patron')->where('patron_barcode', $barcode)->exists());

        return $barcode;
    }

    /**
     * Get a library setting value.
     */
    protected function getSetting(string $key, string $default = ''): string
    {
        $row = DB::table('library_settings')->where('setting_key', $key)->first();
        return $row->setting_value ?? $default;
    }

    /**
     * Get patron statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_patrons'    => DB::table('library_patron')->count(),
            'active_patrons'   => DB::table('library_patron')->where('borrowing_status', 'active')->count(),
            'suspended'        => DB::table('library_patron')->where('borrowing_status', 'suspended')->count(),
            'expired'          => DB::table('library_patron')
                ->where('expiry_date', '<', date('Y-m-d'))
                ->where('borrowing_status', 'active')
                ->count(),
        ];
    }
}
